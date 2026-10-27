<?php

namespace App\Http\Controllers\Scans;

use App\Domain\Artifacts\ArtifactAccessService;
use App\Domain\Identity\AuthorizationService;
use App\Domain\Identity\WorkspaceAction;
use App\Domain\Reports\ScanReportService;
use App\Domain\Scans\ScanExecutionService;
use App\Http\Controllers\Controller;
use App\Models\Artifact;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\Repository;
use App\Models\Scan;
use App\Models\TestResult;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ScanController extends Controller
{
    public function __construct(
        private AuthorizationService $authorization,
        private ScanExecutionService $scanExecution,
        private ArtifactAccessService $artifactAccess,
        private ScanReportService $reportService,
    ) {}

    public function report(Request $request, string $workspace, string $scan): JsonResponse
    {
        $workspaceModel = Workspace::query()->whereKey($workspace)->firstOrFail();
        $this->authorizeAction($request, $workspaceModel, WorkspaceAction::ViewScanResults);

        $scanModel = Scan::query()
            ->where('workspace_id', $workspaceModel->id)
            ->where('id', $scan)
            ->firstOrFail();

        $report = $this->reportService->generateReport($scanModel);

        return response()->json($report);
    }

    public function export(Request $request, string $workspace, string $scan): Response|JsonResponse
    {
        $workspaceModel = Workspace::query()->whereKey($workspace)->firstOrFail();
        $this->authorizeAction($request, $workspaceModel, WorkspaceAction::ViewScanResults);

        $scanModel = Scan::query()
            ->where('workspace_id', $workspaceModel->id)
            ->where('id', $scan)
            ->firstOrFail();

        $format = $request->query('format', 'json');
        if ($format === 'html') {
            $html = $this->reportService->generateHtmlExport($scanModel);
            return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return response()->json($this->reportService->generateReport($scanModel));
    }

    public function index(Request $request, string $workspace): JsonResponse
    {
        $workspaceModel = Workspace::query()->whereKey($workspace)->firstOrFail();
        $this->authorizeAction($request, $workspaceModel, WorkspaceAction::ViewScanResults);

        $scans = Scan::query()
            ->where('workspace_id', $workspaceModel->id)
            ->with(['repository'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'scans' => $scans,
        ]);
    }

    public function show(Request $request, string $workspace, string $scan): JsonResponse
    {
        $workspaceModel = Workspace::query()->whereKey($workspace)->firstOrFail();
        $this->authorizeAction($request, $workspaceModel, WorkspaceAction::ViewScanResults);

        $scanModel = Scan::query()
            ->where('workspace_id', $workspaceModel->id)
            ->where('id', $scan)
            ->with(['repository', 'testResults'])
            ->firstOrFail();

        return response()->json([
            'scan' => $scanModel,
        ]);
    }

    public function store(Request $request, string $workspace): JsonResponse
    {
        $workspaceModel = Workspace::query()->whereKey($workspace)->firstOrFail();
        $this->authorizeAction($request, $workspaceModel, WorkspaceAction::StartScans);

        $repositoryId = $request->input('repository_id');
        if (! $repositoryId) {
            return response()->json([
                'error' => 'repository_required',
                'message' => 'An explicit repository_id belonging to the workspace is required.',
            ], 422);
        }

        $repository = Repository::query()
            ->where('workspace_id', $workspaceModel->id)
            ->where('id', $repositoryId)
            ->first();

        if ($repository === null) {
            return response()->json([
                'error' => 'repository_not_found',
                'message' => 'The specified repository does not exist in this workspace.',
            ], 404);
        }

        if ($repository->status !== 'active' && $repository->status !== 'connected') {
            return response()->json([
                'error' => 'repository_not_connected',
                'message' => 'The specified repository is not actively connected.',
            ], 422);
        }

        $installation = GitHubAppInstallation::query()
            ->where('workspace_id', $workspaceModel->id)
            ->where('id', $repository->github_app_installation_id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->first();

        if ($installation === null) {
            return response()->json([
                'error' => 'github_installation_inactive',
                'message' => 'The GitHub App installation for this repository is missing, inactive, or revoked.',
            ], 422);
        }

        // Fail-closed admission: isolated runner infrastructure is unavailable unless enabled
        if (! config('devguard-runner.enabled', false)) {
            return response()->json([
                'error' => 'isolated_runner_unavailable',
                'reason' => 'isolated_runner_unavailable',
                'message' => 'Isolated runner infrastructure is not available in local environment without verified provider infrastructure.',
            ], 503);
        }

        $containment = app(\App\Domain\Operations\SelectiveContainment::class);
        if (! $containment->isScansAllowed() || ! $containment->isRunnersAllowed()) {
            return response()->json([
                'error' => 'isolated_runner_unavailable',
                'reason' => 'isolated_runner_unavailable',
                'message' => 'Scans or runners are currently paused under emergency containment.',
            ], 503);
        }

        $account = \App\Models\CreditAccount::query()
            ->where('workspace_id', $workspaceModel->id)
            ->where('credit_kind', 'scan')
            ->first();

        if ($account !== null && $account->available_credits < 1) {
            return response()->json([
                'error' => 'insufficient_credits',
                'message' => 'Insufficient scan credits available in workspace.',
            ], 422);
        }

        if ($account !== null) {
            $account->decrement('available_credits', 1);
            $account->increment('consumed_credits', 1);
        }

        $scan = Scan::query()->create([
            'workspace_id' => $workspaceModel->id,
            'repository_id' => $repository->id,
            'initiated_by_user_id' => $request->user()->id,
            'branch' => $repository->default_branch ?? 'main',
            'trigger' => $request->input('trigger', 'manual'),
            'status' => 'pending',
        ]);

        try {
            $executed = $this->scanExecution->execute($scan);
        } catch (\App\Domain\Scans\Exceptions\IsolatedRunnerUnavailableException $e) {
            return response()->json([
                'error' => 'isolated_runner_unavailable',
                'reason' => 'isolated_runner_unavailable',
                'message' => $e->getMessage(),
            ], 503);
        }

        return response()->json([
            'scan' => $executed,
        ], 201);
    }

    public function metrics(Request $request, string $workspace): JsonResponse
    {
        $workspaceModel = Workspace::query()->whereKey($workspace)->firstOrFail();
        $this->authorizeAction($request, $workspaceModel, WorkspaceAction::ViewProjectsAndReports);

        $scansCount = Scan::query()->where('workspace_id', $workspaceModel->id)->count();
        $totalTests = (int) Scan::query()->where('workspace_id', $workspaceModel->id)->sum('total_tests');
        $totalPassed = (int) Scan::query()->where('workspace_id', $workspaceModel->id)->sum('passed_tests');
        $totalFailed = (int) Scan::query()->where('workspace_id', $workspaceModel->id)->sum('failed_tests');

        $passRate = $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 1) : 100.0;

        $latestScan = Scan::query()
            ->where('workspace_id', $workspaceModel->id)
            ->with(['repository', 'testResults'])
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'metrics' => [
                'total_scans' => $scansCount,
                'total_tests' => $totalTests,
                'total_passed' => $totalPassed,
                'total_failed' => $totalFailed,
                'pass_rate' => $passRate,
                'active_repositories' => Repository::query()->where('workspace_id', $workspaceModel->id)->count(),
            ],
            'latest_scan' => $latestScan,
        ]);
    }

    public function screenshot(Request $request, string $workspace, string $scan, string $testResult): Response|JsonResponse
    {
        $workspaceModel = Workspace::query()->whereKey($workspace)->firstOrFail();
        $this->authorizeAction($request, $workspaceModel, WorkspaceAction::ViewScanResults);

        $scanModel = Scan::query()
            ->where('workspace_id', $workspaceModel->id)
            ->where('id', $scan)
            ->firstOrFail();

        $result = TestResult::query()
            ->where('id', $testResult)
            ->where('scan_id', $scanModel->id)
            ->firstOrFail();

        if (empty($result->screenshot_artifact_id)) {
            abort(404, 'Screenshot artifact not found.');
        }

        $artifact = Artifact::query()
            ->whereKey($result->screenshot_artifact_id)
            ->where('workspace_id', $workspaceModel->id)
            ->first();

        if ($artifact === null) {
            abort(404, 'Artifact not found.');
        }

        // If scan_id column exists and is populated, ensure it matches the scan
        if (! empty($artifact->scan_id) && $artifact->scan_id !== $scanModel->id) {
            abort(404, 'Artifact does not belong to this scan.');
        }

        $authHeader = (string) $request->header('Authorization', '');
        if (! str_starts_with($authHeader, 'Artifact ')) {
            abort(401, 'Missing or invalid Artifact token header.');
        }

        $rawToken = trim(substr($authHeader, 9));
        if ($rawToken === '') {
            abort(401, 'Missing Artifact token.');
        }

        $user = $request->user();
        if ($user === null) {
            abort(401, 'Unauthenticated');
        }

        $now = CarbonImmutable::now();
        $storedArtifact = $this->artifactAccess->read($user, $artifact->getKey(), $rawToken, $now);

        if ($storedArtifact === null) {
            abort(404, 'Artifact access denied or expired.');
        }

        $contents = $storedArtifact->contents;

        // Verify PNG signature (first 8 bytes: \x89PNG\r\n\x1a\n)
        if (! str_starts_with($contents, "\x89PNG\r\n\x1a\n")) {
            abort(404, 'Corrupt or non-PNG payload.');
        }

        return response($contents, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache, private, no-store, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeAction(Request $request, Workspace $workspace, WorkspaceAction $action): void
    {
        $user = $request->user();
        if ($user === null) {
            abort(401, 'Unauthenticated');
        }

        $membership = Membership::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null || ! $membership->active) {
            abort(403, 'Forbidden');
        }

        $decision = $this->authorization->decide($membership->role, $action, $membership->active, true);
        if (! $decision->allowed) {
            abort(403, 'Action denied by RBAC matrix');
        }
    }
}
