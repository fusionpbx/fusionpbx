<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CallBlockRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"call_block_direction" => "bail|required|string|in:inbound,outbound",
			'extension_uuid' => "bail|required|uuid|exists:App\Models\Extension,extension_uuid",
			"call_block_name" => "bail|required|string|max:255",
			"call_block_country_code" => "bail|nullable|string|max:3",
			"call_block_number" => "bail|nullable|string",
			"call_block_action" => "bail|nullable|string",
			"call_block_enabled" => "bail|nullable|in:true,false",
			"call_block_description" => "bail|nullable|string|max:255",
		];
	}
}
