<?php

namespace Tests\Feature\Auth;

use App\Domain\Identity\Role;
use App\Models\CreditAccount;
use App\Models\Membership;
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

    public function test_staging_login_with_valid_code_authenticates_and_provisions_clean_staging_workspace(): void
    {
        $code = 'staging-test-code-2026';
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
                'workspace' => [
                    'id',
                    'name',
                    'role',
                ],
            ])
            ->assertJson([
                'status' => 'authenticated',
                'workspace' => [
                    'name' => 'DevGuard Staging Workspace',
                    'role' => 'owner',
                ],
            ]);

        $this->assertAuthenticated();

        $sessionResponse = $this->getJson('/api/session');
        $sessionResponse->assertStatus(200);

        // Verify provisioned staging identity and workspace
        $this->assertDatabaseHas('users', [
            'github_user_id' => 'staging-authorized-user',
            'email' => 'staging@devguard.ai',
        ]);

        $this->assertDatabaseHas('workspaces', [
            'name' => 'DevGuard Staging Workspace',
        ]);

        $workspaceId = $response->json('workspace_id');

        $this->assertDatabaseHas('memberships', [
            'workspace_id' => $workspaceId,
            'role' => Role::Owner->value,
            'active' => true,
        ]);

        $this->assertDatabaseHas('credit_accounts', [
            'workspace_id' => $workspaceId,
            'credit_kind' => 'scan',
            'available_credits' => 50,
        ]);

        $this->assertDatabaseHas('credit_accounts', [
            'workspace_id' => $workspaceId,
            'credit_kind' => 'fix',
            'available_credits' => 20,
        ]);
    }

    public function test_staging_session_cannot_access_other_tenant_workspaces(): void
    {
        // 1. Provision a separate private tenant workspace
        $otherOwner = User::create([
            'github_user_id' => 'other-tenant-user',
            'name' => 'Other Tenant',
            'email' => 'other@tenant.com',
        ]);

        $privateWorkspace = Workspace::create([
            'name' => 'Private Enterprise Workspace',
            'personal_owner_user_id' => $otherOwner->id,
        ]);

        Membership::create([
            'workspace_id' => $privateWorkspace->id,
            'user_id' => $otherOwner->id,
            'role' => Role::Owner,
            'active' => true,
            'version' => 1,
        ]);

        // 2. Authenticate as staging engineer
        $code = 'staging-test-code-2026';
        Config::set('devguard-demo.invitation_code', $code);

        $this->postJson('/auth/demo', ['invitation_code' => $code])
            ->assertStatus(200);

        // 3. Attempt access to the other tenant's workspace
        $blockedResponse = $this->getJson("/workspaces/{$privateWorkspace->id}/projects");
        $blockedResponse->assertStatus(403)
            ->assertJson([
                'error' => 'tenant_isolation_violation',
            ]);
    }

    public function test_staging_user_has_full_authorization_in_staging_workspace(): void
    {
        $code = 'staging-test-code-2026';
        Config::set('devguard-demo.invitation_code', $code);

        $authResponse = $this->postJson('/auth/demo', ['invitation_code' => $code]);
        $workspaceId = $authResponse->json('workspace_id');

        // Can access workspace projects
        $projectsRes = $this->getJson("/workspaces/{$workspaceId}/projects");
        $projectsRes->assertStatus(200)
            ->assertJsonStructure(['projects']);

        // Can access workspace scans
        $scansRes = $this->getJson("/workspaces/{$workspaceId}/scans");
        $scansRes->assertStatus(200)
            ->assertJsonStructure(['scans']);

        // Can access workspace metrics
        $metricsRes = $this->getJson("/workspaces/{$workspaceId}/metrics");
        $metricsRes->assertStatus(200)
            ->assertJsonStructure(['metrics', 'latest_scan']);

        // Can access workspace usage
        $usageRes = $this->getJson("/workspaces/{$workspaceId}/usage");
        $usageRes->assertStatus(200)
            ->assertJsonStructure(['workspace', 'usage' => ['scan_credits', 'fix_credits'], 'system_health']);
    }

    public function test_logout_clears_staging_session_and_credentials(): void
    {
        $code = 'staging-test-code-2026';
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
}
