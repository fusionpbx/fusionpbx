<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillingRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'parent_billing_uuid' => 'bail|nullable|uuid|exists:App\Models\Billing,billing_uuid',
			'contact_uuid_from' => 'bail|nullable|uuid|exists:App\Models\Contact,contact_uuid',
			'contact_uuid_to' => 'bail|nullable|uuid|exists:App\Models\Contact,contact_uuid',
			'type' => 'bail|nullable|in:domain,authcode',
			'type_value' => 'bail|nullable|string',
			'billing_cycle' => 'bail|nullable|string|min:1|max:28',
			'credit_type' => 'bail|nullable|in:prepaid,postpaid',
			'credit' => 'bail|nullable|numeric',
			"force_postpaid_full_payment" => 'bail|nullable|in:true,false',
			'pay_days' => 'bail|nullable|numeric|min:0',
			'balance' => 'bail|nullable|numeric',
			'auto_topup_charge' => 'bail|nullable|integer|min:0',
			'auto_topup_minimum_balance' => 'bail|nullable|integer|min:0',
			'lcr_profile' => 'bail|nullable|string',
			'max_rate' => 'bail|nullable|numeric|min:0',
			'referred_by_uuid' => 'bail|nullable|uuid|exists:App\Models\Contact,contact_uuid',
			'referred_depth' => 'bail|nullable|numeric|min:0',
			'referred_percentage' => 'bail|nullable|numeric|min:0|max:100',
			'billing_notes' => 'bail|nullable|string|max:255',
			'whmcs_user_id' => 'bail|nullable|string',
            'currency' => [
                'bail',
                'nullable',
                'string',
                Rule::in(array_merge(config('currencies'), ['%']))
            ],
		];
	}
}
