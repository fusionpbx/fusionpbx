<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DialplanRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"domain_uuid" => "bail|nullable|uuid|exists:App\Models\Domain,domain_uuid",
			"dialplan_name" => "bail|required|string|max:255",
			"hostname" => "bail|nullable|string|max:255",
			"dialplan_context" => "bail|required|string|max:255",
			"dialplan_name" => "bail|required|string|max:255",
			"dialplan_number" => "bail|nullable|string|max:255",
			"dialplan_destination" => "bail|nullable|string|max:255",
			"dialplan_continue" => "bail|nullable|string|max:255",
			"dialplan_xml" => "bail|nullable|string",
			"dialplan_order" => "bail|integer|min:0|max:999",
			"dialplan_enabled" => "bail|nullable|string|max:255",
			"dialplan_description" => "bail|nullable|string|max:255",
			"app_uuid" => 'nullable|uuid',
		];
	}
}
