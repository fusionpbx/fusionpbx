<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ResolvableHostname implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        // Case 1: IP4 only
        $r1 = '/^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$/';
        $c1 = preg_match($r1, $value);

        if (!$c1)
        {
            // Case 5: IP6 only
            $r5 = '/^([[:xdigit:]]{1,4}(?::[[:xdigit:]]{1,4}){7}|::|:(?::[[:xdigit:]]{1,4}){1,6}|[[:xdigit:]]{1,4}:(?::[[:xdigit:]]{1,4}){1,5}|(?:[[:xdigit:]]{1,4}:){2}(?::[[:xdigit:]]{1,4}){1,4}|(?:[[:xdigit:]]{1,4}:){3}(?::[[:xdigit:]]{1,4}){1,3}|(?:[[:xdigit:]]{1,4}:){4}(?::[[:xdigit:]]{1,4}){1,2}|(?:[[:xdigit:]]{1,4}:){5}:[[:xdigit:]]{1,4}|(?:[[:xdigit:]]{1,4}:){1,6}:)(?::\d+)?$/';
            $c5 = preg_match($r5, $value);
            if (!$c5)
            {
                // Not an IP, let's resolve
                $valid = dns_check_record($value, 'A') || dns_check_record($value, 'AAAA') dns_check_record($value, 'A6');
                if (!$valid)
                {
                    $fail('The :attribute must be a valid IPv4/v6 value or a resolvable hostname.');
                }
            }
        }
    }
}
