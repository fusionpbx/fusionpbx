<?php

namespace App\Providers;

use App\Models\AccessControl;
use App\Models\AccessControlNode;
use App\Models\Domain;
use App\Models\Gateway;
use App\Models\Group;
use App\Models\Permission;
use App\Models\SipProfile;
use App\Models\SipProfileDomain;
use App\Models\SipProfileSetting;
use App\Repositories\AccessControlRepository;
use App\Repositories\DomainRepository;
use App\Repositories\GatewayRepository;
use App\Repositories\GroupRepository;
use App\Repositories\PermissionRepository;
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
            return new GroupRepository(
                $app->make(Group::class)
            );
        });

        $this->app->bind(SipProfileRepository::class, function ($app) {
            return new SipProfileRepository(
                $app->make(SipProfile::class),
                $app->make(SipProfileDomain::class),
                $app->make(SipProfileSetting::class)
            );
        });

        $this->app->bind(AccessControlRepository::class, function ($app) {
            return new AccessControlRepository(
                $app->make(AccessControl::class),
                $app->make(AccessControlNode::class)
            );
        });

        $this->app->bind(DomainRepository::class, function ($app) {
            return new DomainRepository(
                $app->make(Domain::class)
            );
        });

        $this->app->bind(GatewayRepository::class, function ($app) {
            return new GatewayRepository(
                $app->make(Gateway::class)
            );
        });

        $this->app->bind(PermissionRepository::class, function ($app) {
            return new PermissionRepository(
                $app->make(Permission::class)
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