<?php

namespace App\Domain\Identity;

final class AuthorizationService
{
    /** @var array<string, list<string>> */
    private const GRANTS = [
        Role::Owner->value => [
            WorkspaceAction::ViewProjectsAndReports->value,
            WorkspaceAction::ConnectRepositories->value,
            WorkspaceAction::StartScans->value,
            WorkspaceAction::ViewScanResults->value,
            WorkspaceAction::ProposeAiFixPlans->value,
            WorkspaceAction::ApproveAiFixExecution->value,
            WorkspaceAction::ApproveUnverifiedDraftPr->value,
            WorkspaceAction::CancelOwnJobs->value,
            WorkspaceAction::ManageProjectSettings->value,
            WorkspaceAction::InviteMembers->value,
            WorkspaceAction::RemoveMembersOrViewers->value,
            WorkspaceAction::ChangeMemberOrViewerRoles->value,
            WorkspaceAction::GrantOrRevokeAdmin->value,
            WorkspaceAction::ManageBillingAndSubscriptions->value,
            WorkspaceAction::ManageSensitiveWorkspaceSettings->value,
            WorkspaceAction::TransferWorkspaceOwnership->value,
            WorkspaceAction::DeleteWorkspace->value,
        ],
        Role::Admin->value => [
            WorkspaceAction::ViewProjectsAndReports->value,
            WorkspaceAction::ConnectRepositories->value,
            WorkspaceAction::StartScans->value,
            WorkspaceAction::ViewScanResults->value,
            WorkspaceAction::ProposeAiFixPlans->value,
            WorkspaceAction::ApproveAiFixExecution->value,
            WorkspaceAction::ApproveUnverifiedDraftPr->value,
            WorkspaceAction::CancelOwnJobs->value,
            WorkspaceAction::ManageProjectSettings->value,
            WorkspaceAction::InviteMembers->value,
            WorkspaceAction::RemoveMembersOrViewers->value,
            WorkspaceAction::ChangeMemberOrViewerRoles->value,
        ],
        Role::Member->value => [
            WorkspaceAction::ViewProjectsAndReports->value,
            WorkspaceAction::StartScans->value,
            WorkspaceAction::ViewScanResults->value,
            WorkspaceAction::ProposeAiFixPlans->value,
            WorkspaceAction::CancelOwnJobs->value,
        ],
        Role::Viewer->value => [
            WorkspaceAction::ViewProjectsAndReports->value,
            WorkspaceAction::ViewScanResults->value,
        ],
    ];

    public function decide(
        ?Role $role,
        WorkspaceAction|string $action,
        bool $activeMembership = true,
        bool $resourceAuthorized = true,
    ): AuthorizationDecision {
        if ($role === null) {
            return AuthorizationDecision::deny('membership_missing');
        }

        if (! $activeMembership) {
            return AuthorizationDecision::deny('membership_inactive');
        }

        if (! $resourceAuthorized) {
            return AuthorizationDecision::deny('resource_scope_denied');
        }

        if (is_string($action)) {
            $action = WorkspaceAction::tryFrom($action);
        }

        if ($action === null) {
            return AuthorizationDecision::deny('action_unknown');
        }

        if (! in_array($action->value, self::GRANTS[$role->value] ?? [], true)) {
            return AuthorizationDecision::deny('matrix_denied');
        }

        return AuthorizationDecision::allow();
    }
}
