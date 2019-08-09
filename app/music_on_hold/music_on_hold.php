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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('music_on_hold_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//increase the exucution time
	ini_set('max_execution_time', 7200);

//get the music_on_hold array
	$sql = "select * from v_music_on_hold ";
	$sql .= "where ( ";
	$sql .= "domain_uuid = :domain_uuid ";
	if (permission_exists('music_on_hold_domain')) {
		$sql .= "or domain_uuid is null ";
	}
	$sql .= ") ";
	$sql .= "order by domain_uuid desc, music_on_hold_name asc, music_on_hold_rate asc";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$streams = $database->select($sql, $parameters, 'all');

//download music on hold file
	if (
		$_GET['action'] == "download"
		&& is_uuid($_GET['id'])
		&& is_array($streams)
		&& @sizeof($streams) != 0
		) {
		//get the uuid
			$stream_uuid = $_GET['id'];

		//get the record
			foreach($streams as $row) {
				if ($stream_uuid == $row['music_on_hold_uuid']) {
					$stream_domain_uuid = $row['domain_uuid'];
					$stream_name = $row['music_on_hold_name'];
					$stream_path = $row['music_on_hold_path'];
					break;
				}
			}
		
		//replace the sounds_dir variable in the path
			$stream_path = str_replace('$${sounds_dir}', $_SESSION['switch']['sounds']['dir'], $stream_path);

		//get the file
			$stream_file = base64_decode($_GET['file']);
			$stream_full_path = path_join($stream_path, $stream_file);

		//dowload the file
			session_cache_limiter('public');
			if (file_exists($stream_full_path)) {
				$fd = fopen($stream_full_path, "rb");
				if ($_GET['t'] == "bin") {
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					header("Content-Description: File Transfer");
				}
				else {
					$stream_file_ext = pathinfo($stream_file, PATHINFO_EXTENSION);
					switch ($stream_file_ext) {
						case "wav" : header("Content-Type: audio/x-wav"); break;
						case "mp3" : header("Content-Type: audio/mpeg"); break;
						case "ogg" : header("Content-Type: audio/ogg"); break;
					}
				}
				header('Content-Disposition: attachment; filename="'.$stream_file.'"');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header("Content-Length: ".filesize($stream_full_path));
				fpassthru($fd);
			}
			exit;
	}

//upload music on hold file
	if (
		$_POST['action'] == 'upload'
		&& is_array($_FILES)
		&& is_uploaded_file($_FILES['file']['tmp_name'])
		&& is_array($streams)
		&& @sizeof($streams) != 0
		) {

		//determine name
			if ($_POST['name_new'] != '') {
				//set the action
					$action = 'add';
				//get the stream_name
					$stream_name = $_POST['name_new'];
				//get the rate
					$stream_rate = is_numeric($_POST['rate']) ? $_POST['rate'] : '';
			}
			else {
				//get the stream uuid
					$stream_uuid = $_POST['name'];
				//find the matching stream
					foreach ($streams as $row) {
						if ($stream_uuid == $row['music_on_hold_uuid']) {
							//set the action
								$action = 'update';
							//set the variables
								$stream_domain_uuid = $row['domain_uuid'];
								$stream_name = $row['music_on_hold_name'];
								$stream_path = $row['music_on_hold_path'];
								$stream_rate = $row['music_on_hold_rate'];
								$stream_shuffle = $row['music_on_hold_shuffle'];
								$stream_channels = $row['music_on_hold_channels'];
								$stream_internal = $row['music_on_hold_interval'];
								$stream_timer_name = $row['music_on_hold_timer_name'];
								$stream_chime_list = $row['music_on_hold_chime_list'];
								$stream_chime_freq = $row['music_on_hold_chime_freq'];
								$stream_chime_max = $row['music_on_hold_chime_max'];
								$stream_rate = $row['music_on_hold_rate'];
							//end the loop
								break;
						}
					}
			}

		//get remaining values
			$stream_file_name_temp = $_FILES['file']['tmp_name'];
			$stream_file_name = $_FILES['file']['name'];
			$stream_file_ext = strtolower(pathinfo($stream_file_name, PATHINFO_EXTENSION));

		//check file type
			$valid_file_type = ($stream_file_ext == 'wav' || $stream_file_ext == 'mp3' || $stream_file_ext == 'ogg') ? true : false;

		//process, if possible
			if (!$valid_file_type) {
				message::add($text['message-unsupported_file_type']);
			}
			else {

				//add the new stream
					if ($action == "add") {

						//strip slashes, replace spaces
							$slashes = array("/", "\\");
							$stream_name = str_replace($slashes, '', $stream_name);
							$stream_name = str_replace(' ', '_', $stream_name);
							$stream_file_name = str_replace($slashes, '', $stream_file_name);
							$stream_file_name = str_replace(' ', '-', $stream_file_name);

						//detect auto rate
							if ($stream_rate == '') {
								$path_rate = '48000';
								$stream_rate_auto = true;
							}
							else {
								$path_rate = $stream_rate;
								$stream_rate_auto = false;
							}

						//define default path
							$stream_path = path_join($_SESSION['switch']['sounds']['dir'], 'music', $_SESSION['domain_name'],$stream_name, $path_rate);

						//find whether the path already exists
							$stream_new_name = true;
							foreach ($streams as $row) {
								$alternate_path = str_replace('$${sounds_dir}', $_SESSION['switch']['sounds']['dir'], $row['music_on_hold_path']);
								if ($stream_path == $row['music_on_hold_path']
									|| $stream_path == $alternate_path) {
									$stream_new_name = false;
									break;
								}
							}

						//set the variables
							$stream_path = str_replace('$${sounds_dir}', $_SESSION['switch']['sounds']['dir'], $stream_path);

						//execute query
							if ($stream_new_name) {
								$stream_uuid = uuid();
								$array['music_on_hold'][0]['music_on_hold_uuid'] = $stream_uuid;
								$array['music_on_hold'][0]['domain_uuid'] = $domain_uuid;
								$array['music_on_hold'][0]['music_on_hold_name'] = $stream_name;
								$array['music_on_hold'][0]['music_on_hold_path'] = $stream_path;
								$array['music_on_hold'][0]['music_on_hold_rate'] = strlen($stream_rate) != 0 ? $stream_rate : null;
								$array['music_on_hold'][0]['music_on_hold_shuffle'] = 'false';
								$array['music_on_hold'][0]['music_on_hold_channels'] = 1;
								$array['music_on_hold'][0]['music_on_hold_interval'] = 20;
								$array['music_on_hold'][0]['music_on_hold_timer_name'] = 'soft';
								$array['music_on_hold'][0]['music_on_hold_chime_list'] = null;
								$array['music_on_hold'][0]['music_on_hold_chime_freq'] = null;
								$array['music_on_hold'][0]['music_on_hold_chime_max'] = null;

								$p = new permissions;
								$p->add('music_on_hold_add', 'temp');

								$database = new database;
								$database->app_name = 'music_on_hold';
								$database->app_uuid = '1dafe0f8-c08a-289b-0312-15baf4f20f81';
								$database->save($array);
								unset($array);

								$p->delete('music_on_hold_add', 'temp');
							}
					}

				//check target folder, move uploaded file
					if (!is_dir($stream_path)) {
						event_socket_mkdir($stream_path);
					}
					if (is_dir($stream_path)) {
						if (copy($stream_file_name_temp, $stream_path.'/'.$stream_file_name)) {
							@unlink($stream_file_name_temp);
						}
					}

				//set message
					message::add($text['message-upload_completed']);
			}

		//require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
			$music = new switch_music_on_hold;
			$music->reload();

		//redirect
			header("Location: music_on_hold.php");
			exit;
	}

//delete the music on hold file
	if (
		$_GET['action'] == "delete"
		&& is_uuid($_GET['id'])
		&& is_array($streams)
		&& @sizeof($streams) != 0
		) {

		//get submitted values
			$stream_uuid = $_GET['id'];
			$stream_file = base64_decode($_GET['file']);

		//get the record
			foreach($streams as $row) {
				if ($stream_uuid == $row['music_on_hold_uuid']) {
					$stream_domain_uuid = $row['domain_uuid'];
					$stream_name = $row['music_on_hold_name'];
					$stream_path = $row['music_on_hold_path'];
					$stream_rate = $row['music_on_hold_rate'];
					break;
				}
			}

		//check permissions
			if (($stream_domain_uuid == '' && permission_exists('music_on_hold_domain')) ||
				($stream_domain_uuid != '' && permission_exists('music_on_hold_delete'))) {

				//remove specified file
					if ($stream_file != '') {
						@unlink(path_join($stream_path, $stream_file));
					}
				//remove all audio files
					else {
						array_map('unlink', glob(path_join($stream_path, '*.wav')));
						array_map('unlink', glob(path_join($stream_path, '*.mp3')));
						array_map('unlink', glob(path_join($stream_path, '*.ogg')));
					}
				//reload moh
					$music = new switch_music_on_hold;
					$music->reload();
				//set message
					message::add($text['message-delete']);
			}

		//redirect
			header("Location: music_on_hold.php");
			exit;
	}

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-music_on_hold'];

	echo "<script language='JavaScript' type='text/javascript'>\n";

	echo "	function check_filetype(file_input) {\n";
	echo "		file_ext = file_input.value.substr((~-file_input.value.lastIndexOf('.') >>> 0) + 2);\n";
	echo "		if (file_ext != 'mp3' && file_ext != 'wav' && file_ext != 'ogg' && file_ext != '') {\n";
	echo "			display_message(\"".$text['message-unsupported_file_type']."\", 'negative', '2750');\n";
	echo "		}\n";
	echo "		var selected_file_path = file_input.value;\n";
	echo "		selected_file_path = selected_file_path.replace(\"C:\\\\fakepath\\\\\",'');\n";
	echo "		document.getElementById('file_label').innerHTML = selected_file_path;\n";
	echo "	}\n";

	echo "	function name_mode(mode) {\n";
	echo "		if (mode == 'new') {\n";
	echo "			document.getElementById('name_select').style.display='none';\n";
	echo "			document.getElementById('btn_new').style.display='none';\n";
	echo "			document.getElementById('name_new').style.display='';\n";
	echo "			document.getElementById('btn_select').style.display='';\n";
	echo "			document.getElementById('name_new').focus();\n";
	echo "		}\n";
	echo "		else if (mode == 'select') {\n";
	echo "			document.getElementById('name_new').style.display='none';\n";
	echo "			document.getElementById('name_new').value = '';\n";
	echo "			document.getElementById('btn_select').style.display='none';\n";
	echo "			document.getElementById('name_select').selectedIndex = 0;\n";
	echo "			document.getElementById('name_select').style.display='';\n";
	echo "			document.getElementById('btn_new').style.display='';\n";
	echo "		}\n";
	echo "	}\n";

	echo "</script>\n";
	echo "<script language='JavaScript' type='text/javascript' src='".PROJECT_PATH."/resources/javascript/reset_file_input.js'></script>\n";

	echo "<b>".$text['label-music_on_hold']."</b>";
	echo "<br /><br />\n";
	echo $text['desc-music_on_hold']."\n";
	echo "<br /><br />\n";

//show the upload form
	if (permission_exists('music_on_hold_add')) {
		echo "<b>".$text['label-upload-music_on_hold']."</b>\n";
		echo "<br><br>\n";

		echo "<form name='frm' id='frm' method='post' enctype='multipart/form-data'>\n";
		echo "<input name='action' type='hidden' value='upload'>\n";

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";

		echo "<td width='40%' style='vertical-align: top;'>\n";

		echo "	<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "		<tr>\n";
		echo "			<td class='vncell' width='30%' valign='top' nowrap='nowrap'>\n";
		echo "				".$text['label-category']."\n";
		echo "			</td>\n";
		echo "			<td class='vtable' width='70%' style='white-space: nowrap;'>\n";
		echo "				<select name='name' id='name_select' class='formfld' style='width: auto;'>\n";

		if (permission_exists('music_on_hold_domain')) {
			echo "					<optgroup label='".$text['option-global']."'>\n";
			foreach ($streams as $row) {
				if (strlen($row['domain_uuid']) == 0) {
					if (strlen($row['music_on_hold_rate']) == 0) { $option_name = $row['music_on_hold_name']; }
					if (strlen($row['music_on_hold_rate']) > 0) { $option_name = $row['music_on_hold_name'] .'/'.$row['music_on_hold_rate']; }
					echo "						<option value='".escape($row['music_on_hold_uuid'])."'>".escape($option_name)."</option>\n";
				}
			}
			echo "					</optgroup>\n";
		}
		if (permission_exists('music_on_hold_domain')) {
			echo "					<optgroup label='".$text['option-local']."'>\n";
		}
		foreach ($streams as $row) {
			if (strlen($row['domain_uuid']) > 0) {
			if (strlen($row['music_on_hold_rate']) == 0) { $option_name = $row['music_on_hold_name']; }
			if (strlen($row['music_on_hold_rate']) > 0) { $option_name = $row['music_on_hold_name'] .'/'.$row['music_on_hold_rate']; }
				echo "						<option value='".escape($row['music_on_hold_uuid'])."'>".escape($option_name)."</option>\n";
			}
		}
		if (permission_exists('music_on_hold_domain')) {
			echo "					</optgroup>\n";
		}

		echo "				</select>";

		echo "				<button type='button' id='btn_new' class='btn btn-default list_control_icon' style='margin-left: 3px;' onclick=\"name_mode('new');\"><span class='glyphicon glyphicon-plus'></span></button>";
		echo "				<input class='formfld' style='width: 100px; display: none;' type='text' name='name_new' id='name_new' maxlength='255' value=''>";
		echo "				<button type='button' id='btn_select' class='btn btn-default list_control_icon' style='display: none; margin-left: 3px;' onclick=\"name_mode('select');\"><span class='glyphicon glyphicon-list-alt'></span></button>";
		echo "			</td>\n";
		echo "		</tr>\n";
		echo "	</table>\n";

		echo "</td>\n";
		echo "<td width='30%' style='vertical-align: top;'>\n";

		echo "	<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "		<tr>\n";
		echo "			<td class='vncell' width='30%' valign='top' nowrap='nowrap'>\n";
		echo "				".$text['label-rate']."\n";
		echo "			</td>\n";
		echo "			<td class='vtable' width='70%'>\n";
		echo "				<select id='rate' name='rate' class='formfld' style='width: auto;'>\n";
		echo "					<option value=''>".$text['option-default']."</option>\n";
		echo "					<option value='8000'>8 kHz</option>\n";
		echo "					<option value='16000'>16 kHz</option>\n";
		echo "					<option value='32000'>32 kHz</option>\n";
		echo "					<option value='48000'>48 kHz</option>\n";
		echo "				</select>\n";
		echo "			</td>\n";
		echo "		</tr>\n";
		echo "	</table>\n";

		echo "</td>\n";
		echo "<td width='30%' style='vertical-align: top;'>\n";

		echo "	<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "		<tr>\n";
		echo "			<td class='vncell' width='30%' valign='top' nowrap='nowrap'>\n";
		echo "				".$text['label-file-path'];
		echo "			</td>\n";
		echo "			<td class='vtable' width='70%'>\n";
		echo "				<input name='file' id='file' type='file' style='display: none;' onchange=\"check_filetype(this);\">";
		echo "				<label id='file_label' for='file' class='txt' style='width: 150px; overflow: hidden; white-space: nowrap;'>".$text['label-select_a_file']."</label>\n";
		echo "			</td>\n";
		echo "		</tr>\n";
		echo "	</table>\n";

		echo "</td>\n";

		echo "</tr>\n";
		echo "</table>\n";

		echo "<div style='float: right; margin-top: 6px;'>";
		echo "	<input type='reset' class='btn' value='".$text['button-reset']."' onclick=\"reset_file_input('file'); document.getElementById('file_label').innerHTML = '".$text['label-select_a_file']."'; name_mode('select'); return true;\">\n";
		echo "	<input name='submit' type='submit' class='btn' id='upload' value='".$text['button-upload']."'>\n";
		echo "</div>\n";

		echo "</form>\n";
	}

//set the row styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//set the variable with an empty string
	$previous_name = '';

//show the array of data
	if (is_array($streams) && @sizeof($streams) != 0) {

		//start the table
			echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0' style='margin-bottom: 3px;'>\n";

		//loop through the array
			foreach ($streams as $row) {

				//set the variables
					$music_on_hold_name = $row['music_on_hold_name'];
					$music_on_hold_rate = $row['music_on_hold_rate'];

					$stream_rate = $row['music_on_hold_rate'];

				//add vertical space
					echo "<tr class='tr_link_void'><td colspan='5'><div style='width: 1px; height: 10px;'></div></td></tr>\n";

				//add the name
					if ($previous_name != $music_on_hold_name) {
						echo "<tr class='tr_link_void'><td colspan='5'><div style='width: 1px; height: 20px;'></div></td></tr>\n";
						echo "<tr class='tr_link_void'>\n";
						echo "	<td colspan='5'><b><i>".escape($music_on_hold_name)."</i></b>";
						if ($row['domain_uuid'] == null) { 
							echo "&nbsp;&nbsp;- ".$text['label-global']."\n";
						}
						echo "	</td>\n";
						echo "</tr>\n";
					}

				//determine if rate was set to auto or not
					$auto_rate = (strlen($music_on_hold_rate) == 0) ? true : false;

				//determine icons to show
					$stream_icons = array();
					$i = 0;
					if (permission_exists('music_on_hold_path')) {
						$stream_icons[$i]['glyphicon'] = 'glyphicon-folder-open';
						$stream_icons[$i]['title'] = $row['music_on_hold_name'];
						$i++;
					}
					if ($row['music_on_hold_shuffle'] == 'true') {
						$stream_icons[$i]['glyphicon'] = 'glyphicon-random';
						$stream_icons[$i]['title'] = $text['label-shuffle'];
						$i++;
					}
					if ($row['music_on_hold_chime_list'] != '') {
						$stream_icons[$i]['glyphicon'] = 'glyphicon-bell';
						$stream_icons[$i]['title'] = $text['label-chime_list'].': '.$row['music_on_hold_chime_list'];
						$i++;
					}
					if ($row['music_on_hold_channels'] == '2') {
						$stream_icons[$i]['glyphicon'] = 'glyphicon-headphones';
						$stream_icons[$i]['title'] = $text['label-stereo'];
						$stream_icons[$i]['margin'] = 6;
						$i++;
					}
					if (is_array($stream_icons) && sizeof($stream_icons) > 0) {
						foreach ($stream_icons as $stream_icon) {
							$icons .= "<span class='glyphicon ".$stream_icon['glyphicon']." icon_glyphicon_body' title='".escape($stream_icon['title'])."' style='width: 12px; height: 12px; margin-left: ".(($stream_icon['margin'] != '') ? $stream_icon['margin'] : 8)."px; vertical-align: text-top; cursor: help;'></span>";
						}
					}

				//set the rate label
					if ($auto_rate) {
						$stream_details = $text['option-default'].' '.$icons;
					}
					else {
						$stream_details = ($music_on_hold_rate/1000).' kHz / '.$icons;
					}
			
				//show the table header
					echo "	<tr>\n";
					echo "		<th class='listhdr'>".$stream_details."</th>\n";
					echo "		<th class='listhdr' style='width: 55px;'>".$text['label-tools']."</th>\n";
					echo "		<th class='listhdr' style='width: 65px; text-align: right; white-space: nowrap;'>".$text['label-file-size']."</th>\n";
					echo "		<th class='listhdr' style='width: 150px; text-align: right;'>".$text['label-uploaded']."</th>\n";
					echo "		<td class='".((!permission_exists('music_on_hold_domain')) ? 'list_control_icon' : 'list_control_icons')." tr_link_void'>";
					if (permission_exists('music_on_hold_edit')) {
						echo "<a href='music_on_hold_edit.php?id=".escape($row['music_on_hold_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
					}
					if (permission_exists('music_on_hold_delete')) {
						echo "<a href='music_on_hold_delete.php?id=".escape($row['music_on_hold_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
					}
					echo 		"</td>\n";
					echo "	</tr>";
					unset($stream_icons, $icons);

				//add the uuid of to the link
					if (permission_exists('music_on_hold_edit')) {
						$tr_link = "href='music_on_hold_edit.php?id=".escape($row['music_on_hold_uuid'])."'";
					}

				//get the music on hold path
					$stream_path = $row['music_on_hold_path'];
					$stream_path = str_replace("\$\${sounds_dir}",$_SESSION['switch']['sounds']['dir'], $stream_path);

				//show the files
					if (file_exists($stream_path)) {
						$stream_files = array_merge(glob($stream_path.'/*.wav'), glob($stream_path.'/*.mp3'), glob($stream_path.'/*.ogg'));
						if (is_array($stream_files) && @sizeof($stream_files) != 0) {
							foreach ($stream_files as $stream_file_path) {
								$stream_file = pathinfo($stream_file_path, PATHINFO_BASENAME);
								$stream_file_size = byte_convert(filesize($stream_file_path));
								$stream_file_date = date("M d, Y H:i:s", filemtime($stream_file_path));
								$stream_file_ext = pathinfo($stream_file, PATHINFO_EXTENSION);
								switch ($stream_file_ext) {
									case "wav" : $stream_file_type = "audio/wav"; break;
									case "mp3" : $stream_file_type = "audio/mpeg"; break;
									case "ogg" : $stream_file_type = "audio/ogg"; break;
								}
								$row_uuid = uuid();
								echo "<tr id='recording_progress_bar_".$row_uuid."' style='display: none;'><td colspan='4' class='".$row_style[$c]." playback_progress_bar_background' style='padding: 0; border: none;'><span class='playback_progress_bar' id='recording_progress_".$row_uuid."'></span></td></tr>\n";
								$tr_link = "href=\"javascript:recording_play('".$row_uuid."');\"";
								echo "<tr ".$tr_link.">\n";
								echo "	<td class='".$row_style[$c]."'>".escape($stream_file)."</td>\n";
								echo "	<td valign='top' class='".$row_style[$c]." row_style_slim tr_link_void'>";
								echo 		"<audio id='recording_audio_".$row_uuid."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$row_uuid."')\" onended=\"recording_reset('".$row_uuid."');\" src='?action=download&id=".escape($row['music_on_hold_uuid'])."&file=".base64_encode($stream_file)."' type='".escape($stream_file_type)."'></audio>";
								echo 		"<span id='recording_button_".$row_uuid."' onclick=\"recording_play('".$row_uuid."')\" title='".$text['label-play']." / ".$text['label-pause']."'>".$v_link_label_play."</span>";
								echo 		"<span onclick=\"recording_stop('".$row_uuid."')\" title='".$text['label-stop']."'>".$v_link_label_stop."</span>";
								echo "	</td>\n";
								echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right; white-space: nowrap;'>".escape($stream_file_size)."</td>\n";
								echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right; white-space: nowrap;'>".escape($stream_file_date)."</td>\n";
								echo "	<td valign='top' class='".((!permission_exists('music_on_hold_domain')) ? 'list_control_icon' : 'list_control_icons')."'>\n";
								echo 		"<a href='?action=download&id=".escape($row['music_on_hold_uuid'])."&file=".base64_encode($stream_file)."' title='".$text['label-download']."'>".$v_link_label_download."</a>";
								if ( (!is_uuid($domain_uuid) && permission_exists('music_on_hold_domain')) || (is_uuid($domain_uuid) && permission_exists('music_on_hold_delete')) ) {
									echo 	"<a href='?action=delete&id=".escape($row['music_on_hold_uuid'])."&file=".base64_encode($stream_file)."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
								}
								echo "	</td>\n";
								echo "</tr>\n";
								$c = ($c) ? 0 : 1;
							}
						}
					}

				//set the previous music_on_hold_name
					$previous_name = $music_on_hold_name;

				//toggle the light highlighting
					$c = ($c) ? 0 : 1;
			}
			unset($streams, $row);

		//end the table
			echo "</table>\n";

	}

	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	echo "			&nbsp;";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
