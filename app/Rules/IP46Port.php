<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IP46Port implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
	// Case 1: FQDN only
	$r1  = '/(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{1,63}(?<!-)\.)+[a-zA-Z]{2,63}$)/';
	$c1 = preg_match($r1, $value);

	if (!$c1){
		// Case 2: FQDN with Port number
		$r2 = '/(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{1,63}(?<!-)\.)+[a-zA-Z]{2,63}(?::\d{1,5})?$)/';
		$c2 = preg_match($r2, $value);
		if ($c2){
			list ($ip2, $p2) = explode (':', $value, 2);
			$p2 = intval($p2);
			$c2 = ($p2 >= 0) && ($p2 <= 65535);
		}

		if (!$c2){
			// Case 3: IP4 only
			$r3 = '/^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}$/';
			$c3 = preg_match($r3, $value);

			if (!$c3){
				// Case 4: IP4 with Port number
				$r4  = '/^((25[0-5]|(2[0-4]|1\d|[1-9]|)\d)\.?\b){4}(?::\d{1,5})?$/';
				$c4 = preg_match($r4, $value);
				if ($c4){
					list ($ip4, $p4) = explode (':', $value, 2);
					$p4 = intval($p4);
					$c4 = ($p4 >= 0) && ($p4 <= 65535);
				}

				if (!$c4){
				        // Case 5: IP6 only
					$r5 = '/^([[:xdigit:]]{1,4}(?::[[:xdigit:]]{1,4}){7}|::|:(?::[[:xdigit:]]{1,4}){1,6}|[[:xdigit:]]{1,4}:(?::[[:xdigit:]]{1,4}){1,5}|(?:[[:xdigit:]]{1,4}:){2}(?::[[:xdigit:]]{1,4}){1,4}|(?:[[:xdigit:]]{1,4}:){3}(?::[[:xdigit:]]{1,4}){1,3}|(?:[[:xdigit:]]{1,4}:){4}(?::[[:xdigit:]]{1,4}){1,2}|(?:[[:xdigit:]]{1,4}:){5}:[[:xdigit:]]{1,4}|(?:[[:xdigit:]]{1,4}:){1,6}:)(?::\d+)?$/';
					$c5 = preg_match($r5, $value);

					if (!$c5){
						//TODO: Find a Regex for IP6 and port
						$fail('The :attribute must be a valid IPv4/v6 value with an optional port number.');
					}

				}
			}
		}
	}
    }
}
