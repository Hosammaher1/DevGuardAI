<?php

namespace App\Models;

use App\Domain\Identity\Role;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Membership extends Model
{
    use HasUlids;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
        'active',
        'version',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'active' => 'boolean',
            'version' => 'integer',
        ];
    }

    /** @return Attribute<bool, bool> */
    protected function active(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): bool => (bool) $value,
            set: fn (mixed $value): bool => (bool) $value,
        );
    }
}
