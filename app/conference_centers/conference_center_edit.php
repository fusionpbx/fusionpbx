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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('conference_center_add') || permission_exists('conference_center_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$conference_center_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the defaults
	$conference_center_name = '';
	$conference_center_extension = '';
	$conference_center_description = '';

//process the user data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//delete the conference center
			if (!empty($_POST['action']) && $_POST['action'] == 'delete' && permission_exists('conference_center_delete') && is_uuid($conference_center_uuid)) {
				//prepare
					$array[0]['checked'] = 'true';
					$array[0]['uuid'] = $conference_center_uuid;
				//delete
					$obj = new conference_centers;
					$obj->delete_conference_centers($array);
				//redirect
					header('Location: conference_centers.php');
					exit;
			}

		//get http post variables and set them to php variables
			$conference_center_uuid = $_POST["conference_center_uuid"] ?? null;
			$dialplan_uuid = $_POST["dialplan_uuid"] ?? null;
			$conference_center_name = $_POST["conference_center_name"];
			$conference_center_extension = $_POST["conference_center_extension"];
			$conference_center_greeting = $_POST["conference_center_greeting"];
			$conference_center_pin_length = $_POST["conference_center_pin_length"];
			$conference_center_enabled = $_POST["conference_center_enabled"] ?? 'false';
			$conference_center_description = $_POST["conference_center_description"];

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: conference_centers.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (empty($dialplan_uuid)) { $msg .= "Please provide: Dialplan UUID<br>\n"; }
			if (empty($conference_center_name)) { $msg .= "Please provide: Name<br>\n"; }
			if (empty($conference_center_extension)) { $msg .= "Please provide: Extension<br>\n"; }
			if (empty($conference_center_pin_length)) { $msg .= "Please provide: PIN Length<br>\n"; }
			//if (empty($conference_center_order)) { $msg .= "Please provide: Order<br>\n"; }
			//if (empty($conference_center_description)) { $msg .= "Please provide: Description<br>\n"; }
			if (empty($conference_center_enabled)) { $msg .= "Please provide: Enabled<br>\n"; }
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

		//add the conference_center_uuid
			if (empty($_POST["conference_center_uuid"]) || !is_uuid($_POST["conference_center_uuid"])) {
				$conference_center_uuid = uuid();
			}

		//add the dialplan_uuid
			if (empty($_POST["dialplan_uuid"]) || !is_uuid($_POST["dialplan_uuid"])) {
				$dialplan_uuid = uuid();
			}

		//prepare the array
		    $array['conference_centers'][0]['domain_uuid'] = $_SESSION['domain_uuid'];;
		    $array['conference_centers'][0]['conference_center_uuid'] = $conference_center_uuid;
		    $array['conference_centers'][0]['dialplan_uuid'] = $dialplan_uuid;
		    $array['conference_centers'][0]['conference_center_name'] = $conference_center_name;
		    $array['conference_centers'][0]['conference_center_extension'] = $conference_center_extension;
		    $array['conference_centers'][0]['conference_center_greeting'] = $conference_center_greeting;
		    $array['conference_centers'][0]['conference_center_pin_length'] = $conference_center_pin_length;
		    $array['conference_centers'][0]['conference_center_enabled'] = $conference_center_enabled;
		    $array['conference_centers'][0]['conference_center_description'] = $conference_center_description;

		//build the xml dialplan
			$dialplan_xml = "<extension name=\"".xml::sanitize($conference_center_name)."\" continue=\"\" uuid=\"".xml::sanitize($dialplan_uuid)."\">\n";
			if ($conference_center_pin_length > 1 && $conference_center_pin_length < 4) {
				$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^(".xml::sanitize($conference_center_extension).")(\d{".xml::sanitize($conference_center_pin_length)."})$\" break=\"on-true\">\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"destination_number=$1\"/>\n";
				$dialplan_xml .= "		<action application=\"set\" data=\"pin_number=$2\"/>\n";
				$dialplan_xml .= "		<action application=\"lua\" data=\"app.lua conference_center\"/>\n";
				$dialplan_xml .= "	</condition>\n";
			}
			$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".xml::sanitize($conference_center_extension)."$\">\n";
			$dialplan_xml .= "		<action application=\"lua\" data=\"app.lua conference_center\"/>\n";
			$dialplan_xml .= "	</condition>\n";
			$dialplan_xml .= "</extension>\n";

		//build the dialplan array
			$array['dialplans'][0]["domain_uuid"] = $_SESSION['domain_uuid'];
			$array['dialplans'][0]["dialplan_uuid"] = $dialplan_uuid;
			$array['dialplans'][0]["dialplan_name"] = $conference_center_name;
			$array['dialplans'][0]["dialplan_number"] = $conference_center_extension;
			$array['dialplans'][0]["dialplan_context"] = $_SESSION['domain_name'];
			$array['dialplans'][0]["dialplan_continue"] = "false";
			$array['dialplans'][0]["dialplan_xml"] = $dialplan_xml;
			$array['dialplans'][0]["dialplan_order"] = "333";
			$array['dialplans'][0]["dialplan_enabled"] = $conference_center_enabled;
			$array['dialplans'][0]["dialplan_description"] = $conference_center_description;
			$array['dialplans'][0]["app_uuid"] = "b81412e8-7253-91f4-e48e-42fc2c9a38d9";

		//add the dialplan permission
			$p = new permissions;
			$p->add("dialplan_add", "temp");
			$p->add("dialplan_edit", "temp");

		//save to the data
			$database = new database;
			$database->app_name = "conference_centers";
			$database->app_uuid = "b81412e8-7253-91f4-e48e-42fc2c9a38d9";
			$database->save($array);
			$message = $database->message;
			unset($array);

		//remove the temporary permission
			$p->delete("dialplan_add", "temp");
			$p->delete("dialplan_edit", "temp");

		//debug information
			//echo "<pre>\n";
			//print_r($message);
			//echo "</pre>\n";
			//exit;

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["domain_name"]);

		//clear the destinations session array
			if (isset($_SESSION['destinations']['array'])) {
				unset($_SESSION['destinations']['array']);
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					message::add($text['message-add']);
				}
				if ($action == "update") {
					message::add($text['message-update']);
				}
				header("Location: conference_centers.php");
				return;
			}
	} //(is_array($_POST) && empty($_POST["persistformvar"]))

//pre-populate the form
	if (!empty($_GET) && empty($_POST["persistformvar"])) {
		$conference_center_uuid = $_GET["id"];
		$sql = "select * from v_conference_centers ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and conference_center_uuid = :conference_center_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['conference_center_uuid'] = $conference_center_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters ?? null, 'row');
		if (!empty($row)) {
			$conference_center_uuid = $row["conference_center_uuid"];
			$dialplan_uuid = $row["dialplan_uuid"];
			$conference_center_name = $row["conference_center_name"];
			$conference_center_extension = $row["conference_center_extension"];
			$conference_center_greeting = $row["conference_center_greeting"];
			$conference_center_pin_length = $row["conference_center_pin_length"];
			$conference_center_enabled = $row["conference_center_enabled"];
			$conference_center_description = $row["conference_center_description"];
		}
		unset($sql, $parameters, $row);
	}

//set defaults
	if (empty($conference_center_enabled)) { $conference_center_enabled = "true"; }
	if (empty($conference_center_pin_length)) { $conference_center_pin_length = 9; }

//get the recordings
	$sql = "select recording_name, recording_filename from v_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by recording_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$recordings = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//get the phrases
	$sql = "select * from v_phrases ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$phrases = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//get the streams
	$sql = "select * from v_streams ";
	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$sql .= "and stream_enabled = 'true' ";
	$sql .= "order by stream_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$streams = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-conference_center'];
	require_once "resources/header.php";

//show the content
	if (permission_exists('recording_play') || permission_exists('recording_download')) {
		echo "<script type='text/javascript' language='JavaScript'>\n";
		echo "	function set_playable(id, greeting_selected, greeting_type) {\n";
		echo "		file_ext = greeting_selected.split('.').pop();\n";
		echo "		var audio_file_type = '';\n";
		echo "		switch (file_ext) {\n";
		echo "			case 'wav': audio_file_type = 'audio/wav'; break;\n";
		echo "			case 'mp3': audio_file_type = 'audio/mpeg'; break;\n";
		echo "			case 'ogg': audio_file_type = 'audio/ogg'; break;\n";
		echo "		}\n";
		echo "		if (audio_file_type != '' && (greeting_type == 'recordings' || greeting_type == 'sounds')) {\n";
		echo "			if (greeting_type == 'recordings') {\n";
		echo "				if (greeting_selected.includes('/')) {\n";
		echo "					greeting_selected = greeting_selected.split('/').pop()\n";
		echo "				}\n";
		echo "				$('#recording_audio_' + id).attr('src', '../recordings/recordings.php?action=download&type=rec&filename=' + greeting_selected);\n";
		echo "			}\n";
		echo "			else if (greeting_type == 'sounds') {\n";
		echo "				$('#recording_audio_' + id).attr('src', '../switch/sounds.php?action=download&filename=' + greeting_selected);\n";
		echo "			}\n";
		echo "			$('#recording_audio_' + id).attr('type', audio_file_type);\n";
		echo "			$('#recording_button_' + id).show();\n";
		echo "		}\n";
		echo "		else {\n";
		echo "			$('#recording_button_' + id).hide();\n";
		echo "			$('#recording_audio_' + id).attr('src','').attr('type','');\n";
		echo "		}\n";
		echo "	}\n";
		echo "</script>\n";
	}

	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-conference_center']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'conference_centers.php']);
	if ($action == 'update' && permission_exists('conference_center_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update' && permission_exists('conference_center_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_name' maxlength='255' value=\"".escape($conference_center_name)."\">\n";
	echo "<br />\n";
	echo $text['description-conference_center_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_extension' maxlength='255' value=\"".escape($conference_center_extension)."\">\n";
	echo "<br />\n";
	echo $text['description-conference_center_extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' rowspan='2' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_greeting']."\n";
	echo "</td>\n";
	echo "<td class='vtable playback_progress_bar_background' id='recording_progress_bar_greeting' style='display: none; border-bottom: none; padding-top: 0 !important; padding-bottom: 0 !important;' align='left'><span class='playback_progress_bar' id='recording_progress_greeting'></span></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='vtable' align='left'>\n";
	if (if_group("superadmin")) {
		$destination_id = "conference_center_greeting";
		$script = "<script>\n";
		$script .= "var objs;\n";
		$script .= "\n";
		$script .= "function changeToInput".$destination_id."(obj){\n";
		$script .= "	tb=document.createElement('INPUT');\n";
		$script .= "	tb.type='text';\n";
		$script .= "	tb.name=obj.name;\n";
		$script .= "	tb.className='formfld';\n";
		$script .= "	tb.setAttribute('id', '".$destination_id."');\n";
		$script .= "	tb.setAttribute('style', 'width: ' + obj.offsetWidth + 'px;');\n";
		if (!empty($on_change)) {
			$script .= "	tb.setAttribute('onchange', \"".$on_change."\");\n";
			$script .= "	tb.setAttribute('onkeyup', \"".$on_change."\");\n";
		}
		$script .= "	tb.value=obj.options[obj.selectedIndex].value;\n";
		$script .= "	document.getElementById('btn_select_to_input_".$destination_id."').style.display = 'none';\n";
		$script .= "	tbb=document.createElement('INPUT');\n";
		$script .= "	tbb.setAttribute('class', 'btn');\n";
		$script .= "	tbb.setAttribute('style', 'margin-left: 4px;');\n";
		$script .= "	tbb.type='button';\n";
		$script .= "	tbb.value=$('<div />').html('&#9665;').text();\n";
		$script .= "	tbb.objs=[obj,tb,tbb];\n";
		$script .= "	tbb.onclick=function(){ Replace".$destination_id."(this.objs); }\n";
		$script .= "	obj.parentNode.insertBefore(tb,obj);\n";
		$script .= "	obj.parentNode.insertBefore(tbb,obj);\n";
		$script .= "	obj.parentNode.removeChild(obj);\n";
		$script .= "	Replace".$destination_id."(this.objs);\n";
		$script .= "}\n";
		$script .= "\n";
		$script .= "function Replace".$destination_id."(obj){\n";
		$script .= "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		$script .= "	obj[0].parentNode.removeChild(obj[1]);\n";
		$script .= "	obj[0].parentNode.removeChild(obj[2]);\n";
		$script .= "	document.getElementById('btn_select_to_input_".$destination_id."').style.display = 'inline';\n";
		if (!empty($on_change)) {
			$script .= "	".$on_change.";\n";
		}
		$script .= "}\n";
		$script .= "</script>\n";
		$script .= "\n";
		echo $script;
	}
	echo "	<select name='conference_center_greeting' id='conference_center_greeting' class='formfld' ".(permission_exists('recording_play') || permission_exists('recording_download') ? "onchange=\"recording_reset('greeting'); set_playable('greeting', this.value, this.options[this.selectedIndex].parentNode.getAttribute('data-type'));\"" : null).">\n";
	echo "		<option value=''></option>\n";
	$found = $playable_greeting = false;
	//recordings
		if (!empty($recordings) && is_array($recordings) && @sizeof($recordings) != 0) {
			echo "<optgroup label='".$text['label-recordings']."' data-type='recordings'>\n";
			foreach ($recordings as &$row) {
				$recording_name = $row["recording_name"];
				$recording_filename = $row["recording_filename"];
				$recording_path = $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'];
				if (!empty($conference_center_greeting) && $conference_center_greeting == $recording_path."/".$recording_filename) {
					$selected = "selected='selected'";
					$playable_greeting = '../recordings/recordings.php?action=download&type=rec&filename='.$recording_filename;
					$found = true;
				}
				else {
					unset($selected);
				}
				echo "	<option value='".escape($recording_path)."/".escape($recording_filename)."' ".($selected ?? '').">".escape($recording_name)."</option>\n";
			}
			echo "</optgroup>\n";
		}
	//phrases
		if (!empty($phrases) && is_array($phrases) && @sizeof($phrases) != 0) {
			echo "<optgroup label='".$text['label-phrases']."' data-type='phrases'>\n";
			foreach ($phrases as &$row) {
				if (!empty($conference_center_greeting) && $conference_center_greeting == "phrase:".$row["phrase_uuid"]) {
					$selected = "selected='selected'";
					$found = true;
				}
				else {
					unset($selected);
				}
				echo "	<option value='phrase:".escape($row["phrase_uuid"])."' ".($selected ?? '').">".escape($row["phrase_name"])."</option>\n";
			}
			echo "</optgroup>\n";
		}
	//sounds
		$file = new file;
		$sound_files = $file->sounds();
		if (!empty($sound_files) && is_array($sound_files) && @sizeof($sound_files) != 0) {
			echo "<optgroup label='".$text['label-sounds']."' data-type='sounds'>\n";
			foreach ($sound_files as $key => $value) {
				if (!empty($value)) {
					if (!empty($conference_center_greeting) && substr($conference_center_greeting, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
						$conference_center_greeting = substr($conference_center_greeting, 71);
					}
					if (!empty($conference_center_greeting) && $conference_center_greeting == $value) {
						$selected = "selected='selected'";
						$playable_greeting = '../switch/sounds.php?action=download&filename='.$value;
						$found = true;
					}
					else {
						unset($selected);
					}
					echo "	<option value='".escape($value)."' ".($selected ?? '').">".escape($value)."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
	//select
		if (if_group("superadmin") && !empty($conference_center_greeting) && !$found) {
			echo "<optgroup label='selected'>\n";
			if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$conference_center_greeting)) {
				echo "		<option value='".$_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".escape($conference_center_greeting)."' selected='selected'>".escape($conference_center_greeting)."</option>\n";
			}
			else {
				echo "		<option value='".escape($conference_center_greeting)."' selected='selected'>".escape($conference_center_greeting)."</option>\n";
			}
			echo "</optgroup>\n";
		}
		unset($selected);
	echo "	</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_".escape($destination_id)."' class='btn' name='' alt='back' onclick='changeToInput".escape($destination_id)."(document.getElementById(\"".escape($destination_id)."\")); this.style.visibility=\"hidden\";' value='&#9665;'>";
		unset($destination_id);
	}
	if ((permission_exists('recording_play') || permission_exists('recording_download')) && !empty($playable_greeting)) {
		switch (pathinfo($playable_greeting, PATHINFO_EXTENSION)) {
			case 'wav' : $audio_file_type = 'audio/wav'; break;
			case 'mp3' : $audio_file_type = 'audio/mpeg'; break;
			case 'ogg' : $audio_file_type = 'audio/ogg'; break;
		}
	}
	echo "<audio id='recording_audio_greeting' style='display: none;' preload='none' ontimeupdate=\"update_progress('greeting')\" onended=\"recording_reset('greeting');\" src='".($playable_greeting ?? '')."' type='".($audio_file_type ?? '')."'></audio>";
	echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_greeting','style'=>'display: '.(!empty($audio_file_type) ? 'inline' : 'none'),'onclick'=>"recording_play('greeting')"]);
	unset($playable_greeting, $audio_file_type);
	echo "	<br />\n";
	echo "	".$text['description-conference_center_greeting']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_pin_length']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='conference_center_pin_length' maxlength='255' value='".escape($conference_center_pin_length)."'>\n";
	echo "<br />\n";
	echo $text['description-conference_center_pin_length']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='conference_center_enabled' name='conference_center_enabled' value='true' ".($conference_center_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='conference_center_enabled' name='conference_center_enabled'>\n";
		echo "		<option value='true' ".($conference_center_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($conference_center_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-conference_center_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-conference_center_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_description' maxlength='255' value=\"".escape($conference_center_description)."\">\n";
	echo "<br />\n";
	//echo $text['description-conference_center_description']."\n";
	echo "</td>\n";

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
		echo "<input type='hidden' name='conference_center_uuid' value='".escape($conference_center_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
