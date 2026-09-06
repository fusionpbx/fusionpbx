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
	Mark J Crane <markjcrane@fusionpbx.com>
	denisent dev team
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!(permission_exists('paging_group_add') || permission_exists('paging_group_edit'))) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//connect to the database
	$database = database::new();

//set the domain name
	$domain_name = $_SESSION['domain_name'] ?? '';
	$domain_uuid = $_SESSION['domain_uuid'] ?? '';

//add the settings object
	$settings = new settings(["domain_uuid" => $domain_uuid, "user_uuid" => $_SESSION['user_uuid']]);

//set from session variables
	$button_icon_back = $settings->get('theme', 'button_icon_back', '');
	$button_icon_copy = $settings->get('theme', 'button_icon_copy', '');
	$button_icon_delete = $settings->get('theme', 'button_icon_delete', '');
	$button_icon_save = $settings->get('theme', 'button_icon_save', '');
	$input_toggle_style = $settings->get('theme', 'input_toggle_style', 'switch round');

//get the sounds
	$sounds = new sounds(['domain_uuid' => $_SESSION['domain_uuid'], 'domain_name' => $domain_name]);
	$sounds->sound_types = ['sounds'];
	$audio_files = $sounds->get();
	$sound_files = $audio_files['sounds'] ?? [];

//get the recordings
	$sql = "select recording_uuid, recording_name, recording_filename ";
	$sql .= "from v_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by recording_name asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$recordings = $database->select($sql, $parameters, 'all');

//action add or update
	if ((!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) || !empty($_REQUEST["paging_group_uuid"])) {
		$action = "update";
		$paging_group_uuid = $_REQUEST["id"];
		if (!empty($_REQUEST["paging_group_uuid"])) {
			$paging_group_uuid = $_REQUEST["paging_group_uuid"];
		}
	}
	else {
		$action = "add";
		$paging_group_uuid = '';
	}

//set the defaults
	$paging_group_name = '';
	$paging_group_extension = '';
	$paging_group_pin_number = '';
	$paging_group_type = '';
	$paging_group_announcement_source = 'none';
	$paging_group_announcement_sound = '';
	$paging_group_announcement_recording_uuid = '';
	$paging_group_caller_id_name = '';
	$paging_group_caller_id_number = '';
	$paging_group_delay = '';
	$paging_group_destination_status = '';
	$paging_group_hangup_all = 'true';
	$paging_group_timeout = '';
	$paging_group_enabled = 'true';
	$paging_group_description = '';
	$paging_group_destinations = [];
	$paging_group_destination_uuid = '';

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$paging_group_name = $_POST["paging_group_name"] ?? null;
		$paging_group_extension = $_POST["paging_group_extension"] ?? null;
		$dialplan_uuid = $_POST["dialplan_uuid"] ?? null;
		$paging_group_type = $_POST["paging_group_type"] ?? null;
		$paging_group_pin_number = $_POST["paging_group_pin_number"] ?? null;
		$paging_group_announcement_source = $_POST["paging_group_announcement_source"] ?? null;
		$paging_group_announcement_sound = $_POST["paging_group_announcement_sound"] ?? null;
		$paging_group_announcement_recording_uuid = $_POST["paging_group_announcement_recording_uuid"] ?? null;
		$paging_group_destinations = $_POST["paging_group_destinations"] ?? null;
		$paging_group_caller_id_name = $_POST["paging_group_caller_id_name"] ?? null;
		$paging_group_caller_id_number = $_POST["paging_group_caller_id_number"] ?? null;
		$paging_group_delay = $_POST["paging_group_delay"] ?? null;
		$paging_group_destination_status = $_POST["paging_group_destination_status"] ?? null;
		$paging_group_hangup_all = $_POST["paging_group_hangup_all"] ?? null;
		$paging_group_timeout = $_POST["paging_group_timeout"] ?? null;
		$paging_group_enabled = $_POST["paging_group_enabled"] ?? null;
		$paging_group_description = $_POST["paging_group_description"] ?? null;
		$paging_group_destinations_delete = $_POST["paging_group_destinations_delete"] ?? null;
	}

//process the data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: paging_groups.php');
				exit;
			}

		//process the http post data by submitted action
			if (!empty($_POST['action']) && strlen($_POST['action']) > 0) {

				//prepare the array
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $paging_group_uuid;

				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('paging_group_add')) {
							$obj = new paging_groups;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('paging_group_delete')) {
							$obj = new paging_groups;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('paging_group_edit')) {
							$obj = new paging_groups;
							$obj->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: paging_group_edit.php?id='.$paging_group_uuid);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			if (empty($paging_group_name)) { $msg .= $text['message-required']." ".$text['label-paging_group_name']."<br>\n"; }
			if (empty($paging_group_extension)) { $msg .= $text['message-required']." ".$text['label-paging_group_extension']."<br>\n"; }
			//if (strlen($dialplan_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-dialplan_uuid']."<br>\n"; }
			//if (strlen($paging_group_pin_number) == 0) { $msg .= $text['message-required']." ".$text['label-paging_group_pin_number']."<br>\n"; }
			//if (strlen($paging_group_destinations) == 0) { $msg .= $text['message-required']." ".$text['label-paging_group_destinations']."<br>\n"; }
			//if (strlen($paging_group_caller_id_name) == 0) { $msg .= $text['message-required']." ".$text['label-paging_group_caller_id_name']."<br>\n"; }
			//if (strlen($paging_group_caller_id_number) == 0) { $msg .= $text['message-required']." ".$text['label-paging_group_caller_id_number']."<br>\n"; }
			//if (strlen($paging_group_delay) == 0) { $msg .= $text['message-required']." ".$text['label-paging_group_delay']."<br>\n"; }
			//if (strlen($paging_group_destination_status) == 0) { $msg .= $text['message-required']." ".$text['label-paging_group_destination_status']."<br>\n"; }
			//if (strlen($paging_group_hangup_all) == 0) { $msg .= $text['message-required']." ".$text['label-paging_group_hangup_all']."<br>\n"; }
			//if (strlen($paging_group_timeout) == 0) { $msg .= $text['message-required']." ".$text['label-paging_group_timeout']."<br>\n"; }
			//if (strlen($paging_group_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-paging_group_enabled']."<br>\n"; }
			//if (empty($paging_group_description)) { $msg .= $text['message-required']." ".$text['label-paging_group_description']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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

		//add the paging_group_uuid
			if (!is_uuid($_POST["paging_group_uuid"])) {
				$paging_group_uuid = uuid();
			}

		//add the dialplan_uuid
			if (empty($_POST["dialplan_uuid"]) || !is_uuid($_POST["dialplan_uuid"])) {
				$dialplan_uuid = uuid();
			}

		//build the destinations string
			$destinations = '';
			if (is_array($paging_group_destinations)) {
				foreach ($paging_group_destinations as $row) {
					if (!empty($row['destination_number']) && trim($row['destination_number']) != '') {
						$destinations .= ($destinations != '' ? ',' : '').$row['destination_number'];
					}
				}
			}

		//determine the mute setting based on the paging group type
			$paging_group_mute = 'true';
			if ($paging_group_type == 'page') {
				$paging_group_mute = 'true';
			}
			else if ($paging_group_type == 'intercom') {
				$paging_group_mute = 'false';
			}

		//build the xml dialplan
			$dialplan_xml = "<extension name=\"".xml::sanitize($paging_group_name)."\">\n";
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".xml::sanitize($paging_group_extension)."\$\" >\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"caller_id_name=".xml::sanitize($paging_group_caller_id_name)."\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"caller_id_number=".xml::sanitize($paging_group_caller_id_number)."\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"pin_number=".xml::sanitize($paging_group_pin_number)."\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"destinations=".xml::sanitize($destinations). "\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"moderator=false\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"mute=".xml::sanitize($paging_group_mute)."\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"delay=".xml::sanitize($paging_group_delay)."\" />\n";
			$dialplan_xml .= "		<action application=\"set\" data=\"check_destination_status=".xml::sanitize($paging_group_destination_status)."\" />\n";
			if ($paging_group_hangup_all) {
				$dialplan_xml .= "		<action application=\"set\" data=\"api_hangup_hook=conference page-\${destination_number}@\${domain_name} hup all\" />\n";
			}
			if (!empty($paging_group_timeout) && is_numeric($paging_group_timeout) && $paging_group_timeout > 0) {
				$dialplan_xml .= "		<action application=\"set\" data=\"execute_on_answer=sched_hangup +".xml::sanitize($paging_group_timeout)." allotted_timeout\" />\n";
			}
			if ($paging_group_announcement_source == 'sound' && !empty($paging_group_announcement_sound)) {
				$dialplan_xml .= "		<action application=\"set\" data=\"recording_filename=\$\${sounds_dir}/".xml::sanitize($paging_group_announcement_sound)."\"/>\n";
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
					$dialplan_xml .= "		<action application=\"set\" data=\"recording_filename=".xml::sanitize($settings->get('switch', 'recordings').'/'.$domain_name.'/'.$announcement_recording_filename)."\"/>\n";
				}
			}
			$dialplan_xml .= "		<action application=\"lua\" data=\"page.lua\" />\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "</extension>\n";

		//build the dialplan array
			$array["dialplans"][0]["domain_uuid"] = $domain_uuid;
			$array["dialplans"][0]["dialplan_uuid"] = $dialplan_uuid;
			$array["dialplans"][0]["dialplan_name"] = $paging_group_name;
			$array["dialplans"][0]["dialplan_number"] = $paging_group_extension;
			$array["dialplans"][0]["dialplan_context"] = $_SESSION['domain_name'];
			$array["dialplans"][0]["dialplan_continue"] = 'false';
			$array["dialplans"][0]["dialplan_xml"] = $dialplan_xml;
			$array["dialplans"][0]["dialplan_order"] = "240";
			$array["dialplans"][0]["dialplan_enabled"] = $paging_group_enabled;
			$array["dialplans"][0]["dialplan_description"] = $paging_group_description;
			$array["dialplans"][0]["app_uuid"] = "1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2";

		//prepare the array
			$array['paging_groups'][0]['paging_group_uuid'] = $paging_group_uuid;
			$array['paging_groups'][0]['domain_uuid'] = $domain_uuid;
			$array['paging_groups'][0]['paging_group_name'] = $paging_group_name;
			$array['paging_groups'][0]['paging_group_extension'] = $paging_group_extension;
			$array['paging_groups'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['paging_groups'][0]['paging_group_type'] = $paging_group_type;
			$array['paging_groups'][0]['paging_group_pin_number'] = $paging_group_pin_number;
			$array['paging_groups'][0]['paging_group_announcement_source'] = $paging_group_announcement_source;
			$array['paging_groups'][0]['paging_group_announcement_sound'] = $paging_group_announcement_sound;
			$array['paging_groups'][0]['paging_group_announcement_recording_uuid'] = is_uuid($paging_group_announcement_recording_uuid) ? $paging_group_announcement_recording_uuid : null;
			$array['paging_groups'][0]['paging_group_caller_id_name'] = $paging_group_caller_id_name;
			$array['paging_groups'][0]['paging_group_caller_id_number'] = $paging_group_caller_id_number;
			$array['paging_groups'][0]['paging_group_delay'] = $paging_group_delay;
			$array['paging_groups'][0]['paging_group_destination_status'] = $paging_group_destination_status;
			$array['paging_groups'][0]['paging_group_hangup_all'] = $paging_group_hangup_all;
			$array['paging_groups'][0]['paging_group_timeout'] = $paging_group_timeout;
			$array['paging_groups'][0]['paging_group_enabled'] = $paging_group_enabled;
			$array['paging_groups'][0]['paging_group_description'] = $paging_group_description;
			$y = 0;
			if (is_array($paging_group_destinations)) {
				foreach ($paging_group_destinations as $row) {
					if (!empty($row['paging_group_destination_uuid']) && is_uuid($row['paging_group_destination_uuid'])) {
						$paging_group_destination_uuid = $row['paging_group_destination_uuid'];
					}
					else {
						$paging_group_destination_uuid = uuid();
					}
					if (strlen($row['destination_number']) > 0) {
						$array['paging_groups'][0]['paging_group_destinations'][$y]['paging_group_destination_uuid'] = $paging_group_destination_uuid;
						$array['paging_groups'][0]['paging_group_destinations'][$y]['destination_number'] = $row["destination_number"];
						$array['paging_groups'][0]['paging_group_destinations'][$y]['destination_enabled'] = $row["destination_enabled"];
						$array['paging_groups'][0]['paging_group_destinations'][$y]['destination_description'] = $row["destination_description"];
						$y++;
					} elseif (strlen($row['destination_number']) == 0 && !empty($row['paging_group_destination_uuid']) && is_uuid($row['paging_group_destination_uuid'])) {
						$paging_group_destinations_delete[] = [
							'checked' => 'true',
							'uuid' => $row['paging_group_destination_uuid']
						];
					}
				}
			}

		//save the data
			$database->save($array);

		//remove checked destinations
			if (
				$action == 'update'
				&& permission_exists('paging_group_destination_delete')
				&& is_array($paging_group_destinations_delete)
				&& @sizeof($paging_group_destinations_delete) != 0
				) {
				$obj = new paging_groups;
				$obj->paging_group_uuid = $paging_group_uuid;
				$obj->delete_destinations($paging_group_destinations_delete);
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: paging_group_edit.php?id='.urlencode($paging_group_uuid));
				return;
			}
	}

//pre-populate the form
	if (!empty($_GET['id']) && is_uuid($_GET['id']) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$paging_group_uuid = $_GET['id'];
		$sql = "select ";
		$sql .= " paging_group_uuid, ";
		$sql .= " paging_group_name, ";
		$sql .= " paging_group_extension, ";
		$sql .= " dialplan_uuid, ";
		$sql .= " paging_group_type, ";
		$sql .= " paging_group_pin_number, ";
		$sql .= " paging_group_announcement_source, ";
		$sql .= " paging_group_announcement_sound, ";
		$sql .= " paging_group_announcement_recording_uuid, ";
		$sql .= " paging_group_caller_id_name, ";
		$sql .= " paging_group_caller_id_number, ";
		$sql .= " paging_group_delay , ";
		$sql .= " paging_group_destination_status , ";
		$sql .= " paging_group_hangup_all , ";
		$sql .= " paging_group_timeout, ";
		$sql .= " paging_group_enabled , ";
		$sql .= " paging_group_description ";
		$sql .= "from v_paging_groups ";
		$sql .= "where paging_group_uuid = :paging_group_uuid ";
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['paging_group_uuid'] = $paging_group_uuid;
		$parameters['domain_uuid'] = $domain_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$paging_group_name = $row["paging_group_name"];
			$paging_group_extension = $row["paging_group_extension"];
			$dialplan_uuid = $row["dialplan_uuid"];
			$paging_group_type = $row["paging_group_type"];
			$paging_group_pin_number = $row["paging_group_pin_number"];
			$paging_group_announcement_source = $row["paging_group_announcement_source"];
			$paging_group_announcement_sound = $row["paging_group_announcement_sound"];
			$paging_group_announcement_recording_uuid = $row["paging_group_announcement_recording_uuid"];
			$paging_group_caller_id_name = $row["paging_group_caller_id_name"];
			$paging_group_caller_id_number = $row["paging_group_caller_id_number"];
			$paging_group_delay = $row["paging_group_delay"];
			$paging_group_destination_status = $row["paging_group_destination_status"];
			$paging_group_hangup_all = $row["paging_group_hangup_all"];
			$paging_group_timeout = $row["paging_group_timeout"];
			$paging_group_enabled = $row["paging_group_enabled"];
			$paging_group_description = $row["paging_group_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the child data
	if (is_uuid($paging_group_uuid)) {
		$sql = "select ";
		$sql .= " paging_group_destination_uuid, ";
		$sql .= " paging_group_uuid, ";
		$sql .= " destination_number, ";
		$sql .= " destination_enabled , ";
		$sql .= " destination_description ";
		$sql .= "from v_paging_group_destinations ";
		$sql .= "where paging_group_uuid = :paging_group_uuid ";
		$parameters['paging_group_uuid'] = $paging_group_uuid;
		$paging_group_destinations = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add an empty row
	$x = is_array($paging_group_destinations) ? count($paging_group_destinations) : 0;
	$paging_group_destinations[$x]['paging_group_uuid'] = $paging_group_uuid;
	$paging_group_destinations[$x]['paging_group_destination_uuid'] = '';
	$paging_group_destinations[$x]['destination_number'] = '';
	$paging_group_destinations[$x]['destination_enabled'] = '';
	$paging_group_destinations[$x]['destination_description'] = '';

//get the extensions and the users assigned to them
	$sql = "select ";
	$sql .= " extension, ";
	$sql .= " effective_caller_id_name, ";
	$sql .= " description ";
	$sql .= "from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and enabled = 'true' ";
	$sql .= "order by extension asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	$extension_users = [];
	foreach ($extensions as $row) {
		$ext = $row['extension'];
		$extension_users[$ext]['extension'] = $row['extension'];
		$extension_users[$ext]['name'] = $row['effective_caller_id_name'] ?? $row['description'];
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-paging_groups'];
	require_once "resources/header.php";

//add the announcement source and playback support
	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		echo "<script type='text/javascript' language='JavaScript'>\n";
		echo "	function set_playable(id, audio_selected, audio_type) {\n";
		echo "		var file_ext = audio_selected.split('.').pop();\n";
		echo "		var mime_type = '';\n";
		echo "		switch (file_ext) {\n";
		echo "			case 'wav': mime_type = 'audio/wav'; break;\n";
		echo "			case 'mp3': mime_type = 'audio/mpeg'; break;\n";
		echo "			case 'ogg': mime_type = 'audio/ogg'; break;\n";
		echo "			case 'oga': mime_type = 'audio/ogg'; break;\n";
		echo "		}\n";
		echo "		if (audio_type == 'recordings') {\n";
		echo "			if (audio_selected.includes('/')) { audio_selected = audio_selected.split('/').pop(); }\n";
		echo "			if (mime_type != '') {\n";
		echo "				$('#recording_audio_' + id).attr('src', '../recordings/recordings.php?action=download&type=rec&filename=' + audio_selected);\n";
		echo "				$('#recording_audio_' + id).attr('type', mime_type);\n";
		echo "				$('#recording_button_' + id).show();\n";
		echo "			}\n";
		echo "		}\n";
		echo "		else if (audio_type == 'sounds') {\n";
		echo "			if (mime_type == '') { mime_type = 'audio/wav'; }\n";
		echo "			$('#recording_audio_' + id).attr('src', '../switch/sounds.php?action=download&filename=' + audio_selected);\n";
		echo "			$('#recording_audio_' + id).attr('type', mime_type);\n";
		echo "			$('#recording_button_' + id).show();\n";
		echo "		}\n";
		echo "	}\n";
		echo "</script>\n";
	}
	echo "<script type='text/javascript' language='JavaScript'>\n";
	echo "	function update_announcement_source() {\n";
	echo "		const source = document.getElementById('paging_group_announcement_source').value;\n";
	echo "		const soundRow = document.getElementById('paging_group_announcement_sound_row');\n";
	echo "		const recordingRow = document.getElementById('paging_group_announcement_recording_row');\n";
	echo "		soundRow.style.display = (source === 'sound') ? '' : 'none';\n";
	echo "		recordingRow.style.display = (source === 'recording') ? '' : 'none';\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='paging_group_uuid' value='".escape($paging_group_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-paging_groups']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$button_icon_back,'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'paging_groups.php']);
	if ($action == 'update') {
		if (permission_exists('paging_group_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$button_icon_copy,'id'=>'btn_copy','name'=>'btn_copy','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('paging_group_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$button_icon_delete,'id'=>'btn_delete','name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$button_icon_save,'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-paging_groups']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('paging_group_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('paging_group_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_group_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='paging_group_name' maxlength='255' value='".escape($paging_group_name)."'>\n";
	echo "<br />\n";
	echo $text['description-paging_group_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_group_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='paging_group_extension' maxlength='255' value='".escape($paging_group_extension)."' required='required' placeholder='".escape($settings->get('paging_groups', 'extension_range', '') ?? '')."'>\n";
	echo "<br />\n";
	echo $text['description-paging_group_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_group_destinations']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable'>".$text['label-destination_number']."</td>\n";
	echo "			<td class='vtable'>".$text['label-destination_enabled']."</td>\n";
	echo "			<td class='vtable'>".$text['label-destination_description']."</td>\n";
	if (is_array($paging_group_destinations) && @sizeof($paging_group_destinations) > 1 && permission_exists('paging_group_destination_delete')) {
		echo "			<td class='vtable edit_delete_checkbox_all' onmouseover=\"swap_display('delete_label_details', 'delete_toggle_details');\" onmouseout=\"swap_display('delete_label_details', 'delete_toggle_details');\">\n";
		echo "				<span id='delete_label_details'>".$text['label-delete']."</span>\n";
		echo "				<span id='delete_toggle_details'><input type='checkbox' id='checkbox_all_details' name='checkbox_all' onclick=\"edit_all_toggle('details'); checkbox_on_change(this);\"></span>\n";
		echo "			</td>\n";
	}
	echo "		</tr>\n";
	$x = 0;
	if (permission_exists('paging_group_destination_edit')) {
		foreach($paging_group_destinations as $row) {
			echo "			<tr>\n";
			echo "				<td class='formfld'>\n";
			echo "					<input type='hidden' name='paging_group_destinations[$x][paging_group_uuid]' value=\"".escape($row["paging_group_uuid"])."\">\n";
			echo "					<input type='hidden' name='paging_group_destinations[$x][paging_group_destination_uuid]' value=\"".escape($row['paging_group_destination_uuid'])."\">\n";
			$oninput = empty($row['paging_group_destination_uuid']) ? "oninput=\"document.getElementById('paging_group_destinations_".$x."_destination_enabled').value = (this.value != '' ? true : false);\"" : null; // new record
			echo "					<div class='searchable_select_wrapper'>\n";
			echo "						<input class='formfld extension_search_input' type='text' name='paging_group_destinations[$x][destination_number]' value='".escape($row['destination_number'])."' ".$oninput.">\n";
			echo "						<div class='search_results'></div>\n";
			echo "						<select class='extension_hidden_select' style='display:none;'>\n";
			foreach ($extension_users as $ext_data) {
				echo "						<option value='".escape($ext_data['extension'])."' data-users='".$ext_data['name']."'>".escape($ext_data['extension'])."</option>";
			}
			echo "						</select>\n";
			echo "					</div>\n";

			?>
			<script>
			document.addEventListener('DOMContentLoaded', function() {
				const wrappers = document.querySelectorAll('.searchable_select_wrapper:has(.extension_hidden_select)');

				wrappers.forEach(wrapper => {
					const input = wrapper.querySelector('.extension_search_input');
					const hidden_select = wrapper.querySelector('.extension_hidden_select');
					const results = wrapper.querySelector('.search_results');

					// Cache options once for performance
					const options = Array.from(hidden_select.querySelectorAll('option'));

					if (!input || !results) return;

					function render_results() {
						// Hide other dropdowns before showing the active one
						document.querySelectorAll('.search_results').forEach(dropdown => { dropdown.style.display = 'none'; });
						results.style.display = 'block';

						const term = this.value.trim().toLowerCase();

						// Clear previous results
						results.innerHTML = '';

						options.forEach(option => {
							const extension = option.value.trim().toLowerCase();
							const users = (option.getAttribute('data-users') || '').split(',').map(u => u.trim()).filter(Boolean);
							const users_lower = users.map(user => user.toLowerCase());

							// Match if extension or username contains the search term
							const matches_extension = extension.includes(term);
							const matches_user = users_lower.some(user => user.includes(term));

							if (matches_extension || matches_user) {
								const item = document.createElement('div');
								item.className = 'search_result_item';

								const extension = document.createElement('div');
								extension.className = 'search_result_name';
								extension.textContent = option.value;

								const username = document.createElement('div');
								username.className = 'search_result_description';
								username.textContent = option.getAttribute('data-users') || '';

								item.appendChild(extension);
								item.appendChild(username);

								// Click to populate input & hidden select
								item.addEventListener('click', () => {
									input.value = option.value;
									hidden_select.value = option.value;
									results.style.display = 'none';

									input.dispatchEvent(new Event('focus', { bubbles: true }));
									input.dispatchEvent(new Event('input',  { bubbles: true }));
								});

								results.appendChild(item);
							}
						});
					}

					input.addEventListener('focus',  render_results);
					input.addEventListener('input',  render_results);
				});
			});
			</script>
			<?php
			echo "				</td>\n";
			echo "				<td class='formfld'>\n";
			if ($input_toggle_style_switch) {
				echo "	<span class='switch'>\n";
			}
			echo "	<select class='formfld' id='paging_group_destinations_".$x."_destination_enabled' name='paging_group_destinations[$x][destination_enabled]'>\n";
			echo "		<option value='true' ".($row['destination_enabled'] == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
			echo "		<option value='false' ".($row['destination_enabled'] == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
			echo "	</select>\n";
			if ($input_toggle_style_switch) {
				echo "		<span class='slider'></span>\n";
				echo "	</span>\n";
			}
			echo "			</td>\n";
			echo "				<td class='formfld'>\n";
			echo "				<input type='text' class='formfld' name='paging_group_destinations[$x][destination_description]' style='line-height: 1;' value='".escape($row["destination_description"])."'>\n";
			echo "			</td>\n";
			if (is_array($paging_group_destinations) && @sizeof($paging_group_destinations) > 1 && permission_exists('paging_group_destination_delete')) {
				if (!empty($row['paging_group_destination_uuid']) && is_uuid($row['paging_group_destination_uuid'])) {
					echo "		<td class='vtable' style='text-align: center; padding-bottom: 3px;'>\n";
					echo "			<input type='checkbox' name='paging_group_destinations_delete[".$x."][checked]' value='true' class='chk_delete checkbox_details' onclick=\"checkbox_on_change(this);\">\n";
					echo "			<input type='hidden' name='paging_group_destinations_delete[".$x."][uuid]' value='".escape($row['paging_group_destination_uuid'])."' />\n";
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
	echo $text['description-destination_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_mode']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' id='paging_group_type' name='paging_group_type'>\n";
	echo "		<option value='page' ".($paging_group_type == 'page' ? "selected='selected'" : null).">".$text['option-one_way_paging']."</option>\n";
	echo "		<option value='intercom' ".($paging_group_type == 'intercom' ? "selected='selected'" : null).">".$text['option-two_way_intercom']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-paging_mode']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_group_pin_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='paging_group_pin_number' maxlength='255' value='".escape($paging_group_pin_number)."'>\n";
	echo "<br />\n";
	echo $text['description-paging_group_pin_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-announcement_source']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' id='paging_group_announcement_source' name='paging_group_announcement_source' onchange='update_announcement_source();'>\n";
	echo "		<option value='none' ".($paging_group_announcement_source == 'none' ? "selected='selected'" : null).">".$text['option-none']."</option>\n";
	echo "		<option value='sound' ".($paging_group_announcement_source == 'sound' ? "selected='selected'" : null).">".$text['option-sound']."</option>\n";
	echo "		<option value='recording' ".($paging_group_announcement_source == 'recording' ? "selected='selected'" : null).">".$text['option-recording']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-announcement_source']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "\n";

	$instance_id = 'paging_announcement_sound';
	echo "<tr id='paging_group_announcement_sound_row' style='".($paging_group_announcement_source == 'sound' ? '' : 'display: none;')."'>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-sound']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		echo "	<div class='playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' onclick=\"recording_play('".$instance_id."', document.getElementById('paging_group_announcement_sound').value, 'sounds');\" style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important; max-width: 480px;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></div>\n";
	}
	echo "	<select class='formfld searchable_select' id='paging_group_announcement_sound' name='paging_group_announcement_sound' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.value, 'sounds');\"" : null).">\n";
	echo "		<option value=''></option>\n";
	$playable = '';
	$mime_type = '';
	foreach ($sound_files as $sound) {
		$sound_value = $sound['value'] ?? $sound;
		$sound_name = $sound['name'] ?? $sound;
		if (!empty($paging_group_announcement_sound) && $paging_group_announcement_sound == $sound_value) {
			$selected = "selected='selected'";
			$playable = '../switch/sounds.php?action=download&filename='.$sound_value;
		}
		else {
			$selected = null;
		}
		echo "		<option value='".escape($sound_value)."' ".$selected.">".escape($sound_name)."</option>\n";
	}
	if ((permission_exists('recording_play') || permission_exists('recording_download')) && !empty($paging_group_announcement_sound)) {
		$mime_type = 'audio/wav';
	}
	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".$playable."' type='".$mime_type."'></audio>";
		echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$settings->get('theme', 'button_icon_play'),'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($paging_group_announcement_sound) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."', document.getElementById('paging_group_announcement_sound').value, 'sounds');"]);
	}
	echo "<br />\n";
	echo $text['description-sound']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "\n";

	$instance_id = 'paging_announcement_recording';
	echo "<tr id='paging_group_announcement_recording_row' style='".($paging_group_announcement_source == 'recording' ? '' : 'display: none;')."'>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-recording']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		echo "	<div class='playback_progress_bar_background' id='recording_progress_bar_".$instance_id."' onclick=\"recording_play('".$instance_id."', document.getElementById('paging_group_announcement_recording_uuid').options[document.getElementById('paging_group_announcement_recording_uuid').selectedIndex].getAttribute('data-filename'), 'recordings');\" style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important; max-width: 480px;' align='left'><span class='playback_progress_bar' id='recording_progress_".$instance_id."'></span></div>\n";
	}
	echo "	<select class='formfld searchable_select' id='paging_group_announcement_recording_uuid' name='paging_group_announcement_recording_uuid' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('".$instance_id."'); set_playable('".$instance_id."', this.options[this.selectedIndex].getAttribute('data-filename'), 'recordings');\"" : null).">\n";
	echo "		<option value=''></option>\n";
	$playable = '';
	$mime_type = '';
	if (is_array($recordings) && @sizeof($recordings) != 0) {
		foreach ($recordings as $recording) {
			$recording_uuid = $recording['recording_uuid'];
			$recording_name = $recording['recording_name'];
			$recording_filename = $recording['recording_filename'];
			if ($paging_group_announcement_recording_uuid == $recording_uuid) {
				$selected = "selected='selected'";
				$playable = '../recordings/recordings.php?action=download&type=rec&filename='.$recording_filename;
			}
			else {
				$selected = null;
			}
			echo "		<option value='".escape($recording_uuid)."' data-filename='".escape($recording_filename)."' ".$selected.">".escape($recording_name)."</option>\n";
		}
	}
	else {
		echo $text['description-no_recordings']."\n";
	}
	if ((permission_exists('recording_play') || permission_exists('recording_download')) && !empty($playable)) {
		$ext = pathinfo($playable, PATHINFO_EXTENSION);
		switch ($ext) {
			case 'wav' : $mime_type = 'audio/wav'; break;
			case 'mp3' : $mime_type = 'audio/mpeg'; break;
			case 'ogg' : $mime_type = 'audio/ogg'; break;
			default: $mime_type = '';
		}
	}
	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		echo "<audio id='recording_audio_".$instance_id."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$instance_id."')\" onended=\"recording_reset('".$instance_id."');\" src='".$playable."' type='".$mime_type."'></audio>";
		echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$settings->get('theme', 'button_icon_play'),'id'=>'recording_button_'.$instance_id,'style'=>'display: '.(!empty($mime_type) ? 'inline' : 'none'),'onclick'=>"recording_play('".$instance_id."', document.getElementById('paging_group_announcement_recording_uuid').options[document.getElementById('paging_group_announcement_recording_uuid').selectedIndex].getAttribute('data-filename'), 'recordings');"]);
	}
	echo "<br />\n";
	echo $text['description-recording']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_group_timeout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' id='paging_group_timeout' name='paging_group_timeout'>\n";
	$timeout_options = ['', '15', '30', '60', '90', '120', '180', '300', '600', '900'];
	foreach ($timeout_options as $timeout_option) {
		echo "		<option value='".$timeout_option."' ".((int)$paging_group_timeout == $timeout_option ? "selected='selected'" : null).">$timeout_option</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-paging_group_timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "\n";

	if (permission_exists('paging_group_caller_id_name')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-paging_group_caller_id_name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<input class='formfld' type='text' name='paging_group_caller_id_name' maxlength='255' value='".escape($paging_group_caller_id_name)."'>\n";
		echo "<br />\n";
		echo $text['description-paging_group_caller_id_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('paging_group_caller_id_number')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-paging_group_caller_id_number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<input class='formfld' type='text' name='paging_group_caller_id_number' maxlength='255' value='".escape($paging_group_caller_id_number)."'>\n";
		echo "<br />\n";
		echo $text['description-paging_group_caller_id_number']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('paging_group_delay')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-paging_group_delay']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		if ($input_toggle_style_switch) {
			echo "	<span class='switch'>\n";
		}
		echo "	<select class='formfld' id='paging_group_delay' name='paging_group_delay'>\n";
		echo "		<option value='true' ".($paging_group_delay == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($paging_group_delay == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
		if ($input_toggle_style_switch) {
			echo "		<span class='slider'></span>\n";
			echo "	</span>\n";
		}
		echo "<br />\n";
		echo $text['description-paging_group_delay']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('paging_group_destination_status')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-paging_group_destination_status']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		if ($input_toggle_style_switch) {
			echo "	<span class='switch'>\n";
		}
		echo "	<select class='formfld' id='paging_group_destination_status' name='paging_group_destination_status'>\n";
		echo "		<option value='true' ".($paging_group_destination_status == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($paging_group_destination_status == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
		if ($input_toggle_style_switch) {
			echo "		<span class='slider'></span>\n";
			echo "	</span>\n";
		}
		echo "<br />\n";
		echo $text['description-paging_group_destination_status']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('paging_group_hangup_all')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-paging_group_hangup_all']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		if ($input_toggle_style_switch) {
			echo "	<span class='switch'>\n";
		}
		echo "	<select class='formfld' id='paging_group_hangup_all' name='paging_group_hangup_all'>\n";
		echo "		<option value='true' ".($paging_group_hangup_all == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($paging_group_hangup_all == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
		if ($input_toggle_style_switch) {
			echo "		<span class='slider'></span>\n";
			echo "	</span>\n";
		}
		echo "<br />\n";
		echo $text['description-paging_group_hangup_all']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_group_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='paging_group_enabled' name='paging_group_enabled'>\n";
	echo "		<option value='true' ".($paging_group_enabled == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($paging_group_enabled == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-paging_group_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-paging_group_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<textarea class='formfld' name='paging_group_description' style='width: 185px; height: 80px;'>".escape($paging_group_description)."</textarea>\n";
	echo "<br />\n";
	echo $text['description-paging_group_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

	if (!empty($dialplan_uuid)) {
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
	}
	if (!empty($paging_group_uuid)) {
		echo "<input type='hidden' name='paging_group_uuid' value='".escape($paging_group_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
