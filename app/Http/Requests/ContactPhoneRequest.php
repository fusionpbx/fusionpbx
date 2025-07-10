<?php

namespace App\Http\Requests;

use App\Rules\E164;
use Illuminate\Foundation\Http\FormRequest;

class ContactPhoneRequest extends FormRequest
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
    public function rules(): array
    {
        $rules = [
            'phones' => 'nullable|array',
            'phones.*.phone_number' => 'nullable|numeric|integer|max_digits:15',
            'phones.*.phone_label' => 'nullable|string|max:50',
            'phones.*.phone_type_voice' => 'boolean',
            'phones.*.phone_type_video' => 'boolean',
            'phones.*.phone_type_text' => 'boolean',
            'phones.*.phone_type_fax' => 'boolean',
            'phones.*.phone_speed_dial' => 'nullable|numeric|integer|max:10',
            'phones.*.phone_country_code' => ['nullable','numeric','integer','min:1','min_digits:1','max_digits:3','min:1', new E164(config('freeswitch.CHECK_COUNTRY_CODE'), '*')],
            'phones.*.phone_extension' => 'nullable|numeric|integer|min:0|max:10',
            'phones.*.phone_primary' => 'boolean',
            'phones.*.phone_description' => 'nullable|string|max:255',
        ];
        return $rules;
    }
}
