<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GatewayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'gateway' => 'required|max:255',
            'username' => 'required_if:register,true|max:255',
            'password' => 'required_if:register,true|max:255',
            'proxy' => 'required|max:255',
            'expire_seconds' => 'required|integer|min:1|max:65535',
            'register' => 'required|in:true,false',
            'retry_seconds' => 'required|integer|min:1|max:65535',
            'context' => 'required|max:255',
            'profile' => 'required|exists:v_sip_profiles,sip_profile_name',
            'enabled' => 'required|in:true,false',
            'distinct_to' => 'nullable|in:true,false',
            'auth_username' => 'nullable|max:255',
            'extension' => 'nullable|max:255',
            'register_transport' => 'nullable|in:udp,tcp,tls',
            'contact_params' => 'nullable|max:255',
            'register_proxy' => 'nullable|max:255',
            'outbound_proxy' => 'nullable|max:255',
            'caller_id_in_from' => 'nullable|in:true,false',
            'supress_cng' => 'nullable|in:true,false',
            'sip_cid_type' => 'nullable',
            'codec_prefs' => 'nullable|max:255',
            'extension_in_|contact' => 'nullable|in:true,false',
            'ping' => 'nullable|integer|min:1|max:65535',
            'ping_min' => 'nullable|integer|min:1|max:65535',
            'ping_max' => 'nullable|integer|min:1|max:65535',
            'contact_in_ping' => 'nullable|in:true,false',
            'channels' => 'nullable|integer|min:0|max:65535',
            'hostname' => 'nullable|max:255',
            'domain_uuid' => 'nullable|uuid',
            'description' => 'nullable|max:255',
            'gateway_uuid' => 'sometimes|uuid'
        ];
    }
}
