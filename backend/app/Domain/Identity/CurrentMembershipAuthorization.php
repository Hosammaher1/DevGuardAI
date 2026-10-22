<?php

namespace App\Domain\Identity;

use App\Models\Membership;

final readonly class CurrentMembershipAuthorization
{
    public function __construct(private AuthorizationService $authorization) {}

    public function decide(
        ?int $actorId,
        string $workspaceId,
        string $resourceWorkspaceId,
        WorkspaceAction|string $action,
    ): AuthorizationDecision {
        if ($actorId === null) {
            return AuthorizationDecision::deny('unauthenticated');
        }

        if (! hash_equals($workspaceId, $resourceWorkspaceId)) {
            return AuthorizationDecision::deny('resource_scope_denied');
        }

        $membership = Membership::query()
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $actorId)
            ->first();

        if ($membership === null) {
            return AuthorizationDecision::deny('membership_missing');
        }

        return $this->authorization->decide(
            $membership->role,
            $action,
            $membership->active,
            true,
        );
    }
}
