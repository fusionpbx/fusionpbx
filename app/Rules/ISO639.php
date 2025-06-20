<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

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
        $data = config('iso639');
        $filtered_data = array_column($data, $this->index);
        $element = array_find($filtered_data, function (string $v) use ($value){
            return ($value == $v);
        })

        if (is_null($element))
        {
            $fail('The :attribute does not have a valid ISO639 value.');
        }
    }
}
