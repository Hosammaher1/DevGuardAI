<?php

namespace Tests\Feature\Scans;

use App\Domain\Identity\Role;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\Repository;
use App\Models\Scan;
use App\Models\TestResult;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScanLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_admission_fails_closed_and_scan_reads_and_metrics_function_with_stored_data(): void
    {
        $owner = User::query()->create(['github_user_id' => 2001, 'name' => 'Owner', 'email' => 'owner@example.com']);
        $workspace = Workspace::query()->create(['personal_owner_user_id' => $owner->id, 'name' => 'Workspace One']);
        Membership::query()->create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => Role::Owner->value, 'active' => true]);

        $installation = GitHubAppInstallation::query()->create([
            'workspace_id' => $workspace->id,
            'installation_id' => 1001,
            'account_login' => 'org-one',
            'status' => 'active',
        ]);

        $repo = Repository::query()->create([
            'workspace_id' => $workspace->id,
            'github_app_installation_id' => $installation->id,
            'github_repo_id' => 2001,
            'name' => 'repo-one',
            'full_name' => 'org-one/repo-one',
            'owner_login' => 'org-one',
            'is_private' => true,
            'status' => 'connected',
        ]);

        // Attempting scan admission fails closed with 503 isolated_runner_unavailable and creates no scans
        $response = $this->actingAs($owner)->postJson("/workspaces/{$workspace->id}/scans", [
            'repository_id' => $repo->id,
        ]);

        $response->assertStatus(503);
        $response->assertJsonPath('error', 'isolated_runner_unavailable');
        $this->assertDatabaseCount('scans', 0);

        // Verify existing completed scans and metrics read correctly without host demo
        $scan = Scan::query()->create([
            'workspace_id' => $workspace->id,
            'repository_id' => $repo->id,
            'status' => 'completed',
            'total_tests' => 3,
            'passed_tests' => 2,
            'failed_tests' => 1,
            'skipped_tests' => 0,
            'duration_ms' => 4500,
        ]);

        TestResult::query()->create([
            'scan_id' => $scan->id,
            'test_name' => 'test_feature',
            'test_suite' => 'Unit',
            'file_path' => 'tests/Feature/ExampleTest.php',
            'status' => 'failed',
            'duration_ms' => 1500,
            'error_message' => 'Failed',
        ]);
        TestResult::query()->create([
            'scan_id' => $scan->id,
            'test_name' => 'test_pass_1',
            'test_suite' => 'Unit',
            'file_path' => 'tests/Feature/ExampleTest.php',
            'status' => 'passed',
            'duration_ms' => 1500,
        ]);
        TestResult::query()->create([
            'scan_id' => $scan->id,
            'test_name' => 'test_pass_2',
            'test_suite' => 'Unit',
            'file_path' => 'tests/Feature/ExampleTest.php',
            'status' => 'passed',
            'duration_ms' => 1500,
        ]);

        // View scan details
        $showResponse = $this->actingAs($owner)->getJson("/workspaces/{$workspace->id}/scans/{$scan->id}");
        $showResponse->assertOk();
        $showResponse->assertJsonPath('scan.id', $scan->id);
        $this->assertCount(3, $showResponse->json('scan.test_results'));

        // View workspace metrics
        $metricsResponse = $this->actingAs($owner)->getJson("/workspaces/{$workspace->id}/metrics");
        $metricsResponse->assertOk();
        $metricsResponse->assertJsonPath('metrics.total_scans', 1);
        $metricsResponse->assertJsonPath('metrics.total_tests', 3);
        $metricsResponse->assertJsonPath('metrics.total_passed', 2);
        $metricsResponse->assertJsonPath('metrics.total_failed', 1);
    }

    public function test_viewer_is_denied_from_starting_scan(): void
    {
        $owner = User::query()->create(['github_user_id' => 2002, 'name' => 'Owner', 'email' => 'owner2@example.com']);
        $viewer = User::query()->create(['github_user_id' => 2003, 'name' => 'Viewer', 'email' => 'viewer@example.com']);
        $workspace = Workspace::query()->create(['personal_owner_user_id' => $owner->id, 'name' => 'Workspace Two']);

        Membership::query()->create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => Role::Owner->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $workspace->id, 'user_id' => $viewer->id, 'role' => Role::Viewer->value, 'active' => true]);

        $response = $this->actingAs($viewer)->postJson("/workspaces/{$workspace->id}/scans");
        $response->assertStatus(403);
    }

    public function test_cross_tenant_scan_access_is_forbidden(): void
    {
        $userA = User::query()->create(['github_user_id' => 2004, 'name' => 'User A', 'email' => 'usera@example.com']);
        $userB = User::query()->create(['github_user_id' => 2005, 'name' => 'User B', 'email' => 'userb@example.com']);

        $wsA = Workspace::query()->create(['personal_owner_user_id' => $userA->id, 'name' => 'Workspace A']);
        $wsB = Workspace::query()->create(['personal_owner_user_id' => $userB->id, 'name' => 'Workspace B']);

        Membership::query()->create(['workspace_id' => $wsA->id, 'user_id' => $userA->id, 'role' => Role::Owner->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $wsB->id, 'user_id' => $userB->id, 'role' => Role::Owner->value, 'active' => true]);

        $installA = GitHubAppInstallation::query()->create([
            'workspace_id' => $wsA->id,
            'installation_id' => 101,
            'account_login' => 'org-a',
        ]);

        $repoA = Repository::query()->create([
            'workspace_id' => $wsA->id,
            'github_app_installation_id' => $installA->id,
            'github_repo_id' => 201,
            'name' => 'repo-a',
            'full_name' => 'org-a/repo-a',
            'owner_login' => 'org-a',
        ]);

        $scanA = Scan::query()->create([
            'workspace_id' => $wsA->id,
            'repository_id' => $repoA->id,
            'status' => 'completed',
        ]);

        // User B attempts to access User A's scan
        $response = $this->actingAs($userB)->getJson("/workspaces/{$wsA->id}/scans/{$scanA->id}");
        $response->assertStatus(403);
    }
}
