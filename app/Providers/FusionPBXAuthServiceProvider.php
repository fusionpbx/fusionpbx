<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Services\FusionPBXUserProvider;

class FusionPBXAuthServiceProvider extends ServiceProvider
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
        Auth::provider('fusionpbx', function ($app, array $config) {
            return new FusionPBXUserProvider($app['hash'], $config['model']);
        });
    }
}