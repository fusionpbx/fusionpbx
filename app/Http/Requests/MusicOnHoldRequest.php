<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MusicOnHoldRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"music_on_hold_name" => "required|string|max:255",
			"music_on_hold_rate" => "required|numeric|integer|in:8000,16000,32000,48000",
			"music_on_hold_file" => "required|file|mimes:mp3,wav|max:10240",
		];
	}
}
