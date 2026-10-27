<?php

namespace Tests\Feature\Credits;

use App\Domain\Credits\CreditKind;
use App\Domain\Credits\CreditLedgerService;
use App\Domain\Credits\CreditReservationDenied;
use App\Domain\Identity\Role;
use App\Models\CreditAccount;
use App\Models\CreditLedgerEntry;
use App\Models\CreditReservation;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

final class CreditReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_and_fix_reservations_are_separate_and_idempotent(): void
    {
        [$owner, $workspace] = $this->workspaceWithRole(Role::Owner);
        $this->account($workspace, CreditKind::Scan, 2);
        $this->account($workspace, CreditKind::Fix, 3);
        $ledger = app(CreditLedgerService::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');

        $scan = $ledger->reserve($owner, $workspace->getKey(), CreditKind::Scan, 'logical-scan-1', 'request-scan-1', ['revision' => 'sha-synthetic'], 1, $now);
        $scanReplay = $ledger->reserve($owner, $workspace->getKey(), CreditKind::Scan, 'logical-scan-1', 'request-scan-1', ['revision' => 'sha-synthetic'], 1, $now->addSecond());
        $fix = $ledger->reserve($owner, $workspace->getKey(), CreditKind::Fix, 'logical-fix-1', 'request-fix-1', ['plan' => 'synthetic-plan'], 2, $now);

        self::assertSame($scan->getKey(), $scanReplay->getKey());
        self::assertSame(1, $scan->account->fresh()->available_credits);
        self::assertSame(1, $scan->account->fresh()->reserved_credits);
        self::assertSame(0, $scan->account->fresh()->consumed_credits);
        self::assertSame(1, $fix->account->fresh()->available_credits);
        self::assertSame(2, $fix->account->fresh()->reserved_credits);
        self::assertDatabaseCount('credit_ledger_entries', 2);
    }

    public function test_insufficient_balance_denies_without_negative_balance_or_ledger_effect(): void
    {
        [$owner, $workspace] = $this->workspaceWithRole(Role::Owner);
        $account = $this->account($workspace, CreditKind::Scan, 1);
        $ledger = app(CreditLedgerService::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');

        $ledger->reserve($owner, $workspace->getKey(), CreditKind::Scan, 'logical-scan-1', 'request-scan-1', [], 1, $now);
        self::assertThrows(fn () => $ledger->reserve($owner, $workspace->getKey(), CreditKind::Scan, 'logical-scan-2', 'request-scan-2', [], 1, $now), CreditReservationDenied::class);

        self::assertSame(0, $account->fresh()->available_credits);
        self::assertSame(1, $account->fresh()->reserved_credits);
        self::assertDatabaseCount('credit_ledger_entries', 1);
    }

    public function test_scan_reservation_is_exactly_one_credit_per_full_run(): void
    {
        [$owner, $workspace] = $this->workspaceWithRole(Role::Owner);
        $account = $this->account($workspace, CreditKind::Scan, 5);
        $ledger = app(CreditLedgerService::class);

        self::assertThrows(fn () => $ledger->reserve(
            $owner,
            $workspace->getKey(),
            CreditKind::Scan,
            'logical-multi-credit-scan',
            'request-multi-credit-scan',
            ['revision' => 'sha-synthetic'],
            2,
            CarbonImmutable::parse('2026-10-22T10:00:00Z'),
        ), CreditReservationDenied::class);

        self::assertSame(5, $account->fresh()->available_credits);
        self::assertSame(0, $account->fresh()->reserved_credits);
        self::assertDatabaseCount('credit_reservations', 0);
        self::assertDatabaseCount('credit_ledger_entries', 0);
    }

    public function test_credit_ledger_entries_are_append_only(): void
    {
        [$owner, $workspace] = $this->workspaceWithRole(Role::Owner);
        $this->account($workspace, CreditKind::Scan, 2);
        $reservation = app(CreditLedgerService::class)->reserve(
            $owner,
            $workspace->getKey(),
            CreditKind::Scan,
            'immutable-ledger-operation',
            'immutable-ledger-key',
            [],
            1,
            CarbonImmutable::parse('2026-10-22T10:00:00Z'),
        );
        $entry = CreditLedgerEntry::query()->where('credit_reservation_id', $reservation->getKey())->firstOrFail();

        self::assertThrows(fn () => $entry->update(['amount' => 999]), LogicException::class);
        self::assertThrows(fn () => $entry->delete(), LogicException::class);
    }

    public function test_workspace_or_account_deletion_cannot_cascade_erase_credit_history(): void
    {
        [$owner, $workspace] = $this->workspaceWithRole(Role::Owner);
        $account = $this->account($workspace, CreditKind::Scan, 2);
        $reservation = app(CreditLedgerService::class)->reserve(
            $owner,
            $workspace->getKey(),
            CreditKind::Scan,
            'retained-ledger-operation',
            'retained-ledger-key',
            [],
            1,
            CarbonImmutable::parse('2026-10-22T10:00:00Z'),
        );

        self::assertThrows(fn () => $account->delete(), QueryException::class);
        self::assertThrows(fn () => $workspace->delete(), QueryException::class);
        self::assertDatabaseHas('credit_reservations', ['id' => $reservation->getKey()]);
        self::assertDatabaseHas('credit_ledger_entries', ['credit_reservation_id' => $reservation->getKey()]);
    }

    public function test_database_rejects_ledger_entry_linked_to_another_workspaces_reservation(): void
    {
        [$ownerA, $workspaceA] = $this->workspaceWithRole(Role::Owner);
        [$ownerB, $workspaceB] = $this->workspaceWithRole(Role::Owner);
        $this->account($workspaceA, CreditKind::Scan, 2);
        $accountB = $this->account($workspaceB, CreditKind::Scan, 2);
        $reservationA = app(CreditLedgerService::class)->reserve(
            $ownerA,
            $workspaceA->getKey(),
            CreditKind::Scan,
            'workspace-a-ledger-operation',
            'workspace-a-ledger-key',
            [],
            1,
            CarbonImmutable::parse('2026-10-22T10:00:00Z'),
        );

        self::assertThrows(fn () => CreditLedgerEntry::query()->create([
            'workspace_id' => $workspaceB->getKey(),
            'credit_account_id' => $accountB->getKey(),
            'credit_reservation_id' => $reservationA->getKey(),
            'initiating_user_id' => $ownerB->getKey(),
            'entry_type' => 'reserve',
            'idempotency_hash' => hash('sha256', 'forged-ledger-entry'),
            'request_fingerprint' => hash('sha256', 'forged-ledger-request'),
            'amount' => 1,
            'available_delta' => -1,
            'reserved_delta' => 1,
            'consumed_delta' => 0,
            'created_at' => CarbonImmutable::parse('2026-10-22T10:00:00Z'),
        ]), QueryException::class);
    }

    public function test_reused_idempotency_key_or_logical_operation_with_changed_payload_is_rejected(): void
    {
        [$owner, $workspace] = $this->workspaceWithRole(Role::Owner);
        $this->account($workspace, CreditKind::Scan, 5);
        $ledger = app(CreditLedgerService::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $ledger->reserve($owner, $workspace->getKey(), CreditKind::Scan, 'logical-scan-1', 'request-1', ['revision' => 'sha-a'], 1, $now);

        self::assertThrows(fn () => $ledger->reserve($owner, $workspace->getKey(), CreditKind::Scan, 'logical-scan-2', 'request-1', ['revision' => 'sha-a'], 1, $now), CreditReservationDenied::class);
        self::assertThrows(fn () => $ledger->reserve($owner, $workspace->getKey(), CreditKind::Scan, 'logical-scan-1', 'request-2', ['revision' => 'sha-b'], 1, $now), CreditReservationDenied::class);
        self::assertSame(4, CreditAccount::query()->where('workspace_id', $workspace->getKey())->where('credit_kind', CreditKind::Scan)->value('available_credits'));
        self::assertDatabaseCount('credit_ledger_entries', 1);
    }

    public function test_cross_workspace_actor_and_role_without_fix_authority_cannot_reserve(): void
    {
        [$owner, $workspace] = $this->workspaceWithRole(Role::Owner);
        [$member, $memberWorkspace] = $this->workspaceWithRole(Role::Member);
        $this->account($workspace, CreditKind::Scan, 3);
        $this->account($workspace, CreditKind::Fix, 3);
        $ledger = app(CreditLedgerService::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');

        self::assertThrows(fn () => $ledger->reserve($member, $workspace->getKey(), CreditKind::Scan, 'cross-tenant-job', 'cross-tenant-key', [], 1, $now), CreditReservationDenied::class);
        self::assertThrows(fn () => $ledger->reserve($member, $memberWorkspace->getKey(), CreditKind::Fix, 'member-fix-job', 'member-fix-key', [], 1, $now), CreditReservationDenied::class);
        self::assertSame(3, CreditAccount::query()->where('workspace_id', $workspace->getKey())->where('credit_kind', CreditKind::Scan)->value('available_credits'));
        self::assertSame(0, CreditAccount::query()->where('workspace_id', $memberWorkspace->getKey())->count());
        self::assertDatabaseCount('credit_ledger_entries', 0);
    }

    public function test_database_rejects_reservation_bound_to_another_workspace_account(): void
    {
        [$owner, $workspace] = $this->workspaceWithRole(Role::Owner);
        [, $otherWorkspace] = $this->workspaceWithRole(Role::Owner);
        $otherAccount = $this->account($otherWorkspace, CreditKind::Scan, 2);

        self::assertThrows(fn () => CreditReservation::query()->create([
            'workspace_id' => $workspace->getKey(),
            'credit_account_id' => $otherAccount->getKey(),
            'credit_kind' => CreditKind::Scan,
            'initiating_user_id' => $owner->getKey(),
            'logical_operation_id' => 'forged-cross-tenant-reservation',
            'idempotency_hash' => hash('sha256', 'fixture-idempotency'),
            'request_fingerprint' => hash('sha256', 'fixture-request'),
            'amount' => 1,
            'state' => 'held',
            'created_at' => CarbonImmutable::parse('2026-10-22T10:00:00Z'),
        ]), QueryException::class);
    }

    public function test_replay_from_a_different_workspace_member_does_not_inherit_the_initiators_reservation(): void
    {
        [$owner, $workspace] = $this->workspaceWithRole(Role::Owner);
        $member = User::query()->create([
            'name' => 'Synthetic Second Credit User',
            'email' => str()->random(8).'@example.test',
            'github_user_id' => 'github-credit-second-'.str()->random(8),
        ]);
        Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $member->getKey(),
            'role' => Role::Member,
            'active' => true,
            'version' => 1,
        ]);
        $this->account($workspace, CreditKind::Scan, 5);
        $ledger = app(CreditLedgerService::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $ledger->reserve($owner, $workspace->getKey(), CreditKind::Scan, 'logical-shared-op', 'shared-key', ['revision' => 'synthetic-sha'], 1, $now);

        self::assertThrows(fn () => $ledger->reserve($member, $workspace->getKey(), CreditKind::Scan, 'logical-shared-op', 'shared-key', ['revision' => 'synthetic-sha'], 1, $now), CreditReservationDenied::class);
        self::assertSame(4, CreditAccount::query()->where('workspace_id', $workspace->getKey())->where('credit_kind', CreditKind::Scan)->value('available_credits'));
        self::assertDatabaseCount('credit_ledger_entries', 1);
    }

    private function account(Workspace $workspace, CreditKind $kind, int $available): CreditAccount
    {
        return CreditAccount::query()->create([
            'workspace_id' => $workspace->getKey(),
            'credit_kind' => $kind,
            'available_credits' => $available,
            'reserved_credits' => 0,
            'consumed_credits' => 0,
        ]);
    }

    /** @return array{User, Workspace} */
    private function workspaceWithRole(Role $role): array
    {
        $suffix = str()->random(8);
        $user = User::query()->create([
            'name' => 'Synthetic Credit User',
            'email' => $suffix.'@example.test',
            'github_user_id' => 'github-credit-'.$suffix,
        ]);
        $workspace = Workspace::query()->create(['name' => 'Synthetic Credit Workspace '.$suffix, 'personal_owner_user_id' => $user->getKey()]);
        Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $user->getKey(),
            'role' => $role,
            'active' => true,
            'version' => 1,
        ]);

        return [$user, $workspace];
    }
}
