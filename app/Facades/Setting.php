<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed getUserSetting(string $category, string $subcategory, ?string $name = null)
 * @method static mixed getDomainSetting(string $category, string $subcategory, ?string $name = null)
 * @method static mixed getDefaultSetting(string $category, string $subcategory, ?string $name = null)
 * @method static mixed getSetting(string $category, string $subcategory, ?string $name = null)
 * 
 * @see \App\Services\SettingService
 */
class Setting extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'setting';
    }
}