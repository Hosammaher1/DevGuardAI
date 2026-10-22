<?php

namespace App\Domain\Artifacts;

use App\Models\CredentialAccount;
use App\Models\CredentialLease;
use Carbon\CarbonImmutable;

final readonly class IssuedCredentialLease
{
    public function __construct(
        public CredentialAccount $credential,
        public CredentialLease $lease,
        public string $token,
        public CarbonImmutable $expiresAt,
    ) {}
}
