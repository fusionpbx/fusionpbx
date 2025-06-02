<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
  public function rules()
    {
        return [
            'domain_uuid' => 'required|uuid',
            'device_uuid' => 'nullable|uuid',
            'device_mac_address' => 'required|string|max:17',
            'device_label' => 'nullable|string|max:255',
            'device_user_uuid' => 'nullable|uuid',
            'device_username' => 'nullable|string|max:255',
            'device_password' => 'nullable|string|max:255',
            'device_vendor' => 'nullable|string|max:255',
            'device_location' => 'nullable|string|max:255',
            'device_uuid_alternate' => 'nullable|uuid',
            'device_model' => 'nullable|string|max:255',
            'device_firmware_version' => 'nullable|string|max:255',
            'device_enabled' => 'boolean',
            'device_template' => 'nullable|string|max:255',
            'device_profile_uuid' => 'nullable|uuid',
            'device_description' => 'nullable|string',

            'device_lines' => 'nullable|array',
            'device_lines.*.device_line_uuid' => 'nullable|uuid',
            'device_lines.*.line_number' => 'required|integer|min:1',
            'device_lines.*.server_address' => 'required|string|max:255',
            'device_lines.*.outbound_proxy_primary' => 'nullable|string|max:255',
            'device_lines.*.outbound_proxy_secondary' => 'nullable|string|max:255',
            'device_lines.*.server_address_primary' => 'nullable|string|max:255',
            'device_lines.*.server_address_secondary' => 'nullable|string|max:255',
            'device_lines.*.label' => 'nullable|string|max:255',
            'device_lines.*.display_name' => 'nullable|string|max:255',
            'device_lines.*.user_id' => 'required|string|max:255',
            'device_lines.*.auth_id' => 'nullable|string|max:255',
            'device_lines.*.password' => 'nullable|string|max:255',
            'device_lines.*.shared_line' => 'nullable|string|max:255',
            'device_lines.*.enabled' => 'boolean',
            'device_lines.*.sip_port' => 'nullable|integer|min:1|max:65535',
            'device_lines.*.sip_transport' => 'nullable|string|in:udp,tcp,tls',
            'device_lines.*.register_expires' => 'nullable|integer|min:60',

            'device_keys' => 'nullable|array',
            'device_keys.*.device_key_uuid' => 'nullable|uuid',
            'device_keys.*.device_key_category' => 'required|string|max:255',
            'device_keys.*.device_key_vendor' => 'nullable|string|max:255',
            'device_keys.*.device_key_id' => 'nullable|string|max:255',
            'device_keys.*.device_key_type' => 'required|string|max:255',
            'device_keys.*.device_key_subtype' => 'nullable|string|max:255',
            'device_keys.*.device_key_line' => 'nullable|string|max:255',
            'device_keys.*.device_key_value' => 'required|string|max:255',
            'device_keys.*.device_key_extension' => 'nullable|string|max:255',
            'device_keys.*.device_key_label' => 'nullable|string|max:255',
            'device_keys.*.device_key_icon' => 'nullable|string|max:255',

            'device_settings' => 'nullable|array',
            'device_settings.*.device_setting_uuid' => 'nullable|uuid',
            'device_settings.*.device_setting_category' => 'nullable|string|max:255',
            'device_settings.*.device_setting_subcategory' => 'required|string|max:255',
            'device_settings.*.device_setting_name' => 'nullable|string|max:255',
            'device_settings.*.device_setting_value' => 'nullable|string',
            'device_settings.*.device_setting_enabled' => 'boolean',
            'device_settings.*.device_setting_description' => 'nullable|string',

            'device_lines_delete' => 'nullable|array',
            'device_lines_delete.*' => 'uuid',
            'device_keys_delete' => 'nullable|array',
            'device_keys_delete.*' => 'uuid',
            'device_settings_delete' => 'nullable|array',
            'device_settings_delete.*' => 'uuid',

        ];
    }

}
