<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain;

use Illuminate\Support\ServiceProvider;

final class LeadscaptainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/leadscaptain.php',
            'leadscaptain',
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/leadscaptain.php' =>
                config_path('leadscaptain.php'),
        ], 'leadscaptain-config');

        $this->loadMigrationsFrom(
            __DIR__ . '/../database/migrations',
        );
    }
}