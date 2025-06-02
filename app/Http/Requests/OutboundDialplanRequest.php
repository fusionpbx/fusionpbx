<?php

namespace App\Http\Requests;

use App\Rules\ResolvableHostname;
use Illuminate\Foundation\Http\FormRequest;

class OutboundDialplanRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"gateway" => "bail|required|string|max:255",
			"gateway_2" => "bail|required_with:gateway_3|string|max:255",
			"gateway_3" => "bail|string|max:255",
            "dialplan_expression*" => "bail|required|string",
            "prefix_number" => "bail|nullable|numeric|integer|min:1",
            "limit" => "bail|nullable|numeric|integer|min:1",
            "accountcode" => "bail|nullable|string|max:255",
            "toll_allow" => "bail|nullable|string|max:255",
            "pin_numbers_enabled" => "bail|nullable|in:true,false",
            "dialplan_order" => "bail|required|integer|min:0|max:999",
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
