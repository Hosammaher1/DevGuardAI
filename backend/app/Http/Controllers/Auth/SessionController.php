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
                'email' => $user->email,
                'nickname' => $request->session()->get('github_nickname', $user->name),
                'avatar_url' => $request->session()->get('github_avatar_url'),
            ],
            'workspaces' => $workspacesData,
            'is_demo' => (bool) $request->session()->get('is_demo_session', false),
            'has_github_token' => $request->session()->has('github_oauth_token'),
        ])->header('Cache-Control', 'private, no-cache, no-store, must-revalidate');
    }
}
