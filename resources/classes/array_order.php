<?php

class array_order {

	var $sort_fields;
	var $backwards = false;
	var $numeric = false;

	/**
	 * Sorts the provided array based on the specified fields.
	 *
	 * If no fields are provided, sorts in default order. If numeric sorting is enabled,
	 * uses the numericCompare method for comparison; otherwise, uses the stringCompare method.
	 *
	 * @param array $array The array to be sorted
	 *
	 * @return array The sorted array
	 */
	function sort() {
		$args = func_get_args();
		$array = $args[0];
		if (!$array) return [];
		$this->sort_fields = array_slice($args, 1);
		if (!$this->sort_fields) return $array();

		if ($this->numeric) {
			usort($array, [$this, 'numericCompare']);
		} else {
			usort($array, [$this, 'stringCompare']);
		}
		return $array;
	}

	/**
	 * Compares two values based on a specified set of sort fields.
	 *
	 * @param array $a The first value to compare.
	 * @param array $b The second value to compare.
	 *
	 * @return int A negative integer if the first value is less than the second, a positive integer if the first value
	 *             is greater than the second, and zero if they are equal.
	 */
	function numericCompare($a, $b) {
		foreach ($this->sort_fields as $sort_field) {
			if ($a[$sort_field] == $b[$sort_field]) {
				continue;
			}
			return ($a[$sort_field] < $b[$sort_field]) ? ($this->backwards ? 1 : -1) : ($this->backwards ? -1 : 1);
		}
		return 0;
	}

	/**
	 * Compares two strings according to the specified sort fields.
	 *
	 * @param string $a The first string to compare.
	 * @param string $b The second string to compare.
	 *
	 * @return int A negative integer if $a is less than $b, a positive integer if $a is greater than $b,
	 *             and 0 if the strings are equal according to the specified sort fields.
	 */
	function stringCompare($a, $b) {
		foreach ($this->sort_fields as $sort_field) {
			$cmp_result = strcasecmp($a[$sort_field], $b[$sort_field]);
			if ($cmp_result == 0) continue;
			return ($this->backwards ? -$cmp_result : $cmp_result);
		}
		return 0;
	}
}

//$order = new array_order();
//$registrations = $order->sort($registrations, 'domain', 'user');
