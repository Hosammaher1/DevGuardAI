<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class CreditLedgerEntry extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'credit_account_id',
        'credit_reservation_id',
        'initiating_user_id',
        'entry_type',
        'idempotency_hash',
        'request_fingerprint',
        'amount',
        'available_delta',
        'reserved_delta',
        'consumed_delta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'available_delta' => 'integer',
            'reserved_delta' => 'integer',
            'consumed_delta' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Credit ledger entries are append-only.');
        });
        self::deleting(static function (): never {
            throw new LogicException('Credit ledger entries are append-only.');
        });
    }
}
