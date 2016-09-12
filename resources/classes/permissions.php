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
	Copyright (C) 2015	All Rights Reserved.

*/

/**
 * permission class
 *
 * @method string add
 * @method string delete
 * @method string exists
 */
if (!class_exists('permissions')) {
	class permissions {

		/**
		 * Add a permission
		 * @var string $permission
		 */
		public function add($permission, $type = '') {
			if (!$this->exists($permission)) {
				//set the ordinal number
					$i = count($_SESSION["permissions"])+1;

				//set the permission
					$_SESSION["permissions"][$permission] = "temp";
			}
		}

		/**
		 * Remove the permission
		 * @var string $permission
		 */
		public function delete($permission, $type = '') {
			if ($this->exists($permission)) {
				if ($type = "temp" && $_SESSION["permissions"][$permission] == "temp") {
					unset($_SESSION["permissions"][$permission]);
				}
				else {
					unset($_SESSION["permissions"][$permission]);
				}
			}
		}

		/**
		 * Check to see if the permission exists
		 * @var string $permission
		 */
		function exists($permission) {
			//set default false
				$result = false;
			//search for the permission
				if (is_array($_SESSION["permissions"]) && $_SESSION["permissions"][$permission] == true) {
					$result = true;
				}
			//return the result
				return $result;
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
	*/

?>
