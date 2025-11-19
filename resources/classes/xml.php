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

}
