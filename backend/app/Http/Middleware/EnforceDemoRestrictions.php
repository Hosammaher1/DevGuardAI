<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforceDemoRestrictions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('is_demo_session', false)) {
            return $next($request);
        }

        $workspaceParam = $request->route('workspace');
        $workspaceId = is_object($workspaceParam) ? $workspaceParam->getKey() : (string) $workspaceParam;

        $demoWorkspaceName = (string) config('devguard-demo.workspace_name', 'DevGuard AI Demo Workspace');

        if ($workspaceId !== '') {
            $workspace = is_object($workspaceParam)
                ? $workspaceParam
                : Workspace::query()->whereKey($workspaceId)->first();

            if ($workspace === null || $workspace->name !== $demoWorkspaceName) {
                return response()->json([
                    'error' => 'tenant_isolation_violation',
                    'message' => 'Demo sessions are strictly confined to the demo preview workspace.',
                ], 403);
            }
        }

        // Disable external / destructive writes for demo sessions
        if ($request->isMethod('POST') || $request->isMethod('PATCH') || $request->isMethod('DELETE')) {
            if ($request->is('*/publish-pr*')) {
                return response()->json([
                    'error' => 'demo_read_only',
                    'message' => 'GitHub Draft PR publication is disabled in the shared demo environment to prevent untrusted external repository modifications.',
                ], 403);
            }

            if ($request->is('*/invitations*') || $request->is('*/membership*') || $request->is('*/members/*') || $request->is('*/ownership-transfers*')) {
                return response()->json([
                    'error' => 'demo_read_only',
                    'message' => 'Workspace membership and role administration are disabled in the shared demo environment.',
                ], 403);
            }

            if ($request->is('*/github/installations*')) {
                return response()->json([
                    'error' => 'demo_read_only',
                    'message' => 'GitHub App installations are disabled in the shared demo environment.',
                ], 403);
            }
        }

        return $next($request);
    }
}
