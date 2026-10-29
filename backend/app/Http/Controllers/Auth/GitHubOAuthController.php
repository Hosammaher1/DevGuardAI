<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Role;
use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;
use UnexpectedValueException;

final class GitHubOAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('github')
            ->scopes(['read:user', 'user:email', 'repo'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $githubUser = Socialite::driver('github')->user();
            $githubUserId = trim((string) $githubUser->getId());

            if ($githubUserId === '') {
                throw new UnexpectedValueException('GitHub identity is missing.');
            }

            $user = DB::transaction(function () use ($githubUser, $githubUserId): User {
                $user = User::query()->updateOrCreate(
                    ['github_user_id' => $githubUserId],
                    [
                        'name' => $githubUser->getName() ?: $githubUser->getNickname() ?: 'GitHub user',
                        'email' => $githubUser->getEmail(),
                    ],
                );

                $workspaceWasCreated = false;
                $workspace = Workspace::query()->where('personal_owner_user_id', $user->getKey())->first();

                if ($workspace === null) {
                    $workspace = Workspace::query()->create([
                        'personal_owner_user_id' => $user->getKey(),
                        'name' => $user->name.' personal workspace',
                    ]);
                    $workspaceWasCreated = true;
                }

                if ($workspaceWasCreated) {
                    Membership::query()->create([
                        'workspace_id' => $workspace->getKey(),
                        'user_id' => $user->getKey(),
                        'role' => Role::Owner,
                        'active' => true,
                        'version' => 1,
                    ]);
                }

                return $user;
            });
        } catch (Throwable $exception) {
            $context = [
                'exception_type' => $exception::class,
                'code' => $exception->getCode(),
                'message' => $this->sanitizeErrorMessage($exception->getMessage()),
            ];

            if ($exception instanceof \PDOException && isset($exception->errorInfo) && is_array($exception->errorInfo)) {
                $context['sqlstate'] = $exception->errorInfo[0] ?? null;
                $context['driver_code'] = $exception->errorInfo[1] ?? null;
                $context['driver_message'] = isset($exception->errorInfo[2])
                    ? $this->sanitizeErrorMessage((string) $exception->errorInfo[2])
                    : null;
            }

            Log::warning('GitHub OAuth callback failed', $context);
            return redirect($this->appUrl('/'))->with('oauth_error', 'github_authentication_failed');
        }

        Auth::login($user);
        $request->session()->regenerate();

        if (! empty($githubUser->token)) {
            $request->session()->put('github_oauth_token', $githubUser->token);
        }
        if (! empty($githubUser->getNickname())) {
            $request->session()->put('github_nickname', $githubUser->getNickname());
        }
        if (! empty($githubUser->getAvatar())) {
            $request->session()->put('github_avatar_url', $githubUser->getAvatar());
        }

        return redirect($this->appUrl('/dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($this->appUrl('/'));
    }

    private function appUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').($path === '/' ? '' : $path);
    }

    private function sanitizeErrorMessage(string $message): string
    {
        $sanitized = preg_replace('/([a-z]+:\/\/[^:]+:)[^@]+(@)/i', '$1***$2', $message);
        $sanitized = preg_replace('/(password|passwd|secret|token|key)\s*=\s*[^\s;,]+/i', '$1=***', (string) $sanitized);

        return (string) $sanitized;
    }
}
