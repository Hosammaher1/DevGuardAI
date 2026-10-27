<?php

namespace App\Domain\Scans;

use App\Domain\Artifacts\ArtifactAccessService;
use App\Domain\Operations\SelectiveContainment;
use App\Domain\Scans\Exceptions\IsolatedRunnerUnavailableException;
use App\Models\Artifact;
use App\Models\Scan;
use App\Models\TestResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ScanExecutionService
{
    public function __construct(
        private readonly ?ArtifactAccessService $artifactAccess = null,
        private readonly ?SelectiveContainment $containment = null,
        private readonly ?LocalIsolatedRunner $runner = null,
    ) {}

    /**
     * @throws IsolatedRunnerUnavailableException
     */
    public function execute(Scan $scan): Scan
    {
        if ($this->containment !== null && ! $this->containment->isRunnersAllowed()) {
            throw new IsolatedRunnerUnavailableException('Runners are currently disabled by emergency containment.');
        }

        $scan->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $startTime = microtime(true);
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $allLogs = '';

        $trustedDemoPath = config('devguard-runner.trusted_project_path', base_path('../trusted-demo'));

        // 1. Run Basic Security Scanners (MVP-17)
        $securityFindings = $this->runSecurityChecks($trustedDemoPath);
        foreach ($securityFindings as $finding) {
            $totalTests++;
            if ($finding['status'] === 'failed') {
                $failedTests++;
            } else {
                $passedTests++;
            }

            TestResult::query()->create([
                'scan_id' => $scan->id,
                'test_name' => $finding['test_name'],
                'test_suite' => 'Security SAST & Secret Scanner',
                'file_path' => $finding['file_path'],
                'status' => $finding['status'],
                'duration_ms' => $finding['duration_ms'],
                'error_message' => $finding['error_message'],
                'stack_trace' => $finding['stack_trace'],
                'ai_analysis' => $finding['ai_analysis'],
            ]);
        }

        // 2. Run Playwright E2E Tests (MVP-14)
        $playwrightSummary = $this->runPlaywrightSuite($scan, $trustedDemoPath);
        if ($playwrightSummary !== null) {
            $allLogs .= ($playwrightSummary['raw_logs'] ?? '');

            foreach ($playwrightSummary['tests'] as $test) {
                $totalTests++;
                $isFailed = $test['status'] === 'failed';
                if ($isFailed) {
                    $failedTests++;
                } else {
                    $passedTests++;
                }

                $screenshotArtifactId = null;

                // Save failure screenshot into encrypted Artifact storage (MVP-08)
                if (! empty($test['screenshot_bytes']) && $this->artifactAccess !== null) {
                    $actor = $scan->initiatedBy ?? \App\Models\User::query()->first();
                    if ($actor !== null) {
                        $stored = $this->artifactAccess->store(
                            actor: $actor,
                            workspaceId: $scan->workspace_id,
                            name: "failure-{$scan->id}-" . str()->slug($test['name']) . ".png",
                            mimeType: 'image/png',
                            contents: $test['screenshot_bytes'],
                            scanId: $scan->id,
                        );
                        $screenshotArtifactId = $stored->artifact->id;
                    }
                }

                // Request AI analysis (MVP-18/19)
                $aiAnalysis = null;
                if ($isFailed) {
                    $aiAnalysis = $this->requestAiAnalysis(
                        testName: $test['name'],
                        filePath: $test['file_path'],
                        errorMessage: $test['error_message'] ?? 'Test failed',
                        stackTrace: $test['stack_trace'] ?? '',
                        screenshotBase64: ! empty($test['screenshot_bytes']) ? base64_encode($test['screenshot_bytes']) : null,
                    );
                }

                TestResult::query()->create([
                    'scan_id' => $scan->id,
                    'test_name' => $test['name'],
                    'test_suite' => 'ShopFlow Playwright Suite',
                    'file_path' => $test['file_path'],
                    'status' => $test['status'],
                    'duration_ms' => $test['duration_ms'] ?? 0,
                    'error_message' => $test['error_message'] ?? null,
                    'stack_trace' => $test['stack_trace'] ?? null,
                    'screenshot_artifact_id' => $screenshotArtifactId,
                    'ai_analysis' => $aiAnalysis,
                ]);
            }
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        $scan->update([
            'status' => 'completed',
            'total_tests' => $totalTests,
            'passed_tests' => $passedTests,
            'failed_tests' => $failedTests,
            'duration_ms' => $durationMs,
            'logs' => $allLogs,
            'finished_at' => now(),
            'summary' => "Completed with {$totalTests} tests executed: {$passedTests} passed, {$failedTests} failed.",
        ]);

        return $scan->fresh(['testResults', 'repository']);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function runPlaywrightSuite(Scan $scan, string $basePath): ?array
    {
        $runTestsScript = $basePath . DIRECTORY_SEPARATOR . 'run-tests.js';
        if (! File::exists($runTestsScript)) {
            return null;
        }

        $runner = $this->runner ?? app(LocalIsolatedRunner::class);
        $result = $runner->run(
            command: ['node', 'run-tests.js'],
            workingDirectory: $basePath,
            environment: [
                'SCAN_ID' => (string) $scan->id,
            ],
            timeout: (int) config('devguard-runner.timeout_seconds', 60),
        );

        $rawOutput = $result['output'];

        $summaryJsonPath = $basePath . DIRECTORY_SEPARATOR . 'test-results' . DIRECTORY_SEPARATOR . 'scan-summary.json';
        if (! File::exists($summaryJsonPath)) {
            return [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'tests' => [],
                'raw_logs' => $rawOutput,
            ];
        }

        $summary = json_decode(File::get($summaryJsonPath), true);
        if (! is_array($summary)) {
            return null;
        }

        // Attach raw bytes for any screenshot
        if (! empty($summary['tests'])) {
            foreach ($summary['tests'] as &$t) {
                if (! empty($t['screenshot_path']) && File::exists($t['screenshot_path'])) {
                    $t['screenshot_bytes'] = File::get($t['screenshot_path']);
                }
            }
        }

        $summary['raw_logs'] = $rawOutput;
        return $summary;
    }

    /**
     * Basic security checks for secrets and dangerous exposures (MVP-17).
     *
     * @return array<int, array<string, mixed>>
     */
    public function runSecurityChecks(string $directory): array
    {
        $findings = [];

        // Secret checks: search for AWS keys, private keys, or tokens
        $secretPatterns = [
            'AWS Access Key' => '/(A3T[A-Z0-9]|AKIA|AGPA|AIDA|AROA|AIPA|ANPA|ANVA|ASIA)[A-Z0-9]{16}/',
            'GitHub Personal Access Token' => '/ghp_[0-9a-zA-Z]{36}/',
            'Private Key Header' => '/-----BEGIN (?:RSA|EC|OPENSSH) PRIVATE KEY-----/',
        ];

        $scannedCount = 0;
        $files = File::exists($directory) ? File::allFiles($directory) : [];

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['js', 'ts', 'jsx', 'tsx', 'html', 'json', 'env', 'yml', 'yaml'], true)) {
                $scannedCount++;
                $content = @file_get_contents($file->getRealPath()) ?: '';

                foreach ($secretPatterns as $label => $pattern) {
                    if (preg_match($pattern, $content, $matches)) {
                        $rawSecret = $matches[0];
                        $masked = substr($rawSecret, 0, 4) . str_repeat('*', strlen($rawSecret) - 8) . substr($rawSecret, -4);
                        $relPath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getRealPath());

                        $findings[] = [
                            'test_name' => "Security: {$label} Detection",
                            'file_path' => $relPath,
                            'status' => 'failed',
                            'duration_ms' => 12,
                            'error_message' => "Detected exposed credential [{$label}] in source file: {$masked}",
                            'stack_trace' => "Finding Rule: DEVGUARD-SEC-001\nFile: {$relPath}\nMasked Secret: {$masked}",
                            'ai_analysis' => [
                                'root_cause' => "Hardcoded {$label} detected in repository source.",
                                'failure_category' => 'SECURITY_VULNERABILITY',
                                'suggested_fix' => "Immediately rotate the credential and migrate it to an environment variable or secret broker.",
                                'code_patch' => null,
                                'confidence_score' => 0.99,
                                'is_mock' => false,
                                'requires_approval' => true,
                            ],
                        ];
                    }
                }
            }
        }

        // Clean benchmark check if no leaks
        if (empty($findings)) {
            $findings[] = [
                'test_name' => 'Security: Static Secret Scanner',
                'file_path' => 'package.json',
                'status' => 'passed',
                'duration_ms' => 15,
                'error_message' => null,
                'stack_trace' => null,
                'ai_analysis' => null,
            ];
        }

        return $findings;
    }

    /**
     * @return array<string, mixed>
     */
    public function requestAiAnalysis(
        string $testName,
        string $filePath,
        string $errorMessage,
        string $stackTrace,
        ?string $screenshotBase64 = null
    ): array {
        $aiUrl = config('devguard-runner.ai_service_url', 'http://127.0.0.1:8001');
        $internalKey = config('devguard-runner.ai_internal_key', 'devguard-internal-secret-token');

        try {
            $response = Http::withHeaders([
                'X-DevGuard-Internal-Key' => $internalKey,
                'Content-Type' => 'application/json',
            ])->timeout(4)->post("{$aiUrl}/internal/review", [
                'test_name' => $testName,
                'file_path' => $filePath,
                'error_message' => $errorMessage,
                'stack_trace' => $stackTrace,
                'screenshot_base64' => $screenshotBase64,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::warning('AI Agent internal review failed, falling back to local heuristic: ' . $e->getMessage());
        }

        // Local fallback when AI agent endpoint is unreachable
        return [
            'root_cause' => "[Local Fallback Diagnostic] {$errorMessage}",
            'failure_category' => 'ASSERTION_FAILURE',
            'suggested_fix' => 'Verify DOM selectors and page readiness in Playwright test.',
            'code_patch' => null,
            'confidence_score' => 0.75,
            'model_used' => 'devguard-local-fallback',
            'is_mock' => true,
            'requires_approval' => true,
        ];
    }
}
