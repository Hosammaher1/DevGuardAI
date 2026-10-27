<?php

namespace App\Domain\Credits;

use App\Models\CreditAccount;
use App\Models\CreditLedgerEntry;
use App\Models\CreditReservation;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;

/** Applies synthetic ND03 outcomes in tests; this is not a production settlement service. */
final readonly class DevelopmentCreditSettlementService
{
    public function settle(string $reservationId, string $scenario, CarbonImmutable $settledAt): CreditLedgerEntry
    {
        $this->assertTestPolicyAvailable();

        $reservation = CreditReservation::query()->whereKey($reservationId)->first();
        if ($reservation === null) {
            throw new CreditReservationDenied('credit_reservation_unavailable');
        }

        $transition = config('devguard-credit-test-policy.settlement_scenarios.'.$reservation->credit_kind.'.'.$scenario);
        if (! in_array($transition, ['consume', 'release'], true)) {
            throw new CreditReservationDenied('development_settlement_scenario_unavailable');
        }

        $targetState = $transition === 'consume' ? 'settled' : 'released';
        $idempotencyHash = hash('sha256', 'development-credit-settlement:'.$reservationId);
        $fingerprint = hash('sha256', json_encode([
            'reservation' => $reservationId,
            'scenario' => $scenario,
            'transition' => $transition,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($reservation, $reservationId, $settledAt, $targetState, $transition, $idempotencyHash, $fingerprint): CreditLedgerEntry {
            Workspace::query()->whereKey($reservation->workspace_id)->lockForUpdate()->firstOrFail();

            $lockedReservation = CreditReservation::query()->whereKey($reservationId)->lockForUpdate()->firstOrFail();
            $account = CreditAccount::query()
                ->whereKey($lockedReservation->credit_account_id)
                ->where('workspace_id', $lockedReservation->workspace_id)
                ->where('credit_kind', $lockedReservation->credit_kind)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReservation->state !== 'held') {
                $existingEntry = CreditLedgerEntry::query()
                    ->where('workspace_id', $lockedReservation->workspace_id)
                    ->where('credit_reservation_id', $reservationId)
                    ->where('idempotency_hash', $idempotencyHash)
                    ->first();

                if ($lockedReservation->state === $targetState && $existingEntry !== null && hash_equals($existingEntry->request_fingerprint, $fingerprint)) {
                    return $existingEntry;
                }

                throw new CreditReservationDenied('credit_settlement_conflict');
            }

            if ($account->reserved_credits < $lockedReservation->amount) {
                throw new CreditReservationDenied('credit_reservation_invariant_failed');
            }

            $amount = $lockedReservation->amount;
            $released = $transition === 'release';
            $account->update([
                'available_credits' => $account->available_credits + ($released ? $amount : 0),
                'reserved_credits' => $account->reserved_credits - $amount,
                'consumed_credits' => $account->consumed_credits + ($released ? 0 : $amount),
            ]);
            $lockedReservation->update(['state' => $targetState]);

            return CreditLedgerEntry::query()->create([
                'workspace_id' => $lockedReservation->workspace_id,
                'credit_account_id' => $account->getKey(),
                'credit_reservation_id' => $reservationId,
                'initiating_user_id' => $lockedReservation->initiating_user_id,
                'entry_type' => $transition,
                'idempotency_hash' => $idempotencyHash,
                'request_fingerprint' => $fingerprint,
                'amount' => $amount,
                'available_delta' => $released ? $amount : 0,
                'reserved_delta' => -$amount,
                'consumed_delta' => $released ? 0 : $amount,
                'created_at' => $settledAt,
            ]);
        }, 3);
    }

    private function assertTestPolicyAvailable(): void
    {
        if (! app()->environment('testing')
            || config('devguard-credit-test-policy.test_only') !== true
            || config('devguard-credit-test-policy.production_policies_approved') !== false) {
            throw new LogicException('development_credit_settlement_unavailable');
        }
    }
}
