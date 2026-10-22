<?php

namespace Tests\Feature\Identity;

use App\Domain\Identity\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

final class GitHubOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirect_uses_only_the_github_socialite_driver(): void
    {
        Socialite::fake('github');

        $this->get('/auth/github/redirect')
            ->assertRedirect('https://socialite.fake/github/authorize');

        $this->get('/login')->assertNotFound();
        $this->get('/auth/github-app/callback')->assertNotFound();
    }

    public function test_valid_callback_uses_stable_github_identity_and_creates_personal_workspace(): void
    {
        Socialite::fake('github', SocialiteUser::fake([
            'id' => 'github-user-1001',
            'name' => 'Synthetic GitHub User',
            'email' => 'identity@example.test',
        ]));

        $this->get('/auth/github/callback')->assertRedirect('/dashboard');

        $user = User::query()->where('github_user_id', 'github-user-1001')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('workspaces', ['personal_owner_user_id' => $user->getKey()]);
        $this->assertDatabaseHas('memberships', [
            'user_id' => $user->getKey(),
            'role' => Role::Owner->value,
            'active' => true,
        ]);
        self::assertNull($user->getAttribute('github_token'));
    }

    public function test_mutable_email_neither_merges_distinct_github_identities_nor_forks_one_identity(): void
    {
        $this->performCallback('github-user-a', 'shared@example.test', 'Identity A');
        $this->post('/logout');
        $this->performCallback('github-user-b', 'shared@example.test', 'Identity B');

        self::assertSame(2, User::query()->where('email', 'shared@example.test')->count());

        $this->post('/logout');
        $this->performCallback('github-user-a', 'changed@example.test', 'Identity A Updated');

        self::assertSame(2, User::query()->count());
        $userA = User::query()->where('github_user_id', 'github-user-a')->sole();
        self::assertSame('changed@example.test', $userA->email);
        self::assertSame('Identity A Updated', $userA->name);
    }

    public function test_invalid_state_or_provider_failure_creates_no_session_or_identity(): void
    {
        Socialite::fake('github', static function (): never {
            throw new InvalidStateException;
        });

        $this->get('/auth/github/callback')
            ->assertRedirect('/')
            ->assertSessionHas('oauth_error', 'github_authentication_failed');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_logout_invalidates_authentication(): void
    {
        $this->performCallback('github-user-logout', 'logout@example.test', 'Logout User');
        $this->assertAuthenticated();

        $this->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    private function performCallback(string $githubId, string $email, string $name): void
    {
        Socialite::fake('github', SocialiteUser::fake([
            'id' => $githubId,
            'name' => $name,
            'email' => $email,
        ]));

        $this->get('/auth/github/callback')->assertRedirect('/dashboard');
    }
}
