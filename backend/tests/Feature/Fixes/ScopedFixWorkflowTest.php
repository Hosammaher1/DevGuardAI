<?php

namespace Tests\Feature\Fixes;

use App\Domain\Identity\Role;
use App\Models\CreditAccount;
use App\Models\FixPlan;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\Repository;
use App\Models\Scan;
use App\Models\TestResult;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScopedFixWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private User $member;

    private User $viewer;

    private Workspace $workspace;

    private Scan $scan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@fix.test', 'github_user_id' => 'gh-owner']);
        $this->admin = User::query()->create(['name' => 'Admin', 'email' => 'admin@fix.test', 'github_user_id' => 'gh-admin']);
        $this->member = User::query()->create(['name' => 'Member', 'email' => 'member@fix.test', 'github_user_id' => 'gh-member']);
        $this->viewer = User::query()->create(['name' => 'Viewer', 'email' => 'viewer@fix.test', 'github_user_id' => 'gh-viewer']);

        $this->workspace = Workspace::query()->create(['name' => 'Fix Workspace', 'personal_owner_user_id' => $this->owner->id]);

        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->owner->id, 'role' => Role::Owner->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->admin->id, 'role' => Role::Admin->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->member->id, 'role' => Role::Member->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->viewer->id, 'role' => Role::Viewer->value, 'active' => true]);

        $installation = GitHubAppInstallation::query()->create([
            'workspace_id' => $this->workspace->id,
            'installation_id' => 991122,
            'account_login' => 'fix-org',
            'status' => 'active',
        ]);

        $repo = Repository::query()->create([
            'workspace_id' => $this->workspace->id,
            'github_app_installation_id' => $installation->id,
            'github_repo_id' => 445566,
            'name' => 'shopflow',
            'full_name' => 'fix-org/shopflow',
            'owner_login' => 'fix-org',
            'status' => 'connected',
            'default_branch' => 'main',
        ]);

        $this->scan = Scan::query()->create([
            'workspace_id' => $this->workspace->id,
            'repository_id' => $repo->id,
            'status' => 'completed',
            'total_tests' => 3,
            'passed_tests' => 2,
            'failed_tests' => 1,
            'summary' => '1 failed test',
        ]);

        TestResult::query()->create([
            'scan_id' => $this->scan->id,
            'test_name' => 'Checkout Flow',
            'file_path' => 'tests/checkout.spec.js',
            'status' => 'failed',
            'error_message' => 'Element not found: #checkout-button',
            'ai_analysis' => [
                'root_cause' => 'Button #checkout-button was not rendered in cart',
                'failure_category' => 'SELECTOR_MISMATCH',
                'suggested_fix' => 'Add the checkout-button id to cart template',
                'code_patch' => 'page.locator("#checkout-button")',
                'confidence_score' => 0.94,
            ],
        ]);

        // Fix credit account
        CreditAccount::query()->create([
            'workspace_id' => $this->workspace->id,
            'credit_kind' => 'fix',
            'available_credits' => 3,
            'reserved_credits' => 0,
            'consumed_credits' => 0,
        ]);
    }

    public function test_member_can_propose_fix_plan_but_viewer_is_denied(): void
    {
        // Viewer is denied
        $this->actingAs($this->viewer)
            ->postJson("/workspaces/{$this->workspace->id}/scans/{$this->scan->id}/fix-plans")
            ->assertStatus(403);

        // Member proposes fix plan successfully
        $res = $this->actingAs($this->member)
            ->postJson("/workspaces/{$this->workspace->id}/scans/{$this->scan->id}/fix-plans");

        $res->assertStatus(201);
        $plan = $res->json('fix_plan');
        $this->assertSame('proposed', $plan['status']);
        $this->assertSame(1, $plan['fix_credits_required']);
        $this->assertNotEmpty($plan['plan_digest']);
        $this->assertCount(1, $plan['fix_jobs']);
    }

    public function test_owner_and_admin_can_approve_fix_plan_but_member_and_viewer_denied(): void
    {
        // Member creates plan
        $planRes = $this->actingAs($this->member)
            ->postJson("/workspaces/{$this->workspace->id}/scans/{$this->scan->id}/fix-plans");
        $planId = $planRes->json('fix_plan.id');

        // Member cannot approve (ApproveAiFixExecution denied)
        $this->actingAs($this->member)
            ->postJson("/workspaces/{$this->workspace->id}/fix-plans/{$planId}/approvals")
            ->assertStatus(403);

        // Viewer cannot approve
        $this->actingAs($this->viewer)
            ->postJson("/workspaces/{$this->workspace->id}/fix-plans/{$planId}/approvals")
            ->assertStatus(403);

        // Admin approves successfully
        $approveRes = $this->actingAs($this->admin)
            ->postJson("/workspaces/{$this->workspace->id}/fix-plans/{$planId}/approvals");

        $approveRes->assertStatus(200);
        $this->assertSame('approved', $approveRes->json('fix_plan.status'));
        $this->assertSame($this->admin->id, $approveRes->json('fix_plan.approved_by_user_id'));

        // Fix credit consumed
        $this->assertDatabaseHas('credit_accounts', [
            'workspace_id' => $this->workspace->id,
            'credit_kind' => 'fix',
            'available_credits' => 2,
            'consumed_credits' => 1,
        ]);
    }

    public function test_end_to_end_fix_verification_and_draft_pr_publication(): void
    {
        // 1. Propose
        $plan = $this->actingAs($this->owner)
            ->postJson("/workspaces/{$this->workspace->id}/scans/{$this->scan->id}/fix-plans")
            ->json('fix_plan');

        // 2. Approve
        $this->actingAs($this->owner)
            ->postJson("/workspaces/{$this->workspace->id}/fix-plans/{$plan['id']}/approvals")
            ->assertStatus(200);

        // 3. Verify
        $verifyRes = $this->actingAs($this->owner)
            ->postJson("/workspaces/{$this->workspace->id}/fix-plans/{$plan['id']}/verify");

        $verifyRes->assertStatus(200);
        $this->assertSame('verified', $verifyRes->json('fix_plan.status'));

        // 4. Publish Draft PR
        $prRes = $this->actingAs($this->owner)
            ->postJson("/workspaces/{$this->workspace->id}/fix-plans/{$plan['id']}/publish-pr");

        $prRes->assertStatus(200);
        $this->assertSame('published', $prRes->json('status'));
        $this->assertNotNull($prRes->json('pr_url'));
        $this->assertGreaterThan(0, $prRes->json('pr_number'));
        $this->assertStringContainsString('fix-org/shopflow', $prRes->json('pr_url'));
        $this->assertStringContainsString((string) $prRes->json('pr_number'), $prRes->json('pr_url'));
    }

    public function test_approve_plan_is_idempotent_and_prevents_duplicate_credit_deduction(): void
    {
        // 1. Propose plan
        $planRes = $this->actingAs($this->owner)
            ->postJson("/workspaces/{$this->workspace->id}/scans/{$this->scan->id}/fix-plans");
        $planRes->assertStatus(201);
        $plan = $planRes->json('fix_plan');

        // Initial available credits: 3
        $this->assertDatabaseHas('credit_accounts', [
            'workspace_id' => $this->workspace->id,
            'credit_kind' => 'fix',
            'available_credits' => 3,
            'consumed_credits' => 0,
        ]);

        // First approval: deducts 1 credit
        $firstApprove = $this->actingAs($this->owner)
            ->postJson("/workspaces/{$this->workspace->id}/fix-plans/{$plan['id']}/approvals");
        $firstApprove->assertStatus(200);

        $this->assertDatabaseHas('credit_accounts', [
            'workspace_id' => $this->workspace->id,
            'credit_kind' => 'fix',
            'available_credits' => 2,
            'consumed_credits' => 1,
        ]);

        // Second approval (duplicate invocation): should be idempotent and NOT deduct credits again
        $secondApprove = $this->actingAs($this->owner)
            ->postJson("/workspaces/{$this->workspace->id}/fix-plans/{$plan['id']}/approvals");
        $secondApprove->assertStatus(200);

        $this->assertDatabaseHas('credit_accounts', [
            'workspace_id' => $this->workspace->id,
            'credit_kind' => 'fix',
            'available_credits' => 2,
            'consumed_credits' => 1,
        ]);
    }
}
