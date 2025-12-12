<?php

namespace UmutcanGungormus\NamedRouteBinding;

use Illuminate\Contracts\Routing\BindingRegistrar;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Support\ServiceProvider;

class NamedRouteBindingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Replace ControllerDispatcher with our custom implementation
        $this->app->singleton(ControllerDispatcherContract::class, function ($app) {
            return new NamedControllerDispatcher($app);
        });

        // Also replace the dispatcher used by Router
        $this->app->afterResolving('router', function ($router, $app) {
            // Replace Router's ControllerDispatcher
            $this->app->bind(
                \Illuminate\Routing\ControllerDispatcher::class,
                NamedControllerDispatcher::class
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/named-route-binding.php' => config_path('named-route-binding.php'),
            ], 'config');
        }

        $this->mergeConfigFrom(
            __DIR__ . '/../config/named-route-binding.php',
            'named-route-binding'
        );
    }
}

