<?php

return [
    // Development default only. Commercial retention requires product approval.
    'report_retention_days' => (int) env('DEVGUARD_REPORT_RETENTION_DAYS', 30),
    'access_link_ttl_seconds' => (int) env('DEVGUARD_ARTIFACT_LINK_TTL_SECONDS', 300),
    'credential_lease_ttl_seconds' => (int) env('DEVGUARD_CREDENTIAL_LEASE_TTL_SECONDS', 120),
    'credential_account_ttl_seconds' => (int) env('DEVGUARD_CREDENTIAL_ACCOUNT_TTL_SECONDS', 1800),
    'trusted_worker_assertion_key' => env('DEVGUARD_TRUSTED_WORKER_ASSERTION_KEY'),
];
