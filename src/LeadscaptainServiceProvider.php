<?php

declare(strict_types=1);

namespace Leadscaptain\LaravelLeadscaptain;

use Illuminate\Support\ServiceProvider;
use Leadscaptain\LaravelLeadscaptain\Domain\Repositories\LeadRepository;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Http\LeadscaptainClient;
use Leadscaptain\LaravelLeadscaptain\Infrastructure\Persistence\Repositories\EloquentLeadRepository;

final class LeadscaptainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/leadscaptain.php',
            'leadscaptain',
        );

        $this->app->singleton(
            LeadscaptainClient::class,
        );

        $this->app->bind(
            LeadRepository::class,
            EloquentLeadRepository::class,
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/leadscaptain.php' => config_path('leadscaptain.php'),
        ], 'leadscaptain-config');

        $this->loadMigrationsFrom(
            __DIR__.'/../database/migrations',
        );

        $this->app->make('config')->set(
            'logging.channels.leadscaptain',
            [
                'driver' => 'daily',
                'path' => storage_path('logs/leadscaptain.log'),
                'level' => 'info',
                'days' => 14,
            ],
        );
    }
}
