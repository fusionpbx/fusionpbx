<?php

namespace App\Http\Controllers;

use App\Models\DefaultSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DefaultSettingController extends Controller
{
    // $name doesn't mean name, it is more the type of the setting
	public function get(string $category, string $subcategory, ?string $name = null){
        $answer = null;
        $default_settings = DB::table(DefaultSetting::getTableName())
                ->where('default_setting_enabled', '=', 'true')
                ->where('default_setting_category', '=', $category)
                ->where('default_setting_subcategory', '=', $subcategory)
                ->orderBy('default_setting_order')
                ->get();

        foreach ($default_settings as $default_setting){
            if (($name == $default_setting->default_setting_name) || is_null($name)){
                switch($default_setting->default_setting_name){
                    case 'array':
                        $answer[] = $default_setting->default_setting_value;
                        break 1;
                    case 'boolean':
                        $answer = $default_setting->default_setting_value;
                        if (settype($answer, 'boolean') === false){
                            $answer = false;
                        }
                        break 2;
                    case 'code':
                    case 'dir':
                    case 'name':
                    case 'text':
                    case 'uuid':
                        $answer = $default_setting->default_setting_value;
                        if (settype($answer, 'string') == false){
                            $answer = $default_setting->default_setting_value;
                        }
                        break 2;
                    case 'numeric':
                        $answer = $default_setting->default_setting_value;
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
                        $answer = $default_setting->default_setting_value;
                        break 2;
                }
            }
        }

        return $answer;
	}
}
