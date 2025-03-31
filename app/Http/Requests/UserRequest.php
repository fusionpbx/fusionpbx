<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		$isCreating = $this->isMethod("post");

		return [
			"username" => "required|string|max:255",
			"user_email" => "required|email|max:255",
			"password" => ($isCreating ? "required" : "nullable") . "|string|confirmed",
			"domain_uuid" => "required|uuid",
			"language" => "nullable",
			"timezone" => "nullable",
			"user_enabled" => "nullable",
		];
	}
}
