<?php

namespace JanakKapadia\HostingManager;

use Illuminate\Support\ServiceProvider;

class HostingManagerServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/routes.php', 'route');
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

    }
}