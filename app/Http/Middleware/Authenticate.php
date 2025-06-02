<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */

    public function handle($request, Closure $next, ...$guards): Response
    {
        $authenticated = $request->session()->get('authenticated', 0);
        Log::debug('$authenticated: '.print_r($authenticated, true));

        if (!$authenticated){
            route('login');
        }

        return $next($request);
    }
}
