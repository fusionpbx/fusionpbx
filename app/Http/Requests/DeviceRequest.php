<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceRequest extends FormRequest
{
    protected ?string $device_uuid = null;

    public function setVendorUuid(?string $uuid): void
    {
        $this->device_uuid = $uuid;
    }
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
  public function rules()
    {
        return [
            'device_mac_address' => [
                'required',
                'string',
                'mac_address',
                'max:17',
                Rule::unique('v_devices', 'device_mac_address')->ignore($this->device_uuid, 'device_uuid'),
            ],
            'device_label' => 'nullable|string|max:255',
            'device_username' => 'nullable|string|max:255',
            'device_password' => 'nullable|string|max:255',
            'device_vendor' => 'nullable|string|max:255',
            'device_location' => 'nullable|string|max:255',
            'device_uuid_alternate' => 'nullable|uuid',
            'device_model' => 'nullable|string|max:255',
            'device_firmware_version' => 'nullable|string|max:255',
            'device_enabled' => 'nullable',
            'device_template' => 'nullable|string|max:255',
            'device_profile_uuid' => 'nullable|uuid',
            'device_description' => 'nullable|string',

            'deviceLines' => 'nullable|array',
            'deviceLines.*.device_line_uuid' => 'nullable|uuid',
            'deviceLines.*.line_number' => 'nullable|integer|min:1',
            'deviceLines.*.server_address' => 'nullable|string|max:255',
            'deviceLines.*.outbound_proxy_primary' => 'nullable|string|max:255',
            'deviceLines.*.outbound_proxy_secondary' => 'nullable|string|max:255',
            'deviceLines.*.server_address_primary' => 'nullable|string|max:255',
            'deviceLines.*.server_address_secondary' => 'nullable|string|max:255',
            'deviceLines.*.label' => 'nullable|string|max:255',
            'deviceLines.*.display_name' => 'nullable|string|max:255',
            'deviceLines.*.user_id' => 'nullable|string|max:255',
            'deviceLines.*.auth_id' => 'nullable|string|max:255',
            'deviceLines.*.password' => 'nullable|string|max:255',
            'deviceLines.*.shared_line' => 'nullable|string|max:255',
            'deviceLines.*.enabled' => 'nullable',
            'deviceLines.*.sip_port' => 'nullable|integer|min:1|max:65535',
            'deviceLines.*.sip_transport' => 'nullable|string|in:udp,tcp,tls',
            'deviceLines.*.register_expires' => 'nullable|integer|min:60',

            'deviceKeys' => 'nullable|array',
            'deviceKeys.*.device_key_category' => 'nullable|string|max:255',
            'deviceKeys.*.device_key_vendor' => 'nullable|string|max:255',
            'deviceKeys.*.device_key_id' => 'nullable|string|max:255',
            'deviceKeys.*.device_key_type' => 'nullable|string|max:255',
            'deviceKeys.*.device_key_subtype' => 'nullable|string|max:255',
            'deviceKeys.*.device_key_line' => 'nullable|string|max:255',
            'deviceKeys.*.device_key_value' => 'nullable|string|max:255',
            'deviceKeys.*.device_key_extension' => 'nullable|string|max:255',
            'deviceKeys.*.device_key_label' => 'nullable|string|max:255',
            'deviceKeys.*.device_key_icon' => 'nullable|string|max:255',

            'deviceSettings' => 'nullable|array',
            'deviceSettings.*.device_setting_category' => 'nullable|string|max:255',
            'deviceSettings.*.device_setting_subcategory' => 'nullable|string|max:255',
            'deviceSettings.*.device_setting_name' => 'nullable|string|max:255',
            'deviceSettings.*.device_setting_value' => 'nullable|string',
            'deviceSettings.*.device_setting_enabled' => 'nullable',
            'deviceSettings.*.device_setting_description' => 'nullable|string',
        ];
    }

}
