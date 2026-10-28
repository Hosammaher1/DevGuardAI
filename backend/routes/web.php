<?php

use App\Http\Controllers\Artifacts\ArtifactController;
use App\Http\Controllers\Auth\GitHubOAuthController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Fixes\FixPlanController;
use App\Http\Controllers\GitHub\GitHubInstallationController;
use App\Http\Controllers\GitHub\GitHubWebhookController;
use App\Http\Controllers\GitHub\RepositoryController;
use App\Http\Controllers\Identity\MembershipController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Scans\ScanController;
use App\Http\Controllers\Usage\UsageController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'service' => 'devguard-api',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/ready', function () {
    try {
        DB::select('select 1');
    } catch (Throwable) {
        return response()->json(['status' => 'unavailable', 'database' => 'disconnected'], 503);
    }

    return response()->json(['status' => 'ready', 'database' => 'connected']);
})->name('health.ready');

Route::get('/', function () {
    if (file_exists(public_path('index.html'))) {
        return response(file_get_contents(public_path('index.html')))->header('Content-Type', 'text/html');
    }

    return view('welcome');
});

Route::post('/webhooks/github', [GitHubWebhookController::class, 'handle'])
    ->name('webhooks.github');

Route::get('/auth/github/redirect', [GitHubOAuthController::class, 'redirect'])
    ->middleware('guest')
    ->name('auth.github.redirect');
Route::get('/auth/github/callback', [GitHubOAuthController::class, 'callback'])
    ->middleware('guest')
    ->name('auth.github.callback');
Route::post('/logout', [GitHubOAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', function () {
    if (file_exists(public_path('index.html'))) {
        return response(file_get_contents(public_path('index.html')))->header('Content-Type', 'text/html');
    }

    return response('DevGuard AI');
})->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/api/session', [SessionController::class, 'show'])->name('api.session');
    Route::post('/artifacts/{artifact}/access-links', [ArtifactController::class, 'createAccessLink'])->whereUlid('artifact');
    Route::get('/artifacts/{artifact}/content', [ArtifactController::class, 'content'])->whereUlid('artifact');
    Route::delete('/artifacts/{artifact}/access-links/{link}', [ArtifactController::class, 'revokeAccessLink'])->whereUlid('artifact')->whereUlid('link');
    Route::post('/workspaces/{workspace}/invitations', [MembershipController::class, 'storeInvitation']);
    Route::post('/membership-invitations/accept', [MembershipController::class, 'acceptInvitation']);
    Route::patch('/workspaces/{workspace}/members/{user}', [MembershipController::class, 'updateRole'])
        ->whereNumber('user');
    Route::delete('/workspaces/{workspace}/members/{user}', [MembershipController::class, 'destroyMembership'])
        ->whereNumber('user');
    Route::post('/workspaces/{workspace}/ownership-transfers', [MembershipController::class, 'storeOwnershipTransfer']);
    Route::post('/ownership-transfers/{transfer}/accept', [MembershipController::class, 'acceptOwnershipTransfer'])
        ->whereUlid('transfer');
    Route::get('/workspaces/{workspace}/github/installations', [GitHubInstallationController::class, 'index']);
    Route::post('/workspaces/{workspace}/github/installations/complete', [GitHubInstallationController::class, 'complete']);
    Route::get('/workspaces/{workspace}/repositories', [RepositoryController::class, 'index']);
    Route::post('/workspaces/{workspace}/repositories', [RepositoryController::class, 'store']);
    Route::delete('/workspaces/{workspace}/repositories/{repository}', [RepositoryController::class, 'destroy'])
        ->whereUlid('repository');

    // Projects & Targets API
    Route::get('/workspaces/{workspace}/projects', [ProjectController::class, 'index']);
    Route::post('/workspaces/{workspace}/projects', [ProjectController::class, 'store']);
    Route::get('/workspaces/{workspace}/projects/{project}', [ProjectController::class, 'show'])->whereUlid('project');
    Route::post('/workspaces/{workspace}/projects/{project}/targets', [ProjectController::class, 'storeTarget'])->whereUlid('project');
    Route::delete('/workspaces/{workspace}/projects/{project}/targets/{target}', [ProjectController::class, 'destroyTarget'])
        ->whereUlid('project')
        ->whereUlid('target');

    // Scans & Test Results API
    Route::get('/workspaces/{workspace}/scans', [ScanController::class, 'index']);
    Route::post('/workspaces/{workspace}/scans', [ScanController::class, 'store']);
    Route::get('/workspaces/{workspace}/scans/{scan}', [ScanController::class, 'show'])->whereUlid('scan');
    Route::get('/workspaces/{workspace}/scans/{scan}/report', [ScanController::class, 'report'])->whereUlid('scan');
    Route::get('/workspaces/{workspace}/scans/{scan}/export', [ScanController::class, 'export'])->whereUlid('scan');
    Route::get('/workspaces/{workspace}/metrics', [ScanController::class, 'metrics']);
    Route::get('/workspaces/{workspace}/scans/{scan}/tests/{testResult}/screenshot', [ScanController::class, 'screenshot'])
        ->whereUlid('scan')
        ->whereUlid('testResult');

    // AI Fix Plans & Draft PR Workflow
    Route::get('/workspaces/{workspace}/fix-plans', [FixPlanController::class, 'index']);
    Route::post('/workspaces/{workspace}/scans/{scan}/fix-plans', [FixPlanController::class, 'store'])->whereUlid('scan');
    Route::get('/workspaces/{workspace}/fix-plans/{plan}', [FixPlanController::class, 'show'])->whereUlid('plan');
    Route::post('/workspaces/{workspace}/fix-plans/{plan}/approvals', [FixPlanController::class, 'approve'])->whereUlid('plan');
    Route::post('/workspaces/{workspace}/fix-plans/{plan}/verify', [FixPlanController::class, 'verify'])->whereUlid('plan');
    Route::post('/workspaces/{workspace}/fix-plans/{plan}/publish-pr', [FixPlanController::class, 'publishPr'])->whereUlid('plan');

    // Notifications API
    Route::get('/workspaces/{workspace}/notifications', [NotificationController::class, 'index']);
    Route::patch('/workspaces/{workspace}/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->whereUlid('notification');

    // Usage & Health API
    Route::get('/workspaces/{workspace}/usage', [UsageController::class, 'index']);
});
