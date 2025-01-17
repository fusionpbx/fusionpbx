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
require_once "resources/check_auth.php";

//check permissions
if (permission_exists('phrase_add') || permission_exists('phrase_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

function build_data_array_from_post(settings $settings) {
	global $domain_uuid, $drop_rows;
	$phrase_uuid = $_POST['phrase_uuid'];
	$array = [];
	$drop_rows = [];
	$drop_row_count = 0;

	//load sound files from the switch so we can validate selections
	$sound_files = 	(new file)->sounds();

	//update the phrase information
	$array['phrases'][0]['domain_uuid'] = $domain_uuid;
	$array['phrases'][0]['phrase_uuid'] = $phrase_uuid;
	$array['phrases'][0]['phrase_name'] = $_POST['phrase_name'];
	$array['phrases'][0]['phrase_language'] = $_POST['phrase_language'];
	$array['phrases'][0]['phrase_enabled'] = $_POST['phrase_enabled'];
	$array['phrases'][0]['phrase_description'] = $_POST['phrase_description'];

	//recording_files are:
	//  'recording_uuid' => 'recording.wav'
	//       OR
	//  'recording_uuid' => '${lua streamfile.lua ' . base64_data .'}'
	$recording_files = phrases::get_all_domain_recordings($settings);

	//
	// Create two arrays - one for rows to delete and one for new/updated rows
	//
	for ($i = 0; $i < count($_POST['phrase_detail_function']); $i++) {
		//check for function to perform
		$phrase_detail_function = $_POST['phrase_detail_function'][$i];
		$phrase_detail_data = null;
		$recording_uuid_or_file = '';
		$phrase_detail_uuid = '';
		//check for the empty rows to delete -- 0,false,null is valid
		if (strlen($_POST['phrase_detail_data'][$i]) === 0
				&& !empty($_POST['phrase_detail_uuid'][$i])
				&& empty($_POST['slider'][$i])
				&& empty($_POST['phrase_detail_text'][$i])) {
			$drop_rows['phrase_details'][$drop_row_count++]['phrase_detail_uuid'] = $_POST['phrase_detail_uuid'][$i];
			continue;
		}
		switch ($phrase_detail_function) {
			case 'play-file':
				//only save rows with data
				if (!empty($_POST['phrase_detail_data'][$i])) {
					$recording_uuid_or_file = $_POST['phrase_detail_data'][$i];
					//check for valid recordings and files
					if (is_uuid($recording_uuid_or_file)) {
						//recording UUID
						$phrase_detail_data = $recording_files[$recording_uuid_or_file];
					} else {
						//not a recording so must be valid path inside the switch recording files
						if (in_array($recording_uuid_or_file, $sound_files)) {
							//valid switch audio file
							$phrase_detail_data = $recording_uuid_or_file;
						} else {
							//ignore an invalid audio file
							continue(2);
						}
					}
					//build data array
					if ($_POST['phrase_detail_function'][$i] == 'execute' && substr($_POST['phrase_detail_data'][$i], 0,5) != "sleep" && !permission_exists("phrase_execute")) {
						header("Location: phrase_edit.php?id=".$phrase_uuid);
						exit;
					}
				}
				break;
			case 'pause':
				//check for value
				$phrase_detail_data = $_POST['slider'][$i];
				break;
			case 'execute':
				//check for the empty rows to delete
				if (empty($_POST['phrase_detail_text'][$i]) && !empty($_POST['phrase_detail_uuid'][$i])) {
					$drop_rows['phrase_details'][$drop_row_count++]['phrase_detail_uuid'] = $_POST['phrase_detail_uuid'][$i];
					continue(2);
				}
				$phrase_detail_data = $_POST['phrase_detail_text'][$i];
				break;
		}

		$_POST['phrase_detail_tag'] = 'action'; // default, for now
		$_POST['phrase_detail_group'] = "0"; // one group, for now

		if ($phrase_detail_data !== null) {
			if (!empty($_POST['phrase_detail_uuid'][$i])) {
				//update existing records in the database
				$phrase_detail_uuid = $_POST['phrase_detail_uuid'][$i];
			} else {
				//new record
				$phrase_detail_uuid = uuid();
			}
			$array['phrase_details'][$i]['phrase_detail_uuid'] = $phrase_detail_uuid;
			$array['phrase_details'][$i]['phrase_uuid'] = $phrase_uuid;
			$array['phrase_details'][$i]['domain_uuid'] = $domain_uuid;
			$array['phrase_details'][$i]['phrase_detail_order'] = $i;
			$array['phrase_details'][$i]['phrase_detail_tag'] = $_POST['phrase_detail_tag'];
			$array['phrase_details'][$i]['phrase_detail_pattern'] = $_POST['phrase_detail_pattern'] ?? null;
			$array['phrase_details'][$i]['phrase_detail_function'] = $phrase_detail_function;
			$array['phrase_details'][$i]['phrase_detail_data'] = $phrase_detail_data; //path and filename of recording
			$array['phrase_details'][$i]['phrase_detail_method'] = $_POST['phrase_detail_method'] ?? null;
			$array['phrase_details'][$i]['phrase_detail_type'] = $_POST['phrase_detail_type'] ?? null;
			$array['phrase_details'][$i]['phrase_detail_group'] = $_POST['phrase_detail_group'];
		}
	}
	return $array;
}

//set default domain
if (empty($domain_uuid)) {
	$domain_uuid = $_SESSION['domain_uuid'] ?? '';
}

//set default user
if (empty($user_uuid)) {
	$user_uuid = $_SESSION['user_uuid'] ?? '';
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//ensure we have a database object
$database = database::new();

//ensure we have a settings object
$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);

//add the defaults
$phrase_name = '';
$phrase_language = '';
$phrase_description = '';

//set the action as an add or an update
if (!empty($_REQUEST["id"])) {
	$action = "update";
	$phrase_uuid = $_REQUEST["id"];
}
else {
	$action = "add";
}

//get the form value and set to php variables
if (count($_POST) > 0) {

	//process the http post data by submitted action
		if (!empty($_POST['action']) && is_uuid($_POST['phrase_uuid'])) {
			$array[0]['checked'] = 'true';
			$array[0]['uuid'] = $_POST['phrase_uuid'];

			switch ($_POST['action']) {
				case 'delete':
					if (permission_exists('phrase_delete')) {
						$obj = new phrases;
						$obj->delete($array);
					}
					break;
			}

			header('Location: phrases.php');
			exit;
		}

	if (permission_exists('phrase_domain')) {
		$domain_uuid = $_POST["domain_uuid"];
	}
	$phrase_name = $_POST["phrase_name"];
	$phrase_language = $_POST["phrase_language"];
	$phrase_enabled = $_POST["phrase_enabled"] ?? 'false';
	$phrase_description = $_POST["phrase_description"];
	$phrase_details_delete = $_POST["phrase_details_delete"] ?? '';

	//clean the name
	$phrase_name = str_replace(" ", "_", $phrase_name);
	$phrase_name = str_replace("'", "", $phrase_name);
}

//process the changes from the http post
	if (count($_POST) > 0 && empty($_POST["persistformvar"])) {

		//get the uuid
		if ($action == "update") {
			$phrase_uuid = $_POST["phrase_uuid"];
		}

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: phrases.php');
			exit;
		}

		//check for all required data
		$msg = '';
		if (empty($phrase_name)) { $msg .= $text['message-required']." ".$text['label-name']."<br>\n"; }
		if (empty($phrase_language)) { $msg .= $text['message-required']." ".$text['label-language']."<br>\n"; }
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

		//add the phrase
		if (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true") {
			$message = '';
			switch ($action) {
				case 'add':
					//redirect when they don't have permission to add a phrase
					if (!permission_exists('phrase_add')) {
						header('Location: phrases.php');
						exit();
					}
					//set user feedback message to add
					$message = $text['message-add'];
					$phrase_uuid = uuid();
					//do not break
				case 'update':
					//redirect when not adding and don't have permission to edit a phrase
					if (empty($message)) {
						if (!permission_exists('phrase_edit')) {
							header('Location: phrases.php');
							exit();
						}
						//set user feedback message to update
						$message = $text['message-update'];
					}
					if (!empty($_POST['phrase_detail_function'])) {
						$array = build_data_array_from_post($settings);
					}
					//execute update/insert
					$p = permissions::new();
					$p->add('phrase_detail_add', 'temp');
					$p->add('phrase_detail_edit', 'temp');
					$p->add('phrase_detail_delete', 'temp');
					$database->app_name = 'phrases';
					$database->app_uuid = '5c6f597c-9b78-11e4-89d3-123b93f75cba';
					if (count($array) > 0) {
						$database->save($array);
						unset($array);
					}
					if (count($drop_rows) > 0) {
						$database->delete($drop_rows);
						unset($drop_rows);
					}
					$p->delete('phrase_detail_add', 'temp');
					//clear the cache
					$cache = new cache;
					$cache->delete("languages:".$phrase_language.".".$phrase_uuid);

					//clear the destinations session array
					if (isset($_SESSION['destinations']['array'])) {
						unset($_SESSION['destinations']['array']);
					}

					//send a redirect
					message::add($message);
					header("Location: phrase_edit.php?id=".$phrase_uuid);
					exit;
			}
		}
	}

//pre-populate the form
	if (count($_GET)>0 && empty($_POST["persistformvar"])) {
		$phrase_uuid = $_GET["id"];
		$sql = "select * from v_phrases ";
		$sql .= "where ( ";
		$sql .= " domain_uuid = :domain_uuid or ";
		$sql .= " domain_uuid is null ";
		$sql .= ") ";
		$sql .= "and phrase_uuid = :phrase_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['phrase_uuid'] = $phrase_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$phrase_name = $row["phrase_name"];
			$phrase_language = $row["phrase_language"];
			$phrase_enabled = $row["phrase_enabled"];
			$phrase_description = $row["phrase_description"];
		}
		unset($sql, $parameters, $row);
	}

//set the defaults
	if (empty($phrase_enabled)) { $phrase_enabled = 'true'; }

//get the phrase details
	if (!empty($phrase_uuid)) {
		$sql = "select * from v_phrase_details ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and phrase_uuid = :phrase_uuid ";
		$sql .= "order by phrase_detail_order asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['phrase_uuid'] = $phrase_uuid;
		$phrase_details = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get the recording names from the database.
	$sql = "select recording_uuid, recording_name, recording_filename, domain_uuid from v_recordings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "order by recording_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$recordings = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the switch sound files
	$file = new file;
	$sound_files = $file->sounds();

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	if ($action == 'add') { $document['title'] = $text['title-add_phrase']; }
	if ($action == 'update') { $document['title'] = $text['title-edit_phrase']; }
	require_once "resources/header.php";

//javascript constants for use in the selection option group
	echo "<script>\n";
	echo "window.permission_execute = " . (permission_exists('phrase_execute') ? 'true' : 'false') . ";\n";
	echo "window.phrase_label_sounds = '" . ($text['label-sounds'] ?? 'Sounds') . "';\n";
	echo "window.phrase_label_recordings = '" . ($text['label-recordings'] ?? 'Recordings') . "';\n";
	//only include permissive actions
	$phrase_commands = [];
	if (permission_exists('phrase_play')) {
		$phrase_commands[] = $text['label-play'] ?? 'Play';
		$phrase_commands[] = $text['label-pause'] ?? 'Pause';
	}

	if (permission_exists('phrase_execute')) {
		$phrase_commands[] = $text['label-execute'] ?? 'Execute';
	}
	echo "window.phrase_commands = " . json_encode($phrase_commands, true) . ";\n";

	//existing details
	if (!empty($phrase_details)) {
		//update the array to include the recording name for display in select box
		foreach ($phrase_details as &$row) {
			$row['display_name'] = '';
			$file = basename($row['phrase_detail_data']);
			//get the display_name from recording name based on the file matched
			foreach ($recordings as $key => $recordings_row) {
				//match on filename first and then domain_uuid
				if ($recordings_row['recording_filename'] === $file && $recordings_row['domain_uuid'] === $row['domain_uuid']) {
					$row['display_name'] = $recordings[$key]['recording_name'];
					break;
				}
			}
			//check if display_name was not found in the recording names
			if (strlen($row['display_name']) === 0) {
				//try finding display_name in the switch sound files
				if (!empty($sound_files)) {
					//use optimized php function with strict comparison
					$i = array_search($row['phrase_detail_data'], $sound_files, true);
					//if found in the switch sound files
					if ($i !== false) {
						//set the display_name to the switch sound file name
						$row['display_name'] = $sound_files[$i];
					}
				}
			}
		}
		//send the phrase details to the browser as a global scope json array object
		//echo "window.phrase_details = " . json_encode($phrase_details, true) . ";\n";
	} else {
		//send an empty array to the browser as a global scope json array object
		//echo "window.phrase_details = [];\n";
	}

	//recording files
	if ($recordings !== false) {
		//send recordings to the browser as a global scope json array object
		echo "window.phrase_recordings = " . json_encode($recordings, true) . ";\n";
	} else {
		//send an empty array
		echo "window.phrase_recordings = [];\n";
	}

	if (!empty($sound_files)) {
		//send sounds to the browser as a global scope json array object
		echo "window.phrase_sounds = " . json_encode($sound_files, true) . ";\n";
	}
	echo "</script>\n";

//javascript to control action form input using drag and drop
	echo "<script src='resources/javascript/phrase_edit.js'></script>\n";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "add") {
		echo "<b>".$text['title-add_phrase']."</b>";
	}
	if ($action == "update") {
		echo "<b>".$text['title-edit_phrase']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'phrases.php']);
	if ($action == "update" && permission_exists('phrase_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','onclick'=>'submit_phrase()','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == "update" && permission_exists('phrase_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<div class='card'>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phrase_name' maxlength='255' value=\"".escape($phrase_name)."\">\n";
	echo "	<br />\n";
	echo "	".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-language']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phrase_language' maxlength='255' value=\"".escape($phrase_language)."\">\n";
	echo "	<br />\n";
	echo "	".$text['description-language']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	//structure row
	echo "<tr>";
	echo "<td class='vncell' valign='top'>".$text['label-structure']."</td>";
	echo "<td class='vtable' align='left'>";
	//style for dragging rows
	echo "  <link rel=stylesheet href='resources/styles/phrase_edit.css' />";
	//structure table
	echo "	<table border='0' cellpadding='0' cellspacing='0' id='phrases_table'>\n";
	//headings
	echo "		<thead>\n";
	echo "		<tr>\n";
	echo "			<td class='vtable'><strong>" . ($text['label-order'] ?? 'Order') . "</strong></td>\n";
	echo "			<td class='vtable'><strong>".$text['label-action']."</strong></td>\n";
	echo "			<td class='vtable'><strong>".($text['label-recording'] ?? 'Recording')."</strong></td>\n";
	echo "		</tr>\n";
	echo "		</thead>\n";
	//draggable rows are initially empty
	echo "<tbody id='structure'>\n";
	echo "</tbody>";
	//show loading
	echo "<tbody id='loading'><tr><td>&nbsp;</td><td><center>Loading...</center></td><td>&nbsp;</td></tr></tbody>\n";
	//cloning row and buttons created outside of 'structure' table body
	echo "<tbody>";
	echo "<tr id='empty_row' style='display: none;'>\n";
	echo "	<td style='border-bottom: none;' nowrap='nowrap'><center><span class='fa-solid fa-arrows-up-down'></span></center></td>";
	echo "	<td class='vtable' style='border-bottom: none;' align='left' nowrap='nowrap'>\n";
	echo "		<select class='formfld' name='phrase_detail_function_empty' id='phrase_detail_function_empty' tag=''>\n";
	echo "			<option value='play-file'>".$text['label-play']."</option>\n";
	echo "			<option value='pause'>".$text['label-pause']."</option>\n";
	if (permission_exists('phrase_execute')) {
		echo "			<option value='execute'>".$text['label-execute']."</option>\n";
	}
	echo "		</select>\n";
	echo "	</td>\n";
	echo "	<td class='vtable' style='border-bottom: none;' align='left' nowrap='nowrap'>\n";
	echo "		<select  class='formfld' id='phrase_detail_data_empty' name='phrase_detail_data_empty' style='width: 300px; min-width: 300px; max-width: 300px;' tag=''></select>";
//	if (permission_exists('phrase_execute')) {
//		echo "	<input id='phrase_detail_data_switch_empty' type='button' class='btn' style='margin-left: 4px; display: none;' value='&#9665;' onclick=\"action_to_select(); load_action_options(document.getElementById('phrase_detail_function_empty').selectedIndex);\">\n";
//	}
	echo "    <input type=hidden name='empty_uuid' value=''>";
	echo "    <input class='formfld' type=text name='empty_phrase_detail_text' value='' style='width: 300px; min-width: 300px; max-width: 300px; display: none'>";
	echo "    <span style='white-space: nowrap; display: flex; align-items: center; gap: 10px;'>";
	echo "      <input class='form-control-range' type=range name='range' minrange='1' style='width: 250px; min-width: 250px; max-width: 250px; display: none'>";
	echo "	    <input type='text' class='formfld' name='sleep' style='width: 40px; min-width: 40px; max-width: 40px; display: none'>";
	echo "    </span>";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>";
	echo "<td>&nbsp;</td>";
	echo "<td class='vtable' style='align=center;' colspan='2'><center>";
	echo button::create(['type'=>'button','icon'=>$_SESSION['theme']['button_icon_add'], 'label' => $text['label-add'], 'onclick' => 'add_row()']);
	echo button::create(['type'=>'button','icon'=>'fa-solid fa-minus', 'label' => $text['label-delete'], 'onclick' => 'remove_row()']);
	echo "</center></td>";
	echo "<td>&nbsp;</td>";
	echo "</tr>";
	echo "</tbody>\n";
	echo "</table>\n";

	echo "	".$text['description-structure']."\n";
	echo "	<br />\n";
	echo "</td>";
	echo "</tr>";

	if (permission_exists('phrase_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable'>\n";
		echo "	<select name='domain_uuid' class='formfld'>\n";
		if (empty($domain_uuid)) {
			echo "		<option value='' selected='selected'>".$text['label-global']."</option>\n";
		}
		else {
			echo "		<option value=''>".$text['label-global']."</option>\n";
		}
		foreach ($_SESSION['domains'] as $row) {
			if ($row['domain_uuid'] == $domain_uuid) {
				echo "		<option value='".escape($row['domain_uuid'])."' selected='selected'>".escape($row['domain_name'])."</option>\n";
			}
			else {
				echo "		<option value='".escape($row['domain_uuid'])."'>".escape($row['domain_name'])."</option>\n";
			}
		}
		echo "	</select>\n";
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
		echo "		<input type='checkbox' id='phrase_enabled' name='phrase_enabled' value='true' ".($phrase_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='phrase_enabled' name='phrase_enabled'>\n";
		echo "		<option value='true' ".($phrase_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($phrase_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "	<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='phrase_description' maxlength='255' value=\"".escape($phrase_description)."\">\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>\n";
	echo "<br><br>";

	if ($action == "update") {
		echo "	<input type='hidden' name='phrase_uuid' value='".escape($phrase_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";
