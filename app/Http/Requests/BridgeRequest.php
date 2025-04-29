<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class BridgeRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		$isCreating = $this->isMethod("post");
		$rule = [
			"bridge_name" => [
                    'bail',
                    'required',
                    'string',
                    'max:255',
                              ],
			"bridge_destination" => "bail|required|string|max:255",
			"bridge_enabled" => "bail|nullable|in:true,false",
			"bridge_description" => "bail|nullable|string|max:255",
		];

		if ($isCreating){
			$rule['bridge_name'][] = Rule::unique('App\Models\Bridge','bridge_name')
							->where('domain_uuid', Session::get('domain_uuid'));
		}
		return $rule;
	}
}
