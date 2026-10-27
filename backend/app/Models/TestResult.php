<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TestResult extends Model
{
    use HasUlids;

    protected $table = 'test_results';

    protected $fillable = [
        'scan_id',
        'test_name',
        'test_suite',
        'file_path',
        'status',
        'duration_ms',
        'error_message',
        'stack_trace',
        'screenshot_path',
        'screenshot_artifact_id',
        'ai_analysis',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_ms' => 'integer',
            'ai_analysis' => 'array',
        ];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function screenshotArtifact(): BelongsTo
    {
        return $this->belongsTo(Artifact::class, 'screenshot_artifact_id');
    }
}
