<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class StreamRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		$isCreating = $this->isMethod("post");
		$rule = [
			"stream_name" => [
                    'bail',
                    'required',
                    'string',
                    'max:255',
                              ],
			"stream_location" => "bail|required|string|max:255",
			"stream_enabled" => "bail|nullable|in:true,false",
			"stream_description" => "bail|nullable|string|max:255",
		];

		if ($isCreating){
			$rule['stream_name'][] = Rule::unique('App\Models\Stream','stream_name')
							->where('domain_uuid', Session::get('domain_uuid'));
		}
		return $rule;
	}
}
