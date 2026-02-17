<?php

/**
 * cidr class
 * 
 * Classless Inter-Domain Routing is way to represent an abreviated subnet mask
 */
class cidr {

	/**
	 * Checks if the $ip_address is within the range of the given $cidr
	 *
	 * @param string|array $cidr_range string or array of CIDR addresses
	 * @param string $ip_address The IP address used to check
	 *
	 * @return bool return true if the IP address is in CIDR or if it is empty
	 */
	public static function find($cidr, string $ip_address): bool {

		//no cidr restriction
		if (empty($cidr)) {
			return true;
		}

		//check to see if the user's remote address is in the cidr array
		if (is_array($cidr)) {
			//cidr is an array
			foreach ($cidr as $value) {
				if (self::find($value, $ip_address)) {
					return true;
				}
			}
		} else {
			//cidr is a string
			list($subnet, $mask) = explode('/', $cidr);
			return (ip2long($ip_address) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
		}

		//value not found in cidr
		return false;
	}


}
