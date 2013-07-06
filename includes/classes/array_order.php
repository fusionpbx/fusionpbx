<?php

class array_order {

	var $sort_fields;
	var $backwards = false;
	var $numeric = false;

	function sort() {
		$args = func_get_args();
		$array = $args[0];
		if (!$array) return array();
		$this->sort_fields = array_slice($args, 1);
		if (!$this->sort_fields) return $array();

		if ($this->numeric) {
			usort($array, array($this, 'numericCompare'));
		} else {
			usort($array, array($this, 'stringCompare'));
		}
		return $array;
	}

	function numericCompare($a, $b) {
		foreach($this->sort_fields as $sort_field) {
			if ($a[$sort_field] == $b[$sort_field]) {
				continue;
			}
			return ($a[$sort_field] < $b[$sort_field]) ? ($this->backwards ? 1 : -1) : ($this->backwards ? -1 : 1);
		}
		return 0;
	}

	function stringCompare($a, $b) {
		foreach($this->sort_fields as $sort_field) {
			$cmp_result = strcasecmp($a[$sort_field], $b[$sort_field]);
			if ($cmp_result == 0) continue;
			return ($this->backwards ? -$cmp_result : $cmp_result);
		}
		return 0;
	}
}
//$order = new array_order();
//$registrations = $order->sort($registrations, 'domain', 'user');

?>