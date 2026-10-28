<?php

namespace App\Http\Middleware;

use App\Models\Membership;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforceDemoRestrictions
{
    /**
     * Handle an incoming request.
     * Enforces strict tenant isolation for staging/invitation sessions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('is_demo_session', false)) {
            return $next($request);
        }

        $workspaceParam = $request->route('workspace');
        $workspaceId = is_object($workspaceParam) ? $workspaceParam->getKey() : (string) $workspaceParam;

        if ($workspaceId !== '') {
            $user = $request->user();
            if ($user === null) {
                return response()->json(['error' => 'unauthenticated'], 401);
            }

            // Enforce tenant boundary: staging user can only access workspaces where they have active membership
            $hasMembership = Membership::query()
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $user->id)
                ->where('active', true)
                ->exists();

            if (! $hasMembership) {
                return response()->json([
                    'error' => 'tenant_isolation_violation',
                    'message' => 'Access denied: You do not have permission to access another tenant workspace.',
                ], 403);
            }
        }

        return $next($request);
    }
}
