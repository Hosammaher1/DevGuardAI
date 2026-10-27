<?php

namespace App\Domain\GitHub;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubAppClient
{
    public function __construct(
        private readonly ?string $appId = null,
        private readonly ?string $privateKey = null,
    ) {}

    /**
     * Generate an RS256 JWT for authenticating as the GitHub App.
     */
    public function createAppJwt(?string $appId = null, ?string $privateKey = null): ?string
    {
        $appId = $appId ?? $this->appId ?? config('services.github.app_id');
        $privateKey = $privateKey ?? $this->privateKey ?? config('services.github.private_key');

        if (! $appId || ! $privateKey) {
            return null;
        }

        $now = time();
        $payload = [
            'iat' => $now - 60,
            'exp' => $now + (10 * 60),
            'iss' => (string) $appId,
        ];

        $header = [
            'typ' => 'JWT',
            'alg' => 'RS256',
        ];

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        $signingInput = $base64UrlHeader . '.' . $base64UrlPayload;

        $key = openssl_pkey_get_private($privateKey);
        if (! $key) {
            Log::error('Invalid GitHub App private key format');
            return null;
        }

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            Log::error('Failed to sign GitHub App JWT with private key');
            return null;
        }

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        return $signingInput . '.' . $base64UrlSignature;
    }

    /**
     * Verify and fetch installation details from GitHub.
     *
     * @return array<string, mixed>|null
     */
    public function getInstallation(int $githubInstallationId): ?array
    {
        $appId = $this->appId ?? config('services.github.app_id');
        $privateKey = $this->privateKey ?? config('services.github.private_key');

        if (! $appId || ! $privateKey) {
            if (app()->environment('production')) {
                Log::error('Cannot verify GitHub installation in production: credentials not configured.');
                return null;
            }

            return [
                'id' => $githubInstallationId,
                'account' => [
                    'login' => null,
                    'type' => 'Organization',
                ],
                'repository_selection' => 'selected',
                'permissions' => ['contents' => 'read', 'checks' => 'write', 'pull_requests' => 'write'],
            ];
        }

        $jwt = $this->createAppJwt($appId, $privateKey);
        if ($jwt === null) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwt,
                'Accept' => 'application/vnd.github+json',
            ])->timeout(10)->get("https://api.github.com/app/installations/{$githubInstallationId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("GitHub App installation lookup failed: HTTP {$response->status()}", [
                'installation_id' => $githubInstallationId,
            ]);
        } catch (\Throwable $e) {
            Log::warning("GitHub App installation request exception: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * @return array<int, array{
     *     github_repo_id: int,
     *     name: string,
     *     full_name: string,
     *     owner_login: string,
     *     is_private: bool,
     *     default_branch: string,
     * }>
     */
    public function listInstallationRepositories(int $githubInstallationId): array
    {
        $token = $this->createInstallationToken($githubInstallationId);

        if ($token === null) {
            if (app()->environment('production')) {
                return [];
            }
            return $this->fallbackMockRepositories($githubInstallationId);
        }

        if (str_starts_with($token['token'], 'ghs_mock_')) {
            return $this->fallbackMockRepositories($githubInstallationId);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token['token'],
                'Accept' => 'application/vnd.github+json',
            ])->timeout(10)->get('https://api.github.com/installation/repositories');

            if ($response->successful()) {
                $data = $response->json('repositories') ?? [];
                $repos = [];
                foreach ($data as $r) {
                    $repos[] = [
                        'github_repo_id' => (int) $r['id'],
                        'name' => (string) $r['name'],
                        'full_name' => (string) $r['full_name'],
                        'owner_login' => (string) ($r['owner']['login'] ?? ''),
                        'is_private' => (bool) ($r['private'] ?? false),
                        'default_branch' => (string) ($r['default_branch'] ?? 'main'),
                    ];
                }
                return $repos;
            }
        } catch (\Throwable $e) {
            Log::warning("GitHub App API call failed: {$e->getMessage()}");
        }

        if (app()->environment('production')) {
            return [];
        }

        return $this->fallbackMockRepositories($githubInstallationId);
    }

    /**
     * @return array{token: string, expires_at: CarbonImmutable}|null
     */
    public function createInstallationToken(int $githubInstallationId): ?array
    {
        $appId = $this->appId ?? config('services.github.app_id');
        $privateKey = $this->privateKey ?? config('services.github.private_key');

        if (! $appId || ! $privateKey) {
            if (app()->environment('production')) {
                Log::error('Cannot create installation token in production: GitHub App credentials missing.');
                return null;
            }

            return [
                'token' => 'ghs_mock_ephemeral_token_' . hash('sha256', (string) $githubInstallationId),
                'expires_at' => CarbonImmutable::now()->addHour(),
            ];
        }

        $jwt = $this->createAppJwt($appId, $privateKey);
        if ($jwt === null) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwt,
                'Accept' => 'application/vnd.github+json',
            ])->timeout(10)->post("https://api.github.com/app/installations/{$githubInstallationId}/access_tokens");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'token' => (string) $data['token'],
                    'expires_at' => CarbonImmutable::parse($data['expires_at']),
                ];
            }

            Log::error("Failed to generate installation token from GitHub: HTTP {$response->status()}", [
                'installation_id' => $githubInstallationId,
            ]);
        } catch (\Throwable $e) {
            Log::error("Exception exchanging GitHub App installation token: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Create a draft pull request on GitHub via installation access token.
     *
     * @return array{number: int, html_url: string, draft: bool}|null
     */
    public function createDraftPullRequest(
        int $githubInstallationId,
        string $ownerLogin,
        string $repoName,
        string $title,
        string $headBranch,
        string $baseBranch,
        string $body
    ): ?array {
        $token = $this->createInstallationToken($githubInstallationId);
        if ($token === null) {
            return null;
        }

        if (str_starts_with($token['token'], 'ghs_mock_')) {
            if (app()->environment('production')) {
                return null;
            }

            $simulatedPrNumber = (int) (hexdec(substr(md5($headBranch), 0, 4)) % 900) + 100;
            return [
                'number' => $simulatedPrNumber,
                'html_url' => "https://github.com/{$ownerLogin}/{$repoName}/pull/{$simulatedPrNumber}",
                'draft' => true,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token['token'],
                'Accept' => 'application/vnd.github+json',
            ])->timeout(15)->post("https://api.github.com/repos/{$ownerLogin}/{$repoName}/pulls", [
                'title' => $title,
                'head' => $headBranch,
                'base' => $baseBranch,
                'body' => $body,
                'draft' => true,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'number' => (int) $data['number'],
                    'html_url' => (string) $data['html_url'],
                    'draft' => (bool) ($data['draft'] ?? true),
                ];
            }

            Log::error("Failed to create draft PR on GitHub: HTTP {$response->status()}", [
                'response' => $response->json(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Exception creating GitHub draft PR: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * @return array<int, array{
     *     github_repo_id: int,
     *     name: string,
     *     full_name: string,
     *     owner_login: string,
     *     is_private: bool,
     *     default_branch: string,
     * }>
     */
    public function fallbackMockRepositories(int $githubInstallationId): array
    {
        return [
            [
                'github_repo_id' => 987654,
                'name' => 'web-frontend',
                'full_name' => 'acme-corp/web-frontend',
                'owner_login' => 'acme-corp',
                'is_private' => true,
                'default_branch' => 'main',
            ],
            [
                'github_repo_id' => 987655,
                'name' => 'api-backend',
                'full_name' => 'acme-corp/api-backend',
                'owner_login' => 'acme-corp',
                'is_private' => true,
                'default_branch' => 'main',
            ],
            [
                'github_repo_id' => 998811,
                'name' => 'trusted-demo',
                'full_name' => 'scan-org/trusted-demo',
                'owner_login' => 'scan-org',
                'is_private' => false,
                'default_branch' => 'main',
            ],
        ];
    }
}
