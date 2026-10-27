<?php

namespace App\Http\Controllers\Fixes;

use App\Domain\Fixes\FixPlanService;
use App\Domain\Identity\CurrentMembershipAuthorization;
use App\Domain\Identity\WorkspaceAction;
use App\Http\Controllers\Controller;
use App\Models\FixPlan;
use App\Models\Scan;
use App\Models\Workspace;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class FixPlanController extends Controller
{
    public function __construct(
        private readonly CurrentMembershipAuthorization $authorization,
        private readonly FixPlanService $fixPlanService,
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

        $plans = FixPlan::query()
            ->where('workspace_id', $workspace->id)
            ->with(['repository', 'fixJobs', 'proposedBy', 'approvedBy'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'fix_plans' => $plans,
        ]);
    }

    public function store(Request $request, Workspace $workspace, Scan $scan): JsonResponse
    {
        try {
            $plan = $this->fixPlanService->proposePlan($request->user(), $workspace, $scan);
            return response()->json(['fix_plan' => $plan], 201);
        } catch (AuthorizationException $e) {
            return response()->json(['reason' => $e->getMessage()], 403);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function show(Request $request, Workspace $workspace, FixPlan $plan): JsonResponse
    {
        if ($plan->workspace_id !== $workspace->id) {
            return response()->json(['error' => 'not_found'], 404);
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
            'fix_plan' => $plan->load(['repository', 'fixJobs', 'proposedBy', 'approvedBy', 'scan']),
        ]);
    }

    public function approve(Request $request, Workspace $workspace, FixPlan $plan): JsonResponse
    {
        try {
            $approved = $this->fixPlanService->approvePlan($request->user(), $workspace, $plan);
            return response()->json(['fix_plan' => $approved]);
        } catch (AuthorizationException $e) {
            return response()->json(['reason' => $e->getMessage()], 403);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function verify(Request $request, Workspace $workspace, FixPlan $plan): JsonResponse
    {
        try {
            $verified = $this->fixPlanService->verifyPlan($request->user(), $workspace, $plan);
            return response()->json(['fix_plan' => $verified]);
        } catch (AuthorizationException $e) {
            return response()->json(['reason' => $e->getMessage()], 403);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function publishPr(Request $request, Workspace $workspace, FixPlan $plan): JsonResponse
    {
        try {
            $result = $this->fixPlanService->publishDraftPr($request->user(), $workspace, $plan);
            return response()->json($result);
        } catch (AuthorizationException $e) {
            return response()->json(['reason' => $e->getMessage()], 403);
        } catch (HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        }
    }
}
