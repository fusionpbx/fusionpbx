<?php
/*
 * Contributor(s):
 * denisent dev team
 */
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
*/

//includes
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	//Allow the legacy view permission until existing installations import paging_group_view.
	if (!permission_exists('paging_group_view') && !permission_exists('paging_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set variables from HTTP GET parameters
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', ($_GET['order_by'] ?? 'paging_group_extension'));
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$sort = $order_by == 'paging_group_extension' ? 'natural' : null;
	$search = $_GET['search'] ?? '';

//build the query string
	$param = [];
	if (!empty($page)) {
		$param['page'] = $page;
	}
	if (!empty($_GET['order_by'])) {
		$param['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$param['order'] = $order;
	}
	if (!empty($search)) {
		$param['search'] = $search;
	}
	$query_string = http_build_query($param);

//set database and domain variables
	$database = new database;
	$domain_uuid = $_SESSION['domain_uuid'];
	$domain_name = $_SESSION['domain_name'] ?? $_SESSION['context'] ?? '';

//find the next extension from the paging extension range
	function paging_next_extension($database, $domain_uuid, $range) {
		$range = trim((string) $range);
		if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $range, $m)) {
			$start = (int) $m[1];
			$end = (int) $m[2];
		}
		else {
			return '';
		}

		$sql = "select paging_group_extension from v_paging_groups ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and paging_group_extension ~ '^[0-9]+$' ";
		$parameters['domain_uuid'] = $domain_uuid;
		$rows = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);

		$used = [];
		if (is_array($rows)) {
			foreach ($rows as $row) {
				$used[(int) $row['paging_group_extension']] = true;
			}
		}

		for ($i = $start; $i <= $end; $i++) {
			if (!isset($used[$i])) {
				return (string) $i;
			}
		}

		return '';
	}


//normalize boolean-like values
	function paging_bool($value) {
		return ($value === true || $value === 't' || $value === 'true' || $value === '1' || $value === 1);
	}

//build paging dialplan XML
	function paging_build_dialplan_xml($database, $settings, $domain_uuid, $domain_name, $paging_group, $paging_destinations) {
		$dialplan_xml = "<extension name=\"".xml::sanitize($paging_group['paging_group_name'])."\" continue=\"false\" uuid=\"".xml::sanitize($paging_group['dialplan_uuid'])."\">\n";
		$dialplan_xml .= "      <condition field=\"destination_number\" expression=\"^".xml::sanitize($paging_group['paging_group_extension'])."$\">\n";
		$dialplan_xml .= "              <action application=\"answer\" data=\"\"/>\n";
		$dialplan_xml .= "              <action application=\"set\" data=\"paging_group_uuid=".xml::sanitize($paging_group['paging_group_uuid'])."\"/>\n";
		$dialplan_xml .= "              <action application=\"set\" data=\"destinations=".xml::sanitize($paging_destinations)."\"/>\n";
		$dialplan_xml .= "              <action application=\"set\" data=\"check_destination_status=true\"/>\n";
		$dialplan_xml .= "              <action application=\"set\" data=\"mute=".(($paging_group['paging_group_type'] ?? 'page') == 'intercom' ? 'false' : 'true')."\"/>\n";
		if (!empty($paging_group['paging_group_pin_number'])) {
			$dialplan_xml .= "              <action application=\"set\" data=\"pin_number=".xml::sanitize($paging_group['paging_group_pin_number'])."\"/>\n";
		}
		if (!empty($paging_group['paging_group_timeout']) && is_numeric($paging_group['paging_group_timeout'])) {
			$dialplan_xml .= "              <action application=\"sched_hangup\" data=\"+".xml::sanitize($paging_group['paging_group_timeout'])." NORMAL_CLEARING\"/>\n";
		}
		if (!empty($paging_group['paging_group_cid_name'])) {
			$dialplan_xml .= "              <action application=\"set\" data=\"caller_id_name=".xml::sanitize($paging_group['paging_group_cid_name'])."\"/>\n";
		}
		if (!empty($paging_group['paging_group_cid_number'])) {
			$dialplan_xml .= "              <action application=\"set\" data=\"caller_id_number=".xml::sanitize($paging_group['paging_group_cid_number'])."\"/>\n";
		}
		if (($paging_group['paging_group_auto_answer'] ?? 'default') == 'yealink') {
			$dialplan_xml .= "              <action application=\"set\" data=\"auto_answer=call_info\"/>\n";
			$dialplan_xml .= "              <action application=\"set\" data=\"alert_info=auto_answer\"/>\n";
		}
		else {
			$dialplan_xml .= "              <action application=\"set\" data=\"auto_answer=call_info\"/>\n";
			$dialplan_xml .= "              <action application=\"set\" data=\"alert_info=ring_answer\"/>\n";
		}
		if (($paging_group['paging_group_announcement_source'] ?? '') == 'sound' && !empty($paging_group['paging_group_announcement_sound'])) {
			$dialplan_xml .= "              <action application=\"set\" data=\"recording_filename=\$\${sounds_dir}/".xml::sanitize($paging_group['paging_group_announcement_sound'])."\"/>\n";
		}
		if (($paging_group['paging_group_announcement_source'] ?? '') == 'recording' && !empty($paging_group['paging_group_announcement_recording_uuid']) && is_uuid($paging_group['paging_group_announcement_recording_uuid'])) {
			$sql = "select recording_filename from v_recordings ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and recording_uuid = :recording_uuid ";
			$parameters = [];
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['recording_uuid'] = $paging_group['paging_group_announcement_recording_uuid'];
			$announcement_recording_filename = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);
			if (!empty($announcement_recording_filename)) {
				$dialplan_xml .= "              <action application=\"set\" data=\"recording_filename=".xml::sanitize($settings->get('switch', 'recordings').'/'.$domain_name.'/'.$announcement_recording_filename)."\"/>\n";
			}
		}
		$dialplan_xml .= "              <action application=\"lua\" data=\"page.lua\"/>\n";
		$dialplan_xml .= "      </condition>\n";
		$dialplan_xml .= "</extension>\n";
		return $dialplan_xml;
	}

//get posted data
	if (!empty($_POST['paging_groups'])) {
		$action = $_POST['action'] ?? '';
		$paging_groups = $_POST['paging_groups'];
	}

//process the http post data by action
	if (!empty($action) && !empty($paging_groups)) {

		//check the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: paging.php'.($query_string ? '?'.$query_string : ''));
				exit;
			}

		//copy
			if ($action == 'copy' && permission_exists('paging_group_add')) {
				foreach ($paging_groups as $row) {
					if (empty($row['checked']) || empty($row['paging_group_uuid']) || !is_uuid($row['paging_group_uuid'])) {
						continue;
					}

					//get the source paging group
						$sql = "select * from v_paging_groups ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and paging_group_uuid = :paging_group_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['paging_group_uuid'] = $row['paging_group_uuid'];
						$source = $database->select($sql, $parameters, 'row');
						unset($sql, $parameters);

					if (is_array($source) && @sizeof($source) != 0) {
						$new_uuid = uuid();
						$new_dialplan_uuid = uuid();
						$new_extension = paging_next_extension($database, $domain_uuid, $settings->get('paging', 'extension_range', ''));

						if (empty($new_extension)) {
							message::add($text['message-copy_range_unavailable'], 'negative');
							continue;
						}

						//copy the parent paging group fields
							$array['paging_groups'][0]['paging_group_uuid'] = $new_uuid;
							$array['paging_groups'][0]['domain_uuid'] = $domain_uuid;
							$array['paging_groups'][0]['dialplan_uuid'] = $new_dialplan_uuid;
							$array['paging_groups'][0]['paging_group_extension'] = $new_extension;
							$array['paging_groups'][0]['paging_group_name'] = trim(($source['paging_group_name'] ?? '').' (Copy)');

							$skip_fields = [
								'paging_group_uuid' => true,
								'domain_uuid' => true,
								'dialplan_uuid' => true,
								'paging_group_extension' => true,
								'paging_group_name' => true,
								'insert_date' => true,
								'insert_user' => true,
								'update_date' => true,
								'update_user' => true,
							];

							foreach ($source as $field_name => $field_value) {
								if (isset($skip_fields[$field_name])) {
									continue;
								}
								if (substr($field_name, 0, 13) == 'paging_group_') {
									$array['paging_groups'][0][$field_name] = $field_value;
								}
							}

						//copy the paging members
							$sql = "select paging_group_destination_uuid, destination_number, destination_order, destination_enabled ";
							$sql .= "from v_paging_group_destinations ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and paging_group_uuid = :paging_group_uuid ";
							$sql .= "order by destination_order asc, destination_number asc ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['paging_group_uuid'] = $row['paging_group_uuid'];
							$destinations = $database->select($sql, $parameters, 'all');
							unset($sql, $parameters);

							$paging_member_numbers = [];
							if (is_array($destinations)) {
								$y = 0;
								foreach ($destinations as $destination) {
									if (empty($destination['destination_number'])) {
										continue;
									}
									$paging_member_numbers[] = trim($destination['destination_number']);
									$array['paging_groups'][0]['paging_group_destinations'][$y]['paging_group_destination_uuid'] = uuid();
									$array['paging_groups'][0]['paging_group_destinations'][$y]['paging_group_uuid'] = $new_uuid;
									$array['paging_groups'][0]['paging_group_destinations'][$y]['domain_uuid'] = $domain_uuid;
									$array['paging_groups'][0]['paging_group_destinations'][$y]['destination_number'] = $destination['destination_number'];
									$array['paging_groups'][0]['paging_group_destinations'][$y]['destination_order'] = $destination['destination_order'];
									$array['paging_groups'][0]['paging_group_destinations'][$y]['destination_enabled'] = $destination['destination_enabled'];
									$y++;
								}
								unset($y);
							}

						//build the copied paging dialplan
							$copied_group = $source;
							$copied_group['paging_group_uuid'] = $new_uuid;
							$copied_group['domain_uuid'] = $domain_uuid;
							$copied_group['dialplan_uuid'] = $new_dialplan_uuid;
							$copied_group['paging_group_extension'] = $new_extension;
							$copied_group['paging_group_name'] = trim(($source['paging_group_name'] ?? '').' (Copy)');
							$paging_destinations = implode(',', $paging_member_numbers);
							$dialplan_xml = paging_build_dialplan_xml($database, $settings, $domain_uuid, $domain_name, $copied_group, $paging_destinations);

							$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
							$array['dialplans'][0]['dialplan_uuid'] = $new_dialplan_uuid;
							$array['dialplans'][0]['dialplan_name'] = $copied_group['paging_group_name'];
							$array['dialplans'][0]['dialplan_number'] = $new_extension;
							$array['dialplans'][0]['dialplan_context'] = $domain_name;
							$array['dialplans'][0]['dialplan_continue'] = 'false';
							$array['dialplans'][0]['dialplan_xml'] = $dialplan_xml;
							$array['dialplans'][0]['dialplan_order'] = '101';
							$array['dialplans'][0]['dialplan_enabled'] = paging_bool($source['paging_group_enabled'] ?? 'true') ? 'true' : 'false';
							$array['dialplans'][0]['dialplan_description'] = $source['paging_group_description'] ?? null;
							$array['dialplans'][0]['app_uuid'] = 'bae044dd-e773-471c-a890-5220ebca3bc9';
							unset($copied_group, $paging_destinations, $dialplan_xml, $paging_member_numbers);

						//save the copied paging group and members
							$database->app_name = 'paging';
							$database->app_uuid = 'bae044dd-e773-471c-a890-5220ebca3bc9';
							$p = permissions::new();
							$p->add('paging_group_add', 'temp');
							$p->add('paging_group_edit', 'temp');
							$p->add('paging_group_destination_add', 'temp');
							$p->add('paging_group_destination_edit', 'temp');
							$p->add('dialplan_add', 'temp');
							$p->add('dialplan_edit', 'temp');
							$database->save($array);
							$p->delete('paging_group_add', 'temp');
							$p->delete('paging_group_edit', 'temp');
							$p->delete('paging_group_destination_add', 'temp');
							$p->delete('paging_group_destination_edit', 'temp');
							$p->delete('dialplan_add', 'temp');
							$p->delete('dialplan_edit', 'temp');
							unset($array);
					}
				}

				$_SESSION['reload_xml'] = true;
				message::add($text['message-copy']);
			}

		//toggle
			if ($action == 'toggle' && permission_exists('paging_group_edit')) {
				foreach ($paging_groups as $row) {
					if (empty($row['checked']) || empty($row['paging_group_uuid']) || !is_uuid($row['paging_group_uuid'])) {
						continue;
					}

					$sql = "select paging_group_enabled from v_paging_groups ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and paging_group_uuid = :paging_group_uuid ";
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['paging_group_uuid'] = $row['paging_group_uuid'];
					$current_enabled = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);

					$enabled = ($current_enabled === true || $current_enabled === 't' || $current_enabled === 'true' || $current_enabled === '1') ? 'false' : 'true';

					$sql = "update v_paging_groups ";
					$sql .= "set paging_group_enabled = :paging_group_enabled, ";
					$sql .= "update_date = :update_date, ";
					$sql .= "update_user = :update_user ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and paging_group_uuid = :paging_group_uuid ";
					$parameters['paging_group_enabled'] = $enabled;
					$parameters['update_date'] = date('Y-m-d H:i:s');
					$parameters['update_user'] = $_SESSION['user_uuid'];
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['paging_group_uuid'] = $row['paging_group_uuid'];
					$database->execute($sql, $parameters);
					unset($sql, $parameters);

					//sync the linked dialplan enabled state
					$sql = "update v_dialplans ";
					$sql .= "set dialplan_enabled = :dialplan_enabled, ";
					$sql .= "update_date = :update_date, ";
					$sql .= "update_user = :update_user ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and dialplan_uuid = (";
					$sql .= "	select dialplan_uuid from v_paging_groups ";
					$sql .= "	where domain_uuid = :domain_uuid ";
					$sql .= "	and paging_group_uuid = :paging_group_uuid";
					$sql .= ") ";
					$parameters['dialplan_enabled'] = $enabled;
					$parameters['update_date'] = date('Y-m-d H:i:s');
					$parameters['update_user'] = $_SESSION['user_uuid'];
					$parameters['domain_uuid'] = $domain_uuid;
					$parameters['paging_group_uuid'] = $row['paging_group_uuid'];
					$database->execute($sql, $parameters);
					unset($sql, $parameters);
				}

				$_SESSION['reload_xml'] = true;
				message::add($text['message-update']);
			}

		//delete
			if ($action == 'delete' && permission_exists('paging_group_delete')) {
				foreach ($paging_groups as $row) {
					if (empty($row['checked']) || empty($row['paging_group_uuid']) || !is_uuid($row['paging_group_uuid'])) {
						continue;
					}

					//get the linked dialplan uuid before deleting the paging group
						$sql = "select dialplan_uuid from v_paging_groups ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and paging_group_uuid = :paging_group_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['paging_group_uuid'] = $row['paging_group_uuid'];
						$dialplan_uuid = $database->select($sql, $parameters, 'column');
						unset($sql, $parameters);

					//delete child destinations first
						$sql = "delete from v_paging_group_destinations ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and paging_group_uuid = :paging_group_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['paging_group_uuid'] = $row['paging_group_uuid'];
						$database->execute($sql, $parameters);
						unset($sql, $parameters);

					//delete paging group
						$sql = "delete from v_paging_groups ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and paging_group_uuid = :paging_group_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['paging_group_uuid'] = $row['paging_group_uuid'];
						$database->execute($sql, $parameters);
						unset($sql, $parameters);

					//delete linked dialplan
						if (!empty($dialplan_uuid) && is_uuid($dialplan_uuid)) {
							$sql = "delete from v_dialplans ";
							$sql .= "where domain_uuid = :domain_uuid ";
							$sql .= "and dialplan_uuid = :dialplan_uuid ";
							$parameters['domain_uuid'] = $domain_uuid;
							$parameters['dialplan_uuid'] = $dialplan_uuid;
							$database->execute($sql, $parameters);
							unset($sql, $parameters);
						}
						unset($dialplan_uuid);
				}

				$_SESSION['reload_xml'] = true;
				message::add($text['message-delete']);
			}

		header('Location: paging.php'.($query_string ? '?'.$query_string : ''));
		exit;
	}

//get total paging group count
	$sql = "select count(paging_group_uuid) from v_paging_groups ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $domain_uuid;
	$total_paging_groups = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//get filtered paging group count
	$sql = "select count(paging_group_uuid) from v_paging_groups ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $domain_uuid;
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= "lower(paging_group_name) like :search ";
		$sql .= "or lower(paging_group_extension) like :search ";
		$sql .= "or lower(paging_group_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.lower_case($search).'%';
	}
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = $settings->get('domain', 'paging', 50);
	list($paging_controls, $rows_per_page) = paging($num_rows, $query_string, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $query_string, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select paging_group_uuid, domain_uuid, paging_group_extension, paging_group_name, cast(paging_group_enabled as text), paging_group_description ";
	$sql .= "from v_paging_groups ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $domain_uuid;
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= "lower(paging_group_name) like :search ";
		$sql .= "or lower(paging_group_extension) like :search ";
		$sql .= "or lower(paging_group_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.lower_case($search).'%';
	}
	$sql .= order_by($order_by, $order, null, null, $sort);
	$sql .= limit_offset($rows_per_page, $offset);
	$paging_groups = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-paging'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-paging']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('paging_group_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','link'=>'paging_edit.php']);
	}
	if (permission_exists('paging_group_add') && $paging_groups) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('paging_group_edit') && $paging_groups) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('paging_group_delete') && $paging_groups) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<form method='get' action=''>\n";
	echo "<div class='search_bar'>\n";
	echo "	<input type='text' class='txt' style='width: 250px;' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\">\n";
	foreach ($param as $key => $value) {
		if ($key !== 'search' && $key !== 'page') {
			echo "	<input type='hidden' name='".escape($key)."' value='".escape($value)."'>\n";
		}
	}
	echo "	".button::create(['label'=>$text['button-search'],'icon'=>$settings->get('theme', 'button_icon_search'),'type'=>'submit','id'=>'btn_search'])."\n";
	if ($paging_controls_mini != '') {
		echo "	<span style='float: right;'>".$paging_controls_mini."</span>\n";
	}
	echo "</div>\n";
	echo "</form>\n";

	if (permission_exists('paging_group_add') && $paging_groups) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('paging_group_edit') && $paging_groups) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('paging_group_delete') && $paging_groups) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-paging']."<br /><br />\n";

	echo "<form id='form_list' method='post' action='paging.php".($query_string ? '?'.$query_string : '')."'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('paging_group_add') || permission_exists('paging_group_edit') || permission_exists('paging_group_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($paging_groups) ? "" : "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('paging_group_extension', $text['label-extension'], $order_by, $order, null, null, $query_string);
	echo th_order_by('paging_group_name', $text['label-name'], $order_by, $order, null, null, $query_string);
	echo th_order_by('paging_group_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'", $query_string);
	echo th_order_by('paging_group_description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn'", $query_string);
	if (permission_exists('paging_group_edit') && $settings->get('theme', 'list_row_edit_button', false)) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($paging_groups) && @sizeof($paging_groups) != 0) {
		$x = 0;
		foreach ($paging_groups as $row) {
			$enabled = ($row['paging_group_enabled'] === true || $row['paging_group_enabled'] === 't' || $row['paging_group_enabled'] === 'true' || $row['paging_group_enabled'] === '1');
			$list_row_url = '';
			if (permission_exists('paging_group_edit')) {
				$list_row_url = "paging_edit.php?id=".urlencode($row['paging_group_uuid']).($query_string ? '&'.$query_string : '');
			}

			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('paging_group_add') || permission_exists('paging_group_edit') || permission_exists('paging_group_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='paging_groups[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='paging_groups[$x][paging_group_uuid]' value='".escape($row['paging_group_uuid'])."'>\n";
				echo "	</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('paging_group_edit')) {
				echo "		<a href='".$list_row_url."'>".escape($row['paging_group_extension'])."</a>\n";
			}
			else {
				echo "		".escape($row['paging_group_extension'])."\n";
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['paging_group_name'])."</td>\n";
			echo "	<td class='center'>\n";
			if (permission_exists('paging_group_edit')) {
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($enabled ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo $text['label-'.($enabled ? 'true' : 'false')];
			}
			echo "	</td>\n";
			echo "	<td class='hide-sm-dn'>".escape($row['paging_group_description'])."&nbsp;</td>\n";
			if (permission_exists('paging_group_edit') && $settings->get('theme', 'list_row_edit_button', false)) {
				echo "	<td class='action-button'>".button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$settings->get('theme', 'button_icon_edit'),'link'=>$list_row_url])."</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($paging_groups);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "</form>\n";

	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

//include the footer
	require_once "resources/footer.php";

?>
