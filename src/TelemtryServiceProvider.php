<?php

namespace ObvioBySage\Telemetry;

use Illuminate\Support\ServiceProvider;
use ObvioBySage\Telemetry\Telemetry;
use ObvioBySage\Telemetry\Transports\RedisTransport;

class TelemtryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('Telemetry', Telemetry::class);
        $this->app->bind('RedisTransport', RedisTransport::class);

        $this->mergeConfigFrom(
            __DIR__.'/config/telemetry.php', 'telemetry'
        );
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__.'/config/telemetry.php' => config_path('telemetry.php'),
            ],
            'obvio-telemetry'
        );
    }
}
