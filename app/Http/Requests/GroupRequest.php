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
			"domain_uuid" => "nullable|uuid",
			"group_level" => "nullable|numeric|in:10,20,30,40,50,60,70,80,90",
			"group_protected" => "nullable|string|in:on",
			"group_description" => "nullable|string|max:255",
		];
	}
}
