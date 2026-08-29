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
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	denisent dev team
*/

//includes
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('paging_group_add') && !permission_exists('paging_group_edit')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set variables
	$settings = new settings(['database' => $database, 'domain_uuid' => $_SESSION['domain_uuid']]);
	$domain_uuid = $_SESSION['domain_uuid'];
	$domain_name = $_SESSION['domain_name'] ?? $_SESSION['domain']['name'] ?? '';
	$paging_group_uuid = $_GET['id'] ?? $_POST['paging_group_uuid'] ?? '';
	$dialplan_uuid = $_POST['dialplan_uuid'] ?? '';
	$action = empty($paging_group_uuid) ? 'add' : 'update';
	$input_toggle_style_switch = $settings->get('theme', 'input_toggle_style_switch', false);

//labels / fallback text
	$label_true = $text['option-true'];
	$label_false = $text['option-false'];
	$button_back = $text['button-back'];
	$button_save = $text['button-save'];
	$button_advanced = $text['button-advanced'];

//get the defaults
	$paging_group_extension = '';
	$paging_group_name = '';
	$paging_group_cid_name = '';
	$paging_group_cid_number = '';
	$paging_group_type = 'page';
	$paging_group_pin_number = '';
	$paging_group_recording_uuid = '';
	$paging_group_announcement_source = 'none';
	$paging_group_announcement_sound = '';
	$paging_group_announcement_recording_uuid = '';
	$paging_group_timeout = '30';
	$paging_group_skip_busy = 'true';
	$paging_group_registered_only = 'true';
	$paging_group_include_originator = 'false';
	$paging_group_auto_answer = 'default';
	$paging_group_waiver_enabled = 'false';
	$paging_group_waiver_accept_user = null;
	$paging_group_waiver_accept_date = null;
	$paging_group_waiver_remove_user = null;
	$paging_group_waiver_remove_date = null;
	$paging_group_destinations = [];
	$show_destination_delete = false;
	$paging_group_enabled = 'true';
	$paging_group_description = '';

//save the data
	if (is_array($_POST) && @sizeof($_POST) != 0) {

		//check the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: paging.php');
				exit;
			}

		//get posted values
			$paging_group_uuid = $_POST['paging_group_uuid'] ?? '';
			$dialplan_uuid = $_POST['dialplan_uuid'] ?? '';
			$paging_group_extension = trim($_POST['paging_group_extension'] ?? '');
			$paging_group_name = trim($_POST['paging_group_name'] ?? '');
			$paging_group_cid_name = trim($_POST['paging_group_cid_name'] ?? '');
			$paging_group_cid_number = trim($_POST['paging_group_cid_number'] ?? '');
			$paging_group_type = $_POST['paging_group_type'] ?? 'page';
			$paging_group_pin_number = trim($_POST['paging_group_pin_number'] ?? '');
			$paging_group_recording_uuid = $_POST['paging_group_recording_uuid'] ?? '';
			$paging_group_announcement_source = $_POST['paging_group_announcement_source'] ?? 'none';
			$paging_group_announcement_sound = trim($_POST['paging_group_announcement_sound'] ?? '');
			$paging_group_announcement_recording_uuid = $_POST['paging_group_announcement_recording_uuid'] ?? '';
			$paging_group_timeout = $_POST['paging_group_timeout'] ?? '30';
			$paging_group_skip_busy = 'true';
			$paging_group_registered_only = 'true';
			$paging_group_include_originator = 'false';
			$paging_group_auto_answer = $_POST['paging_group_auto_answer'] ?? 'default';
			$paging_group_waiver_enabled_posted = $_POST['paging_group_waiver_enabled'] ?? 'false';
			$paging_group_destinations = $_POST['paging_group_destinations'] ?? [];
			$paging_group_destinations_delete = $_POST['paging_group_destinations_delete'] ?? [];
			$paging_group_enabled = $_POST['paging_group_enabled'] ?? 'false';
			$paging_group_description = $_POST['paging_group_description'] ?? '';

		//normalize posted values
			if (!in_array($paging_group_type, ['page', 'intercom'])) {
				$paging_group_type = 'page';
			}
			if (!in_array($paging_group_auto_answer, ['default', 'yealink', 'disabled'])) {
				$paging_group_auto_answer = 'default';
			}
			if (!is_numeric($paging_group_timeout)) {
				$paging_group_timeout = '30';
			}
			if (!empty($paging_group_recording_uuid) && !is_uuid($paging_group_recording_uuid)) {
				$paging_group_recording_uuid = null;
			}
			if (empty($paging_group_recording_uuid)) {
				$paging_group_recording_uuid = null;
			}
			if (!in_array($paging_group_announcement_source, ['none', 'sound', 'recording'])) {
				$paging_group_announcement_source = 'none';
			}
			if ($paging_group_announcement_source == 'sound') {
				$paging_group_announcement_recording_uuid = null;
			}
			else if ($paging_group_announcement_source == 'recording') {
				$paging_group_announcement_sound = '';
				if (!is_uuid($paging_group_announcement_recording_uuid)) {
					$paging_group_announcement_recording_uuid = null;
				}
			}
			else {
				$paging_group_announcement_sound = '';
				$paging_group_announcement_recording_uuid = null;
			}

		//determine add/update after posted uuid is known
			$action = empty($paging_group_uuid) ? 'add' : 'update';

		//permission check for action
			if ($action == 'add' && !permission_exists('paging_group_add')) {
				echo "access denied";
				exit;
			}
			if ($action == 'update' && !permission_exists('paging_group_edit')) {
				echo "access denied";
				exit;
			}

		//validate required fields
			if (empty($paging_group_extension) || empty($paging_group_name)) {
				message::add($text['message-required_fields'], 'negative');
				header('Location: paging_edit.php'.(!empty($paging_group_uuid) ? '?id='.urlencode($paging_group_uuid) : ''));
				exit;
			}

		//validate extension format
			if (!preg_match('/^[0-9*#]+$/', $paging_group_extension)) {
				message::add($text['message-extension_format'], 'negative');
				header('Location: paging_edit.php'.(!empty($paging_group_uuid) ? '?id='.urlencode($paging_group_uuid) : ''));
				exit;
			}

		//check for duplicate paging group extension in this domain
			$sql = "select count(*) as count from v_paging_groups ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and paging_group_extension = :paging_group_extension ";
			if (!empty($paging_group_uuid) && is_uuid($paging_group_uuid)) {
				$sql .= "and paging_group_uuid <> :paging_group_uuid ";
				$parameters['paging_group_uuid'] = $paging_group_uuid;
			}
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['paging_group_extension'] = $paging_group_extension;
			$row = $database->select($sql, $parameters, 'row');
			unset($sql, $parameters);
			if (isset($row['count']) && (int) $row['count'] > 0) {
				message::add($text['message-extension_exists'], 'negative');
				header('Location: paging_edit.php'.(!empty($paging_group_uuid) ? '?id='.urlencode($paging_group_uuid) : ''));
				exit;
			}

		//check for generated dialplan destination conflict in this domain
			$sql = "select count(*) as count ";
			$sql .= "from v_dialplans dp ";
			$sql .= "join v_dialplan_details dd on dd.dialplan_uuid = dp.dialplan_uuid ";
			$sql .= "where dp.domain_uuid = :domain_uuid ";
			$sql .= "and dd.dialplan_detail_tag = 'condition' ";
			$sql .= "and dd.dialplan_detail_type = 'destination_number' ";
			$sql .= "and dd.dialplan_detail_data in (:pattern_plain, :pattern_exact) ";
			if (!empty($paging_group_uuid) && is_uuid($paging_group_uuid)) {
				$sql .= "and dp.dialplan_uuid not in (select dialplan_uuid from v_paging_groups where paging_group_uuid = :paging_group_uuid and domain_uuid = :domain_uuid and dialplan_uuid is not null) ";
				$parameters['paging_group_uuid'] = $paging_group_uuid;
			}
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['pattern_plain'] = $paging_group_extension;
			$parameters['pattern_exact'] = '^'.$paging_group_extension.'$';
			$row = $database->select($sql, $parameters, 'row');
			unset($sql, $parameters);
			if (isset($row['count']) && (int) $row['count'] > 0) {
				message::add($text['message-dialplan_destination_exists'], 'negative');
				header('Location: paging_edit.php'.(!empty($paging_group_uuid) ? '?id='.urlencode($paging_group_uuid) : ''));
				exit;
			}

		//get existing waiver state before save
			$existing_waiver_enabled = 'false';
			$existing_waiver_accept_user = null;
			$existing_waiver_accept_date = null;
			$existing_waiver_remove_user = null;
			$existing_waiver_remove_date = null;
			if (!empty($paging_group_uuid) && is_uuid($paging_group_uuid)) {
				$sql = "select paging_group_waiver_enabled, paging_group_waiver_accept_user, paging_group_waiver_accept_date, paging_group_waiver_remove_user, paging_group_waiver_remove_date ";
				$sql .= "from v_paging_groups ";
				$sql .= "where domain_uuid = :domain_uuid and paging_group_uuid = :paging_group_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
				$parameters['paging_group_uuid'] = $paging_group_uuid;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row)) {
					$existing_waiver_enabled = $row['paging_group_waiver_enabled'] ?? 'false';
					$existing_waiver_accept_user = $row['paging_group_waiver_accept_user'] ?? null;
					$existing_waiver_accept_date = $row['paging_group_waiver_accept_date'] ?? null;
					$existing_waiver_remove_user = $row['paging_group_waiver_remove_user'] ?? null;
					$existing_waiver_remove_date = $row['paging_group_waiver_remove_date'] ?? null;
				}
				unset($sql, $parameters, $row);
			}
			$existing_waiver_enabled = ($existing_waiver_enabled === true || $existing_waiver_enabled === 't' || $existing_waiver_enabled === 'true' || $existing_waiver_enabled === '1') ? 'true' : 'false';
			$paging_group_waiver_enabled = ($paging_group_waiver_enabled_posted == 'true' ? 'true' : 'false');

		//auto answer options are only available after waiver acknowledgement
			if ($paging_group_waiver_enabled != 'true') {
				$paging_group_auto_answer = 'default';
			}

		//two-way intercom requires waiver acknowledgement
			if ($paging_group_type == 'intercom' && $paging_group_waiver_enabled != 'true') {
				$paging_group_type = 'page';
				message::add($text['message-waiver_required'], 'negative');
			}
			if ($existing_waiver_enabled == 'true' && $paging_group_waiver_enabled != 'true') {
				$paging_group_type = 'page';
			}

		//create uuid for add
			if (empty($paging_group_uuid)) {
				$paging_group_uuid = uuid();
			}

		//get or create the dialplan uuid
			if (!is_uuid($dialplan_uuid) && !empty($paging_group_uuid) && is_uuid($paging_group_uuid)) {
				$sql = "select dialplan_uuid from v_paging_groups ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and paging_group_uuid = :paging_group_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
				$parameters['paging_group_uuid'] = $paging_group_uuid;
				$dialplan_uuid = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);
			}
			if (!is_uuid($dialplan_uuid)) {
				$dialplan_uuid = uuid();
			}

		//build enabled paging member list for page.lua
			$paging_member_numbers = [];
			if (is_array($paging_group_destinations)) {
				foreach ($paging_group_destinations as $destination) {
					$destination_number = trim($destination['destination_number'] ?? '');
					$destination_enabled = $destination['destination_enabled'] ?? 'false';
					$destination_description = trim($destination['destination_description'] ?? '');
					if ($destination_number != '' && ($destination_enabled === true || $destination_enabled == 'true' || $destination_enabled == 't' || $destination_enabled == '1')) {
						$paging_member_numbers[] = $destination_number;
					}
				}
			}
			$paging_member_numbers = array_values(array_unique($paging_member_numbers));
			$paging_destinations = implode(',', $paging_member_numbers);

		//save the paging group
			$array['paging_groups'][0]['paging_group_uuid'] = $paging_group_uuid;
			$array['paging_groups'][0]['domain_uuid'] = $domain_uuid;
			$array['paging_groups'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['paging_groups'][0]['paging_group_extension'] = $paging_group_extension;
			$array['paging_groups'][0]['paging_group_name'] = $paging_group_name;
			$array['paging_groups'][0]['paging_group_cid_name'] = $paging_group_cid_name;
			$array['paging_groups'][0]['paging_group_cid_number'] = $paging_group_cid_number;
			$array['paging_groups'][0]['paging_group_type'] = $paging_group_type;
			$array['paging_groups'][0]['paging_group_pin_number'] = $paging_group_pin_number;
			$array['paging_groups'][0]['paging_group_recording_uuid'] = is_uuid($paging_group_recording_uuid) ? $paging_group_recording_uuid : null;
			$array['paging_groups'][0]['paging_group_announcement_source'] = $paging_group_announcement_source;
			$array['paging_groups'][0]['paging_group_announcement_sound'] = $paging_group_announcement_sound;
			$array['paging_groups'][0]['paging_group_announcement_recording_uuid'] = is_uuid($paging_group_announcement_recording_uuid) ? $paging_group_announcement_recording_uuid : null;
			$array['paging_groups'][0]['paging_group_timeout'] = $paging_group_timeout;
			$array['paging_groups'][0]['paging_group_skip_busy'] = $paging_group_skip_busy;
			$array['paging_groups'][0]['paging_group_registered_only'] = $paging_group_registered_only;
			$array['paging_groups'][0]['paging_group_include_originator'] = $paging_group_include_originator;
			$array['paging_groups'][0]['paging_group_auto_answer'] = $paging_group_auto_answer;
			$array['paging_groups'][0]['paging_group_waiver_enabled'] = $paging_group_waiver_enabled;
			$waiver_accept_user = $existing_waiver_accept_user;
			$waiver_accept_date = $existing_waiver_accept_date;
			$waiver_remove_user = $existing_waiver_remove_user;
			$waiver_remove_date = $existing_waiver_remove_date;
			if ($existing_waiver_enabled != 'true' && $paging_group_waiver_enabled == 'true') {
				$waiver_accept_user = $_SESSION['user_uuid'];
				$waiver_accept_date = date('Y-m-d H:i:s');
				$waiver_remove_user = null;
				$waiver_remove_date = null;
			}
			if ($existing_waiver_enabled == 'true' && $paging_group_waiver_enabled != 'true') {
				$waiver_accept_user = null;
				$waiver_accept_date = null;
				$waiver_remove_user = $_SESSION['user_uuid'];
				$waiver_remove_date = date('Y-m-d H:i:s');
			}
			$array['paging_groups'][0]['paging_group_waiver_accept_user'] = $waiver_accept_user;
			$array['paging_groups'][0]['paging_group_waiver_accept_date'] = $waiver_accept_date;
			$array['paging_groups'][0]['paging_group_waiver_remove_user'] = $waiver_remove_user;
			$array['paging_groups'][0]['paging_group_waiver_remove_date'] = $waiver_remove_date;
			$array['paging_groups'][0]['paging_group_enabled'] = $paging_group_enabled;
			$array['paging_groups'][0]['paging_group_description'] = $paging_group_description;

		//build the XML dialplan
			$dialplan_xml = "<extension name=\"".xml::sanitize($paging_group_name)."\" continue=\"false\" uuid=\"".xml::sanitize($dialplan_uuid)."\">\n";
			$dialplan_xml .= "      <condition field=\"destination_number\" expression=\"^".xml::sanitize($paging_group_extension)."$\">\n";
			$dialplan_xml .= "              <action application=\"set\" data=\"destinations=".xml::sanitize($paging_destinations)."\"/>\n";
			$dialplan_xml .= "              <action application=\"set\" data=\"check_destination_status=true\"/>\n";
			$dialplan_xml .= "              <action application=\"set\" data=\"mute=".($paging_group_type == 'intercom' ? 'false' : 'true')."\"/>\n";
			if (!empty($paging_group_pin_number)) {
				$dialplan_xml .= "              <action application=\"set\" data=\"pin_number=".xml::sanitize($paging_group_pin_number)."\"/>\n";
			}
			if (!empty($paging_group_timeout) && is_numeric($paging_group_timeout)) {
				$dialplan_xml .= "              <action application=\"set\" data=\"api_hangup_hook=conference page-\${destination_number}@\${domain_name} hup all\"/>\n";
				$dialplan_xml .= "              <action application=\"set\" data=\"execute_on_answer=sched_hangup +".xml::sanitize($paging_group_timeout)." allotted_timeout\"/>\n";
			}
			if (!empty($paging_group_cid_name)) {
				$dialplan_xml .= "              <action application=\"set\" data=\"caller_id_name=".xml::sanitize($paging_group_cid_name)."\"/>\n";
			}
			else {
				$dialplan_xml .= "              <action application=\"set\" data=\"caller_id_name=".xml::sanitize($paging_group_type)."\"/>\n";
			}
			if (!empty($paging_group_cid_number)) {
				$dialplan_xml .= "              <action application=\"set\" data=\"caller_id_number=".xml::sanitize($paging_group_cid_number)."\"/>\n";
			}
			if ($paging_group_auto_answer == 'yealink') {
				$dialplan_xml .= "              <action application=\"set\" data=\"auto_answer=call_info\"/>\n";
				$dialplan_xml .= "              <action application=\"set\" data=\"alert_info=auto_answer\"/>\n";
			}
			else if ($paging_group_auto_answer == 'disabled') {
				$dialplan_xml .= "              <action application=\"set\" data=\"auto_answer=call_info\"/>\n";
				$dialplan_xml .= "              <action application=\"set\" data=\"alert_info=ring_answer\"/>\n";
			}
			else {
				$dialplan_xml .= "              <action application=\"set\" data=\"auto_answer=call_info\"/>\n";
				$dialplan_xml .= "              <action application=\"set\" data=\"alert_info=ring_answer\"/>\n";
			}
			if ($paging_group_announcement_source == 'sound' && !empty($paging_group_announcement_sound)) {
				$dialplan_xml .= "              <action application=\"set\" data=\"recording_filename=\$\${sounds_dir}/".xml::sanitize($paging_group_announcement_sound)."\"/>\n";
			}
			else if ($paging_group_announcement_source == 'recording' && is_uuid($paging_group_announcement_recording_uuid)) {
				$sql = "select recording_filename from v_recordings ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "and recording_uuid = :recording_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
				$parameters['recording_uuid'] = $paging_group_announcement_recording_uuid;
				$announcement_recording_filename = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);
				if (!empty($announcement_recording_filename)) {
					$dialplan_xml .= "              <action application=\"set\" data=\"recording_filename=".xml::sanitize($settings->get('switch', 'recordings').'/'.$domain_name.'/'.$announcement_recording_filename)."\"/>\n";
				}
			}
			$dialplan_xml .= "              <action application=\"lua\" data=\"page.lua\"/>\n";
			$dialplan_xml .= "      </condition>\n";
			$dialplan_xml .= "</extension>\n";

		//build the dialplan array
			$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
			$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplans'][0]['dialplan_name'] = $paging_group_name;
			$array['dialplans'][0]['dialplan_number'] = $paging_group_extension;
			$array['dialplans'][0]['dialplan_context'] = $domain_name;
			$array['dialplans'][0]['dialplan_continue'] = 'false';
			$array['dialplans'][0]['dialplan_xml'] = $dialplan_xml;
			$array['dialplans'][0]['dialplan_order'] = '101';
			$array['dialplans'][0]['dialplan_enabled'] = $paging_group_enabled;
			$array['dialplans'][0]['dialplan_description'] = $paging_group_description;
			$array['dialplans'][0]['app_uuid'] = 'bae044dd-e773-471c-a890-5220ebca3bc9';


			$p = permissions::new();
			$p->add('dialplan_add', 'temp');
			$p->add('dialplan_edit', 'temp');

		//save to the data
			$database->save($array);
			$message = $database->message;
			unset($array);

		//delete checked destinations
			if (is_array($paging_group_destinations_delete) && @sizeof($paging_group_destinations_delete) != 0) {
				foreach ($paging_group_destinations_delete as $delete_row) {
					if (!empty($delete_row['checked']) && !empty($delete_row['uuid']) && is_uuid($delete_row['uuid'])) {
						$array['paging_group_destinations'][]['paging_group_destination_uuid'] = $delete_row['uuid'];
					}
				}
				if (!empty($array)) {
					$database->delete($array);
					unset($array);
				}
			}

		//save the destinations
			if (is_array($paging_group_destinations)) {
				$order = 0;
				foreach ($paging_group_destinations as $destination) {
					$paging_group_destination_uuid = $destination['paging_group_destination_uuid'] ?? '';
					$destination_number = trim($destination['destination_number'] ?? '');
					$destination_enabled = $destination['destination_enabled'] ?? 'false';
					$destination_description = trim($destination['destination_description'] ?? '');

					if (empty($destination_number)) {
						continue;
					}

					$order += 10;
					$x = count($array['paging_group_destinations'] ?? []);
					$array['paging_group_destinations'][$x]['paging_group_destination_uuid'] = is_uuid($paging_group_destination_uuid) ? $paging_group_destination_uuid : uuid();
					$array['paging_group_destinations'][$x]['paging_group_uuid'] = $paging_group_uuid;
					$array['paging_group_destinations'][$x]['domain_uuid'] = $domain_uuid;
					$array['paging_group_destinations'][$x]['destination_number'] = $destination_number;
					$array['paging_group_destinations'][$x]['destination_order'] = $order;
					$array['paging_group_destinations'][$x]['destination_enabled'] = $destination_enabled;
					$array['paging_group_destinations'][$x]['destination_description'] = $destination_description;
				}
				if (!empty($array)) {
					$database->save($array);
					unset($array);
				}
			}

		//remove the temporary permissions
			$p->delete('dialplan_add', 'temp');
			$p->delete('dialplan_edit', 'temp');

		//apply settings reminder
			$_SESSION['reload_xml'] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete('dialplan:'.$domain_name);

		//redirect
			message::add($action == 'add' ? $text['message-add'] : $text['message-update']);
			header('Location: paging.php');
			exit;
	}

//get existing data
	if (!empty($paging_group_uuid)) {
		$sql = "select * from v_paging_groups ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and paging_group_uuid = :paging_group_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['paging_group_uuid'] = $paging_group_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$paging_group_extension = $row['paging_group_extension'];
			$paging_group_name = $row['paging_group_name'];
			$dialplan_uuid = $row['dialplan_uuid'] ?? '';
			$paging_group_cid_name = $row['paging_group_cid_name'] ?? '';
			$paging_group_cid_number = $row['paging_group_cid_number'] ?? '';
			$paging_group_type = $row['paging_group_type'] ?? 'page';
			$paging_group_pin_number = $row['paging_group_pin_number'] ?? '';
			$paging_group_recording_uuid = $row['paging_group_recording_uuid'] ?? '';
			$paging_group_announcement_source = $row['paging_group_announcement_source'] ?? (!empty($paging_group_recording_uuid) ? 'recording' : 'none');
			$paging_group_announcement_sound = $row['paging_group_announcement_sound'] ?? '';
			$paging_group_announcement_recording_uuid = $row['paging_group_announcement_recording_uuid'] ?? $paging_group_recording_uuid;
			$paging_group_timeout = $row['paging_group_timeout'] ?? '30';
			$paging_group_skip_busy = $row['paging_group_skip_busy'] ?? 'true';
			$paging_group_registered_only = $row['paging_group_registered_only'] ?? 'true';
			$paging_group_include_originator = $row['paging_group_include_originator'] ?? 'false';
			$paging_group_auto_answer = $row['paging_group_auto_answer'] ?? 'default';
			$paging_group_waiver_enabled = $row['paging_group_waiver_enabled'] ?? 'false';
			$paging_group_waiver_accept_user = $row['paging_group_waiver_accept_user'] ?? null;
			$paging_group_waiver_accept_date = $row['paging_group_waiver_accept_date'] ?? null;
			$paging_group_waiver_remove_user = $row['paging_group_waiver_remove_user'] ?? null;
			$paging_group_waiver_remove_date = $row['paging_group_waiver_remove_date'] ?? null;
			$paging_group_enabled = $row['paging_group_enabled'];
			$paging_group_description = $row['paging_group_description'];
		}
		else {
			message::add($text['message-invalid_uuid'], 'negative');
			header('Location: paging.php');
			exit;
		}
		unset($sql, $parameters, $row);

		$sql = "select * from v_paging_group_destinations ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and paging_group_uuid = :paging_group_uuid ";
		$sql .= "order by destination_order asc, destination_number asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['paging_group_uuid'] = $paging_group_uuid;
		$paging_group_destinations = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//normalize destinations and add blank rows similar to Ring Groups
	if (!is_array($paging_group_destinations)) {
		$paging_group_destinations = [];
	}
	if (count($paging_group_destinations) == 0) {
		$rows = 5;
		$show_destination_delete = false;
	}
	else {
		$rows = 1;
		$show_destination_delete = true;
	}
	for ($i = 0; $i < $rows; $i++) {
		$paging_group_destinations[] = [
			'paging_group_destination_uuid' => '',
			'destination_number' => '',
			'destination_order' => '',
			'destination_enabled' => 'false',
			'destination_description' => '',
		];
	}

//get the extensions and the users assigned to them - copied pattern from Ring Groups
	$sql = "select ";
	$sql .= "e.extension, ";
	$sql .= "u.username ";
	$sql .= "from v_extensions e ";
	$sql .= "left join v_extension_users eu on e.extension_uuid = eu.extension_uuid ";
	$sql .= "left join v_users u on eu.user_uuid = u.user_uuid and u.user_enabled = true ";
	$sql .= "where e.domain_uuid = :domain_uuid ";
	$sql .= "order by e.extension asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$extension_users = [];
	if (is_array($extensions)) {
		foreach ($extensions as $row) {
			$ext = $row['extension'];
			if (!isset($extension_users[$ext])) {
				$extension_users[$ext] = ['extension' => $ext, 'users' => []];
			}
			if (!empty($row['username'])) {
				$extension_users[$ext]['users'][] = $row['username'];
			}
		}
	}

//get sounds for announcement select
	$sounds = new sounds;
	$audio_files = $sounds->get();
	$sound_files = [];
	if (!empty($audio_files['sounds']) && is_array($audio_files['sounds'])) {
		$sound_files = $audio_files['sounds'];
	}

//get recordings for announcement select
	$sql = "select recording_uuid, recording_name, recording_filename ";
	$sql .= "from v_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by recording_name asc, recording_filename asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$recordings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);
	if (!is_array($recordings)) {
		$recordings = [];
	}

//get waiver audit user display names
	$waiver_accept_username = '';
	$waiver_remove_username = '';
	$user_uuids = [];
	if (!empty($paging_group_waiver_accept_user) && is_uuid($paging_group_waiver_accept_user)) { $user_uuids[] = $paging_group_waiver_accept_user; }
	if (!empty($paging_group_waiver_remove_user) && is_uuid($paging_group_waiver_remove_user)) { $user_uuids[] = $paging_group_waiver_remove_user; }
	$user_uuids = array_values(array_unique($user_uuids));
	if (count($user_uuids) > 0) {
		$placeholders = [];
		$parameters = [];
		foreach ($user_uuids as $index => $user_uuid) {
			$key = 'user_uuid_'.$index;
			$placeholders[] = ':'.$key;
			$parameters[$key] = $user_uuid;
		}
		$sql = "select user_uuid, username from v_users where user_uuid in (".implode(',', $placeholders).") ";
		$users = $database->select($sql, $parameters, 'all');
		if (is_array($users)) {
			foreach ($users as $user) {
				if ($user['user_uuid'] == $paging_group_waiver_accept_user) { $waiver_accept_username = $user['username']; }
				if ($user['user_uuid'] == $paging_group_waiver_remove_user) { $waiver_remove_username = $user['username']; }
			}
		}
		unset($sql, $parameters, $users);
	}

//set the title
	$document['title'] = $text['title-paging_group'];

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	require_once "resources/header.php";

//audio playback javascript
	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		echo "<script type='text/javascript' language='JavaScript'>\n";
		echo "	function set_playable(id, audio_selected, audio_type) {\n";
		echo "		if (!audio_selected) { audio_selected = ''; }\n";
		echo "		file_ext = audio_selected.split('.').pop();\n";
		echo "		var mime_type = '';\n";
		echo "		switch (file_ext) {\n";
		echo "			case 'wav': mime_type = 'audio/wav'; break;\n";
		echo "			case 'mp3': mime_type = 'audio/mpeg'; break;\n";
		echo "			case 'ogg': mime_type = 'audio/ogg'; break;\n";
		echo "		}\n";
		echo "		if (mime_type != '' && (audio_type == 'recordings' || audio_type == 'sounds')) {\n";
		echo "			if (audio_type == 'recordings') {\n";
		echo "				$('#recording_audio_' + id).attr('src', '../recordings/recordings.php?action=download&type=rec&filename=' + audio_selected);\n";
		echo "			}\n";
		echo "			else if (audio_type == 'sounds') {\n";
		echo "				$('#recording_audio_' + id).attr('src', '../switch/sounds.php?action=download&filename=' + audio_selected);\n";
		echo "			}\n";
		echo "			$('#recording_audio_' + id).attr('type', mime_type);\n";
		echo "			$('#recording_button_' + id).show();\n";
		echo "		}\n";
		echo "		else {\n";
		echo "			$('#recording_button_' + id).hide();\n";
		echo "			$('#recording_audio_' + id).attr('src','').attr('type','');\n";
		echo "		}\n";
		echo "	}\n";
		echo "</script>\n";
	}

//advanced display javascript
	echo "<script type='text/javascript'>\n";
	echo "function show_advanced_config() {\n";
	echo "\tconst rows = document.querySelectorAll('.advanced-row');\n";
	echo "\trows.forEach(function(row) { row.style.display = ''; });\n";
	echo "\tconst button = document.getElementById('btn_advanced');\n";
	echo "\tif (button) { button.style.display = 'none'; }\n";
	echo "}\n";
	echo "function update_announcement_source() {\n";
	echo "\tconst source = document.getElementById('paging_group_announcement_source');\n";
	echo "\tconst soundRow = document.getElementById('paging_group_announcement_sound_row');\n";
	echo "\tconst recordingRow = document.getElementById('paging_group_announcement_recording_row');\n";
	echo "\tif (soundRow) { soundRow.style.display = source && source.value == 'sound' ? '' : 'none'; }\n";
	echo "\tif (recordingRow) { recordingRow.style.display = source && source.value == 'recording' ? '' : 'none'; }\n";
	echo "}\n";
	echo "document.addEventListener('DOMContentLoaded', function() {\n";
	echo "\tupdate_announcement_source();\n";
	echo "\tconst wrappers = document.querySelectorAll('.searchable_select_wrapper');\n";
	echo "\twrappers.forEach(wrapper => {\n";
	echo "\t\tconst input = wrapper.querySelector('.extension_search_input');\n";
	echo "\t\tconst hidden_select = wrapper.querySelector('.extension_hidden_select');\n";
	echo "\t\tconst results = wrapper.querySelector('.search_results');\n";
	echo "\t\tif (!input || !hidden_select || !results) return;\n";
	echo "\t\tconst options = Array.from(hidden_select.querySelectorAll('option'));\n";
	echo "\t\tfunction render_results() {\n";
	echo "\t\t\tdocument.querySelectorAll('.search_results').forEach(dropdown => { dropdown.style.display = 'none'; });\n";
	echo "\t\t\tresults.style.display = 'block';\n";
	echo "\t\t\tconst term = input.value.trim().toLowerCase();\n";
	echo "\t\t\tresults.innerHTML = '';\n";
	echo "\t\t\toptions.forEach(option => {\n";
	echo "\t\t\t\tconst extension_value = option.value.trim().toLowerCase();\n";
	echo "\t\t\t\tconst users = (option.getAttribute('data-users') || '').split(',').map(u => u.trim()).filter(Boolean);\n";
	echo "\t\t\t\tconst users_lower = users.map(user => user.toLowerCase());\n";
	echo "\t\t\t\tif (term === '' || extension_value.includes(term) || users_lower.some(user => user.includes(term))) {\n";
	echo "\t\t\t\t\tconst item = document.createElement('div');\n";
	echo "\t\t\t\t\titem.className = 'search_result_item';\n";
	echo "\t\t\t\t\tconst extension = document.createElement('div');\n";
	echo "\t\t\t\t\textension.className = 'search_result_name';\n";
	echo "\t\t\t\t\textension.textContent = option.value;\n";
	echo "\t\t\t\t\tconst username = document.createElement('div');\n";
	echo "\t\t\t\t\tusername.className = 'search_result_description';\n";
	echo "\t\t\t\t\tusername.textContent = option.getAttribute('data-users') || '';\n";
	echo "\t\t\t\t\titem.appendChild(extension);\n";
	echo "\t\t\t\t\titem.appendChild(username);\n";
	echo "\t\t\t\t\titem.addEventListener('click', () => {\n";
	echo "\t\t\t\t\t\tinput.value = option.value;\n";
	echo "\t\t\t\t\t\thidden_select.value = option.value;\n";
	echo "\t\t\t\t\t\tresults.style.display = 'none';\n";
	echo "\t\t\t\t\t\tinput.dispatchEvent(new Event('input', { bubbles: true }));\n";
	echo "\t\t\t\t\t});\n";
	echo "\t\t\t\t\tresults.appendChild(item);\n";
	echo "\t\t\t\t}\n";
	echo "\t\t\t});\n";
	echo "\t\t}\n";
	echo "\t\tinput.addEventListener('focus', render_results);\n";
	echo "\t\tinput.addEventListener('input', render_results);\n";
	echo "\t});\n";
	echo "});\n";
	echo "</script>\n";

//show the content
	echo "<form method='post' name='frm' id='frm' action=''>\n";
	echo "<input type='hidden' name='paging_group_uuid' value='".escape($paging_group_uuid)."'>\n";
	echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "\t<div class='heading'><b>".$text['title-paging_group']."</b></div>\n";
	echo "\t<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$button_back,'icon'=>$settings->get('theme', 'button_icon_back'),'link'=>'paging.php']);
	echo button::create(['type'=>'submit','label'=>$button_save,'icon'=>$settings->get('theme', 'button_icon_save'),'form'=>'frm']);
	echo "\t</div>\n";
	echo "\t<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

//basic fields
	echo "<tr>\n";
	echo "\t<td class='vncellreq' valign='top'>".$text['label-name']."</td>\n";
	echo "\t<td class='vtable'>\n";
	echo "\t\t<input class='formfld' type='text' name='paging_group_name' maxlength='255' value='".escape($paging_group_name ?? '')."' required='required'>\n";
	echo "\t\t<br />".$text['description-name']."\n";
	echo "\t</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "\t<td class='vncellreq' valign='top'>".$text['label-extension']."</td>\n";
	echo "\t<td class='vtable'>\n";
	echo "\t\t<input class='formfld' type='text' name='paging_group_extension' maxlength='255' value='".escape($paging_group_extension ?? '')."' required='required' placeholder='".escape($settings->get('paging', 'extension_range', '') ?? '')."'>\n";
	echo "\t\t<br />".$text['description-extension']."\n";
	echo "\t</td>\n";
	echo "</tr>\n";
//paging members - Ring Groups style block
	echo "<tr>\n";
	echo "\t<td class='vncellreq' valign='top'>".$text['label-members']."</td>\n";
	echo "\t<td class='vtable' align='left'>\n";
	echo "\t\t<table border='0' cellpadding='0' cellspacing='0'>\n";
	echo "\t\t\t<tr>\n";
	echo "\t\t\t\t<td class='vtable'>".$text['label-member_extension']."</td>\n";
	echo "\t\t\t\t<td class='vtable'>".$text['label-enabled']."</td>\n";
	echo "\t\t\t\t<td class='vtable'>".$text['label-description']."</td>\n";
	if ($show_destination_delete && permission_exists('paging_group_destination_delete')) {
		echo "\t\t\t\t<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_destinations', 'delete_toggle_destinations');\" onmouseout=\"swap_display('delete_label_destinations', 'delete_toggle_destinations');\">\n";
		echo "\t\t\t\t\t<span id='delete_label_destinations'>".$text['label-delete']."</span>\n";
		echo "\t\t\t\t\t<span id='delete_toggle_destinations'><input type='checkbox' id='checkbox_all_destinations' name='checkbox_all' onclick=\"edit_all_toggle('destinations');\"></span>\n";
		echo "\t\t\t\t</td>\n";
	}
	echo "\t\t\t</tr>\n";

	$x = 0;
	foreach ($paging_group_destinations as $row) {
		$row['destination_number'] = $row['destination_number'] ?? '';
		$row['destination_description'] = $row['destination_description'] ?? '';
		$row['destination_enabled'] = ($row['destination_enabled'] === true || $row['destination_enabled'] === 't' || $row['destination_enabled'] === 'true' || $row['destination_enabled'] === '1') ? true : false;

		if (!empty($row['paging_group_destination_uuid']) && is_uuid($row['paging_group_destination_uuid'])) {
			echo "\t\t\t<input name='paging_group_destinations[".$x."][paging_group_destination_uuid]' type='hidden' value=\"".escape($row['paging_group_destination_uuid'])."\">\n";
		}

		echo "\t\t\t<tr>\n";
		echo "\t\t\t\t<td class='formfld'>\n";
		$oninput = empty($row['paging_group_destination_uuid']) ? "oninput=\"document.getElementById('paging_group_destinations_".$x."_destination_enabled').value = (this.value != '' ? true : false);\"" : null;
		echo "\t\t\t\t\t<div class='searchable_select_wrapper'>\n";
		echo "\t\t\t\t\t\t<input type='text' name='paging_group_destinations[".$x."][destination_number]' class='formfld extension_search_input' value='".escape($row['destination_number'])."' ".$oninput.">\n";
		echo "\t\t\t\t\t\t<div class='search_results'></div>\n";
		echo "\t\t\t\t\t\t<select class='extension_hidden_select' style='display:none;'>\n";
		foreach ($extension_users as $ext_data) {
			$users_string = implode(', ', array_map('htmlspecialchars', $ext_data['users']));
			echo "\t\t\t\t\t\t\t<option value='".escape($ext_data['extension'])."' data-users='".$users_string."'>".escape($ext_data['extension'])."</option>\n";
		}
		echo "\t\t\t\t\t\t</select>\n";
		echo "\t\t\t\t\t</div>\n";
		echo "\t\t\t\t</td>\n";
		echo "\t\t\t\t<td class='formfld'>\n";
		if ($input_toggle_style_switch) { echo "\t\t\t\t\t<span class='switch'>\n"; }
		echo "\t\t\t\t\t<select class='formfld' id='paging_group_destinations_".$x."_destination_enabled' name='paging_group_destinations[".$x."][destination_enabled]'>\n";
		echo "\t\t\t\t\t\t<option value='true' ".($row['destination_enabled'] == true ? "selected='selected'" : null).">".$label_true."</option>\n";
		echo "\t\t\t\t\t\t<option value='false' ".($row['destination_enabled'] == false ? "selected='selected'" : null).">".$label_false."</option>\n";
		echo "\t\t\t\t\t</select>\n";
		if ($input_toggle_style_switch) { echo "\t\t\t\t\t<span class='slider'></span></span>\n"; }
		echo "\t\t\t\t</td>\n";
		echo "\t\t\t\t<td class='formfld'>\n";
		echo "\t\t\t\t\t<input type='text' name='paging_group_destinations[".$x."][destination_description]' class='formfld' value='".escape($row['destination_description'])."'>\n";
		echo "\t\t\t\t</td>\n";
		if ($show_destination_delete && permission_exists('paging_group_destination_delete')) {
			if (!empty($row['paging_group_destination_uuid']) && is_uuid($row['paging_group_destination_uuid'])) {
				echo "\t\t\t\t<td class='vtable' style='text-align: center; padding-bottom: 3px;'>";
				echo "<input type='checkbox' name='paging_group_destinations_delete[".$x."][checked]' value='true' class='chk_delete checkbox_destinations' onclick=\"edit_delete_action('destinations');\">\n";
				echo "<input type='hidden' name='paging_group_destinations_delete[".$x."][uuid]' value='".escape($row['paging_group_destination_uuid'])."' />\n";
			}
			else {
				echo "\t\t\t\t<td>\n";
			}
			echo "\t\t\t\t</td>\n";
		}
		echo "\t\t\t</tr>\n";
		$x++;
	}
	echo "\t\t</table>\n";
	echo "\t\t".$text['description-members']."\n";
	echo "\t\t<br />\n";
	echo "\t</td>\n";
	echo "</tr>\n";

//paging mode
	$waiver_is_enabled = ($paging_group_waiver_enabled === true || $paging_group_waiver_enabled === 't' || $paging_group_waiver_enabled === 'true' || $paging_group_waiver_enabled === '1');
	echo "<tr>";
	echo "	<td class='vncell' valign='top'>".$text['label-paging_mode']."</td>";
	echo "	<td class='vtable'>";
	echo "		<select class='formfld' name='paging_group_type'>";
	echo "			<option value='page'".($paging_group_type == 'page' ? " selected='selected'" : null).">".$text['option-one_way_paging']."</option>";
	echo "			<option value='intercom'".($paging_group_type == 'intercom' ? " selected='selected'" : null).($waiver_is_enabled ? "" : " disabled='disabled'").">".$text['option-two_way_intercom'].($waiver_is_enabled ? "" : " - ".$text['option-waiver_required'])."</option>
";
	echo "		</select>
";
	echo "		<br />".$text['description-paging_mode']."
";
	echo "	</td>
";
	echo "</tr>
";

//pin
	echo "<tr>\n";
	echo "\t<td class='vncell' valign='top'>".$text['label-pin']."</td>\n";
	echo "\t<td class='vtable'>\n";
	echo "\t\t<input class='formfld' type='text' name='paging_group_pin_number' maxlength='32' value='".escape($paging_group_pin_number ?? '')."'>\n";
	echo "\t\t<br />".$text['description-pin']."\n";
	echo "\t</td>\n";
	echo "</tr>\n";

//announcement
	echo "<tr>";
	echo "	<td class='vncell' valign='top'>".$text['label-announcement_source']."</td>";
	echo "	<td class='vtable'>";
	echo "		<select class='formfld' id='paging_group_announcement_source' name='paging_group_announcement_source' onchange='update_announcement_source();'>";
	echo "			<option value='none'".($paging_group_announcement_source == 'none' ? " selected='selected'" : null).">".$text['option-none']."</option>";
	echo "			<option value='sound'".($paging_group_announcement_source == 'sound' ? " selected='selected'" : null).">".$text['option-sound']."</option>";
	echo "			<option value='recording'".($paging_group_announcement_source == 'recording' ? " selected='selected'" : null).">".$text['option-recording']."</option>";
	echo "		</select>";
	echo "		<br />".$text['description-announcement_source'];
	echo "	</td>";
	echo "</tr>";

//announcement sound
	$instance_id = 'paging_announcement_sound';
	$instance_value = $paging_group_announcement_sound;
	$playable = '';
	$mime_type = '';
	echo "<tr id='paging_group_announcement_sound_row'>\n";
	echo "\t<td class='vncell' valign='top'>".$text['label-sound']."</td>\n";
	echo "\t<td class='vtable'>\n";
	echo "\t\t<div class='playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' onclick=\"recording_play('".$instance_id."', document.getElementById('paging_group_announcement_sound').value, 'sounds');\" style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important; max-width: 480px;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></div>\n";
	echo "\t\t<select class='formfld searchable_select' id='paging_group_announcement_sound' name='paging_group_announcement_sound' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.value, 'sounds');\"" : null).">\n";
	echo "\t\t\t<option value=''></option>\n";
	foreach ($sound_files as $sound) {
		$sound_value = $sound['value'] ?? '';
		$sound_name = $sound['name'] ?? $sound_value;
		if (!empty($sound_value) && $paging_group_announcement_sound == $sound_value) {
			$playable = '../switch/sounds.php?action=download&filename='.$sound_value;
		}
		echo "\t\t\t<option value='".escape($sound_value)."'".($paging_group_announcement_sound == $sound_value ? " selected='selected'" : null).">".escape($sound_name)."</option>\n";
	}
	echo "\t\t</select>\n";
	if ((permission_exists('recording_play') || permission_exists('recording_download'))) {
		if (!empty($playable)) {
			switch (pathinfo($playable, PATHINFO_EXTENSION)) {
				case 'wav' : $mime_type = 'audio/wav'; break;
				case 'mp3' : $mime_type = 'audio/mpeg'; break;
				case 'ogg' : $mime_type = 'audio/ogg'; break;
			}
		}
		echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".($playable ?? '')."' type='".($mime_type ?? '')."'></audio>";
		echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$settings->get('theme', 'button_icon_play'),'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."', document.getElementById('paging_group_announcement_sound').value, 'sounds');"]);
	}
	echo "\t\t<br />".$text['description-sound']."\n";
	echo "\t</td>\n";
	echo "</tr>\n";

//announcement recording
	$instance_id = 'paging_announcement_recording';
	$selected_recording_filename = '';
	$playable = '';
	$mime_type = '';
	echo "<tr id='paging_group_announcement_recording_row'>\n";
	echo "\t<td class='vncell' valign='top'>".$text['label-recording']."</td>\n";
	echo "\t<td class='vtable'>\n";
	echo "\t\t<div class='playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' onclick=\"recording_play('".$instance_id."', document.getElementById('paging_group_announcement_recording_uuid').options[document.getElementById('paging_group_announcement_recording_uuid').selectedIndex].getAttribute('data-filename'), 'recordings');\" style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important; max-width: 480px;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></div>\n";
	echo "\t\t<select class='formfld searchable_select' id='paging_group_announcement_recording_uuid' name='paging_group_announcement_recording_uuid' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.options[this.selectedIndex].getAttribute('data-filename'), 'recordings');\"" : null).">\n";
	echo "\t\t\t<option value='' data-filename=''></option>\n";
	foreach ($recordings as $recording) {
		$recording_label = !empty($recording['recording_name']) ? $recording['recording_name'] : $recording['recording_filename'];
		$recording_filename = $recording['recording_filename'] ?? '';
		if ($paging_group_announcement_recording_uuid == $recording['recording_uuid']) {
			$selected_recording_filename = $recording_filename;
			$playable = '../recordings/recordings.php?action=download&type=rec&filename='.$recording_filename;
		}
		echo "\t\t\t<option value='".escape($recording['recording_uuid'])."' data-filename='".escape($recording_filename)."'".($paging_group_announcement_recording_uuid == $recording['recording_uuid'] ? " selected='selected'" : null).">".escape($recording_label)."</option>\n";
	}
	echo "\t\t</select>\n";
	if ((permission_exists('recording_play') || permission_exists('recording_download'))) {
		if (!empty($playable)) {
			switch (pathinfo($playable, PATHINFO_EXTENSION)) {
				case 'wav' : $mime_type = 'audio/wav'; break;
				case 'mp3' : $mime_type = 'audio/mpeg'; break;
				case 'ogg' : $mime_type = 'audio/ogg'; break;
			}
		}
		echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".($playable ?? '')."' type='".($mime_type ?? '')."'></audio>";
		echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$settings->get('theme', 'button_icon_play'),'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."', document.getElementById('paging_group_announcement_recording_uuid').options[document.getElementById('paging_group_announcement_recording_uuid').selectedIndex].getAttribute('data-filename'), 'recordings');"]);
	}
	if (count($recordings) == 0) {
		echo "\t\t<br />".$text['description-no_recordings']."\n";
	}
	else {
		echo "\t\t<br />".$text['description-recording']."\n";
	}
	echo "\t</td>\n";
	echo "</tr>\n";

//timeout
	echo "<tr>\n";
	echo "\t<td class='vncell' valign='top'>".$text['label-paging_timeout']."</td>\n";
	echo "\t<td class='vtable'>\n";
	echo "\t\t<select class='formfld' name='paging_group_timeout'>\n";
	foreach ([15, 30, 60, 90, 120, 180, 300] as $timeout) {
		echo "\t\t\t<option value='".$timeout."'".((string)$paging_group_timeout == (string)$timeout ? " selected='selected'" : null).">".$timeout." ".$text['label-seconds']."</option>\n";
	}
	echo "\t\t</select>\n";
	echo "\t\t<br />".$text['description-paging_timeout']."\n";
	echo "\t</td>\n";
	echo "</tr>\n";

//advanced button
	echo "<tr>\n";
	echo "\t<td class='vncell' valign='top'>&nbsp;</td>\n";
	echo "\t<td class='vtable'>".button::create(['type'=>'button','label'=>$button_advanced,'icon'=>'tools','id'=>'btn_advanced','onclick'=>'show_advanced_config();'])."</td>\n";
	echo "</tr>\n";

//advanced fields
	echo "<tr class='advanced-row' style='display: none;'>\n";
	echo "\t<td class='vncell' valign='top'>".$text['label-caller_id_name']."</td>\n";
	echo "\t<td class='vtable'><input class='formfld' type='text' name='paging_group_cid_name' maxlength='255' value='".escape($paging_group_cid_name ?? '')."'><br />".$text['description-caller_id_name']."</td>\n";
	echo "</tr>\n";
	echo "<tr class='advanced-row' style='display: none;'>\n";
	echo "\t<td class='vncell' valign='top'>".$text['label-caller_id_number']."</td>\n";
	echo "\t<td class='vtable'><input class='formfld' type='text' name='paging_group_cid_number' maxlength='255' value='".escape($paging_group_cid_number ?? '')."'><br />".$text['description-caller_id_number']."</td>\n";
	echo "</tr>\n";
		if ($waiver_is_enabled) {
	echo "<tr class='advanced-row' style='display: none;'>";
	echo "	<td class='vncell' valign='top'>".$text['label-auto_answer']."</td>";
	echo "	<td class='vtable'>";
	echo "		<select class='formfld' name='paging_group_auto_answer'>";
	echo "			<option value='default'".($paging_group_auto_answer == 'default' ? " selected='selected'" : null).">".$text['option-default']."</option>";
	echo "			<option value='yealink'".($paging_group_auto_answer == 'yealink' ? " selected='selected'" : null).">".$text['option-yealink']."</option>";
	echo "			<option value='disabled'".($paging_group_auto_answer == 'disabled' ? " selected='selected'" : null).">".$text['option-disabled']."</option>";
	echo "		</select>";
	echo "		<br />".$text['description-auto_answer'];
	echo "	</td>";
	echo "</tr>";
	}
	echo "<tr class='advanced-row' style='display: none;'>";
	echo "	<td class='vncell' valign='top'>".$text['label-two_way_waiver']."</td>";
	echo "	<td class='vtable'>";
	echo "		<div style='max-width: 760px; padding: 12px; border: 1px solid #ccc; border-radius: 4px;'>";
	echo "			<strong>".strtoupper($text['label-warning']).":</strong><br /><br />";
	echo "			".$text['description-waiver_microphone']."<br /><br />";
	echo "			".$text['description-waiver_legal']."<br /><br />";
	echo "			<label><input type='checkbox' name='paging_group_waiver_enabled' value='true'".($waiver_is_enabled ? " checked='checked'" : null)."> ".$text['label-waiver_on_file']."</label><br /><br />";
	if (!empty($paging_group_waiver_accept_date) && !empty($waiver_accept_username)) {
		echo "			".$text['label-accepted_by'].": ".escape($waiver_accept_username)."<br />";
		echo "			".$text['label-accepted'].": ".escape($paging_group_waiver_accept_date)."<br /><br />";
	}
	if (!empty($paging_group_waiver_remove_date) && !empty($waiver_remove_username)) {
		echo "			".$text['label-removed_by'].": ".escape($waiver_remove_username)."<br />";
		echo "			".$text['label-removed'].": ".escape($paging_group_waiver_remove_date)."<br /><br />";
	}
	echo "			".$text['description-waiver_save'];
	echo "		</div>";
	echo "	</td>";
	echo "</tr>";

//enabled
	echo "<tr>\n";
	echo "\t<td class='vncell' valign='top'>".$text['label-enabled']."</td>\n";
	echo "\t<td class='vtable'>\n";
	if ($input_toggle_style_switch) { echo "\t\t<span class='switch'>\n"; }
	echo "\t\t<select class='formfld' name='paging_group_enabled'>\n";
	echo "\t\t\t<option value='true'".(($paging_group_enabled == 'true' || $paging_group_enabled === true || $paging_group_enabled === 't') ? " selected='selected'" : null).">".$label_true."</option>\n";
	echo "\t\t\t<option value='false'".(($paging_group_enabled == 'false' || $paging_group_enabled === false || $paging_group_enabled === 'f') ? " selected='selected'" : null).">".$label_false."</option>\n";
	echo "\t\t</select>\n";
	if ($input_toggle_style_switch) { echo "\t\t<span class='slider'></span></span>\n"; }
	echo "\t</td>\n";
	echo "</tr>\n";

//description
	echo "<tr>\n";
	echo "\t<td class='vncell' valign='top'>".$text['label-description']."</td>\n";
	echo "\t<td class='vtable'>\n";
	echo "\t\t<textarea class='formfld' name='paging_group_description' rows='3'>".escape($paging_group_description ?? '')."</textarea>\n";
	echo "\t\t<br />".$text['description-description']."\n";
	echo "\t</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
