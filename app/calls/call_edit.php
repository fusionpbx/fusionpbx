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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//define the destination_select function
	function destination_select($select_name, $select_value, $select_default) {
		if (strlen($select_value) == 0) { $select_value = $select_default; }
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
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && sizeof($row) != 0) {
		$extension = $row["extension"];
		$number_alias = $row["number_alias"];
		$accountcode = $row["accountcode"];
		$effective_caller_id_name = $row["effective_caller_id_name"];
		$effective_caller_id_number = $row["effective_caller_id_number"];
		$outbound_caller_id_name = $row["outbound_caller_id_name"];
		$outbound_caller_id_number = $row["outbound_caller_id_number"];
		$do_not_disturb = $row["do_not_disturb"] != '' ? $row["do_not_disturb"] : 'false';
		$forward_all_destination = $row["forward_all_destination"];
		$forward_all_enabled = $row["forward_all_enabled"];
		$forward_busy_destination = $row["forward_busy_destination"];
		$forward_busy_enabled = $row["forward_busy_enabled"];
		$forward_no_answer_destination = $row["forward_no_answer_destination"];
		$forward_no_answer_enabled = $row["forward_no_answer_enabled"];
		$forward_user_not_registered_destination = $row["forward_user_not_registered_destination"];
		$forward_user_not_registered_enabled = $row["forward_user_not_registered_enabled"];
		$follow_me_uuid = $row["follow_me_uuid"];
	}
	else {
		echo "access denied";
		exit;
	}
	unset($sql, $parameters, $row);

//process post vars
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get http post variables and set them to php variables
			if (count($_POST) > 0) {
				$forward_all_enabled = $_POST["forward_all_enabled"];
				$forward_all_destination = $_POST["forward_all_destination"];
				$forward_busy_enabled = $_POST["forward_busy_enabled"];
				$forward_busy_destination = $_POST["forward_busy_destination"];
				$forward_no_answer_enabled = $_POST["forward_no_answer_enabled"];
				$forward_no_answer_destination = $_POST["forward_no_answer_destination"];
				$forward_user_not_registered_enabled = $_POST["forward_user_not_registered_enabled"];
				$forward_user_not_registered_destination = $_POST["forward_user_not_registered_destination"];

				$cid_name_prefix = $_POST["cid_name_prefix"];
				$cid_number_prefix = $_POST["cid_number_prefix"];
				$follow_me_enabled = $_POST["follow_me_enabled"];
				$follow_me_ignore_busy = $_POST["follow_me_ignore_busy"];

				$n = 0;
				$destination_found = false;
				foreach ($_POST["destinations"] as $field) {
					$destinations[$n]['uuid'] = $field['uuid'];
					$destinations[$n]['destination'] = $field['destination'];
					$destinations[$n]['delay'] = $field['delay'];
					$destinations[$n]['prompt'] = $field['prompt'];
					$destinations[$n]['timeout'] = $field['timeout'];
					if ($field['destination'] != '') {
						$destination_found = true;
					}
					$n++;
				}
				$dnd_enabled = $_POST["dnd_enabled"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: calls.php');
				exit;
			}

		//check for all required data
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				$document['title'] = $text['title-call_routing'];
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

		//include the classes
			include "resources/classes/call_forward.php";
			include "resources/classes/follow_me.php";
			include "resources/classes/do_not_disturb.php";

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
				$array['extensions'][0]['forward_all_enabled'] = $forward_all_enabled;
				$array['extensions'][0]['forward_all_destination'] = $forward_all_destination;
				$array['extensions'][0]['forward_busy_enabled'] = $forward_busy_enabled;
				$array['extensions'][0]['forward_busy_destination'] = $forward_busy_destination;
				$array['extensions'][0]['forward_no_answer_enabled'] = $forward_no_answer_enabled;
				$array['extensions'][0]['forward_no_answer_destination'] = $forward_no_answer_destination;
				$array['extensions'][0]['forward_user_not_registered_enabled'] = $forward_user_not_registered_enabled;
				$array['extensions'][0]['forward_user_not_registered_destination'] = $forward_user_not_registered_destination;
			}

		//do not disturb (dnd) config
			if (permission_exists('do_not_disturb')) {
				$array['extensions'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
				$array['extensions'][0]['extension_uuid'] = $extension_uuid;
				$array['extensions'][0]['do_not_disturb'] = $dnd_enabled;
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
					if ($destination_found) {
						$array['extensions'][0]['follow_me_enabled'] = $follow_me_enabled;
					}
					else {
						$array['extensions'][0]['follow_me_enabled'] = 'false';
					}
				//build the follow me array
					$array['follow_me'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					$array['follow_me'][0]['follow_me_uuid'] = $follow_me_uuid;
					$array['follow_me'][0]['cid_name_prefix'] = $cid_name_prefix;
					$array['follow_me'][0]['cid_number_prefix'] = $cid_number_prefix;
					$array['follow_me'][0]['follow_me_ignore_busy'] = $follow_me_ignore_busy;
					if ($destination_found) {
						$array['follow_me'][0]['follow_me_enabled'] = $follow_me_enabled;
					}
					else {
						$array['follow_me'][0]['follow_me_enabled'] = 'false';
					}

					$d = 0;
					$destination_found = false;
					foreach ($destinations as $field) {
						if ($field['destination'] != '') {
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
			$p = new permissions;
			$p->add("extension_edit", "temp");

		//save the data
			$database = new database;
			$database->app_name = 'call_routing';
			$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
			$database->save($array);
			unset($array);
			//$message = $database->message;

		//remove the temporary permission
			$p->delete("extension_edit", "temp");

		//delete empty destination records
			if (is_array($follow_me_delete_uuids) && sizeof($follow_me_delete_uuids) > 0) {
				foreach ($follow_me_delete_uuids as $follow_me_delete_uuid) {
					$array['follow_me_destinations'][]['follow_me_destination_uuid'] = $follow_me_delete_uuid;
				}
				$database = new database;
				$database->app_name = 'call_routing';
				$database->app_uuid = '19806921-e8ed-dcff-b325-dd3e5da4959d';
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
				$dnd->enabled = $dnd_enabled;
			}

		//if follow me is enabled then process call forward and dnd first
			if ($follow_me_enabled == "true") {
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
			if ($follow_me_enabled != "true") {
				if ($forward_all_enabled == "true") {
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
			if ($_SESSION['device']['feature_sync']['boolean'] == "true") {
				$ring_count = ceil($call_timeout / 6);
				$feature_event_notify = new feature_event_notify;
				$feature_event_notify->domain_name = $_SESSION['domain_name'];
				$feature_event_notify->extension = $extension;
				$feature_event_notify->do_not_disturb = $dnd_enabled;
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

		//synchronize configuration
			if (is_readable($_SESSION['switch']['extensions']['dir'])) {
				require_once "app/extensions/resources/classes/extension.php";
				$ext = new extension;
				$ext->xml();
				unset($ext);
			}

		//clear the cache
			$cache = new cache;
			$cache->delete("directory:".$extension."@".$_SESSION['domain_name']);
			if (strlen($number_alias) > 0) {
				$cache->delete("directory:".$number_alias."@".$_SESSION['domain_name']);
			}

		//add the message
			message::add($text['confirm-update']);
	}

//show the header
	$document['title'] = $text['title-call_routing'];
	require_once "resources/header.php";

//pre-populate the form
	if (is_uuid($follow_me_uuid)) {
		$sql = "select * from v_follow_me ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and follow_me_uuid = :follow_me_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['follow_me_uuid'] = $follow_me_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		unset($sql, $parameters);

		if (is_array($row) && sizeof($row) != 0) {
			$cid_name_prefix = $row["cid_name_prefix"];
			$cid_number_prefix = $row["cid_number_prefix"];
			$follow_me_enabled = $row["follow_me_enabled"];
			$follow_me_ignore_busy = $row["follow_me_ignore_busy"];
			unset($row);

			$sql = "select * from v_follow_me_destinations ";
			$sql .= "where follow_me_uuid = :follow_me_uuid ";
			$sql .= "order by follow_me_order asc ";
			$parameters['follow_me_uuid'] = $follow_me_uuid;
			$database = new database;
			$result = $database->select($sql, $parameters, 'all');

			unset($destinations);
			foreach ($result as $x => &$row) {
				$destinations[$x]['uuid'] = $row["follow_me_destination_uuid"];
				$destinations[$x]['destination'] = $row["follow_me_destination"];
				$destinations[$x]['delay'] = $row["follow_me_delay"];
				$destinations[$x]['prompt'] = $row["follow_me_prompt"];
				$destinations[$x]['timeout'] = $row["follow_me_timeout"];
			}
			unset($sql, $parameters, $result, $row);
		}
	}

//get the extensions array - used with autocomplete
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by extension, number_alias asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters, $row);

//set the default
	if (!isset($dnd_enabled)) {
		//set the value from the database
		$dnd_enabled = $do_not_disturb;
	}

//prepare the autocomplete
	if($_SESSION['follow_me']['follow_me_autocomplete']['boolean'] == 'true') {

	echo "<link rel=\"stylesheet\" href=\"".PROJECT_PATH."/resources/jquery/jquery-ui.min.css\" />\n";
	echo "<script src=\"".PROJECT_PATH."/resources/jquery/jquery-ui.min.js\"></script>\n";
	echo "<script type=\"text/javascript\">\n";
	echo "\$(function() {\n";
	echo "	var extensions = [\n";
	foreach ($extensions as &$row) {
		if (strlen($number_alias) == 0) {
			echo "		\"".escape($row["extension"])."\",\n";
		}
		else {
			echo "		\"".escape($row["number_alias"])."\",\n";
		}
	}
	echo "	];\n";
	for ($n = 0; $n <= ((($_SESSION['follow_me']['max_destinations']['numeric'] != '') ? $_SESSION['follow_me']['max_destinations']['numeric'] : 5) - 1); $n++) {
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

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-call_forward']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'calls.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description']." <strong>".escape($extension)."</strong>\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	<strong>".$text['label-call_forward']."</strong>\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	$on_click = "document.getElementById('follow_me_disabled').checked=true; ";
	$on_click .= "$('#tr_follow_me_settings').slideUp('fast'); ";
	$on_click .= "document.getElementById('dnd_disabled').checked=true; ";
	$on_click .= "document.getElementById('forward_all_destination').focus(); ";
	echo "	<label for='forward_all_disabled'><input type='radio' name='forward_all_enabled' id='forward_all_disabled' onclick=\"\" value='false' ".(($forward_all_enabled == "false" || $forward_all_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='forward_all_enabled'><input type='radio' name='forward_all_enabled' id='forward_all_enabled' onclick=\"$on_click\" value='true' ".(($forward_all_enabled == "true") ? "checked='checked'" : null)." /> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "&nbsp;&nbsp;&nbsp;";
	echo "	<input class='formfld' type='text' name='forward_all_destination' id='forward_all_destination' maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".escape($forward_all_destination)."\">\n";
	echo "	<br />".$text['description-call_forward']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-on-busy']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('dnd_disabled').checked=true;";
	$on_click .= "document.getElementById('forward_busy_destination').focus();";
	echo "	<label for='forward_busy_disabled'><input type='radio' name='forward_busy_enabled' id='forward_busy_disabled' onclick=\"\" value='false' ".(($forward_busy_enabled == "false" || $forward_busy_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='forward_busy_enabled'><input type='radio' name='forward_busy_enabled' id='forward_busy_enabled' onclick=\"$on_click\" value='true' ".(($forward_busy_enabled == "true") ? "checked='checked'" : null)."/> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "&nbsp;&nbsp;&nbsp;";
	echo "	<input class='formfld' type='text' name='forward_busy_destination' id='forward_busy_destination' maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".escape($forward_busy_destination)."\">\n";
	echo "	<br />".$text['description-on-busy']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-no_answer']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('dnd_disabled').checked=true;";
	$on_click .= "document.getElementById('forward_no_answer_destination').focus();";
	echo "	<label for='forward_no_answer_disabled'><input type='radio' name='forward_no_answer_enabled' id='forward_no_answer_disabled' onclick=\"\" value='false' ".(($forward_no_answer_enabled == "false" || $forward_no_answer_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='forward_no_answer_enabled'><input type='radio' name='forward_no_answer_enabled' id='forward_no_answer_enabled' onclick=\"$on_click\" value='true' ".(($forward_no_answer_enabled == "true") ? "checked='checked'" : null)."/> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "&nbsp;&nbsp;&nbsp;";
	echo "	<input class='formfld' type='text' name='forward_no_answer_destination' id='forward_no_answer_destination' maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".escape($forward_no_answer_destination)."\">\n";
	echo "	<br />".$text['description-no_answer']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-not_registered']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('forward_user_not_registered_destination').focus();";
	echo "	<label for='forward_user_not_registered_disabled'><input type='radio' name='forward_user_not_registered_enabled' id='forward_user_not_registered_disabled' onclick=\"\" value='false' ".(($forward_user_not_registered_enabled == "false" || $forward_user_not_registered_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='forward_user_not_registered_enabled'><input type='radio' name='forward_user_not_registered_enabled' id='forward_user_not_registered_enabled' onclick=\"$on_click\" value='true' ".(($forward_user_not_registered_enabled == "true") ? "checked='checked'" : null)."/> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "&nbsp;&nbsp;&nbsp;";
	echo "	<input class='formfld' type='text' name='forward_user_not_registered_destination' id='forward_user_not_registered_destination' maxlength='255' placeholder=\"".$text['label-destination']."\" value=\"".escape($forward_user_not_registered_destination)."\">\n";
	echo "	<br />".$text['description-not_registered']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr><td colspan='2'><br /></td></tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	<strong>".$text['label-follow_me']."</strong>\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$on_click = "document.getElementById('forward_all_disabled').checked=true; ";
	$on_click .= "document.getElementById('dnd_disabled').checked=true; ";
	echo "	<label for='follow_me_disabled'><input type='radio' name='follow_me_enabled' id='follow_me_disabled' onclick=\"$('#tr_follow_me_settings').slideUp('fast');\" value='false' ".(($follow_me_enabled == "false" || $follow_me_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='follow_me_enabled'><input type='radio' name='follow_me_enabled' id='follow_me_enabled' onclick=\"$('#tr_follow_me_settings').slideDown('fast'); $on_click\" value='true' ".(($follow_me_enabled == "true") ? "checked='checked'" : null)."/> ".$text['label-enabled']."</label> \n";
	unset($on_click);
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	if ($follow_me_enabled == "true") { $style = ''; } else { $style = 'display: none;'; }
	echo "<div id='tr_follow_me_settings' style='$style'>\n";

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
	for ($n = 0; $n <= ((($_SESSION['follow_me']['max_destinations']['numeric'] != '') ? $_SESSION['follow_me']['max_destinations']['numeric'] : 5) - 1); $n++) {
		echo "		<input type='hidden' name='destinations[".$n."][uuid]' value='".(($destinations[$n]['uuid'] != '') ? $destinations[$n]['uuid'] : uuid())."'>\n";
		echo "		<tr>\n";
		echo "			<td><input class='formfld' style='min-width: 135px;' type='text' name='destinations[".$n."][destination]' id='destination_".$n."' maxlength='255' value=\"".escape($destinations[$n]['destination'])."\"></td>\n";
		echo "			<td>\n";
								destination_select('destinations['.$n.'][delay]', $destinations[$n]['delay'], '0');
		echo "			</td>\n";
		echo "			<td>\n";
								destination_select('destinations['.$n.'][timeout]', $destinations[$n]['timeout'], (($_SESSION['follow_me']['timeout']['numeric'] != '') ? $_SESSION['follow_me']['timeout']['numeric'] : 30));
		echo "			</td>\n";
		if (permission_exists('follow_me_prompt')) {
			echo "		<td>\n";
			echo "			<select class='formfld' style='width: 90px;' name='destinations[".$n."][prompt]'>\n";
			echo "				<option value=''></option>\n";
			echo "				<option value='1' ".(($destinations[$n]['prompt'])?"selected='selected'":null).">".$text['label-destination_prompt_confirm']."</option>\n";
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
		echo "				<label for='follow_me_ignore_busy_false'><input type='radio' name='follow_me_ignore_busy' id='follow_me_ignore_busy_false' value='false' onclick=\"\" ".(($follow_me_ignore_busy == "false" || $follow_me_ignore_busy == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
		echo "				<label for='follow_me_ignore_busy_true'><input type='radio' name='follow_me_ignore_busy' id='follow_me_ignore_busy_true' value='true' onclick=\"$on_click\" ".(($follow_me_ignore_busy == "true") ? "checked='checked'" : null)." /> ".$text['label-enabled']."</label> \n";
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
	$on_click = "document.getElementById('forward_all_disabled').checked=true;";
	$on_click .= "document.getElementById('follow_me_disabled').checked=true;";
	$on_click .= "$('#tr_follow_me_settings').slideUp('fast'); ";
	echo "	<label for='dnd_disabled'><input type='radio' name='dnd_enabled' id='dnd_disabled' value='false' onclick=\"\" ".(($dnd_enabled == "false" || $dnd_enabled == "") ? "checked='checked'" : null)." /> ".$text['label-disabled']."</label> \n";
	echo "	<label for='dnd_enabled'><input type='radio' name='dnd_enabled' id='dnd_enabled' value='true' onclick=\"$on_click\" ".(($dnd_enabled == "true") ? "checked='checked'" : null)." /> ".$text['label-enabled']."</label> \n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='id' value='".escape($extension_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
