<?php

namespace App\Http\Controllers;

use App\Models\DomainSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DomainSettingController extends Controller
{
    // $name doesn't mean name, it is more the type of the setting
	public function get(string $category, string $subcategory, ?string $name = null){
        $domain_uuid = Session::get('domain_uuid');
        $answer = null;
        $domain_settings = DB::table(DomainSetting::getTableName())
                ->where('domain_setting_enabled', '=', 'true')
                ->where('domain_setting_category', '=', $category)
                ->where('domain_setting_subcategory', '=', $subcategory)
                ->where('domain_uuid', '=', $domain_uuid)
                ->orderBy('default_setting_order')
                ->get();

        foreach ($domain_settings as $domain_setting){
            if (($name == $domain_setting->domain_setting_name) || is_null($name)){
                switch($domain_setting->domain_setting_name){
                    case 'array':
                        $answer[] = $domain_setting->domain_setting_value;
                        break 1;
                    case 'boolean':
                        $answer = $domain_setting->domain_setting_value;
                        if (settype($answer, 'boolean') === false){
                            $answer = false;
                        }
                        break 2;
                    case 'code':
                    case 'dir':
                    case 'name':
                    case 'text':
                    case 'uuid':
                        $answer = $domain_setting->domain_setting_value;
                        if (settype($answer, 'string') == false){
                            $domain_setting->domain_setting_value;
                        }
                        break 2;
                    case 'numeric':
                        $answer = $domain_setting->domain_setting_value;
                        if (strstr($answer, '.')){
                            if (settype($answer, 'float') == false){
                                $answer = 0.0;
                            }
                        }
                        else{
                            if (settype($answer, 'integer') == false){
                                $answer = 0;
                            }
                        }
                        break 2;

                    default:
                        $answer = $domain_setting->domain_setting_value;
                        break 2;
                }
            }
        }

        return $answer;
	}
}
