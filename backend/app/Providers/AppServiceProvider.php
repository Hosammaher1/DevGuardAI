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
        LocalDevelopmentModePolicy::current();
    }
}
