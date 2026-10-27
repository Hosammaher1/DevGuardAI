<?php

namespace Tests\Feature\GitHub;

use App\Domain\GitHub\GitHubAppClient;
use App\Domain\Identity\Role;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

final class InstallationVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::query()->create([
            'name' => 'Owner User',
            'email' => 'owner@example.test',
            'github_user_id' => 'gh-owner-100',
        ]);

        $this->workspace = Workspace::query()->create([
            'name' => 'Acme Workspace',
            'personal_owner_user_id' => $this->owner->id,
        ]);

        Membership::query()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->owner->id,
            'role' => Role::Owner->value,
            'active' => true,
        ]);
    }

    public function test_complete_installation_verifies_and_creates_record(): void
    {
        $response = $this->actingAs($this->owner)->postJson("/workspaces/{$this->workspace->id}/github/installations/complete", [
            'installation_id' => 999888,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('github_app_installations', [
            'workspace_id' => $this->workspace->id,
            'installation_id' => 999888,
            'status' => 'active',
        ]);
    }

    public function test_complete_installation_fails_when_github_verification_rejects(): void
    {
        $this->mock(GitHubAppClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getInstallation')
                ->with(999888)
                ->once()
                ->andReturn(null);
        });

        $response = $this->actingAs($this->owner)->postJson("/workspaces/{$this->workspace->id}/github/installations/complete", [
            'installation_id' => 999888,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Unable to verify installation with GitHub. Ensure the App is properly installed.');
    }
}
