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
 * Builds an event filter chain link of any class implementing an event_filter interface
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
final class filter_chain {

	/**
	 * Builds a filter chain link for filter objects
	 * @param array $filters Array of filter objects
	 * @return filter
	 */
	public static function or_link(array $filters): filter {

		// Create an anonymous object to end the filter
		$final = new class implements filter {

			public function __invoke(string $key, $value): bool {
				return false;
			}
		};

		// Add the final object
		$chain = $final;

		// Iterate over the objects to add them in reverse order
		for ($i = count($filters) - 1; $i >= 0; $i--) {
			$current = $filters[$i];

			// Remember the chain that will be called next
			$next = $chain;

			// Create an anonymous object to start the filter
			$chain = new class($current, $next) implements filter {

				private $current;
				private $next;

				public function __construct(filter $current, filter $next) {
					$this->current = $current;
					$this->next = $next;
				}

				public function __invoke(string $key, $value): ?bool {
					if (($this->current)($key, $value)) {
						// Any filter passed so return true
						return true;
					}
					// Filter did not pass so we check the next one
					return ($this->next)($key, $value);
				}
			};
		}

		// Return the completed filter chain
		return $chain;
	}

	public static function and_link(array $filters): filter {
		return new class($filters) implements filter {
			private $filters;

			public function __construct(array $filters) {
				$this->filters = $filters;
			}

			public function __invoke(string $key, $value): ?bool {
				foreach ($this->filters as $filter) {
					$result = ($filter)($key, $value);
					// Check if a filter requires a null to be returned
					if ($result === null) {
						return null;
					} elseif(!$result) {
						return false;
					}
				}
				// All filters passed so return true
				return true;
			}
		};
	}
}
