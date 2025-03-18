<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Services\CoolPBXUserProvider;

class CoolPBXAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Auth::provider('coolpbx', function ($app, array $config) {
            return new CoolPBXUserProvider($app['hash'], $config['model']);
        });
    }
}
