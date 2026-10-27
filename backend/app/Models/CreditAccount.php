<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class CreditAccount extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'credit_kind',
        'available_credits',
        'reserved_credits',
        'consumed_credits',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'available_credits' => 'integer',
            'reserved_credits' => 'integer',
            'consumed_credits' => 'integer',
        ];
    }
}
