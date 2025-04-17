<?php

namespace App\Providers;

use App\Contracts\FreeSwitchConnectionManagerInterface;
use App\Services\FreeSwitch\FreeSwitchConnectionManager;
use App\Services\FreeSwitch\FreeSwitchService;
use Illuminate\Support\ServiceProvider;

class FreeSwitchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register() : void
    {
        $this->app->singleton(FreeSwitchConnectionManagerInterface::class, FreeSwitchConnectionManager::class);
        $this->app->singleton('freeswitch', function ($app) {
            return $app->make(FreeSwitchService::class);
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
