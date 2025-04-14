<?php

namespace App\Http\Requests;

use App\Rules\ValidCidr;
use Illuminate\Foundation\Http\FormRequest;

class AccessControlRequest extends FormRequest
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
            'accessControlName' => 'required|string|max:255',
            'accessControlDefault' => 'required|in:allow,deny',
            'accessControlDescription' => 'nullable|string|max:255',
            'nodes.*.node_type' => 'nullable|in:allow,deny',
            'nodes.*.node_cidr' => ['nullable', 'string', 'max:255', new ValidCidr],
            'nodes.*.node_description' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'accessControlName.required' => 'The access control name is required.',
            'accessControlDefault.required' => 'The access control default is required.',
            'accessControlDefault.in' => 'The access control default must be "allow" or "deny".',
            'nodes.*.node_type.required' => 'The node type is required.',
            'nodes.*.node_type.in' => 'The node type must be "allow" or "deny".',
            'nodes.*.node_cidr.required' => 'The node CIDR is required.',
            'nodes.*.node_cidr.string' => 'The node CIDR must be a string.',
            'nodes.*.node_cidr.max' => 'The node CIDR must not exceed 255 characters.',
        ];
    }
}
