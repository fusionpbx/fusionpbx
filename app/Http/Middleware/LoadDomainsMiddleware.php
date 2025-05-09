<?php

namespace App\Http\Middleware;

use App\Facades\DomainService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Models\Domain;

use App\Http\Controllers\DomainController;

class LoadDomainsMiddleware
{
	public function handle(Request $request, Closure $next)
	{

		$domainSelectControl = DomainService::selectControl();

		View::share("domainSelectControl", $domainSelectControl);

		return $next($request);
	}
}
