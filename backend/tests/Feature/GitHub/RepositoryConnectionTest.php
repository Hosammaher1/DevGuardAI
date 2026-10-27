<?php

namespace Tests\Feature\GitHub;

use App\Domain\GitHub\GitHubWebhookVerifier;
use App\Domain\GitHub\RepositoryAccessDenied;
use App\Domain\GitHub\RepositoryBinding;
use App\Domain\GitHub\RepositoryBindingService;
use App\Domain\Identity\Role;
use App\Models\CreditAccount;
use App\Models\CreditLedgerEntry;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\Repository;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RepositoryConnectionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private User $member;

    private User $viewer;

    private Workspace $workspace;

    private GitHubAppInstallation $installation;

    private RepositoryBindingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RepositoryBindingService::class);

        $this->owner = User::query()->create([
            'name' => 'Owner User',
            'email' => 'owner@example.test',
            'github_user_id' => 'gh-owner-100',
        ]);
        $this->admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'github_user_id' => 'gh-admin-200',
        ]);
        $this->member = User::query()->create([
            'name' => 'Member User',
            'email' => 'member@example.test',
            'github_user_id' => 'gh-member-300',
        ]);
        $this->viewer = User::query()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.test',
            'github_user_id' => 'gh-viewer-400',
        ]);

        $this->workspace = Workspace::query()->create([
            'name' => 'Acme Workspace',
            'personal_owner_user_id' => $this->owner->id,
        ]);

        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->owner->id, 'role' => Role::Owner->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->admin->id, 'role' => Role::Admin->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->member->id, 'role' => Role::Member->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->viewer->id, 'role' => Role::Viewer->value, 'active' => true]);

        $this->installation = GitHubAppInstallation::query()->create([
            'workspace_id' => $this->workspace->id,
            'installation_id' => 12345678,
            'account_login' => 'acme-corp',
            'account_type' => 'Organization',
            'repository_selection' => 'selected',
            'permissions' => ['contents' => 'read', 'checks' => 'write', 'pull_requests' => 'write'],
            'installed_by_user_id' => $this->owner->id,
            'status' => 'active',
        ]);
    }

    public function test_owner_and_admin_can_connect_repository_but_member_and_viewer_are_denied(): void
    {
        $repoData = [
            'github_repo_id' => 987654,
            'name' => 'web-frontend',
            'full_name' => 'acme-corp/web-frontend',
            'owner_login' => 'acme-corp',
            'is_private' => true,
            'default_branch' => 'main',
        ];

        // Owner connects successfully
        $repo = $this->service->connectRepository(
            $this->owner,
            $this->workspace,
            $this->installation,
            $repoData,
        );

        self::assertInstanceOf(Repository::class, $repo);
        self::assertSame('connected', $repo->status);
        self::assertSame('acme-corp/web-frontend', $repo->full_name);
        self::assertSame($this->workspace->id, $repo->workspace_id);

        // Admin can also connect another repository
        $adminRepoData = [
            'github_repo_id' => 987655,
            'name' => 'api-backend',
            'full_name' => 'acme-corp/api-backend',
            'owner_login' => 'acme-corp',
            'is_private' => true,
            'default_branch' => 'main',
        ];
        $adminRepo = $this->service->connectRepository(
            $this->admin,
            $this->workspace,
            $this->installation,
            $adminRepoData,
        );
        self::assertSame('connected', $adminRepo->status);

        // Member is denied
        self::assertThrows(
            fn () => $this->service->connectRepository(
                $this->member,
                $this->workspace,
                $this->installation,
                ['github_repo_id' => 987656, 'name' => 'mobile', 'full_name' => 'acme-corp/mobile', 'owner_login' => 'acme-corp'],
            ),
            RepositoryAccessDenied::class
        );

        // Viewer is denied
        self::assertThrows(
            fn () => $this->service->connectRepository(
                $this->viewer,
                $this->workspace,
                $this->installation,
                ['github_repo_id' => 987657, 'name' => 'desktop', 'full_name' => 'acme-corp/desktop', 'owner_login' => 'acme-corp'],
            ),
            RepositoryAccessDenied::class
        );
    }

    public function test_repository_connection_enforces_strict_tenant_isolation(): void
    {
        $otherWorkspace = Workspace::query()->create(['name' => 'Other Tenant']);
        $otherInstallation = GitHubAppInstallation::query()->create([
            'workspace_id' => $otherWorkspace->id,
            'installation_id' => 87654321,
            'account_login' => 'other-corp',
            'account_type' => 'Organization',
            'repository_selection' => 'all',
            'permissions' => ['contents' => 'read'],
            'status' => 'active',
        ]);

        // Trying to use another workspace's installation must fail closed
        self::assertThrows(
            fn () => $this->service->connectRepository(
                $this->owner,
                $this->workspace,
                $otherInstallation,
                ['github_repo_id' => 111222, 'name' => 'leak', 'full_name' => 'other-corp/leak', 'owner_login' => 'other-corp'],
            ),
            RepositoryAccessDenied::class
        );
    }

    public function test_unlimited_repository_connections_consume_zero_credits_and_trigger_no_execution(): void
    {
        // Setup initial credit account
        $account = CreditAccount::query()->create([
            'workspace_id' => $this->workspace->id,
            'credit_kind' => 'scan',
            'available_credits' => 10,
            'reserved_credits' => 0,
            'consumed_credits' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $initialLedgerCount = CreditLedgerEntry::query()->count();

        // Connect 5 repositories
        for ($i = 1; $i <= 5; $i++) {
            $this->service->connectRepository(
                $this->owner,
                $this->workspace,
                $this->installation,
                [
                    'github_repo_id' => 2000 + $i,
                    'name' => "project-{$i}",
                    'full_name' => "acme-corp/project-{$i}",
                    'owner_login' => 'acme-corp',
                    'is_private' => true,
                    'default_branch' => 'main',
                ],
            );
        }

        // Verify credit balance and ledger entries did NOT change (PD-033, DG-GH-015)
        $account->refresh();
        self::assertSame(10, $account->available_credits);
        self::assertSame(0, $account->reserved_credits);
        self::assertSame(0, $account->consumed_credits);
        self::assertSame($initialLedgerCount, CreditLedgerEntry::query()->count());
    }

    public function test_revoked_installation_immediately_blocks_checkout_binding_and_fails_closed(): void
    {
        $repo = $this->service->connectRepository(
            $this->owner,
            $this->workspace,
            $this->installation,
            [
                'github_repo_id' => 555666,
                'name' => 'checkout-test',
                'full_name' => 'acme-corp/checkout-test',
                'owner_login' => 'acme-corp',
                'is_private' => true,
                'default_branch' => 'main',
            ],
        );

        // Active binding succeeds
        $binding = $this->service->resolveBindingForCheckout($this->workspace->id, $repo->id);
        self::assertInstanceOf(RepositoryBinding::class, $binding);
        self::assertSame('acme-corp/checkout-test', $binding->fullName);
        self::assertSame(12345678, $binding->installationId);

        // Revoke installation
        $this->service->revokeInstallation($this->installation->installation_id);

        // Subsequent checkout resolution must fail closed immediately (DG-GH-005)
        self::assertThrows(
            fn () => $this->service->resolveBindingForCheckout($this->workspace->id, $repo->id),
            RepositoryAccessDenied::class
        );
    }

    public function test_webhook_signature_verification_accepts_valid_hmac_and_rejects_tampered_payload(): void
    {
        $verifier = new GitHubWebhookVerifier;
        $secret = 'test-secret-key-123';
        $payload = json_encode(['action' => 'deleted', 'installation' => ['id' => 12345678]], JSON_THROW_ON_ERROR);

        $validSignature = 'sha256='.hash_hmac('sha256', $payload, $secret);
        $tamperedSignature = 'sha256='.hash_hmac('sha256', $payload.'tampered', $secret);

        self::assertTrue($verifier->verifySignature($payload, $validSignature, $secret));
        self::assertFalse($verifier->verifySignature($payload, $tamperedSignature, $secret));
        self::assertFalse($verifier->verifySignature($payload, null, $secret));
        self::assertFalse($verifier->verifySignature($payload, 'invalid-format', $secret));
    }

    public function test_webhook_installation_deleted_event_revokes_all_workspace_repository_bindings(): void
    {
        $repo = $this->service->connectRepository(
            $this->owner,
            $this->workspace,
            $this->installation,
            [
                'github_repo_id' => 777888,
                'name' => 'webhook-revocation-test',
                'full_name' => 'acme-corp/webhook-revocation-test',
                'owner_login' => 'acme-corp',
                'is_private' => true,
                'default_branch' => 'main',
            ],
        );

        $secret = 'webhook-secret-xyz';
        config(['services.github.webhook_secret' => $secret]);

        $payload = json_encode([
            'action' => 'deleted',
            'installation' => [
                'id' => 12345678,
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        $response = $this->postJson('/webhooks/github', json_decode($payload, true), [
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Event' => 'installation',
            'X-GitHub-Delivery' => 'test-delivery-id-001',
        ]);

        $response->assertOk();

        // Repository and installation must now be revoked
        $repo->refresh();
        $this->installation->refresh();

        self::assertSame('revoked', $repo->status);
        self::assertSame('revoked', $this->installation->status);
        self::assertNotNull($this->installation->revoked_at);
    }

    public function test_http_repository_endpoints_enforce_rbac_and_scope(): void
    {
        $payload = [
            'installation_id' => $this->installation->id,
            'github_repo_id' => 999111,
            'name' => 'api-service',
            'full_name' => 'acme-corp/api-service',
            'owner_login' => 'acme-corp',
            'is_private' => false,
            'default_branch' => 'main',
        ];

        // Member cannot connect via HTTP
        $this->actingAs($this->member)
            ->postJson("/workspaces/{$this->workspace->id}/repositories", $payload)
            ->assertForbidden();

        // Admin can connect via HTTP
        $response = $this->actingAs($this->admin)
            ->postJson("/workspaces/{$this->workspace->id}/repositories", $payload)
            ->assertStatus(201);

        $repoId = $response->json('repository.id');
        self::assertNotNull($repoId);

        // Member can view repositories
        $this->actingAs($this->member)
            ->getJson("/workspaces/{$this->workspace->id}/repositories")
            ->assertOk()
            ->assertJsonCount(1, 'repositories');

        // Member cannot disconnect
        $this->actingAs($this->member)
            ->deleteJson("/workspaces/{$this->workspace->id}/repositories/{$repoId}")
            ->assertForbidden();

        // Owner can disconnect
        $this->actingAs($this->owner)
            ->deleteJson("/workspaces/{$this->workspace->id}/repositories/{$repoId}")
            ->assertOk();

        // Repo is now disconnected
        $this->actingAs($this->member)
            ->getJson("/workspaces/{$this->workspace->id}/repositories")
            ->assertOk()
            ->assertJsonCount(0, 'repositories');
    }
}
