<?php

namespace App\Http\Requests;

use App\Models\Module;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class ModuleRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"module_label" => "bail|required|string|max:255",
			"module_name" => "bail|required|string|max:255",
			"module_order" => "bail|nullable|integer|max:100",
			"module_category" => "bail|required|string|max:255",
			"module_enabled" => "bail|nullable|in:true,false",
			"module_default_enabled" => "bail|nullable|in:true,false",
			"module_description" => "bail|nullable|string|max:255",
		];
	}
}
