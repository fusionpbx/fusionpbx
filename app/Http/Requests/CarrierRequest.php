<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class CarrierRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"carrier_name" => "bail|required|string|max:255",
			"enabled" => "bail|nullable|bool",
			"carrier_channels" => "bail|nullable|numeric",
			"priority" => "bail|nullable|numeric",
			"fax_enabled" => "bail|nullable|bool",
			"short_call_friendly" => "bail|nullable|bool",
			"cancellation_ratio" => "bail|nullable|decimal:0,2",
			"lcr_tags" => "bail|nullable|string|max:255",
		];
	}
}
