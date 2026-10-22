<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class AuditEvent extends Model
{
    use HasUlids;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['allowed' => 'boolean'];
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Audit events are append-only.');
        });
        self::deleting(static function (): never {
            throw new LogicException('Audit events are append-only.');
        });
    }
}
