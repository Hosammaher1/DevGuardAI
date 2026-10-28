<?php

namespace Tests\Feature\Identity;

use App\Database\PostgresConnection;
use App\Domain\Identity\Role;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PDO;
use PDOException;
use PDOStatement;
use Symfony\Component\Process\Process;
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

    public function test_github_callback_uses_configured_https_origin_behind_render_proxy(): void
    {
        $script = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$request = \Illuminate\Http\Request::create(
    getenv('TEST_REQUEST_URL'),
    'GET', [], [], [], ['HTTP_X_FORWARDED_PROTO' => 'https', 'REMOTE_ADDR' => '198.51.100.10']
);
$response = $app->make(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
echo $response->headers->get('Location');
PHP;

        foreach ([
            ['https://devguard-api-dev-01.onrender.com', 'http://devguard-api-dev-01.onrender.com/auth/github/redirect', 'https://devguard-api-dev-01.onrender.com/auth/github/callback'],
            ['http://localhost', 'http://localhost/auth/github/redirect', 'http://localhost/auth/github/callback'],
            ['https://devguard-api-dev-01.onrender.com', 'http://untrusted.example/auth/github/redirect', 'https://devguard-api-dev-01.onrender.com/auth/github/callback'],
        ] as [$appUrl, $requestUrl, $expectedCallback]) {
            $process = new Process([PHP_BINARY, '-r', $script], base_path(), [
                'APP_ENV' => 'testing',
                'APP_URL' => $appUrl,
                'GITHUB_REDIRECT_URI' => '/auth/github/callback',
                'GITHUB_CLIENT_ID' => 'test-client-id',
                'GITHUB_CLIENT_SECRET' => 'test-client-secret',
                'SESSION_DRIVER' => 'array',
                'TEST_REQUEST_URL' => $requestUrl,
            ]);
            $process->run();
            self::assertSame(0, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());

            parse_str((string) parse_url($process->getOutput(), PHP_URL_QUERY), $query);
            self::assertSame($expectedCallback, $query['redirect_uri'] ?? null);
        }
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

    public function test_oauth_login_persists_through_a_new_session_store_and_logout_clears_it(): void
    {
        config(['session.driver' => 'database', 'session.secure' => true]);
        app('session')->forgetDrivers();

        $guest = $this->getJson('/api/session')->assertUnauthorized();
        $sessionName = config('session.cookie');
        $guestCookie = collect($guest->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === $sessionName);
        self::assertNotNull($guestCookie);

        Socialite::fake('github', SocialiteUser::fake([
            'id' => 'github-session-1001',
            'name' => 'Session User',
            'email' => 'session@example.test',
        ]));
        $this->withUnencryptedCookie($sessionName, $guestCookie->getValue())->withCredentials();
        $callback = $this->get('/auth/github/callback')->assertRedirect('/dashboard');
        $loginCookie = collect($callback->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === $sessionName);
        self::assertNotNull($loginCookie);
        self::assertNotSame($guestCookie->getValue(), $loginCookie->getValue());
        self::assertTrue($loginCookie->isSecure());
        self::assertTrue($loginCookie->isHttpOnly());
        self::assertNull($loginCookie->getDomain());
        self::assertSame('lax', $loginCookie->getSameSite());

        Auth::forgetGuards();
        app('session')->forgetDrivers();
        $this->withUnencryptedCookie($sessionName, $loginCookie->getValue())
            ->getJson('/api/session')
            ->assertOk()
            ->assertJsonPath('user.name', 'Session User')
            ->assertJsonCount(1, 'workspaces');

        $logout = $this->post('/logout')->assertRedirect('/');
        $logoutCookie = collect($logout->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === $sessionName);
        self::assertNotNull($logoutCookie);
        Auth::forgetGuards();
        app('session')->forgetDrivers();
        $this->withUnencryptedCookie($sessionName, $logoutCookie->getValue())
            ->getJson('/api/session')
            ->assertUnauthorized();
    }

    public function test_successful_callback_redirects_to_configured_https_dashboard(): void
    {
        config(['app.url' => 'https://devguard-api-dev-01.onrender.com']);
        Socialite::fake('github', SocialiteUser::fake([
            'id' => 'github-https-1001',
            'name' => 'HTTPS User',
            'email' => 'https@example.test',
        ]));

        $this->get('http://devguard-api-dev-01.onrender.com/auth/github/callback')
            ->assertRedirect('https://devguard-api-dev-01.onrender.com/dashboard');
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

    public function test_real_github_provider_rejects_invalid_oauth_state(): void
    {
        config([
            'services.github.client_id' => 'test-client-id',
            'services.github.client_secret' => 'test-client-secret',
        ]);

        $this->get('/auth/github/redirect')->assertRedirectContains('github.com/login/oauth/authorize');
        $this->get('/auth/github/callback?code=not-used&state=invalid')->assertRedirect('/');
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

    public function test_database_failure_during_callback_logs_sanitized_warning_and_fails_closed(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('GitHub OAuth callback failed', \Mockery::on(function (array $context): bool {
                return $context['exception_type'] === PDOException::class
                    && str_contains($context['message'], '***')
                    && !str_contains($context['message'], 'supersecretpassword');
            }));

        User::saving(function (): void {
            throw new PDOException('Connection error to postgresql://user:supersecretpassword@neon.tech/db failed');
        });

        Socialite::fake('github', SocialiteUser::fake([
            'id' => 'github-db-fail',
            'name' => 'DB Fail User',
            'email' => 'fail@example.test',
        ]));

        $this->get('/auth/github/callback')
            ->assertRedirect('/')
            ->assertSessionHas('oauth_error', 'github_authentication_failed');

        $this->assertGuest();
    }

    public function test_postgresql_connection_preserves_boolean_bindings_for_membership_active(): void
    {
        $mockPdo = new class extends PDO {
            public function __construct() {}
        };

        $connection = new PostgresConnection($mockPdo, 'database', '', []);

        // Positional bindings as generated by QueryBuilder insert
        $bindings = [
            '01HXYZ',
            101,
            Role::Owner->value,
            true,
            1,
        ];

        $prepared = $connection->prepareBindings($bindings);

        self::assertTrue($prepared[3]);
        self::assertIsBool($prepared[3], 'PostgreSQL active column requires native boolean binding, integer sent by default base Connection.');

        $statement = Mockery::mock(PDOStatement::class);
        $statement->shouldReceive('bindValue')
            ->with(1, '01HXYZ', PDO::PARAM_STR)
            ->once();
        $statement->shouldReceive('bindValue')
            ->with(2, 101, PDO::PARAM_INT)
            ->once();
        $statement->shouldReceive('bindValue')
            ->with(3, 'owner', PDO::PARAM_STR)
            ->once();
        $statement->shouldReceive('bindValue')
            ->with(4, true, PDO::PARAM_BOOL)
            ->once();
        $statement->shouldReceive('bindValue')
            ->with(5, 1, PDO::PARAM_INT)
            ->once();

        $connection->bindValues($statement, $prepared);
    }

    public function test_membership_model_normalizes_active_attribute_to_boolean(): void
    {
        $membershipWithInt = new Membership();
        $membershipWithInt->active = 1;
        self::assertTrue($membershipWithInt->getAttributes()['active']);
        self::assertIsBool($membershipWithInt->getAttributes()['active']);

        $membershipWithString = new Membership(['active' => '1']);
        self::assertTrue($membershipWithString->getAttributes()['active']);
        self::assertIsBool($membershipWithString->getAttributes()['active']);

        $membershipWithFalse = new Membership(['active' => 0]);
        self::assertFalse($membershipWithFalse->getAttributes()['active']);
        self::assertIsBool($membershipWithFalse->getAttributes()['active']);
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
