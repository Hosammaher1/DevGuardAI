<?php

namespace Tests\Feature\Identity;

use App\Domain\Identity\Role;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

final class MvpSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_requires_authentication(): void
    {
        $this->getJson('/api/session')->assertUnauthorized();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_authenticated_session_returns_minimal_user_and_only_active_workspaces(): void
    {
        $user = User::query()->create([
            'github_user_id' => '10001',
            'name' => 'Synthetic User',
            'email' => 'synthetic@example.test',
        ]);

        $otherUser = User::query()->create([
            'github_user_id' => '99999',
            'name' => 'Foreign User',
            'email' => 'foreign@example.test',
        ]);

        $activeWorkspace = Workspace::query()->create([
            'personal_owner_user_id' => $user->getKey(),
            'name' => 'Active Workspace',
        ]);

        $inactiveWorkspace = Workspace::query()->create([
            'personal_owner_user_id' => null,
            'name' => 'Inactive Workspace',
        ]);

        $foreignWorkspace = Workspace::query()->create([
            'personal_owner_user_id' => $otherUser->getKey(),
            'name' => 'Foreign Workspace',
        ]);

        Membership::query()->create([
            'workspace_id' => $activeWorkspace->getKey(),
            'user_id' => $user->getKey(),
            'role' => Role::Owner,
            'active' => true,
            'version' => 1,
        ]);

        Membership::query()->create([
            'workspace_id' => $inactiveWorkspace->getKey(),
            'user_id' => $user->getKey(),
            'role' => Role::Viewer,
            'active' => false,
            'version' => 2,
        ]);

        Membership::query()->create([
            'workspace_id' => $foreignWorkspace->getKey(),
            'user_id' => $otherUser->getKey(),
            'role' => Role::Owner,
            'active' => true,
            'version' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/session');

        $response->assertOk();

        $response->assertExactJson([
            'user' => [
                'id' => $user->getKey(),
                'name' => 'Synthetic User',
            ],
            'workspaces' => [
                [
                    'id' => $activeWorkspace->getKey(),
                    'name' => 'Active Workspace',
                    'role' => Role::Owner->value,
                ],
            ],
        ]);

        $data = $response->json();
        self::assertArrayNotHasKey('email', $data['user']);
        self::assertArrayNotHasKey('github_user_id', $data['user']);
        self::assertArrayNotHasKey('github_token', $data['user']);
        self::assertArrayNotHasKey('token', $data['user']);
    }

    public function test_dev_session_returns_404_and_does_not_create_or_authenticate_demo_user(): void
    {
        $response = $this->getJson('/api/dev/session');

        $response->assertNotFound();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('workspaces', 0);
        $this->assertDatabaseCount('memberships', 0);
    }

    public function test_oauth_callback_for_existing_personal_workspace_preserves_inactive_viewer_membership_and_version(): void
    {
        $existingUser = User::query()->create([
            'github_user_id' => 'github-user-downgraded',
            'name' => 'Downgraded User',
            'email' => 'downgraded@example.test',
        ]);

        $workspace = Workspace::query()->create([
            'personal_owner_user_id' => $existingUser->getKey(),
            'name' => 'Downgraded Personal Workspace',
        ]);

        $membership = Membership::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $existingUser->getKey(),
            'role' => Role::Viewer,
            'active' => false,
            'version' => 7,
        ]);

        Socialite::fake('github', SocialiteUser::fake([
            'id' => 'github-user-downgraded',
            'name' => 'Downgraded User',
            'email' => 'downgraded@example.test',
        ]));

        $this->get('/auth/github/callback')->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($existingUser);

        $membership->refresh();
        self::assertFalse($membership->active);
        self::assertSame(Role::Viewer, $membership->role);
        self::assertSame(7, $membership->version);
    }
}
