<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyAuthenticationKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->extractApiKey($request);

        if (config('app.debug')) {
            Log::debug('API Authentication attempt', [
                'has_api_key' => !empty($apiKey),
                'request_path' => $request->path()
            ]);
        }

        if (empty($apiKey)) {
            return response()->json([
                'error' => 'API key required',
                'message' => 'Please provide an API key in the Authorization header'
            ], 401);
        }

        try {
            $user = User::where('api_key', $apiKey)->first();

            if (!$user) {
                return response()->json([
                    'error' => 'Invalid API key',
                    'message' => 'The provided API key is invalid'
                ], 401);
            }
            Auth::setUser($user);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('API authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Authentication failed',
                'message' => 'An error occurred during authentication'
            ], 500);
        }
    }

    /**
     * Extract API key from request headers
     */
    private function extractApiKey(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if (empty($authorization)) {
            return null;
        }

        if (str_starts_with($authorization, 'Bearer ')) {
            return trim(substr($authorization, 7));
        }

        return trim($authorization);
    }
}
