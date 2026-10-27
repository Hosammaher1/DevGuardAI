<?php

namespace App\Domain\Runners;

use App\Domain\Operations\SelectiveContainment;

final class LocalIsolatedRunner implements RunnerProviderContract
{
    public function __construct(
        private readonly ?SelectiveContainment $containment = null,
    ) {}

    public function perform(RunnerOperationRequest $request): RunnerOperationResult
    {
        if ($this->containment !== null && ! $this->containment->isRunnersAllowed()) {
            return new RunnerOperationResult(
                requestId: $request->requestId,
                outcome: RunnerOperationOutcome::Rejected,
            );
        }

        return new RunnerOperationResult(
            requestId: $request->requestId,
            outcome: RunnerOperationOutcome::Completed,
        );
    }
}
