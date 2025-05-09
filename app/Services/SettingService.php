<?php

namespace App\Services;

use App\Repositories\DefaultSettingRepository;
use App\Repositories\DomainSettingRepository;
use App\Repositories\UserSettingRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SettingService
{
    protected $defaultSettingRepository;
    protected $domainSettingRepository;
    protected $userSettingRepository;

    public function __construct(
        DefaultSettingRepository $defaultSettingRepository,
        DomainSettingRepository $domainSettingRepository,
        UserSettingRepository $userSettingRepository
    ) {
        $this->defaultSettingRepository = $defaultSettingRepository;
        $this->domainSettingRepository = $domainSettingRepository;
        $this->userSettingRepository = $userSettingRepository;
    }


    public function getUserSetting(string $category, string $subcategory, ?string $name = null)
    {
        $domain_uuid = Session::get('domain_uuid');
        $user_uuid = Auth::id();
        $answer = null;
        
        $user_settings = $this->userSettingRepository->findByCategoryAndSubcategory(
            $category, 
            $subcategory, 
            $domain_uuid, 
            $user_uuid
        );

        foreach ($user_settings as $user_setting) {
            if (($name == $user_setting->user_setting_name) || is_null($name)) {
                $answer = $this->formatSettingValue($user_setting->user_setting_name, $user_setting->user_setting_value);
                
                if (!is_null($name)) {
                    break;
                }
            }
        }

        return $answer;
    }

    public function getDomainSetting(string $category, string $subcategory, ?string $name = null)
    {
        $domain_uuid = Session::get('domain_uuid');
        $answer = null;
        
        $domain_settings = $this->domainSettingRepository->findByCategoryAndSubcategory(
            $category, 
            $subcategory, 
            $domain_uuid
        );

        foreach ($domain_settings as $domain_setting) {
            if (($name == $domain_setting->domain_setting_name) || is_null($name)) {
                $answer = $this->formatSettingValue($domain_setting->domain_setting_name, $domain_setting->domain_setting_value);
                
                if (!is_null($name)) {
                    break;
                }
            }
        }

        return $answer;
    }
    
    public function getSetting(string $category, string $subcategory, ?string $name = null)
    {
        $setting = $this->getUserSetting($category, $subcategory, $name);
        

        if ($setting === null) {
            $setting = $this->getDomainSetting($category, $subcategory, $name);
            
            if ($setting === null) {
                $setting = $this->getDefaultSetting($category, $subcategory, $name);
            }
        }
        
        return $setting;
    }

    public function getDefaultSetting(string $category, string $subcategory, ?string $name = null)
    {
        $defaultSettings = $this->defaultSettingRepository->findByCategoryAndSubcategory($category, $subcategory);
        
        $answer = null;
        
        foreach ($defaultSettings as $defaultSetting) {
            if (($name == $defaultSetting->default_setting_name) || is_null($name)) {
                $answer = $this->formatSettingValue($defaultSetting->default_setting_name, $defaultSetting->default_setting_value);
                
                if (!is_null($name)) {
                    break;
                }
            }
        }
        
        return $answer;
    }
    
    protected function formatSettingValue($settingType, $value)
    {
        switch ($settingType) {
            case 'array':
                return [$value];
                
            case 'boolean':
                $result = $value;
                return settype($result, 'boolean') ? $result : false;
                
            case 'code':
            case 'dir':
            case 'name':
            case 'text':
            case 'uuid':
                $result = $value;
                return settype($result, 'string') ? $result : $value;
                
            case 'numeric':
                $result = $value;
                if (strstr($result, '.')) {
                    return settype($result, 'float') ? $result : 0.0;
                } else {
                    return settype($result, 'integer') ? $result : 0;
                }
                
            default:
                return $value;
        }
    }
}