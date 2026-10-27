<?php

namespace App\Domain\Runners;

interface RunnerProviderContract
{
    public function perform(RunnerOperationRequest $request): RunnerOperationResult;
}
