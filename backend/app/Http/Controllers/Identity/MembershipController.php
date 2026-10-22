<?php

namespace App\Http\Controllers\Identity;

use App\Domain\Identity\MembershipService;
use App\Domain\Identity\Role;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class MembershipController extends Controller
{
    public function storeInvitation(Request $request, Workspace $workspace, MembershipService $memberships): JsonResponse
    {
        $validated = $request->validate([
            'intended_github_user_id' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in([Role::Member->value, Role::Viewer->value])],
            'expires_at' => ['required', 'date', 'after:now'],
            'workspace_id' => ['prohibited'],
            'invited_by_user_id' => ['prohibited'],
            'active' => ['prohibited'],
            'version' => ['prohibited'],
        ]);

        try {
            $issued = $memberships->issueInvitation(
                (int) $request->user()->getKey(),
                $workspace->getKey(),
                $validated['intended_github_user_id'],
                Role::from($validated['role']),
                CarbonImmutable::parse($validated['expires_at']),
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'invitation_id' => $issued->invitation->getKey(),
            'token' => $issued->rawToken,
            'expires_at' => $issued->invitation->expires_at->toIso8601String(),
        ], 201);
    }

    public function acceptInvitation(Request $request, MembershipService $memberships): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:128'],
            'workspace_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
        ]);

        try {
            $membership = $memberships->acceptInvitation(
                $validated['token'],
                (int) $request->user()->getKey(),
                CarbonImmutable::now(),
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'workspace_id' => $membership->workspace_id,
            'role' => $membership->role->value,
            'active' => $membership->active,
        ], 201);
    }

    public function updateRole(
        Request $request,
        Workspace $workspace,
        int $user,
        MembershipService $memberships,
    ): JsonResponse {
        $validated = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
            'workspace_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'active' => ['prohibited'],
            'version' => ['prohibited'],
        ]);

        try {
            $membership = $memberships->changeRole(
                (int) $request->user()->getKey(),
                $workspace->getKey(),
                $user,
                Role::from($validated['role']),
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'workspace_id' => $membership->workspace_id,
            'user_id' => $membership->user_id,
            'role' => $membership->role->value,
            'version' => $membership->version,
        ]);
    }

    public function destroyMembership(
        Request $request,
        Workspace $workspace,
        int $user,
        MembershipService $memberships,
    ): JsonResponse {
        try {
            $memberships->removeMembership(
                (int) $request->user()->getKey(),
                $workspace->getKey(),
                $user,
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json(status: 204);
    }

    public function storeOwnershipTransfer(
        Request $request,
        Workspace $workspace,
        MembershipService $memberships,
    ): JsonResponse {
        $validated = $request->validate([
            'intended_new_owner_user_id' => ['required', 'integer'],
            'workspace_id' => ['prohibited'],
            'current_owner_user_id' => ['prohibited'],
            'confirmed_at' => ['prohibited'],
            'accepted_at' => ['prohibited'],
            'completed_at' => ['prohibited'],
        ]);

        try {
            $transfer = $memberships->initiateOwnershipTransfer(
                (int) $request->user()->getKey(),
                $workspace->getKey(),
                (int) $validated['intended_new_owner_user_id'],
                CarbonImmutable::now(),
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'transfer_id' => $transfer->getKey(),
            'status' => 'awaiting_acceptance',
        ], 201);
    }

    public function acceptOwnershipTransfer(
        Request $request,
        string $transfer,
        MembershipService $memberships,
    ): JsonResponse {
        $request->validate([
            'workspace_id' => ['prohibited'],
            'current_owner_user_id' => ['prohibited'],
            'intended_new_owner_user_id' => ['prohibited'],
            'confirmed_at' => ['prohibited'],
            'accepted_at' => ['prohibited'],
            'completed_at' => ['prohibited'],
        ]);

        try {
            $completed = $memberships->acceptOwnershipTransfer(
                $transfer,
                (int) $request->user()->getKey(),
                CarbonImmutable::now(),
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'transfer_id' => $completed->getKey(),
            'status' => 'completed',
        ]);
    }

    private function domainError(DomainException $exception): JsonResponse
    {
        $reason = $exception->getMessage();
        $status = match ($reason) {
            'membership_target_missing', 'invitation_invalid', 'ownership_transfer_invalid' => 404,
            'invitation_expired', 'invitation_revoked', 'invitation_used', 'membership_already_exists', 'ownership_transfer_used' => 409,
            default => 403,
        };

        return response()->json(['reason' => $reason], $status);
    }
}
