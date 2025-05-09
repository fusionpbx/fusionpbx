<?php

namespace App\Http\Middleware;

use App\Facades\Domain as FacadesDomain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Models\Domain;

use App\Http\Controllers\DomainController;

class LoadDomainsMiddleware
{
	public function handle(Request $request, Closure $next)
	{

		$domainSelectControl = FacadesDomain::selectControl();

		View::share("domainSelectControl", $domainSelectControl);

		return $next($request);
	}
}
