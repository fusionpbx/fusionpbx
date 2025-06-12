<?php

namespace App\Providers;

use App\Services\FreeSwitch\FreeSwitchModuleService;
use App\Services\FreeSwitch\FreeSwitchService;
use Illuminate\Support\ServiceProvider;

class FreeSwitchModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register() : void
    {
        $this->app->singleton(FreeSwitchModuleService::class, function ($app) {
            return new FreeSwitchModuleService(
                $app->make(FreeSwitchService::class)
            );
        });

        $this->app->singleton('FreeSwitchModule', function ($app) {
            return $app->make(FreeSwitchModuleService::class);
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
