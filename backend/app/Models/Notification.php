<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Notification extends Model
{
    use HasUlids;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'type',
        'title',
        'message',
        'link_url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'immutable_datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
