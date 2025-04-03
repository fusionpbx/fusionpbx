<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MenuItemRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			"menu_uuid" => "required|uuid",
			"menu_item_parent_uuid" => "nullable|uuid",
			"menu_item_title" => "required|string|max:255",
			"menu_item_link" => "required|string|max:255",
			"menu_item_category" => "required|string|max:255",
			"menu_item_icon" => "string|max:255",
			"menu_item_protected" => "nullable",
			"menu_item_description" => "required|string|max:255",
		];
	}
}
