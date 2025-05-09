<?php

namespace App\Providers;

use App\Repositories\DomainRepository;
use App\Services\DomainService;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('domain', function ($app) {
            return new DomainService(
                $app->make(DomainRepository::class)
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
}