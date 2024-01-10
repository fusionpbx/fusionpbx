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
	Copyright (C) 2016 - 2023	All Rights Reserved.
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
		 * Check to see if the permission exists
		 * @var string $permission
		 */
		public function exists($permission_name) {

			//if run from command line then return true
			if (defined('STDIN')) {
				return true;
			}

			//set permisisons array
			$permissions = $_SESSION["permissions"];

			//set default to false
			$result = false;

			//search for the permission
			if (!empty($permissions) && !empty($permission_name)) {
				foreach($permissions as $key => $value) {
					if ($key == $permission_name) {
						$result = true;
						break;
					}
				}
			}

			//return the result
			return $result;
		}

		/**
		 * get the assigned permissions
		 * @var array $groups
		 */
		public function assigned($domain_uuid, $groups) {
			//groups not provided return false
			if (empty($groups)) {
				return false;
			}

			//prepare the parameters
			$x = 0;
			foreach ($groups as $field) {
				if (!empty($field['group_name'])) {
					$parameter_names[] = ":group_name_".$x;
					$parameters['group_name_'.$x] = $field['group_name'];
					$x++;
				}
			}

			//get the permissions assigned to the user through the assigned groups
			$sql = "select distinct(permission_name) from v_group_permissions ";
			$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
			if (is_array($parameter_names) && @sizeof($parameter_names) != 0) {
				$sql .= "and group_name in (".implode(", ", $parameter_names).") \n";
			}
			$sql .= "and permission_assigned = 'true' ";
			$parameters['domain_uuid'] = $domain_uuid;
			$database = new database;
			$permissions = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters, $result);
			return $permissions;
		}

		/**
		 * save the assigned permissions to a session
		 */
		public function session($domain_uuid, $groups) {
			$permissions = $this->assigned($domain_uuid, $groups);
			if (!empty($permissions)) {
				foreach ($permissions as $row) {
					$_SESSION['permissions'][$row["permission_name"]] = true;
					$_SESSION["user"]["permissions"][$row["permission_name"]] = true;
				}
			}
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
