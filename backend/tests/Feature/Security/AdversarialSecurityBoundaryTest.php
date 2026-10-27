<?php

namespace Tests\Feature\Security;

use App\Domain\Identity\Role;
use App\Domain\Operations\SelectiveContainment;
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

final class AdversarialSecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantOwnerA;

    private Workspace $workspaceA;

    private User $tenantOwnerB;

    private Workspace $workspaceB;

    private Scan $scanA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantOwnerA = User::query()->create(['name' => 'Owner A', 'email' => 'a@tenant.test', 'github_user_id' => 'gh-a']);
        $this->workspaceA = Workspace::query()->create(['name' => 'Tenant A Workspace', 'personal_owner_user_id' => $this->tenantOwnerA->id]);
        Membership::query()->create(['workspace_id' => $this->workspaceA->id, 'user_id' => $this->tenantOwnerA->id, 'role' => Role::Owner->value, 'active' => true]);

        $this->tenantOwnerB = User::query()->create(['name' => 'Owner B', 'email' => 'b@tenant.test', 'github_user_id' => 'gh-b']);
        $this->workspaceB = Workspace::query()->create(['name' => 'Tenant B Workspace', 'personal_owner_user_id' => $this->tenantOwnerB->id]);
        Membership::query()->create(['workspace_id' => $this->workspaceB->id, 'user_id' => $this->tenantOwnerB->id, 'role' => Role::Owner->value, 'active' => true]);

        $installA = GitHubAppInstallation::query()->create(['workspace_id' => $this->workspaceA->id, 'installation_id' => 101, 'account_login' => 'org-a', 'status' => 'active']);
        $repoA = Repository::query()->create(['workspace_id' => $this->workspaceA->id, 'github_app_installation_id' => $installA->id, 'github_repo_id' => 1001, 'name' => 'repo-a', 'full_name' => 'org-a/repo-a', 'owner_login' => 'org-a', 'status' => 'connected']);

        $this->scanA = Scan::query()->create(['workspace_id' => $this->workspaceA->id, 'repository_id' => $repoA->id, 'status' => 'completed', 'total_tests' => 2, 'passed_tests' => 1, 'failed_tests' => 1]);
    }

    public function test_tenant_b_cannot_access_tenant_a_scans_reports_or_fix_plans(): void
    {
        // Scan report
        $this->actingAs($this->tenantOwnerB)
            ->getJson("/workspaces/{$this->workspaceB->id}/scans/{$this->scanA->id}/report")
            ->assertStatus(404);

        // Fix plans creation on foreign scan
        $this->actingAs($this->tenantOwnerB)
            ->postJson("/workspaces/{$this->workspaceB->id}/scans/{$this->scanA->id}/fix-plans")
            ->assertStatus(404);
    }

    public function test_revoked_membership_fails_closed_immediately(): void
    {
        $member = User::query()->create(['name' => 'Revoked Member', 'email' => 'revoked@a.test', 'github_user_id' => 'gh-rev']);
        $membership = Membership::query()->create([
            'workspace_id' => $this->workspaceA->id,
            'user_id' => $member->id,
            'role' => Role::Member->value,
            'active' => true,
        ]);

        // Active member can view
        $this->actingAs($member)->getJson("/workspaces/{$this->workspaceA->id}/scans")->assertStatus(200);

        // Revoke membership
        $membership->update(['active' => false]);

        // Revoked member is immediately denied 403
        $this->actingAs($member)->getJson("/workspaces/{$this->workspaceA->id}/scans")->assertStatus(403);
    }

    public function test_emergency_containment_stops_effects_server_side(): void
    {
        $containment = app(SelectiveContainment::class);

        // Disable fixes
        $containment->disableFixes('Adversarial drill');

        $plan = FixPlan::query()->create([
            'workspace_id' => $this->workspaceA->id,
            'scan_id' => $this->scanA->id,
            'repository_id' => $this->scanA->repository_id,
            'plan_digest' => 'abc',
            'title' => 'Test Plan',
            'status' => 'proposed',
            'fix_credits_required' => 1,
        ]);

        // Approval is rejected with 503
        $this->actingAs($this->tenantOwnerA)
            ->postJson("/workspaces/{$this->workspaceA->id}/fix-plans/{$plan->id}/approvals")
            ->assertStatus(503);

        $containment->enableFixes();
    }
}
