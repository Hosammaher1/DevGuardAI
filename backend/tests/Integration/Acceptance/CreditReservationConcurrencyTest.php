<?php

namespace Tests\Integration\Acceptance;

use App\Domain\Credits\CreditKind;
use App\Domain\Credits\CreditLedgerService;
use App\Domain\Identity\Role;
use App\Models\CreditAccount;
use App\Models\CreditLedgerEntry;
use App\Models\CreditReservation;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class CreditReservationConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    /** @var list<string> */
    private array $temporaryDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();
        self::assertSame('pgsql', config('database.default'));
        self::assertSame('devguard_test', config('database.connections.pgsql.database'));
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            File::deleteDirectory($directory);
        }
        parent::tearDown();
    }

    public function test_concurrent_scan_reservations_cannot_spend_the_same_last_credit(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        CreditAccount::query()->create([
            'workspace_id' => $workspace->getKey(),
            'credit_kind' => CreditKind::Scan,
            'available_credits' => 1,
            'reserved_credits' => 0,
            'consumed_credits' => 0,
        ]);

        $results = $this->race([
            ['user_id' => $owner->getKey(), 'workspace_id' => $workspace->getKey(), 'operation_id' => 'scan-race-a', 'idempotency_key' => 'scan-request-a'],
            ['user_id' => $owner->getKey(), 'workspace_id' => $workspace->getKey(), 'operation_id' => 'scan-race-b', 'idempotency_key' => 'scan-request-b'],
        ]);
        sort($results);

        self::assertSame(1, count(array_filter($results, static fn (string $result): bool => $result === 'insufficient_credits')));
        self::assertCount(1, array_filter($results, static fn (string $result): bool => str_starts_with($result, 'reserved:')));
        $account = CreditAccount::query()->where('workspace_id', $workspace->getKey())->where('credit_kind', CreditKind::Scan)->firstOrFail();
        self::assertSame(0, $account->available_credits);
        self::assertSame(1, $account->reserved_credits);
        self::assertSame(1, CreditReservation::query()->where('workspace_id', $workspace->getKey())->count());
        self::assertSame(1, CreditLedgerEntry::query()->where('workspace_id', $workspace->getKey())->count());
    }

    public function test_concurrent_fix_reservations_cannot_spend_the_same_last_credit(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        CreditAccount::query()->create([
            'workspace_id' => $workspace->getKey(),
            'credit_kind' => CreditKind::Fix,
            'available_credits' => 1,
            'reserved_credits' => 0,
            'consumed_credits' => 0,
        ]);

        $results = $this->race([
            ['user_id' => $owner->getKey(), 'workspace_id' => $workspace->getKey(), 'kind' => 'fix', 'operation_id' => 'fix-race-a', 'idempotency_key' => 'fix-request-a'],
            ['user_id' => $owner->getKey(), 'workspace_id' => $workspace->getKey(), 'kind' => 'fix', 'operation_id' => 'fix-race-b', 'idempotency_key' => 'fix-request-b'],
        ]);
        sort($results);

        self::assertSame(1, count(array_filter($results, static fn (string $result): bool => $result === 'insufficient_credits')));
        self::assertCount(1, array_filter($results, static fn (string $result): bool => str_starts_with($result, 'reserved:')));
        $account = CreditAccount::query()->where('workspace_id', $workspace->getKey())->where('credit_kind', CreditKind::Fix)->firstOrFail();
        self::assertSame(0, $account->available_credits);
        self::assertSame(1, $account->reserved_credits);
        self::assertSame(1, CreditReservation::query()->where('workspace_id', $workspace->getKey())->where('credit_kind', CreditKind::Fix)->count());
    }

    public function test_concurrent_replay_of_one_scan_operation_returns_one_reservation_and_one_ledger_effect(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        CreditAccount::query()->create([
            'workspace_id' => $workspace->getKey(),
            'credit_kind' => CreditKind::Scan,
            'available_credits' => 3,
            'reserved_credits' => 0,
            'consumed_credits' => 0,
        ]);
        $request = ['user_id' => $owner->getKey(), 'workspace_id' => $workspace->getKey(), 'kind' => 'scan', 'operation_id' => 'logical-replay', 'idempotency_key' => 'same-request-key'];

        $results = $this->race([$request, $request]);

        self::assertSame($results[0], $results[1]);
        self::assertStringStartsWith('reserved:', $results[0]);
        self::assertSame(1, CreditReservation::query()->where('workspace_id', $workspace->getKey())->count());
        self::assertSame(1, CreditLedgerEntry::query()->where('workspace_id', $workspace->getKey())->count());
    }

    public function test_concurrent_development_settlement_replay_creates_one_terminal_effect(): void
    {
        [$owner, $workspace] = $this->workspaceWithOwner();
        CreditAccount::query()->create([
            'workspace_id' => $workspace->getKey(),
            'credit_kind' => CreditKind::Scan,
            'available_credits' => 1,
            'reserved_credits' => 0,
            'consumed_credits' => 0,
        ]);
        $reservation = app(CreditLedgerService::class)->reserve(
            $owner,
            $workspace->getKey(),
            CreditKind::Scan,
            'settlement-race-operation',
            'settlement-race-request',
            ['source' => 'synthetic-settlement-race'],
            1,
            CarbonImmutable::parse('2026-10-22T10:00:00Z'),
        );

        $results = $this->race([
            ['action' => 'settle', 'reservation_id' => $reservation->getKey(), 'scenario' => 'completed'],
            ['action' => 'settle', 'reservation_id' => $reservation->getKey(), 'scenario' => 'completed'],
        ]);

        self::assertSame($results[0], $results[1]);
        self::assertStringStartsWith('settled:', $results[0]);
        self::assertSame('settled', $reservation->fresh()->state);
        self::assertSame(0, $reservation->account->fresh()->reserved_credits);
        self::assertSame(1, $reservation->account->fresh()->consumed_credits);
        self::assertSame(2, CreditLedgerEntry::query()->where('workspace_id', $workspace->getKey())->count());
    }

    /** @param list<array{user_id: int, workspace_id: string, operation_id: string, idempotency_key: string, kind?: string}> $jobs
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
            $jobPath = $directory."/job-{$index}.json";
            $resultPath = $directory."/result-{$index}.json";
            File::put($jobPath, json_encode([
                ...$job,
                'ready_path' => $directory."/ready-{$index}",
                'go_path' => $goPath,
                'result_path' => $resultPath,
            ], JSON_THROW_ON_ERROR), true);
            $process = new Process([PHP_BINARY, base_path('tests/Support/credit-reservation-worker.php'), $jobPath], base_path(), ['APP_ENV' => 'testing']);
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
        self::assertTrue($ready, 'Concurrent credit workers did not reach the start barrier.');

        File::put($goPath, 'go', true);
        foreach ($processes as $process) {
            $process->wait();
            self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
        }

        return array_map(fn (string $path): string => json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR)['result'], $resultPaths);
    }

    /** @return array{User, Workspace} */
    private function workspaceWithOwner(): array
    {
        $suffix = 'credit-race-'.Str::random(8);
        $owner = User::query()->create([
            'name' => 'Synthetic Credit Race Owner',
            'email' => $suffix.'@example.test',
            'github_user_id' => $suffix,
        ]);
        $workspace = Workspace::query()->create(['name' => 'Synthetic Credit Race Workspace', 'personal_owner_user_id' => $owner->getKey()]);
        Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $owner->getKey(),
            'role' => Role::Owner,
            'active' => true,
            'version' => 1,
        ]);

        return [$owner, $workspace];
    }
}
