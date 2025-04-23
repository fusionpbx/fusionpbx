<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $category, string $subcategory, ?string $name = null)
 * 
 * @see \App\Services\DefaultSettingService
 */
class DefaultSetting extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'default-setting';
    }
}