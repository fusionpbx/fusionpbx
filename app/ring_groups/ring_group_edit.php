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
	Portions created by the Initial Developer are Copyright (C) 2010-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/classes/ringbacks.php";

//check permissions
	if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//initialize the destinations object
	$destination = new destinations;

//get total domain ring group count
	$sql = "select count(*) from v_ring_groups ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$total_ring_groups = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//action add or update
	if (is_uuid($_REQUEST["id"]) || is_uuid($_REQUEST["ring_group_uuid"])) {
		$action = "update";

		//get the ring_group_uuid
		$ring_group_uuid = $_REQUEST["id"];
		if (is_uuid($_REQUEST["ring_group_uuid"])) {
			$ring_group_uuid = $_REQUEST["ring_group_uuid"];
		}

		//get the domain_uuid
		if (is_uuid($ring_group_uuid) && permission_exists('ring_group_all')) {
			$sql = "select domain_uuid from v_ring_groups ";
			$sql .= "where ring_group_uuid = :ring_group_uuid ";
			$parameters['ring_group_uuid'] = $ring_group_uuid;
			$database = new database;
			$domain_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);
		}
		else {
			$domain_uuid = $_SESSION['domain_uuid'];
		}
	}
	else {
		$action = "add";
		$domain_uuid = $_SESSION['domain_uuid'];
	}

//delete the user from the ring group
	if (
		$_GET["a"] == "delete"
		&& is_uuid($_REQUEST["user_uuid"])
		&& permission_exists("ring_group_edit")
		) {
		//set the variables
			$user_uuid = $_REQUEST["user_uuid"];
		//build array
			$array['ring_group_users'][0]['domain_uuid'] = $domain_uuid;
			$array['ring_group_users'][0]['ring_group_uuid'] = $ring_group_uuid;
			$array['ring_group_users'][0]['user_uuid'] = $user_uuid;
		//grant temporary permissions
			$p = new permissions;
			$p->add('ring_group_user_delete', 'temp');
		//execute delete
			$database = new database;
			$database->app_name = 'ring_groups';
			$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
			$database->delete($array);
			unset($array);
		//revoke temporary permissions
			$p->delete('ring_group_user_delete', 'temp');
		//save the message to a session variable
			message::add($text['message-delete']);
		//redirect the browser
			header("Location: ring_group_edit.php?id=$ring_group_uuid");
			exit;
	}

//get total ring group count from the database, check limit, if defined
	if ($action == 'add') {
		if ($_SESSION['limit']['ring_groups']['numeric'] != '') {
			$sql = "select count(*) from v_ring_groups ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$database = new database;
			$total_ring_groups = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

			if (is_numeric($_SESSION['limit']['ring_groups']['numeric']) && $total_ring_groups >= $_SESSION['limit']['ring_groups']['numeric']) {
				message::add($text['message-maximum_ring_groups'].' '.$_SESSION['limit']['ring_groups']['numeric'], 'negative');
				header('Location: ring_groups.php');
				exit;
			}
		}
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {

		//process the http post data by submitted action
			if ($_POST['action'] != '' && is_uuid($ring_group_uuid)) {
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $ring_group_uuid;

				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('ring_group_add')) {
							$obj = new ring_groups;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('ring_group_delete')) {
							$obj = new ring_groups;
							$obj->delete($array);
						}
						break;
				}

				header('Location: ring_groups.php');
				exit;
			}

		//set variables from http values
			$ring_group_name = $_POST["ring_group_name"];
			$ring_group_extension = $_POST["ring_group_extension"];
			$ring_group_greeting = $_POST["ring_group_greeting"];
			$ring_group_strategy = $_POST["ring_group_strategy"];
			$ring_group_destinations = $_POST["ring_group_destinations"];
			$ring_group_timeout_action = $_POST["ring_group_timeout_action"];
			$ring_group_call_timeout = $_POST["ring_group_call_timeout"];
			$ring_group_caller_id_name = $_POST["ring_group_caller_id_name"];
			$ring_group_caller_id_number = $_POST["ring_group_caller_id_number"];
			$ring_group_cid_name_prefix = $_POST["ring_group_cid_name_prefix"];
			$ring_group_cid_number_prefix = $_POST["ring_group_cid_number_prefix"];
			$ring_group_distinctive_ring = $_POST["ring_group_distinctive_ring"];
			$ring_group_ringback = $_POST["ring_group_ringback"];
			$ring_group_call_forward_enabled = $_POST["ring_group_call_forward_enabled"];
			$ring_group_follow_me_enabled = $_POST["ring_group_follow_me_enabled"];
			$ring_group_missed_call_app = $_POST["ring_group_missed_call_app"];
			$ring_group_missed_call_data = $_POST["ring_group_missed_call_data"];
			$ring_group_forward_enabled = $_POST["ring_group_forward_enabled"];
			$ring_group_forward_destination = $_POST["ring_group_forward_destination"];
			$ring_group_forward_toll_allow = $_POST["ring_group_forward_toll_allow"];
			$ring_group_enabled = $_POST["ring_group_enabled"] ?: 'false';
			$ring_group_description = $_POST["ring_group_description"];
			$dialplan_uuid = $_POST["dialplan_uuid"];
			//$ring_group_timeout_action = "transfer:1001 XML default";
			$ring_group_timeout_array = explode(":", $ring_group_timeout_action);
			$ring_group_timeout_app = array_shift($ring_group_timeout_array);
			$ring_group_timeout_data = join(':', $ring_group_timeout_array);
			$destination_number = $_POST["destination_number"];
			$destination_delay = $_POST["destination_delay"];
			$destination_timeout = $_POST["destination_timeout"];
			$destination_prompt = $_POST["destination_prompt"];
			$ring_group_destinations_delete = $_POST["ring_group_destinations_delete"];

		//set the context for users that do not have the permission
			if (permission_exists('ring_group_context')) {
				$ring_group_context = $_POST["ring_group_context"];
			}
			else if ($action == 'add') {
				$ring_group_context = $_SESSION['domain_name'];
			}

	}

//assign the user to the ring group
	if (is_uuid($_REQUEST["user_uuid"]) && is_uuid($_REQUEST["id"]) && $_GET["a"] != "delete" && permission_exists("ring_group_edit")) {
		//set the variables
			$user_uuid = $_REQUEST["user_uuid"];
		//build array
			$array['ring_group_users'][0]['ring_group_user_uuid'] = uuid();
			$array['ring_group_users'][0]['domain_uuid'] = $domain_uuid;
			$array['ring_group_users'][0]['ring_group_uuid'] = $ring_group_uuid;
			$array['ring_group_users'][0]['user_uuid'] = $user_uuid;
		//grant temporary permissions
			$p = new permissions;
			$p->add('ring_group_user_add', 'temp');
		//execute delete
			$database = new database;
			$database->app_name = 'ring_groups';
			$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
			$database->save($array);
			unset($array);
		//revoke temporary permissions
			$p->delete('ring_group_user_add', 'temp');
		//set message
			message::add($text['message-add']);
		//redirect the browser
			header("Location: ring_group_edit.php?id=".urlencode($ring_group_uuid));
			exit;
	}

//process the HTTP POST
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: ring_groups.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($ring_group_name) == 0) { $msg .= $text['message-name']."<br>\n"; }
			if (strlen($ring_group_extension) == 0) { $msg .= $text['message-extension']."<br>\n"; }
			//if (strlen($ring_group_greeting) == 0) { $msg .= $text['message-greeting']."<br>\n"; }
			if (strlen($ring_group_strategy) == 0) { $msg .= $text['message-strategy']."<br>\n"; }
			if (strlen($ring_group_call_timeout) == 0) { $msg .= $text['message-call_timeout']."<br>\n"; }
			//if (strlen($ring_group_timeout_app) == 0) { $msg .= $text['message-timeout_action']."<br>\n"; }
			//if (strlen($ring_group_cid_name_prefix) == 0) { $msg .= "Please provide: Caller ID Name Prefix<br>\n"; }
			//if (strlen($ring_group_cid_number_prefix) == 0) { $msg .= "Please provide: Caller ID Number Prefix<br>\n"; }
			//if (strlen($ring_group_ringback) == 0) { $msg .= "Please provide: Ringback<br>\n"; }
			//if (strlen($ring_group_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//prep missed call values for db insert/update
			switch ($ring_group_missed_call_app) {
				case 'email':
					$ring_group_missed_call_data = str_replace(';',',',$ring_group_missed_call_data);
					$ring_group_missed_call_data = str_replace(' ','',$ring_group_missed_call_data);
					if (substr_count($ring_group_missed_call_data, ',') > 0) {
						$ring_group_missed_call_data_array = explode(',', $ring_group_missed_call_data);
						foreach ($ring_group_missed_call_data_array as $array_index => $email_address) {
							if (!valid_email($email_address)) { unset($ring_group_missed_call_data_array[$array_index]); }
						}
						if (sizeof($ring_group_missed_call_data_array) > 0) {
							$ring_group_missed_call_data = implode(',', $ring_group_missed_call_data_array);
						}
						else {
							unset($ring_group_missed_call_app, $ring_group_missed_call_data);
						}
					}
					else {
						if (!valid_email($ring_group_missed_call_data)) {
							unset($ring_group_missed_call_app, $ring_group_missed_call_data);
						}
					}
					break;
				case 'text':
					$ring_group_missed_call_data = str_replace('-','',$ring_group_missed_call_data);
					$ring_group_missed_call_data = str_replace('.','',$ring_group_missed_call_data);
					$ring_group_missed_call_data = str_replace('(','',$ring_group_missed_call_data);
					$ring_group_missed_call_data = str_replace(')','',$ring_group_missed_call_data);
					$ring_group_missed_call_data = str_replace(' ','',$ring_group_missed_call_data);
					if (!is_numeric($ring_group_missed_call_data)) { unset($ring_group_missed_call_app, $ring_group_missed_call_data); }
					break;
				default:
					unset($ring_group_missed_call_app, $ring_group_missed_call_data);
			}

		//set the app and data
			$ring_group_timeout_array = explode(":", $ring_group_timeout_action);
			$ring_group_timeout_app = array_shift($ring_group_timeout_array);
			$ring_group_timeout_data = join(':', $ring_group_timeout_array);

		//add a uuid to ring_group_uuid if it is empty
			if ($action == 'add') {
				$ring_group_uuid = uuid();
			}

		//add the dialplan_uuid
			if (!is_uuid($_POST["dialplan_uuid"])) {
				$dialplan_uuid = uuid();
			}

		//build the array
			$array["ring_groups"][0]["ring_group_uuid"] = $ring_group_uuid;
			$array["ring_groups"][0]["domain_uuid"] = $domain_uuid;
			$array['ring_groups'][0]["ring_group_name"] = $ring_group_name;
			$array['ring_groups'][0]["ring_group_extension"] = $ring_group_extension;
			$array['ring_groups'][0]["ring_group_greeting"] = $ring_group_greeting;
			$array['ring_groups'][0]["ring_group_strategy"] = $ring_group_strategy;
			$array["ring_groups"][0]["ring_group_call_timeout"] = $ring_group_call_timeout;
			if (permission_exists('ring_group_caller_id_name')) {
				$array["ring_groups"][0]["ring_group_caller_id_name"] = $ring_group_caller_id_name;
			}
			if (permission_exists('ring_group_caller_id_number')) {
				$array["ring_groups"][0]["ring_group_caller_id_number"] = $ring_group_caller_id_number;
			}
			if (permission_exists('ring_group_cid_name_prefix')) {
				$array["ring_groups"][0]["ring_group_cid_name_prefix"] = $ring_group_cid_name_prefix;
			}
			if (permission_exists('ring_group_cid_number_prefix')) {
				$array["ring_groups"][0]["ring_group_cid_number_prefix"] = $ring_group_cid_number_prefix;
			}
			$array["ring_groups"][0]["ring_group_distinctive_ring"] = $ring_group_distinctive_ring;
			$array["ring_groups"][0]["ring_group_ringback"] = $ring_group_ringback;
			$array["ring_groups"][0]["ring_group_call_forward_enabled"] = $ring_group_call_forward_enabled;
			$array["ring_groups"][0]["ring_group_follow_me_enabled"] = $ring_group_follow_me_enabled;
			if (permission_exists('ring_group_missed_call')) {
				$array["ring_groups"][0]["ring_group_missed_call_app"] = $ring_group_missed_call_app;
				$array["ring_groups"][0]["ring_group_missed_call_data"] = $ring_group_missed_call_data;
			}
			if (permission_exists('ring_group_forward')) {
				$array["ring_groups"][0]["ring_group_forward_enabled"] = $ring_group_forward_enabled;
				$array["ring_groups"][0]["ring_group_forward_destination"] = $ring_group_forward_destination;
			}
			$array["ring_groups"][0]["ring_group_forward_toll_allow"] = $ring_group_forward_toll_allow;
			if (isset($ring_group_context)) {
				$array["ring_groups"][0]["ring_group_context"] = $ring_group_context;
			}
			$array["ring_groups"][0]["ring_group_enabled"] = $ring_group_enabled;
			$array["ring_groups"][0]["ring_group_description"] = $ring_group_description;
			$array["ring_groups"][0]["dialplan_uuid"] = $dialplan_uuid;
			if ($destination->valid($ring_group_timeout_app.':'.$ring_group_timeout_data)) {
				$array["ring_groups"][0]["ring_group_timeout_app"] = $ring_group_timeout_app;
				$array["ring_groups"][0]["ring_group_timeout_data"] = $ring_group_timeout_data;
			}

			$y = 0;
			foreach ($ring_group_destinations as $row) {
				if (is_uuid($row['ring_group_destination_uuid'])) {
					$ring_group_destination_uuid = $row['ring_group_destination_uuid'];
				}
				else {
					$ring_group_destination_uuid = uuid();
				}
				if (strlen($row['destination_number']) > 0) {
					$array["ring_groups"][0]["ring_group_destinations"][$y]["ring_group_uuid"] = $ring_group_uuid;
					$array['ring_groups'][0]["ring_group_destinations"][$y]["ring_group_destination_uuid"] = $ring_group_destination_uuid;
					$array['ring_groups'][0]["ring_group_destinations"][$y]["destination_number"] = $row['destination_number'];
					$array['ring_groups'][0]["ring_group_destinations"][$y]["destination_delay"] = $row['destination_delay'];
					$array['ring_groups'][0]["ring_group_destinations"][$y]["destination_timeout"] = $row['destination_timeout'];
					$array['ring_groups'][0]["ring_group_destinations"][$y]["destination_prompt"] = $row['destination_prompt'];
					$array['ring_groups'][0]["ring_group_destinations"][$y]["destination_enabled"] = $row['destination_enabled'] ?: 'false';
					$array['ring_groups'][0]["ring_group_destinations"][$y]["domain_uuid"] = $domain_uuid;
				}
				$y++;
			}

		//build the xml dialplan
			$dialplan_xml = "<extension name=\"".$ring_group_name."\" continue=\"\" uuid=\"".$dialplan_uuid."\">\n";
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$ring_group_extension."$\">\n";
			$dialplan_xml .= "		<action application=\"ring_ready\" data=\"\"/>\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"ring_group_uuid=".$ring_group_uuid."\"/>\n";
			$dialplan_xml .= "		<action application=\"lua\" data=\"app.lua ring_groups\"/>\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "</extension>\n";

		//build the dialplan array
			$array["dialplans"][0]["domain_uuid"] = $domain_uuid;
			$array["dialplans"][0]["dialplan_uuid"] = $dialplan_uuid;
			$array["dialplans"][0]["dialplan_name"] = $ring_group_name;
			$array["dialplans"][0]["dialplan_number"] = $ring_group_extension;
			if (isset($ring_group_context)) {
				$array["dialplans"][0]["dialplan_context"] = $ring_group_context;
			}
			$array["dialplans"][0]["dialplan_continue"] = "false";
			$array["dialplans"][0]["dialplan_xml"] = $dialplan_xml;
			$array["dialplans"][0]["dialplan_order"] = "101";
			$array["dialplans"][0]["dialplan_enabled"] = $ring_group_enabled;
			$array["dialplans"][0]["dialplan_description"] = $ring_group_description;
			$array["dialplans"][0]["app_uuid"] = "1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2";

		//add the dialplan permission
			$p = new permissions;
			$p->add("dialplan_add", "temp");
			$p->add("dialplan_edit", "temp");

		//save to the data
			$database = new database;
			$database->app_name = 'ring_groups';
			$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
			$database->save($array);
			$message = $database->message;

		//remove the temporary permission
			$p->delete("dialplan_add", "temp");
			$p->delete("dialplan_edit", "temp");

		//remove checked destinations
			if (
				$action == 'update'
				&& permission_exists('ring_group_destination_delete')
				&& is_array($ring_group_destinations_delete)
				&& @sizeof($ring_group_destinations_delete) != 0
				) {
				$obj = new ring_groups;
				$obj->ring_group_uuid = $ring_group_uuid;
				$obj->delete_destinations($ring_group_destinations_delete);
			}

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$ring_group_context);

		//clear the destinations session array
			if (isset($_SESSION['destinations']['array'])) {
				unset($_SESSION['destinations']['array']);
			}

		//set the message
			if ($action == "add") {
				//save the message to a session variable
					message::add($text['message-add']);
			}
			if ($action == "update") {
				//save the message to a session variable
					message::add($text['message-update']);
			}

		//redirect the browser
			header("Location: ring_group_edit.php?id=".urlencode($ring_group_uuid));
			exit;
	}

//pre-populate the form
	if (is_uuid($ring_group_uuid)) {
		$sql = "select * from v_ring_groups ";
		$sql .= "where ring_group_uuid = :ring_group_uuid ";
		$parameters['ring_group_uuid'] = $ring_group_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$ring_group_name = $row["ring_group_name"];
			$ring_group_extension = $row["ring_group_extension"];
			$ring_group_greeting = $row["ring_group_greeting"];
			$ring_group_context = $row["ring_group_context"];
			$ring_group_strategy = $row["ring_group_strategy"];
			$ring_group_timeout_app = $row["ring_group_timeout_app"];
			$ring_group_timeout_data = $row["ring_group_timeout_data"];
			$ring_group_call_timeout = $row["ring_group_call_timeout"];
			$ring_group_caller_id_name = $row["ring_group_caller_id_name"];
			$ring_group_caller_id_number = $row["ring_group_caller_id_number"];
			$ring_group_cid_name_prefix = $row["ring_group_cid_name_prefix"];
			$ring_group_cid_number_prefix = $row["ring_group_cid_number_prefix"];
			$ring_group_distinctive_ring = $row["ring_group_distinctive_ring"];
			$ring_group_ringback = $row["ring_group_ringback"];
			$ring_group_call_forward_enabled = $row["ring_group_call_forward_enabled"];
			$ring_group_follow_me_enabled = $row["ring_group_follow_me_enabled"];
			$ring_group_missed_call_app = $row["ring_group_missed_call_app"];
			$ring_group_missed_call_data = $row["ring_group_missed_call_data"];
			$ring_group_forward_enabled = $row["ring_group_forward_enabled"];
			$ring_group_forward_destination = $row["ring_group_forward_destination"];
			$ring_group_forward_toll_allow = $row["ring_group_forward_toll_allow"];
			$ring_group_enabled = $row["ring_group_enabled"];
			$ring_group_description = $row["ring_group_description"];
			$dialplan_uuid = $row["dialplan_uuid"];
		}
		unset($sql, $parameters, $row);
		if (strlen($ring_group_timeout_app) > 0) {
			$ring_group_timeout_action = $ring_group_timeout_app.":".$ring_group_timeout_data;
		}
	}

//set the defaults
	$destination_delay_max = $_SESSION['ring_group']['destination_delay_max']['numeric'];
	$destination_timeout_max = $_SESSION['ring_group']['destination_timeout_max']['numeric'];
	if (strlen($ring_group_ringback) == 0) {
		$ring_group_ringback = '${us-ring}';
	}
	if (strlen($ring_group_call_timeout) == 0) {
		$ring_group_call_timeout = '30';
	}
	if (strlen($ring_group_enabled) == 0) { $ring_group_enabled = 'true'; }

//get the ring group destination array
	if ($action == "add") {
		$x = 0;
		$limit = 5;
	}
	if (is_uuid($ring_group_uuid)) {
		$sql = "select * from v_ring_group_destinations ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and ring_group_uuid = :ring_group_uuid ";
		$sql .= "order by destination_delay, destination_number asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['ring_group_uuid'] = $ring_group_uuid;
		$database = new database;
		$ring_group_destinations = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//add an empty row to the options array
	if (!is_array($ring_group_destinations) || count($ring_group_destinations) == 0) {
		$rows = $_SESSION['ring_group']['destination_add_rows']['numeric'];
		$id = 0;
		$show_destination_delete = false;
	}
	if (is_array($ring_group_destinations) && count($ring_group_destinations) > 0) {
		$rows = $_SESSION['ring_group']['destination_edit_rows']['numeric'];
		$id = count($ring_group_destinations)+1;
		$show_destination_delete = true;
	}
	for ($x = 0; $x < $rows; $x++) {
		$ring_group_destinations[$id]['destination_number'] = '';
		$ring_group_destinations[$id]['destination_delay'] = '';
		$ring_group_destinations[$id]['destination_timeout'] = '';
		$ring_group_destinations[$id]['destination_prompt'] = '';
		$id++;
	}

//get the ring group users
	if (is_uuid($ring_group_uuid)) {
		$sql = "select u.username, r.user_uuid, r.ring_group_uuid ";
		$sql .= "from v_ring_group_users as r, v_users as u ";
		$sql .= "where r.user_uuid = u.user_uuid  ";
		$sql .= "and u.user_enabled = 'true' ";
		$sql .= "and r.domain_uuid = :domain_uuid ";
		$sql .= "and r.ring_group_uuid = :ring_group_uuid ";
		$sql .= "order by u.username asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['ring_group_uuid'] = $ring_group_uuid;
		$database = new database;
		$ring_group_users = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get the users
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and user_enabled = 'true' ";
	$sql .= "order by username asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set defaults
	if (strlen($ring_group_enabled) == 0) { $ring_group_enabled = 'true'; }

//set the default ring group context
	if (strlen($ring_group_context) == 0) {
		$ring_group_context = $_SESSION['domain_name'];
	}

//get the ring backs
	$ringbacks = new ringbacks;
	$ringbacks = $ringbacks->select('ring_group_ringback', $ring_group_ringback);

//get the sounds
	$sounds = new sounds;
	$sounds = $sounds->get();

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-ring_group'];
	require_once "resources/header.php";

//option to change select to text
	if (if_group("superadmin")) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.setAttribute('class', 'formfld');\n";
		//echo "	tb.setAttribute('style', 'width: 380px;');\n";
		echo "	tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "	tbb=document.createElement('INPUT');\n";
		echo "	tbb.setAttribute('class', 'btn');\n";
		echo "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
		echo "	tbb.type='button';\n";
		echo "	tbb.value=$('<div />').html('&#9665;').text();\n";
		echo "	tbb.objs=[obj,tb,tbb];\n";
		echo "	tbb.onclick=function(){ Replace(this.objs); }\n";
		echo "	obj.parentNode.insertBefore(tb,obj);\n";
		echo "	obj.parentNode.insertBefore(tbb,obj);\n";
		echo "	obj.parentNode.removeChild(obj);\n";
		echo "}\n";
		echo "\n";
		echo "function Replace(obj){\n";
		echo "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		echo "	obj[0].parentNode.removeChild(obj[1]);\n";
		echo "	obj[0].parentNode.removeChild(obj[2]);\n";
		echo "}\n";
		echo "</script>\n";
		echo "\n";
	}

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-ring_group']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'ring_groups.php']);
	if ($action == 'update') {
		$button_margin = 'margin-left: 15px;';
		if (permission_exists('ring_group_add') && (!is_numeric($_SESSION['limit']['ring_groups']['numeric']) || ($total_ring_groups < $_SESSION['limit']['ring_groups']['numeric']))) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','style'=>$button_margin,'onclick'=>"modal_open('modal-copy','btn_copy');"]);
			unset($button_margin);
		}
		if (permission_exists('ring_group_delete') || permission_exists('ring_group_destination_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>$button_margin,'onclick'=>"modal_open('modal-delete','btn_delete');"]);
			unset($button_margin);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update") {
		if (permission_exists('ring_group_add') && (!is_numeric($_SESSION['limit']['ring_groups']['numeric']) || ($total_ring_groups < $_SESSION['limit']['ring_groups']['numeric']))) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('ring_group_delete') || permission_exists('ring_group_destination_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo $text['description']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_name' maxlength='255' value=\"".escape($ring_group_name)."\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_extension' maxlength='255' value=\"".escape($ring_group_extension)."\" required='required'>\n";
	echo "<br />\n";
	echo $text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-greeting']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "<select name='ring_group_greeting' class='formfld' style='width: 200px;' ".((if_group("superadmin")) ? "onchange='changeToInput(this);'" : null).">\n";
	echo "	<option value=''></option>\n";
	foreach($sounds as $key => $value) {
		echo "<optgroup label=".$text['label-'.$key].">\n";
		$selected = false;
		foreach ($value as $row) {
			if ($ring_group_greeting == $row["value"]) {
				$selected = true;
				echo "	<option value='".escape($row["value"])."' selected='selected'>".escape($row["name"])."</option>\n";
			}
			else {
				echo "	<option value='".escape($row["value"])."'>".escape($row["name"])."</option>\n";
			}
		}
		echo "</optgroup>\n";
	}
	if (if_group("superadmin")) {
		if (!$selected && strlen($ring_group_greeting) > 0) {
			echo "	<option value='".escape($ring_group_greeting)."' selected='selected'>".escape($ring_group_greeting)."</option>\n";
		}
		unset($selected);
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-greeting']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-strategy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_strategy' onchange=\"getElementById('destination_delayorder').innerHTML = (this.selectedIndex == 1 || this.selectedIndex == 3) ? '".$text['label-destination_order']."' : '".$text['label-destination_delay']."';\">\n";
	echo "		<option value='simultaneous' ".(($ring_group_strategy == "simultaneous") ? "selected='selected'" : null).">".$text['option-simultaneous']."</option>\n";
	echo "		<option value='sequence' ".(($ring_group_strategy == "sequence") ? "selected='selected'" : null).">".$text['option-sequence']."</option>\n";
	echo "		<option value='enterprise' ".(($ring_group_strategy == "enterprise") ? "selected='selected'" : null).">".$text['option-enterprise']."</option>\n";
	echo "		<option value='rollover' ".(($ring_group_strategy == "rollover") ? "selected='selected'" : null).">".$text['option-rollover']."</option>\n";
	echo "		<option value='random' ".(($ring_group_strategy == "random") ? "selected='selected'" : null).">".$text['option-random']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-strategy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncellreq' valign='top'>".$text['label-destinations']."</td>";
	echo "		<td class='vtable' align='left'>";

	echo "			<table border='0' cellpadding='0' cellspacing='0'>\n";
	echo "				<tr>\n";
	echo "					<td class='vtable'>".$text['label-destination_number']."</td>\n";
	echo "					<td class='vtable' id='destination_delayorder'>";
	echo 						($ring_group_strategy == 'sequence' || $ring_group_strategy == 'rollover') ? $text['label-destination_order'] : $text['label-destination_delay'];
	echo "					</td>\n";
	echo "					<td class='vtable'>".$text['label-destination_timeout']."</td>\n";
	if (permission_exists('ring_group_prompt')) {
		echo "				<td class='vtable'>".$text['label-destination_prompt']."</td>\n";
	}
	echo "					<td class='vtable'>".$text['label-destination_enabled']."</td>\n";
	if ($show_destination_delete && permission_exists('ring_group_destination_delete')) {
		echo "					<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_destinations', 'delete_toggle_destinations');\" onmouseout=\"swap_display('delete_label_destinations', 'delete_toggle_destinations');\">\n";
		echo "						<span id='delete_label_destinations'>".$text['label-delete']."</span>\n";
		echo "						<span id='delete_toggle_destinations'><input type='checkbox' id='checkbox_all_destinations' name='checkbox_all' onclick=\"edit_all_toggle('destinations');\"></span>\n";
		echo "					</td>\n";
	}
	echo "				</tr>\n";
	$x = 0;
	foreach ($ring_group_destinations as $row) {
		if (strlen($row['destination_delay']) == 0) { $row['destination_delay'] = "0"; }
		if (strlen($row['destination_timeout']) == 0) { $row['destination_timeout'] = "30"; }

		if (strlen($row['ring_group_destination_uuid']) > 0) {
			echo "		<input name='ring_group_destinations[".$x."][ring_group_destination_uuid]' type='hidden' value=\"".escape($row['ring_group_destination_uuid'])."\">\n";
		}

		echo "			<tr>\n";
		echo "				<td class='formfld'>\n";
		if (!is_uuid($row['ring_group_destination_uuid'])) { // new record
			if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
				$onkeyup = "onkeyup=\"document.getElementById('ring_group_destinations_".$x."_destination_enabled').checked = (this.value != '' ? true : false);\""; // switch
			}
			else {
				$onkeyup = "onkeyup=\"document.getElementById('ring_group_destinations_".$x."_destination_enabled').value = (this.value != '' ? true : false);\""; // select
			}
		}
		echo "					<input type=\"text\" name=\"ring_group_destinations[".$x."][destination_number]\" class=\"formfld\" value=\"".escape($row['destination_number'])."\" ".$onkeyup.">\n";
		echo "				</td>\n";
		echo "				<td class='formfld'>\n";
		echo "					<select name='ring_group_destinations[".$x."][destination_delay]' class='formfld' style='width:55px'>\n";
		$i=0;
		while ($i <= $destination_delay_max) {
			if ($i == $row['destination_delay']) {
				echo "				<option value='$i' selected='selected'>$i</option>\n";
			}
			else {
				echo "				<option value='$i'>$i</option>\n";
			}
			$i = $i + 5;
		}
		echo "					</select>\n";
		echo "				</td>\n";
		echo "				<td class='formfld'>\n";
		echo "					<select name='ring_group_destinations[".$x."][destination_timeout]' class='formfld' style='width:55px'>\n";

		$i = 5;
		while($i <= $destination_timeout_max) {
			if ($i == $row['destination_timeout']) {
				echo "				<option value='$i' selected='selected'>$i</option>\n";
			}
			else {
				echo "				<option value='$i'>$i</option>\n";
			}
			$i = $i + 5;
		}
		echo "					</select>\n";
		echo "				</td>\n";
		if (permission_exists('ring_group_prompt')) {
			echo "			<td class='formfld'>\n";
			echo "				<select class='formfld' style='width: 90px;' name='ring_group_destinations[".$x."][destination_prompt]'>\n";
			echo "					<option value=''></option>\n";
			echo "					<option value='1' ".(($row['destination_prompt'])?"selected='selected'":null).">".$text['label-destination_prompt_confirm']."</option>\n";
			//echo "				<option value='2'>".$text['label-destination_prompt_announce]."</option>\n";
			echo "				</select>\n";
			echo "			</td>\n";
		}
		echo "				<td class='formfld'>\n";
		// switch
		if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
			echo "				<label class='switch'>\n";
			echo "					<input type='checkbox' id='ring_group_destinations_".$x."_destination_enabled' name='ring_group_destinations[".$x."][destination_enabled]' value='true' ".($row['destination_enabled'] == 'true' ? "checked='checked'" : null).">\n";
			echo "					<span class='slider'></span>\n";
			echo "				</label>\n";
		}
		// select
		else {
			echo "				<select class='formfld' id='ring_group_destinations_".$x."_destination_enabled' name='ring_group_destinations[".$x."][destination_enabled]'>\n";
			echo "					<option value='false'>".$text['option-false']."</option>\n";
			echo "					<option value='true' ".($row['destination_enabled'] == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
			echo "				</select>\n";
		}
		echo "				</td>\n";
		if ($show_destination_delete && permission_exists('ring_group_destination_delete')) {
			if (is_uuid($row['ring_group_destination_uuid'])) {
				echo "			<td class='vtable' style='text-align: center; padding-bottom: 3px;'>";
				echo "				<input type='checkbox' name='ring_group_destinations_delete[".$x."][checked]' value='true' class='chk_delete checkbox_destinations' onclick=\"edit_delete_action('destinations');\">\n";
				echo "				<input type='hidden' name='ring_group_destinations_delete[".$x."][uuid]' value='".escape($row['ring_group_destination_uuid'])."' />\n";
			}
			else {
				echo "			<td>\n";
			}
			echo "			</td>\n";
		}
		echo "			</tr>\n";
		$x++;
	}
	echo "			</table>\n";
	echo "			".$text['description-destinations']."\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-timeout_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo $destination->select('dialplan', 'ring_group_timeout_action', $ring_group_timeout_action);
	echo "	<br />\n";
	echo "	".$text['description-timeout_destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_timeout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ring_group_call_timeout' maxlength='255' value='".escape($ring_group_call_timeout)."'>\n";
	echo "<br />\n";
	echo $text['description-ring_group_call_timeout']." \n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('ring_group_caller_id_name')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-caller_id_name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='ring_group_caller_id_name' maxlength='255' value='".escape($ring_group_caller_id_name)."'>\n";
		echo "<br />\n";
		echo $text['description-caller_id_name']." \n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('ring_group_caller_id_number')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-caller_id_number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ring_group_caller_id_number' maxlength='255' min='0' step='1' value='".escape($ring_group_caller_id_number)."'>\n";
		echo "<br />\n";
		echo $text['description-caller_id_number']." \n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('ring_group_cid_name_prefix')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-cid-name-prefix']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='text' name='ring_group_cid_name_prefix' maxlength='255' value='".escape($ring_group_cid_name_prefix)."'>\n";
		echo "<br />\n";
		echo $text['description-cid-name-prefix']." \n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('ring_group_cid_number_prefix')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-cid-number-prefix']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "  <input class='formfld' type='number' name='ring_group_cid_number_prefix' maxlength='255' min='0' step='1' value='".escape($ring_group_cid_number_prefix)."'>\n";
		echo "<br />\n";
		echo $text['description-cid-number-prefix']." \n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-distinctive_ring']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ring_group_distinctive_ring' maxlength='255' value='".escape($ring_group_distinctive_ring)."'>\n";
	echo "<br />\n";
	echo $text['description-distinctive_ring']." \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	 ".$text['label-ringback']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	".$ringbacks;
	echo "<br />\n";
	echo $text['description-ringback']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-user_list']."</td>";
	echo "		<td class='vtable'>";
	if (is_array($ring_group_users) && @sizeof($ring_group_users) != 0) {
		echo "		<table width='300px'>\n";
		foreach ($ring_group_users as $field) {
			echo "			<tr>\n";
			echo "				<td class='vtable'>".escape($field['username'])."</td>\n";
			echo "				<td>\n";
			echo "					<a href='ring_group_edit.php?id=".urlencode($ring_group_uuid)."&user_uuid=".urlencode($field['user_uuid'])."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
			echo "				</td>\n";
			echo "			</tr>\n";
		}
		echo "		</table>\n";
		echo "		<br />\n";
	}
	echo "			<select name=\"user_uuid\" class='formfld' style='width: auto;'>\n";
	echo "			<option value=\"\"></option>\n";
	if (is_array($users) && @sizeof($users) != 0) {
		foreach ($users as $field) {
			foreach ($ring_group_users as $user) {
				if ($user['user_uuid'] == $field['user_uuid']) { continue 2; } //skip already assigned
			}
			echo "			<option value='".escape($field['user_uuid'])."'>".escape($field['username'])."</option>\n";
		}
	}
	echo "			</select>";
	echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'collapse'=>'never']);
	echo "			<br>\n";
	echo "			".$text['description-user_list']."\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-ring_group_call_forward_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_call_forward_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($ring_group_call_forward_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($ring_group_call_forward_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-ring_group_call_forward_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-ring_group_follow_me_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_follow_me_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($ring_group_follow_me_enabled == "true") {
		echo "	<option value='true' selected='selected'>".$text['option-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['option-true']."</option>\n";
	}
	if ($ring_group_follow_me_enabled == "false") {
		echo "	<option value='false' selected='selected'>".$text['option-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['option-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-ring_group_follow_me_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('ring_group_missed_call')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-missed_call']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='ring_group_missed_call_app' id='ring_group_missed_call_app' onchange=\"if (this.selectedIndex != 0) { document.getElementById('ring_group_missed_call_data').style.display = ''; document.getElementById('ring_group_missed_call_data').focus(); } else { document.getElementById('ring_group_missed_call_data').style.display='none'; }\">\n";
		echo "		<option value=''></option>\n";
		echo "    	<option value='email' ".(($ring_group_missed_call_app == "email" && $ring_group_missed_call_data != '') ? "selected='selected'" : null).">".$text['label-email']."</option>\n";
		//echo "    	<option value='text' ".(($ring_group_missed_call_app == "text" && $ring_group_missed_call_data != '') ? "selected='selected'" : null).">".$text['label-text']."</option>\n";
		//echo "    	<option value='url' ".(($ring_group_missed_call_app == "url" && $ring_group_missed_call_data != '') ? "selected='selected'" : null).">".$text['label-url']."</option>\n";
		echo "    </select>\n";
		$ring_group_missed_call_data = ($ring_group_missed_call_app == 'text') ? format_phone($ring_group_missed_call_data) : $ring_group_missed_call_data;
		echo "    <input class='formfld' type='text' name='ring_group_missed_call_data' id='ring_group_missed_call_data' maxlength='255' value=\"".escape($ring_group_missed_call_data)."\" style='min-width: 200px; width: 200px; ".(($ring_group_missed_call_app == '' || $ring_group_missed_call_data == '') ? "display: none;" : null)."'>\n";
		echo "<br />\n";
		echo $text['description-missed_call']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('ring_group_forward')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-ring_group_forward']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<select class='formfld' name='ring_group_forward_enabled' id='ring_group_forward_enabled' onchange=\"(this.selectedIndex == 1) ? document.getElementById('ring_group_forward_destination').focus() : null;\">";
		echo "		<option value='false'>".$text['option-disabled']."</option>";
		echo "		<option value='true' ".($ring_group_forward_enabled == 'true' ? "selected='selected'" : null).">".$text['option-enabled']."</option>";
		echo "	</select>";
		echo 	"<input class='formfld' type='text' name='ring_group_forward_destination' id='ring_group_forward_destination' placeholder=\"".$text['label-forward_destination']."\" maxlength='255' value=\"".escape($ring_group_forward_destination)."\">";
		echo "<br />\n";
		echo $text['description-ring-group-forward']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('ring_group_forward_toll_allow')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-ring_group_forward_toll_allow']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ring_group_forward_toll_allow' maxlength='255' value=".escape($ring_group_forward_toll_allow).">\n";
		echo "<br />\n";
		echo $text['description-ring_group_forward_toll_allow']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists("ring_group_context")) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-context']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ring_group_context' maxlength='255' value=\"".escape($ring_group_context)."\" required='required'>\n";
		echo "<br />\n";
		echo $text['description-enter-context']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='ring_group_enabled' name='ring_group_enabled' value='true' ".($ring_group_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='ring_group_enabled' name='ring_group_enabled'>\n";
		echo "		<option value='true' ".($ring_group_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($ring_group_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_description' maxlength='255' value=\"".escape($ring_group_description)."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if (is_uuid($dialplan_uuid)) {
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	if (is_uuid($ring_group_uuid)) {
		echo "<input type='hidden' name='ring_group_uuid' value='".escape($ring_group_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
