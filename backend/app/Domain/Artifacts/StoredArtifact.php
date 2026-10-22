<?php

namespace App\Domain\Artifacts;

final readonly class StoredArtifact
{
    public function __construct(public string $contents) {}
}
