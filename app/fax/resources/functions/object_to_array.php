<?php

/**
 * Recursively converts an object or array to a multi-dimensional array.
 *
 * @param mixed $obj The object or array to be converted. If the value is neither an object nor an array, it will be returned as is.
 *
 * @return array A multi-dimensional array representation of the input object or array.
 */
function object_to_array($obj) {
	if (!is_object($obj) && !is_array($obj)) { return $obj; }
	if (is_object($obj)) { $obj = get_object_vars($obj); }
	return array_map('object_to_array', $obj);
}

?>