<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DomainRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"domain_name" => "required|string|max:255",
			"domain_description" => "required|string|max:255",
			"domain_enabled" => "optional|string|in:true,false",
		];
	}
}
