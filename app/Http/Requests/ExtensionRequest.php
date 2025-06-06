<?php

namespace App\Http\Requests;

use App\Facades\DefaultSetting;
use App\Models\Extension;
use App\Rules\UniqueFSDestination;
use App\Rules\ValidCidr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ExtensionRequest extends FormRequest
{

    protected ?string $extensionUuid = null;
    protected ?Extension $extension = null;

    public function setExtensionUuid(?string $extensionUuid): void
    {
        $this->extensionUuid = $extensionUuid;
    }

    public function setExtension(?Extension $extension): void
    {
        $this->extension = $extension;
	if (isset($extension))
	{
		$this->setExtensionUuid($extension->extension_uuid);
	}
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
        $reqLength = DefaultSetting::get('users', 'password_length', 'numeric') ?? 0;
        $reqNumber = DefaultSetting::get('users', 'password_number', 'boolean') ?? false;
        $reqLowcase = DefaultSetting::get('users', 'password_lowercase', 'boolean') ?? false;
        $reqUpcase = DefaultSetting::get('users', 'password_uppercase', 'boolean') ?? false;
        $reqSpecial = DefaultSetting::get('users', 'password_special', 'boolean') ?? false;

        $rules = [
            'extension' => [
                'nullable',
                'string',
                'max:50',
            ],
            'number_alias' => ['nullable','numeric',],
            'password' => ['nullable','string'],
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
            'range' => 'sometimes|nullable|integer|min:1',
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

        if ($reqLength > 0)
        {
            //$rule["password"][] = "min:".$reqLength;
            $rule["password"][] = "Password::min($reqLength)";
        }
        else
        {
            $rule["password"][] = "Password::min(1)";
        }

        if ($reqNumber)
        {
            //$rule["password"][] = 'regex:/(?=.*[\d])/';
            $rule["password"][] = "Password::numbers()";
        }

        if ($reqLowcase)
        {
            $rule["password"][] = 'regex:/(?=.*[a-z])/';
        }

        if ($reqUpcase)
        {
            $rule["password"][] = 'regex:/(?=.*[A-Z])/';
        }

        if ($reqSpecial)
        {
            //$rule["password"][] = 'regex:/(?=.*[\W])/';
            $rule["password"][] = "Password::symbols()";
        }

        if ($extensionUuid)
        {
		// TODO: fix UniqueFSDestination to accept ->ignore()
            $rules['extension'][] = Rule::unique('App\Models\Extension','extension')->ignore($this->extension, $this->extension->getKeyName());
            $rules['number_alias'][] = Rule::unique('App\Models\Extension','number_alias')->ignore($this->extension, $this->extension->getKeyName());
        }
        else
        {
            $rules['extension'][] = new UniqueFSDestination();
            $rules['number_alias'][] = new UniqueFSDestination();
        }


        return $rules;
    }
}
