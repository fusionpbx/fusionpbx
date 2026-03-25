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
 * Role-aware filter for agent data payloads.
 *
 * Supervisors (those with the operator_panel_manage permission) receive the
 * full agent list for all queues in their domain.
 *
 * Regular agents receive an aggregate-only view: caller counts per queue and
 * their own individual stats — no peer details are included.
 *
 * This class does not implement the filter interface because agent stats are
 * built as complete arrays rather than streamed key/value pairs.  It is used
 * directly by {@see operator_panel_service} to shape the payload before
 * sending.
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class operator_panel_agent_filter {

	/**
	 * Whether this subscriber is a supervisor (has operator_panel_manage).
	 *
	 * @var bool
	 */
	private $is_supervisor;

	/**
	 * The agent_name value from v_call_center_agents for this user, or empty string.
	 *
	 * @var string
	 */
	private $agent_name;

	/**
	 * @param bool   $is_supervisor Whether the subscriber has supervisor privileges.
	 * @param string $agent_name    The call-center agent name for this user (empty for non-agents).
	 */
	public function __construct(bool $is_supervisor, string $agent_name = '') {
		$this->is_supervisor = $is_supervisor;
		$this->agent_name    = $agent_name;
	}

	/**
	 * Filter an array of agent rows for this subscriber.
	 *
	 * @param array $agents  Full agent list: each element is an associative array with
	 *                       keys: agent_name, queue_name, status, state, calls_answered,
	 *                       talk_time, last_status_change, ready_time.
	 *
	 * @return array  Filtered agent data appropriate for this subscriber.
	 */
	public function filter(array $agents): array {
		if ($this->is_supervisor) {
			// Supervisors see everything
			return $agents;
		}

		// Regular agent: build aggregate queue counts + own row only
		$result = [
			'role'        => 'agent',
			'own_stats'   => null,
			'queue_counts' => [],
		];

		$queue_counts = [];
		foreach ($agents as $agent) {
			$queue = $agent['queue_name'] ?? '';
			if (!isset($queue_counts[$queue])) {
				$queue_counts[$queue] = ['queue_name' => $queue, 'agent_count' => 0, 'available_count' => 0];
			}
			$queue_counts[$queue]['agent_count']++;
			if (($agent['status'] ?? '') === 'Available') {
				$queue_counts[$queue]['available_count']++;
			}

			// Include own stats
			if (!empty($this->agent_name) && ($agent['agent_name'] ?? '') === $this->agent_name) {
				$result['own_stats'] = $agent;
			}
		}
		$result['queue_counts'] = array_values($queue_counts);

		return $result;
	}
}
