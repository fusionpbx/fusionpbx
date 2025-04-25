<?php

namespace App\Providers;

use App\Models\Group;
use App\Models\SipProfile;
use App\Models\SipProfileDomain;
use App\Models\SipProfileSetting;
use App\Repositories\GroupRepository;
use App\Repositories\SipProfileRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(GroupRepository::class, function ($app) {
            return new GroupRepository($app->make(Group::class));
        });

        $this->app->bind(SipProfileRepository::class, function ($app) {
            return new SipProfileRepository(
                new SipProfile(),
                new SipProfileDomain(),
                new SipProfileSetting()
            );
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