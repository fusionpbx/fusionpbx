<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserSettingRepository
{
    public function findByCategoryAndSubcategory(string $category, string $subcategory, string $domainUuid, string $userUuid): Collection
    {
        return DB::table('user_settings')
                ->where('domain_setting_enabled', '=', 'true')
                ->where('domain_setting_category', '=', $category)
                ->where('domain_setting_subcategory', '=', $subcategory)
                ->where('domain_uuid', '=', $domainUuid)
                ->where('user_uuid', '=', $userUuid)
                ->orderBy('default_setting_order')
                ->get();
    }
}