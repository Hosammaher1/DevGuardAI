<?php

namespace Tests\Feature\Scans;

use App\Domain\Identity\Role;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\Repository;
use App\Models\Scan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ScanAdmissionSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_scan_admission_fails_closed_with_503_and_creates_no_records(): void
    {
        [$owner, $workspace, $repo] = $this->createWorkspaceWithConnectedRepo();

        $response = $this->actingAs($owner)->postJson("/workspaces/{$workspace->getKey()}/scans", [
            'repository_id' => $repo->getKey(),
        ]);

        $response->assertStatus(503);
        $response->assertJsonPath('error', 'isolated_runner_unavailable');
        $response->assertJsonPath('reason', 'isolated_runner_unavailable');

        $this->assertDatabaseCount('scans', 0);
        $this->assertDatabaseCount('test_results', 0);
        $this->assertDatabaseCount('credit_ledger_entries', 0);
    }

    public function test_scan_admission_denies_viewer_with_403(): void
    {
        [$owner, $workspace, $repo] = $this->createWorkspaceWithConnectedRepo();

        $viewer = User::query()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.test',
            'github_user_id' => 'github-viewer',
        ]);

        Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $viewer->getKey(),
            'role' => Role::Viewer,
            'active' => true,
            'version' => 1,
        ]);

        $response = $this->actingAs($viewer)->postJson("/workspaces/{$workspace->getKey()}/scans", [
            'repository_id' => $repo->getKey(),
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('scans', 0);
    }

    public function test_scan_admission_rejects_missing_or_foreign_repository(): void
    {
        [$ownerA, $workspaceA] = $this->createWorkspaceWithOwner('Workspace A');
        [$ownerB, $workspaceB, $repoB] = $this->createWorkspaceWithConnectedRepo();

        // Missing repository_id
        $missingRepoResponse = $this->actingAs($ownerA)->postJson("/workspaces/{$workspaceA->getKey()}/scans", []);
        $missingRepoResponse->assertStatus(422);
        $missingRepoResponse->assertJsonPath('error', 'repository_required');

        // Foreign repository_id from Workspace B
        $foreignRepoResponse = $this->actingAs($ownerA)->postJson("/workspaces/{$workspaceA->getKey()}/scans", [
            'repository_id' => $repoB->getKey(),
        ]);
        $foreignRepoResponse->assertStatus(404);
        $foreignRepoResponse->assertJsonPath('error', 'repository_not_found');

        $this->assertDatabaseCount('scans', 0);
    }

    public function test_scan_admission_rejects_disconnected_or_revoked_installation_repository(): void
    {
        [$owner, $workspace, $repo, $installation] = $this->createWorkspaceWithConnectedRepo(true);

        // Mark repository inactive
        $repo->update(['status' => 'disconnected']);
        $disconnectedResponse = $this->actingAs($owner)->postJson("/workspaces/{$workspace->getKey()}/scans", [
            'repository_id' => $repo->getKey(),
        ]);
        $disconnectedResponse->assertStatus(422);
        $disconnectedResponse->assertJsonPath('error', 'repository_not_connected');

        // Mark repository connected but installation revoked
        $repo->update(['status' => 'connected']);
        $installation->update(['status' => 'revoked', 'revoked_at' => now()]);
        $revokedResponse = $this->actingAs($owner)->postJson("/workspaces/{$workspace->getKey()}/scans", [
            'repository_id' => $repo->getKey(),
        ]);
        $revokedResponse->assertStatus(422);
        $revokedResponse->assertJsonPath('error', 'github_installation_inactive');

        $this->assertDatabaseCount('scans', 0);
    }

    private function createWorkspaceWithOwner(string $name): array
    {
        $suffix = str()->random(8);
        $user = User::query()->create([
            'name' => 'User '.$suffix,
            'email' => $suffix.'@example.test',
            'github_user_id' => 'github-'.$suffix,
        ]);

        $workspace = Workspace::query()->create([
            'name' => $name.' '.$suffix,
            'personal_owner_user_id' => $user->getKey(),
        ]);

        Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $user->getKey(),
            'role' => Role::Owner,
            'active' => true,
            'version' => 1,
        ]);

        return [$user, $workspace];
    }

    private function createWorkspaceWithConnectedRepo(bool $returnInstallation = false): array
    {
        [$user, $workspace] = $this->createWorkspaceWithOwner('Scan Workspace');
        $userId = (int) $user->getKey();

        $installation = GitHubAppInstallation::query()->create([
            'workspace_id' => $workspace->getKey(),
            'installation_id' => 300000 + $userId,
            'account_login' => 'connected-org',
            'account_type' => 'Organization',
            'installed_by_user_id' => $user->getKey(),
            'status' => 'active',
        ]);

        $repo = Repository::query()->create([
            'workspace_id' => $workspace->getKey(),
            'github_app_installation_id' => $installation->getKey(),
            'github_repo_id' => 400000 + $userId,
            'name' => 'connected-repo',
            'full_name' => 'connected-org/connected-repo',
            'owner_login' => 'connected-org',
            'is_private' => true,
            'default_branch' => 'main',
            'status' => 'connected',
            'connected_by_user_id' => $user->getKey(),
        ]);

        if ($returnInstallation) {
            return [$user, $workspace, $repo, $installation];
        }

        return [$user, $workspace, $repo];
    }
}
