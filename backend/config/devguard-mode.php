<?php

return [
    'mode' => env('DEVGUARD_OPERATING_MODE', 'local_development'),
    'policy_version' => '2026-10-25.1',
    'environments' => ['local', 'testing'],
    'banner' => 'LOCAL DEVELOPMENT — NO REAL PAYMENTS',
    'evidence' => [
        'default_class' => 'LOCAL_UNIT',
        'allowed_classes' => [
            'LOCAL_UNIT',
            'LOCAL_INTEGRATION',
            'SYNTHETIC_CONTRACT',
        ],
    ],
    'capabilities' => [
        'commercial_payments_enabled' => false,
        'subscription_checkout_enabled' => false,
        'payment_provider_calls_enabled' => false,
        'real_financial_charges_enabled' => false,
        'commercial_subscription_enforcement_enabled' => false,
    ],
    'unavailable_capability_reasons' => [
        'commercial_payments' => 'Commercial payments are deferred until post-validation',
        'subscription_checkout' => 'Subscription checkout is deferred until post-validation',
        'payment_provider_calls' => 'Payment provider calls are deferred until post-validation',
        'real_financial_charges' => 'Real financial charges are deferred until post-validation',
        'commercial_subscription_enforcement' => 'Commercial subscription enforcement is deferred until post-validation',
    ],
];
