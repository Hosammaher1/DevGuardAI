<?php

namespace Tests\Feature\Identity;

use App\Domain\Identity\Role;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MembershipApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_requires_current_server_side_authority_and_rejects_privileged_fields(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $viewer = $this->user('github-api-viewer');
        $this->membership($workspace, $viewer, Role::Viewer);
        $payload = [
            'intended_github_user_id' => 'github-api-invitee',
            'role' => Role::Member->value,
            'expires_at' => CarbonImmutable::now()->addDay()->toIso8601String(),
        ];

        $this->actingAs($viewer)
            ->postJson("/workspaces/{$workspace->getKey()}/invitations", $payload)
            ->assertForbidden()
            ->assertJsonPath('reason', 'invitation_unauthorized');

        $this->actingAs($owner)
            ->postJson("/workspaces/{$workspace->getKey()}/invitations", [
                ...$payload,
                'workspace_id' => (string) str()->ulid(),
                'invited_by_user_id' => $viewer->getKey(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['workspace_id', 'invited_by_user_id']);

        self::assertDatabaseCount('membership_invitations', 0);
    }

    public function test_authorized_invitation_returns_token_once_and_intended_identity_accepts_it(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $intended = $this->user('github-api-intended');
        $substitute = $this->user('github-api-substitute');

        $response = $this->actingAs($owner)
            ->postJson("/workspaces/{$workspace->getKey()}/invitations", [
                'intended_github_user_id' => $intended->github_user_id,
                'role' => Role::Member->value,
                'expires_at' => CarbonImmutable::now()->addDay()->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonStructure(['invitation_id', 'token', 'expires_at']);

        $token = $response->json('token');
        self::assertIsString($token);
        self::assertDatabaseMissing('membership_invitations', ['token_hash' => $token]);

        $this->actingAs($substitute)
            ->postJson('/membership-invitations/accept', ['token' => $token])
            ->assertForbidden()
            ->assertJsonPath('reason', 'invitation_identity_mismatch');

        $this->actingAs($intended)
            ->postJson('/membership-invitations/accept', ['token' => $token])
            ->assertCreated()
            ->assertJsonPath('role', Role::Member->value)
            ->assertJsonPath('workspace_id', $workspace->getKey());
    }

    public function test_role_mutation_uses_route_scope_and_current_nd01_authority(): void
    {
        [$ownerA, $workspaceA] = $this->workspaceWithOwner();
        [$ownerB, $workspaceB] = $this->workspaceWithOwner();
        $memberA = $this->user('github-api-member-a');
        $adminA = $this->user('github-api-admin-a');
        $this->membership($workspaceA, $memberA, Role::Member);
        $this->membership($workspaceA, $adminA, Role::Admin);

        $this->actingAs($ownerB)
            ->patchJson("/workspaces/{$workspaceB->getKey()}/members/{$memberA->getKey()}", ['role' => Role::Admin->value])
            ->assertNotFound();

        $this->actingAs($adminA)
            ->patchJson("/workspaces/{$workspaceA->getKey()}/members/{$memberA->getKey()}", ['role' => Role::Admin->value])
            ->assertForbidden()
            ->assertJsonPath('reason', 'role_change_unauthorized');

        $this->actingAs($ownerA)
            ->patchJson("/workspaces/{$workspaceA->getKey()}/members/{$memberA->getKey()}", ['role' => Role::Admin->value])
            ->assertOk()
            ->assertJsonPath('role', Role::Admin->value);

        self::assertSame(Role::Admin, Membership::query()->where('workspace_id', $workspaceA->getKey())->where('user_id', $memberA->getKey())->firstOrFail()->role);
    }

    public function test_ownership_transfer_requires_owner_confirmation_and_intended_acceptance(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $newOwner = $this->user('github-api-new-owner');
        $admin = $this->user('github-api-transfer-admin');
        $this->membership($workspace, $newOwner, Role::Member);
        $this->membership($workspace, $admin, Role::Admin);

        $this->actingAs($admin)
            ->postJson("/workspaces/{$workspace->getKey()}/ownership-transfers", [
                'intended_new_owner_user_id' => $admin->getKey(),
            ])
            ->assertForbidden()
            ->assertJsonPath('reason', 'ownership_confirmation_unauthorized');

        $response = $this->actingAs($owner)
            ->postJson("/workspaces/{$workspace->getKey()}/ownership-transfers", [
                'intended_new_owner_user_id' => $newOwner->getKey(),
                'current_owner_user_id' => $admin->getKey(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_owner_user_id']);

        $transferId = $this->actingAs($owner)
            ->postJson("/workspaces/{$workspace->getKey()}/ownership-transfers", [
                'intended_new_owner_user_id' => $newOwner->getKey(),
            ])
            ->assertCreated()
            ->json('transfer_id');

        self::assertIsString($transferId);
        self::assertSame(Role::Member, Membership::query()->where('workspace_id', $workspace->getKey())->where('user_id', $newOwner->getKey())->firstOrFail()->role);

        $this->actingAs($admin)
            ->postJson("/ownership-transfers/{$transferId}/accept")
            ->assertForbidden()
            ->assertJsonPath('reason', 'ownership_acceptance_identity_mismatch');

        $this->actingAs($newOwner)
            ->postJson("/ownership-transfers/{$transferId}/accept")
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        self::assertSame(Role::Owner, Membership::query()->where('workspace_id', $workspace->getKey())->where('user_id', $newOwner->getKey())->firstOrFail()->role);
        self::assertSame(Role::Admin, Membership::query()->where('workspace_id', $workspace->getKey())->where('user_id', $owner->getKey())->firstOrFail()->role);
    }

    public function test_membership_removal_revokes_future_authority_and_cannot_remove_an_owner(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $admin = $this->user('github-api-removal-admin');
        $member = $this->user('github-api-removal-member');
        $viewer = $this->user('github-api-removal-viewer');
        $this->membership($workspace, $admin, Role::Admin);
        $this->membership($workspace, $member, Role::Member);
        $this->membership($workspace, $viewer, Role::Viewer);

        $this->actingAs($viewer)
            ->deleteJson("/workspaces/{$workspace->getKey()}/members/{$member->getKey()}")
            ->assertForbidden()
            ->assertJsonPath('reason', 'membership_removal_unauthorized');

        $this->actingAs($admin)
            ->deleteJson("/workspaces/{$workspace->getKey()}/members/{$member->getKey()}")
            ->assertNoContent();

        self::assertFalse(Membership::query()->where('workspace_id', $workspace->getKey())->where('user_id', $member->getKey())->firstOrFail()->active);

        $this->actingAs($owner)
            ->deleteJson("/workspaces/{$workspace->getKey()}/members/{$owner->getKey()}")
            ->assertForbidden()
            ->assertJsonPath('reason', 'owner_change_requires_transfer');
    }

    /** @return array{User, Workspace} */
    private function workspaceWithOwner(): array
    {
        $suffix = str()->random(8);
        $owner = $this->user('github-api-owner-'.$suffix);
        $workspace = Workspace::query()->create([
            'name' => 'API Workspace '.$suffix,
            'personal_owner_user_id' => $owner->getKey(),
        ]);
        $this->membership($workspace, $owner, Role::Owner);

        return [$owner, $workspace];
    }

    private function user(string $githubId): User
    {
        return User::query()->create([
            'name' => 'Synthetic API User',
            'email' => $githubId.'@example.test',
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
