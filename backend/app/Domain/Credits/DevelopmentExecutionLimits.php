<?php

namespace App\Domain\Credits;

use LogicException;

final readonly class DevelopmentExecutionLimits
{
    private function __construct(
        public int $executionTimeoutSeconds,
        public int $memoryLimitMb,
        public int $processLimit,
        public int $maxParallelJobsPerWorkspace,
        public bool $chargingEnabled,
        public bool $paidProvidersEnabled,
        public bool $externalExecutionEnabled,
    ) {}

    public static function current(): self
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('development_limits_environment_denied');
        }

        $values = [
            'execution_timeout_seconds' => [1, 1800],
            'memory_limit_mb' => [64, 1024],
            'process_limit' => [1, 32],
            'max_parallel_jobs_per_workspace' => [1, 2],
        ];
        $resolved = [];
        foreach ($values as $key => [$minimum, $maximum]) {
            $value = config('devguard-limits.'.$key);
            if (! is_int($value) || $value < $minimum || $value > $maximum) {
                throw new LogicException('development_limits_configuration_invalid');
            }
            $resolved[$key] = $value;
        }

        return new self(
            $resolved['execution_timeout_seconds'],
            $resolved['memory_limit_mb'],
            $resolved['process_limit'],
            $resolved['max_parallel_jobs_per_workspace'],
            false,
            false,
            false,
        );
    }

    public function assertWithin(int $executionSeconds, int $memoryMb, int $processCount, int $parallelJobs): void
    {
        if (! app()->environment(['local', 'testing'])
            || config('devguard-limits.production_limits_approved') !== false
            || $executionSeconds < 0
            || $memoryMb < 0
            || $processCount < 0
            || $parallelJobs < 0) {
            throw new LogicException('development_limits_observation_invalid');
        }

        if ($executionSeconds > $this->executionTimeoutSeconds
            || $memoryMb > $this->memoryLimitMb
            || $processCount > $this->processLimit
            || $parallelJobs > $this->maxParallelJobsPerWorkspace) {
            throw new LogicException('development_execution_limit_exceeded');
        }
    }
}
