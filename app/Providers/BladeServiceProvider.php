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
        Blade::directive('hasRole', function ($expression) {
            return "<?php if(Auth::check() && Auth::user()->groups->contains('group_name', {$expression})): ?>";
        });

        Blade::directive('endHasRole', function () {
            return "<?php endif; ?>";
        });

        Blade::directive('can', function ($expression) {
            return "<?php if(Auth::check() && Auth::user()->groups->contains(function(\$group) use ($expression) { 
                return \$group->permissions->contains('permission_name', $expression); 
            })): ?>";
        });

        Blade::directive('endCan', function () {
            return "<?php endif; ?>";
        });

    }
}
