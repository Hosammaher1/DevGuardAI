<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class GitHubAppInstallation extends Model
{
    use HasUlids;

    protected $table = 'github_app_installations';

    protected $fillable = [
        'workspace_id',
        'installation_id',
        'account_login',
        'account_type',
        'repository_selection',
        'permissions',
        'installed_by_user_id',
        'status',
        'revoked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'installation_id' => 'integer',
            'permissions' => 'array',
            'revoked_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class, 'github_app_installation_id');
    }

    public function installedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by_user_id');
    }
}
