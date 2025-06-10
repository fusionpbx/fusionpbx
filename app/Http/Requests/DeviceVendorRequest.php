<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceVendorRequest extends FormRequest
{
    protected ?string $vendorUuid = null;

    public function setVendorUuid(?string $vendorUuid): void
    {
        $this->vendorUuid = $vendorUuid;
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
        $rules = [
            'vendorName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('v_device_vendors', 'name')->ignore($this->vendorUuid, 'device_vendor_uuid'),
            ],
            'vendorEnabled' => 'boolean',
            'vendorDescription' => 'nullable|string|max:1000',

            'functions' => 'nullable|array',
            'functions.*.type' => 'required|string|max:100',
            'functions.*.subtype' => 'nullable|string|max:100',
            'functions.*.value' => 'required|string|max:500',
            'functions.*.enabled' => 'boolean',
            'functions.*.description' => 'nullable|string|max:500',
            'functions.*.selected_groups' => 'nullable|array',
            'functions.*.selected_groups.*' => [
                'string',
                'exists:groups,group_uuid'
            ],

            'tempFunction.type' => 'nullable|string|max:100',
            'tempFunction.subtype' => 'nullable|string|max:100',
            'tempFunction.value' => 'nullable|string|max:500',
            'tempFunction.enabled' => 'boolean',
            'tempFunction.description' => 'nullable|string|max:500',
            'tempFunction.groups' => 'nullable|array',
            'tempFunction.groups.*' => [
                'string',
                'exists:groups,group_uuid'
            ],
        ];

        return $rules;
    }
}
