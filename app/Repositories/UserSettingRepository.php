<?php

namespace App\Repositories;

use App\Models\UserSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserSettingRepository
{
    public function findByCategoryAndSubcategory(string $category, string $subcategory, string $domainUuid, string $userUuid): Collection
    {
        return UserSetting::where('user_setting_enabled', '=', 'true')
                ->where('user_setting_category', '=', $category)
                ->where('user_setting_subcategory', '=', $subcategory)
                ->where('domain_uuid', '=', $domainUuid)
                ->where('user_uuid', '=', $userUuid)
                ->orderBy('user_setting_order')
                ->get();
    }
}
