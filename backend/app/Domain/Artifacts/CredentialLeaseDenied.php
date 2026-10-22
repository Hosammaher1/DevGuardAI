<?php

namespace App\Domain\Artifacts;

use DomainException;

final class CredentialLeaseDenied extends DomainException
{
    public function __construct()
    {
        parent::__construct('credential_lease_denied');
    }
}
