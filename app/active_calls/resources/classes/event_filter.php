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

	public function __construct(array $event_names) {
		$this->add_event_names($event_names);
	}

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

	public function has_event_name(string $name): ?bool {
		if (isset($this->event_names[$name]))
			return true;
		//
		// If the event name is not allowed by the permissions given in
		// this object, then the entire event must be dropped. I could
		// not figure out a better way to do this except to throw an
		// exception so that the caller can drop the message.
		//
		// TODO: Find another way not so expensive to reject the payload
		//
		return null;
	}
}
