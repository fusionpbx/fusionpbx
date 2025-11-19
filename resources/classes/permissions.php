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
 */
class permissions {

	private static $permission;
	private $database;
	private $domain_uuid;
	private $user_uuid;
	private $groups;
	private $permissions;

	/**
	 * Constructor.
	 *
	 * Initializes this object with a database connection, domain UUID, and user UUID.
	 *
	 * @param Database|null $database    Database connection. If null, a new database connection will be created.
	 * @param string|null   $domain_uuid Domain UUID. If null, the value from the session will be used.
	 * @param string|null   $user_uuid   User UUID. If null, the value from the session will be used.
	 *
	 * @return void
	 */
	public function __construct($database = null, $domain_uuid = null, $user_uuid = null) {

		//intitialize as empty arrays
		$this->groups = [];
		$this->permissions = [];

		//handle the database object
		if (isset($database)) {
			$this->database = $database;
		} else {
			$this->database = database::new();
		}

		//set the domain_uuid
		if (!empty($domain_uuid) && is_uuid($domain_uuid)) {
			$this->domain_uuid = $domain_uuid;
		} elseif (isset($_SESSION['domain_uuid']) && is_uuid($_SESSION['domain_uuid'])) {
			$this->domain_uuid = $_SESSION['domain_uuid'];
		}

		//set the user_uuid
		if (!empty($user_uuid) && is_uuid($user_uuid)) {
			$this->user_uuid = $user_uuid;
		} elseif (isset($_SESSION['user_uuid']) && is_uuid($_SESSION['user_uuid'])) {
			$this->user_uuid = $_SESSION['user_uuid'];
		}

		//get the permissions
		if (isset($_SESSION['permissions'])) {
			$this->permissions = $_SESSION['permissions'];
		} else {
			//create the groups object
			$groups = new groups($this->database, $this->domain_uuid, $this->user_uuid);
			$this->groups = $groups->assigned();

			//get the list of groups assigned to the user
			if (!empty($this->groups)) {
				$this->assigned();
			}
		}
	}

	/**
	 * A singleton pattern for either creating a new object or the existing object.
	 *
	 * Initializes this object with a database connection, domain UUID, and user UUID.
	 *
	 * @param Database|null $database    Database connection. If null, a new database connection will be created.
	 * @param string|null   $domain_uuid Domain UUID. If null, the value from the session will be used.
	 * @param string|null   $user_uuid   User UUID. If null, the value from the session will be used.
	 *
	 * @return self
	 */
	public static function new($database = null, $domain_uuid = null, $user_uuid = null) {
		if (self::$permission === null) {
			self::$permission = new permissions($database, $domain_uuid, $user_uuid);
		}
		return self::$permission;
	}

	/**
	 * Method to retrieve permissions assigned to the user through their groups.
	 *
	 * Retrieves the list of group names associated with the user's assigned groups,
	 * and then uses these group names to query for distinct permission names that are
	 * assigned to these groups. The resulting list of permission names is stored in
	 * this object's 'permissions' array.
	 *
	 * @return void
	 */
	private function assigned() {
		//define the array
		$permissions = [];
		$parameter_names = [];

		//return empty array if there are no groups
		if (empty($this->groups)) {
			return [];
		}

		//prepare the parameters
		$x = 0;
		foreach ($this->groups as $field) {
			if (!empty($field['group_name'])) {
				$parameter_names[] = ":group_name_" . $x;
				$parameters['group_name_' . $x] = $field['group_name'];
				$x++;
			}
		}

		//get the permissions assigned to the user through the assigned groups
		$sql = "select distinct(permission_name) from v_group_permissions ";
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$sql .= "and group_name in (" . implode(", ", $parameter_names) . ") \n";
		$sql .= "and permission_assigned = 'true' ";
		$parameters['domain_uuid'] = $this->domain_uuid;
		$group_permissions = $this->database->select($sql, $parameters, 'all');

		//format the permission array
		foreach ($group_permissions as $row) {
			$permissions[$row['permission_name']] = 1;
		}

		//save permissions to this object
		$this->permissions = $permissions;
	}

	/**
	 * Returns an array of permissions assigned to this user.
	 *
	 * The list of permissions is populated from the session or retrieved from the database based on
	 * the domain UUID and user UUID associated with this object.
	 *
	 * @return array An array of permission identifiers (e.g. 'create_user', 'edit_group', etc.)
	 */
	public function get_permissions() {
		return $this->permissions;
	}

	/**
	 * Adds a permission to this object.
	 *
	 * If the specified permission does not already exist, it will be added to the permissions array with the provided
	 * type.
	 *
	 * @param string $permission Permission to add.
	 * @param mixed  $type       Type of the permission.
	 *
	 * @return void
	 */
	public function add($permission, $type) {
		//add the permission if it is not in array
		if (!$this->exists($permission)) {
			$this->permissions[$permission] = $type;
		}
	}

	/**
	 * Checks if a permission exists.
	 *
	 * Returns true if the permission is assigned to the user, or if this method is called from the command line.
	 *
	 * @param string $permission_name Name of the permission to check for existence.
	 *
	 * @return bool True if the permission exists, false otherwise.
	 */
	public function exists($permission_name) {

		//if run from command line then return true
		if (defined('STDIN')) {
			return true;
		}

		//search for the permission
		if (!empty($permission_name)) {
			return isset($this->permissions[$permission_name]);
		}

		return false;
	}

	/**
	 * Deletes a permission.
	 *
	 * If the permission exists and is not temporary, it will be removed from the permissions array.
	 *
	 * @param string $permission The name of the permission to delete.
	 * @param string $type       The type of permission (e.g. "temp", "permanent").
	 *
	 * @return void
	 */
	public function delete($permission, $type) {
		if ($this->exists($permission) && !empty($this->permissions[$permission])) {
			if ($type === "temp") {
				if ($this->permissions[$permission] === "temp") {
					unset($this->permissions[$permission]);
				}
			} else {
				if ($this->permissions[$permission] !== "temp") {
					unset($this->permissions[$permission]);
				}
			}
		}
	}

	/**
	 * Saves the current permissions to the session.
	 *
	 * @return void
	 */
	public function session() {
		if (!empty($this->permissions)) {
			foreach ($this->permissions as $permission_name => $row) {
				$_SESSION['permissions'][$permission_name] = true;
				$_SESSION["user"]["permissions"][$permission_name] = true;
			}
		}
	}

}

//examples
/*
//add the permission
	$p = permissions::new();
	$p->add($permission);
//delete the permission
	$p = permissions::new();
	$p->delete($permission);
*/
