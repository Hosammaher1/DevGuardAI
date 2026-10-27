<?php

namespace App\Http\Controllers\GitHub;

use App\Domain\GitHub\RepositoryAccessDenied;
use App\Domain\GitHub\RepositoryBindingService;
use App\Domain\Identity\CurrentMembershipAuthorization;
use App\Domain\Identity\WorkspaceAction;
use App\Http\Controllers\Controller;
use App\Models\GitHubAppInstallation;
use App\Models\Repository;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RepositoryController extends Controller
{
    public function __construct(
        private readonly CurrentMembershipAuthorization $authorization,
        private readonly RepositoryBindingService $bindingService,
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

        $repositories = Repository::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'connected')
            ->get();

        return response()->json([
            'repositories' => $repositories,
        ]);
    }

    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'installation_id' => ['required', 'string'],
            'github_repo_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:512'],
            'owner_login' => ['required', 'string', 'max:255'],
            'is_private' => ['nullable', 'boolean'],
            'default_branch' => ['nullable', 'string', 'max:128'],
            'auto_scan_enabled' => ['nullable', 'boolean'],
        ]);

        $installation = GitHubAppInstallation::query()
            ->where('id', $validated['installation_id'])
            ->first();

        if ($installation === null) {
            return response()->json(['reason' => 'installation_not_found'], 404);
        }

        try {
            $repo = $this->bindingService->connectRepository(
                $request->user(),
                $workspace,
                $installation,
                $validated,
            );
        } catch (RepositoryAccessDenied $e) {
            return response()->json(['reason' => $e->reason], 403);
        }

        return response()->json([
            'repository' => $repo,
        ], 201);
    }

    public function destroy(Request $request, Workspace $workspace, Repository $repository): JsonResponse
    {
        try {
            $this->bindingService->disconnectRepository(
                $request->user(),
                $workspace,
                $repository,
            );
        } catch (RepositoryAccessDenied $e) {
            return response()->json(['reason' => $e->reason], 403);
        }

        return response()->json(['status' => 'disconnected']);
    }
}
