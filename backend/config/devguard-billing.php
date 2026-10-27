<?php

return [
    // Temporary local-development guard. Billing plans and commercial behavior remain in the approved roadmap.
    'development' => [
        'applies_to_environments' => ['local', 'testing'],
        'real_payment_processing_enabled' => false,
        'subscription_checkout_enabled' => false,
        'payment_provider_calls_enabled' => false,
        'real_financial_charges_enabled' => false,
    ],
];
