<?php

namespace Tests\Feature\Credits;

use App\Domain\Credits\CreditKind;
use App\Domain\Credits\CreditLedgerService;
use App\Domain\Credits\CreditReservationDenied;
use App\Domain\Credits\DevelopmentCreditSettlementService;
use App\Domain\Identity\Role;
use App\Models\CreditAccount;
use App\Models\CreditReservation;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

final class DevelopmentCreditSettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_policy_is_test_only_and_does_not_approve_production_rules(): void
    {
        self::assertTrue(config('devguard-credit-test-policy.test_only'));
        self::assertFalse(config('devguard-credit-test-policy.production_policies_approved'));
    }

    public function test_completed_scan_consumes_one_reserved_credit_and_replay_is_idempotent(): void
    {
        [$owner, $workspace] = $this->workspaceWithAccount(CreditKind::Scan, 2);
        $reservation = $this->reserve($owner, $workspace, CreditKind::Scan, 1, 'scan-complete');
        $service = app(DevelopmentCreditSettlementService::class);
        $at = CarbonImmutable::parse('2026-10-22T10:05:00Z');

        $entry = $service->settle($reservation->getKey(), 'completed', $at);
        $replay = $service->settle($reservation->getKey(), 'completed', $at->addMinute());

        self::assertSame($entry->getKey(), $replay->getKey());
        self::assertSame('settled', $reservation->fresh()->state);
        self::assertSame(1, $reservation->account->fresh()->available_credits);
        self::assertSame(0, $reservation->account->fresh()->reserved_credits);
        self::assertSame(1, $reservation->account->fresh()->consumed_credits);
        self::assertDatabaseCount('credit_ledger_entries', 2);
    }

    public function test_cancellation_before_execution_releases_a_scan_reservation(): void
    {
        [$owner, $workspace] = $this->workspaceWithAccount(CreditKind::Scan, 2);
        $reservation = $this->reserve($owner, $workspace, CreditKind::Scan, 1, 'scan-cancel-before');

        $entry = app(DevelopmentCreditSettlementService::class)->settle(
            $reservation->getKey(),
            'cancelled_before_execution',
            CarbonImmutable::parse('2026-10-22T10:05:00Z'),
        );

        self::assertSame('release', $entry->entry_type);
        self::assertSame('released', $reservation->fresh()->state);
        self::assertSame(2, $reservation->account->fresh()->available_credits);
        self::assertSame(0, $reservation->account->fresh()->reserved_credits);
        self::assertSame(0, $reservation->account->fresh()->consumed_credits);
    }

    public function test_cancellation_after_execution_consumes_the_scan_reservation(): void
    {
        [$owner, $workspace] = $this->workspaceWithAccount(CreditKind::Scan, 2);
        $reservation = $this->reserve($owner, $workspace, CreditKind::Scan, 1, 'scan-cancel-after');

        $entry = app(DevelopmentCreditSettlementService::class)->settle(
            $reservation->getKey(),
            'cancelled_after_execution',
            CarbonImmutable::parse('2026-10-22T10:05:00Z'),
        );

        self::assertSame('consume', $entry->entry_type);
        self::assertSame('settled', $reservation->fresh()->state);
        self::assertSame(1, $reservation->account->fresh()->available_credits);
        self::assertSame(0, $reservation->account->fresh()->reserved_credits);
        self::assertSame(1, $reservation->account->fresh()->consumed_credits);
    }

    public function test_executed_unsuccessful_fix_consumes_once_under_the_development_policy(): void
    {
        [$owner, $workspace] = $this->workspaceWithAccount(CreditKind::Fix, 3);
        $reservation = $this->reserve($owner, $workspace, CreditKind::Fix, 2, 'fix-unsuccessful');

        $entry = app(DevelopmentCreditSettlementService::class)->settle(
            $reservation->getKey(),
            'failed_after_execution',
            CarbonImmutable::parse('2026-10-22T10:05:00Z'),
        );

        self::assertSame('consume', $entry->entry_type);
        self::assertSame(1, $reservation->account->fresh()->available_credits);
        self::assertSame(0, $reservation->account->fresh()->reserved_credits);
        self::assertSame(2, $reservation->account->fresh()->consumed_credits);
    }

    public function test_replayed_reservation_cannot_be_settled_with_a_different_outcome(): void
    {
        [$owner, $workspace] = $this->workspaceWithAccount(CreditKind::Scan, 2);
        $reservation = $this->reserve($owner, $workspace, CreditKind::Scan, 1, 'scan-outcome-conflict');
        $service = app(DevelopmentCreditSettlementService::class);
        $service->settle($reservation->getKey(), 'completed', CarbonImmutable::parse('2026-10-22T10:05:00Z'));

        self::assertThrows(fn () => $service->settle(
            $reservation->getKey(),
            'cancelled_before_execution',
            CarbonImmutable::parse('2026-10-22T10:06:00Z'),
        ), CreditReservationDenied::class);

        self::assertDatabaseCount('credit_ledger_entries', 2);
        self::assertSame(1, $reservation->account->fresh()->consumed_credits);
    }

    public function test_development_settlement_is_unavailable_outside_the_testing_environment(): void
    {
        $this->app['env'] = 'local';

        self::assertThrows(fn () => app(DevelopmentCreditSettlementService::class)->settle(
            'synthetic-reservation',
            'completed',
            CarbonImmutable::parse('2026-10-22T10:05:00Z'),
        ), LogicException::class);
    }

    private function workspaceWithAccount(CreditKind $kind, int $available): array
    {
        $suffix = str()->random(8);
        $owner = User::query()->create([
            'name' => 'Synthetic Settlement Owner',
            'email' => $suffix.'@example.test',
            'github_user_id' => 'github-settlement-'.$suffix,
        ]);
        $workspace = Workspace::query()->create(['name' => 'Synthetic Settlement Workspace '.$suffix, 'personal_owner_user_id' => $owner->getKey()]);
        Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $owner->getKey(),
            'role' => Role::Owner,
            'active' => true,
            'version' => 1,
        ]);
        CreditAccount::query()->create([
            'workspace_id' => $workspace->getKey(),
            'credit_kind' => $kind,
            'available_credits' => $available,
            'reserved_credits' => 0,
            'consumed_credits' => 0,
        ]);

        return [$owner, $workspace];
    }

    private function reserve(User $owner, Workspace $workspace, CreditKind $kind, int $amount, string $operation): CreditReservation
    {
        return app(CreditLedgerService::class)->reserve(
            $owner,
            $workspace->getKey(),
            $kind,
            'logical-'.$operation,
            'request-'.$operation,
            ['fixture' => 'synthetic'],
            $amount,
            CarbonImmutable::parse('2026-10-22T10:00:00Z'),
        );
    }
}
