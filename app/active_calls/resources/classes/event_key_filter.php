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
 * Active call filter class definition
 * @author Tim Fry <tim@fusionpbx.com>
 */
class event_key_filter implements filter {

	private $filters;

	public function __construct(array $filters = []) {
		$this->add_filters($filters);
	}

	public function __invoke(string $key, $value): ?bool {
		return $this->has_filter_key($key);
	}

	/**
	 * Adds a single filter
	 * @param string $key
	 */
	public function add_filter(string $key) {
		$this->filters[$key] = $key;
	}

	/**
	 * Returns the current list of filters
	 * @return array
	 */
	public function get_filters(): array {
		return array_values($this->filters);
	}

	/**
	 * Removes a single list of filters
	 * @param string $key
	 */
	public function remove_filter(string $key) {
		unset($this->filters[$key]);
	}

	/**
	 * Clears all filters
	 */
	public function clear_filters() {
		$this->filters = [];
	}

	/**
	 * Adds the array list to the filters.
	 * @param array $list_of_keys
	 */
	public function add_filters(array $list_of_keys) {
		// Add all event key filters passed
		foreach ($list_of_keys as $key) {
			$this->filters[$key] = $key;
		}
	}

	public function has_filter_key(string $key): bool {
		return isset($this->filters[$key]);
	}
}
