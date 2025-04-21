<?php

namespace App\Services;

use App\Repositories\DefaultSettingRepository;

class DefaultSettingService
{
    protected $repository;

    public function __construct(DefaultSettingRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get default settings by category, subcategory and optionally by name
     * 
     * @param string $category
     * @param string $subcategory
     * @param string|null $name
     * @return mixed
     */
    public function get(string $category, string $subcategory, ?string $name = null)
    {
        $defaultSettings = $this->repository->findByCategoryAndSubcategory($category, $subcategory);
        
        $answer = null;
        
        foreach ($defaultSettings as $defaultSetting) {
            if (($name == $defaultSetting->default_setting_name) || is_null($name)) {
                $answer = $this->formatSettingValue($defaultSetting);
                
                if (!is_null($name)) {
                    break;
                }
            }
        }
        
        return $answer;
    }
    
    /**
     * Format the setting value based on its type
     * 
     * @param object $defaultSetting
     * @return mixed
     */
    protected function formatSettingValue($defaultSetting)
    {
        $value = $defaultSetting->default_setting_value;
        
        switch ($defaultSetting->default_setting_name) {
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