<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FixPlan extends Model
{
    use HasUlids;

    protected $fillable = [
        'workspace_id',
        'scan_id',
        'repository_id',
        'base_commit_hash',
        'plan_digest',
        'title',
        'summary',
        'risk_level',
        'fix_credits_required',
        'status',
        'proposed_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'expires_at',
        'verified_at',
        'pr_number',
        'pr_url',
    ];

    protected $casts = [
        'approved_at' => 'immutable_datetime',
        'expires_at' => 'immutable_datetime',
        'verified_at' => 'immutable_datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function fixJobs(): HasMany
    {
        return $this->hasMany(FixJob::class);
    }
}
