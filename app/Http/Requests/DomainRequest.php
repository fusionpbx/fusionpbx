<?php

namespace App\Http\Requests;

use App\Models\Domain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DomainRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"domain_name" => [
                                "bail",
                                "required",
                                Rule::unique('domains')->ignore($this->route('domain') ? $this->route('domain')->domain_uuid : null),
                                "unique:App\Models\Domain,domain_name",
                                "string",
                                "max:255",
                              ],
			"domain_description" => "bail|sometimes|nullable|string|max:255",
			"domain_enabled" => "nullable|in:true,false",
			"domain_parent_uuid" => "bail|nullable|uuid",
		];
	}
}
