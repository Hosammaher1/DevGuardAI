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
use Laravel\Socialite\Facades\Socialite;
use Throwable;
use UnexpectedValueException;

final class GitHubOAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('github')->redirect();
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
        } catch (Throwable) {
            return redirect('/')->with('oauth_error', 'github_authentication_failed');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
