<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Copyright (C) 2016	All Rights Reserved.

*/

/**
 * permission class
 *
 * @method string add
 * @method string delete
 * @method string exists - legacy
 * @method string has
 * @method string has_any
 * @method string has_all
 * @method string require_any
 * @method string require_all
 * @method string access_denied
 */
if (!class_exists('permissions')) {
	class permissions {

		private $use_header_method = false;
		
		/**
		 * Called when the object is created
		 */
		public function __construct() {		
			if (PHP_MAJOR_VERSION <= 5 and PHP_MINOR_VERSION < 4) {
				$this->use_header_method = true;
			}
		}

		/**
		 * Called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			if (is_array($this)) foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * Add the permission
		 * @var string $permission
		 */
		public function add($permission, $type) {
			//add the permission if it is not in array
			if (!$this->exists($permission)) {
				$_SESSION["permissions"][$permission] = $type;
			}
		}

		/**
		 * Remove the permission
		 * @var string $permission
		 */
		public function delete($permission, $type) {
			if ($this->exists($permission)) {
				if ($type === "temp") {
					if ($_SESSION["permissions"][$permission] === "temp") {
						unset($_SESSION["permissions"][$permission]);
					}
				}
				else {
					if ($_SESSION["permissions"][$permission] !== "temp") {
						unset($_SESSION["permissions"][$permission]);
					}
				}
			}
		}

		/**
		 * Legacy call for compatibility with older code
		 * Check to see if the session has the requested permission
		 * @var string $permission
		 */
		function exists($permission) {
			return $this->has_any(array($permission));
		}

		/**
		 * Check to see if the session has the requested permission
		 * @var string $permission
		 */
		function has($permission) {
			return $this->has_any(array($permission));
		}

		/**
		 * Check to see if the session has any of the requested permission
		 * @var array[string] $permissions
		 */
		function has_any($permissions = array()) {
			if (!is_array($_SESSION["permissions"])) {
				return false;
			}
			foreach ($permissions as $permission) {
				if (isset($_SESSION["permissions"][$permission])) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Check to see if the session has all of the requested permission
		 * @var array[string] $permissions
		 */
		function has_all($permissions = array()) {
			if (!is_array($_SESSION["permissions"])) {
				return false;
			}
			foreach ($permissions as $permission) {
				if (!isset($_SESSION["permissions"][$permission])) {
					return false;
				}
			}
			return true;
		}

		/**
		 * Check to see if the session has any of the requested permission.
		 * Cause a access denied if session has none of the permissions
		 * @var array[string] $permissions
		 */
		function require_any($permissions = array()) {
			if(!$this->has_any($permissions)) {
				$this->access_denied();
			}
		}

		/**
		 * Check to see if the session has all of the requested permission
		 * Cause a access denied if session is missing any of the permissions
		 * @var array[string] $permissions
		 */
		function require_all($permissions = array()) {
			if(!$this->has_all($permissions)) {
				$this->access_denied();
			}
		}

		/**
		 * Bail out with access denied
		 * @var string $reason - currently unused but could be logged
		 */
		function access_denied($reason = null) {
			if (defined('STDIN')) {
				throw new Exception("Access Denied");
			}
			elseif ($this->use_header_method) {
				$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
				header($protocol.' Forbidden');
				$GLOBALS['http_response_code'] = 403;
			}
			else {
				http_response_code(403);
			}
			require "root.php";

			$language = new text;
			$text = $language->get(null,'resources');
			require "resources/require.php";
			require_once "resources/header.php";

			echo "<p style='text-align:center;font-size:xx-large;'>".$text['message-access_denied']."</p>\n";
			echo "<p style='text-align:center;'>".$text['description-access_denied']."</p>\n";

			require_once "resources/footer.php";
			die;
		}
	}
}

//examples
	/*
	//add the permission
		$p = new permissions;
		$p->add($permission);
	//delete the permission
		$p = new permissions;
		$p->delete($permission);
	//require the session to have a permission
		$p = new permissions;
		$p->require_any($permissions);
	*/

?>
