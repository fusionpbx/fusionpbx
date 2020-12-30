<?php

abstract class feature_base {
	private const app_name = 'calls';
	private const app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
	protected const enabled  = 'true';
	protected const disabled = 'false';

	protected $cache;

	public function __construct() {
		$this->cache = new cache;
	}

	protected function getExistingState(array $uuids) : array {
		if (is_array($uuids) && @sizeof($uuids) != 0) {
			$sql  = "select extension_uuid, extension, number_alias, call_timeout, ";
			$sql .= "do_not_disturb, forward_all_enabled, forward_all_destination, ";
			$sql .= "forward_busy_enabled, forward_busy_destination, forward_no_answer_enabled, ";
			$sql .= "forward_no_answer_destination, follow_me_enabled, follow_me_uuid ";
			$sql .= "from v_extensions ";
			$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$sql .= "and extension_uuid in ('".implode('\', \'', $uuids)."') ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$rows = $database->select($sql, $parameters);
			if (is_array($rows) && @sizeof($rows) != 0) {
				return $rows;
			}
			unset($sql, $parameters, $rows, $row);
		}
		return array();
	}

	/**
	 * @param array $extension The updated extension information
	 * @return array The input extension is returned with possibly modified data
	 */
	protected function update(array $extension) : array {
		// Feature key sync if enabled
		if ($_SESSION['device']['feature_sync']['boolean'] == "true") {
			$feature_event_notify = new feature_event_notify;
			$feature_event_notify->domain_name = $_SESSION['domain_name'];
			$feature_event_notify->extension = $extension['extension'];
			$feature_event_notify->do_not_disturb = $extension['do_not_disturb'];
			$feature_event_notify->ring_count = ceil($extension['call_timeout'] / 6);
			$feature_event_notify->forward_all_enabled = $extension['forward_all_enabled'];
			$feature_event_notify->forward_busy_enabled = $extension['forward_busy_enabled'];
			$feature_event_notify->forward_no_answer_enabled = $extension['forward_no_answer_enabled'];
			//workarounds: send 0 as freeswitch doesn't send NOTIFY when destination values are nil
			$feature_event_notify->forward_all_destination = $extension['forward_all_destination'] != '' ? $extension['forward_all_destination'] : '0';
			$feature_event_notify->forward_busy_destination = $extension['forward_busy_destination'] != '' ? $extension['forward_busy_destination'] : '0';
			$feature_event_notify->forward_no_answer_destination = $extension['forward_no_answer_destination'] != '' ? $extension['forward_no_answer_destination'] : '0';
			$feature_event_notify->send_notify();
			unset($feature_event_notify);
		}

		// Clear the cache
		$this->cache->delete("directory:{$extension['extension']}@{$_SESSION['domain_name']}");
		if ($extension['number_alias'] != '') {
			$this->cache->delete("directory:{$extension['number_alias']}@{$_SESSION['domain_name']}");
		}

		return $extension;
	}

	protected function save(array $updates) {
		//save the changes
		if (@sizeof($updates) > 0) {
			//grant temporary permissions
			$p = new permissions;
			$p->add('extension_edit', 'temp');

			//save the array
			$database = new database;
			$database->app_name = feature_base::app_name;
			$database->app_uuid = feature_base::app_uuid;
			$database->save($updates);
			unset($updates);

			//revoke temporary permissions
			$p->delete('extension_edit', 'temp');
		}

		//synchronize configuration
		if (is_readable($_SESSION['switch']['extensions']['dir'])) {
			require_once "app/extensions/resources/classes/extension.php";
			$ext = new extension;
			$ext->xml();
			unset($ext);
		}
	}
}