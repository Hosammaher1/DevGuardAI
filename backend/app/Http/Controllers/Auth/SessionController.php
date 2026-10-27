<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SessionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $memberships = Membership::query()
            ->where('user_id', $user->getKey())
            ->where('active', true)
            ->get();

        $workspaceIds = $memberships->pluck('workspace_id');

        $workspaces = Workspace::query()
            ->whereIn('id', $workspaceIds)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $workspacesData = [];
        foreach ($memberships as $membership) {
            $workspace = $workspaces->get($membership->workspace_id);
            if ($workspace !== null) {
                $workspacesData[] = [
                    'id' => $workspace->getKey(),
                    'name' => $workspace->name,
                    'role' => $membership->role->value,
                ];
            }
        }

        // Keep workspaces deterministic by id
        usort($workspacesData, static fn (array $a, array $b): int => strcmp($a['id'], $b['id']));

        return response()->json([
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
            ],
            'workspaces' => $workspacesData,
        ])->header('Cache-Control', 'private, no-cache, no-store, must-revalidate');
    }
}
