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
	Copyright (C) 2016 - 2024	All Rights Reserved.
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

		private $database;
		private $domain_uuid;
		private $user_uuid;
		private $groups;
		private $permissions;

		/**
		 * called when the object is created
		 */
		public function __construct($database = null, $domain_uuid = null, $user_uuid = null) {

			//handle the database object
			if (isset($database)) {
				$this->database = $database;
			}
			else {
				$this->database = new database;
			}

			//set the domain_uuid
			if (!empty($domain_uuid) && is_uuid($domain_uuid)) {
				$this->domain_uuid = $domain_uuid;
			}
			elseif (isset($_SESSION['domain_uuid']) && is_uuid($_SESSION['domain_uuid'])) {
				$this->domain_uuid = $_SESSION['domain_uuid'];
			}

			//set the user_uuid
			if (!empty($user_uuid) && is_uuid($user_uuid)) {
				$this->user_uuid = $user_uuid;
			}
			elseif (isset($_SESSION['user_uuid']) && is_uuid($_SESSION['user_uuid'])) {
				$this->user_uuid = $_SESSION['user_uuid'];
			}

			//get the permissions
			if (isset($_SESSION['permissions'])) {
				$this->permissions = $_SESSION['permissions'];
			}
			else {
				//create the groups object
				$groups = new groups($this->database, $this->domain_uuid, $this->user_uuid);
				$this->groups = $groups->assigned();

				//get the list of groups assigned to the user
				$this->permissions = $this->assigned();
			}
		}

		/**
		 * get the array of permissions
		 */
		public function get_permissions() {
			return $this->permissions;
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
			if ($this->exists($permission) && !empty($_SESSION["permissions"][$permission])) {
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

			//set default to false
			$result = false;

			//search for the permission
			if (!empty($this->permissions) && !empty($permission_name)) {
				foreach($this->permissions as $key => $value) {
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
		public function assigned() {
			//define the array
			$parameter_names = [];

			//prepare the parameters
			$x = 0;
			foreach ($this->groups as $field) {
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
			$parameters['domain_uuid'] = $this->domain_uuid;
			$database = new database;
			$permissions = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters, $result);
			return $permissions;
		}

		/**
		 * save the assigned permissions to a session
		 */
		public function session() {
			if (!empty($this->permissions)) {
				foreach ($this->permissions as $row) {
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