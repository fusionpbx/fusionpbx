<?php

/*
 * FusionPBX
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is FusionPBX
 *
 * The Initial Developer of the Original Code is
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Portions created by the Initial Developer are Copyright (C) 2008-2025
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

/**
 * Description of permission_filter
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class permission_filter implements filter {

	private $field_map;
	private $permissions;

	public function __construct(array $event_field_key_to_permission_map, array $permissions = []) {
		$this->field_map = $event_field_key_to_permission_map;
		$this->add_permissions($permissions);
	}

	public function __invoke(string $key, $value): ?bool {
		$permission = $this->field_map[$key] ?? null;
		if ($permission === null || $this->has_permission($permission)) {
			return true;
		}
		return false;
	}

	/**
	 * Adds an associative array of permissions where $key is the name of the permission and $value is ignored as it should always be set to true.
	 * @param array $permissions
	 */
	public function add_permissions(array $permissions) {
		// Add all event key filters passed
		foreach (array_keys($permissions) as $key) {
			$this->add_permission($key);
		}
	}

	/**
	 * Adds a single permission
	 * @param string $key
	 */
	public function add_permission(string $key) {
		$this->permissions[$key] = $key;
	}

	/**
	 * Checks if the filter has a permission
	 * @param string $key
	 * @return bool
	 */
	public function has_permission(string $key): bool {
		return isset($this->permissions[$key]);
	}
}
