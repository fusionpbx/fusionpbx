<?php

	/**
	 * xml class
	 */
	class xml {

		/**
		 * Escapes xml special characters to html entities and sanitze switch special chars.
		 * @param  mixed $string
		 * @return void
		 */
		static function sanitize($string) {
			$string = preg_replace('/\$\{[^}]+\}/', '', $string);
			return htmlspecialchars($string, ENT_XML1);
		}

	}
