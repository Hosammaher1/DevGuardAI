<?php

namespace Tests\Unit\Domain\Identity;

use App\Domain\Identity\AuthorizationService;
use App\Domain\Identity\Role;
use App\Domain\Identity\WorkspaceAction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthorizationMatrixTest extends TestCase
{
    public function test_role_enum_contains_exactly_the_four_approved_roles(): void
    {
        self::assertSame(
            ['owner', 'admin', 'member', 'viewer'],
            array_map(static fn (Role $role): string => $role->value, Role::cases()),
        );
        self::assertNull(Role::tryFrom('platform_operator'));
    }

    #[DataProvider('matrixProvider')]
    public function test_every_role_action_cell_matches_the_approved_matrix(
        Role $role,
        WorkspaceAction $action,
        bool $expected,
    ): void {
        $decision = (new AuthorizationService)->decide($role, $action);

        self::assertSame($expected, $decision->allowed);
        self::assertSame($expected ? 'allowed' : 'matrix_denied', $decision->reason);
    }

    public function test_unspecified_actions_are_denied(): void
    {
        $decision = (new AuthorizationService)->decide(Role::Owner, 'unspecified_action');

        self::assertFalse($decision->allowed);
        self::assertSame('action_unknown', $decision->reason);
    }

    #[DataProvider('invalidScopeProvider')]
    public function test_current_active_membership_and_resource_scope_are_required(
        ?Role $role,
        bool $activeMembership,
        bool $resourceAuthorized,
        string $reason,
    ): void {
        $decision = (new AuthorizationService)->decide(
            $role,
            WorkspaceAction::ViewProjectsAndReports,
            $activeMembership,
            $resourceAuthorized,
        );

        self::assertFalse($decision->allowed);
        self::assertSame($reason, $decision->reason);
    }

    /** @return iterable<string, array{Role, WorkspaceAction, bool}> */
    public static function matrixProvider(): iterable
    {
        $admin = [
            WorkspaceAction::ViewProjectsAndReports,
            WorkspaceAction::ConnectRepositories,
            WorkspaceAction::StartScans,
            WorkspaceAction::ViewScanResults,
            WorkspaceAction::ProposeAiFixPlans,
            WorkspaceAction::ApproveAiFixExecution,
            WorkspaceAction::ApproveUnverifiedDraftPr,
            WorkspaceAction::CancelOwnJobs,
            WorkspaceAction::ManageProjectSettings,
            WorkspaceAction::InviteMembers,
            WorkspaceAction::RemoveMembersOrViewers,
            WorkspaceAction::ChangeMemberOrViewerRoles,
        ];
        $member = [
            WorkspaceAction::ViewProjectsAndReports,
            WorkspaceAction::StartScans,
            WorkspaceAction::ViewScanResults,
            WorkspaceAction::ProposeAiFixPlans,
            WorkspaceAction::CancelOwnJobs,
        ];
        $viewer = [
            WorkspaceAction::ViewProjectsAndReports,
            WorkspaceAction::ViewScanResults,
        ];

        foreach (Role::cases() as $role) {
            foreach (WorkspaceAction::cases() as $action) {
                $allowed = match ($role) {
                    Role::Owner => true,
                    Role::Admin => in_array($action, $admin, true),
                    Role::Member => in_array($action, $member, true),
                    Role::Viewer => in_array($action, $viewer, true),
                };

                yield $role->value.' / '.$action->value => [$role, $action, $allowed];
            }
        }
    }

    /** @return iterable<string, array{?Role, bool, bool, string}> */
    public static function invalidScopeProvider(): iterable
    {
        yield 'missing membership' => [null, false, false, 'membership_missing'];
        yield 'revoked membership' => [Role::Owner, false, true, 'membership_inactive'];
        yield 'resource outside workspace scope' => [Role::Owner, true, false, 'resource_scope_denied'];
    }
}
