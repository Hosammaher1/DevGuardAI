<?php

namespace App\Domain\Artifacts;

use App\Models\ArtifactAccessLink;
use Carbon\CarbonImmutable;

final readonly class IssuedArtifactLink
{
    public function __construct(
        public ArtifactAccessLink $link,
        public string $token,
        public CarbonImmutable $expiresAt,
    ) {}
}
