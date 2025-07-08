<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LcrImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'upload_file'   => 'bail|required|file|mimes:csv,txt',
            'provider'  => 'bail|nullable|string',
            'clear_before'  => 'bail|nullable|boolean',
            'lcr_profile'   => 'bail|nullable|string',
            'carrier_uuid'  => 'bail|required|uuid|exists:App\Models\Carrier,carrier_uuid',
        ];
    }
}
