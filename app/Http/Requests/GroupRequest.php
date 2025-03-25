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
			"group_domain" => "required|uuid",
			"group_level" => "sometimes|decimal|in:10,20,30,40,50,60,70,80,90",
			"group_protected" => "required|string|in:true,false",
			"group_description" => "sometimes|string|max:255",
		];
	}
}
