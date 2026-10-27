<?php

namespace Tests\Feature\Projects;

use App\Domain\Identity\Role;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Repository;
use App\Models\TargetAuthorization;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProjectAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $member;

    private User $viewer;

    private Workspace $workspace;

    private Repository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@test.io', 'github_user_id' => 'gh-owner']);
        $this->member = User::query()->create(['name' => 'Member', 'email' => 'member@test.io', 'github_user_id' => 'gh-member']);
        $this->viewer = User::query()->create(['name' => 'Viewer', 'email' => 'viewer@test.io', 'github_user_id' => 'gh-viewer']);

        $this->workspace = Workspace::query()->create([
            'name' => 'Project Workspace',
            'personal_owner_user_id' => $this->owner->id,
        ]);

        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->owner->id, 'role' => Role::Owner->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->member->id, 'role' => Role::Member->value, 'active' => true]);
        Membership::query()->create(['workspace_id' => $this->workspace->id, 'user_id' => $this->viewer->id, 'role' => Role::Viewer->value, 'active' => true]);

        $installation = GitHubAppInstallation::query()->create([
            'workspace_id' => $this->workspace->id,
            'installation_id' => 54321,
            'account_login' => 'acme-test',
            'status' => 'active',
        ]);

        $this->repository = Repository::query()->create([
            'workspace_id' => $this->workspace->id,
            'github_app_installation_id' => $installation->id,
            'github_repo_id' => 999001,
            'name' => 'shopflow',
            'full_name' => 'acme-test/shopflow',
            'owner_login' => 'acme-test',
            'status' => 'connected',
            'default_branch' => 'main',
        ]);
    }

    public function test_owner_can_create_project_and_member_viewer_denied(): void
    {
        // Viewer is denied
        $this->actingAs($this->viewer)
            ->postJson("/workspaces/{$this->workspace->id}/projects", [
                'repository_id' => $this->repository->id,
                'name' => 'ShopFlow Web App',
            ])
            ->assertStatus(403);

        // Member is denied ManageProjectSettings
        $this->actingAs($this->member)
            ->postJson("/workspaces/{$this->workspace->id}/projects", [
                'repository_id' => $this->repository->id,
                'name' => 'ShopFlow Web App',
            ])
            ->assertStatus(403);

        // Owner succeeds
        $res = $this->actingAs($this->owner)
            ->postJson("/workspaces/{$this->workspace->id}/projects", [
                'repository_id' => $this->repository->id,
                'name' => 'ShopFlow Web App',
                'description' => 'Main customer store',
                'framework' => 'react',
            ]);

        $res->assertStatus(201);
        $this->assertDatabaseHas('projects', [
            'workspace_id' => $this->workspace->id,
            'repository_id' => $this->repository->id,
            'name' => 'ShopFlow Web App',
            'framework' => 'react',
        ]);
    }

    public function test_target_authorization_creation_and_revocation(): void
    {
        $project = Project::query()->create([
            'workspace_id' => $this->workspace->id,
            'repository_id' => $this->repository->id,
            'name' => 'ShopFlow Project',
        ]);

        // Add valid target authorization
        $res = $this->actingAs($this->owner)
            ->postJson("/workspaces/{$this->workspace->id}/projects/{$project->id}/targets", [
                'target_url' => 'http://localhost:3000',
                'environment' => 'test',
                'action_scope' => 'non_destructive',
            ]);

        $res->assertStatus(201);
        $targetId = $res->json('target_authorization.id');
        $this->assertNotNull($targetId);

        // Revoke target
        $delRes = $this->actingAs($this->owner)
            ->deleteJson("/workspaces/{$this->workspace->id}/projects/{$project->id}/targets/{$targetId}");

        $delRes->assertStatus(200);
        $this->assertDatabaseHas('target_authorizations', [
            'id' => $targetId,
            'status' => 'revoked',
        ]);
    }

    public function test_production_target_rejects_localhost_loopback(): void
    {
        $project = Project::query()->create([
            'workspace_id' => $this->workspace->id,
            'repository_id' => $this->repository->id,
            'name' => 'ShopFlow Project',
        ]);

        // Production environment cannot point to loopback
        $res = $this->actingAs($this->owner)
            ->postJson("/workspaces/{$this->workspace->id}/projects/{$project->id}/targets", [
                'target_url' => 'http://127.0.0.1:8080',
                'environment' => 'production',
            ]);

        $res->assertStatus(422);
        $res->assertJson(['error' => 'production_target_invalid_origin']);
    }
}
