<?php

return [
    'enabled' => (bool) env('DEVGUARD_ISOLATED_RUNNER_ENABLED', false),
    'trusted_project_path' => env('DEVGUARD_TRUSTED_PROJECT_PATH', base_path('../trusted-demo')),
    'ai_service_url' => env('DEVGUARD_AI_SERVICE_URL', 'http://127.0.0.1:8001'),
    'ai_internal_key' => env('DEVGUARD_INTERNAL_KEY', 'devguard-internal-secret-token'),
    'timeout_seconds' => (int) env('DEVGUARD_RUNNER_TIMEOUT_SECONDS', 60),
];
