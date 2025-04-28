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
            'sip_profile_name' => 'bail|required|string|alpha_dash:ascii|max:255',
            'sip_profile_hostname' => 'bail|nullable|string|max:255|alpha_dash:ascii',
            'sip_profile_enabled' => 'bail|required|boolean',
            'sip_profile_description' => 'bail|required|string',
            'domains.*.sip_profile_domain_name' => 'sometimes|bail|nullable|string|alpha_dash:ascii|max:255',
            'domains.*.sip_profile_domain_alias' => 'sometimes|bail|required|in:true,false',
            'domains.*.sip_profile_domain_parse' => 'sometimes|bail|required|in:true,false',
            'settings.*.sip_profile_setting_name' => 'sometimes|bail|nullable|string|alpha_dash:ascii|max:255',
            'settings.*.sip_profile_setting_value' => 'sometimes|bail|nullable|alpha_dash:ascii|string',
            'settings.*.sip_profile_setting_enabled' => 'sometimes|bail|required|in:true,false',
            'settings.*.sip_profile_setting_description' => 'sometimes|bail|nullable|string',
        ];
    }
}
