<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BridgeRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"bridge_name" => "bail|required|string|max:255",
			"bridge_destination" => "bail|nullable|string|max:255",
			"bridge_enabled" => "bail|nullable|in:true,false",
			"bridge_description" => "bail|nullable|string|max:255",
		];
	}
}
