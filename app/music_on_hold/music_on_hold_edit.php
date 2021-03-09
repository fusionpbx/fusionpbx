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
	Portions created by the Initial Developer are Copyright (C) 2016-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('music_on_hold_add') || permission_exists('music_on_hold_edit')) {
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
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$music_on_hold_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		if (permission_exists('music_on_hold_domain')) {
			$domain_uuid = $_POST["domain_uuid"];
		}
		$music_on_hold_name = $_POST["music_on_hold_name"];
		$music_on_hold_path = $_POST["music_on_hold_path"];
		$music_on_hold_rate = $_POST["music_on_hold_rate"];
		$music_on_hold_shuffle = $_POST["music_on_hold_shuffle"];
		$music_on_hold_channels = $_POST["music_on_hold_channels"];
		$music_on_hold_interval = $_POST["music_on_hold_interval"];
		$music_on_hold_timer_name = $_POST["music_on_hold_timer_name"];
		$music_on_hold_chime_list = $_POST["music_on_hold_chime_list"];
		$music_on_hold_chime_freq = $_POST["music_on_hold_chime_freq"];
		$music_on_hold_chime_max = $_POST["music_on_hold_chime_max"];
	}

//add or update the data
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid
			if ($action == "update") {
				$music_on_hold_uuid = $_POST["music_on_hold_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: music_on_hold.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($music_on_hold_name) == 0) { $msg .= $text['message-required']." ".$text['label-name']."<br>\n"; }
			if (strlen($music_on_hold_path) == 0) { $msg .= $text['message-required']." ".$text['label-path']."<br>\n"; }
			//if (strlen($music_on_hold_rate) == 0) { $msg .= $text['message-required']." ".$text['label-rate']."<br>\n"; }
			if (strlen($music_on_hold_shuffle) == 0) { $msg .= $text['message-required']." ".$text['label-shuffle']."<br>\n"; }
			if (strlen($music_on_hold_channels) == 0) { $msg .= $text['message-required']." ".$text['label-channels']."<br>\n"; }
			//if (strlen($music_on_hold_interval) == 0) { $msg .= $text['message-required']." ".$text['label-interval']."<br>\n"; }
			//if (strlen($music_on_hold_timer_name) == 0) { $msg .= $text['message-required']." ".$text['label-timer_name']."<br>\n"; }
			//if (strlen($music_on_hold_chime_list) == 0) { $msg .= $text['message-required']." ".$text['label-chime_list']."<br>\n"; }
			//if (strlen($music_on_hold_chime_freq) == 0) { $msg .= $text['message-required']." ".$text['label-chime_freq']."<br>\n"; }
			//if (strlen($music_on_hold_chime_max) == 0) { $msg .= $text['message-required']." ".$text['label-chime_max']."<br>\n"; }
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

		//add or update the database
			if ($_POST["persistformvar"] != "true") {
				if ($action == "add" && permission_exists('music_on_hold_add')) {
					//begin insert array
						$array['music_on_hold'][0]['music_on_hold_uuid'] = uuid();
					//set message
						message::add($text['message-add']);
				}

				if ($action == "update" && permission_exists('music_on_hold_edit')) {
					//begin update array
						$array['music_on_hold'][0]['music_on_hold_uuid'] = $music_on_hold_uuid;

					//set message
						message::add($text['message-update']);
				}

				if (is_array($array) && @sizeof($array) != 0) {

					//add common array elements
						if (permission_exists('music_on_hold_domain')) {
							$array['music_on_hold'][0]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
						}
						else {
							$array['music_on_hold'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
						}
						$array['music_on_hold'][0]['music_on_hold_name'] = $music_on_hold_name;
						$array['music_on_hold'][0]['music_on_hold_path'] = $music_on_hold_path;
						$array['music_on_hold'][0]['music_on_hold_rate'] = strlen($music_on_hold_rate) != 0 ? $music_on_hold_rate : null;
						$array['music_on_hold'][0]['music_on_hold_shuffle'] = $music_on_hold_shuffle;
						$array['music_on_hold'][0]['music_on_hold_channels'] = strlen($music_on_hold_channels) != 0 ? $music_on_hold_channels : null;
						$array['music_on_hold'][0]['music_on_hold_interval'] = strlen($music_on_hold_interval) != 0 ? $music_on_hold_interval : null;
						$array['music_on_hold'][0]['music_on_hold_timer_name'] = $music_on_hold_timer_name;
						$array['music_on_hold'][0]['music_on_hold_chime_list'] = $music_on_hold_chime_list;
						$array['music_on_hold'][0]['music_on_hold_chime_freq'] = strlen($music_on_hold_chime_freq) != 0 ? $music_on_hold_chime_freq : null;
						$array['music_on_hold'][0]['music_on_hold_chime_max'] = strlen($music_on_hold_chime_max) != 0 ? $music_on_hold_chime_max : null;

					//execute
						$database = new database;
						$database->app_name = 'music_on_hold';
						$database->app_uuid = '1dafe0f8-c08a-289b-0312-15baf4f20f81';
						$database->save($array);
						unset($array);

					//clear the cache
						$cache = new cache;
						$cache->delete("configuration:local_stream.conf");

					//reload mod local stream
						$music = new switch_music_on_hold;
						$music->reload();

					//redirect the user
						header("Location: music_on_hold.php");
						exit;

				}
			}

	}

//pre-populate the form
	if (count($_GET) > 0 && is_uuid($_GET["id"]) && $_POST["persistformvar"] != "true") {
		$music_on_hold_uuid = $_GET["id"];
		$sql = "select * from v_music_on_hold ";
		$sql .= "where ( ";
		$sql .= "	domain_uuid = :domain_uuid ";
		$sql .= "	or domain_uuid is null ";
		$sql .= ") ";
		$sql .= "and music_on_hold_uuid = :music_on_hold_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['music_on_hold_uuid'] = $music_on_hold_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$domain_uuid = $row["domain_uuid"];
			$music_on_hold_name = $row["music_on_hold_name"];
			$music_on_hold_path = $row["music_on_hold_path"];
			$music_on_hold_rate = $row["music_on_hold_rate"];
			$music_on_hold_shuffle = $row["music_on_hold_shuffle"];
			$music_on_hold_channels = $row["music_on_hold_channels"];
			$music_on_hold_interval = $row["music_on_hold_interval"];
			$music_on_hold_timer_name = $row["music_on_hold_timer_name"];
			$music_on_hold_chime_list = $row["music_on_hold_chime_list"];
			$music_on_hold_chime_freq = $row["music_on_hold_chime_freq"];
			$music_on_hold_chime_max = $row["music_on_hold_chime_max"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-music_on_hold'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-music_on_hold']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'music_on_hold.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','name'=>'action','value'=>'save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='music_on_hold_name' maxlength='255' value=\"".escape($music_on_hold_name)."\">\n";
	echo "<br />\n";
	echo $text['description-music_on_hold_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-path']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='music_on_hold_path' maxlength='255' value=\"".escape($music_on_hold_path)."\">\n";
	echo "<br />\n";
	echo $text['description-music_on_hold_path']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-rate']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='music_on_hold_rate'>\n";
	if ($music_on_hold_rate == "") {
		echo "	<option value='' selected='selected'>".$text['option-default']."</option>\n";
	}
	else {
		echo "	<option value=''>".$text['option-default']."</option>\n";
	}
	if ($music_on_hold_rate == "8000") {
		echo "	<option value='8000' selected='selected'>8000</option>\n";
	}
	else {
		echo "	<option value='8000'>8000</option>\n";
	}
	if ($music_on_hold_rate == "16000") {
		echo "	<option value='16000' selected='selected'>16000</option>\n";
	}
	else {
		echo "	<option value='16000'>16000</option>\n";
	}
	if ($music_on_hold_rate == "32000") {
		echo "	<option value='32000' selected='selected'>32000</option>\n";
	}
	else {
		echo "	<option value='32000'>32000</option>\n";
	}
	if ($music_on_hold_rate == "48000") {
		echo "	<option value='48000' selected='selected'>48000</option>\n";
	}
	else {
		echo "	<option value='48000'>48000</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-music_on_hold_rate']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-shuffle']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='music_on_hold_shuffle'>\n";
	echo "	<option value=''></option>\n";
	if ($music_on_hold_shuffle == "true") {
		echo "	<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "	<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($music_on_hold_shuffle == "false") {
		echo "	<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "	<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-music_on_hold_shuffle']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-channels']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='music_on_hold_channels' class='formfld'>\n";
	echo "		<option value='1' ".(($music_on_hold_channels == '2') ? 'selected' : null).">".$text['label-mono']."</option>\n";
	echo "		<option value='2' ".(($music_on_hold_channels == '2') ? 'selected' : null).">".$text['label-stereo']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-music_on_hold_channels']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-interval']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='music_on_hold_interval' maxlength='255' value='".escape($music_on_hold_interval)."'>\n";
	echo "<br />\n";
	echo $text['description-music_on_hold_interval']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-timer_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='music_on_hold_timer_name' maxlength='255' value=\"".escape($music_on_hold_timer_name)."\">\n";
	echo "<br />\n";
	echo $text['description-music_on_hold_timer_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell'>\n";
	echo "	".$text['label-chime_list']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='music_on_hold_chime_list' class='formfld' style='width: 350px;' ".((permission_exists('music_on_hold_path')) ? "onchange='changeToInput(this);'" : null).">\n";
	echo "		<option value=''></option>\n";
	//misc optgroup
		/*
		if (if_group("superadmin")) {
			echo "<optgroup label='Misc'>\n";
			echo "	<option value='phrase:'>phrase:</option>\n";
			echo "	<option value='say:'>say:</option>\n";
			echo "	<option value='tone_stream:'>tone_stream:</option>\n";
			echo "</optgroup>\n";
		}
		*/
	//recordings
		$tmp_selected = false;
		$sql = "select recording_name, recording_filename from v_recordings where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$recordings = $database->select($sql, $parameters, 'all');
		if (is_array($recordings) && @sizeof($recordings) != 0) {
			echo "<optgroup label='Recordings'>\n";
			foreach ($recordings as &$row) {
				$recording_name = $row["recording_name"];
				$recording_filename = $row["recording_filename"];
				if ($music_on_hold_chime_list == $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename && strlen($music_on_hold_chime_list) > 0) {
					$tmp_selected = true;
					echo "	<option value='".escape($_SESSION['switch']['recordings']['dir'])."/".escape($_SESSION['domain_name'])."/".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
				}
				else if ($music_on_hold_chime_list == $recording_filename && strlen($music_on_hold_chime_list) > 0) {
					$tmp_selected = true;
					echo "	<option value='".escape($recording_filename)."' selected='selected'>".escape($recording_name)."</option>\n";
				}
				else {
					echo "	<option value='".escape($recording_filename)."'>".escape($recording_name)."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
		unset($sql, $parameters, $recordings, $row);

	//phrases
		$sql = "select * from v_phrases where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			echo "<optgroup label='Phrases'>\n";
			foreach ($result as &$row) {
				if ($music_on_hold_chime_list == "phrase:".$row["phrase_uuid"]) {
					$tmp_selected = true;
					echo "	<option value='phrase:".escape($row["phrase_uuid"])."' selected='selected'>".escape($row["phrase_name"])."</option>\n";
				}
				else {
					echo "	<option value='phrase:".escape($row["phrase_uuid"])."'>".escape($row["phrase_name"])."</option>\n";
				}
			}
			echo "</optgroup>\n";
		}
		unset($sql, $parameters, $result, $row);
	//sounds
		$file = new file;
		$sound_files = $file->sounds();
		if (is_array($sound_files) && @sizeof($sound_files) != 0) {
			echo "<optgroup label='Sounds'>\n";
			foreach ($sound_files as $value) {
				if (strlen($value) > 0) {
					if (substr($music_on_hold_chime_list, 0, 71) == "\$\${sounds_dir}/\${default_language}/\${default_dialect}/\${default_voice}/") {
						$music_on_hold_chime_list = substr($music_on_hold_chime_list, 71);
					}
					if ($music_on_hold_chime_list == $value) {
						$tmp_selected = true;
						echo "	<option value='".escape($value)."' selected='selected'>".escape($value)."</option>\n";
					}
					else {
						echo "	<option value='".escape($value)."'>".escape($value)."</option>\n";
					}
				}
			}
			echo "</optgroup>\n";
		}
		unset($sound_files, $value);
	//select
		if (if_group("superadmin")) {
			if (!$tmp_selected && strlen($music_on_hold_chime_list) > 0) {
				echo "<optgroup label='Selected'>\n";
				if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$music_on_hold_chime_list)) {
					echo "	<option value='".escape($_SESSION['switch']['recordings']['dir'])."/".escape($_SESSION['domain_name'])."/".escape($music_on_hold_chime_list)."' selected='selected'>".escape($music_on_hold_chime_list)."</option>\n";
				}
				else if (substr($music_on_hold_chime_list, -3) == "wav" || substr($music_on_hold_chime_list, -3) == "mp3") {
					echo "	<option value='".escape($music_on_hold_chime_list)."' selected='selected'>".escape($music_on_hold_chime_list)."</option>\n";
				}
				echo "</optgroup>\n";
			}
			unset($tmp_selected);
		}
	echo "	</select>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-chime_frequency']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='music_on_hold_chime_freq' maxlength='255' value=\"".escape($music_on_hold_chime_freq)."\">\n";
	echo "<br />\n";
	echo $text['description-music_on_hold_chime_freq']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-chime_maximum']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='music_on_hold_chime_max' maxlength='255' value=\"".escape($music_on_hold_chime_max)."\">\n";
	echo "<br />\n";
	echo $text['description-music_on_hold_chime_max']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('music_on_hold_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable'>\n";
		echo "	<select name='domain_uuid' class='formfld'>\n";
		if (strlen($domain_uuid) == 0) {
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

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='music_on_hold_uuid' value='".escape($music_on_hold_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
