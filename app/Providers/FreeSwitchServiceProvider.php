<?php

namespace App\Providers;

use App\Contracts\FreeSwitchConnectionManagerInterface;
use App\Services\FreeSwitch\FreeSwitchConnectionManager;
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
