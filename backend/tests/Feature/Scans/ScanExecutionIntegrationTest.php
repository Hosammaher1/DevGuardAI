<?php

namespace Tests\Feature\Scans;

use App\Domain\Identity\Role;
use App\Models\CreditAccount;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\Repository;
use App\Models\Scan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ScanExecutionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Repository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@scan-exec.test',
            'github_user_id' => 'gh-scan-owner',
        ]);

        $this->workspace = Workspace::query()->create([
            'name' => 'Scan Exec Workspace',
            'personal_owner_user_id' => $this->owner->id,
        ]);

        Membership::query()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->owner->id,
            'role' => Role::Owner->value,
            'active' => true,
        ]);

        $installation = GitHubAppInstallation::query()->create([
            'workspace_id' => $this->workspace->id,
            'installation_id' => 888123,
            'account_login' => 'scan-org',
            'status' => 'active',
        ]);

        $this->repository = Repository::query()->create([
            'workspace_id' => $this->workspace->id,
            'github_app_installation_id' => $installation->id,
            'github_repo_id' => 998811,
            'name' => 'trusted-demo',
            'full_name' => 'scan-org/trusted-demo',
            'owner_login' => 'scan-org',
            'status' => 'connected',
            'default_branch' => 'main',
        ]);

        // Provide scan credits
        CreditAccount::query()->create([
            'workspace_id' => $this->workspace->id,
            'credit_kind' => 'scan',
            'available_credits' => 5,
            'reserved_credits' => 0,
            'consumed_credits' => 0,
        ]);
    }

    public function test_scan_execution_runs_real_suite_and_populates_results(): void
    {
        // Enable runner for this test
        config(['devguard-runner.enabled' => true]);

        // Mock AI agent response so test runs fast and reliably without external network
        Http::fake([
            'http://127.0.0.1:8001/internal/review' => Http::response([
                'root_cause' => 'Button element #checkout-button missing in DOM',
                'failure_category' => 'SELECTOR_MISMATCH',
                'suggested_fix' => 'Add the checkout-button element or adjust selector',
                'code_patch' => 'page.locator("#checkout-button")',
                'confidence_score' => 0.95,
                'model_used' => 'test-mock',
                'is_mock' => false,
                'requires_approval' => true,
            ], 200),
        ]);

        $res = $this->actingAs($this->owner)->postJson("/workspaces/{$this->workspace->id}/scans", [
            'repository_id' => $this->repository->id,
            'trigger' => 'manual',
        ]);

        $res->assertStatus(201);
        $scanData = $res->json('scan');
        $this->assertNotNull($scanData);
        $this->assertSame('completed', $scanData['status']);
        $this->assertGreaterThan(0, $scanData['total_tests']);
        $this->assertGreaterThan(0, $scanData['passed_tests']);
        $this->assertGreaterThan(0, $scanData['failed_tests']);

        $scanId = $scanData['id'];
        $this->assertDatabaseHas('scans', [
            'id' => $scanId,
            'status' => 'completed',
        ]);

        // Verify test results are stored
        $this->assertDatabaseHas('test_results', [
            'scan_id' => $scanId,
            'status' => 'passed',
        ]);

        $this->assertDatabaseHas('test_results', [
            'scan_id' => $scanId,
            'status' => 'failed',
        ]);

        // Verify credit was consumed
        $this->assertDatabaseHas('credit_accounts', [
            'workspace_id' => $this->workspace->id,
            'credit_kind' => 'scan',
            'available_credits' => 4,
            'consumed_credits' => 1,
        ]);
    }

    public function test_scan_admission_denies_when_emergency_containment_active(): void
    {
        config(['devguard-runner.enabled' => true]);

        $containment = app(\App\Domain\Operations\SelectiveContainment::class);
        $containment->disableScans('Operator maintenance');

        $res = $this->actingAs($this->owner)->postJson("/workspaces/{$this->workspace->id}/scans", [
            'repository_id' => $this->repository->id,
        ]);

        $res->assertStatus(503);
        $res->assertJsonPath('reason', 'isolated_runner_unavailable');

        $containment->enableScans();
    }
}
