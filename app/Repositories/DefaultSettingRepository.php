<?php

namespace App\Repositories;

use App\Models\DefaultSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DefaultSettingRepository
{
    /**
     * Find default settings by category and subcategory
     *
     * @param string $category
     * @param string $subcategory
     * @return Collection
     */
    public function findByCategoryAndSubcategory(string $category, string $subcategory): Collection
    {
        return DB::table(DefaultSetting::getTableName())
            ->where('default_setting_enabled', '=', 'true')
            ->where('default_setting_category', '=', $category)
            ->where('default_setting_subcategory', '=', $subcategory)
            ->orderBy('default_setting_order')
            ->get();
    }
}