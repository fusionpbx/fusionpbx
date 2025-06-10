<?php

namespace App\Http\Requests;

use App\Models\Carrier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class CarrierRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(?string $carrier_uuid = null): array
	{
		$rules = [
			"carrier_name" => ["bail","required","string","max:255"],
			"enabled" => "bail|nullable|bool",
			"carrier_channels" => "bail|nullable|numeric|integer|min:1",
			"priority" => "bail|nullable|numeric|integer|min:0",
			"fax_enabled" => "bail|nullable|bool",
			"short_call_friendly" => "bail|nullable|bool",
			"cancellation_ratio" => "bail|nullable|integer|min:0|max:100",
			"lcr_tags" => "bail|nullable|string|max:255",
		];
        if(App::hasDebugModeEnabled())
        {
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] request: '.print_r(request()->toArray(), true));
            Log::notice('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] method: '.$this->getMethod());
        }

        if ($carrier_uuid || $this->route('id'))
        {
   	    $this->carrier = Carrier::find($carrier_uuid ?? $this->route('id'));
            $rule['domain_name'][] = Rule::unique('App\Models\Carrier','carrier_name')->ignore($this->carrier->carrier_uuid, $this->carrier->getKeyName());
        }
        else
        {
            $rule['domain_name'][] = Rule::unique('App\Models\Carrier','carrier_name');
        }


        return $rules;
	}
}
