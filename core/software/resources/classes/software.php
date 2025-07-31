<?php

/**
 * software class
 */
	class software {

		/**
		 * version
		 */
		public static function version() {
			return '5.5.0';
		}

		/**
		 * numeric_version
		 */
		public static function numeric_version() {
			$v = explode('.', software::version());
			$n = ($v[0] * 10000 + $v[1] * 100 + $v[2]);
			return $n;
		}

	}
