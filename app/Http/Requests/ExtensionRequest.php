<?php

namespace App\Http\Requests;

use App\Rules\ValidCidr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtensionRequest extends FormRequest
{

    protected ?string $extensionUuid = null;

    public function setExtensionUuid(?string $extensionUuid): void
    {
        $this->extensionUuid = $extensionUuid;
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
    public function rules(): array
    {
        $extensionUuid = $this->extensionUuid;

        return [
            'extension' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('v_extensions')->ignore($extensionUuid, 'extension_uuid')
            ],
            'number_alias' => 'nullable|numeric',
            'password' => 'nullable|string|min:6|max:100',
            'accountcode' => 'nullable|string|max:50',
            'enabled' => 'string',
            'description' => 'nullable|string|max:500',
            'effective_caller_id_name' => 'nullable|string|max:100',
            'effective_caller_id_number' => 'nullable|string|max:50',
            'outbound_caller_id_name' => 'nullable|string|max:100',
            'outbound_caller_id_number' => 'nullable|string|max:50',
            'emergency_caller_id_name' => 'nullable|string|max:100',
            'emergency_caller_id_number' => 'nullable|string|max:50',
            'directory_first_name' => 'nullable|string|max:100',
            'directory_last_name' => 'nullable|string|max:100',
            'directory_visible' => 'string|nullable',
            'directory_exten_visible' => 'string|nullable',
            'max_registrations' => 'nullable|integer|min:1|max:10',
            'limit_max' => 'nullable|integer|min:1|max:100',
            'limit_destination' => 'nullable|string|max:100',
            'user_context' => 'nullable|string|max:100',
            'range' => 'nullable|integer|min:1',
            'missed_call_app' => 'nullable|in:email,text',
            'missed_call_data' => 'nullable|string|max:500',
            'toll_allow' => 'nullable|string|max:200',
            'call_timeout' => 'nullable|integer|min:5|max:300',
            'call_group' => 'nullable|string|max:100',
            'call_screen_enabled' => 'nullable|string',
            'user_record' => 'nullable|string|max:50',
            'hold_music' => 'nullable|string|max:100',
            'auth_acl' => 'nullable|string|max:100',
            'cidr' => ['nullable', 'string', 'max:255', new ValidCidr()],
            'sip_force_contact' => 'nullable|string|max:100',
            'sip_force_expires' => 'nullable|integer|min:60|max:3600',
            'mwi_account' => 'nullable|string|max:100',
            'sip_bypass_media' => 'nullable|string|max:50',
            'absolute_codec_string' => 'nullable|string|max:200',
            'force_ping' => 'nullable|string|max:50',
            'dial_string' => 'nullable|string|max:200',
            'voicemail_password' => 'nullable|string|min:4|max:20',
            'voicemail_enabled' => 'string|nullable',
            'voicemail_mail_to' => 'nullable|email|max:200',
            'voicemail_transcription_enabled' => 'string|nullable',
            'voicemail_file' => 'nullable|string|max:50',
            'voicemail_local_after_email' => 'string|nullable',

            // TODO: 
            // 'devices' => 'nullable|array',
            // 'devices.*.device_mac_address' => 'required_with:devices|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            // 'devices.*.line_number' => 'required_with:devices|integer|min:1|max:10',
            // 'devices.*.device_template' => 'nullable|string|max:100',

        ];
    }
}
