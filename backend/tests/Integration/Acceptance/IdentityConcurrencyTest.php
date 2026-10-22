<?php

namespace Tests\Integration\Acceptance;

use App\Domain\Identity\MembershipService;
use App\Domain\Identity\Role;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class IdentityConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    /** @var list<string> */
    private array $temporaryDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::assertSame('pgsql', config('database.default'));
        self::assertSame('UTC', config('database.connections.pgsql.timezone'));
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            File::deleteDirectory($directory);
        }

        parent::tearDown();
    }

    public function test_dg_iam_018_concurrent_invitation_replay_has_one_effect(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $intended = $this->user('github-concurrent-invite');
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $issued = app(MembershipService::class)->issueInvitation(
            $owner->getKey(),
            $workspace->getKey(),
            $intended->github_user_id,
            Role::Member,
            $now->addHour(),
        );

        $results = $this->race([
            ['operation' => 'accept_invitation', 'token' => $issued->rawToken, 'user_id' => $intended->getKey(), 'at' => $now->toIso8601String()],
            ['operation' => 'accept_invitation', 'token' => $issued->rawToken, 'user_id' => $intended->getKey(), 'at' => $now->toIso8601String()],
        ]);

        sort($results);
        self::assertSame(['accepted', 'invitation_used'], $results);
        self::assertSame(1, Membership::query()->where('workspace_id', $workspace->getKey())->where('user_id', $intended->getKey())->count());
    }

    public function test_dg_iam_020_competing_transfers_preserve_exactly_one_owner(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        $candidateA = $this->user('github-owner-candidate-a');
        $candidateB = $this->user('github-owner-candidate-b');
        $this->membership($workspace, $candidateA, Role::Member);
        $this->membership($workspace, $candidateB, Role::Member);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $service = app(MembershipService::class);
        $transferA = $service->initiateOwnershipTransfer($owner->getKey(), $workspace->getKey(), $candidateA->getKey(), $now);
        $transferB = $service->initiateOwnershipTransfer($owner->getKey(), $workspace->getKey(), $candidateB->getKey(), $now);

        $results = $this->race([
            ['operation' => 'accept_ownership_transfer', 'transfer_id' => $transferA->getKey(), 'user_id' => $candidateA->getKey(), 'at' => $now->addMinute()->toIso8601String()],
            ['operation' => 'accept_ownership_transfer', 'transfer_id' => $transferB->getKey(), 'user_id' => $candidateB->getKey(), 'at' => $now->addMinute()->toIso8601String()],
        ]);

        sort($results);
        self::assertSame(['accepted', 'ownership_current_owner_invalid'], $results);
        self::assertSame(1, Membership::query()->where('workspace_id', $workspace->getKey())->where('role', Role::Owner->value)->where('active', true)->count());
        $activeOwnerId = Membership::query()->where('workspace_id', $workspace->getKey())->where('role', Role::Owner->value)->where('active', true)->value('user_id');
        self::assertContains((int) $activeOwnerId, [$candidateA->getKey(), $candidateB->getKey()]);
        self::assertSame((int) $activeOwnerId, (int) $workspace->fresh()->personal_owner_user_id);
    }

    /** @param list<array<string, mixed>> $jobs
     * @return list<string>
     */
    private function race(array $jobs): array
    {
        $directory = storage_path('framework/testing/concurrency/'.Str::ulid());
        File::ensureDirectoryExists($directory);
        $this->temporaryDirectories[] = $directory;
        $goPath = $directory.'/go';
        $processes = [];
        $resultPaths = [];

        foreach ($jobs as $index => $job) {
            $readyPath = $directory."/ready-{$index}";
            $resultPath = $directory."/result-{$index}.json";
            $jobPath = $directory."/job-{$index}.json";
            File::put($jobPath, json_encode([
                ...$job,
                'ready_path' => $readyPath,
                'go_path' => $goPath,
                'result_path' => $resultPath,
            ], JSON_THROW_ON_ERROR), true);

            $process = new Process([PHP_BINARY, base_path('tests/Support/identity-concurrency-worker.php'), $jobPath], base_path(), ['APP_ENV' => 'testing']);
            $process->setTimeout(20);
            $process->start();
            $processes[] = $process;
            $resultPaths[] = $resultPath;
        }

        $deadline = microtime(true) + 10;
        do {
            $ready = count(File::glob($directory.'/ready-*')) === count($jobs);
            if (! $ready) {
                usleep(10_000);
            }
        } while (! $ready && microtime(true) < $deadline);
        self::assertTrue($ready, 'Concurrent workers did not reach the start barrier.');

        File::put($goPath, 'go', true);
        foreach ($processes as $process) {
            $process->wait();
            self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
        }

        return array_map(
            fn (string $path): string => json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR)['result'],
            $resultPaths,
        );
    }

    /** @return array{User, Workspace} */
    private function workspaceWithOwner(): array
    {
        $owner = $this->user('github-concurrency-owner-'.Str::random(8));
        $workspace = Workspace::query()->create([
            'name' => 'Concurrent Synthetic Workspace',
            'personal_owner_user_id' => $owner->getKey(),
        ]);
        $this->membership($workspace, $owner, Role::Owner);

        return [$owner, $workspace];
    }

    private function user(string $githubId): User
    {
        return User::query()->create([
            'name' => 'Concurrent Synthetic User',
            'email' => $githubId.'@example.test',
            'github_user_id' => $githubId,
        ]);
    }

    private function membership(Workspace $workspace, User $user, Role $role): Membership
    {
        return Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $user->getKey(),
            'role' => $role,
            'active' => true,
            'version' => 1,
        ]);
    }
}
