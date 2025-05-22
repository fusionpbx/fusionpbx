<?php

namespace App\Http\Requests;

use App\Rules\ResolvableHostname;
use Illuminate\Foundation\Http\FormRequest;

class InboundDialplanRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"dialplan_name" => "bail|required|string|max:255",
            "destination_uuid" => "bail|nullable|uuid|exists:App\Models\Destination,destination_uuid",
            "condition_field_*" => "bail|string|max:255",
            "condition_expression_*" => "bail|nullable|string|max:255",
            "action_*" => "bail|string|max:255",
            "limit" => "bail|nullable|numeric|integer|min:1",
            "caller_id_outbound_prefix" => "bail|nullable|string|max:255",
            "dialplan_order" => "bail|integer|min:0|max:999",
            "dialplan_enabled" => "bail|nullable|in:true,false",
            "dialplan_description" => "bail|nullable|string|max:255",
		];
	}

	protected function prepareForValidation()
	{
		$this->merge([
			"dialplan_context" => $this->dialplan_context ?? "public",
		]);
	}
}
