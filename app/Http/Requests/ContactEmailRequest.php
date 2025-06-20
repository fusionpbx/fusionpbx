<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactEmailRequest extends FormRequest
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
            'emails' => 'nullable|array',
            'emails.*.email_address' => [
                'nullable',
                'email:rfc,dns,spoof,filter',
                'max:255',
            ],
            'emails.*.email_label' => 'nullable|string|max:100',
            'emails.*.email_primary' => 'nullable|boolean',
            'emails.*.email_description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'emails.*.email_address.email' => 'The email address must be a valid email address.',
        ];
    }
}
