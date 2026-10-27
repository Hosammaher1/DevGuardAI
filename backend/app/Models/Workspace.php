<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Workspace extends Model
{
    use HasUlids;

    protected $fillable = [
        'personal_owner_user_id',
        'name',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function githubInstallations(): HasMany
    {
        return $this->hasMany(GitHubAppInstallation::class);
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(Repository::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }
}
