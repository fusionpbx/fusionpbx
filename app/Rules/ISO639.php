<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class ISO639 implements ValidationRule
{
    private ?string $index = null;
    public function __construct(string $index = 'alpha2')
    {
        $this->index = $index;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $countryData = config('iso639');
        if(App::hasDebugModeEnabled()){
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $countryData: '.print_r($countryData, true));
        }
        if (isset($countryData))
        {
            $filtered_data = array_column($countryData, $this->index);
            if(App::hasDebugModeEnabled()){
                Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] filtered_data: '.print_r($filtered_data, true));
            }
            $element = array_find($filtered_data, function (string $v) use ($value){
                return ($value == $v);
            });

            if (is_null($element))
            {
                $fail('The :attribute does not have a valid ISO639 value.');
            }
        }
    }
}
