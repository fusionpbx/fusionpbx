<?php

/**
 * xml class
 */
class xml {

	/**
	 * Sanitizes a string by removing any PHP-style placeholders and encoding special characters.
	 *
	 * @param string $string The input string to be sanitized.
	 *
	 * @return string The sanitized string with special characters encoded.
	 */
	static function sanitize($string) {
		$string = preg_replace('/\$\{[^}]+\}/', '', $string);
		return htmlspecialchars($string, ENT_XML1);
	}

	/**
	 * Tests the XML to determine if it is valid
	 *
	 * @param string $xml_string The XML string to be validated.
	 *
	 * @return array Return the xml_valid, and xml_errors. Valid is a boolean value, and errors return an array.
	 */
	static function valid($xml_string) {
		//set the default value to true
		$xml_valid = true;
		$xml_errors = '';

		//use the XML to check if it's valid
		if (PHP_VERSION_ID < 80000) {
			libxml_disable_entity_loader(true);
		}

		//enable internal error handling
		libxml_use_internal_errors(true);

		//load the XML object
		$xml_object = simplexml_load_string($xml_string, 'SimpleXMLElement', LIBXML_NOCDATA);
		if (!$xml_object) {
			//set the xml_errors and the xml_valid boolean value
			$xml_errors = libxml_get_errors();
			$xml_valid = false;

			//clear the libxml error buffer.
			libxml_clear_errors();
		}

		//send the result
		return ['valid' -> $xml_valid, 'errors' -> $xml_errors];
	}
}
