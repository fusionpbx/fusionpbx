<?php
/**
 * install class
 *
 * @method null config
 */
if (!class_exists('install')) {
	class install {

		/**
		* declare the variables
		*/
		private $app_name;
		private $app_uuid;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
			$this->app_name = 'install';
			$this->app_uuid = '75507e6e-891e-11e5-af63-feff819cdc9f';
		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * create the config.conf file
		 */
		public function config() {

		}

	}
}

?>
