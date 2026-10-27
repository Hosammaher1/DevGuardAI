<?php

namespace App\Domain\Credits;

use App\Domain\Identity\AuthorizationService;
use App\Domain\Identity\WorkspaceAction;
use App\Models\CreditAccount;
use App\Models\CreditLedgerEntry;
use App\Models\CreditReservation;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use JsonException;

final readonly class CreditLedgerService
{
    public function __construct(private AuthorizationService $authorization) {}

    /** @param array<string, mixed> $operationContext */
    public function reserve(
        User $actor,
        string $workspaceId,
        CreditKind $kind,
        string $logicalOperationId,
        string $idempotencyKey,
        array $operationContext,
        int $amount,
        CarbonImmutable $reservedAt,
    ): CreditReservation {
        if ($logicalOperationId === '' || strlen($logicalOperationId) > 128 || $idempotencyKey === '' || strlen($idempotencyKey) > 200 || $amount < 1) {
            throw new CreditReservationDenied('credit_request_invalid');
        }
        if ($kind === CreditKind::Scan && $amount !== 1) {
            throw new CreditReservationDenied('scan_run_credit_amount_invalid');
        }

        $fingerprint = $this->fingerprint((int) $actor->getKey(), $workspaceId, $kind, $logicalOperationId, $operationContext, $amount);
        $idempotencyHash = hash('sha256', $actor->getKey().':'.$kind->value.':'.$idempotencyKey);

        return DB::transaction(function () use ($actor, $workspaceId, $kind, $logicalOperationId, $idempotencyHash, $fingerprint, $amount, $reservedAt): CreditReservation {
            $workspace = Workspace::query()->whereKey($workspaceId)->lockForUpdate()->first();
            $membership = Membership::query()
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $actor->getKey())
                ->lockForUpdate()
                ->first();

            if ($workspace === null || $membership === null || ! $membership->active) {
                throw new CreditReservationDenied;
            }

            $action = $kind === CreditKind::Scan ? WorkspaceAction::StartScans : WorkspaceAction::ApproveAiFixExecution;
            if (! $this->authorization->decide($membership->role, $action, $membership->active, true)->allowed) {
                throw new CreditReservationDenied;
            }

            $account = CreditAccount::query()
                ->where('workspace_id', $workspaceId)
                ->where('credit_kind', $kind->value)
                ->lockForUpdate()
                ->first();
            if ($account === null) {
                throw new CreditReservationDenied('credit_account_unavailable');
            }

            $existingByKey = CreditReservation::query()
                ->where('workspace_id', $workspaceId)
                ->where('idempotency_hash', $idempotencyHash)
                ->first();
            if ($existingByKey !== null) {
                return $this->returnMatchingReservation($existingByKey, $fingerprint);
            }

            $existingByOperation = CreditReservation::query()
                ->where('workspace_id', $workspaceId)
                ->where('credit_kind', $kind->value)
                ->where('logical_operation_id', $logicalOperationId)
                ->first();
            if ($existingByOperation !== null) {
                return $this->returnMatchingReservation($existingByOperation, $fingerprint);
            }

            if ($amount > $account->available_credits) {
                throw new CreditReservationDenied('insufficient_credits');
            }

            $reservation = CreditReservation::query()->create([
                'workspace_id' => $workspaceId,
                'credit_account_id' => $account->getKey(),
                'credit_kind' => $kind->value,
                'initiating_user_id' => $actor->getKey(),
                'logical_operation_id' => $logicalOperationId,
                'idempotency_hash' => $idempotencyHash,
                'request_fingerprint' => $fingerprint,
                'amount' => $amount,
                'state' => 'held',
                'created_at' => $reservedAt,
            ]);

            $account->update([
                'available_credits' => $account->available_credits - $amount,
                'reserved_credits' => $account->reserved_credits + $amount,
            ]);

            CreditLedgerEntry::query()->create([
                'workspace_id' => $workspaceId,
                'credit_account_id' => $account->getKey(),
                'credit_reservation_id' => $reservation->getKey(),
                'initiating_user_id' => $actor->getKey(),
                'entry_type' => 'reserve',
                'idempotency_hash' => $idempotencyHash,
                'request_fingerprint' => $fingerprint,
                'amount' => $amount,
                'available_delta' => -$amount,
                'reserved_delta' => $amount,
                'consumed_delta' => 0,
                'created_at' => $reservedAt,
            ]);

            return $reservation->load('account');
        }, 3);
    }

    private function returnMatchingReservation(CreditReservation $reservation, string $fingerprint): CreditReservation
    {
        if (! hash_equals($reservation->request_fingerprint, $fingerprint)) {
            throw new CreditReservationDenied('idempotency_conflict');
        }

        return $reservation->load('account');
    }

    /** @param array<string, mixed> $context */
    private function fingerprint(int $actorId, string $workspaceId, CreditKind $kind, string $operationId, array $context, int $amount): string
    {
        try {
            return hash('sha256', json_encode([
                'workspace' => $workspaceId,
                'initiator' => $actorId,
                'kind' => $kind->value,
                'operation' => $operationId,
                'context' => $this->sortRecursively($context),
                'amount' => $amount,
            ], JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            throw new CreditReservationDenied('credit_request_invalid');
        }
    }

    /** @param array<mixed> $value
     * @return array<mixed>
     */
    private function sortRecursively(array $value): array
    {
        foreach ($value as &$child) {
            if (is_array($child)) {
                $child = $this->sortRecursively($child);
            }
        }
        unset($child);
        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
