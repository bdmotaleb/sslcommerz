<?php

namespace Sslcommerz\Laravel;

use Illuminate\Support\ServiceProvider;
use Sslcommerz\Laravel\Contracts\PaymentGatewayInterface;
use Sslcommerz\Laravel\Services\HashValidator;
use Sslcommerz\Laravel\Services\SslcommerzService;

class SslcommerzServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        // Merge default config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sslcommerz.php',
            'sslcommerz'
        );

        // Bind the PaymentGatewayInterface to SslcommerzService (singleton)
        $this->app->singleton(PaymentGatewayInterface::class, function ($app) {
            return new SslcommerzService();
        });

        // Bind the HashValidator as a singleton
        $this->app->singleton(HashValidator::class, function ($app) {
            return new HashValidator(
                config('sslcommerz.store_password')
            );
        });
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
    }

    /**
     * Register publishable resources.
     */
    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/sslcommerz.php' => config_path('sslcommerz.php'),
        ], 'sslcommerz-config');


        // Publish routes
        $this->publishes([
            __DIR__ . '/../routes/sslcommerz.php' => base_path('routes/sslcommerz.php'),
        ], 'sslcommerz-routes');

        // Publish everything
        $this->publishes([
            __DIR__ . '/../config/sslcommerz.php'  => config_path('sslcommerz.php'),
            __DIR__ . '/../routes/sslcommerz.php'   => base_path('routes/sslcommerz.php'),
        ], 'sslcommerz');
    }

    /**
     * Register package routes.
     */
    private function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/sslcommerz.php');
    }
}
