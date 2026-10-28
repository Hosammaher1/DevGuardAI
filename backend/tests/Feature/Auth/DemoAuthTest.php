<?php

namespace Tests\Feature\Auth;

use App\Domain\Demo\DemoWorkspaceProvisioner;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class DemoAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('throttle:5,1');
    }

    public function test_demo_login_with_invalid_code_fails_with_401(): void
    {
        $response = $this->postJson('/auth/demo', [
            'invitation_code' => 'wrong-code-xyz',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'invalid_code',
            ]);

        $this->assertGuest();
    }

    public function test_demo_login_with_valid_code_authenticates_and_provisions_demo_workspace(): void
    {
        $code = 'demo-test-invitation-2026';
        Config::set('devguard-demo.invitation_code', $code);

        $response = $this->postJson('/auth/demo', [
            'invitation_code' => $code,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'workspace_id',
                'user' => [
                    'id',
                    'email',
                    'name',
                ],
                'is_demo',
            ])
            ->assertJson([
                'status' => 'authenticated',
                'is_demo' => true,
            ]);

        $this->assertAuthenticated();

        $sessionResponse = $this->getJson('/api/session');
        $sessionResponse->assertStatus(200)
            ->assertJson([
                'is_demo' => true,
            ]);

        // Verify provisioned data
        $this->assertDatabaseHas('users', [
            'github_user_id' => 'demo-visitor-shared',
            'email' => 'demo@devguard.ai',
        ]);

        $this->assertDatabaseHas('workspaces', [
            'name' => 'DevGuard AI Demo Workspace',
        ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Storefront E2E Suite',
        ]);

        $this->assertDatabaseHas('scans', [
            'trigger' => 'scheduled',
            'status' => 'completed',
        ]);
    }

    public function test_demo_session_cannot_access_other_tenant_workspaces(): void
    {
        // 1. Provision a separate real workspace
        $realWorkspace = Workspace::create([
            'name' => 'Private Enterprise Workspace',
            'owner_id' => 'private-user-123',
            'status' => 'active',
        ]);

        // 2. Authenticate as demo visitor
        $code = 'devguard-demo-2026';
        Config::set('devguard-demo.invitation_code', $code);

        $this->postJson('/auth/demo', ['invitation_code' => $code])
            ->assertStatus(200);

        // 3. Attempt access to the private workspace
        $blockedResponse = $this->getJson("/workspaces/{$realWorkspace->id}/projects");
        $blockedResponse->assertStatus(403)
            ->assertJson([
                'error' => 'tenant_isolation_violation',
            ]);
    }

    public function test_demo_session_cannot_publish_pr_or_perform_destructive_actions(): void
    {
        $code = 'devguard-demo-2026';
        Config::set('devguard-demo.invitation_code', $code);

        $authResponse = $this->postJson('/auth/demo', ['invitation_code' => $code]);
        $workspaceId = $authResponse->json('workspace_id');

        $plan = \App\Models\FixPlan::where('workspace_id', $workspaceId)->firstOrFail();

        // Attempting to publish PR should be intercepted by demo restrictions
        $blockedPrResponse = $this->postJson("/workspaces/{$workspaceId}/fix-plans/{$plan->id}/publish-pr");
        $blockedPrResponse->assertStatus(403)
            ->assertJson([
                'error' => 'demo_read_only',
            ]);
    }

    public function test_logout_clears_demo_session_and_credentials(): void
    {
        $code = 'devguard-demo-2026';
        Config::set('devguard-demo.invitation_code', $code);

        $this->postJson('/auth/demo', ['invitation_code' => $code])
            ->assertStatus(200);

        $this->assertAuthenticated();

        $logoutResponse = $this->postJson('/logout');
        $logoutResponse->assertStatus(200)
            ->assertJson([
                'status' => 'logged_out',
            ]);

        $this->assertGuest();

        $sessionCheck = $this->getJson('/api/session');
        $sessionCheck->assertStatus(401);
    }

    public function test_rate_limiting_throttles_excessive_failed_logins(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/auth/demo', ['invitation_code' => 'bad-attempt'])
                ->assertStatus(401);
        }

        // 6th attempt should be throttled
        $throttled = $this->postJson('/auth/demo', ['invitation_code' => 'bad-attempt']);
        $throttled->assertStatus(429);
    }

    public function test_demo_visitor_can_read_all_demo_workspace_data(): void
    {
        $code = 'devguard-demo-2026';
        Config::set('devguard-demo.invitation_code', $code);

        $authResponse = $this->postJson('/auth/demo', ['invitation_code' => $code]);
        $workspaceId = $authResponse->json('workspace_id');

        // Projects
        $projectsRes = $this->getJson("/workspaces/{$workspaceId}/projects");
        $projectsRes->assertStatus(200)
            ->assertJsonStructure(['projects']);
        $this->assertNotEmpty($projectsRes->json('projects'));

        // Scans
        $scansRes = $this->getJson("/workspaces/{$workspaceId}/scans");
        $scansRes->assertStatus(200)
            ->assertJsonStructure(['scans']);
        $this->assertNotEmpty($scansRes->json('scans'));

        // Metrics
        $metricsRes = $this->getJson("/workspaces/{$workspaceId}/metrics");
        $metricsRes->assertStatus(200)
            ->assertJsonStructure(['metrics' => ['total_scans', 'total_tests', 'total_passed', 'total_failed', 'pass_rate'], 'latest_scan']);

        // Fix Plans
        $plansRes = $this->getJson("/workspaces/{$workspaceId}/fix-plans");
        $plansRes->assertStatus(200)
            ->assertJsonStructure(['fix_plans']);
        $this->assertNotEmpty($plansRes->json('fix_plans'));

        // Notifications
        $notificationsRes = $this->getJson("/workspaces/{$workspaceId}/notifications");
        $notificationsRes->assertStatus(200)
            ->assertJsonStructure(['notifications']);
        $this->assertNotEmpty($notificationsRes->json('notifications'));
    }

    public function test_demo_session_cannot_invite_members_or_transfer_ownership(): void
    {
        $code = 'devguard-demo-2026';
        Config::set('devguard-demo.invitation_code', $code);

        $authResponse = $this->postJson('/auth/demo', ['invitation_code' => $code]);
        $workspaceId = $authResponse->json('workspace_id');

        $inviteRes = $this->postJson("/workspaces/{$workspaceId}/invitations", [
            'email' => 'attacker@external.com',
            'role' => 'admin',
        ]);
        $inviteRes->assertStatus(403)
            ->assertJson(['error' => 'demo_read_only']);

        $transferRes = $this->postJson("/workspaces/{$workspaceId}/ownership-transfers", [
            'target_user_id' => 9999,
        ]);
        $transferRes->assertStatus(403)
            ->assertJson(['error' => 'demo_read_only']);
    }
}
