<?php

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

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

if (!function_exists('getModels')) {
	function getModels(): Collection
	{
	    $models = collect(File::allFiles(app_path()))
	        ->map(function ($item) {
	            $path = $item->getRelativePathName();
	            $class = sprintf('\%s%s',
	                Container::getInstance()->getNamespace(),
	                strtr(substr($path, 0, strrpos($path, '.')), '/', '\\'));

	            return $class;
	        })
	        ->filter(function ($class) {
	            $valid = false;

	            if (class_exists($class)) {
	                $reflection = new \ReflectionClass($class);
	                $valid = $reflection->isSubclassOf(Model::class) &&
	                    !$reflection->isAbstract();
	            }

	            return $valid;
	        });

	    return $models->values();
	}
}
