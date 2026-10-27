<?php

namespace Tests\Feature\Credits;

use App\Domain\Credits\DevelopmentExecutionLimits;
use LogicException;
use Tests\TestCase;

final class DevelopmentExecutionLimitsTest extends TestCase
{
    public function test_local_limits_are_configurable_and_external_effects_remain_disabled(): void
    {
        config([
            'devguard-limits.execution_timeout_seconds' => 240,
            'devguard-limits.memory_limit_mb' => 384,
            'devguard-limits.process_limit' => 12,
            'devguard-limits.max_parallel_jobs_per_workspace' => 1,
        ]);

        $limits = DevelopmentExecutionLimits::current();

        self::assertSame(240, $limits->executionTimeoutSeconds);
        self::assertSame(384, $limits->memoryLimitMb);
        self::assertSame(12, $limits->processLimit);
        self::assertSame(1, $limits->maxParallelJobsPerWorkspace);
        self::assertFalse(config('devguard-limits.production_limits_approved'));
        self::assertFalse($limits->chargingEnabled);
        self::assertFalse($limits->paidProvidersEnabled);
        self::assertFalse($limits->externalExecutionEnabled);
    }

    public function test_local_limits_reject_invalid_values_and_non_development_environment(): void
    {
        config(['devguard-limits.memory_limit_mb' => 0]);
        self::assertThrows(fn () => DevelopmentExecutionLimits::current(), LogicException::class);

        config(['devguard-limits.memory_limit_mb' => 512]);
        $this->app['env'] = 'production';
        self::assertThrows(fn () => DevelopmentExecutionLimits::current(), LogicException::class);
    }

    public function test_local_enforcer_accepts_observed_usage_at_each_configured_boundary(): void
    {
        config([
            'devguard-limits.execution_timeout_seconds' => 240,
            'devguard-limits.memory_limit_mb' => 384,
            'devguard-limits.process_limit' => 12,
            'devguard-limits.max_parallel_jobs_per_workspace' => 1,
        ]);

        $limits = DevelopmentExecutionLimits::current();
        $limits->assertWithin(240, 384, 12, 1);
        self::assertSame(240, $limits->executionTimeoutSeconds);
    }

    public function test_local_enforcer_rejects_observed_usage_above_each_configured_boundary(): void
    {
        $limits = DevelopmentExecutionLimits::current();

        foreach ([
            [301, 512, 16, 1],
            [300, 513, 16, 1],
            [300, 512, 17, 1],
            [300, 512, 16, 2],
        ] as [$seconds, $memoryMb, $processes, $parallelJobs]) {
            self::assertThrows(
                fn () => $limits->assertWithin($seconds, $memoryMb, $processes, $parallelJobs),
                LogicException::class,
            );
        }
    }
}
