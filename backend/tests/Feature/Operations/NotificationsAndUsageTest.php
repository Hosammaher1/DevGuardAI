<?php

namespace Tests\Feature\Operations;

use App\Domain\Artifacts\ArtifactLifecycleService;
use App\Domain\Identity\Role;
use App\Models\Artifact;
use App\Models\CreditAccount;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NotificationsAndUsageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@notify.test', 'github_user_id' => 'gh-owner']);
        $this->workspace = Workspace::query()->create(['name' => 'Notify Workspace', 'personal_owner_user_id' => $this->owner->id]);

        Membership::query()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->owner->id,
            'role' => Role::Owner->value,
            'active' => true,
        ]);

        CreditAccount::query()->create([
            'workspace_id' => $this->workspace->id,
            'credit_kind' => 'scan',
            'available_credits' => 10,
            'reserved_credits' => 0,
            'consumed_credits' => 2,
        ]);

        CreditAccount::query()->create([
            'workspace_id' => $this->workspace->id,
            'credit_kind' => 'fix',
            'available_credits' => 5,
            'reserved_credits' => 0,
            'consumed_credits' => 1,
        ]);
    }

    public function test_notifications_list_and_mark_as_read(): void
    {
        $notification = Notification::query()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->owner->id,
            'type' => 'scan_completed',
            'title' => 'Scan Completed',
            'message' => 'Scan #123 finished with 3 tests executed.',
        ]);

        $res = $this->actingAs($this->owner)
            ->getJson("/workspaces/{$this->workspace->id}/notifications");

        $res->assertStatus(200);
        $res->assertJsonPath('unread_count', 1);
        $this->assertCount(1, $res->json('notifications'));

        // Mark as read
        $markRes = $this->actingAs($this->owner)
            ->patchJson("/workspaces/{$this->workspace->id}/notifications/{$notification->id}/read");

        $markRes->assertStatus(200);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_usage_endpoint_reports_credits_and_health(): void
    {
        $res = $this->actingAs($this->owner)
            ->getJson("/workspaces/{$this->workspace->id}/usage");

        $res->assertStatus(200);
        $res->assertJsonPath('usage.scan_credits.available', 10);
        $res->assertJsonPath('usage.fix_credits.available', 5);
        $res->assertJsonPath('system_health.status', 'healthy');
        $res->assertJsonPath('system_health.containment.scans_allowed', true);
    }

    public function test_artifact_lifecycle_reaper_clears_expired_payloads(): void
    {
        $past = CarbonImmutable::now()->subDays(1);
        $future = CarbonImmutable::now()->addDays(5);

        $expiredArtifact = Artifact::query()->create([
            'workspace_id' => $this->workspace->id,
            'artifact_type' => 'screenshot',
            'original_name' => 'old.png',
            'storage_key' => 'old-key-123',
            'ciphertext_sha256' => hash('sha256', 'old'),
            'plaintext_bytes' => 100,
            'expires_at' => $past,
        ]);

        $validArtifact = Artifact::query()->create([
            'workspace_id' => $this->workspace->id,
            'artifact_type' => 'screenshot',
            'original_name' => 'valid.png',
            'storage_key' => 'valid-key-456',
            'ciphertext_sha256' => hash('sha256', 'valid'),
            'plaintext_bytes' => 100,
            'expires_at' => $future,
        ]);

        $service = new ArtifactLifecycleService();
        $reapedCount = $service->reapExpiredArtifacts($this->workspace);

        $this->assertSame(1, $reapedCount);
        $this->assertNull($expiredArtifact->fresh());
        $this->assertNotNull($validArtifact->fresh());
    }
}
