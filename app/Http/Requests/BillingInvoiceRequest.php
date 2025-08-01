<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillingInvoiceRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'billing_invoice_uuid' => 'bail|nullable|uuid|exists:App\Models\BillingInvoice,billing_invoice_uuid',
			'settled' => 'bail|nullable|numeric|in:0,1,-1',
		];
	}
}
