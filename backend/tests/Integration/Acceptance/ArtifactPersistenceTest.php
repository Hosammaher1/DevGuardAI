<?php

namespace Tests\Integration\Acceptance;

use App\Domain\Artifacts\ArtifactAccessService;
use App\Domain\Artifacts\ArtifactLifecycleService;
use App\Domain\Identity\Role;
use App\Models\ArtifactAccessLink;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ArtifactPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPrivateArtifactsDisk();

        self::assertSame('pgsql', config('database.default'));
        self::assertSame('devguard_test', config('database.connections.pgsql.database'));
    }

    public function test_private_artifact_persistence_authorization_revocation_and_retention_work_on_postgresql(): void
    {
        $suffix = str()->random(8);
        $owner = User::query()->create([
            'name' => 'Synthetic PostgreSQL Artifact Owner',
            'email' => $suffix.'@example.test',
            'github_user_id' => 'github-artifact-pg-'.$suffix,
        ]);
        $workspace = Workspace::query()->create([
            'name' => 'Synthetic PostgreSQL Artifact Workspace '.$suffix,
            'personal_owner_user_id' => $owner->getKey(),
        ]);
        Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $owner->getKey(),
            'role' => Role::Owner,
            'active' => true,
            'version' => 1,
        ]);

        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        config(['devguard-artifacts.report_retention_days' => 30]);
        $service = app(ArtifactAccessService::class);
        $artifact = $service->store($workspace->getKey(), 'report', 'private.json', '{"synthetic":"postgresql"}', $now);
        $link = $service->issueLink($owner, $artifact, $now);

        self::assertSame('{"synthetic":"postgresql"}', $service->read($owner, $artifact->getKey(), $link->token, $now)->contents);
        self::assertTrue($service->revokeLink($owner, $artifact, $link->link->getKey(), $now->addMinute()));
        self::assertNull($service->read($owner, $artifact->getKey(), $link->token, $now->addMinute()));
        self::assertNotSame($link->token, ArtifactAccessLink::query()->whereKey($link->link->getKey())->value('token_hash'));

        $expired = $service->store($workspace->getKey(), 'report', 'expired.json', 'expired', $now->subDays(31));
        self::assertSame(1, app(ArtifactLifecycleService::class)->purgeExpiredReports($now));
        self::assertDatabaseMissing('artifacts', ['id' => $expired->getKey()]);
    }
}
