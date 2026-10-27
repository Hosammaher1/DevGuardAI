<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Scan extends Model
{
    use HasUlids;

    protected $table = 'scans';

    protected $fillable = [
        'workspace_id',
        'repository_id',
        'initiated_by_user_id',
        'credit_reservation_id',
        'status',
        'branch',
        'commit_hash',
        'trigger',
        'total_tests',
        'passed_tests',
        'failed_tests',
        'skipped_tests',
        'duration_ms',
        'summary',
        'logs',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_tests' => 'integer',
            'passed_tests' => 'integer',
            'failed_tests' => 'integer',
            'skipped_tests' => 'integer',
            'duration_ms' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function creditReservation(): BelongsTo
    {
        return $this->belongsTo(CreditReservation::class, 'credit_reservation_id');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(TestResult::class, 'scan_id');
    }
}
