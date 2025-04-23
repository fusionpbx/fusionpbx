<?php

namespace App\Providers;

use App\Services\DefaultSettingService;
use App\Repositories\DefaultSettingRepository;
use Illuminate\Support\ServiceProvider;

class DefaultSettingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DefaultSettingRepository::class, function ($app) {
            return new DefaultSettingRepository();
        });
        
        $this->app->singleton('default-setting', function ($app) {
            return new DefaultSettingService(
                $app->make(DefaultSettingRepository::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['default-setting'];
    }
}