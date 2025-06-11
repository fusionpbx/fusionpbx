<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactAddressRequest extends FormRequest
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
        return [
            'addresses' => 'nullable|array',
            'addresses.*.address_street' => 'nullable|string|max:255',
            'addresses.*.address_primary' => 'boolean',
            'addresses.*.address_extended' => 'nullable|string|max:255',
            'addresses.*.address_region' => 'nullable|string|max:100',
            'addresses.*.address_postal_code' => 'nullable|string|max:20',
            'addresses.*.address_locality' => 'nullable|string|max:100',
            'addresses.*.address_country' => 'nullable|string|max:2',
            'addresses.*.address_type' => 'nullable|string|max:50',
            'addresses.*.address_label' => 'nullable|string|max:50',
            'addresses.*.address_description' => 'nullable|string|max:255',
            'addresses.*.address_city' => 'nullable|string|max:100',
        ];
    }
}
