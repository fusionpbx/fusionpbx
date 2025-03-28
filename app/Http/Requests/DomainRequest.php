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
			"domain_description" => "sometimes|string|max:255",
			"domain_enabled" => "nullable",
			"domain_parent_uuid" => "nullable|uuid",
		];
	}
}
