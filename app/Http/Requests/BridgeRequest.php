<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BridgeRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"bridge_name" => [
                    'bail',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('App\Models\User','username')->where(fn (Builder $query) => $query->where('domain_uuid', Session::get('domain_uuid'))),
                              ],
			"bridge_destination" => "bail|required|string|max:255",
			"bridge_enabled" => "bail|nullable|in:true,false",
			"bridge_description" => "bail|nullable|string|max:255",
		];
	}
}
