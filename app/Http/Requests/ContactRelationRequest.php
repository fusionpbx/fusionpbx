<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRelationRequest extends FormRequest
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
            'relations' => 'nullable|array',
            'relations.*.relation_label' => 'nullable|string|max:100',
            'relations.*.relation_contact_uuid' => 'nullable|uuid|exists:contacts,contact_uuid',
            'relations.*.contact_name' => 'nullable|string|max:255',
        ];
    }
}
