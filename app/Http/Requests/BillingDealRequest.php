<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillingDealRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'label' => 'bail|required|string',
			'direction' => 'bail|required|in:outbound,inbound,local',
			'digits' => 'bail|nullable|string',
			'minutes' => 'bail|nullable|numeric|min:1',
			'rate' => 'bail|nullable|numeric|min:0',
			'billing_deal_notes' => 'bail|nullable|string|max:255',
            'currency' => [
                'bail',
                'nullable',
                'string',
                Rule::in(array_merge(config('currencies'), ['%']))
            ],
		];
	}
}
