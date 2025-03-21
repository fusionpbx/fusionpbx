<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        if (!Auth::check()) return false;

        $user = Auth::user();
        foreach ($user->groups as $group) {
            if ($group->permissions->contains('permission_name', $permission)) {
                return true;
            }
        }

        return false;
    }
}