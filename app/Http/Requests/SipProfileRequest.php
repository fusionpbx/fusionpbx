<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SipProfileRequest extends FormRequest
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
            'sip_profile_name' => 'required|string|max:255',
            'sip_profile_hostname' => 'nullable|string|max:255',
            'sip_profile_enabled' => 'required|boolean',
            'sip_profile_description' => 'required|string',
            'domains.*.sip_profile_domain_name' => 'nullable|string|max:255',
            'domains.*.sip_profile_domain_alias' => 'required|in:true,false',
            'domains.*.sip_profile_domain_parse' => 'required|in:true,false',
            'settings.*.sip_profile_setting_name' => 'nullable|string|max:255',
            'settings.*.sip_profile_setting_value' => 'nullable|string',
            'settings.*.sip_profile_setting_enabled' => 'required|in:true,false',
            'settings.*.sip_profile_setting_description' => 'nullable|string',
        ];
    }
}
