<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PhraseDetailRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"phraseDetails" => "nullable|array",
			"phraseDetails.*.phrase_detail_function" => "nullable|string|in:play-file,pause-file,execute",
			"phraseDetails.*.phrase_detail_data" => "nullable|string|max:255",
			"phraseDetails.*.phrase_detail_order" => ["nullable","string","min:0","max:3","regex:/(?:[0-9]){1,3}/i",]
		];
	}
}
