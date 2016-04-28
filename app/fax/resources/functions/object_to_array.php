<?php

function object_to_array($obj) {
	if (!is_object($obj) && !is_array($obj)) { return $obj; }
	if (is_object($obj)) { $obj = get_object_vars($obj); }
	return array_map('object_to_array', $obj);
}

?>