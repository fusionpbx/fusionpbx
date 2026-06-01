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
 * Filters conference events using conference-aware domain detection.
 *
 * Conference maintenance events are not consistent about which field carries
 * the domain. Accept a message when the domain can be proven from either the
 * conference identifier or the caller context, and otherwise keep the message
 * so operator panel conference updates are not dropped.
 */
class operator_panel_conf_filter implements filter {

	/**
	 * Allowed domain names keyed for fast lookup.
	 *
	 * @var array
	 */
	private $domains = [];

	/**
	 * Keys that are permitted through the filter.
	 *
	 * @var array
	 */
	private $allowed_keys = [];

	/**
	 * Whether the current event should be dropped.
	 *
	 * @var bool
	 */
	private $drop_message = false;

	/**
	 * Whether a matching domain has been positively identified.
	 *
	 * @var bool
	 */
	private $matched_domain = false;

	/**
	 * @param array $domain_names Domain names this subscriber is allowed to see.
	 * @param array $allowed_keys Event keys to include in the forwarded payload.
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
	 * @return bool|null true to keep, false to skip this key, null to drop the entire message.
	 */
	public function __invoke($key, $value): ?bool {
		if ($this->drop_message) {
			return null;
		}

		if ($this->is_domain_key($key)) {
			$matched = $this->match_domain((string)$value, $key === 'caller_context');
			if ($matched === false) {
				$this->drop_message = true;
				return null;
			}
		}

		if ($this->matched_domain || !$this->is_domain_key($key)) {
			return isset($this->allowed_keys[$key]) ? true : false;
		}

		return true;
	}

	/**
	 * Determine whether the key can identify the conference domain.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	private function is_domain_key(string $key): bool {
		return in_array($key, ['caller_context', 'conference_name', 'channel_presence_id'], true);
	}

	/**
	 * Match the event's domain against the allowed set.
	 *
	 * @param string $value
	 * @param bool   $is_context
	 *
	 * @return bool|null false when the event belongs to another domain, true when it matches,
	 *                   or null when the key does not conclusively identify a domain.
	 */
	private function match_domain(string $value, bool $is_context): ?bool {
		$value = trim($value);
		if ($value === '') {
			return null;
		}

		$domain = $value;
		if (!$is_context && strpos($value, '@') !== false) {
			$parts = explode('@', $value);
			$domain = end($parts) ?: '';
		}

		if ($domain === '') {
			return null;
		}

		if (isset($this->domains[$domain])) {
			$this->matched_domain = true;
			return true;
		}

		return false;
	}
}
