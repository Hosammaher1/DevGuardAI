<?php

return [
    // Synthetic ledger outcomes for isolated tests only. This never defines the production ND03 policy.
    'test_only' => true,
    'production_policies_approved' => false,
    'settlement_scenarios' => [
        'scan' => [
            'completed' => 'consume',
            'cancelled_before_execution' => 'release',
            'cancelled_after_execution' => 'consume',
        ],
        'fix' => [
            'completed' => 'consume',
            'failed_after_execution' => 'consume',
            'cancelled_before_execution' => 'release',
            'cancelled_after_execution' => 'consume',
        ],
    ],
];
