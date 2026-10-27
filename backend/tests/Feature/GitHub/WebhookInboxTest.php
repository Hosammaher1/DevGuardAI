<?php

namespace Tests\Feature\GitHub;

use App\Domain\GitHub\GitHubWebhookVerifier;
use App\Domain\Identity\Role;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\ProviderInbox;
use App\Models\Repository;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WebhookInboxTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private GitHubAppInstallation $installation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'github_user_id' => 'gh-owner',
        ]);

        $this->workspace = Workspace::query()->create([
            'name' => 'Test Workspace',
            'personal_owner_user_id' => $this->owner->id,
        ]);

        Membership::query()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->owner->id,
            'role' => Role::Owner->value,
            'active' => true,
        ]);

        $this->installation = GitHubAppInstallation::query()->create([
            'workspace_id' => $this->workspace->id,
            'installation_id' => 999111,
            'account_login' => 'test-org',
            'status' => 'active',
        ]);
    }

    public function test_webhook_persists_inbox_entry_and_ignores_duplicate_delivery_id(): void
    {
        config(['services.github.webhook_secret' => 'test-secret']);
        $secret = 'test-secret';

        $payload = json_encode([
            'action' => 'created',
            'installation' => ['id' => 999111],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        $deliveryId = 'del-uuid-001';

        // First delivery
        $res1 = $this->postJson('/webhooks/github', json_decode($payload, true), [
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Event' => 'installation',
            'X-GitHub-Delivery' => $deliveryId,
        ]);

        $res1->assertStatus(200);
        $res1->assertJson(['status' => 'received', 'delivery_id' => $deliveryId]);

        $this->assertDatabaseHas('provider_inbox', [
            'provider' => 'github',
            'delivery_id' => $deliveryId,
            'event_type' => 'installation',
            'status' => 'processed',
        ]);

        // Duplicate delivery with identical payload
        $res2 = $this->postJson('/webhooks/github', json_decode($payload, true), [
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Event' => 'installation',
            'X-GitHub-Delivery' => $deliveryId,
        ]);

        $res2->assertStatus(200);
        $res2->assertJson(['status' => 'duplicate_ignored', 'delivery_id' => $deliveryId]);

        // Total inbox records is still 1
        $this->assertSame(1, ProviderInbox::query()->where('delivery_id', $deliveryId)->count());
    }

    public function test_webhook_rejects_tampered_payload_on_same_delivery_id(): void
    {
        config(['services.github.webhook_secret' => 'test-secret']);
        $secret = 'test-secret';

        $payload1 = json_encode(['action' => 'created', 'installation' => ['id' => 999111]]);
        $sig1 = 'sha256=' . hash_hmac('sha256', $payload1, $secret);
        $deliveryId = 'del-uuid-conflict';

        $this->postJson('/webhooks/github', json_decode($payload1, true), [
            'X-Hub-Signature-256' => $sig1,
            'X-GitHub-Event' => 'installation',
            'X-GitHub-Delivery' => $deliveryId,
        ])->assertStatus(200);

        // Replay with different body on same delivery ID
        $payload2 = json_encode(['action' => 'deleted', 'installation' => ['id' => 999111]]);
        $sig2 = 'sha256=' . hash_hmac('sha256', $payload2, $secret);

        $res = $this->postJson('/webhooks/github', json_decode($payload2, true), [
            'X-Hub-Signature-256' => $sig2,
            'X-GitHub-Event' => 'installation',
            'X-GitHub-Delivery' => $deliveryId,
        ]);

        $res->assertStatus(409);
        $res->assertJson(['error' => 'delivery_payload_conflict']);
    }

    public function test_installation_complete_endpoint_enforces_rbac_and_persists_binding(): void
    {
        $admin = User::query()->create(['name' => 'Admin', 'email' => 'admin@test.io', 'github_user_id' => 'gh-admin']);
        Membership::query()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $admin->id,
            'role' => Role::Admin->value,
            'active' => true,
        ]);

        $viewer = User::query()->create(['name' => 'Viewer', 'email' => 'viewer@test.io', 'github_user_id' => 'gh-viewer']);
        Membership::query()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $viewer->id,
            'role' => Role::Viewer->value,
            'active' => true,
        ]);

        // Viewer is denied
        $this->actingAs($viewer)
            ->postJson("/workspaces/{$this->workspace->id}/github/installations/complete", [
                'installation_id' => 777888,
                'account_login' => 'my-org',
            ])
            ->assertStatus(403);

        // Admin succeeds
        $res = $this->actingAs($admin)
            ->postJson("/workspaces/{$this->workspace->id}/github/installations/complete", [
                'installation_id' => 777888,
                'account_login' => 'my-org',
                'account_type' => 'Organization',
            ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('github_app_installations', [
            'workspace_id' => $this->workspace->id,
            'installation_id' => 777888,
            'account_login' => 'my-org',
            'status' => 'active',
        ]);
    }
}
