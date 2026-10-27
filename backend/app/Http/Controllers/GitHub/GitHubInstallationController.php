<?php

namespace App\Http\Controllers\GitHub;

use App\Domain\GitHub\GitHubAppClient;
use App\Domain\Identity\CurrentMembershipAuthorization;
use App\Domain\Identity\WorkspaceAction;
use App\Http\Controllers\Controller;
use App\Models\GitHubAppInstallation;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GitHubInstallationController extends Controller
{
    public function __construct(
        private readonly CurrentMembershipAuthorization $authorization,
        private readonly GitHubAppClient $githubClient,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $decision = $this->authorization->decide(
            (int) $request->user()->getKey(),
            $workspace->id,
            $workspace->id,
            WorkspaceAction::ViewProjectsAndReports,
        );

        if (! $decision->allowed) {
            return response()->json(['reason' => $decision->reason], 403);
        }

        $installations = GitHubAppInstallation::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->get();

        return response()->json([
            'installations' => $installations,
        ]);
    }

    public function complete(Request $request, Workspace $workspace): JsonResponse
    {
        $decision = $this->authorization->decide(
            (int) $request->user()->getKey(),
            $workspace->id,
            $workspace->id,
            WorkspaceAction::ConnectRepositories,
        );

        if (! $decision->allowed) {
            return response()->json(['reason' => $decision->reason], 403);
        }

        $validated = $request->validate([
            'installation_id' => ['required', 'integer'],
            'account_login' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:64'],
            'repository_selection' => ['nullable', 'string', 'max:32'],
            'permissions' => ['nullable', 'array'],
        ]);

        $installationId = (int) $validated['installation_id'];

        // Verify installation via authenticated GitHub API call
        $verifiedInstallation = $this->githubClient->getInstallation($installationId);
        if ($verifiedInstallation === null) {
            return response()->json([
                'message' => 'Unable to verify installation with GitHub. Ensure the App is properly installed.',
            ], 422);
        }

        $accountLogin = (string) (($verifiedInstallation['account']['login'] ?? null) ?: ($validated['account_login'] ?? 'verified-installation'));
        $accountType = (string) (($verifiedInstallation['account']['type'] ?? null) ?: ($validated['account_type'] ?? 'Organization'));
        $repoSelection = (string) (($verifiedInstallation['repository_selection'] ?? null) ?: ($validated['repository_selection'] ?? 'selected'));
        $permissions = $verifiedInstallation['permissions'] ?? $validated['permissions'] ?? ['contents' => 'read', 'checks' => 'write', 'pull_requests' => 'write'];

        $installation = GitHubAppInstallation::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'installation_id' => $installationId,
            ],
            [
                'account_login' => $accountLogin,
                'account_type' => $accountType,
                'repository_selection' => $repoSelection,
                'permissions' => $permissions,
                'installed_by_user_id' => $request->user()->id,
                'status' => 'active',
                'revoked_at' => null,
            ]
        );

        return response()->json([
            'installation' => $installation,
        ], 201);
    }
}
