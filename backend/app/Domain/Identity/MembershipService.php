<?php

namespace App\Domain\Identity;

use App\Domain\Audit\AuthorizationAudit;
use App\Models\Membership;
use App\Models\MembershipInvitation;
use App\Models\OwnershipTransfer;
use App\Models\User;
use App\Models\Workspace;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MembershipService
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly AuthorizationAudit $audit,
    ) {}

    public function issueInvitation(
        int $actorUserId,
        string $workspaceId,
        string $intendedGithubUserId,
        Role $role,
        DateTimeInterface $expiresAt,
    ): IssuedInvitation {
        if (! in_array($role, [Role::Member, Role::Viewer], true)) {
            throw new DomainException('invitation_role_not_permitted');
        }

        if ($intendedGithubUserId === '') {
            throw new DomainException('invitation_identity_required');
        }

        return DB::transaction(function () use ($actorUserId, $workspaceId, $intendedGithubUserId, $role, $expiresAt): IssuedInvitation {
            $this->lockWorkspace($workspaceId);
            $memberships = $this->lockMemberships($workspaceId);
            $actor = $this->activeMembership($memberships, $actorUserId);
            $this->requireAction($actor, WorkspaceAction::InviteMembers, 'invitation_unauthorized');

            $rawToken = $this->newInvitationToken();
            $invitation = MembershipInvitation::query()->create([
                'workspace_id' => $workspaceId,
                'invited_by_user_id' => $actorUserId,
                'intended_github_user_id' => $intendedGithubUserId,
                'role' => $role,
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => $expiresAt,
            ]);

            return new IssuedInvitation($invitation, $rawToken);
        }, 3);
    }

    public function acceptInvitation(string $rawToken, int $acceptorUserId, DateTimeInterface $now): Membership
    {
        $tokenHash = hash('sha256', $rawToken);
        $workspaceId = MembershipInvitation::query()->where('token_hash', $tokenHash)->value('workspace_id');

        if (! is_string($workspaceId)) {
            throw new DomainException('invitation_invalid');
        }

        return DB::transaction(function () use ($tokenHash, $workspaceId, $acceptorUserId, $now): Membership {
            $this->lockWorkspace($workspaceId);
            $invitation = MembershipInvitation::query()
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if ($invitation === null) {
                throw new DomainException('invitation_invalid');
            }
            if ($invitation->revoked_at !== null) {
                throw new DomainException('invitation_revoked');
            }
            if ($invitation->accepted_at !== null) {
                throw new DomainException('invitation_used');
            }
            if ($invitation->expires_at->lessThanOrEqualTo($now)) {
                throw new DomainException('invitation_expired');
            }

            $acceptor = User::query()->find($acceptorUserId);
            if ($acceptor === null || ! hash_equals($invitation->intended_github_user_id, $acceptor->github_user_id)) {
                throw new DomainException('invitation_identity_mismatch');
            }

            $memberships = $this->lockMemberships($workspaceId);
            if ($memberships->contains(fn (Membership $membership): bool => (int) $membership->user_id === $acceptorUserId)) {
                throw new DomainException('membership_already_exists');
            }

            $membership = Membership::query()->create([
                'workspace_id' => $workspaceId,
                'user_id' => $acceptorUserId,
                'role' => $invitation->role,
                'active' => true,
                'version' => 1,
            ]);
            $invitation->update([
                'accepted_at' => $now,
                'accepted_by_user_id' => $acceptorUserId,
            ]);

            return $membership;
        }, 3);
    }

    public function initiateOwnershipTransfer(
        int $actorUserId,
        string $workspaceId,
        int $intendedNewOwnerUserId,
        DateTimeInterface $confirmedAt,
    ): OwnershipTransfer {
        return DB::transaction(function () use ($actorUserId, $workspaceId, $intendedNewOwnerUserId, $confirmedAt): OwnershipTransfer {
            $this->lockWorkspace($workspaceId);
            $memberships = $this->lockMemberships($workspaceId);
            $actor = $this->activeMembership($memberships, $actorUserId);
            $this->requireAction($actor, WorkspaceAction::TransferWorkspaceOwnership, 'ownership_confirmation_unauthorized');

            if ($actorUserId === $intendedNewOwnerUserId) {
                throw new DomainException('ownership_self_transfer_denied');
            }

            $intended = $this->activeMembership($memberships, $intendedNewOwnerUserId);
            if ($intended === null) {
                throw new DomainException('ownership_target_not_active_member');
            }

            return OwnershipTransfer::query()->create([
                'workspace_id' => $workspaceId,
                'current_owner_user_id' => $actorUserId,
                'intended_new_owner_user_id' => $intendedNewOwnerUserId,
                'confirmed_at' => $confirmedAt,
            ]);
        }, 3);
    }

    public function acceptOwnershipTransfer(
        string $transferId,
        int $acceptorUserId,
        DateTimeInterface $acceptedAt,
    ): OwnershipTransfer {
        $workspaceId = OwnershipTransfer::query()->whereKey($transferId)->value('workspace_id');
        if (! is_string($workspaceId)) {
            throw new DomainException('ownership_transfer_invalid');
        }

        return DB::transaction(function () use ($transferId, $workspaceId, $acceptorUserId, $acceptedAt): OwnershipTransfer {
            $workspace = $this->lockWorkspace($workspaceId);
            $transfer = OwnershipTransfer::query()->whereKey($transferId)->lockForUpdate()->first();
            if ($transfer === null || $transfer->confirmed_at === null) {
                throw new DomainException('ownership_confirmation_missing');
            }
            if ($transfer->completed_at !== null) {
                throw new DomainException('ownership_transfer_used');
            }
            if ((int) $transfer->intended_new_owner_user_id !== $acceptorUserId) {
                throw new DomainException('ownership_acceptance_identity_mismatch');
            }

            $memberships = $this->lockMemberships($workspaceId);
            $currentOwner = $this->activeMembership($memberships, (int) $transfer->current_owner_user_id);
            $newOwner = $this->activeMembership($memberships, $acceptorUserId);
            if ($currentOwner?->role !== Role::Owner) {
                throw new DomainException('ownership_current_owner_invalid');
            }
            if ($newOwner === null) {
                throw new DomainException('ownership_target_not_active_member');
            }

            $newOwner->update(['role' => Role::Owner, 'version' => $newOwner->version + 1]);
            $currentOwner->update(['role' => Role::Admin, 'version' => $currentOwner->version + 1]);
            if ($workspace->personal_owner_user_id !== null) {
                $workspace->update(['personal_owner_user_id' => $acceptorUserId]);
            }
            $transfer->update([
                'accepted_at' => $acceptedAt,
                'completed_at' => $acceptedAt,
            ]);

            return $transfer->fresh();
        }, 3);
    }

    public function changeRole(int $actorUserId, string $workspaceId, int $targetUserId, Role $newRole): Membership
    {
        $action = $newRole === Role::Owner
            ? WorkspaceAction::TransferWorkspaceOwnership
            : WorkspaceAction::ChangeMemberOrViewerRoles;
        $roleBefore = null;

        try {
            return DB::transaction(function () use ($actorUserId, $workspaceId, $targetUserId, $newRole, &$action, &$roleBefore): Membership {
                $this->lockWorkspace($workspaceId);
                $memberships = $this->lockMemberships($workspaceId);
                $actor = $this->activeMembership($memberships, $actorUserId);
                $target = $this->activeMembership($memberships, $targetUserId);
                if ($target === null) {
                    throw new DomainException('membership_target_missing');
                }

                $roleBefore = $target->role;
                $action = match (true) {
                    $target->role === Role::Owner || $newRole === Role::Owner => WorkspaceAction::TransferWorkspaceOwnership,
                    $target->role === Role::Admin || $newRole === Role::Admin => WorkspaceAction::GrantOrRevokeAdmin,
                    default => WorkspaceAction::ChangeMemberOrViewerRoles,
                };

                if ($target->role === Role::Owner || $newRole === Role::Owner) {
                    throw new DomainException('owner_change_requires_transfer');
                }

                $this->requireAction($actor, $action, 'role_change_unauthorized');
                $target->update(['role' => $newRole, 'version' => $target->version + 1]);
                $this->recordRoleChangeAudit(
                    $actorUserId,
                    $workspaceId,
                    $targetUserId,
                    $action,
                    true,
                    'allowed',
                    $roleBefore,
                    $newRole,
                );

                return $target;
            }, 3);
        } catch (DomainException $exception) {
            $this->recordRoleChangeAudit(
                $actorUserId,
                $workspaceId,
                $targetUserId,
                $action,
                false,
                $exception->getMessage(),
                $roleBefore,
                $newRole,
            );

            throw $exception;
        }
    }

    public function removeMembership(int $actorUserId, string $workspaceId, int $targetUserId): void
    {
        DB::transaction(function () use ($actorUserId, $workspaceId, $targetUserId): void {
            $this->lockWorkspace($workspaceId);
            $memberships = $this->lockMemberships($workspaceId);
            $actor = $this->activeMembership($memberships, $actorUserId);
            $target = $this->activeMembership($memberships, $targetUserId);
            if ($target === null) {
                throw new DomainException('membership_target_missing');
            }
            if ($target->role === Role::Owner) {
                throw new DomainException('owner_change_requires_transfer');
            }
            if (! in_array($target->role, [Role::Member, Role::Viewer], true)) {
                throw new DomainException('membership_removal_unauthorized');
            }

            $this->requireAction($actor, WorkspaceAction::RemoveMembersOrViewers, 'membership_removal_unauthorized');
            $target->update(['active' => false, 'version' => $target->version + 1]);
        }, 3);
    }

    private function lockWorkspace(string $workspaceId): Workspace
    {
        $workspace = Workspace::query()->whereKey($workspaceId)->lockForUpdate()->first();
        if ($workspace === null) {
            throw new DomainException('workspace_missing');
        }

        return $workspace;
    }

    /** @return Collection<int, Membership> */
    private function lockMemberships(string $workspaceId): Collection
    {
        return Membership::query()
            ->where('workspace_id', $workspaceId)
            ->orderBy('user_id')
            ->lockForUpdate()
            ->get();
    }

    /** @param Collection<int, Membership> $memberships */
    private function activeMembership(Collection $memberships, int $userId): ?Membership
    {
        return $memberships->first(
            fn (Membership $membership): bool => (int) $membership->user_id === $userId && $membership->active,
        );
    }

    private function requireAction(?Membership $membership, WorkspaceAction $action, string $reason): void
    {
        $decision = $this->authorization->decide($membership?->role, $action, $membership?->active ?? false);
        if (! $decision->allowed) {
            throw new DomainException($reason);
        }
    }

    private function newInvitationToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function recordRoleChangeAudit(
        int $actorUserId,
        string $workspaceId,
        int $targetUserId,
        WorkspaceAction $action,
        bool $allowed,
        string $reason,
        ?Role $roleBefore,
        Role $roleAfter,
    ): void {
        $this->audit->record([
            'actor_identifier' => 'user:'.$actorUserId,
            'actor_type' => 'user',
            'workspace_id' => $workspaceId,
            'resource_identifier' => 'membership:user:'.$targetUserId,
            'action' => $action,
            'allowed' => $allowed,
            'reason' => $reason,
            'role_before' => $roleBefore,
            'role_after' => $roleAfter,
            'policy_version' => 'nd01-v1',
            'correlation_id' => (string) Str::ulid(),
            'originating_service' => 'laravel-control-plane',
        ]);
    }
}
