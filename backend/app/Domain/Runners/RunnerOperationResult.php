<?php

namespace App\Domain\Runners;

use InvalidArgumentException;

final readonly class RunnerOperationResult
{
    public function __construct(
        public string $requestId,
        public RunnerOperationOutcome $outcome,
    ) {
        if ($requestId === '' || strlen($requestId) > 128 || preg_match('/[\x00-\x1F\x7F]/', $requestId) === 1) {
            throw new InvalidArgumentException('runner_result_request_id_invalid');
        }
    }
}
