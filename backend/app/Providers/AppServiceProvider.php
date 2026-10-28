<?php

namespace App\Providers;

use App\Domain\OperatingMode\LocalDevelopmentModePolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Composer rebuilds package metadata before the production runtime is configured.
        if ($this->app->runningConsoleCommand('package:discover')) {
            return;
        }

        LocalDevelopmentModePolicy::current();
    }
}
