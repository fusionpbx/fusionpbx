<?php

if (!class_exists('xml')) {
	class xml {

		/**
		 * Escapes xml special characters to html entities and sanitze switch special chars.
		 */
		static function sanitize($string) {
			$string = preg_replace('/\$\{[^}]+\}/', '', $string);
			return htmlspecialchars($string, ENT_XML1);
		}

	}
}

?>
