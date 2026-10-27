<?php

namespace Tests\Feature\Artifacts;

use App\Domain\Artifacts\ArtifactLifecycleService;
use App\Domain\Identity\Role;
use App\Models\Artifact;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ArtifactLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Workspace $workspace;
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'devguard-test-lifecycle-artifacts';
        if (! is_dir($this->tempRoot)) {
            mkdir($this->tempRoot, 0777, true);
        }

        Storage::set(
            'private-artifacts',
            Storage::createLocalDriver([
                'root' => $this->tempRoot,
            ])
        );

        $this->owner = User::query()->create([
            'name' => 'Owner User',
            'email' => 'owner@lifecycle.test',
            'github_user_id' => 'gh-owner-lifecycle',
        ]);

        $this->workspace = Workspace::query()->create([
            'name' => 'Lifecycle Workspace',
            'personal_owner_user_id' => $this->owner->id,
        ]);

        Membership::query()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->owner->id,
            'role' => Role::Owner->value,
            'active' => true,
        ]);
    }

    public function test_reap_expired_artifacts_deletes_physical_file_and_db_record(): void
    {
        $disk = Storage::disk('private-artifacts');
        $storageKey = 'test-file-to-delete.enc';
        $disk->put($storageKey, 'encrypted-payload');

        $this->assertTrue($disk->exists($storageKey));

        $artifact = Artifact::query()->create([
            'workspace_id' => $this->workspace->id,
            'artifact_type' => 'test_screenshot',
            'original_name' => 'failure.png',
            'storage_key' => $storageKey,
            'ciphertext_sha256' => hash('sha256', 'encrypted-payload'),
            'plaintext_bytes' => 17,
            'expires_at' => CarbonImmutable::now()->subMinute(),
        ]);

        $service = app(ArtifactLifecycleService::class);
        $reaped = $service->reapExpiredArtifacts($this->workspace);

        $this->assertSame(1, $reaped);
        $this->assertFalse($disk->exists($storageKey));
        $this->assertDatabaseMissing('artifacts', [
            'id' => $artifact->id,
        ]);
    }

    public function test_active_unexpired_artifacts_are_not_reaped(): void
    {
        $disk = Storage::disk('private-artifacts');
        $storageKey = 'test-active-file.enc';
        $disk->put($storageKey, 'active-payload');

        $artifact = Artifact::query()->create([
            'workspace_id' => $this->workspace->id,
            'artifact_type' => 'test_screenshot',
            'original_name' => 'active.png',
            'storage_key' => $storageKey,
            'ciphertext_sha256' => hash('sha256', 'active-payload'),
            'plaintext_bytes' => 14,
            'expires_at' => CarbonImmutable::now()->addHour(),
        ]);

        $service = app(ArtifactLifecycleService::class);
        $reaped = $service->reapExpiredArtifacts($this->workspace);

        $this->assertSame(0, $reaped);
        $this->assertTrue($disk->exists($storageKey));
        $this->assertDatabaseHas('artifacts', [
            'id' => $artifact->id,
        ]);
    }
}
