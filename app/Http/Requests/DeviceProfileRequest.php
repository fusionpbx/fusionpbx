<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'device_profile_name' => [
                'required',
                'string',
                'max:255',
            ],
            'device_profile_enabled' => 'nullable|in:true,false',
            'device_profile_description' => 'nullable|string',
            'domain_uuid' => 'nullable|exists:v_domains,domain_uuid',

            'profileKeys' => 'nullable|array',
            'profileKeys.*.profile_key_vendor' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_id' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_category' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_type' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_subtype' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_line' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_value' => 'nullable|string',
            'profileKeys.*.profile_key_extension' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_protected' => 'nullable|in:true,false',
            'profileKeys.*.profile_key_label' => 'nullable|string|max:255',
            'profileKeys.*.profile_key_icon' => 'nullable|string|max:255',

            'profileSettings' => 'nullable|array',
            'profileSettings.*.profile_setting_name' => 'nullable|string|max:255',
            'profileSettings.*.profile_setting_value' => 'nullable|string',
            'profileSettings.*.profile_setting_enabled' => 'nullable',
            'profileSettings.*.profile_setting_description' => 'nullable|string',
        ];
    }
}
