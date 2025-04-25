<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Models\Domain;

use App\Http\Controllers\DomainController;

class LoadDomainsMiddleware
{
	public function handle(Request $request, Closure $next)
	{
		$DomainController = app()->make(DomainController::class);

		$domainSelectControl = $DomainController->selectControl();

		View::share("domainSelectControl", $domainSelectControl);

		return $next($request);
	}
}
