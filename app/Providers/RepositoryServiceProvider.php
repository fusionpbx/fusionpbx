<?php

namespace App\Providers;

use App\Models\AccessControl;
use App\Models\AccessControlNode;
use App\Models\Bridge;
use App\Models\Dialplan;
use App\Models\Domain;
use App\Models\Gateway;
use App\Models\Group;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuItemGroup;
use App\Models\Permission;
use App\Models\SipProfile;
use App\Models\SipProfileDomain;
use App\Models\SipProfileSetting;
use App\Repositories\AccessControlRepository;
use App\Repositories\BridgeRepository;
use App\Repositories\DialplanRepository;
use App\Repositories\DomainRepository;
use App\Repositories\GatewayRepository;
use App\Repositories\GroupRepository;
use App\Repositories\MenuItemRepository;
use App\Repositories\MenuRepository;
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

        $this->app->bind(MenuRepository::class, function ($app) {
            return new MenuRepository(
                $app->make(Menu::class),
                $app->make(MenuItem::class),
                $app->make(MenuItemGroup::class)
            );
        });

        $this->app->bind(MenuItemRepository::class, function ($app){
            return new MenuItemRepository(
                $app->make(MenuItem::class)
            );
        });

        $this->app->bind(BridgeRepository::class, function ($app) {
            return new BridgeRepository(
                $app->make(Bridge::class)
            );
        });

        $this->app->bind(DialplanRepository::class, function ($app) {
            return new DialplanRepository(
                $app->make(Dialplan::class),
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