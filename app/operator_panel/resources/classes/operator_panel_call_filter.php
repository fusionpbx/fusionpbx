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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Tim Fry <tim@fusionpbx.com>
*/

/**
 * Filters call events to only include those belonging to the subscriber's domain.
 *
 * Checks the 'caller_context' field of each event key/value pair.  When the
 * context does not match any of the allowed domain names the entire message is
 * dropped by returning null.
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class operator_panel_call_filter implements filter {

	/**
	 * Allowed domain names keyed for fast lookup
	 *
	 * @var array
	 */
	private $domains;

	/**
	 * @param array $domain_names Domain names this subscriber is allowed to see.
	 */
	public function __construct(array $domain_names) {
		foreach ($domain_names as $name) {
			$this->domains[$name] = true;
		}
	}

	/**
	 * Called for each key/value pair in the event payload.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool|null  true to keep, null to drop the entire message.
	 */
	public function __invoke($key, $value): ?bool {
		if ($key !== 'caller_context') {
			return true;
		}
		return isset($this->domains[$value]) ? true : null;
	}
}
