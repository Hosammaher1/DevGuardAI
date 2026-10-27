<?php

namespace Tests\Feature\Integration;

use App\Domain\Identity\Role;
use App\Models\CreditAccount;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\Repository;
use App\Models\Scan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class MvpJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_mvp_user_journey_from_scan_to_draft_pr(): void
    {
        config(['devguard-runner.enabled' => true]);

        // Mock internal AI review endpoint
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

        // Step 1: User & Workspace Setup
        $owner = User::query()->create(['name' => 'Alice Founder', 'email' => 'alice@shopflow.io', 'github_user_id' => 'gh-alice']);
        $admin = User::query()->create(['name' => 'Bob Lead', 'email' => 'bob@shopflow.io', 'github_user_id' => 'gh-bob']);
        $viewer = User::query()->create(['name' => 'Charlie Stakeholder', 'email' => 'charlie@shopflow.io', 'github_user_id' => 'gh-charlie']);

        $workspace = Workspace::query()->create(['name' => 'ShopFlow Prod', 'personal_owner_user_id' => $owner->id]);

        Membership::query()->create(['workspace_id' => $workspace->id, 'user_id' => $owner->id, 'role' => Role::Owner->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $workspace->id, 'user_id' => $admin->id, 'role' => Role::Admin->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $workspace->id, 'user_id' => $viewer->id, 'role' => Role::Viewer->value, 'active' => true]);

        CreditAccount::query()->create(['workspace_id' => $workspace->id, 'credit_kind' => 'scan', 'available_credits' => 10]);
        CreditAccount::query()->create(['workspace_id' => $workspace->id, 'credit_kind' => 'fix', 'available_credits' => 5]);

        // Step 2: GitHub App Installation & Repository Binding
        $installation = GitHubAppInstallation::query()->create([
            'workspace_id' => $workspace->id,
            'installation_id' => 554433,
            'account_login' => 'shopflow-corp',
            'status' => 'active',
        ]);

        $repo = Repository::query()->create([
            'workspace_id' => $workspace->id,
            'github_app_installation_id' => $installation->id,
            'github_repo_id' => 880011,
            'name' => 'shopflow',
            'full_name' => 'shopflow-corp/shopflow',
            'owner_login' => 'shopflow-corp',
            'status' => 'connected',
            'default_branch' => 'main',
        ]);

        // Step 3: Trigger Scan (Viewer fails 403, Owner succeeds 201)
        $this->actingAs($viewer)
            ->postJson("/workspaces/{$workspace->id}/scans", ['repository_id' => $repo->id])
            ->assertStatus(403);

        $scanRes = $this->actingAs($owner)
            ->postJson("/workspaces/{$workspace->id}/scans", ['repository_id' => $repo->id, 'trigger' => 'manual']);

        $scanRes->assertStatus(201);
        $scan = $scanRes->json('scan');
        $this->assertSame('completed', $scan['status']);
        $this->assertGreaterThan(0, $scan['total_tests']);

        // Step 4: Scan Report & Evidence Verification
        $reportRes = $this->actingAs($owner)
            ->getJson("/workspaces/{$workspace->id}/scans/{$scan['id']}/report");
        $reportRes->assertStatus(200);
        $this->assertNotEmpty($reportRes->json('findings'));

        // Step 5: Propose AI Fix Plan
        $planRes = $this->actingAs($owner)
            ->postJson("/workspaces/{$workspace->id}/scans/{$scan['id']}/fix-plans");
        $planRes->assertStatus(201);
        $planId = $planRes->json('fix_plan.id');

        // Step 6: Approve Fix Plan (Admin approves, deducting 1 fix credit)
        $approveRes = $this->actingAs($admin)
            ->postJson("/workspaces/{$workspace->id}/fix-plans/{$planId}/approvals");
        $approveRes->assertStatus(200);
        $this->assertSame('approved', $approveRes->json('fix_plan.status'));

        // Step 7: Verify Patch in Fresh Isolation
        $verifyRes = $this->actingAs($admin)
            ->postJson("/workspaces/{$workspace->id}/fix-plans/{$planId}/verify");
        $verifyRes->assertStatus(200);
        $this->assertSame('verified', $verifyRes->json('fix_plan.status'));

        // Step 8: Publish Verified Draft PR to GitHub
        $prRes = $this->actingAs($owner)
            ->postJson("/workspaces/{$workspace->id}/fix-plans/{$planId}/publish-pr");
        $prRes->assertStatus(200);
        $this->assertSame('published', $prRes->json('status'));
        $this->assertStringContainsString('shopflow-corp/shopflow/pull/', $prRes->json('pr_url'));
        $this->assertGreaterThan(0, $prRes->json('pr_number'));

        // Step 9: Verify Usage Accounting & Credit Ledger
        $usageRes = $this->actingAs($owner)
            ->getJson("/workspaces/{$workspace->id}/usage");
        $usageRes->assertStatus(200);
        $this->assertSame(9, $usageRes->json('usage.scan_credits.available'));
        $this->assertSame(4, $usageRes->json('usage.fix_credits.available'));
    }
}
