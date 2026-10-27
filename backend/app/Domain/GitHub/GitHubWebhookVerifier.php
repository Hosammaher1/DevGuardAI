<?php

namespace App\Domain\GitHub;

final class GitHubWebhookVerifier
{
    public function verifySignature(string $payload, ?string $signatureHeader, ?string $secret = null): bool
    {
        $secret ??= (string) config('services.github.webhook_secret', '');
        if ($secret === '' || $signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        if (! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        $providedSignature = substr($signatureHeader, 7);

        return hash_equals($expectedSignature, $providedSignature);
    }
}
