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
 */

/**
 * Filters events based on event type permissions.
 *
 * When the 'event_name' or 'action' key is encountered, this filter checks
 * if the subscriber has permission to receive this event type. If not,
 * returns null to drop the entire message.
 *
 * @author FusionPBX
 */
class event_type_permission_filter implements filter {

	/**
	 * Map of event types to required permissions
	 * @var array
	 */
	private $event_permission_map;

	/**
	 * The subscriber's permissions
	 * @var array
	 */
	private $permissions;

	/**
	 * Whether permission check has been performed for this message
	 * @var bool
	 */
	private $checked = false;

	/**
	 * Constructor
	 *
	 * @param array $event_permission_map Map of event types to required permissions
	 * @param array $permissions The subscriber's permissions
	 */
	public function __construct(array $event_permission_map, array $permissions) {
		$this->event_permission_map = $event_permission_map;
		$this->permissions = $permissions;
	}

	/**
	 * Check if the subscriber has permission to receive this event type.
	 *
	 * When invoked with the 'event_name' or 'action' key, checks the event type
	 * against the permission map. Returns null to drop the message if subscriber
	 * doesn't have permission.
	 *
	 * @param string $key The key from the payload
	 * @param mixed $value The value from the payload
	 *
	 * @return bool|null True if permitted or not an event type key, null to drop message
	 */
	public function __invoke(string $key, $value): ?bool {
		// Only check event_name or action keys (first one wins)
		if ($this->checked || ($key !== 'event_name' && $key !== 'action')) {
			return true;
		}

		$this->checked = true;

		// Normalize the event name (replace hyphens with underscores)
		$event_type = str_replace('-', '_', $value);

		// Look up required permission for this event type
		$required_permission = $this->event_permission_map[$event_type] ?? null;

		// If event is not in map, allow by default (base view permission already checked)
		if ($required_permission === null) {
			return true;
		}

		// Check if subscriber has the required permission
		if (isset($this->permissions[$required_permission])) {
			return true;
		}

		// Subscriber doesn't have permission - drop the entire message
		return null;
	}

	/**
	 * Reset the filter for reuse with a new message
	 */
	public function reset(): void {
		$this->checked = false;
	}
}
