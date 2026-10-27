<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FixJob extends Model
{
    use HasUlids;

    protected $fillable = [
        'fix_plan_id',
        'test_result_id',
        'target_file',
        'root_cause',
        'suggested_patch',
        'status',
        'verification_output',
    ];

    public function fixPlan(): BelongsTo
    {
        return $this->belongsTo(FixPlan::class);
    }

    public function testResult(): BelongsTo
    {
        return $this->belongsTo(TestResult::class);
    }
}
