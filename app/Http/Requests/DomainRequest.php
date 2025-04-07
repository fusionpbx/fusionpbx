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
        //dd($this);
		$r =  [
			"domain_name" => [
                                "bail",
                                "required",
                                "string",
                                "min:2",
                                "max:253",      // DNS max lenght
                                "regex:/(?:[a-z0-9])((?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9][a-z0-9-]{0,61}[a-z0-9]/i",
                              ],
			"domain_description" => "bail|sometimes|nullable|string|max:255",
			"domain_enabled" => "nullable|in:true,false",
			"domain_parent_uuid" => "bail|nullable|uuid",
		];
        if ($this->isMethod('post')){
            if(App::hasDebugModeEnabled()){
                Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] PUT');
            }
            $r['domain_name'][] = Rule::unique('App\Models\Domain','domain_name');
        }
        return $r;
	}
}
