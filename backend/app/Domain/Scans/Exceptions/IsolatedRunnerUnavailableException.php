<?php

namespace App\Domain\Scans\Exceptions;

use RuntimeException;

final class IsolatedRunnerUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'isolated_runner_unavailable', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
