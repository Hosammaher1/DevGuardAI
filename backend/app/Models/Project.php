<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Project extends Model
{
    use HasUlids;

    protected $fillable = [
        'workspace_id',
        'repository_id',
        'name',
        'description',
        'default_branch',
        'framework',
        'status',
        'created_by_user_id',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function targetAuthorizations(): HasMany
    {
        return $this->hasMany(TargetAuthorization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
