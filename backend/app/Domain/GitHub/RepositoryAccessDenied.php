<?php

namespace App\Domain\GitHub;

use RuntimeException;

final class RepositoryAccessDenied extends RuntimeException
{
    public function __construct(public readonly string $reason = 'repository_access_denied')
    {
        parent::__construct($reason);
    }
}
