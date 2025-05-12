<?php

namespace App\Repositories;

use App\Models\DomainSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DomainSettingRepository
{
    public function findByCategoryAndSubcategory(string $category, string $subcategory, string $domainUuid): Collection
    {
        return DomainSetting::where('domain_setting_enabled', '=', 'true')
                ->where('domain_setting_category', '=', $category)
                ->where('domain_setting_subcategory', '=', $subcategory)
                ->where('domain_uuid', '=', $domainUuid)
                ->orderBy('domain_setting_order')
                ->get();
    }
}
