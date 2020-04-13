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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
	unset($sql, $parameters);

//get the http post data
	if (is_array($_POST['moh'])) {
		$action = $_POST['action'];
		$moh = $_POST['moh'];
	}

//process the http post data by action
	if ($action != '' && is_array($moh) && @sizeof($moh) != 0) {
		switch ($action) {
			case 'delete':
				if (permission_exists('music_on_hold_delete')) {
					$obj = new switch_music_on_hold;
					$obj->delete($moh);
				}
				break;
		}

		header('Location: music_on_hold.php');
		exit;
	}

//download music on hold file
	if ($_GET['action'] == "download"
		&& is_uuid($_GET['id'])
		&& is_array($streams)
		&& @sizeof($streams) != 0) {

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
			$stream_path = str_replace('..', '', $stream_path);

		//get the file and sanitize it
			$stream_file = basename($_GET['file']);
			$search = array('..', '/', ':');
			$stream_file = str_replace($search, '', $stream_file);

		//join the path and file name
			$stream_full_path = path_join($stream_path, $stream_file);

		//download the file
			if (file_exists($stream_full_path)) {
				//content-range
				if (isset($_SERVER['HTTP_RANGE']) && $_GET['t'] != "bin")  {
					range_download($stream_full_path);
				}

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
				if ($_GET['t'] == "bin") {
					header("Content-Length: ".filesize($stream_full_path));
				}
				ob_clean();
				fpassthru($fd);
			}
			exit;
	}

//upload music on hold file
	if ($_POST['action'] == 'upload'
		&& is_array($_FILES)
		&& is_uploaded_file($_FILES['file']['tmp_name'])
		) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: music_on_hold.php');
				exit;
			}

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
					if (is_array($streams) && @sizeof($streams) != 0) {
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
			}

		//get remaining values
			$stream_file_name_temp = $_FILES['file']['tmp_name'];
			$stream_file_name = $_FILES['file']['name'];
			$stream_file_ext = strtolower(pathinfo($stream_file_name, PATHINFO_EXTENSION));

		//check file type
			$valid_file_type = ($stream_file_ext == 'wav' || $stream_file_ext == 'mp3' || $stream_file_ext == 'ogg') ? true : false;

		//proceed for valid file type
			if ($stream_file_ext == 'wav' || $stream_file_ext == 'mp3' || $stream_file_ext == 'ogg') {

				//strip slashes, replace spaces
					$slashes = ["/","\\"];
					$stream_file_name = str_replace($slashes, '', $stream_file_name);
					$stream_file_name = str_replace(' ', '-', $stream_file_name);
					if ($action == "add") {
						$stream_name = str_replace($slashes, '', $stream_name);
						$stream_name = str_replace(' ', '_', $stream_name);
					}

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
					if ($action == "add") {
						$stream_path = path_join($_SESSION['switch']['sounds']['dir'], 'music', $_SESSION['domain_name'], $stream_name, $path_rate);
					}

				//find whether the path already exists
					$stream_new_name = true;
					if (is_array($streams) && @sizeof($streams) != 0) {
						foreach ($streams as $row) {
							$alternate_path = str_replace('$${sounds_dir}', $_SESSION['switch']['sounds']['dir'], $row['music_on_hold_path']);
							if ($stream_path == $row['music_on_hold_path'] || $stream_path == $alternate_path) {
								$stream_new_name = false;
								break;
							}
						}
					}

				//set the variables
					$stream_path = str_replace('$${sounds_dir}', $_SESSION['switch']['sounds']['dir'], $stream_path);

				//add new path
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

				//clear the cache
					$cache = new cache;
					$cache->delete("configuration:local_stream.conf");

				//require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
					$music = new switch_music_on_hold;
					$music->reload();

			}
		//set message for unsupported file type
			else {
				message::add($text['message-unsupported_file_type']);
			}

		//redirect
			header("Location: music_on_hold.php");
			exit;
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-music_on_hold'];
	require_once "resources/header.php";

//script
	echo "<script language='JavaScript' type='text/javascript'>\n";

	//file type check
		echo "	function check_file_type(file_input) {\n";
		echo "		file_ext = file_input.value.substr((~-file_input.value.lastIndexOf('.') >>> 0) + 2);\n";
		echo "		if (file_ext != 'mp3' && file_ext != 'wav' && file_ext != 'ogg' && file_ext != '') {\n";
		echo "			display_message(\"".$text['message-unsupported_file_type']."\", 'negative', '2750');\n";
		echo "		}\n";
		echo "	}\n";

	//custom name (category)
		echo "	function name_mode(mode) {\n";
		echo "		if (mode == 'new') {\n";
		echo "			document.getElementById('name_select').style.display='none';\n";
		echo "			document.getElementById('btn_new').style.display='none';\n";
		echo "			document.getElementById('name_new').style.display='';\n";
		echo "			document.getElementById('btn_select').style.display='';\n";
		echo "			document.getElementById('rate').style.display='';\n";
		echo "			document.getElementById('name_new').focus();\n";
		echo "		}\n";
		echo "		else if (mode == 'select') {\n";
		echo "			document.getElementById('name_new').style.display='none';\n";
		echo "			document.getElementById('name_new').value = '';\n";
		echo "			document.getElementById('rate').style.display='none';\n";
		echo "			document.getElementById('btn_select').style.display='none';\n";
		echo "			document.getElementById('name_select').selectedIndex = 0;\n";
		echo "			document.getElementById('name_select').style.display='';\n";
		echo "			document.getElementById('btn_new').style.display='';\n";
		echo "		}\n";
		echo "	}\n";

	echo "</script>";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-music_on_hold']."</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('music_on_hold_add')) {
		$modify_add_action = !is_array($streams) || @sizeof($streams) == 0 ? "name_mode('new'); $('#btn_select').hide();" : null; //hide categories select box when none exist
		echo 	"<form id='form_upload' class='inline' method='post' enctype='multipart/form-data'>\n";
		echo 	"<input name='action' type='hidden' value='upload'>\n";
		echo 	"<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','onclick'=>"$(this).fadeOut(250, function(){ ".$modify_add_action." $('span#form_upload').fadeIn(250); });"]);
		echo 	"<span id='form_upload' style='display: none;'>";
		echo button::create(['label'=>$text['button-cancel'],'icon'=>$_SESSION['theme']['button_icon_cancel'],'type'=>'button','id'=>'btn_upload_cancel','onclick'=>"$('span#form_upload').fadeOut(250, function(){ name_mode('select'); document.getElementById('form_upload').reset(); $('#btn_add').fadeIn(250) });"]);
		//name (category)
			echo 	"<select name='name' id='name_select' class='formfld' style='width: auto;'>\n";
			echo "		<option value='' selected='selected' disabled='disabled'>".$text['label-category']."</option>\n";

			if (permission_exists('music_on_hold_domain')) {
				echo "	<optgroup label='".$text['option-global']."'>\n";
				if (is_array($streams) && @sizeof($streams) != 0) {
					foreach ($streams as $row) {
						if (strlen($row['domain_uuid']) == 0) {
							if (strlen($row['music_on_hold_rate']) == 0) { $option_name = $row['music_on_hold_name']; }
							if (strlen($row['music_on_hold_rate']) > 0) { $option_name = $row['music_on_hold_name'] .'/'.$row['music_on_hold_rate']; }
							echo "	<option value='".escape($row['music_on_hold_uuid'])."'>".escape($option_name)."</option>\n";
						}
					}
				}
				echo "	</optgroup>\n";
			}
			$local_found = false;
			if (is_array($streams) && @sizeof($streams) != 0) {
				foreach ($streams as $row) {
					if (is_uuid($row['domain_uuid'])) {
						$local_found = true;
						break;
					}
				}
			}
			if ($local_found) {
				if (permission_exists('music_on_hold_domain')) {
					echo "	<optgroup label='".$text['option-local']."'>\n";
				}
				if (is_array($streams) && @sizeof($streams) != 0) {
					foreach ($streams as $row) {
						if (strlen($row['domain_uuid']) > 0) {
							if (strlen($row['music_on_hold_rate']) == 0) { $option_name = $row['music_on_hold_name']; }
							if (strlen($row['music_on_hold_rate']) > 0) { $option_name = $row['music_on_hold_name'] .'/'.$row['music_on_hold_rate']; }
							echo "	<option value='".escape($row['music_on_hold_uuid'])."'>".escape($option_name)."</option>\n";
						}
					}
				}
				if (permission_exists('music_on_hold_domain')) {
					echo "	</optgroup>\n";
				}
			}
			echo "	</select>";
			echo 	"<input class='formfld' style='width: 100px; display: none;' type='text' name='name_new' id='name_new' maxlength='255' placeholder=\"".$text['label-category']."\" value=''>";
		//rate
			echo 	"<select id='rate' name='rate' class='formfld' style='display: none; width: auto;'>\n";
			echo "		<option value=''>".$text['option-default']."</option>\n";
			echo "		<option value='8000'>8 kHz</option>\n";
			echo "		<option value='16000'>16 kHz</option>\n";
			echo "		<option value='32000'>32 kHz</option>\n";
			echo "		<option value='48000'>48 kHz</option>\n";
			echo 	"</select>";
			echo button::create(['type'=>'button','title'=>$text['label-new'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_new','onclick'=>"name_mode('new');"]);
			echo button::create(['type'=>'button','title'=>$text['label-select'],'icon'=>'list','id'=>'btn_select','style'=>'display: none;','onclick'=>"name_mode('select');"]);
		//file
			echo 	"<input type='text' class='txt' style='width: 100px; cursor: pointer;' id='filename' placeholder='Select...' onclick=\"document.getElementById('file').click(); this.blur();\" onfocus='this.blur();'>";
			echo 	"<input type='file' id='file' name='file' style='display: none;' accept='.wav,.mp3,.ogg' onchange=\"document.getElementById('filename').value = this.files.item(0).name; check_file_type(this);\">";
		//submit
			$margin_right = permission_exists('music_on_hold_delete') ? 'margin-right: 15px;' : null;
			echo button::create(['type'=>'submit','label'=>$text['button-upload'],'style'=>$margin_right,'icon'=>$_SESSION['theme']['button_icon_upload']]);
		echo 	"</span>\n";
		echo 	"</form>";
	}
	if (permission_exists('music_on_hold_delete') && $streams) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('music_on_hold_delete') && $streams) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-music_on_hold']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";

//show the array of data
	if (is_array($streams) && @sizeof($streams) != 0) {
		$previous_name = '';

		//loop through the array
			$x = 0;
			foreach ($streams as $row) {

				//set the variables
					$music_on_hold_name = $row['music_on_hold_name'];
					$music_on_hold_rate = $row['music_on_hold_rate'];

				//add the name (category)
					if ($previous_name != $music_on_hold_name) {
						echo "<b><i>".escape($music_on_hold_name)."</i></b>".(!is_uuid($row['domain_uuid']) ? '&nbsp;&nbsp;&nbsp;('.$text['label-global'].')' : null)."<br />\n";
					}

				//determine if rate was set to auto or not
					$auto_rate = strlen($music_on_hold_rate) == 0 ? true : false;

				//determine icons to show
					$stream_icons = array();
					$i = 0;
					if (permission_exists('music_on_hold_path')) {
						$stream_icons[$i]['icon'] = 'fa-folder-open';
						$stream_icons[$i]['title'] = $row['music_on_hold_name'];
						$i++;
					}
					if ($row['music_on_hold_shuffle'] == 'true') {
						$stream_icons[$i]['icon'] = 'fa-random';
						$stream_icons[$i]['title'] = $text['label-shuffle'];
						$i++;
					}
					if ($row['music_on_hold_chime_list'] != '') {
						$stream_icons[$i]['icon'] = 'fa-bell';
						$stream_icons[$i]['title'] = $text['label-chime_list'].': '.$row['music_on_hold_chime_list'];
						$i++;
					}
					if ($row['music_on_hold_channels'] == '2') {
						$stream_icons[$i]['icon'] = 'fa-headphones';
						$stream_icons[$i]['title'] = $text['label-stereo'];
						$stream_icons[$i]['margin'] = 6;
						$i++;
					}
					if (is_array($stream_icons) && sizeof($stream_icons) > 0) {
						foreach ($stream_icons as $stream_icon) {
							$icons .= "<span class='fas ".$stream_icon['icon']." icon_body' title='".escape($stream_icon['title'])."' style='width: 12px; height: 12px; margin-left: ".($stream_icon['margin'] != '' ? $stream_icon['margin'] : 8)."px; vertical-align: text-top; cursor: help;'></span>";
						}
					}

				//set the rate label
					$stream_rate = $auto_rate ? $text['option-default'] : ($music_on_hold_rate/1000).' kHz';
					if (permission_exists('music_on_hold_edit')) {
						$stream_details = "<a href='music_on_hold_edit.php?id=".urlencode($row['music_on_hold_uuid'])."' class='default-color'>".$stream_rate.'</a> '.$icons;
					}
					else {
						$stream_details = $stream_rate.' '.$icons;
					}

				//get the music on hold path and files
					$stream_path = str_replace("\$\${sounds_dir}",$_SESSION['switch']['sounds']['dir'], $row['music_on_hold_path']);
					if (file_exists($stream_path)) {
						$stream_files = array_merge(glob($stream_path.'/*.wav'), glob($stream_path.'/*.mp3'), glob($stream_path.'/*.ogg'));
					}

				//start the table
					echo "<table class='list'>\n";
					echo "	<tr class='list-header'>\n";
					if (permission_exists('music_on_hold_delete')) {
						echo "	<th class='checkbox'>\n";
						echo "		<input type='checkbox' id='checkbox_all_".$row['music_on_hold_uuid']."' name='checkbox_all' onclick=\"list_all_toggle('".$row['music_on_hold_uuid']."'); document.getElementById('checkbox_all_".$row['music_on_hold_uuid']."_hidden').value = this.checked ? 'true' : '';\">\n";
						echo "		<input type='hidden' id='checkbox_all_".$row['music_on_hold_uuid']."_hidden' name='moh[".$row['music_on_hold_uuid']."][checked]'>\n";
						echo "	</th>\n";
					}
					echo "		<th class='pct-50'>".$stream_details."</th>\n";
					echo "		<th class='center shrink'>".$text['label-tools']."</th>\n";
					echo "		<th class='right hide-xs no-wrap pct-20'>".$text['label-file-size']."</th>\n";
					echo "		<th class='right hide-sm-dn pct-30'>".$text['label-uploaded']."</th>\n";
					echo "	</tr>";
					unset($stream_icons, $icons);

				//list the stream files
					if (is_array($stream_files) && @sizeof($stream_files) != 0) {
						foreach ($stream_files as $stream_file_path) {
							$row_uuid = uuid();
							$stream_file = pathinfo($stream_file_path, PATHINFO_BASENAME);
							$stream_file_size = byte_convert(filesize($stream_file_path));
							$stream_file_date = date("M d, Y H:i:s", filemtime($stream_file_path));
							$stream_file_ext = pathinfo($stream_file, PATHINFO_EXTENSION);
							switch ($stream_file_ext) {
								case "wav" : $stream_file_type = "audio/wav"; break;
								case "mp3" : $stream_file_type = "audio/mpeg"; break;
								case "ogg" : $stream_file_type = "audio/ogg"; break;
							}
							//playback progress bar
								echo "<tr class='list-row' id='recording_progress_bar_".$row_uuid."' style='display: none;'><td class='playback_progress_bar_background' style='padding: 0; border: none;' colspan='5'><span class='playback_progress_bar' id='recording_progress_".$row_uuid."'></span></td></tr>\n";
								echo "<tr class='list-row' style='display: none;'><td></td></tr>\n"; // dummy row to maintain alternating background color
							$list_row_link = "javascript:recording_play('".$row_uuid."');";
							echo "<tr class='list-row' href=\"".$list_row_link."\">\n";
							if (permission_exists('music_on_hold_delete')) {
								echo "	<td class='checkbox'>\n";
								echo "		<input type='checkbox' name='moh[".$row['music_on_hold_uuid']."][$x][checked]' id='checkbox_".$x."' class='checkbox_".$row['music_on_hold_uuid']."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all_".$row['music_on_hold_uuid']."').checked = false; }\">\n";
								echo "		<input type='hidden' name='moh[".$row['music_on_hold_uuid']."][$x][file_name]' value=\"".escape($stream_file)."\" />\n";
								echo "	</td>\n";
							}
							echo "	<td class='overflow'>".escape($stream_file)."</td>\n";
							echo "	<td class='button center no-link no-wrap'>";
							echo 		"<audio id='recording_audio_".$row_uuid."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$row_uuid."')\" onended=\"recording_reset('".$row_uuid."');\" src='music_on_hold.php?action=download&id=".escape($row['music_on_hold_uuid'])."&file=".urlencode($stream_file)."' type='".$stream_file_type."'></audio>";
							echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.$row_uuid,'onclick'=>"recording_play('".$row_uuid."');"]);
							echo button::create(['type'=>'button','title'=>$text['label-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'link'=>"?action=download&id=".urlencode($row['music_on_hold_uuid'])."&file=".urlencode($stream_file)]);
							echo "	</td>\n";
							echo "	<td class='right no-wrap hide-xs'>".escape($stream_file_size)."</td>\n";
							echo "	<td class='right no-wrap hide-sm-dn'>".escape($stream_file_date)."</td>\n";
							echo "</tr>\n";
							$x++;
						}
					}

					echo "</table>\n";
					echo "<br />\n";

				//set the previous music_on_hold_name
					$previous_name = $music_on_hold_name;

			}
			unset($streams, $row);

	}

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

//define the download function (helps safari play audio sources)
	function range_download($file) {
		$fp = @fopen($file, 'rb');

		$size   = filesize($file); // File size
		$length = $size;           // Content length
		$start  = 0;               // Start byte
		$end    = $size - 1;       // End byte
		// Now that we've gotten so far without errors we send the accept range header
		/* At the moment we only support single ranges.
		* Multiple ranges requires some more work to ensure it works correctly
		* and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
		*
		* Multirange support annouces itself with:
		* header('Accept-Ranges: bytes');
		*
		* Multirange content must be sent with multipart/byteranges mediatype,
		* (mediatype = mimetype)
		* as well as a boundry header to indicate the various chunks of data.
		*/
		header("Accept-Ranges: 0-$length");
		// header('Accept-Ranges: bytes');
		// multipart/byteranges
		// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
		if (isset($_SERVER['HTTP_RANGE'])) {

			$c_start = $start;
			$c_end   = $end;
			// Extract the range string
			list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			// Make sure the client hasn't sent us a multibyte range
			if (strpos($range, ',') !== false) {
				// (?) Shoud this be issued here, or should the first
				// range be used? Or should the header be ignored and
				// we output the whole content?
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $start-$end/$size");
				// (?) Echo some info to the client?
				exit;
			}
			// If the range starts with an '-' we start from the beginning
			// If not, we forward the file pointer
			// And make sure to get the end byte if spesified
			if ($range0 == '-') {
				// The n-number of the last bytes is requested
				$c_start = $size - substr($range, 1);
			}
			else {
				$range  = explode('-', $range);
				$c_start = $range[0];
				$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
			}
			/* Check the range and make sure it's treated according to the specs.
			* http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
			*/
			// End bytes can not be larger than $end.
			$c_end = ($c_end > $end) ? $end : $c_end;
			// Validate the requested range and return an error if it's not correct.
			if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {

				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $start-$end/$size");
				// (?) Echo some info to the client?
				exit;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1; // Calculate new content length
			fseek($fp, $start);
			header('HTTP/1.1 206 Partial Content');
		}
		// Notify the client the byte range we'll be outputting
		header("Content-Range: bytes $start-$end/$size");
		header("Content-Length: $length");

		// Start buffered download
		$buffer = 1024 * 8;
		while(!feof($fp) && ($p = ftell($fp)) <= $end) {
			if ($p + $buffer > $end) {
				// In case we're only outputtin a chunk, make sure we don't
				// read past the length
				$buffer = $end - $p + 1;
			}
			set_time_limit(0); // Reset time limit for big files
			echo fread($fp, $buffer);
			flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
		}

		fclose($fp);
	}

?>