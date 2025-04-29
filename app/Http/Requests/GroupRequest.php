<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GroupRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"group_name" => "required|string|max:255",
            "domain_uuid" => "sometimes|uuid|exists:App\Models\Domain,domain_uuid",
			"group_level" => "nullable|numeric|integer|min:0",
			"group_protected" => "nullable|string|in:on,true,false",
			"group_description" => "nullable|string|max:255",
		];
	}
}
