<?php

namespace App\Http\Requests;

use App\Rules\ISO639;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class PhraseRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"domain_uuid" => "bail|nullable|uuid|exists:App\Models\Domain,domain_uuid",
			"phrase_name" => "bail|required|string|max:255",
			"phrase_language" => ["bail","required","string","max:2", new ISO639('alpha2')],
			"phrase_enabled" => "bail|nullable|bool",
			"phrase_description" => "bail|nullable|string|max:255",
		];
	}

	protected function prepareForValidation(): void
    {
        $this->merge([
            'phrase_language' => strtolower($this->phrase_language),
        ]);
    }
}
