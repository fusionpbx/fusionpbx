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
 * Filter an event based on event name or event subclass or event command
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class event_filter implements filter {

	private $event_names;

	/**
	 * Initializes the object with the event names to filter on.
	 *
	 * @param array $event_names Array of event names to initialize the object with
	 */
	public function __construct(array $event_names) {
		$this->add_event_names($event_names);
	}

	/**
	 * Invokes this method to filter events.
	 *
	 * @param string $key   The key name that will be used for filtering, currently only
	 *                      supports the "event_name" key.
	 * @param mixed  $value The value associated with the provided key.
	 *
	 * @return bool|null Returns true if the invocation is valid and the event name
	 *         filter has not been applied yet. If the invocation has an "event_name"
	 *         key and its corresponding value, this method will return a boolean
	 *         indicating whether the event name already exists in the list of allowed
	 *         names or not. Otherwise returns null.
	 */
	public function __invoke(string $key, $value): ?bool {
		if ($key !== 'event_name') {
			return true;
		}
		return $this->has_event_name($value);
	}

	/**
	 * Adds a single event name filter
	 * @param string $name
	 */
	public function add_event_name(string $name) {
		$this->event_names[$name] = $name;
	}

	/**
	 * Adds the array list to the filters.
	 * @param array $event_names
	 */
	public function add_event_names(array $event_names) {
		// Add all event key filters passed
		foreach ($event_names as $event_name) {
			if (is_array($event_name)) {
				$this->add_event_names($event_name);
			} else {
				$this->add_event_name($event_name);
			}
		}
	}

	/**
	 * Checks if an event with the given name is present in the filters.
	 *
	 * @param string $name The name of the event to check for.
	 *
	 * @return bool|null True if the event is found, otherwise null. Returns null
	 *                  when the event is not allowed due to permissions constraints.
	 */
	public function has_event_name(string $name): ?bool {
		if (isset($this->event_names[$name])) {
			return true;
		}

		//reject the payload
		return null;
	}
}
