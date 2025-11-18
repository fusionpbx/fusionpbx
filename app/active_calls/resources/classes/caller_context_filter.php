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
 * Description of caller_context_filter
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class caller_context_filter implements filter {

	private $domains;

	/**
	 * Initializes the object with an array of domain names.
	 *
	 * @param array $domain_names Array of domain names to initialize the object with.
	 *
	 * @return void
	 */
	public function __construct(array $domain_names) {
		foreach ($domain_names as $name) {
			$this->domains[$name] = true;
		}
	}

	/**
	 * Invokes this method as a callable.
	 *
	 * @param string $key   The event key, which is used for validation and filtering.
	 * @param mixed  $value The value associated with the event key, used to determine
	 *                      whether the filter chain should drop the payload.
	 *
	 * @return bool|null Returns true if the key is not 'caller_context' or the value exists in the domains,
	 *                   otherwise returns null to drop the payload.
	 */
	public function __invoke(string $key, $value): ?bool {
		// return true when not on the event key caller_context to validate
		if ($key !== 'caller_context') {
			return true;
		}
		// Instruct the filter chain to drop the payload
		if (!isset($this->domains[$value])) {
			return null;
		}
		return true;
	}

}
