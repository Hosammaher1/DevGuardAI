<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ProviderInbox extends Model
{
    use HasUlids;

    protected $table = 'provider_inbox';

    protected $fillable = [
        'provider',
        'delivery_id',
        'event_type',
        'payload_hash',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'immutable_datetime',
    ];
}
