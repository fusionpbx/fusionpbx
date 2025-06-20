<?php

namespace App\Http\Requests;

use App\Rules\ValidURL;
use Illuminate\Foundation\Http\FormRequest;

class ContactUrlRequest extends FormRequest
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
            'urls' => 'nullable|array',
            'urls.*.url_label' => 'nullable|string|max:100',
            'urls.*.url_address' => ['nullable','string','url:http,https','max:255', new ValidURL()],
            'urls.*.url_description' => 'nullable|string|max:255',
            'urls.*.url_primary' => 'boolean',
        ];
    }
}
