<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Repository extends Model
{
    use HasUlids;

    protected $table = 'repositories';

    protected $fillable = [
        'workspace_id',
        'github_app_installation_id',
        'github_repo_id',
        'name',
        'full_name',
        'owner_login',
        'is_private',
        'default_branch',
        'status',
        'auto_scan_enabled',
        'connected_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'github_repo_id' => 'integer',
            'is_private' => 'boolean',
            'auto_scan_enabled' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(GitHubAppInstallation::class, 'github_app_installation_id');
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by_user_id');
    }
}
