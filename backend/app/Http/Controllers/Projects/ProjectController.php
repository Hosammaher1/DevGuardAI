<?php

namespace App\Http\Controllers\Projects;

use App\Domain\Identity\CurrentMembershipAuthorization;
use App\Domain\Identity\WorkspaceAction;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Repository;
use App\Models\TargetAuthorization;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProjectController extends Controller
{
    public function __construct(
        private readonly CurrentMembershipAuthorization $authorization,
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

        $projects = Project::query()
            ->where('workspace_id', $workspace->id)
            ->with(['repository', 'targetAuthorizations'])
            ->get();

        return response()->json([
            'projects' => $projects,
        ]);
    }

    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $decision = $this->authorization->decide(
            (int) $request->user()->getKey(),
            $workspace->id,
            $workspace->id,
            WorkspaceAction::ManageProjectSettings,
        );

        if (! $decision->allowed) {
            return response()->json(['reason' => $decision->reason], 403);
        }

        $validated = $request->validate([
            'repository_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_branch' => ['nullable', 'string', 'max:128'],
            'framework' => ['nullable', 'string', 'max:64'],
        ]);

        $repository = Repository::query()
            ->where('workspace_id', $workspace->id)
            ->where('id', $validated['repository_id'])
            ->where('status', 'connected')
            ->first();

        if ($repository === null) {
            return response()->json(['error' => 'repository_not_found_or_not_connected'], 404);
        }

        $project = Project::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'repository_id' => $repository->id,
            ],
            [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'default_branch' => $validated['default_branch'] ?? $repository->default_branch,
                'framework' => $validated['framework'] ?? 'react',
                'status' => 'active',
                'created_by_user_id' => $request->user()->id,
            ]
        );

        return response()->json([
            'project' => $project->load(['repository', 'targetAuthorizations']),
        ], 201);
    }

    public function show(Request $request, Workspace $workspace, Project $project): JsonResponse
    {
        if ($project->workspace_id !== $workspace->id) {
            return response()->json(['error' => 'project_outside_workspace'], 404);
        }

        $decision = $this->authorization->decide(
            (int) $request->user()->getKey(),
            $workspace->id,
            $workspace->id,
            WorkspaceAction::ViewProjectsAndReports,
        );

        if (! $decision->allowed) {
            return response()->json(['reason' => $decision->reason], 403);
        }

        return response()->json([
            'project' => $project->load(['repository', 'targetAuthorizations']),
        ]);
    }

    public function storeTarget(Request $request, Workspace $workspace, Project $project): JsonResponse
    {
        if ($project->workspace_id !== $workspace->id) {
            return response()->json(['error' => 'project_outside_workspace'], 404);
        }

        $decision = $this->authorization->decide(
            (int) $request->user()->getKey(),
            $workspace->id,
            $workspace->id,
            WorkspaceAction::ManageProjectSettings,
        );

        if (! $decision->allowed) {
            return response()->json(['reason' => $decision->reason], 403);
        }

        $validated = $request->validate([
            'target_url' => ['required', 'url', 'max:1024'],
            'environment' => ['nullable', 'string', 'in:test,preview,staging,production'],
            'action_scope' => ['nullable', 'string', 'in:read_only,non_destructive,full_test'],
        ]);

        // Guard against dangerous loopback/internal URL abuse if production environment is claimed
        $urlParts = parse_url($validated['target_url']);
        $host = strtolower($urlParts['host'] ?? '');
        $environment = $validated['environment'] ?? 'test';

        if ($environment === 'production' && in_array($host, ['localhost', '127.0.0.1', '::1', '169.254.169.254'], true)) {
            return response()->json(['error' => 'production_target_invalid_origin'], 422);
        }

        $target = TargetAuthorization::query()->create([
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'target_url' => $validated['target_url'],
            'environment' => $environment,
            'action_scope' => $validated['action_scope'] ?? 'non_destructive',
            'status' => 'authorized',
            'verified_at' => now(),
            'expires_at' => now()->addDays(30),
            'authorized_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'target_authorization' => $target,
        ], 201);
    }

    public function destroyTarget(
        Request $request,
        Workspace $workspace,
        Project $project,
        TargetAuthorization $target
    ): JsonResponse {
        if ($project->workspace_id !== $workspace->id || $target->project_id !== $project->id) {
            return response()->json(['error' => 'target_outside_scope'], 404);
        }

        $decision = $this->authorization->decide(
            (int) $request->user()->getKey(),
            $workspace->id,
            $workspace->id,
            WorkspaceAction::ManageProjectSettings,
        );

        if (! $decision->allowed) {
            return response()->json(['reason' => $decision->reason], 403);
        }

        $target->update(['status' => 'revoked']);

        return response()->json(['status' => 'revoked']);
    }
}
