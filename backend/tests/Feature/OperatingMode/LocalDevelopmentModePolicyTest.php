<?php

namespace Tests\Feature\OperatingMode;

use App\Domain\OperatingMode\LocalDevelopmentModePolicy;
use App\Domain\OperatingMode\OperatingMode;
use Illuminate\Support\Facades\Route;
use LogicException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class LocalDevelopmentModePolicyTest extends TestCase
{
    public function test_valid_local_and_testing_startup_returns_immutable_policy_state(): void
    {
        $policy = LocalDevelopmentModePolicy::current();

        self::assertSame(OperatingMode::LocalDevelopment, $policy->mode);
        self::assertSame('local_development', $policy->mode->value);
        self::assertSame('2026-10-25.1', $policy->policyVersion);
        self::assertContains($policy->environment, ['local', 'testing']);

        // Commercial capabilities are strictly disabled
        self::assertFalse($policy->commercialPaymentsEnabled);
        self::assertFalse($policy->subscriptionCheckoutEnabled);
        self::assertFalse($policy->paymentProviderCallsEnabled);
        self::assertFalse($policy->realFinancialChargesEnabled);
        self::assertFalse($policy->commercialSubscriptionEnforcementEnabled);

        // Required banner and machine-readable state
        self::assertSame('LOCAL DEVELOPMENT — NO REAL PAYMENTS', $policy->banner);
        self::assertSame('LOCAL_UNIT', $policy->defaultEvidenceClass);
        self::assertContains('LOCAL_UNIT', $policy->allowedEvidenceClasses);
        self::assertContains('LOCAL_INTEGRATION', $policy->allowedEvidenceClasses);
        self::assertContains('SYNTHETIC_CONTRACT', $policy->allowedEvidenceClasses);
        self::assertNotContains('COMMERCIAL_ACCEPTANCE', $policy->allowedEvidenceClasses);

        // SHA-256 fingerprint
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $policy->fingerprint);

        // Serialized state check
        $state = $policy->toArray();
        self::assertSame('local_development', $state['mode']);
        self::assertSame('2026-10-25.1', $state['policy_version']);
        self::assertFalse($state['commercial_payments_enabled']);
        self::assertFalse($state['subscription_checkout_enabled']);
        self::assertFalse($state['payment_provider_calls_enabled']);
        self::assertFalse($state['real_financial_charges_enabled']);
        self::assertFalse($state['commercial_subscription_enforcement_enabled']);
        self::assertSame('LOCAL_UNIT', $state['default_evidence_class']);
        self::assertArrayHasKey('unavailable_capability_reasons', $state);
    }

    public function test_production_and_staging_environments_are_denied(): void
    {
        $this->app['env'] = 'production';
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);

        $this->app['env'] = 'staging';
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);
    }

    public function test_production_package_discovery_runs_but_normal_artisan_boot_stays_denied(): void
    {
        $discovery = new Process([PHP_BINARY, 'artisan', 'package:discover', '--no-ansi'], base_path(), [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ]);
        $discovery->run();
        self::assertSame(0, $discovery->getExitCode(), $discovery->getOutput().$discovery->getErrorOutput());

        $runtime = new Process([PHP_BINARY, 'artisan', 'route:list', '--no-ansi'], base_path(), [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ]);
        $runtime->run();
        self::assertNotSame(0, $runtime->getExitCode());
        self::assertStringContainsString('operating_mode_environment_denied', $runtime->getOutput().$runtime->getErrorOutput());
    }

    public function test_unknown_or_missing_mode_fails_closed(): void
    {
        config(['devguard-mode.mode' => null]);
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);

        config(['devguard-mode.mode' => 'invalid_mode']);
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);

        config(['devguard-mode.policy_version' => null]);
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);

        config(['devguard-mode.policy_version' => 'unrecognized_version']);
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);
    }

    public function test_every_commercial_flag_must_remain_false(): void
    {
        $policy = LocalDevelopmentModePolicy::current();
        self::assertFalse($policy->commercialPaymentsEnabled);
        self::assertFalse($policy->subscriptionCheckoutEnabled);
        self::assertFalse($policy->paymentProviderCallsEnabled);
        self::assertFalse($policy->realFinancialChargesEnabled);
        self::assertFalse($policy->commercialSubscriptionEnforcementEnabled);

        $billing = config('devguard-billing.development');
        self::assertIsArray($billing);
        self::assertFalse($billing['real_payment_processing_enabled']);
        self::assertFalse($billing['subscription_checkout_enabled']);
        self::assertFalse($billing['payment_provider_calls_enabled']);
        self::assertFalse($billing['real_financial_charges_enabled']);

        // Attempting to enable any commercial capability in configuration must fail closed
        $commercialKeys = [
            'commercial_payments_enabled',
            'subscription_checkout_enabled',
            'payment_provider_calls_enabled',
            'real_financial_charges_enabled',
            'commercial_subscription_enforcement_enabled',
        ];

        foreach ($commercialKeys as $key) {
            config(['devguard-mode.capabilities.'.$key => true]);
            self::assertThrows(
                fn () => LocalDevelopmentModePolicy::current(),
                LogicException::class
            );
            config(['devguard-mode.capabilities.'.$key => false]);
        }
    }

    public function test_contradictory_configuration_is_denied(): void
    {
        // Contradictory billing settings
        config(['devguard-billing.development.real_payment_processing_enabled' => true]);
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);
        config(['devguard-billing.development.real_payment_processing_enabled' => false]);

        config(['devguard-billing.development.subscription_checkout_enabled' => true]);
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);
        config(['devguard-billing.development.subscription_checkout_enabled' => false]);

        // Contradictory production limits approval
        config(['devguard-limits.production_limits_approved' => true]);
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);
        config(['devguard-limits.production_limits_approved' => false]);

        // Contradictory test policy claiming production approval
        config(['devguard-credit-test-policy.production_policies_approved' => true]);
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);
        config(['devguard-credit-test-policy.production_policies_approved' => false]);
    }

    public function test_evidence_classification_is_immutable_and_restricted_to_local_classes(): void
    {
        $policy = LocalDevelopmentModePolicy::current();

        self::assertSame(['LOCAL_UNIT', 'LOCAL_INTEGRATION', 'SYNTHETIC_CONTRACT'], $policy->allowedEvidenceClasses);
        self::assertSame('LOCAL_UNIT', $policy->defaultEvidenceClass);

        // Attempting to configure an invalid or unapproved evidence class fails closed
        config(['devguard-mode.evidence.default_class' => 'COMMERCIAL_ACCEPTANCE']);
        self::assertThrows(fn () => LocalDevelopmentModePolicy::current(), LogicException::class);
    }

    public function test_absence_of_request_level_override(): void
    {
        // Simulate a request attempting to inject overrides via query parameters or headers
        $this->withHeaders([
            'X-DevGuard-Mode' => 'production',
            'X-DevGuard-Commercial' => 'true',
        ]);

        $policy = LocalDevelopmentModePolicy::current();

        self::assertSame(OperatingMode::LocalDevelopment, $policy->mode);
        self::assertFalse($policy->commercialPaymentsEnabled);
        self::assertFalse($policy->subscriptionCheckoutEnabled);
        self::assertFalse($policy->paymentProviderCallsEnabled);
        self::assertFalse($policy->realFinancialChargesEnabled);
        self::assertFalse($policy->commercialSubscriptionEnforcementEnabled);
    }

    public function test_no_commercial_mutation_routes_or_payment_services_registered(): void
    {
        $routes = Route::getRoutes()->getRoutes();

        $prohibitedPatterns = [
            'checkout',
            'payment',
            'charge',
            'refund',
            'billing/webhook',
            'subscription/upgrade',
            'subscription/cancel',
        ];

        foreach ($routes as $route) {
            $uri = strtolower($route->uri());
            $name = strtolower($route->getName() ?? '');

            foreach ($prohibitedPatterns as $pattern) {
                self::assertStringNotContainsString(
                    $pattern,
                    $uri,
                    "Route URI '{$uri}' violates commercial deferral by matching prohibited pattern '{$pattern}'."
                );
                self::assertStringNotContainsString(
                    $pattern,
                    $name,
                    "Route name '{$name}' violates commercial deferral by matching prohibited pattern '{$pattern}'."
                );
            }
        }

        // Prohibited payment container bindings
        $prohibitedBindings = ['stripe', 'paddle', 'payment.driver', 'payment.gateway'];
        foreach ($prohibitedBindings as $binding) {
            self::assertFalse(
                $this->app->bound($binding),
                "Container binding '{$binding}' must not be bound in local development mode."
            );
        }
    }
}
