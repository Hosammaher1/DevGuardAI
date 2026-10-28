<?php

namespace App\Providers;

use App\Database\PostgresConnection;
use App\Domain\OperatingMode\LocalDevelopmentModePolicy;
use Illuminate\Database\Connection;
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
        Connection::resolverFor('pgsql', function ($connection, $database, $prefix, $config) {
            return new PostgresConnection($connection, $database, $prefix, $config);
        });

        // Composer rebuilds package metadata before the production runtime is configured.
        if ($this->app->runningConsoleCommand('package:discover')) {
            return;
        }

        LocalDevelopmentModePolicy::current();
    }
}
