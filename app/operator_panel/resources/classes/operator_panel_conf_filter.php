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
 * Filters conference events to only include keys relevant to the operator panel,
 * and drops messages from domains other than the subscriber's.
 *
 * Passes only the keys listed in {@see operator_panel_service::conf_event_keys}
 * and enforces domain isolation using caller_context.
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class operator_panel_conf_filter implements filter {

	/**
	 * Allowed domain names keyed for fast lookup
	 *
	 * @var array
	 */
	private $domains;

	/**
	 * Keys that are permitted through the filter
	 *
	 * @var array
	 */
	private $allowed_keys;

	/**
	 * @param array $domain_names  Domain names this subscriber is allowed to see.
	 * @param array $allowed_keys  Event keys to include in the forwarded payload.
	 */
	public function __construct(array $domain_names, array $allowed_keys) {
		foreach ($domain_names as $name) {
			$this->domains[$name] = true;
		}
		$this->allowed_keys = array_flip($allowed_keys);
	}

	/**
	 * Called for each key/value pair in the event payload.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool|null  true to keep, false to skip this key, null to drop the entire message.
	 */
	public function __invoke($key, $value): ?bool {
		// Domain guard — drop whole message if context is wrong
		if ($key === 'caller_context') {
			return isset($this->domains[$value]) ? true : null;
		}

		// Key allow-list
		return isset($this->allowed_keys[$key]) ? true : false;
	}
}
