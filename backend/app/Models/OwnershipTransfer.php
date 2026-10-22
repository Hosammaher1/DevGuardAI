<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class OwnershipTransfer extends Model
{
    use HasUlids;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
