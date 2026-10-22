<?php

namespace Tests\Feature\Identity;

use App\Domain\Identity\CurrentMembershipAuthorization;
use App\Domain\Identity\Role;
use App\Domain\Identity\WorkspaceAction;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CurrentMembershipAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_membership_is_evaluated_against_the_approved_matrix(): void
    {
        [$user, $workspace] = $this->membershipFixture(Role::Member);

        $service = app(CurrentMembershipAuthorization::class);

        self::assertTrue($service->decide(
            $user->getKey(),
            $workspace->getKey(),
            $workspace->getKey(),
            WorkspaceAction::StartScans,
        )->allowed);
        self::assertFalse($service->decide(
            $user->getKey(),
            $workspace->getKey(),
            $workspace->getKey(),
            WorkspaceAction::ApproveAiFixExecution,
        )->allowed);
    }

    public function test_cross_tenant_resource_scope_is_denied(): void
    {
        [$user, $workspaceA] = $this->membershipFixture(Role::Owner);
        $workspaceB = Workspace::query()->create(['name' => 'Synthetic Workspace B']);

        $decision = app(CurrentMembershipAuthorization::class)->decide(
            $user->getKey(),
            $workspaceA->getKey(),
            $workspaceB->getKey(),
            WorkspaceAction::ViewProjectsAndReports,
        );

        self::assertFalse($decision->allowed);
        self::assertSame('resource_scope_denied', $decision->reason);
    }

    public function test_membership_revocation_is_observed_on_the_next_decision(): void
    {
        [$user, $workspace, $membership] = $this->membershipFixture(Role::Owner);
        $service = app(CurrentMembershipAuthorization::class);

        self::assertTrue($service->decide(
            $user->getKey(),
            $workspace->getKey(),
            $workspace->getKey(),
            WorkspaceAction::ManageSensitiveWorkspaceSettings,
        )->allowed);

        $membership->update(['active' => false, 'version' => 2]);

        $decision = $service->decide(
            $user->getKey(),
            $workspace->getKey(),
            $workspace->getKey(),
            WorkspaceAction::ManageSensitiveWorkspaceSettings,
        );

        self::assertFalse($decision->allowed);
        self::assertSame('membership_inactive', $decision->reason);
    }

    public function test_missing_actor_and_missing_membership_fail_closed(): void
    {
        $workspace = Workspace::query()->create(['name' => 'Synthetic Workspace']);
        $user = User::query()->create([
            'name' => 'Synthetic User',
            'email' => 'synthetic@example.test',
            'github_user_id' => 'github-synthetic-missing',
        ]);
        $service = app(CurrentMembershipAuthorization::class);

        self::assertSame('unauthenticated', $service->decide(
            null,
            $workspace->getKey(),
            $workspace->getKey(),
            WorkspaceAction::ViewProjectsAndReports,
        )->reason);
        self::assertSame('membership_missing', $service->decide(
            $user->getKey(),
            $workspace->getKey(),
            $workspace->getKey(),
            WorkspaceAction::ViewProjectsAndReports,
        )->reason);
    }

    public function test_duplicate_workspace_membership_is_rejected(): void
    {
        [$user, $workspace] = $this->membershipFixture(Role::Viewer);

        $this->expectException(QueryException::class);

        Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $user->getKey(),
            'role' => Role::Member,
            'active' => true,
            'version' => 1,
        ]);
    }

    /** @return array{User, Workspace, Membership} */
    private function membershipFixture(Role $role): array
    {
        $user = User::query()->create([
            'name' => 'Synthetic User',
            'email' => 'synthetic-'.$role->value.'@example.test',
            'github_user_id' => 'github-synthetic-'.$role->value,
        ]);
        $workspace = Workspace::query()->create(['name' => 'Synthetic Workspace '.$role->value]);
        $membership = Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $user->getKey(),
            'role' => $role,
            'active' => true,
            'version' => 1,
        ]);

        return [$user, $workspace, $membership];
    }
}
