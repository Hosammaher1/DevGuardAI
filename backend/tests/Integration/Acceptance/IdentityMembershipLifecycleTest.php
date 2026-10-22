<?php

namespace Tests\Integration\Acceptance;

use App\Domain\Identity\MembershipService;
use App\Domain\Identity\Role;
use App\Models\AuditEvent;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IdentityMembershipLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        self::assertSame('pgsql', config('database.default'));
        self::assertSame('devguard_test', config('database.connections.pgsql.database'));
    }

    public function test_dg_iam_017_invitation_is_identity_bound_and_grants_only_the_approved_role(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $intended = $this->user('github-intended', 'intended@example.test');
        $substitute = $this->user('github-substitute', 'substitute@example.test');
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $service = app(MembershipService::class);

        $issued = $service->issueInvitation(
            $owner->getKey(),
            $workspace->getKey(),
            $intended->github_user_id,
            Role::Member,
            $now->addDay(),
        );

        self::assertFalse(Membership::query()->where('user_id', $intended->getKey())->where('workspace_id', $workspace->getKey())->exists());
        self::assertNotSame($issued->rawToken, $issued->invitation->token_hash);

        try {
            $service->acceptInvitation($issued->rawToken, $substitute->getKey(), $now);
            self::fail('A substituted identity must not accept an invitation.');
        } catch (DomainException $exception) {
            self::assertSame('invitation_identity_mismatch', $exception->getMessage());
        }

        $membership = $service->acceptInvitation($issued->rawToken, $intended->getKey(), $now);

        self::assertSame(Role::Member, $membership->role);
        self::assertTrue($membership->active);
        self::assertSame($workspace->getKey(), $membership->workspace_id);
        self::assertSame($intended->getKey(), $membership->user_id);
    }

    public function test_dg_iam_018_expired_revoked_and_used_invitations_have_no_additional_effect(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $service = app(MembershipService::class);

        $expiredUser = $this->user('github-expired', 'expired@example.test');
        $expired = $service->issueInvitation($owner->getKey(), $workspace->getKey(), $expiredUser->github_user_id, Role::Viewer, $now->subSecond());
        $this->assertInvitationRejected($service, $expired->rawToken, $expiredUser, $now, 'invitation_expired');

        $revokedUser = $this->user('github-revoked', 'revoked@example.test');
        $revoked = $service->issueInvitation($owner->getKey(), $workspace->getKey(), $revokedUser->github_user_id, Role::Viewer, $now->addHour());
        $revoked->invitation->update(['revoked_at' => $now]);
        $this->assertInvitationRejected($service, $revoked->rawToken, $revokedUser, $now, 'invitation_revoked');

        $acceptedUser = $this->user('github-used', 'used@example.test');
        $used = $service->issueInvitation($owner->getKey(), $workspace->getKey(), $acceptedUser->github_user_id, Role::Member, $now->addHour());
        $storedExpiry = $used->invitation->fresh()->expires_at;
        self::assertSame('2026-10-22T11:00:00+00:00', $storedExpiry->toIso8601String());
        self::assertFalse($storedExpiry->lessThanOrEqualTo($now));
        $service->acceptInvitation($used->rawToken, $acceptedUser->getKey(), $now);
        $this->assertInvitationRejected($service, $used->rawToken, $acceptedUser, $now, 'invitation_used');

        self::assertSame(1, Membership::query()->where('workspace_id', $workspace->getKey())->where('user_id', $acceptedUser->getKey())->count());
    }

    public function test_dg_iam_019_ownership_changes_only_after_owner_confirmation_and_intended_acceptance(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $newOwner = $this->user('github-new-owner', 'new-owner@example.test');
        $attacker = $this->user('github-attacker', 'attacker@example.test');
        $this->membership($workspace, $newOwner, Role::Member);
        $this->membership($workspace, $attacker, Role::Admin);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $service = app(MembershipService::class);

        try {
            $service->initiateOwnershipTransfer($attacker->getKey(), $workspace->getKey(), $attacker->getKey(), $now);
            self::fail('An Admin must not appoint itself Owner.');
        } catch (DomainException $exception) {
            self::assertSame('ownership_confirmation_unauthorized', $exception->getMessage());
        }

        $transfer = $service->initiateOwnershipTransfer($owner->getKey(), $workspace->getKey(), $newOwner->getKey(), $now);

        try {
            $service->acceptOwnershipTransfer($transfer->getKey(), $attacker->getKey(), $now->addMinute());
            self::fail('Only the intended new Owner may accept.');
        } catch (DomainException $exception) {
            self::assertSame('ownership_acceptance_identity_mismatch', $exception->getMessage());
        }

        $service->acceptOwnershipTransfer($transfer->getKey(), $newOwner->getKey(), $now->addMinute());

        self::assertSame(Role::Admin, Membership::query()->where('workspace_id', $workspace->getKey())->where('user_id', $owner->getKey())->firstOrFail()->role);
        self::assertSame(Role::Owner, Membership::query()->where('workspace_id', $workspace->getKey())->where('user_id', $newOwner->getKey())->firstOrFail()->role);
        self::assertSame($newOwner->getKey(), $workspace->fresh()->personal_owner_user_id);
    }

    public function test_dg_iam_020_last_owner_cannot_be_removed_or_demoted_outside_completed_transfer(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $member = $this->user('github-member', 'member@example.test');
        $this->membership($workspace, $member, Role::Member);
        $service = app(MembershipService::class);

        foreach ([Role::Admin, Role::Member, Role::Viewer] as $role) {
            try {
                $service->changeRole($owner->getKey(), $workspace->getKey(), $owner->getKey(), $role);
                self::fail('The last Owner must not be demoted.');
            } catch (DomainException $exception) {
                self::assertSame('owner_change_requires_transfer', $exception->getMessage());
            }
        }

        try {
            $service->removeMembership($owner->getKey(), $workspace->getKey(), $owner->getKey());
            self::fail('The last Owner must not be removed.');
        } catch (DomainException $exception) {
            self::assertSame('owner_change_requires_transfer', $exception->getMessage());
        }

        self::assertSame(1, Membership::query()->where('workspace_id', $workspace->getKey())->where('role', Role::Owner->value)->where('active', true)->count());
    }

    public function test_dg_iam_021_role_changes_and_denied_escalation_are_audited(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $member = $this->user('github-audit-member', 'audit-member@example.test');
        $admin = $this->user('github-audit-admin', 'audit-admin@example.test');
        $this->membership($workspace, $member, Role::Member);
        $this->membership($workspace, $admin, Role::Admin);
        $service = app(MembershipService::class);

        $service->changeRole($owner->getKey(), $workspace->getKey(), $member->getKey(), Role::Admin);

        try {
            $service->changeRole($admin->getKey(), $workspace->getKey(), $admin->getKey(), Role::Owner);
            self::fail('An Admin must not self-promote to Owner.');
        } catch (DomainException $exception) {
            self::assertSame('owner_change_requires_transfer', $exception->getMessage());
        }

        $events = AuditEvent::query()->where('workspace_id', $workspace->getKey())->orderBy('created_at')->get();
        self::assertCount(2, $events);
        self::assertTrue($events[0]->allowed);
        self::assertSame('member', $events[0]->role_before);
        self::assertSame('admin', $events[0]->role_after);
        self::assertFalse($events[1]->allowed);
        self::assertSame('owner_change_requires_transfer', $events[1]->reason);
        self::assertSame('user:'.$admin->getKey(), $events[1]->actor_identifier);
    }

    private function assertInvitationRejected(MembershipService $service, string $token, User $user, CarbonImmutable $now, string $reason): void
    {
        try {
            $service->acceptInvitation($token, $user->getKey(), $now);
            self::fail("Expected invitation rejection: {$reason}");
        } catch (DomainException $exception) {
            self::assertSame($reason, $exception->getMessage());
        }
    }

    /** @return array{User, Workspace} */
    private function workspaceWithOwner(): array
    {
        $owner = $this->user('github-owner-'.str()->random(8), 'owner-'.str()->random(8).'@example.test');
        $workspace = Workspace::query()->create([
            'name' => 'Synthetic Workspace',
            'personal_owner_user_id' => $owner->getKey(),
        ]);
        $this->membership($workspace, $owner, Role::Owner);

        return [$owner, $workspace];
    }

    private function user(string $githubId, string $email): User
    {
        return User::query()->create([
            'name' => 'Synthetic User',
            'email' => $email,
            'github_user_id' => $githubId,
        ]);
    }

    private function membership(Workspace $workspace, User $user, Role $role): Membership
    {
        return Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $user->getKey(),
            'role' => $role,
            'active' => true,
            'version' => 1,
        ]);
    }
}
