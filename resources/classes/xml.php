<?php

/**
 * xml class
 */
class xml {

	/**
	 * Sanitizes a string by removing any PHP-style placeholders and encoding special characters.
	 *
	 * FreeSWITCH / template variables (${var} and $${var}) are intentionally removed here. When the
	 * value is allowed to keep its variable syntax, such as SIP profile settings holding
	 * $${external_rtp_ip}, use escape() instead so the variable is not destroyed.
	 *
	 * @param string $string The input string to be sanitized.
	 *
	 * @return string The sanitized string with special characters encoded.
	 */
	static function sanitize($string) {
		$string = (string) preg_replace('/\$\{[^}]+\}/', '', (string) $string);
		return self::escape($string);
	}

	/**
	 * Encodes a string for safe use inside an XML element or a quoted XML attribute value.
	 *
	 * Unlike sanitize() this leaves FreeSWITCH variable syntax ($${var}, ${var}) untouched, which is
	 * required when writing user managed values into the FreeSWITCH configuration. Quotes are always
	 * encoded, without ENT_QUOTES htmlspecialchars() leaves double quotes untouched which allows an
	 * attacker to break out of the attribute and inject additional attributes or elements. Invalid
	 * UTF-8 is substituted rather than returning an empty string that would blank the whole value.
	 *
	 * @param string $string The input string to be encoded.
	 *
	 * @return string The encoded string, safe to place between XML quotes.
	 */
	static function escape($string) {
		return htmlspecialchars((string) $string, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE);
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
