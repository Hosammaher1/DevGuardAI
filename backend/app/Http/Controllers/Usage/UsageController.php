<?php

namespace App\Http\Controllers\Usage;

use App\Domain\Identity\CurrentMembershipAuthorization;
use App\Domain\Identity\WorkspaceAction;
use App\Domain\Operations\SelectiveContainment;
use App\Http\Controllers\Controller;
use App\Models\CreditAccount;
use App\Models\Repository;
use App\Models\Scan;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UsageController extends Controller
{
    public function __construct(
        private readonly CurrentMembershipAuthorization $authorization,
        private readonly SelectiveContainment $containment,
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

        $scanAccount = CreditAccount::query()
            ->where('workspace_id', $workspace->id)
            ->where('credit_kind', 'scan')
            ->first();

        $fixAccount = CreditAccount::query()
            ->where('workspace_id', $workspace->id)
            ->where('credit_kind', 'fix')
            ->first();

        $scansCount = Scan::query()->where('workspace_id', $workspace->id)->count();
        $reposCount = Repository::query()->where('workspace_id', $workspace->id)->where('status', 'connected')->count();

        return response()->json([
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
            ],
            'usage' => [
                'scan_credits' => [
                    'available' => $scanAccount->available_credits ?? 10,
                    'reserved' => $scanAccount->reserved_credits ?? 0,
                    'consumed' => $scanAccount->consumed_credits ?? 0,
                    'monthly_allowance' => 10,
                ],
                'fix_credits' => [
                    'available' => $fixAccount->available_credits ?? 5,
                    'reserved' => $fixAccount->reserved_credits ?? 0,
                    'consumed' => $fixAccount->consumed_credits ?? 0,
                    'monthly_allowance' => 5,
                ],
                'active_repositories' => $reposCount,
                'total_scans' => $scansCount,
            ],
            'system_health' => [
                'status' => 'healthy',
                'containment' => $this->containment->getStatus(),
                'rpo_target' => '15m',
                'rto_target' => '30m',
                'database' => 'operational',
            ],
        ]);
    }
}
