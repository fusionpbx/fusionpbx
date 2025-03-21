<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Blade::directive('hasgroup', function ($expression) {
            return "<?php if(Auth::check() && Auth::user()->hasRole({$expression})): ?>";
        });

        Blade::directive('endhasgroup', function () {
            return "<?php endif; ?>";
        });

        Blade::directive('can', function ($expression) {
            return "<?php if(Auth::check() && Auth::user()->hasPermission({$expression})): ?>";
        });

        Blade::directive('endcan', function () {
            return "<?php endif; ?>";
        });
    }
}
