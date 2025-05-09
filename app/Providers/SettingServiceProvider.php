<?php

namespace App\Providers;

use App\Repositories\DefaultSettingRepository;
use App\Repositories\DomainSettingRepository;
use App\Repositories\UserSettingRepository;
use App\Services\SettingService;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DefaultSettingRepository::class, function ($app) {
            return new DefaultSettingRepository();
        });

        $this->app->bind(DomainSettingRepository::class, function ($app) {
            return new DomainSettingRepository();
        });

        $this->app->bind(UserSettingRepository::class, function ($app) {
            return new UserSettingRepository();
        });

        $this->app->singleton('setting', function ($app) {
            return new SettingService(
                $app->make(DefaultSettingRepository::class),
                $app->make(DomainSettingRepository::class),
                $app->make(UserSettingRepository::class)
            );
        });

        $this->app->singleton(SettingService::class, function ($app) {
            return $app->make('setting');
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}