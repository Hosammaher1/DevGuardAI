<?php

namespace App\Domain\Reports;

use App\Models\Scan;
use App\Models\TestResult;
use Carbon\CarbonImmutable;

final class ScanReportService
{
    /**
     * @return array<string, mixed>
     */
    public function generateReport(Scan $scan): array
    {
        $scan->loadMissing(['repository', 'testResults']);

        $results = $scan->testResults;

        $findings = [];
        foreach ($results as $result) {
            $isFailure = $result->status === 'failed';
            $findings[] = [
                'id' => $result->id,
                'name' => $result->test_name,
                'suite' => $result->test_suite ?? 'General',
                'file_path' => $result->file_path,
                'status' => $result->status,
                'duration_ms' => $result->duration_ms,
                'severity' => $isFailure ? ($result->test_suite === 'Security SAST & Secret Scanner' ? 'critical' : 'high') : 'info',
                'error_message' => $result->error_message,
                'has_screenshot' => ! empty($result->screenshot_artifact_id),
                'screenshot_artifact_id' => $result->screenshot_artifact_id,
                'ai_diagnosis' => $result->ai_analysis,
            ];
        }

        // Previous scan comparison for regressions
        $previousScan = Scan::query()
            ->where('workspace_id', $scan->workspace_id)
            ->where('repository_id', $scan->repository_id)
            ->where('id', '!=', $scan->id)
            ->where('created_at', '<', $scan->created_at)
            ->orderByDesc('created_at')
            ->first();

        $comparison = [
            'has_previous' => $previousScan !== null,
            'previous_scan_id' => $previousScan?->id,
            'previous_pass_rate' => ($previousScan && $previousScan->total_tests > 0)
                ? round(($previousScan->passed_tests / $previousScan->total_tests) * 100, 1)
                : null,
            'fixed_count' => 0,
            'regressed_count' => 0,
        ];

        $passRate = $scan->total_tests > 0 ? round(($scan->passed_tests / $scan->total_tests) * 100, 1) : 100.0;

        return [
            'scan_id' => $scan->id,
            'workspace_id' => $scan->workspace_id,
            'repository' => [
                'id' => $scan->repository?->id,
                'full_name' => $scan->repository?->full_name,
                'branch' => $scan->branch,
                'commit_hash' => $scan->commit_hash,
            ],
            'status' => $scan->status,
            'summary' => $scan->summary,
            'started_at' => $scan->started_at?->toIso8601String(),
            'finished_at' => $scan->finished_at?->toIso8601String(),
            'duration_ms' => $scan->duration_ms,
            'metrics' => [
                'total_tests' => $scan->total_tests,
                'passed_tests' => $scan->passed_tests,
                'failed_tests' => $scan->failed_tests,
                'skipped_tests' => $scan->skipped_tests,
                'pass_rate' => $passRate,
            ],
            'comparison' => $comparison,
            'findings' => $findings,
            'exported_at' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    public function generateHtmlExport(Scan $scan): string
    {
        $report = $this->generateReport($scan);
        $repoName = htmlspecialchars($report['repository']['full_name'] ?? 'DevGuard Scan');
        $status = htmlspecialchars(strtoupper($report['status']));
        $passRate = $report['metrics']['pass_rate'];
        $passed = $report['metrics']['passed_tests'];
        $failed = $report['metrics']['failed_tests'];
        $total = $report['metrics']['total_tests'];

        $findingsRows = '';
        foreach ($report['findings'] as $f) {
            $name = htmlspecialchars($f['name']);
            $file = htmlspecialchars($f['file_path']);
            $statusBadge = $f['status'] === 'passed' ? '<span style="color:#10b981">PASSED</span>' : '<span style="color:#ef4444">FAILED</span>';
            $error = $f['error_message'] ? '<pre style="background:#1e293b;color:#f1f5f9;padding:8px;border-radius:4px;overflow-x:auto;">' . htmlspecialchars($f['error_message']) . '</pre>' : '';
            $findingsRows .= "<tr><td style='padding:8px;border-bottom:1px solid #334155;'>{$name}</td><td style='padding:8px;border-bottom:1px solid #334155;'>{$file}</td><td style='padding:8px;border-bottom:1px solid #334155;'>{$statusBadge}</td></tr>";
            if ($error) {
                $findingsRows .= "<tr><td colspan='3' style='padding:4px 8px 12px;border-bottom:1px solid #334155;'>{$error}</td></tr>";
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DevGuard Scan Report - {$repoName}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 24px; }
        .container { max-width: 900px; margin: 0 auto; background: #1e293b; border-radius: 8px; padding: 24px; border: 1px solid #334155; }
        h1 { margin-top: 0; font-size: 24px; color: #38bdf8; }
        .stats { display: flex; gap: 16px; margin: 20px 0; }
        .stat-card { background: #0f172a; padding: 12px 18px; border-radius: 6px; border: 1px solid #334155; flex: 1; }
        .stat-val { font-size: 20px; font-weight: bold; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left; }
        th { padding: 8px; border-bottom: 2px solid #475569; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>DevGuard Security & QA Scan Report</h1>
        <p><strong>Repository:</strong> {$repoName} | <strong>Status:</strong> {$status}</p>
        <div class="stats">
            <div class="stat-card"><div>Total Tests</div><div class="stat-val">{$total}</div></div>
            <div class="stat-card"><div>Passed</div><div class="stat-val" style="color:#10b981;">{$passed}</div></div>
            <div class="stat-card"><div>Failed</div><div class="stat-val" style="color:#ef4444;">{$failed}</div></div>
            <div class="stat-card"><div>Pass Rate</div><div class="stat-val">{$passRate}%</div></div>
        </div>
        <h2>Test Results & Findings</h2>
        <table>
            <thead><tr><th>Test</th><th>File</th><th>Status</th></tr></thead>
            <tbody>{$findingsRows}</tbody>
        </table>
    </div>
</body>
</html>
HTML;
    }
}
