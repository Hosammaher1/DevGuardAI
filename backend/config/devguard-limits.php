<?php

return [
    // Development/testing defaults only; ND04 commercial limits remain unresolved.
    'profile_scope' => 'development_only',
    'production_limits_approved' => false,
    'execution_timeout_seconds' => (int) env('DEVGUARD_DEV_EXECUTION_TIMEOUT_SECONDS', 300),
    'memory_limit_mb' => (int) env('DEVGUARD_DEV_MEMORY_LIMIT_MB', 512),
    'process_limit' => (int) env('DEVGUARD_DEV_PROCESS_LIMIT', 16),
    'max_parallel_jobs_per_workspace' => (int) env('DEVGUARD_DEV_MAX_PARALLEL_JOBS', 1),
];
