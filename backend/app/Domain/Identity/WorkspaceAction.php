<?php

namespace App\Domain\Identity;

enum WorkspaceAction: string
{
    case ViewProjectsAndReports = 'view_projects_and_reports';
    case ConnectRepositories = 'connect_repositories';
    case StartScans = 'start_scans';
    case ViewScanResults = 'view_scan_results';
    case ProposeAiFixPlans = 'propose_ai_fix_plans';
    case ApproveAiFixExecution = 'approve_ai_fix_execution';
    case ApproveUnverifiedDraftPr = 'approve_unverified_draft_pr';
    case CancelOwnJobs = 'cancel_own_jobs';
    case ManageProjectSettings = 'manage_project_settings';
    case InviteMembers = 'invite_members';
    case RemoveMembersOrViewers = 'remove_members_or_viewers';
    case ChangeMemberOrViewerRoles = 'change_member_or_viewer_roles';
    case GrantOrRevokeAdmin = 'grant_or_revoke_admin';
    case ManageBillingAndSubscriptions = 'manage_billing_and_subscriptions';
    case ManageSensitiveWorkspaceSettings = 'manage_sensitive_workspace_settings';
    case TransferWorkspaceOwnership = 'transfer_workspace_ownership';
    case DeleteWorkspace = 'delete_workspace';
}
