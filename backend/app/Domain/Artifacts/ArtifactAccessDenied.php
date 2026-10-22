<?php

namespace App\Domain\Artifacts;

use DomainException;

final class ArtifactAccessDenied extends DomainException
{
    public function __construct()
    {
        parent::__construct('artifact_access_denied');
    }
}
