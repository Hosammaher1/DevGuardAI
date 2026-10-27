<?php

namespace App\Domain\Runners;

use InvalidArgumentException;

final readonly class RunnerOperationRequest
{
    public function __construct(
        public string $workspaceId,
        public string $jobId,
        public string $capability,
        public RunnerOperation $operation,
        public string $requestId,
        public string $budgetReference,
    ) {
        foreach ([$workspaceId, $jobId, $capability, $requestId, $budgetReference] as $value) {
            if ($value === '' || strlen($value) > 128 || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
                throw new InvalidArgumentException('runner_request_scope_invalid');
            }
        }
    }
}
