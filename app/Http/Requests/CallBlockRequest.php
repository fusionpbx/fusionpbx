<?php

namespace App\Http\Requests;

use App\Rules\E164;
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
			"extension_uuid" => "bail|sometimes|nullable|uuid|exists:App\Models\Extension,extension_uuid",
			"call_block_name" => "bail|sometimes|string|max:255",
			"call_block_country_code" => ['nullable','numeric','integer','min:1','min_digits:1','max_digits:3', new E164(config('freeswitch.CHECK_COUNTRY_CODE'), '*')],
			"call_block_number" => "bail|required_without:selected_xml_cdrs|integer|min_digits:1|max_digits:14",
			"call_block_action" => "bail|nullable|string",
			"call_block_enabled" => "bail|nullable|in:true,false",
			"call_block_description" => "bail|nullable|string|max:255",
		];
	}
}
