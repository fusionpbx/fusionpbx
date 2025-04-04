<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MenuRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"menu_name" => "required|string|max:255",
			"menu_language" => ['required','string','min:5','max:16','regex:/[a-z]{2,3}\-\w+/i'], //TODO: Find a better regex, maybe a Request class to verify
			"menu_description" => "required|string|max:255",
		];
	}
}
