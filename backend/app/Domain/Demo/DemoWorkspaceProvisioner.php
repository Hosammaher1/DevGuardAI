<?php

namespace App\Domain\Demo;

use App\Domain\Identity\Role;
use App\Models\CreditAccount;
use App\Models\FixJob;
use App\Models\FixPlan;
use App\Models\GitHubAppInstallation;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Repository;
use App\Models\Scan;
use App\Models\TargetAuthorization;
use App\Models\TestResult;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DemoWorkspaceProvisioner
{
    /**
     * Provision or retrieve the isolated shared demo workspace and user.
     *
     * @return array{user: User, workspace: Workspace}
     */
    public function provision(): array
    {
        return DB::transaction(function (): array {
            $user = $this->ensureDemoUser();
            $workspace = $this->ensureDemoWorkspace();
            $this->ensureDemoMembership($workspace, $user);
            $installation = $this->ensureDemoInstallation($workspace, $user);
            $repository = $this->ensureDemoRepository($workspace, $installation, $user);
            $project = $this->ensureDemoProject($workspace, $repository, $user);
            $this->ensureDemoTargetAuthorization($workspace, $project, $user);
            $this->ensureDemoCredits($workspace);
            $scan = $this->ensureDemoScan($workspace, $repository, $user);
            $this->ensureDemoFixPlan($workspace, $scan, $repository, $user);
            $this->ensureDemoNotifications($workspace, $user, $scan);

            return [
                'user' => $user,
                'workspace' => $workspace,
            ];
        });
    }

    private function ensureDemoUser(): User
    {
        $githubId = (string) config('devguard-demo.user_github_id', 'demo-visitor-shared');
        $user = User::query()->where('github_user_id', $githubId)->first();

        if ($user === null) {
            $user = User::query()->create([
                'github_user_id' => $githubId,
                'name' => (string) config('devguard-demo.user_name', 'Demo Visitor'),
                'email' => (string) config('devguard-demo.user_email', 'demo@devguard.ai'),
            ]);
        }

        return $user;
    }

    private function ensureDemoWorkspace(): Workspace
    {
        $name = (string) config('devguard-demo.workspace_name', 'DevGuard AI Demo Workspace');
        $workspace = Workspace::query()->where('name', $name)->first();

        if ($workspace === null) {
            $workspace = Workspace::query()->create([
                'name' => $name,
                'personal_owner_user_id' => null,
            ]);
        }

        return $workspace;
    }

    private function ensureDemoMembership(Workspace $workspace, User $user): Membership
    {
        $membership = Membership::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            $membership = Membership::query()->create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => Role::Admin,
                'active' => true,
                'version' => 1,
            ]);
        }

        return $membership;
    }

    private function ensureDemoInstallation(Workspace $workspace, User $user): GitHubAppInstallation
    {
        $installation = GitHubAppInstallation::query()
            ->where('workspace_id', $workspace->id)
            ->where('installation_id', 99990001)
            ->first();

        if ($installation === null) {
            $installation = GitHubAppInstallation::query()->create([
                'workspace_id' => $workspace->id,
                'installation_id' => 99990001,
                'account_login' => 'DevGuardAI',
                'account_type' => 'Organization',
                'repository_selection' => 'selected',
                'permissions' => ['contents' => 'read', 'pull_requests' => 'write', 'checks' => 'write'],
                'installed_by_user_id' => $user->id,
                'status' => 'active',
            ]);
        }

        return $installation;
    }

    private function ensureDemoRepository(
        Workspace $workspace,
        GitHubAppInstallation $installation,
        User $user
    ): Repository {
        $repository = Repository::query()
            ->where('workspace_id', $workspace->id)
            ->where('name', 'trusted-demo')
            ->first();

        if ($repository === null) {
            $repository = Repository::query()->create([
                'workspace_id' => $workspace->id,
                'github_app_installation_id' => $installation->id,
                'github_repo_id' => 88880001,
                'name' => 'trusted-demo',
                'full_name' => 'DevGuardAI/trusted-demo',
                'owner_login' => 'DevGuardAI',
                'is_private' => false,
                'default_branch' => 'main',
                'status' => 'connected',
                'auto_scan_enabled' => true,
                'connected_by_user_id' => $user->id,
            ]);
        }

        return $repository;
    }

    private function ensureDemoProject(Workspace $workspace, Repository $repository, User $user): Project
    {
        $project = Project::query()
            ->where('workspace_id', $workspace->id)
            ->where('repository_id', $repository->id)
            ->first();

        if ($project === null) {
            $project = Project::query()->create([
                'workspace_id' => $workspace->id,
                'repository_id' => $repository->id,
                'name' => 'Storefront E2E Suite',
                'description' => 'Autonomous regression & security monitoring for storefront checkout, auth, and API contracts.',
                'default_branch' => 'main',
                'framework' => 'react',
                'status' => 'active',
                'created_by_user_id' => $user->id,
            ]);
        }

        return $project;
    }

    private function ensureDemoTargetAuthorization(
        Workspace $workspace,
        Project $project,
        User $user
    ): TargetAuthorization {
        $target = TargetAuthorization::query()
            ->where('workspace_id', $workspace->id)
            ->where('project_id', $project->id)
            ->first();

        if ($target === null) {
            $target = TargetAuthorization::query()->create([
                'workspace_id' => $workspace->id,
                'project_id' => $project->id,
                'target_url' => 'https://demo.devguard.ai',
                'environment' => 'staging',
                'action_scope' => 'non_destructive',
                'status' => 'authorized',
                'verification_token' => 'dg_demo_auth_token_verified',
                'verified_at' => CarbonImmutable::now(),
                'authorized_by_user_id' => $user->id,
            ]);
        }

        return $target;
    }

    private function ensureDemoCredits(Workspace $workspace): void
    {
        $kinds = [
            'scan' => ['available' => 50, 'consumed' => 1],
            'fix' => ['available' => 20, 'consumed' => 0],
        ];

        foreach ($kinds as $kind => $values) {
            $account = CreditAccount::query()
                ->where('workspace_id', $workspace->id)
                ->where('credit_kind', $kind)
                ->first();

            if ($account === null) {
                CreditAccount::query()->create([
                    'workspace_id' => $workspace->id,
                    'credit_kind' => $kind,
                    'available_credits' => $values['available'],
                    'reserved_credits' => 0,
                    'consumed_credits' => $values['consumed'],
                    'created_at' => CarbonImmutable::now(),
                    'updated_at' => CarbonImmutable::now(),
                ]);
            }
        }
    }

    private function ensureDemoScan(Workspace $workspace, Repository $repository, User $user): Scan
    {
        $scan = Scan::query()
            ->where('workspace_id', $workspace->id)
            ->where('repository_id', $repository->id)
            ->first();

        if ($scan !== null) {
            return $scan;
        }

        $now = CarbonImmutable::now();

        $scan = Scan::query()->create([
            'workspace_id' => $workspace->id,
            'repository_id' => $repository->id,
            'initiated_by_user_id' => $user->id,
            'status' => 'completed',
            'branch' => 'main',
            'commit_hash' => 'c8f4b12',
            'trigger' => 'scheduled',
            'total_tests' => 6,
            'passed_tests' => 4,
            'failed_tests' => 2,
            'skipped_tests' => 0,
            'duration_ms' => 3420,
            'summary' => 'Autonomous regression scan detected 2 failures in checkout flow and payment gateway contract.',
            'logs' => implode("\n", [
                '[INFO] DevGuard AI Test Runner initialized in container sandbox',
                '[INFO] Executing Playwright E2E suite against https://demo.devguard.ai',
                '[PASS] tests/auth.spec.js > User authentication with valid JWT (450ms)',
                '[PASS] tests/security.spec.js > Content Security Policy and X-Frame header audit (220ms)',
                '[FAIL] tests/checkout.spec.js > Complete guest checkout flow (1240ms)',
                '       TimeoutError: locator.click: Timeout 5000ms exceeded waiting for locator(\'#checkout-button\')',
                '[PASS] tests/cart.spec.js > Cart state persistence on page reload (680ms)',
                '[FAIL] tests/api-contract.spec.js > Payment mock endpoint returns required Idempotency-Key header (310ms)',
                '       AssertionError: expected headers to contain "Idempotency-Key"',
                '[PASS] tests/navigation.spec.js > Deep link navigation to catalog items (520ms)',
                '[INFO] Scan finished in 3420ms: 4 passed, 2 failed',
            ]),
            'started_at' => $now->subMinutes(20),
            'finished_at' => $now->subMinutes(19),
        ]);

        $this->createDemoTestResults($scan);

        return $scan;
    }

    private function createDemoTestResults(Scan $scan): void
    {
        TestResult::query()->create([
            'scan_id' => $scan->id,
            'test_name' => 'Complete guest checkout flow',
            'test_suite' => 'Checkout Flow',
            'file_path' => 'tests/checkout.spec.js',
            'status' => 'failed',
            'duration_ms' => 1240,
            'error_message' => "TimeoutError: locator.click: Timeout 5000ms exceeded waiting for locator('#checkout-button')",
            'stack_trace' => "TimeoutError: locator.click: Timeout 5000ms exceeded\n    at CheckoutPage.submitOrder (tests/checkout.spec.js:42:35)\n    at Object.test (tests/checkout.spec.js:18:9)",
            'ai_analysis' => [
                'root_cause' => 'Client-side async bundle hydration delays DOM mounting of #checkout-button during fast automated navigation.',
                'confidence' => 0.94,
                'code_patch' => "// Fix async race condition on checkout button locator\n- await page.locator('#checkout-button').click();\n+ await page.locator('#checkout-button').waitFor({ state: 'visible', timeout: 8000 });\n+ await page.locator('#checkout-button').click();",
                'severity' => 'medium',
            ],
        ]);

        TestResult::query()->create([
            'scan_id' => $scan->id,
            'test_name' => 'Payment mock endpoint returns required Idempotency-Key header',
            'test_suite' => 'API Contracts',
            'file_path' => 'tests/api-contract.spec.js',
            'status' => 'failed',
            'duration_ms' => 310,
            'error_message' => 'AssertionError: expected headers to contain "Idempotency-Key", but received [content-type, cache-control]',
            'stack_trace' => "AssertionError: missing Idempotency-Key header\n    at PaymentGatewayClient.verifyHeaders (tests/api-contract.spec.js:29:14)\n    at Object.test (tests/api-contract.spec.js:12:7)",
            'ai_analysis' => [
                'root_cause' => 'Mock provider handler does not reflect the required RFC idempotency header on checkout dispatch.',
                'confidence' => 0.98,
                'code_patch' => "// Include Idempotency-Key header in mock response contract\n- headers: { 'Content-Type': 'application/json' }\n+ headers: { 'Content-Type': 'application/json', 'Idempotency-Key': crypto.randomUUID() }",
                'severity' => 'low',
            ],
        ]);

        TestResult::query()->create([
            'scan_id' => $scan->id,
            'test_name' => 'User authentication with valid JWT',
            'test_suite' => 'Authentication',
            'file_path' => 'tests/auth.spec.js',
            'status' => 'passed',
            'duration_ms' => 450,
        ]);

        TestResult::query()->create([
            'scan_id' => $scan->id,
            'test_name' => 'Content Security Policy and X-Frame header audit',
            'test_suite' => 'Security Auditing',
            'file_path' => 'tests/security.spec.js',
            'status' => 'passed',
            'duration_ms' => 220,
        ]);

        TestResult::query()->create([
            'scan_id' => $scan->id,
            'test_name' => 'Cart state persistence on page reload',
            'test_suite' => 'Cart',
            'file_path' => 'tests/cart.spec.js',
            'status' => 'passed',
            'duration_ms' => 680,
        ]);

        TestResult::query()->create([
            'scan_id' => $scan->id,
            'test_name' => 'Deep link navigation to catalog items',
            'test_suite' => 'Navigation',
            'file_path' => 'tests/navigation.spec.js',
            'status' => 'passed',
            'duration_ms' => 520,
        ]);
    }

    private function ensureDemoFixPlan(
        Workspace $workspace,
        Scan $scan,
        Repository $repository,
        User $user
    ): FixPlan {
        $plan = FixPlan::query()
            ->where('workspace_id', $workspace->id)
            ->where('scan_id', $scan->id)
            ->first();

        if ($plan !== null) {
            return $plan;
        }

        $failedTests = $scan->testResults()->where('status', 'failed')->get();

        $plan = FixPlan::query()->create([
            'workspace_id' => $workspace->id,
            'scan_id' => $scan->id,
            'repository_id' => $repository->id,
            'base_commit_hash' => $scan->commit_hash ?? 'c8f4b12',
            'plan_digest' => hash('sha256', 'demo-plan-' . $scan->id),
            'title' => 'Fix 2 detected issues from Scan #' . substr($scan->id, -6),
            'summary' => 'Automated patch addressing checkout button race condition and payment gateway idempotency header contract.',
            'risk_level' => 'low',
            'fix_credits_required' => 1,
            'status' => 'proposed',
            'proposed_by_user_id' => $user->id,
        ]);

        foreach ($failedTests as $test) {
            $ai = $test->ai_analysis ?? [];
            FixJob::query()->create([
                'fix_plan_id' => $plan->id,
                'test_result_id' => $test->id,
                'target_file' => $test->file_path,
                'root_cause' => $ai['root_cause'] ?? $test->error_message,
                'suggested_patch' => $ai['code_patch'] ?? '// Automated patch placeholder',
                'status' => 'pending',
            ]);
        }

        return $plan;
    }

    private function ensureDemoNotifications(Workspace $workspace, User $user, Scan $scan): void
    {
        $existing = Notification::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existing) {
            return;
        }

        Notification::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'type' => 'scan_completed',
            'title' => 'Scan #' . substr($scan->id, -6) . ' Completed',
            'message' => 'Autonomous regression scan completed with 4 passed and 2 failed tests.',
            'link_url' => '/workspaces/' . $workspace->id . '/scans/' . $scan->id,
            'read_at' => null,
        ]);

        Notification::query()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'type' => 'fix_plan_ready',
            'title' => 'AI Fix Plan Available',
            'message' => 'Autonomous patch proposed for checkout race condition.',
            'link_url' => '/workspaces/' . $workspace->id . '/fix-plans',
            'read_at' => null,
        ]);
    }
}
