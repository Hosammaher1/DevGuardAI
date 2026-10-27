<?php

namespace App\Domain\OperatingMode;

use LogicException;

final readonly class LocalDevelopmentModePolicy
{
    public const SUPPORTED_POLICY_VERSION = '2026-10-25.1';

    public const PERMITTED_EVIDENCE_CLASSES = [
        'LOCAL_UNIT',
        'LOCAL_INTEGRATION',
        'SYNTHETIC_CONTRACT',
    ];

    /**
     * @param  array<string>  $allowedEvidenceClasses
     * @param  array<string, string>  $unavailableCapabilityReasons
     */
    private function __construct(
        public OperatingMode $mode,
        public string $policyVersion,
        public string $environment,
        public bool $commercialPaymentsEnabled,
        public bool $subscriptionCheckoutEnabled,
        public bool $paymentProviderCallsEnabled,
        public bool $realFinancialChargesEnabled,
        public bool $commercialSubscriptionEnforcementEnabled,
        public string $defaultEvidenceClass,
        public array $allowedEvidenceClasses,
        public array $unavailableCapabilityReasons,
        public string $banner,
        public string $fingerprint,
    ) {}

    public static function current(): self
    {
        $environment = app()->environment();
        $allowedEnvironments = config('devguard-mode.environments');

        if (! is_array($allowedEnvironments)
            || ! in_array($environment, $allowedEnvironments, true)
            || ! app()->environment(['local', 'testing'])) {
            throw new LogicException('operating_mode_environment_denied');
        }

        $modeValue = config('devguard-mode.mode');
        if (! is_string($modeValue)) {
            throw new LogicException('operating_mode_missing');
        }

        $mode = OperatingMode::tryFrom($modeValue);
        if ($mode !== OperatingMode::LocalDevelopment) {
            throw new LogicException('operating_mode_unknown');
        }

        $policyVersion = config('devguard-mode.policy_version');
        if (! is_string($policyVersion) || $policyVersion !== self::SUPPORTED_POLICY_VERSION) {
            throw new LogicException('operating_mode_unrecognized_version');
        }

        // Validate commercial capability guards in devguard-mode
        $capabilities = config('devguard-mode.capabilities');
        if (! is_array($capabilities)) {
            throw new LogicException('operating_mode_contradictory_configuration');
        }

        $commercialKeys = [
            'commercial_payments_enabled',
            'subscription_checkout_enabled',
            'payment_provider_calls_enabled',
            'real_financial_charges_enabled',
            'commercial_subscription_enforcement_enabled',
        ];

        foreach ($commercialKeys as $key) {
            if (! array_key_exists($key, $capabilities) || $capabilities[$key] !== false) {
                throw new LogicException('operating_mode_contradictory_configuration');
            }
        }

        // Validate billing configuration guards
        $billing = config('devguard-billing.development');
        if (! is_array($billing)
            || ($billing['real_payment_processing_enabled'] ?? null) !== false
            || ($billing['subscription_checkout_enabled'] ?? null) !== false
            || ($billing['payment_provider_calls_enabled'] ?? null) !== false
            || ($billing['real_financial_charges_enabled'] ?? null) !== false) {
            throw new LogicException('operating_mode_contradictory_configuration');
        }

        // Validate limits and credit policy: production approval must be false
        if (config('devguard-limits.production_limits_approved') !== false
            || config('devguard-credit-test-policy.production_policies_approved') !== false) {
            throw new LogicException('operating_mode_contradictory_configuration');
        }

        // Validate evidence classes
        $defaultEvidenceClass = config('devguard-mode.evidence.default_class');
        $allowedEvidenceClasses = config('devguard-mode.evidence.allowed_classes');

        if (! is_string($defaultEvidenceClass)
            || ! in_array($defaultEvidenceClass, self::PERMITTED_EVIDENCE_CLASSES, true)
            || ! is_array($allowedEvidenceClasses)
            || ! in_array($defaultEvidenceClass, $allowedEvidenceClasses, true)) {
            throw new LogicException('operating_mode_invalid_evidence_class');
        }

        foreach ($allowedEvidenceClasses as $class) {
            if (! in_array($class, self::PERMITTED_EVIDENCE_CLASSES, true)) {
                throw new LogicException('operating_mode_invalid_evidence_class');
            }
        }

        $banner = config('devguard-mode.banner', 'LOCAL DEVELOPMENT — NO REAL PAYMENTS');
        $unavailableReasons = config('devguard-mode.unavailable_capability_reasons', []);

        // Compute authoritative fingerprint across canonical security-relevant fields
        $securityFields = [
            'commercial_payments_enabled' => false,
            'commercial_subscription_enforcement_enabled' => false,
            'default_evidence_class' => $defaultEvidenceClass,
            'environment' => $environment,
            'mode' => $mode->value,
            'payment_provider_calls_enabled' => false,
            'policy_version' => $policyVersion,
            'real_financial_charges_enabled' => false,
            'subscription_checkout_enabled' => false,
        ];
        ksort($securityFields);
        $fingerprint = hash('sha256', json_encode($securityFields, JSON_THROW_ON_ERROR));

        return new self(
            $mode,
            $policyVersion,
            $environment,
            false,
            false,
            false,
            false,
            false,
            $defaultEvidenceClass,
            $allowedEvidenceClasses,
            $unavailableReasons,
            $banner,
            $fingerprint,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode->value,
            'policy_version' => $this->policyVersion,
            'environment' => $this->environment,
            'commercial_payments_enabled' => $this->commercialPaymentsEnabled,
            'subscription_checkout_enabled' => $this->subscriptionCheckoutEnabled,
            'payment_provider_calls_enabled' => $this->paymentProviderCallsEnabled,
            'real_financial_charges_enabled' => $this->realFinancialChargesEnabled,
            'commercial_subscription_enforcement_enabled' => $this->commercialSubscriptionEnforcementEnabled,
            'default_evidence_class' => $this->defaultEvidenceClass,
            'allowed_evidence_classes' => $this->allowedEvidenceClasses,
            'unavailable_capability_reasons' => $this->unavailableCapabilityReasons,
            'banner' => $this->banner,
            'fingerprint' => $this->fingerprint,
        ];
    }
}
