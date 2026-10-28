<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DevGuard AI Demo Invitation Access Configuration
    |--------------------------------------------------------------------------
    |
    | The demo environment allows invitation-only visitors to access a shared,
    | isolated preview workspace. The code can be verified directly against
    | a plaintext secret or a pre-computed SHA-256 hash.
    |
    */

    'invitation_code' => env('DEVGUARD_DEMO_INVITATION_CODE', 'devguard-demo-2026'),

    'invitation_code_hash' => env('DEVGUARD_DEMO_INVITATION_HASH', null),

    'workspace_name' => env('DEVGUARD_DEMO_WORKSPACE_NAME', 'DevGuard Staging Workspace'),

    'user_email' => env('DEVGUARD_DEMO_USER_EMAIL', 'staging@devguard.ai'),

    'user_name' => env('DEVGUARD_DEMO_USER_NAME', 'Staging Engineer'),

    'user_github_id' => 'staging-authorized-user',

    'throttle_attempts' => (int) env('DEVGUARD_DEMO_THROTTLE_ATTEMPTS', 5),

    'throttle_decay_minutes' => (int) env('DEVGUARD_DEMO_THROTTLE_DECAY_MINUTES', 1),
];
