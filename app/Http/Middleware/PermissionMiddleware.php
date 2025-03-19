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
        $currentName = $request->route()->getName();       
        $permissions = config('permissions');

        if (!array_key_exists($currentName, $permissions)) {
            return $next($request);
        }
        
        $requiredPermission = $permissions[$currentName];
        $user = Auth::user();
          
        foreach ($user->groups as $group) {
            if ($group->permissions->contains('permission_name', $requiredPermission)) {
                return $next($request);
            }
        }
        
        return response(__('Access denied. You do not have permission to access this page.'), 403);
    }
}
