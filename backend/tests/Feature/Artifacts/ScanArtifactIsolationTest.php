<?php

namespace Tests\Feature\Artifacts;

use App\Domain\Artifacts\ArtifactAccessService;
use App\Domain\Identity\Role;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\Repository;
use App\Models\Scan;
use App\Models\TestResult;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ScanArtifactIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_PNG = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15c4\x00\x00\x00\nIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82";

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPrivateArtifactsDisk();
    }

    public function test_mixed_workspace_scan_and_result_returns_404(): void
    {
        [$ownerA, $workspaceA] = $this->createWorkspaceWithOwner('Workspace A');
        [$ownerB, $workspaceB] = $this->createWorkspaceWithOwner('Workspace B');

        $repoB = $this->createRepository($workspaceB, $ownerB);
        $scanB = $this->createScan($workspaceB, $repoB, $ownerB);
        $resultB = $this->createTestResult($scanB);

        $response = $this->actingAs($ownerA)->get(
            "/workspaces/{$workspaceA->getKey()}/scans/{$scanB->getKey()}/tests/{$resultB->getKey()}/screenshot"
        );

        $response->assertNotFound();
    }

    public function test_result_without_screenshot_artifact_id_returns_404(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner('Isolated Workspace');
        $repo = $this->createRepository($workspace, $owner);
        $scan = $this->createScan($workspace, $repo, $owner);
        $result = $this->createTestResult($scan, null);

        $response = $this->actingAs($owner)->get(
            "/workspaces/{$workspace->getKey()}/scans/{$scan->getKey()}/tests/{$result->getKey()}/screenshot"
        );

        $response->assertNotFound();
    }

    public function test_valid_own_result_with_artifact_service_token_delivers_own_synthetic_png_bytes(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner('Secure Workspace');
        $repo = $this->createRepository($workspace, $owner);
        $scan = $this->createScan($workspace, $repo, $owner);

        $now = CarbonImmutable::now();
        $artifactService = app(ArtifactAccessService::class);
        $artifact = $artifactService->store(
            $workspace->getKey(),
            'screenshot',
            'failure.png',
            self::SAMPLE_PNG,
            $now,
            [],
            $scan->getKey(),
        );

        $result = $this->createTestResult($scan, $artifact->getKey());
        $issuedLink = $artifactService->issueLink($owner, $artifact, $now);

        $response = $this->actingAs($owner)
            ->withHeader('Authorization', 'Artifact '.$issuedLink->token)
            ->get("/workspaces/{$workspace->getKey()}/scans/{$scan->getKey()}/tests/{$result->getKey()}/screenshot");

        $response->assertOk();
        self::assertSame(self::SAMPLE_PNG, $response->getContent());
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_missing_token_header_is_denied(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner('Token Required Workspace');
        $repo = $this->createRepository($workspace, $owner);
        $scan = $this->createScan($workspace, $repo, $owner);

        $now = CarbonImmutable::now();
        $artifactService = app(ArtifactAccessService::class);
        $artifact = $artifactService->store(
            $workspace->getKey(),
            'screenshot',
            'failure.png',
            self::SAMPLE_PNG,
            $now,
            [],
            $scan->getKey(),
        );

        $result = $this->createTestResult($scan, $artifact->getKey());

        $response = $this->actingAs($owner)
            ->get("/workspaces/{$workspace->getKey()}/scans/{$scan->getKey()}/tests/{$result->getKey()}/screenshot");

        $this->assertContains($response->getStatusCode(), [401, 403, 404]);
    }

    public function test_expired_token_is_denied(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner('Expired Token Workspace');
        $repo = $this->createRepository($workspace, $owner);
        $scan = $this->createScan($workspace, $repo, $owner);

        $now = CarbonImmutable::now();
        $artifactService = app(ArtifactAccessService::class);
        $artifact = $artifactService->store(
            $workspace->getKey(),
            'screenshot',
            'failure.png',
            self::SAMPLE_PNG,
            $now->subHours(2),
            [],
            $scan->getKey(),
        );

        $result = $this->createTestResult($scan, $artifact->getKey());
        $issuedLink = $artifactService->issueLink($owner, $artifact, $now->subHours(2));

        $response = $this->actingAs($owner)
            ->withHeader('Authorization', 'Artifact '.$issuedLink->token)
            ->get("/workspaces/{$workspace->getKey()}/scans/{$scan->getKey()}/tests/{$result->getKey()}/screenshot");

        $this->assertContains($response->getStatusCode(), [401, 403, 404]);
    }

    public function test_revoked_token_is_denied(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner('Revoked Token Workspace');
        $repo = $this->createRepository($workspace, $owner);
        $scan = $this->createScan($workspace, $repo, $owner);

        $now = CarbonImmutable::now();
        $artifactService = app(ArtifactAccessService::class);
        $artifact = $artifactService->store(
            $workspace->getKey(),
            'screenshot',
            'failure.png',
            self::SAMPLE_PNG,
            $now,
            [],
            $scan->getKey(),
        );

        $result = $this->createTestResult($scan, $artifact->getKey());
        $issuedLink = $artifactService->issueLink($owner, $artifact, $now);

        $artifactService->revokeLink($owner, $artifact, $issuedLink->link->getKey(), $now);

        $response = $this->actingAs($owner)
            ->withHeader('Authorization', 'Artifact '.$issuedLink->token)
            ->get("/workspaces/{$workspace->getKey()}/scans/{$scan->getKey()}/tests/{$result->getKey()}/screenshot");

        $this->assertContains($response->getStatusCode(), [401, 403, 404]);
    }

    public function test_revoked_membership_is_denied(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner('Revoked Member Workspace');
        $repo = $this->createRepository($workspace, $owner);
        $scan = $this->createScan($workspace, $repo, $owner);

        $now = CarbonImmutable::now();
        $artifactService = app(ArtifactAccessService::class);
        $artifact = $artifactService->store(
            $workspace->getKey(),
            'screenshot',
            'failure.png',
            self::SAMPLE_PNG,
            $now,
            [],
            $scan->getKey(),
        );

        $result = $this->createTestResult($scan, $artifact->getKey());
        $issuedLink = $artifactService->issueLink($owner, $artifact, $now);

        Membership::query()
            ->where('workspace_id', $workspace->getKey())
            ->where('user_id', $owner->getKey())
            ->update(['active' => false]);

        $response = $this->actingAs($owner)
            ->withHeader('Authorization', 'Artifact '.$issuedLink->token)
            ->get("/workspaces/{$workspace->getKey()}/scans/{$scan->getKey()}/tests/{$result->getKey()}/screenshot");

        $this->assertContains($response->getStatusCode(), [401, 403, 404]);
    }

    public function test_cross_workspace_artifact_association_is_denied(): void
    {
        [$ownerA, $workspaceA] = $this->createWorkspaceWithOwner('Workspace A');
        [$ownerB, $workspaceB] = $this->createWorkspaceWithOwner('Workspace B');

        $repoA = $this->createRepository($workspaceA, $ownerA);
        $scanA = $this->createScan($workspaceA, $repoA, $ownerA);

        $now = CarbonImmutable::now();
        $artifactService = app(ArtifactAccessService::class);
        $foreignArtifact = $artifactService->store(
            $workspaceB->getKey(),
            'screenshot',
            'foreign_failure.png',
            self::SAMPLE_PNG,
            $now,
            [],
            null,
        );

        $resultA = $this->createTestResult($scanA, $foreignArtifact->getKey());
        $issuedLink = $artifactService->issueLink($ownerB, $foreignArtifact, $now);

        $response = $this->actingAs($ownerA)
            ->withHeader('Authorization', 'Artifact '.$issuedLink->token)
            ->get("/workspaces/{$workspaceA->getKey()}/scans/{$scanA->getKey()}/tests/{$resultA->getKey()}/screenshot");

        $this->assertContains($response->getStatusCode(), [401, 403, 404]);
    }

    public function test_same_workspace_other_scan_artifact_association_is_denied(): void
    {
        [$owner, $workspace] = $this->createWorkspaceWithOwner('Multi Scan Workspace');
        $repo = $this->createRepository($workspace, $owner);
        $scan1 = $this->createScan($workspace, $repo, $owner);
        $scan2 = $this->createScan($workspace, $repo, $owner);

        $now = CarbonImmutable::now();
        $artifactService = app(ArtifactAccessService::class);
        $artifact1 = $artifactService->store(
            $workspace->getKey(),
            'screenshot',
            'scan1_failure.png',
            self::SAMPLE_PNG,
            $now,
            [],
            $scan1->getKey(),
        );

        $result2 = $this->createTestResult($scan2, $artifact1->getKey());
        $issuedLink = $artifactService->issueLink($owner, $artifact1, $now);

        $response = $this->actingAs($owner)
            ->withHeader('Authorization', 'Artifact '.$issuedLink->token)
            ->get("/workspaces/{$workspace->getKey()}/scans/{$scan2->getKey()}/tests/{$result2->getKey()}/screenshot");

        $this->assertContains($response->getStatusCode(), [401, 403, 404]);
    }

    private function createWorkspaceWithOwner(string $name): array
    {
        $suffix = str()->random(8);
        $user = User::query()->create([
            'name' => 'Synthetic User '.$suffix,
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

    private function createRepository(Workspace $workspace, User $user): Repository
    {
        $userId = (int) $user->getKey();
        $installation = GitHubAppInstallation::query()->create([
            'workspace_id' => $workspace->getKey(),
            'installation_id' => 100000 + $userId,
            'account_login' => 'synthetic-org',
            'account_type' => 'Organization',
            'installed_by_user_id' => $user->getKey(),
            'status' => 'active',
        ]);

        return Repository::query()->create([
            'workspace_id' => $workspace->getKey(),
            'github_app_installation_id' => $installation->getKey(),
            'github_repo_id' => 200000 + $userId,
            'name' => 'repo-'.$userId,
            'full_name' => 'synthetic-org/repo-'.$userId,
            'owner_login' => 'synthetic-org',
            'is_private' => true,
            'default_branch' => 'main',
            'status' => 'active',
            'connected_by_user_id' => $user->getKey(),
        ]);
    }

    private function createScan(Workspace $workspace, Repository $repository, User $user): Scan
    {
        return Scan::query()->create([
            'workspace_id' => $workspace->getKey(),
            'repository_id' => $repository->getKey(),
            'initiated_by_user_id' => $user->getKey(),
            'status' => 'completed',
            'branch' => 'main',
            'commit_hash' => hash('sha256', str()->random(16)),
            'trigger' => 'manual',
            'total_tests' => 1,
            'passed_tests' => 0,
            'failed_tests' => 1,
            'skipped_tests' => 0,
            'started_at' => CarbonImmutable::now(),
            'finished_at' => CarbonImmutable::now(),
        ]);
    }

    private function createTestResult(Scan $scan, ?string $screenshotArtifactId = null): TestResult
    {
        return TestResult::query()->create([
            'scan_id' => $scan->getKey(),
            'test_name' => 'test_feature_failure',
            'test_suite' => 'Unit',
            'file_path' => 'tests/Feature/ExampleTest.php',
            'status' => 'failed',
            'duration_ms' => 42,
            'error_message' => 'Assertion failure',
            'screenshot_artifact_id' => $screenshotArtifactId,
        ]);
    }
}
