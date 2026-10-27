<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TargetAuthorization extends Model
{
    use HasUlids;

    protected $fillable = [
        'workspace_id',
        'project_id',
        'target_url',
        'environment',
        'action_scope',
        'status',
        'verification_token',
        'verified_at',
        'expires_at',
        'authorized_by_user_id',
    ];

    protected $casts = [
        'verified_at' => 'immutable_datetime',
        'expires_at' => 'immutable_datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }
}
