<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CreditReservation extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'credit_account_id',
        'credit_kind',
        'initiating_user_id',
        'logical_operation_id',
        'idempotency_hash',
        'request_fingerprint',
        'amount',
        'state',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CreditAccount::class, 'credit_account_id');
    }
}
