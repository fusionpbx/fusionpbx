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
	Portions created by the Initial Developer are Copyright (C) 2024
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('fifo_add') || permission_exists('fifo_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//connect to the database
	$database = database::new();

//add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);

//setup the event socket connection
	$event_socket = event_socket::create();

//set from session variables
	$button_icon_back = $settings->get('theme', 'button_icon_back', '');
	$button_icon_copy = $settings->get('theme', 'button_icon_copy', '');
	$button_icon_delete = $settings->get('theme', 'button_icon_delete', '');
	$button_icon_save = $settings->get('theme', 'button_icon_save', '');
	$input_toggle_style = $settings->get('theme', 'input_toggle_style', 'switch round');

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$fifo_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$dialplan_uuid = $_POST["dialplan_uuid"];
		$fifo_name = $_POST["fifo_name"];
		$fifo_extension = $_POST["fifo_extension"];
		$fifo_agent_status = $_POST["fifo_agent_status"];
		$fifo_agent_queue = $_POST["fifo_agent_queue"];
		$fifo_strategy = $_POST["fifo_strategy"];
		$fifo_members = $_POST["fifo_members"];
		$fifo_timeout_seconds = $_POST["fifo_timeout_seconds"];
		$fifo_exit_key = $_POST["fifo_exit_key"];
		$fifo_exit_action = $_POST["fifo_exit_action"];
		$fifo_music = $_POST["fifo_music"];
		$domain_uuid = $_POST["domain_uuid"];
		$fifo_order = $_POST["fifo_order"];
		$fifo_enabled = $_POST["fifo_enabled"] ?? 'false';
		$fifo_description = $_POST["fifo_description"];
	}

//process the data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: fifo.php');
				exit;
			}

		//process the http post data by submitted action
			if (!empty($_POST['action']) && $_POST['action'] != '' && strlen($_POST['action']) > 0) {

				//prepare the array(s)
				$x = 0;
				if (is_array($_POST['fifo_members'])) {
					foreach ($_POST['fifo_members'] as $row) {
						if (is_uuid($row['fifo_member_uuid']) && $row['checked'] === 'true') {
							$array['fifo'][$x]['checked'] = $row['checked'];
							$array['fifo'][$x]['fifo_members'][]['fifo_member_uuid'] = $row['fifo_member_uuid'];
							$x++;
						}
					}
				}

				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('fifo_add')) {
							$database->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('fifo_delete')) {
							$database->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('fifo_update')) {
							$database->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: fifo_edit.php?id='.$id);
					exit;
				}
			}

		//validate the data
			$fifo_extension = preg_replace('#[^a-zA-Z0-9.\-\*]#', '', $fifo_extension ?? '');
			$fifo_order = preg_replace('#[^0-9]#', '', $fifo_order ?? '');
			$fifo_exit_key = preg_replace('#[^0-9]#', '', $fifo_exit_key ?? '');
			$fifo_timeout_seconds = preg_replace('#[^0-9]#', '', $fifo_timeout_seconds ?? '');
			$fifo_agent_status = preg_replace('#[^a-zA-Z0-9.\-\*]#', '', $fifo_agent_status ?? '');
			$fifo_agent_queue = preg_replace('#[^a-zA-Z0-9.\-\*]#', '', $fifo_agent_queue ?? '');
			if (!empty($fifo_uuid) && !is_uuid($fifo_uuid)) { throw new Exception("invalid uuid"); }
			if (!empty($dialplan_uuid) && !is_uuid($dialplan_uuid)) { throw new Exception("invalid uuid"); }

			if (is_array($fifo_members)) {
				$i = 0;
				foreach ($fifo_members as $row) {
					$fifo_members[$i]['member_contact'] = preg_replace('#[^a-zA-Z0-9/@.\-\*]#', '', $row["member_contact"] ?? '');
					$fifo_members[$i]['member_call_timeout'] = preg_replace('#[^0-9]#', '', $row["member_call_timeout"] ?? '20');
					$fifo_members[$i]['member_wrap_up_time'] = preg_replace('#[^0-9]#', '', $row["member_wrap_up_time"] ?? '10');
					$fifo_members[$i]['member_enabled'] = $row["member_enabled"] ?? 'false';
					$i++;
				}
			}
			
		//check for all required data
			$msg = '';
			if (strlen($fifo_name) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_name']."<br>\n"; }
			if (strlen($fifo_extension) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_extension']."<br>\n"; }
			//if (strlen($fifo_agent_status) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_agent_status']."<br>\n"; }
			//if (strlen($fifo_agent_queue) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_agent_queue']."<br>\n"; }
			if (strlen($fifo_strategy) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_strategy']."<br>\n"; }
			//if (strlen($fifo_members) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_members']."<br>\n"; }
			//if (strlen($fifo_timeout_seconds) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_timeout_seconds']."<br>\n"; }
			//if (strlen($fifo_exit_key) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_exit_key']."<br>\n"; }
			//if (strlen($fifo_exit_action) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_exit_action']."<br>\n"; }
			//if (strlen($fifo_music) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_music']."<br>\n"; }
			if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			if (strlen($fifo_order) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_order']."<br>\n"; }
			//if (strlen($fifo_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_enabled']."<br>\n"; }
			//if (strlen($fifo_description) == 0) { $msg .= $text['message-required']." ".$text['label-fifo_description']."<br>\n"; }
			if (strlen($msg) > 0 && (empty($_POST["persistformvar"]) || strlen($_POST["persistformvar"]) == 0)) {
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

		//add the fifo_uuid
			$fifo_uuid = !empty($_POST["fifo_uuid"]) && is_uuid($_POST["fifo_uuid"]) ? $_POST["fifo_uuid"] : uuid();

		//add a uuid to dialplan_uuid if it is empty
			if (empty($dialplan_uuid) && !is_uuid($dialplan_uuid)) {
				$dialplan_uuid = uuid();
			}

		//prepare the variables
			$queue_name = $fifo_extension."@".$_SESSION['domain_name'];
			$app_uuid = '16589224-c876-aeb3-f59f-523a1c0801f7';
			$dialplan_context = $_SESSION['domain_name'];
			$domain_uuid = $_SESSION['domain_uuid'];
			$dialplan_detail_order = 0;

		//prepare the array
			$array['fifo'][0]['fifo_uuid'] = $fifo_uuid;
			$array['fifo'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['fifo'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['fifo'][0]['fifo_name'] = $fifo_name;
			$array['fifo'][0]['fifo_extension'] = $fifo_extension;
			$array['fifo'][0]['fifo_agent_status'] = $fifo_agent_status;
			$array['fifo'][0]['fifo_agent_queue'] = $fifo_agent_queue;
			$array['fifo'][0]['fifo_strategy'] = $fifo_strategy;
			$array['fifo'][0]['fifo_timeout_seconds'] = $fifo_timeout_seconds;
			$array['fifo'][0]['fifo_exit_key'] = $fifo_exit_key;
			$array['fifo'][0]['fifo_exit_action'] = $fifo_exit_action;
			$array['fifo'][0]['fifo_music'] = $fifo_music;
			$array['fifo'][0]['fifo_order'] = $fifo_order;
			$array['fifo'][0]['fifo_enabled'] = $fifo_enabled;
			$array['fifo'][0]['fifo_description'] = $fifo_description;
			if (is_array($fifo_members)) {
				$y = 0;
				foreach ($fifo_members as $row) {
					if (!empty($row['member_contact']) && strlen($row['member_contact']) > 0) {
						$array['fifo'][0]['fifo_members'][$y]['fifo_member_uuid'] = $row["fifo_member_uuid"];
						$array['fifo'][0]['fifo_members'][$y]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['fifo'][0]['fifo_members'][$y]['member_contact'] = $row["member_contact"];
						$array['fifo'][0]['fifo_members'][$y]['member_call_timeout'] = $row["member_call_timeout"] ?? '20';
						//$array['fifo'][0]['fifo_members'][$y]['member_simultaneous'] = $row["member_simultaneous"];
						$array['fifo'][0]['fifo_members'][$y]['member_wrap_up_time'] = $row["member_wrap_up_time"] ?? '10';
						$array['fifo'][0]['fifo_members'][$y]['member_enabled'] = $row["member_enabled"] ?? 'false';
						$y++;
					}
				}
			}

		//send commands for agent login or agent logout
			if (is_array($fifo_members)) {
				foreach ($fifo_members as $row) {
					//empty row skip iteration
					if (empty($row["member_contact"])) {
						continue;
					}

					//build the command to add or remove the agent from the FIFO queue
					if ($row["member_enabled"] == 'true') {
						$command = "fifo_member add ".$fifo_extension."@".$_SESSION['domain_name']." {fifo_member_wait=nowait}".$row["member_contact"]." 5 ".$row['member_call_timeout']." ".$row['member_wrap_up_time'];
					}
					else {
						$command = "fifo_member del ".$fifo_extension."@".$_SESSION['domain_name']." {fifo_member_wait=nowait}".$row["member_contact"];
					}

					if ($event_socket->is_connected()) {
						$response = $event_socket->command('api '.$command);
					}
				}
			}

		//get the action destination number
			if (!empty($fifo_exit_action)) {
				$fifo_exit_destination = explode(':', $fifo_exit_action)[1];
				$fifo_exit_destination = explode(' ', $fifo_exit_destination)[0];
			}

		//add the fifo dialplan
			if (!empty($fifo_extension)) {
				//escape the * symbol
				$fifo_agent_status_xml = str_replace("*", "\*", $fifo_agent_status);
				$fifo_agent_queue_xml = str_replace("*", "\*", $fifo_agent_queue);

				//prepare the fifo orbit extension
				if (!empty($fifo_exit_destination) && $fifo_timeout_seconds == 0) {
					$fifo_orbit_exten = $fifo_exit_destination;
				}
				else {
					$fifo_orbit_exten = $fifo_exit_destination.":".$fifo_timeout_seconds;
				}

				//build the xml dialplan
				$dialplan_xml = "<extension name=\"".xml::sanitize($fifo_name)."\" continue=\"false\" uuid=\"".xml::sanitize($dialplan_uuid)."\">\n";
				$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".xml::sanitize($fifo_extension)."\$\" break=\"on-true\">\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"fifo_uuid=".xml::sanitize($fifo_uuid)."\" inline=\"true\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"fifo_music=".xml::sanitize($fifo_music)."\" inline=\"true\"/>\n";
				if ($fifo_strategy == 'longest_idle_agent') {
					$dialplan_xml .= "		<action application=\"set\" data=\"fifo_strategy=waiting_longer\" inline=\"true\"/>\n";
				}
				if ($fifo_strategy == 'simultaneous') {
					$dialplan_xml .= "		<action application=\"set\" data=\"fifo_strategy=more_ppl\" inline=\"true\"/>\n";
				}
			/*
			<action application="set" data="fifo_orbit_dialplan=XML"/>
			<action application="set" data="fifo_orbit_context=default"/>
			<action application="set" data="fifo_orbit_announce=digits/6.wav"/>
			<action application="set" data="fifo_caller_exit_key=2"/>
			<action application="set" data="fifo_caller_exit_to_orbit=true"/>
			*/
				if (!empty($fifo_exit_key)) {
					$dialplan_xml .= "		<action application=\"set\" data=\"fifo_caller_exit_key=".xml::sanitize($fifo_exit_key)."\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"fifo_orbit_dialplan=XML\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"fifo_orbit_context=".xml::sanitize($_SESSION['domain_name'])."\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"fifo_caller_exit_to_orbit=true\"/>\n";

				}
				if (!empty($fifo_orbit_exten)) {
					$dialplan_xml .= "		<action application=\"set\" data=\"fifo_orbit_exten=".xml::sanitize($fifo_orbit_exten)."\"/>\n";
				}
				$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
				$dialplan_xml .= "		<action application=\"fifo\" data=\"".xml::sanitize($queue_name)." in\"/>\n";
				$dialplan_xml .= "	</condition>\n";
				$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".xml::sanitize($fifo_agent_status_xml)."\$\" break=\"on-true\">\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"fifo_uuid=".xml::sanitize($fifo_uuid)."\" inline=\"true\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"fifo_name=".xml::sanitize($queue_name)."\" inline=\"true\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"user_name=\${caller_id_number}@\${domain_name}\" inline=\"true\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"pin_number=\" inline=\"true\"/>\n";
				$dialplan_xml .= "		<action application=\"lua\" data=\"app/fifo/resources/scripts/member.lua\"/>\n";
				$dialplan_xml .= "	</condition>\n";
				$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".xml::sanitize($fifo_agent_queue_xml)."\$\" break=\"on-true\">\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"fifo_uuid=".xml::sanitize($fifo_uuid)."\" inline=\"true\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"fifo_music=".xml::sanitize($fifo_music)."\" inline=\"true\"/>\n";
				$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
				$dialplan_xml .= "		<action application=\"fifo\" data=\"".xml::sanitize($queue_name)." out wait\"/>\n";
				$dialplan_xml .= "	</condition>\n";
				$dialplan_xml .= "</extension>\n";

				//start building the dialplan array
				$y=0;
				$array["dialplans"][$y]["domain_uuid"] = $_SESSION['domain_uuid'];
				$array["dialplans"][$y]["dialplan_uuid"] = $dialplan_uuid;
				$array["dialplans"][$y]["app_uuid"] = $app_uuid;
				$array["dialplans"][$y]["dialplan_name"] = $fifo_name;
				$array["dialplans"][$y]["dialplan_number"] = $fifo_extension;
				$array["dialplans"][$y]["dialplan_xml"] = $dialplan_xml;
				$array["dialplans"][$y]["dialplan_order"] = $fifo_order;
				$array["dialplans"][$y]["dialplan_context"] = $_SESSION['domain_name'];
				$array["dialplans"][$y]["dialplan_enabled"] = $fifo_enabled;
				$array["dialplans"][$y]["dialplan_description"] = $fifo_description;
				$y++;
			}

		//add the dialplan permission
			$p = permissions::new();
			$p->add("dialplan_add", "temp");
			$p->add("dialplan_edit", "temp");

		//save the data
			$database->app_name = 'fifo';
			$database->app_uuid = '16589224-c876-aeb3-f59f-523a1c0801f7';
			$result = $database->save($array);

		//remove the temporary permission
			$p->delete("dialplan_add", "temp");
			$p->delete("dialplan_edit", "temp");

		//redirect the user
			if (isset($action)) {

				//apply settings reminder
				$_SESSION["reload_xml"] = true;

				//clear the cache
				$cache = new cache;
				$cache->delete("dialplan:".$_SESSION['domain_name']);

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

				//header('Location: fifo.php');
				header('Location: fifo_edit.php?id='.urlencode($fifo_uuid));
				return;
			}
	}

//pre-populate the form
	if (!empty($_GET) && is_array($_GET) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$sql = "select ";
		$sql .= " dialplan_uuid, ";
		$sql .= " fifo_uuid, ";
		$sql .= " fifo_name, ";
		$sql .= " fifo_extension, ";
		$sql .= " fifo_agent_status, ";
		$sql .= " fifo_agent_queue, ";
		$sql .= " fifo_strategy, ";
		$sql .= " fifo_timeout_seconds, ";
		$sql .= " fifo_exit_key, ";
		$sql .= " fifo_exit_action, ";
		$sql .= " fifo_music, ";
		$sql .= " domain_uuid, ";
		$sql .= " fifo_order, ";
		$sql .= " cast(fifo_enabled as text), ";
		$sql .= " fifo_description ";
		$sql .= "from v_fifo ";
		$sql .= "where fifo_uuid = :fifo_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['fifo_uuid'] = $fifo_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$dialplan_uuid = $row["dialplan_uuid"];
			$fifo_name = $row["fifo_name"];
			$fifo_extension = $row["fifo_extension"];
			$fifo_agent_status = $row["fifo_agent_status"];
			$fifo_agent_queue = $row["fifo_agent_queue"];
			$fifo_strategy = $row["fifo_strategy"];
			$fifo_timeout_seconds = $row["fifo_timeout_seconds"];
			$fifo_exit_key = $row["fifo_exit_key"];
			$fifo_exit_action = $row["fifo_exit_action"];
			$fifo_music = $row["fifo_music"];
			$domain_uuid = $row["domain_uuid"];
			$fifo_order = $row["fifo_order"];
			$fifo_enabled = $row["fifo_enabled"];
			$fifo_description = $row["fifo_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the child data
	if (!empty($fifo_uuid) && is_uuid($fifo_uuid)) {
		$sql = "select ";
		$sql .= " fifo_member_uuid, ";
		$sql .= " domain_uuid, ";
		$sql .= " fifo_uuid, ";
		$sql .= " member_contact, ";
		$sql .= " member_call_timeout, ";
		//$sql .= " member_simultaneous, ";
		$sql .= " member_wrap_up_time, ";
		$sql .= " cast(member_enabled as text) ";
		$sql .= "from v_fifo_members ";
		$sql .= "where fifo_uuid = :fifo_uuid ";
		//$sql .= "and domain_uuid = '".$domain_uuid."' ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['fifo_uuid'] = $fifo_uuid;
		$fifo_members = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}
	else {
		$fifo_members = [];
		$fifo_uuid = uuid();
	}

//add a uuid to dialplan_uuid if it is empty
	if (empty($dialplan_uuid) || !is_uuid($dialplan_uuid)) {
		$dialplan_uuid = uuid();
	}

//add the $fifo_member_uuid
	if (empty($fifo_member_uuid) || !is_uuid($fifo_member_uuid)) {
		$fifo_member_uuid = uuid();
	}

//add an empty row to the members array
	if (count($fifo_members) == 0) {
		$rows = $settings->get('fifo', 'option_add_rows', '5');
		$id = 0;
		$show_option_delete = false;
	}
	if (count($fifo_members) > 0) {
		$rows = $settings->get('fifo', 'option_edit_rows', '1');
		$id = count($fifo_members)+1;
		$show_option_delete = true;
	}
	for ($x = 0; $x < $rows; $x++) {
		$fifo_members[$id]['domain_uuid'] = $_SESSION['domain_uuid'];
		$fifo_members[$id]['fifo_uuid'] = $fifo_uuid;
		$fifo_members[$id]['fifo_member_uuid'] = uuid();
		$fifo_members[$id]['member_contact'] = '';
		$fifo_members[$id]['member_call_timeout'] = '';
		//$fifo_members[$id]['member_simultaneous'] = '';
		$fifo_members[$id]['member_wrap_up_time'] = '';
		$fifo_members[$id]['member_enabled'] = '';
		$id++;
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//initialize the destinations object
	$destination = new destinations;

//set the defaults
	if (empty($fifo_timeout_seconds)) {
		$fifo_timeout_seconds = 0;
	}
	if (empty($fifo_order)) {
		$fifo_order = 50;
	}
	if (empty($fifo_enabled)) {
		$fifo_enabled = true;
	}

//show the header
	$document['title'] = $text['title-fifo'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='fifo_uuid' value='".escape($fifo_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-fifo']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$button_icon_back,'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'fifo.php']);
 	if ($action == 'update') {
 		if (permission_exists('fifo_member_delete')) {
 			echo button::create(['type'=>'submit','label'=>$text['button-delete'],'icon'=>$button_icon_delete,'id'=>'btn_delete','name'=>'action','value'=>'delete','style'=>'display: none; margin-right: 15px;']);
 		}
 	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$button_icon_save,'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-fifo']."\n";
	echo "<br /><br />\n";

// 	if ($action == 'update') {
// 		if (permission_exists('fifo_add')) {
// 			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
// 		}
// 		if (permission_exists('fifo_delete')) {
// 			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
// 		}
// 	}

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fifo_name' maxlength='255' value='".escape($fifo_name ?? '')."'>\n";
	echo "<br />\n";
	echo $text['description-fifo_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fifo_extension' maxlength='255' value='".escape($fifo_extension ?? '')."'>\n";
	echo "<br />\n";
	echo $text['description-fifo_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_agent_status']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fifo_agent_status' maxlength='255' value='".escape($fifo_agent_status ?? '')."'>\n";
	echo "<br />\n";
	echo $text['description-fifo_agent_status']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_agent_queue']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fifo_agent_queue' maxlength='255' value='".escape($fifo_agent_queue ?? '')."'>\n";
	echo "<br />\n";
	echo $text['description-fifo_agent_queue']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-strategy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='fifo_strategy' onchange=\"getElementById('destination_delayorder').innerHTML = (this.selectedIndex == 1 || this.selectedIndex == 3) ? '".$text['label-destination_order']."' : '".$text['label-destination_delay']."';\">\n";
	echo "		<option value='longest_idle_agent' ".(($fifo_strategy == "'option-longest_idle_agent") ? "selected='selected'" : null).">".$text['option-longest_idle_agent']."</option>\n";
	echo "		<option value='simultaneous' ".(($fifo_strategy == "simultaneous") ? "selected='selected'" : null).">".$text['option-simultaneous']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-strategy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_members']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<th class='vtablereq'>".$text['label-member_contact']."</th>\n";
	echo "			<th class='vtablereq'>".$text['label-member_call_timeout']."</th>\n";
	//echo "			<th class='vtablereq'>".$text['label-member_simultaneous']."</th>\n";
	echo "			<th class='vtablereq'>".$text['label-member_wrap_up_time']."</th>\n";
	echo "			<th class='vtablereq'>".$text['label-member_enabled']."</th>\n";
	if ($show_option_delete && is_array($fifo_members) && @sizeof($fifo_members) > 1 && permission_exists('fifo_member_delete')) {
		echo "			<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_details', 'delete_toggle_details');\" onmouseout=\"swap_display('delete_label_details', 'delete_toggle_details');\">\n";
		echo "				<span id='delete_label_details'>".$text['label-delete']."</span>\n";
		echo "				<span id='delete_toggle_details'><input type='checkbox' id='checkbox_all_details' name='checkbox_all' onclick=\"edit_all_toggle('details'); checkbox_on_change(this);\"></span>\n";
		echo "			</td>\n";
	}
	echo "		</tr>\n";

	$x = 0;
	if (permission_exists('fifo_member_edit')) {
		foreach($fifo_members as $row) {
			$member_contact = $destination->select('user_contact', 'fifo_members['.$x.'][member_contact]', $row['member_contact'] ?? null);
			if (empty($row["member_call_timeout"])) { $row["member_call_timeout"] = '20'; }
			//if (empty($row["member_simultaneous"])) { $row["member_simultaneous"] = '3'; }
			if (empty($row["member_wrap_up_time"])) { $row["member_wrap_up_time"] = '10'; }

			echo "			<tr>\n";
			echo "				<input type='hidden' name='fifo_members[$x][domain_uuid]' value=\"".escape($row["domain_uuid"])."\">\n";
			echo "				<input type='hidden' name='fifo_members[$x][fifo_uuid]' value=\"".escape($row["fifo_uuid"])."\">\n";
			echo "				<input type='hidden' name='fifo_members[$x][fifo_member_uuid]' value=\"".escape($row["fifo_member_uuid"])."\">\n";
			echo "				<td class='formfld'>\n";
			echo "					$member_contact\n";
			echo "			</td>\n";
			echo "				<td class='formfld'>\n";
			echo "				<input class='formfld' type='text' name='fifo_members[$x][member_call_timeout]' maxlength='255' style='width:55px;' value=\"".escape($row["member_call_timeout"])."\">\n";
			echo "			</td>\n";
			//echo "			<td class='formfld'>\n";
			//echo "				<input class='formfld' type='text' name='fifo_members[$x][member_simultaneous]' maxlength='255' style='width:55px;' value=\"".escape($row["member_simultaneous"])."\">\n";
			//echo "			</td>\n";
			echo "				<td class='formfld'>\n";
			echo "				<input class='formfld' type='text' name='fifo_members[$x][member_wrap_up_time]' maxlength='255' style='width:55px;' value=\"".escape($row["member_wrap_up_time"])."\">\n";
			echo "			</td>\n";
			echo "				<td class='formfld'>\n";
			if (substr($input_toggle_style, 0, 6) == 'switch') {
				echo "	<label class='switch'>\n";
				echo "		<input type='checkbox' id='member_enabled' name='fifo_members[$x][member_enabled]' value='true' ".($row['member_enabled'] == 'true' ? "checked='checked'" : null).">\n";
				echo "		<span class='slider'></span>\n";
				echo "	</label>\n";
			}
			else {
				echo "	<select class='formfld' id='member_enabled' name='fifo_members[$x][member_enabled]'>\n";
				echo "		<option value='true' ".($row['member_enabled'] == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
				echo "		<option value='false' ".($row['$member_enabled'] == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
				echo "	</select>\n";
			}
			echo "			</td>\n";
			if ($show_option_delete && is_array($fifo_members) && @sizeof($fifo_members) > 1 && permission_exists('fifo_member_delete')) {
				if (is_uuid($row['fifo_member_uuid'])) {
					echo "		<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
					echo "			<input type='checkbox' name='fifo_members[".$x."][checked]' value='true' class='chk_delete checkbox_details' onclick=\"checkbox_on_change(this);\">\n";
					echo "		</td>\n";
				}
				else {
					echo "		<td></td>\n";
				}
			}
			echo "		</tr>\n";
			$x++;
		}
	}
	echo "	</table>\n";
	echo "<br />\n";
	echo $text['description-member_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_timeout_seconds']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fifo_timeout_seconds' maxlength='255' value='".escape($fifo_timeout_seconds)."'>\n";
	echo "<br />\n";
	echo $text['description-fifo_timeout_seconds']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_exit_key']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fifo_exit_key' maxlength='255' value='".escape($fifo_exit_key)."'>\n";
	echo "<br />\n";
	echo $text['description-fifo_exit_key']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_exit_action']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	$destination = new destinations;
	echo $destination->select('dialplan', 'fifo_exit_action', $fifo_exit_action);
	echo "<br />\n";
	echo $text['description-fifo_exit_action']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_music']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	$ringbacks = new ringbacks;
	echo $ringbacks->select('fifo_music', $fifo_music ?? null);
	echo "<br />\n";
	echo $text['description-fifo_music']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-domain_uuid']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='domain_uuid'>\n";
	if (strlen($domain_uuid) == 0) {
		echo "		<option value='' selected='selected'>".$text['select-global']."</option>\n";
	}
	else {
		echo "		<option value=''>".$text['label-global']."</option>\n";
	}
	foreach ($_SESSION['domains'] as $row) {
		if ($row['domain_uuid'] == $domain_uuid) {
			echo "		<option value='".$row['domain_uuid']."' selected='selected'>".escape($row['domain_name'])."</option>\n";
		}
		else {
			echo "		<option value='".$row['domain_uuid']."'>".$row['domain_name']."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo ($text['description-domain_uuid'] ?? '')."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='fifo_order' class='formfld'>\n";
	$i=0;
	while ($i<=999) {
		$selected = ($i == $fifo_order) ? "selected" : null;
		if (strlen($i) == 1) {
			echo "		<option value='00$i' ".$selected.">00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "		<option value='0$i' ".$selected.">0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "		<option value='$i' ".$selected.">$i</option>\n";
		}
		$i++;
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-fifo_order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (substr($input_toggle_style, 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='fifo_enabled' name='fifo_enabled' value='true' ".(!empty($fifo_enabled) && $fifo_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='fifo_enabled' name='fifo_enabled'>\n";
		echo "		<option value='true' ".($fifo_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($fifo_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-fifo_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fifo_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<textarea class='formfld' name='fifo_description' style='width: 185px; height: 80px;'>".($fifo_description ?? '')."</textarea>\n";
	echo "<br />\n";
	echo $text['description-fifo_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>\n";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
