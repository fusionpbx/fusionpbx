<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BasicInformationRequest extends FormRequest
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
            'contactType' => 'required|string|in:customer,contractor,friend,lead,member,family,subscriber,supplier,provider,user,volunteer',
            'contactOrganization' => 'nullable|string|max:255',
            'contactNamePrefix' => 'nullable|string|max:50',
            'contactNameGiven' => 'nullable|string|max:255',
            'contactNameMiddle' => 'nullable|string|max:255',
            'contactNameFamily' => 'nullable|string|max:255',
            'contactNameSuffix' => 'nullable|string|max:50',
            'contactNickname' => 'nullable|string|max:255',
            'contactTitle' => 'nullable|string|max:255',
            'contactRole' => 'nullable|string|max:255',
            'contactCategory' => 'nullable|string|max:255',
            'contactTimeZone' => 'nullable|string|timezone',
            'contactNote' => 'nullable|string|max:1000',
        ];
    }
}
