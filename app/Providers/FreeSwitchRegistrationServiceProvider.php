<?php

namespace App\Providers;


use App\Services\FreeSwitch\FreeSwitchRegistrationService;
use App\Services\FreeSwitch\FreeSwitchService;
use Illuminate\Support\ServiceProvider;

class FreeSwitchRegistrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register() : void
    {
        $this->app->singleton(FreeSwitchRegistrationService::class, function ($app) {
            return new FreeSwitchRegistrationService(
                $app->make(FreeSwitchService::class)
            );
        });

        $this->app->singleton('FreeSwitchRegistration', function ($app) {
            return $app->make(FreeSwitchRegistrationService::class);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() : void
    {
        //
    }
}
