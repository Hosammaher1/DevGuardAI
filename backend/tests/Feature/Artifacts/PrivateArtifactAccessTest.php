<?php

namespace Tests\Feature\Artifacts;

use App\Domain\Artifacts\ArtifactAccessDenied;
use App\Domain\Artifacts\ArtifactAccessService;
use App\Domain\Artifacts\ArtifactLifecycleService;
use App\Domain\Identity\Role;
use App\Models\AuditEvent;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PrivateArtifactAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPrivateArtifactsDisk();
    }

    public function test_dg_report_003_artifact_access_is_authorized_against_current_workspace_membership(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        [$attacker, $otherWorkspace] = $this->workspaceWithOwner();
        $artifact = app(ArtifactAccessService::class)->store(
            $workspace->getKey(),
            'report',
            'report.json',
            '{"synthetic":"private report"}',
            CarbonImmutable::now(),
        );
        $access = app(ArtifactAccessService::class);
        $ownerLink = $access->issueLink($owner, $artifact, CarbonImmutable::now());

        self::assertSame('{"synthetic":"private report"}', $access->read($owner, $artifact->getKey(), $ownerLink->token, CarbonImmutable::now())->contents);
        self::assertNull($access->read($attacker, $artifact->getKey(), $ownerLink->token, CarbonImmutable::now()));

        $membership = Membership::query()->where('workspace_id', $workspace->getKey())->where('user_id', $owner->getKey())->firstOrFail();
        $membership->update(['active' => false, 'version' => $membership->version + 1]);
        self::assertNull($access->read($owner, $artifact->getKey(), $ownerLink->token, CarbonImmutable::now()));
        self::assertNotSame($workspace->getKey(), $otherWorkspace->getKey());
    }

    public function test_dg_report_004_artifact_payload_is_encrypted_and_synthetic_secret_is_not_exposed(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $secret = 'DG_SECRET_SENTINEL_REPORT_004';
        $service = app(ArtifactAccessService::class);
        $artifact = $service->store($workspace->getKey(), 'log', 'console.log', 'safe output '.$secret, CarbonImmutable::now(), [$secret]);
        $link = $service->issueLink($owner, $artifact, CarbonImmutable::now());
        $diskContents = Storage::disk('private-artifacts')->get($artifact->storage_key);

        self::assertStringNotContainsString($secret, $diskContents);
        $readable = $service->read($owner, $artifact->getKey(), $link->token, CarbonImmutable::now())->contents;
        self::assertSame('safe output [REDACTED]', $readable);
        self::assertStringNotContainsString($secret, $readable);
        self::assertStringNotContainsString($secret, json_encode($artifact->fresh()->getAttributes(), JSON_THROW_ON_ERROR));
    }

    public function test_dg_report_006_and_012_expired_or_revoked_links_and_expired_artifacts_are_denied(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $service = app(ArtifactAccessService::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $artifact = $service->store($workspace->getKey(), 'report', 'report.json', 'private', $now);
        $link = $service->issueLink($owner, $artifact, $now);

        self::assertNull($service->read($owner, $artifact->getKey(), $link->token, $link->expiresAt));
        self::assertTrue($service->revokeLink($owner, $artifact, $link->link->getKey(), $now));
        self::assertNull($service->read($owner, $artifact->getKey(), $link->token, $now));

        $expiredArtifact = $service->store($workspace->getKey(), 'report', 'expired.json', 'expired', $now);
        $freshLink = $service->issueLink($owner, $expiredArtifact, $now);
        $expiredArtifact->update(['expires_at' => $now->subSecond()]);
        self::assertNull($service->read($owner, $expiredArtifact->getKey(), $freshLink->token, $now));
    }

    public function test_dg_report_010_uses_configurable_development_retention_default(): void
    {
        config(['devguard-artifacts.report_retention_days' => 30]);
        [$owner, $workspace] = $this->workspaceWithOwner();
        $service = app(ArtifactAccessService::class);
        $createdAt = CarbonImmutable::parse('2026-10-01T00:00:00Z');
        $artifact = $service->store($workspace->getKey(), 'report', 'report.json', 'retained', $createdAt);
        $day29 = $createdAt->addDays(29);
        $link = $service->issueLink($owner, $artifact, $day29);

        self::assertNotNull($service->read($owner, $artifact->getKey(), $link->token, $day29));
        self::assertThrows(fn () => $service->issueLink($owner, $artifact, $createdAt->addDays(30)), ArtifactAccessDenied::class);
    }

    public function test_dg_report_011_cleanup_removes_only_report_artifacts_and_preserves_audit_and_financial_data(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $service = app(ArtifactAccessService::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $expired = $service->store($workspace->getKey(), 'report', 'expired.json', 'expired', $now->subDays(31));
        $recent = $service->store($workspace->getKey(), 'log', 'recent.log', 'recent', $now);
        $auditCount = AuditEvent::query()->count();
        $financialTableNames = ['credit_accounts', 'credit_ledger_entries', 'billing_events'];
        $before = [];
        foreach ($financialTableNames as $table) {
            $before[$table] = Schema::hasTable($table);
        }

        $deleted = app(ArtifactLifecycleService::class)->purgeExpiredReports($now);

        self::assertSame(1, $deleted);
        self::assertDatabaseMissing('artifacts', ['id' => $expired->getKey()]);
        self::assertDatabaseHas('artifacts', ['id' => $recent->getKey()]);
        self::assertSame($auditCount, AuditEvent::query()->count());
        foreach ($before as $table => $existed) {
            self::assertSame($existed, Schema::hasTable($table));
        }
        self::assertFalse(Storage::disk('private-artifacts')->exists($expired->storage_key));
    }

    private function workspaceWithOwner(): array
    {
        $suffix = str()->random(8);
        $user = User::query()->create([
            'name' => 'Synthetic Artifact User',
            'email' => $suffix.'@example.test',
            'github_user_id' => 'github-artifact-'.$suffix,
        ]);
        $workspace = Workspace::query()->create(['name' => 'Synthetic Artifact Workspace '.$suffix, 'personal_owner_user_id' => $user->getKey()]);
        Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $user->getKey(),
            'role' => Role::Owner,
            'active' => true,
            'version' => 1,
        ]);

        return [$user, $workspace];
    }
}
