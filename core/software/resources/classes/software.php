<?php

/**
 * software class
 *
 * @method string version
 */
if (!class_exists('software')) {
	class software {

		/**
		 * version
		 */
		public static function version() {
			return '4.5.12';
		}

		/**
		 * numeric_version
		 */
		public static function numeric_version() {
			$v = explode('.', $this->version());
			$n = ($v[0] * 10000 + $v[1] * 100 + $v[2]);
			return $n;
		}

	}
}

?>
