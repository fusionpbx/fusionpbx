<?php

namespace App\Http\Requests;

use App\Models\Domain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;


class DomainRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
        dd($this);
		return [
			"domain_name" => [
                                "bail",
                                "required",
//                                Rule::unique('App\Models\Domain,domain_name')->ignore($this->route('domain') ? $this->route('domain')->domain_uuid : null),
                                "string",
                                "max:253",      // DNS max lenght
                              ],
			"domain_description" => "bail|sometimes|nullable|string|max:255",
			"domain_enabled" => "nullable|in:true,false",
			"domain_parent_uuid" => "bail|nullable|uuid",
		];
	}
}
