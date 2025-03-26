<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()->getName();
        $permissions = config('permissions');

        if (!array_key_exists($routeName, $permissions)) {
            return $next($request);
        }

        $requiredPermission = $permissions[$routeName];
        $user = Auth::user();

        foreach ($user->groups as $group) {
            if ($group->permissions->contains('permission_name', $requiredPermission)) {
                return $next($request);
            }
        }

        return abort(403);
    }
}
