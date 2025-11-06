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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!(permission_exists('follow_me') || !permission_exists('call_forward') || permission_exists('do_not_disturb'))) {
		echo "access denied";
		exit;
	}

//set toggle defaults
	$forward_all_enabled = false;
	$forward_busy_enabled = false;
	$forward_no_answer_enabled = false;
	$forward_user_not_registered_enabled = false;
	$do_not_disturb = false;
	$follow_me_enabled = false;
	$follow_me_ignore_busy = false;

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//define the destination_select function
	function destination_select($select_name, $select_value, $select_default) {
		if (empty($select_value)) { $select_value = $select_default; }
		echo "	<select class='formfld' style='width: 55px;' name='$select_name'>\n";
		$i = 0;
		while($i <= 100) {
			echo "	<option value='".$i."' ".(($select_value == $i) ? "selected='selected'" : null).">".$i."</option>\n";
			$i = $i + 5;
		}
		echo "</select>\n";
	}

//get the extension_uuid
	$extension_uuid = $_REQUEST["id"];

//get the extension number
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and extension_uuid = :extension_uuid ";
	if (!permission_exists('extension_edit')) {
		if (count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $index => $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = :extension_".$index." ";
				$parameters['extension_'.$index] = $row['user'];
				$x++;
			}
			$sql .= ")";
		}
		else {
			//hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['extension_uuid'] = $extension_uuid;
	$row = $database->select($sql, $parameters, 'row');
	if (!empty($row)) {
		$extension = $row["extension"];
		$number_alias = $row["number_alias"];
		$accountcode = $row["accountcode"];
		$effective_caller_id_name = $row["effective_caller_id_name"];
		$effective_caller_id_number = $row["effective_caller_id_number"];
		$outbound_caller_id_name = $row["outbound_caller_id_name"];
		$outbound_caller_id_number = $row["outbound_caller_id_number"];
		$do_not_disturb = filter_var($row["do_not_disturb"], FILTER_VALIDATE_BOOLEAN);
		$forward_all_destination = $row["forward_all_destination"];
		$forward_all_enabled = filter_var($row["forward_all_enabled"], FILTER_VALIDATE_BOOLEAN);
		$forward_busy_destination = $row["forward_busy_destination"];
		$forward_busy_enabled = filter_var($row["forward_busy_enabled"], FILTER_VALIDATE_BOOLEAN);
		$forward_no_answer_destination = $row["forward_no_answer_destination"];
		$forward_no_answer_enabled = filter_var($row["forward_no_answer_enabled"], FILTER_VALIDATE_BOOLEAN);
		$forward_user_not_registered_destination = $row["forward_user_not_registered_destination"];
		$forward_user_not_registered_enabled = filter_var($row["forward_user_not_registered_enabled"], FILTER_VALIDATE_BOOLEAN);
		$follow_me_uuid = $row["follow_me_uuid"];
	}
	else {
		echo "access denied";
		exit;
	}
	unset($sql, $parameters, $row);

//process post vars
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//get http post variables and set them to php variables
			$forward_all_enabled = filter_var($_POST["forward_all_enabled"], FILTER_VALIDATE_BOOLEAN);
			$forward_all_destination = $_POST["forward_all_destination"];
			$forward_busy_enabled = filter_var($_POST["forward_busy_enabled"], FILTER_VALIDATE_BOOLEAN);
			$forward_busy_destination = $_POST["forward_busy_destination"];
			$forward_no_answer_enabled = filter_var($_POST["forward_no_answer_enabled"], FILTER_VALIDATE_BOOLEAN);
			$forward_no_answer_destination = $_POST["forward_no_answer_destination"];
			$forward_user_not_registered_enabled = filter_var($_POST["forward_user_not_registered_enabled"], FILTER_VALIDATE_BOOLEAN);
			$forward_user_not_registered_destination = $_POST["forward_user_not_registered_destination"];
			$do_not_disturb = filter_var($_POST["do_not_disturb"], FILTER_VALIDATE_BOOLEAN);

			$cid_name_prefix = $_POST["cid_name_prefix"] ?? '';
			$cid_number_prefix = $_POST["cid_number_prefix"] ?? '';
			$follow_me_enabled = filter_var($_POST["follow_me_enabled"], FILTER_VALIDATE_BOOLEAN);
			$follow_me_ignore_busy = filter_var($_POST["follow_me_ignore_busy"], FILTER_VALIDATE_BOOLEAN);

			$n = 0;
			$destination_found = false;
			foreach ($_POST["destinations"] as $field) {
				$destinations[$n]['uuid'] = $field['uuid'];
				$destinations[$n]['destination'] = $field['destination'];
				$destinations[$n]['delay'] = $field['delay'];
				$destinations[$n]['prompt'] = $field['prompt'];
				$destinations[$n]['timeout'] = $field['timeout'];
				if (isset($field['destination'])) {
					$destination_found = true;
				}
				$n++;
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: call_forward.php');
				exit;
			}

		//check for all required data
			if (!empty($msg) && empty($_POST["persistformvar"])) {
				$document['title'] = $text['title-call_forward'];
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

		//call forward config
			if (permission_exists('call_forward')) {
				//sanitize the destinations
				$forward_all_destination = preg_replace('#[^\*0-9]#', '', $forward_all_destination);
				$forward_busy_destination = preg_replace('#[^\*0-9]#', '', $forward_busy_destination);
				$forward_no_answer_destination = preg_replace('#[^\*0-9]#', '', $forward_no_answer_destination);
				$forward_user_not_registered_destination = preg_replace('#[^\*0-9]#', '', $forward_user_not_registered_destination);

				//build the array
				$array['extensions'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
				$array['extensions'][0]['extension_uuid'] = $extension_uuid;
				$array['extensions'][0]['forward_all_enabled'] = $forward_all_enabled ? 'true' : 'false';
				$array['extensions'][0]['forward_all_destination'] = $forward_all_destination;
				$array['extensions'][0]['forward_busy_enabled'] = $forward_busy_enabled ? 'true' : 'false';
				$array['extensions'][0]['forward_busy_destination'] = $forward_busy_destination;
				$array['extensions'][0]['forward_no_answer_enabled'] = $forward_no_answer_enabled ? 'true' : 'false';
				$array['extensions'][0]['forward_no_answer_destination'] = $forward_no_answer_destination;
				$array['extensions'][0]['forward_user_not_registered_enabled'] = $forward_user_not_registered_enabled ? 'true' : 'false';
				$array['extensions'][0]['forward_user_not_registered_destination'] = $forward_user_not_registered_destination;
			}

		//do not disturb (dnd) config
			if (permission_exists('do_not_disturb')) {
				$array['extensions'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
				$array['extensions'][0]['extension_uuid'] = $extension_uuid;
				$array['extensions'][0]['do_not_disturb'] = $do_not_disturb ? 'true' : 'false';
			}

		//follow me config
			if (permission_exists('follow_me')) {

				//add follow_me_uuid and follow_me_enabled to the extensions array
					if ($follow_me_uuid == '') {
						$follow_me_uuid = uuid();
						$array['extensions'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
						$array['extensions'][0]['extension_uuid'] = $extension_uuid;
						$array['extensions'][0]['follow_me_uuid'] = $follow_me_uuid;
					}
					$array['extensions'][0]['follow_me_enabled'] = ($destination_found && $follow_me_enabled) ? 'true' : 'false';

				//build the follow me array
					$array['follow_me'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					$array['follow_me'][0]['follow_me_uuid'] = $follow_me_uuid;
					$array['follow_me'][0]['cid_name_prefix'] = $cid_name_prefix;
					$array['follow_me'][0]['cid_number_prefix'] = $cid_number_prefix;
					$array['follow_me'][0]['follow_me_ignore_busy'] = $follow_me_ignore_busy ? 'true' : 'false';
					$array['follow_me'][0]['follow_me_enabled'] = ($destination_found && $follow_me_enabled) ? 'true' : 'false';

					$d = 0;
					$destination_found = false;
					foreach ($destinations as $field) {
						if (!empty($field['destination'])) {
							//sanitize the destination
							$field['destination'] = preg_replace('#[^\*0-9]#', '', $field['destination']);

							//build the array
							$array['follow_me'][0]['follow_me_destinations'][$d]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['follow_me'][0]['follow_me_destinations'][$d]['follow_me_uuid'] = $follow_me_uuid;
							$array['follow_me'][0]['follow_me_destinations'][$d]['follow_me_destination_uuid'] = $field['uuid'];
							$array['follow_me'][0]['follow_me_destinations'][$d]['follow_me_destination'] = $field['destination'];
							$array['follow_me'][0]['follow_me_destinations'][$d]['follow_me_delay'] = $field['delay'];
							$array['follow_me'][0]['follow_me_destinations'][$d]['follow_me_prompt'] = $field['prompt'];
							$array['follow_me'][0]['follow_me_destinations'][$d]['follow_me_timeout'] = $field['timeout'];
							$array['follow_me'][0]['follow_me_destinations'][$d]['follow_me_order'] = $d;
							$destination_found = true;
							$d++;
						}
						else {
							$follow_me_delete_uuids[] = $field['uuid'];
						}
					}
			}

		//add the dialplan permission
			$p = permissions::new();
			$p->add("extension_edit", "temp");

		//save the data
			$database->save($array);
			unset($array);

		//remove the temporary permission
			$p->delete("extension_edit", "temp");

		//delete empty destination records
			if (!empty($follow_me_delete_uuids)) {
				foreach ($follow_me_delete_uuids as $follow_me_delete_uuid) {
					$array['follow_me_destinations'][]['follow_me_destination_uuid'] = $follow_me_delete_uuid;
				}
				$database->delete($array);
				unset($array);
			}

		/*
		//call forward config
			if (permission_exists('call_forward')) {
				$call_forward = new call_forward;
				$call_forward->domain_uuid = $_SESSION['domain_uuid'];
				$call_forward->domain_name = $_SESSION['domain_name'];
				$call_forward->extension_uuid = $extension_uuid;
				$call_forward->forward_all_destination = $forward_all_destination;
				$call_forward->forward_all_enabled = $forward_all_enabled;
			}

		//do not disturb (dnd) config
			if (permission_exists('do_not_disturb')) {
				$dnd = new do_not_disturb;
				$dnd->domain_uuid = $_SESSION['domain_uuid'];
				$dnd->domain_name = $_SESSION['domain_name'];
				$dnd->extension_uuid = $extension_uuid;
				$dnd->extension = $extension;
				$dnd->enabled = $do_not_disturb ? 'true' : 'false';
			}

		//if follow me is enabled then process call forward and dnd first
			if ($follow_me_enabled) {
				//call forward
					$call_forward->set();
					unset($call_forward);
				//dnd
					$dnd->set();
					$dnd->user_status();
					unset($dnd);
			}

		//follow me config and process
			if (permission_exists('follow_me')) {
				$follow_me = new follow_me;
				$follow_me->domain_uuid = $_SESSION['domain_uuid'];
				$follow_me->extension_uuid = $extension_uuid;
				$follow_me->follow_me_uuid = $follow_me_uuid;
				$follow_me->follow_me_ignore_busy = $follow_me_ignore_busy;
				$follow_me->follow_me_enabled = $follow_me_enabled;
				$follow_me->set();
				unset($follow_me);
			}

		//if dnd or call forward are enabled process them last
			if ($follow_me_enabled != true) {
				if ($forward_all_enabled) {
					//dnd
						$dnd->set();
						$dnd->user_status();
						unset($dnd);
					//call forward
						$call_forward->set();
						unset($call_forward);
				}
				else{
					//call forward
						$call_forward->set();
						unset($call_forward);
					//dnd
						$dnd->set();
						$dnd->user_status();
						unset($dnd);
				}
			}
		*/

		//send feature event notify to the phone
			if ($settings->get('device', 'feature_sync', false)) {
				$ring_count = ceil($call_timeout / 6);
				$feature_event_notify = new feature_event_notify;
				$feature_event_notify->domain_name = $_SESSION['domain_name'];
				$feature_event_notify->extension = $extension;
				$feature_event_notify->do_not_disturb = $do_not_disturb ? 'true' : 'false';
				$feature_event_notify->ring_count = $ring_count;
				$feature_event_notify->forward_all_enabled = $forward_all_enabled;
				$feature_event_notify->forward_busy_enabled = $forward_busy_enabled;
				$feature_event_notify->forward_no_answer_enabled = $forward_no_answer_enabled;
				//workaround for freeswitch not sending NOTIFY when destination values are nil. Send 0.
				if ($forward_all_destination == "") {
					$feature_event_notify->forward_all_destination = "0";
				}
				else {
					$feature_event_notify->forward_all_destination = $forward_all_destination;
				}

				if ($forward_busy_destination == "") {
					$feature_event_notify->forward_busy_destination = "0";
				}
				else {
					$feature_event_notify->forward_busy_destination = $forward_busy_destination;
				}

				if ($forward_no_answer_destination == "") {
					$feature_event_notify->forward_no_answer_destination = "0";
				}
				else {
					$feature_event_notify->forward_no_answer_destination = $forward_no_answer_destination;
				}
				$feature_event_notify->send_notify();
				unset($feature_event_notify);
			}

		//send presence event
			if (permission_exists('do_not_disturb')) {
				if ($do_not_disturb) {
					//build the event
					$cmd = "sendevent PRESENCE_IN\n";
					$cmd .= "proto: sip\n";
					$cmd .= "login: ".$extension."@".$_SESSION['domain_name']."\n";
					$cmd .= "from: ".$extension."@".$_SESSION['domain_name']."\n";
					$cmd .= "status: Active (1 waiting)\n";
					$cmd .= "rpid: unknown\n";
					$cmd .= "event_type: presence\n";
					$cmd .= "alt_event_type: dialog\n";
					$cmd .= "event_count: 1\n";
					$cmd .= "unique-id: ".uuid()."\n";
					$cmd .= "Presence-Call-Direction: outbound\n";
					$cmd .= "answer-state: confirmed\n";

					//send the event
					$switch_result = event_socket::command($cmd);
				}
				else {
					$presence = new presence;
					if (!$presence->active($extension."@".$_SESSION['domain_name'])) {
						//build the event
						$cmd = "sendevent PRESENCE_IN\n";
						$cmd .= "proto: sip\n";
						$cmd .= "login: ".$extension."@".$_SESSION['domain_name']."\n";
						$cmd .= "from: ".$extension."@".$_SESSION['domain_name']."\n";
						$cmd .= "status: Active (1 waiting)\n";
						$cmd .= "rpid: unknown\n";
						$cmd .= "event_type: presence\n";
						$cmd .= "alt_event_type: dialog\n";
						$cmd .= "event_count: 1\n";
						$cmd .= "unique-id: ".uuid()."\n";
						$cmd .= "Presence-Call-Direction: outbound\n";
						$cmd .= "answer-state: terminated\n";

						//send the event
						$switch_result = event_socket::command($cmd);
					}
				}
			}

		//synchronize configuration
			if (!empty($settings->get('switch', 'extensions')) && is_readable($settings->get('switch', 'extensions'))) {
				$ext = new extension;
				$ext->xml();
				unset($ext);
			}

		//clear the cache
			$cache = new cache;
			$cache->delete(gethostname().":directory:".$extension."@".$_SESSION['domain_name']);
			if (!empty($number_alias)) {
				$cache->delete(gethostname().":directory:".$number_alias."@".$_SESSION['domain_name']);
			}

		//add the message
			message::add($text['confirm-update']);

		// redirect
			header('Location: call_forward_edit.php?id='.$extension_uuid);
			exit;

	}

//show the header
	$document['title'] = $text['title-call_forward'];
	require_once "resources/header.php";

//pre-populate the form
	if (!empty($follow_me_uuid) && is_uuid($follow_me_uuid)) {
		$sql = "select * from v_follow_me ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and follow_me_uuid = :follow_me_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['follow_me_uuid'] = $follow_me_uuid;
		$row = $database->select($sql, $parameters, 'row');
		unset($sql, $parameters);

		if (!empty($row)) {
			$cid_name_prefix = $row["cid_name_prefix"];
			$cid_number_prefix = $row["cid_number_prefix"];
			$follow_me_enabled = filter_var($row["follow_me_enabled"], FILTER_VALIDATE_BOOLEAN);
			$follow_me_ignore_busy = filter_var($row["follow_me_ignore_busy"], FILTER_VALIDATE_BOOLEAN);

			unset($row);

			$sql = "select * from v_follow_me_destinations ";
			$sql .= "where follow_me_uuid = :follow_me_uuid ";
			$sql .= "order by follow_me_order asc ";
			$parameters['follow_me_uuid'] = $follow_me_uuid;
			$result = $database->select($sql, $parameters, 'all');

			unset($destinations);
			foreach ($result as $x => $row) {
				$destinations[$x]['uuid'] = $row["follow_me_destination_uuid"];
				$destinations[$x]['destination'] = $row["follow_me_destination"];
				$destinations[$x]['delay'] = $row["follow_me_delay"];
				$destinations[$x]['prompt'] = $row["follow_me_prompt"];
				$destinations[$x]['timeout'] = $row["follow_me_timeout"];
			}
			unset($sql, $parameters, $result, $row);
		}
	}

//add the pre-defined follow me destinations
	for ($n = 0; $n <= $settings->get('follow_me', 'max_destinations', 5) - 1; $n++) {
		if (empty($destinations[$n]['uuid'])) { $destinations[$n]['uuid'] =  null; }
		if (empty($destinations[$n]['destination'])) { $destinations[$n]['destination'] =  null; }
		if (empty($destinations[$n]['delay'])) { $destinations[$n]['delay'] =  null; }
		if (empty($destinations[$n]['prompt'])) { $destinations[$n]['prompt'] =  null; }
		if (empty($destinations[$n]['timeout'])) { $destinations[$n]['timeout'] =  30; }
	}

//get the extensions array - used with autocomplete
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by extension, number_alias asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters, $row);

//prepare the autocomplete
	if ($settings->get('follow_me', 'follow_me_autocomplete', false)) {
		echo "<link rel=\"stylesheet\" href=\"".PROJECT_PATH."/resources/jquery/jquery-ui.min.css\" />\n";
		echo "<script src=\"".PROJECT_PATH."/resources/jquery/jquery-ui.min.js\"></script>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "\$(function() {\n";
		echo "	var extensions = [\n";
		foreach ($extensions as $row) {
			if (empty($number_alias)) {
				echo "		\"".escape($row["extension"])."\",\n";
			}
			else {
				echo "		\"".escape($row["number_alias"])."\",\n";
			}
		}
		echo "	];\n";
		for ($n = 0; $n <= $settings->get('follow_me', 'max_destinations', 5) - 1; $n++) {
			echo "	\$(\"#destination_".$n."\").autocomplete({\n";
			echo "		source: extensions\n";
			echo "	});\n";
		}

		echo "});\n";
		echo "</script>\n";
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//save the back button location using referer
	$back_destination = "window.location.href='" . ($_SESSION['call_forward_back'] ?? "/app/call_forward/call_forward.php") . "'";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-call_forward']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','onclick'=>$back_destination]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description']." <strong>".escape($extension)."</strong>\n";
	echo "<br /><br />\n";

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	<strong>".$text['label-call_forward']."</strong>\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<script>\n";
	echo "		function changed_call_forward() {\n";
	echo "			if (document.getElementById('forward_all_enabled').value == 'true'){\n";
	echo "				document.getElementById('forward_all_destination').focus();\n";
	echo "          	document.getElementById('follow_me_enabled').value = 'false';\n";
	echo "          	document.getElementById('do_not_disturb').value = 'false';\n";
	echo "				$('#div_follow_me_settings').slideUp('fast');\n";
	echo "          }\n";
	echo "		}\n";
	echo "	</script>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='forward_all_enabled' name='forward_all_enabled' onchange='changed_call_forward();'>\n";
	echo "			<option value='true' ".($forward_all_enabled === true ? "selected='selected'" : '').">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($forward_all_enabled === false ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
		echo "&nbsp;";
	}
	echo "	<input class='formfld' type='text' name='forward_all_destination' id='forward_all_destination' ".($input_toggle_style_switch ? "style='margin-top: -21px;'" : null)." maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".escape($forward_all_destination)."\">\n";
	echo "	<br />".$text['description-call_forward']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-on-busy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<script>\n";
	echo "		function changed_forward_on_busy() {\n";
	echo "			if (document.getElementById('forward_busy_enabled').value == 'true') {\n";
	echo "				document.getElementById('do_not_disturb').value = 'false';\n";
	echo "				document.getElementById('forward_busy_destination').focus();\n";
	echo "			}\n";
	echo "		}\n";
	echo "	</script>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='forward_busy_enabled' name='forward_busy_enabled' onchange='changed_forward_on_busy();'>\n";
	echo "			<option value='true' ".($forward_busy_enabled === true ? "selected='selected'" : '').">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($forward_busy_enabled === false ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
		echo "&nbsp;";
	}
	echo "	<input class='formfld' type='text' name='forward_busy_destination' id='forward_busy_destination' ".($input_toggle_style_switch ? "style='margin-top: -21px;'" : null)." maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".escape($forward_busy_destination)."\">\n";
	echo "	<br />".$text['description-on-busy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-no_answer']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<script>\n";
	echo "		function changed_forward_no_answer() {\n";
	echo "			if (document.getElementById('forward_no_answer_enabled').value == 'true') {\n";
	echo "				document.getElementById('do_not_disturb').value = 'false';\n";
	echo "				document.getElementById('forward_no_answer_destination').focus();\n";
	echo "			}\n";
	echo "		}\n";
	echo "	</script>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='forward_no_answer_enabled' name='forward_no_answer_enabled' onchange='changed_forward_no_answer();'>\n";
	echo "			<option value='true' ".($forward_no_answer_enabled === true ? "selected='selected'" : '').">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($forward_no_answer_enabled === false ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
		echo "&nbsp;";
	}
	echo "	<input class='formfld' type='text' name='forward_no_answer_destination' id='forward_no_answer_destination' ".($input_toggle_style_switch ? "style='margin-top: -21px;'" : null)." maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".escape($forward_no_answer_destination)."\">\n";
	echo "	<br />".$text['description-no_answer']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-not_registered']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<script>\n";
	echo "		function changed_forward_not_registered() {\n";
	echo "			if (document.getElementById('forward_user_not_registered_enabled').value == 'true') {\n";
	echo "				document.getElementById('forward_user_not_registered_destination').focus();\n";
	echo "			}\n";
	echo "		}\n";
	echo "	</script>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='forward_user_not_registered_enabled' name='forward_user_not_registered_enabled' onchange='changed_forward_not_registered(this);'>\n";
	echo "			<option value='true' ".($forward_user_not_registered_enabled === true ? "selected='selected'" : '').">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($forward_user_not_registered_enabled === false ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
		echo "&nbsp;";
	}
	echo "	<input class='formfld' type='text' name='forward_user_not_registered_destination' id='forward_user_not_registered_destination' ".($input_toggle_style_switch ? "style='margin-top: -21px;'" : null)." maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".escape($forward_user_not_registered_destination)."\">\n";
	echo "	<br />".$text['description-not_registered']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr><td colspan='2'><br /></td></tr>\n";
	echo "</table>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	<strong>".$text['label-follow_me']."</strong>\n";
	echo "</td>\n";
	echo "<td id='td_follow_me' width='70%' class='vtable' align='left'>\n";
	echo "	<script>\n";
	echo "		function changed_follow_me() {\n";
	echo "			if (document.getElementById('follow_me_enabled').value == 'true'){\n";
	echo "          	$('#div_follow_me_settings').slideDown('fast');\n";
	echo "          	document.getElementById('forward_all_enabled').value = 'false';\n";
	echo "          	document.getElementById('do_not_disturb').value = 'false';\n";
	echo "          } else {\n";
	echo "          	$('#div_follow_me_settings').slideUp('fast');\n";
	echo "          }\n";
	echo "		}\n";
	echo "	</script>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "		<select class='formfld' id='follow_me_enabled' name='follow_me_enabled' onchange='changed_follow_me();'>\n";
	echo "			<option value='true' ".($follow_me_enabled === true ? "selected='selected'" : '').">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($follow_me_enabled === false ? "selected='selected'" : '').">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<div id='div_follow_me_settings' ".(empty($follow_me_enabled) || $follow_me_enabled !== true ? "style='display: none;'" : null).">\n";

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destinations']."\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";

		echo "	<table border='0' cellpadding='2' cellspacing='0'>\n";
		echo "		<tr>\n";
		echo "			<td class='vtable'>".$text['label-destination_number']."</td>\n";
		echo "			<td class='vtable'>".$text['label-destination_delay']."</td>\n";
		echo "			<td class='vtable'>".$text['label-destination_timeout']."</td>\n";
		if (permission_exists('follow_me_prompt')) {
			echo "		<td class='vtable'>".$text['label-destination_prompt']."</td>\n";
		}
		echo "		</tr>\n";

		//output destinations
		$on_click = "";
		foreach ($destinations as $n => $destination) {
			echo "		<input type='hidden' name='destinations[".$n."][uuid]' value='".((!empty($destination['uuid'])) ? $destination['uuid'] : uuid())."'>\n";
			echo "		<tr>\n";
			echo "			<td><input class='formfld' style='min-width: 135px;' type='text' name='destinations[".$n."][destination]' id='destination_".$n."' maxlength='255' value=\"".escape($destination['destination'])."\"></td>\n";
			echo "			<td>\n";
									destination_select('destinations['.$n.'][delay]', $destination['delay'], '0');
			echo "			</td>\n";
			echo "			<td>\n";
									destination_select('destinations['.$n.'][timeout]', $destination['timeout'], $settings->get('follow_me', 'timeout', 30));
			echo "			</td>\n";
			if (permission_exists('follow_me_prompt')) {
				echo "		<td>\n";
				echo "			<select class='formfld' style='width: 90px;' name='destinations[".$n."][prompt]'>\n";
				echo "				<option value=''></option>\n";
				echo "				<option value='1' ".(($destination['prompt']) ? "selected='selected'" : null).">".$text['label-destination_prompt_confirm']."</option>\n";
				//echo "			<option value='2'>".$text['label-destination_prompt_announce]."</option>\n";
				echo "			</select>\n";
				echo "		</td>\n";
			}
			echo "		</tr>\n";
		}

		echo "	</table>\n";
		echo "</td>\n";
		echo "</tr>\n";

		if (permission_exists('follow_me_ignore_busy')) {
			echo "		<tr>\n";
			echo "			<td class='vncell' valign='top' align='left' nowrap='nowrap'>";
			echo 				$text['label-ignore_busy'];
			echo "			</td>\n";
			echo "			<td class='vtable' align='left'>\n";
			if ($input_toggle_style_switch) {
				echo "	<span class='switch'>\n";
			}
			echo "		<select class='formfld' id='follow_me_ignore_busy' name='follow_me_ignore_busy'>\n";
			echo "			<option value='true' ".($follow_me_ignore_busy === true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
			echo "			<option value='false' ".($follow_me_ignore_busy === false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
			echo "		</select>\n";
			if ($input_toggle_style_switch) {
				echo "		<span class='slider'></span>\n";
				echo "	</span>\n";
			}

			echo "				<br />\n";
			echo 				$text['description-ignore_busy']."\n";
			echo "			</td>\n";
			echo "		</tr>\n";
		}

		if (permission_exists('follow_me_cid_name_prefix')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-cid-name-prefix']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "  <input class='formfld' type='text' name='cid_name_prefix' maxlength='255' value='".escape($cid_name_prefix)."'>\n";
			echo "<br />\n";
			echo $text['description-cid-name-prefix']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		if (permission_exists('follow_me_cid_number_prefix')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-cid-number-prefix']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "  <input class='formfld' type='text' name='cid_number_prefix' maxlength='255' value='".escape($cid_number_prefix)."'>\n";
			echo "<br />\n";
			echo $text['description-cid-number-prefix']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";

	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr><td colspan='2'><br /></td></tr>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	<strong>".$text['label-dnd']."</strong>\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<script>\n";
	echo "		function changed_do_not_disturb(el) {\n";
	echo "			if (document.getElementById('do_not_disturb').value == 'true') {\n";
	echo "				document.getElementById('forward_all_enabled').value = 'false';\n";
	echo "			}\n";
	echo "		}\n";
	echo "	</script>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch' onclick='changed_do_not_disturb(this);'>\n";
	}
	echo "		<select class='formfld' id='do_not_disturb' name='do_not_disturb' onchange='changed_do_not_disturb();'>\n";
	echo "			<option value='true' ".($do_not_disturb === true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "			<option value='false' ".($do_not_disturb === false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "		</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>\n";
	echo "<br /><br />";

	if (!empty($action) && $action == "update") {
		echo "<input type='hidden' name='id' value='".escape($extension_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";
