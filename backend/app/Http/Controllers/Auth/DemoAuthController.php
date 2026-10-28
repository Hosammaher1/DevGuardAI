<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Demo\DemoWorkspaceProvisioner;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class DemoAuthController extends Controller
{
    public function __construct(
        private readonly DemoWorkspaceProvisioner $provisioner,
    ) {}

    public function login(Request $request): JsonResponse
    {
        try {
            $code = trim((string) $request->input('invitation_code', ''));

            if ($code === '') {
                return response()->json([
                    'error' => 'missing_code',
                    'message' => 'Please enter a valid invitation code.',
                ], 422);
            }

            if (! $this->verifyInvitationCode($code)) {
                return response()->json([
                    'error' => 'invalid_code',
                    'message' => 'The invitation code you entered is invalid or expired.',
                ], 401);
            }

            $provisioned = $this->provisioner->provision();
            $user = $provisioned['user'];
            $workspace = $provisioned['workspace'];

            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put('is_demo_session', true);

            return response()->json([
                'status' => 'authenticated',
                'message' => 'Staging session established successfully.',
                'workspace_id' => $workspace->getKey(),
                'user' => [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'workspace' => [
                    'id' => $workspace->getKey(),
                    'name' => $workspace->name,
                    'role' => 'owner',
                ],
                'is_demo' => false,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Staging login error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => 'server_error',
                'message' => 'Login error: ' . $e->getMessage(),
                'exception' => get_class($e),
                'location' => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*') || $request->is('auth/*')) {
            return response()->json([
                'status' => 'logged_out',
                'message' => 'Session invalidated successfully.',
            ]);
        }

        return redirect('/');
    }

    private function verifyInvitationCode(string $code): bool
    {
        $configuredHash = config('devguard-demo.invitation_code_hash');

        if (! empty($configuredHash)) {
            $inputHash = hash('sha256', $code);
            return hash_equals((string) $configuredHash, $inputHash);
        }

        $configuredCode = (string) config('devguard-demo.invitation_code', 'devguard-demo-2026');

        return hash_equals($configuredCode, $code);
    }
}
