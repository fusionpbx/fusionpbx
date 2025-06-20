<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ValidURL implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = Http::get($value);
        if(App::hasDebugModeEnabled()){
            Log::debug('['.__FILE__.':'.__LINE__.']['.__CLASS__.']['.__METHOD__.'] $response: '.print_r($response, true));
        }

        if ($response->failed())
        {
            $fail('The URL on :attribute does not work.');
        }
    }
}
