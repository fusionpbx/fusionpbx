<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactSettingRequest extends FormRequest
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
            'settings' => 'nullable|array',
            'settings.*.contact_setting_category'    => 'nullable|string|max:100',
            'settings.*.contact_setting_subcategory' => 'nullable|string|max:100',
            'settings.*.contact_setting_name'        => 'nullable|string|max:100',
            'settings.*.contact_setting_value'       => 'nullable|string|max:255',
            'settings.*.contact_setting_order'       => 'nullable|integer|min:0',
            'settings.*.contact_setting_enabled'     => 'nullable|boolean',
            'settings.*.contact_setting_description' => 'nullable|string|max:255',
        ];
    }
}
