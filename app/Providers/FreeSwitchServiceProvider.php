<?php

namespace App\Providers;

use App\Services\FreeSwitch\FreeSwitchService;
use Illuminate\Support\ServiceProvider;

class FreeSwitchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('freeswitch', function ($app) {
            return new FreeSwitchService();
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