<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Closure;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */

    public function handle($request, Closure $next, ...$guards): Response
    {
        $authenticated = $request->session()->get('authenticated', false);
        \Log::debug('$authenticated: '.print_r($authenticated, true));

        if ($authenticated !== false){
            route('login');
        }
        else
            return $next($request);
    }
}
