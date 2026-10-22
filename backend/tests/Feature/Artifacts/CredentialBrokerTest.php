<?php

namespace Tests\Feature\Artifacts;

use App\Domain\Artifacts\CredentialBroker;
use App\Domain\Artifacts\CredentialLeaseDenied;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CredentialBrokerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['devguard-artifacts.trusted_worker_assertion_key' => str_repeat('synthetic-worker-key-', 2)]);
    }

    public function test_dg_secret_001_credential_is_available_only_to_the_bound_trusted_worker(): void
    {
        $broker = app(CredentialBroker::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $lease = $broker->issue('synthetic-account', 'job-01', 'trusted-login-worker', 'https://staging.example.test', 'SYNTHETIC_SECRET_VALUE', $now);
        $assertion = $broker->issueWorkerAssertion('trusted-login-worker', 'job-01', $now);

        self::assertSame('SYNTHETIC_SECRET_VALUE', $broker->redeem($lease->token, $assertion, 'https://staging.example.test', $now));
        $this->expectException(CredentialLeaseDenied::class);
        $broker->redeem($lease->token, $broker->issueWorkerAssertion('repository-script', 'job-01', $now), 'https://staging.example.test', $now);
    }

    public function test_dg_secret_002_and_005_credential_denials_do_not_disclose_secret(): void
    {
        $broker = app(CredentialBroker::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $lease = $broker->issue('synthetic-account', 'job-02', 'trusted-login-worker', 'https://staging.example.test', 'DG_SECRET_SENTINEL_BROKER', $now);
        $assertion = $broker->issueWorkerAssertion('trusted-login-worker', 'job-02', $now);

        foreach ([
            [$broker->issueWorkerAssertion('repository-script', 'job-02', $now), 'https://staging.example.test'],
            [$assertion, 'https://attacker.example.test'],
            [$assertion, 'http://127.0.0.1'],
        ] as [$workerAssertion, $origin]) {
            try {
                $broker->redeem($lease->token, $workerAssertion, $origin, $now);
                self::fail('Untrusted worker or origin must be denied.');
            } catch (CredentialLeaseDenied $exception) {
                self::assertSame('credential_lease_denied', $exception->getMessage());
                self::assertStringNotContainsString('DG_SECRET_SENTINEL_BROKER', $exception->getMessage());
            }
        }
    }

    public function test_dg_secret_006_expired_revoked_and_rotated_leases_are_denied(): void
    {
        $broker = app(CredentialBroker::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $expired = $broker->issue('synthetic-account-expired', 'job-03', 'trusted-login-worker', 'https://staging.example.test', 'OLD_SECRET', $now);
        $expiredAssertion = $broker->issueWorkerAssertion('trusted-login-worker', 'job-03', $now);
        self::assertNotNull($broker->redeem($expired->token, $expiredAssertion, 'https://staging.example.test', $now));
        $laterAssertion = $broker->issueWorkerAssertion('trusted-login-worker', 'job-03', $expired->expiresAt);
        self::assertThrows(fn () => $broker->redeem($expired->token, $laterAssertion, 'https://staging.example.test', $expired->expiresAt));

        $revoked = $broker->issue('synthetic-account-revoked', 'job-04', 'trusted-login-worker', 'https://staging.example.test', 'REVOKED_SECRET', $now);
        $revokedAssertion = $broker->issueWorkerAssertion('trusted-login-worker', 'job-04', $now);
        $broker->revokeAccount('synthetic-account-revoked', $now);
        self::assertThrows(fn () => $broker->redeem($revoked->token, $revokedAssertion, 'https://staging.example.test', $now));

        $rotated = $broker->issue('synthetic-account-rotated', 'job-05', 'trusted-login-worker', 'https://staging.example.test', 'OLD_ROTATED_SECRET', $now);
        $rotatedAssertion = $broker->issueWorkerAssertion('trusted-login-worker', 'job-05', $now);
        $broker->rotateAccount('synthetic-account-rotated', 'NEW_ROTATED_SECRET', $now->addMinute());
        self::assertThrows(fn () => $broker->redeem($rotated->token, $rotatedAssertion, 'https://staging.example.test', $now));
    }

    public function test_credential_ciphertext_and_secret_material_are_not_serialized(): void
    {
        $broker = app(CredentialBroker::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $secret = 'DG_SECRET_SENTINEL_NEVER_PERSIST_PLAINTEXT';
        $lease = $broker->issue('synthetic-account-secure', 'job-06', 'trusted-login-worker', 'https://staging.example.test', $secret, $now);

        self::assertStringNotContainsString($secret, json_encode($lease->credential->getAttributes(), JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString($secret, $lease->credential->encrypted_secret);
    }

    public function test_forged_worker_assertion_and_missing_signing_key_fail_closed(): void
    {
        $broker = app(CredentialBroker::class);
        $now = CarbonImmutable::parse('2026-10-22T10:00:00Z');
        $lease = $broker->issue('synthetic-account-proof', 'job-07', 'trusted-login-worker', 'https://staging.example.test', 'SYNTHETIC_SECRET', $now);
        $assertion = $broker->issueWorkerAssertion('trusted-login-worker', 'job-07', $now);
        [$payload, $signature] = explode('.', $assertion, 2);
        $forgedPayload = rtrim(strtr(base64_encode('{"aud":"devguard-credential-broker","worker":"trusted-login-worker","job":"job-07","iat":1792663200,"exp":1792663230,"nonce":"forged"}'), '+/', '-_'), '=');

        self::assertNotSame($payload, $forgedPayload);
        self::assertThrows(fn () => $broker->redeem($lease->token, $forgedPayload.'.'.$signature, 'https://staging.example.test', $now), CredentialLeaseDenied::class);

        config(['devguard-artifacts.trusted_worker_assertion_key' => null]);
        self::assertThrows(fn () => $broker->issueWorkerAssertion('trusted-login-worker', 'job-07', $now), CredentialLeaseDenied::class);
        self::assertThrows(fn () => $broker->redeem($lease->token, $assertion, 'https://staging.example.test', $now), CredentialLeaseDenied::class);
    }
}
