<?php

namespace App\Domain\Artifacts;

use App\Models\CredentialAccount;
use App\Models\CredentialLease;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

final class CredentialBroker
{
    public function issue(
        string $accountKey,
        string $jobIdentifier,
        string $workerIdentifier,
        string $targetOrigin,
        string $secret,
        CarbonImmutable $now,
    ): IssuedCredentialLease {
        if ($accountKey === '' || $jobIdentifier === '' || $workerIdentifier === '' || $secret === '') {
            throw new \InvalidArgumentException('credential_lease_parameters_invalid');
        }
        $origin = $this->normalizeOrigin($targetOrigin);
        $token = $this->newToken();
        $leaseSeconds = (int) config('devguard-artifacts.credential_lease_ttl_seconds', 120);
        $accountSeconds = (int) config('devguard-artifacts.credential_account_ttl_seconds', 1800);
        if ($leaseSeconds < 1 || $leaseSeconds > 300 || $accountSeconds < $leaseSeconds || $accountSeconds > 86400) {
            throw new \LogicException('credential_lease_ttl_invalid');
        }
        $leaseExpires = $now->addSeconds($leaseSeconds);

        return DB::transaction(function () use ($accountKey, $jobIdentifier, $workerIdentifier, $origin, $secret, $now, $token, $leaseExpires, $accountSeconds): IssuedCredentialLease {
            $account = CredentialAccount::query()->where('account_key', $accountKey)->lockForUpdate()->first();
            if ($account === null) {
                $account = CredentialAccount::query()->create([
                    'account_key' => $accountKey,
                    'encrypted_secret' => Crypt::encryptString($secret),
                    'version' => 1,
                    'expires_at' => $now->addSeconds($accountSeconds),
                    'revoked_at' => null,
                ]);
            } else {
                CredentialLease::query()->where('credential_account_id', $account->getKey())->whereNull('revoked_at')->update(['revoked_at' => $now]);
                $account->update([
                    'encrypted_secret' => Crypt::encryptString($secret),
                    'version' => $account->version + 1,
                    'expires_at' => $now->addSeconds($accountSeconds),
                    'revoked_at' => null,
                ]);
            }

            $lease = CredentialLease::query()->create([
                'credential_account_id' => $account->getKey(),
                'job_identifier' => $jobIdentifier,
                'worker_identifier' => $workerIdentifier,
                'target_origin' => $origin,
                'token_hash' => hash('sha256', $token),
                'credential_version' => $account->version,
                'expires_at' => $leaseExpires,
                'created_at' => $now,
            ]);

            return new IssuedCredentialLease($account, $lease, $token, $leaseExpires);
        }, 3);
    }

    public function issueWorkerAssertion(string $workerIdentifier, string $jobIdentifier, CarbonImmutable $now): string
    {
        $key = $this->assertionKey();
        if ($workerIdentifier === '' || $jobIdentifier === '') {
            throw new CredentialLeaseDenied;
        }
        $payload = rtrim(strtr(base64_encode(json_encode([
            'aud' => 'devguard-credential-broker',
            'worker' => $workerIdentifier,
            'job' => $jobIdentifier,
            'iat' => $now->getTimestamp(),
            'exp' => $now->addSeconds(30)->getTimestamp(),
            'nonce' => bin2hex(random_bytes(16)),
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        return $payload.'.'.hash_hmac('sha256', $payload, $key);
    }

    public function redeem(string $rawToken, string $workerAssertion, string $targetOrigin, CarbonImmutable $now): string
    {
        $origin = $this->normalizeOrigin($targetOrigin);
        $identity = $this->verifyWorkerAssertion($workerAssertion, $now);

        return DB::transaction(function () use ($rawToken, $identity, $origin, $now): string {
            $lease = CredentialLease::query()->where('token_hash', hash('sha256', $rawToken))->lockForUpdate()->first();
            if ($lease === null || $lease->revoked_at !== null || $lease->redeemed_at !== null || $lease->expires_at->lessThanOrEqualTo($now)) {
                throw new CredentialLeaseDenied;
            }
            if (! hash_equals($lease->worker_identifier, $identity['worker'])
                || ! hash_equals($lease->job_identifier, $identity['job'])
                || ! hash_equals($lease->target_origin, $origin)) {
                throw new CredentialLeaseDenied;
            }

            $account = CredentialAccount::query()->whereKey($lease->credential_account_id)->lockForUpdate()->first();
            if ($account === null || $account->revoked_at !== null || $account->expires_at->lessThanOrEqualTo($now) || $account->version !== $lease->credential_version) {
                throw new CredentialLeaseDenied;
            }

            $lease->update(['redeemed_at' => $now]);

            return Crypt::decryptString($account->encrypted_secret);
        }, 3);
    }

    public function rotateAccount(string $accountKey, string $newSecret, CarbonImmutable $rotatedAt): void
    {
        DB::transaction(function () use ($accountKey, $newSecret, $rotatedAt): void {
            $account = CredentialAccount::query()->where('account_key', $accountKey)->lockForUpdate()->first();
            if ($account === null || $newSecret === '') {
                throw new CredentialLeaseDenied;
            }
            CredentialLease::query()->where('credential_account_id', $account->getKey())->whereNull('revoked_at')->update(['revoked_at' => $rotatedAt]);
            $account->update([
                'encrypted_secret' => Crypt::encryptString($newSecret),
                'version' => $account->version + 1,
                'expires_at' => $rotatedAt->addSeconds((int) config('devguard-artifacts.credential_account_ttl_seconds', 1800)),
                'revoked_at' => null,
            ]);
        }, 3);
    }

    public function revokeAccount(string $accountKey, CarbonImmutable $revokedAt): void
    {
        DB::transaction(function () use ($accountKey, $revokedAt): void {
            $account = CredentialAccount::query()->where('account_key', $accountKey)->lockForUpdate()->first();
            if ($account === null) {
                return;
            }
            CredentialLease::query()->where('credential_account_id', $account->getKey())->whereNull('revoked_at')->update(['revoked_at' => $revokedAt]);
            $account->update(['revoked_at' => $revokedAt, 'version' => $account->version + 1]);
        }, 3);
    }

    private function normalizeOrigin(string $origin): string
    {
        $parts = parse_url($origin);
        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || isset($parts['path'])) {
            throw new CredentialLeaseDenied;
        }
        $host = strtolower(rtrim((string) $parts['host'], '.'));
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP) !== false || ! str_contains($host, '.')) {
            throw new CredentialLeaseDenied;
        }
        $port = isset($parts['port']) ? (int) $parts['port'] : 443;
        if ($port < 1 || $port > 65535) {
            throw new CredentialLeaseDenied;
        }

        return 'https://'.$host.($port === 443 ? '' : ':'.$port);
    }

    private function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /** @return array{worker: string, job: string} */
    private function verifyWorkerAssertion(string $assertion, CarbonImmutable $now): array
    {
        $parts = explode('.', $assertion);
        if (count($parts) !== 2) {
            throw new CredentialLeaseDenied;
        }
        [$encodedPayload, $signature] = $parts;
        if (! hash_equals(hash_hmac('sha256', $encodedPayload, $this->assertionKey()), $signature)) {
            throw new CredentialLeaseDenied;
        }
        $json = base64_decode(strtr($encodedPayload, '-_', '+/'), true);
        if (! is_string($json)) {
            throw new CredentialLeaseDenied;
        }
        try {
            $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new CredentialLeaseDenied;
        }
        if (! is_array($payload)
            || ($payload['aud'] ?? null) !== 'devguard-credential-broker'
            || ! is_string($payload['worker'] ?? null)
            || ! is_string($payload['job'] ?? null)
            || ! is_int($payload['iat'] ?? null)
            || ! is_int($payload['exp'] ?? null)
            || ! is_string($payload['nonce'] ?? null)
            || $payload['iat'] > $now->getTimestamp()
            || $payload['exp'] <= $now->getTimestamp()
            || $payload['exp'] - $payload['iat'] > 30
            || $payload['iat'] < $now->subSeconds(30)->getTimestamp()) {
            throw new CredentialLeaseDenied;
        }

        return ['worker' => $payload['worker'], 'job' => $payload['job']];
    }

    private function assertionKey(): string
    {
        $key = config('devguard-artifacts.trusted_worker_assertion_key');
        if (! is_string($key) || strlen($key) < 32) {
            throw new CredentialLeaseDenied;
        }

        return $key;
    }
}
