<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Models\Menu;
use App\Models\MenuItem;

use App\Http\Controllers\MenuController;

class LoadMenuMiddleware
{
	public function handle(Request $request, Closure $next)
	{
		$MenuController = new MenuController;

		$menu = $MenuController->getMenu();

		View::share("menu", $menu);

		return $next($request);
	}
}
