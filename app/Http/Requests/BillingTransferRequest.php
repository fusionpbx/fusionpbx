<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillingTransferRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'billing_uuid_to' => 'bail|required|uuid|exists:App\Models\Billing,billing_uuid',
			'transfer' => 'bail|required|numeric|min:1',
		];
	}
}
