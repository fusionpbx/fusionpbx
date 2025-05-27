<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyAuthenticationKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
	$apiKey = $request->input('api_key') ?? '';
	if(App::hasDebugModeEnabled()){
    	    Log::debug('[' . __FILE__ . ':' . __LINE__ . '][' . __CLASS__ . '][' . __METHOD__ . '] $request: ' . print_r($request->toArray(), true));
	}
	$user = User::where('api_key', $apiKey)->first();
	if ($user)
	{
		Auth::login($user);
	}
	else
	{
	return response()->json([
            'data' => 'No Auth!'
        ], 403);
	}

        return $next($request);
    }
}
