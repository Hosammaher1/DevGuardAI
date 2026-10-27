<?php

namespace Tests\Feature\Reports;

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

final class ReportCoverageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Scan $scan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@report.io', 'github_user_id' => 'gh-owner']);
        $this->workspace = Workspace::query()->create(['name' => 'Report Workspace', 'personal_owner_user_id' => $this->owner->id]);

        Membership::query()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->owner->id,
            'role' => Role::Owner->value,
            'active' => true,
        ]);

        $installation = GitHubAppInstallation::query()->create([
            'workspace_id' => $this->workspace->id,
            'installation_id' => 123000,
            'account_login' => 'report-org',
            'status' => 'active',
        ]);

        $repo = Repository::query()->create([
            'workspace_id' => $this->workspace->id,
            'github_app_installation_id' => $installation->id,
            'github_repo_id' => 771122,
            'name' => 'report-repo',
            'full_name' => 'report-org/report-repo',
            'owner_login' => 'report-org',
            'status' => 'connected',
            'default_branch' => 'main',
        ]);

        $this->scan = Scan::query()->create([
            'workspace_id' => $this->workspace->id,
            'repository_id' => $repo->id,
            'status' => 'completed',
            'total_tests' => 2,
            'passed_tests' => 1,
            'failed_tests' => 1,
            'duration_ms' => 4500,
            'summary' => '1 passed, 1 failed',
        ]);

        TestResult::query()->create([
            'scan_id' => $this->scan->id,
            'test_name' => 'Homepage Test',
            'file_path' => 'tests/home.spec.js',
            'status' => 'passed',
            'duration_ms' => 1200,
        ]);

        TestResult::query()->create([
            'scan_id' => $this->scan->id,
            'test_name' => 'Checkout Flow',
            'file_path' => 'tests/checkout.spec.js',
            'status' => 'failed',
            'duration_ms' => 3300,
            'error_message' => 'Element not found: #checkout-button',
        ]);
    }

    public function test_scan_report_json_returns_complete_structured_data(): void
    {
        $res = $this->actingAs($this->owner)
            ->getJson("/workspaces/{$this->workspace->id}/scans/{$this->scan->id}/report");

        $res->assertStatus(200);
        $res->assertJsonPath('scan_id', $this->scan->id);
        $res->assertJsonPath('metrics.total_tests', 2);
        $res->assertJsonPath('metrics.passed_tests', 1);
        $res->assertJsonPath('metrics.failed_tests', 1);
        $this->assertEquals(50.0, $res->json('metrics.pass_rate'));
        $this->assertCount(2, $res->json('findings'));
    }

    public function test_scan_export_html_returns_sanitized_rendered_document(): void
    {
        $res = $this->actingAs($this->owner)
            ->get("/workspaces/{$this->workspace->id}/scans/{$this->scan->id}/export?format=html");

        $res->assertStatus(200);
        $res->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('DevGuard Security & QA Scan Report', $res->getContent());
        $this->assertStringContainsString('report-org/report-repo', $res->getContent());
        $this->assertStringContainsString('Checkout Flow', $res->getContent());
    }

    public function test_cross_tenant_report_access_is_forbidden(): void
    {
        $tenantB = Workspace::query()->create(['name' => 'Tenant B']);
        $userB = User::query()->create(['name' => 'User B', 'email' => 'b@test.io', 'github_user_id' => 'gh-b']);
        Membership::query()->create([
            'workspace_id' => $tenantB->id,
            'user_id' => $userB->id,
            'role' => Role::Owner->value,
            'active' => true,
        ]);

        $this->actingAs($userB)
            ->getJson("/workspaces/{$tenantB->id}/scans/{$this->scan->id}/report")
            ->assertStatus(404);
    }
}
