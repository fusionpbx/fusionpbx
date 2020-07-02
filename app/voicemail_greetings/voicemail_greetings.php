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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

	//check permissions
	if (!permission_exists('voicemail_greeting_view') || (!permission_exists('voicemail_view') && !extension_assigned($_REQUEST["id"]))) {
		echo "access denied";
		return;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http get values and set them as php variables
	$voicemail_id = $_REQUEST["id"];
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//set the back button url
	$_SESSION['back'][$_SERVER['PHP_SELF']] = ($_GET['back'] != '') ? urldecode($_GET['back']) : $_SESSION['back'][$_SERVER['PHP_SELF']];

//define order by default
	if ($order_by == '') {
		$order_by = "greeting_name";
		$order = "asc";
	}

//used (above) to search the array to determine if an extension is assigned to the user
	function extension_assigned($number) {
		foreach ($_SESSION['user']['extension'] as $row) {
			if ((is_numeric($row['number_alias']) && $row['number_alias'] == $number) || $row['user'] == $number) {
				return true;
			}
		}
		return false;
	}

//get currently selected greeting
	$sql = "select greeting_id from v_voicemails ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and voicemail_id = :voicemail_id ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['voicemail_id'] = $voicemail_id;
	$database = new database;
	$selected_greeting_id = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//define greeting directory
	$v_greeting_dir = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$domain_uuid]['domain_name'].'/'.$voicemail_id;

//download the greeting
	if ($_GET['a'] == "download" && (permission_exists('voicemail_greeting_play') || permission_exists('voicemail_greeting_download'))) {
		if ($_GET['type'] == "rec") {
			$voicemail_greeting_uuid = $_GET['uuid'];
			//get voicemail greeting details from db
			$sql = "select greeting_filename, greeting_base64, greeting_id ";
			$sql .= "from v_voicemail_greetings ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and voicemail_greeting_uuid = :voicemail_greeting_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$greeting_filename = $row['greeting_filename'];
				$greeting_id = $row['greeting_id'];
				if ($_SESSION['voicemail']['storage_type']['text'] == 'base64' && $row['greeting_base64'] != '') {
					$greeting_decoded = base64_decode($row['greeting_base64']);
					file_put_contents($v_greeting_dir.'/'.$greeting_filename, $greeting_decoded);
				}
			}
			unset($sql, $row, $greeting_decoded);
			if (file_exists($v_greeting_dir.'/'.$greeting_filename)) {
				//content-range
				if (isset($_SERVER['HTTP_RANGE']) && $_GET['t'] != "bin") {
					range_download($v_greeting_dir.'/'.$greeting_filename);
				}

				$fd = fopen($v_greeting_dir.'/'.$greeting_filename, "rb");
				if ($_GET['t'] == "bin") {
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					header("Content-Description: File Transfer");
				}
				else {
					$file_ext = pathinfo($greeting_filename, PATHINFO_EXTENSION);
					switch ($file_ext) {
						case "wav" : header("Content-Type: audio/x-wav"); break;
						case "mp3" : header("Content-Type: audio/mpeg"); break;
						case "ogg" : header("Content-Type: audio/ogg"); break;
					}
				}
				header('Content-Disposition: attachment; filename="'.$greeting_filename.'"');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				if ($_GET['t'] == "bin") {
					header("Content-Length: ".filesize($v_greeting_dir.'/'.$greeting_filename));
				}
				ob_clean();
				fpassthru($fd);
			}

			//if base64, remove temp greeting file (if not currently selected greeting)
			if ($_SESSION['voicemail']['storage_type']['text'] == 'base64' && $row['greeting_base64'] != '') {
				if ($greeting_id != $selected_greeting_id) {
					@unlink($v_greeting_dir.'/'.$greeting_filename);
				}
			}
		}
		exit;
	}

//upload the greeting
	if (
		$_POST['a'] == "upload"
		&& permission_exists('voicemail_greeting_upload')
		&& $_POST['type'] == 'rec'
		&& is_uploaded_file($_FILES['file']['tmp_name'])
		) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: voicemail_greetings.php?id='.urlencode($voicemail_id));
				exit;
			}

		//get the file extension
			$file_ext = substr($_FILES['file']['name'], -4);

		//check file extension
		if ($file_ext == '.wav' || $file_ext == '.mp3') {

			//find the next available
				for ($i = 1; $i < 10; $i++) {

					//set the file name
					$file_name = 'greeting_'.$i.$file_ext;

					//check the database
					if (is_uuid($domain_uuid) && is_numeric($voicemail_id) ) {
						$sql = "select count(*) from v_voicemail_greetings ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and voicemail_id = :voicemail_id ";
						$sql .= "and greeting_filename = :greeting_filename ";
						$parameters['domain_uuid'] = $domain_uuid;
						$parameters['voicemail_id'] = $voicemail_id;
						$parameters['greeting_filename'] = $file_name;
						$database = new database;
						$num_rows = $database->select($sql, $parameters, 'column');
						unset($sql, $parameters);

						if ($num_rows == 0 && !file_exists($v_greeting_dir.'/'.$file_name)) {
							//move the uploaded greeting
								event_socket_mkdir($v_greeting_dir);
								if ($file_ext == '.wav' || $file_ext == '.mp3') {
									move_uploaded_file($_FILES['file']['tmp_name'], $v_greeting_dir.'/'.$file_name);
								}
							//set newly uploaded greeting as active greeting for voicemail box
								$sql = "update v_voicemails ";
								$sql .= "set greeting_id = :greeting_id ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and voicemail_id = :voicemail_id ";
								$parameters['greeting_id'] = $i;
								$parameters['domain_uuid'] = $domain_uuid;
								$parameters['voicemail_id'] = $voicemail_id;
								$database = new database;
								$database->execute($sql, $parameters);
								unset($sql, $parameters);
							//set message
								message::add($text['message-uploaded'].": ".$_FILES['file']['name']);
							//found available id, exit;
								break;
						}
						else {
							continue;
						}
						unset($num_rows);
					}

				}
		}

		//set the file name to be inserted as the greeting description
			$greeting_description = base64_encode($_FILES['file']['name']);
			header("Location: voicemail_greetings.php?id=".urlencode($voicemail_id)."&order_by=".urlencode($order_by)."&order=".urlencode($order)."&gd=".$greeting_description);
			exit;
	}

//check the permission
	if (permission_exists('voicemail_greeting_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set the greeting
	if ($_REQUEST['action'] == "set") {
		//save the greeting_id to a variable
			$greeting_id = $_REQUEST['greeting_id'];

		//set the greeting_id
			$sql = "update v_voicemails ";
			$sql .= "set greeting_id = :greeting_id ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and voicemail_id = :voicemail_id ";
			$parameters['greeting_id'] = $greeting_id;
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['voicemail_id'] = $voicemail_id;
			$database = new database;
			$database->execute($sql, $parameters);
			unset($sql, $parameters);
		//set message
			message::add($text['message-greeting_selected']);
		//redirect
			header("Location: voicemail_greetings.php?id=".$voicemail_id."&order_by=".$order_by."&order=".$order);
			exit;
	}

//get existing greetings
	$sql = "select voicemail_greeting_uuid, greeting_filename, greeting_base64 ";
	$sql .= "from v_voicemail_greetings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and voicemail_id = :voicemail_id ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['voicemail_id'] = $voicemail_id;
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

	if (is_array($result) && @sizeof($result) != 0) {
		foreach ($result as $x => &$row) {
			$array_greetings[$row['voicemail_greeting_uuid']] = $row['greeting_filename'];
			$array_base64_exists[$row['voicemail_greeting_uuid']] = ($row['greeting_base64'] != '') ? true : false;
			//if not base64, convert back to local files and remove base64 from db
			if ($_SESSION['voicemail']['storage_type']['text'] != 'base64' && $row['greeting_base64'] != '') {
				if (file_exists($v_greeting_dir.'/'.$row['greeting_filename'])) {
					@unlink($v_greeting_dir.'/'.$row['greeting_filename']);
				}
				$greeting_decoded = base64_decode($row['greeting_base64']);
				file_put_contents($v_greeting_dir.'/'.$row['greeting_filename'], $greeting_decoded);
				//build array
				$array['voicemail_greetings'][$x]['voicemail_greeting_uuid'] = $row['voicemail_greeting_uuid'];
				$array['voicemail_greetings'][$x]['greeting_base64'] = null;
			}
		}
		if (is_array($array) && @sizeof($array) != 0) {
			//grant temporary permissions
				$p = new permissions;
				$p->add('voicemail_greeting_edit', 'temp');
			//execute update
				$database = new database;
				$database->app_name = 'voicemail_greetings';
				$database->app_uuid = 'e4b4fbee-9e4d-8e46-3810-91ba663db0c2';
				$database->save($array);
				unset($array);
			//revoke temporary permissions
				$p->delete('voicemail_greeting_edit', 'temp');
		}
	}
	unset($result, $row);

//add greetings to the database
	if (is_dir($v_greeting_dir.'/')) {
		if ($dh = opendir($v_greeting_dir.'/')) {
			$x = 0;
			//prepare for temporary permissions
			$p = new permissions;
			while (($file = readdir($dh)) !== false) {
				if (filetype($v_greeting_dir."/".$file) == "file" && substr($file, 0, 8) == "greeting" && substr($file, 10, 4) != ".tmp") {
					$greeting_number = preg_replace('{\D}', '', $file);
					if (!is_array($array_greetings) || !in_array($file, $array_greetings)) {
						//file not found, add to database
							$greeting_name = $text['label-greeting'].' '.$greeting_number;
							$greeting_description = base64_decode($_GET['gd']);
							$voicemail_greeting_uuid = uuid();
						//build insert array
							$array['voicemail_greetings'][$x]['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
							$array['voicemail_greetings'][$x]['domain_uuid'] = $domain_uuid;
							$array['voicemail_greetings'][$x]['voicemail_id'] = $voicemail_id;
							$array['voicemail_greetings'][$x]['greeting_name'] = $greeting_name;
							$array['voicemail_greetings'][$x]['greeting_filename'] = $file;
							$array['voicemail_greetings'][$x]['greeting_description'] = $greeting_description;
							if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
								$array['voicemail_greetings'][$x]['greeting_base64'] = base64_encode(file_get_contents($v_greeting_dir.'/'.$file));
							}
							$array['voicemail_greetings'][$x]['greeting_id'] = $greeting_number;
							$x++;
						//grant temporary permissions
							$p->add('voicemail_greeting_add', 'temp');
					}
					else {
						//file found, check if base64 present
							if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
								$found_greeting_uuid = array_search($file, $array_greetings);
								if (!$array_base64_exists[$found_greeting_uuid]) {
									//build update array
										$array['voicemail_greetings'][$x]['voicemail_greeting_uuid'] = $found_greeting_uuid;
										$array['voicemail_greetings'][$x]['greeting_base64'] = base64_encode(file_get_contents($v_greeting_dir.'/'.$file));
										$x++;
									//grant temporary permissions
										$p->add('voicemail_greeting_edit', 'temp');
								}
							}
					}

					//if base64, remove local file (unless currently selected greeting)
					if ($_SESSION['voicemail']['storage_type']['text'] == 'base64' && file_exists($v_greeting_dir.'/'.$file)) {
						if ($greeting_number != $selected_greeting_id) {
							@unlink($v_greeting_dir.'/'.$file);
						}
					}
				}
			}
			if (is_array($array) && @sizeof($array) != 0) {
				//execute inserts/updates
					$database = new database;
					$database->app_name = 'voicemail_greetings';
					$database->app_uuid = 'e4b4fbee-9e4d-8e46-3810-91ba663db0c2';
					$database->save($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('voicemail_greeting_add', 'temp');
					$p->delete('voicemail_greeting_edit', 'temp');
			}

			closedir($dh);
		}
	}

//get the http post data
	if (is_array($_POST['voicemail_greetings'])) {
		$action = $_POST['action'];
		$voicemail_id = $_POST['voicemail_id'];
		$voicemail_greetings = $_POST['voicemail_greetings'];
	}

//process the http post data by action
	if ($action != '' && is_array($voicemail_greetings) && @sizeof($voicemail_greetings) != 0) {
		switch ($action) {
			case 'delete':
				if (permission_exists('voicemail_greeting_delete')) {
					$obj = new voicemail_greetings;
					$obj->voicemail_id = $voicemail_id;
					$obj->delete($voicemail_greetings);
				}
				break;
		}

		header('Location: voicemail_greetings.php?id='.urlencode($voicemail_id).'&back='.urlencode(PROJECT_PATH.'/app/voicemails/voicemails.php'));
		exit;
	}

//get the greetings list
	if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
		switch ($db_type) {
			case 'pgsql': $sql_file_size = ", length(decode(greeting_base64,'base64')) as greeting_size "; break;
			case 'mysql': $sql_file_size = ", length(from_base64(greeting_base64)) as greeting_size "; break;
		}
	}
	$sql = "select * ".$sql_file_size." from v_voicemail_greetings ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and voicemail_id = :voicemail_id ";
	$sql .= order_by($order_by, $order);
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['voicemail_id'] = $voicemail_id;
	$database = new database;
	$greetings = $database->select($sql, $parameters, 'all');
	$num_rows = is_array($greetings) ? @sizeof($greetings) : 0;
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title'];
	require_once "resources/header.php";

//file type check script
	echo "<script language='JavaScript' type='text/javascript'>\n";
	echo "	function check_file_type(file_input) {\n";
	echo "		file_ext = file_input.value.substr((~-file_input.value.lastIndexOf('.') >>> 0) + 2);\n";
	echo "		if (file_ext != 'mp3' && file_ext != 'wav' && file_ext != 'ogg' && file_ext != '') {\n";
	echo "			display_message(\"".$text['message-unsupported_file_type']."\", 'negative', '2750');\n";
	echo "			document.getElementById('form_upload').reset();\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>$_SESSION['back'][$_SERVER['PHP_SELF']]]);
	if (permission_exists('voicemail_greeting_upload')) {
		echo 	"<form id='form_upload' class='inline' method='post' enctype='multipart/form-data'>\n";
		echo 	"<input name='a' type='hidden' value='upload'>\n";
		echo 	"<input type='hidden' name='id' value='".escape($voicemail_id)."'>\n";
		echo 	"<input type='hidden' name='type' value='rec'>\n";
		echo 	"<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','onclick'=>"$(this).fadeOut(250, function(){ $('span#form_upload').fadeIn(250); document.getElementById('ulfile').click(); });"]);
		echo 	"<span id='form_upload' style='display: none;'>";
		echo button::create(['label'=>$text['button-cancel'],'icon'=>$_SESSION['theme']['button_icon_cancel'],'type'=>'button','id'=>'btn_upload_cancel','onclick'=>"$('span#form_upload').fadeOut(250, function(){ document.getElementById('form_upload').reset(); $('#btn_add').fadeIn(250) });"]);
		echo 		"<input type='text' class='txt' style='width: 100px; cursor: pointer;' id='filename' placeholder='Select...' onclick=\"document.getElementById('ulfile').click(); this.blur();\" onfocus='this.blur();'>";
		echo 		"<input type='file' id='ulfile' name='file' style='display: none;' accept='.wav,.mp3,.ogg' onchange=\"document.getElementById('filename').value = this.files.item(0).name; check_file_type(this);\">";
		echo button::create(['type'=>'submit','label'=>$text['button-upload'],'icon'=>$_SESSION['theme']['button_icon_upload']]);
		echo 	"</span>\n";
		echo 	"</form>";
	}
	if (permission_exists('voicemail_greeting_delete') && $greetings) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('voicemail_greeting_delete') && $greetings) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description']." <strong>".escape($voicemail_id)."</strong>\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' id='voicemail_id' name='voicemail_id' value='".escape($voicemail_id)."'>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	$col_count = 0;
	if (permission_exists('voicemail_greeting_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($greetings ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
		$col_count++;
	}
	echo "<th class='shrink center'>".$text['label-selected']."</th>\n";
	$col_count++;
 	echo th_order_by('greeting_id', $text['label-number'], $order_by, $order, null, "class='center shrink'", "id=".urlencode($voicemail_id));
	$col_count++;
	echo th_order_by('greeting_name', $text['label-name'], $order_by, $order, null, null, "id=".urlencode($voicemail_id));
	$col_count++;
	if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
		echo th_order_by('greeting_filename', $text['label-filename'], $order_by, $order, null, "class='hide-sm-dn'", "id=".urlencode($voicemail_id));
		$col_count++;
	}
	if (permission_exists('voicemail_greeting_play') || permission_exists('voicemail_greeting_download')) {
		echo "<th class='center'>".$text['label-tools']."</th>\n";
		$col_count++;
	}
	echo "<th class='center no-wrap hide-xs'>".$text['label-size']."</th>\n";
	$col_count++;
	if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
		echo "<th class='center no-wrap hide-xs'>".$text['label-uploaded']."</th>\n";
		$col_count++;
	}
	echo th_order_by('greeting_description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn pct-25'", "id=".urlencode($voicemail_id));
	if (permission_exists('voicemail_greeting_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($greetings) && @sizeof($greetings) != 0) {
		$x = 0;
		foreach ($greetings as $row) {
			//playback progress bar
				if (permission_exists('voicemail_greeting_play')) {
					echo "<tr class='list-row' id='recording_progress_bar_".escape($row['voicemail_greeting_uuid'])."' style='display: none;'><td class='playback_progress_bar_background' style='padding: 0; border: none;' colspan='".$col_count."'><span class='playback_progress_bar' id='recording_progress_".escape($row['voicemail_greeting_uuid'])."'></span></td><td class='description hide-sm-dn' style='border-bottom: none !important;'></td></tr>\n";
					echo "<tr class='list-row' style='display: none;'><td></td></tr>\n"; // dummy row to maintain alternating background color
				}
			if (permission_exists('voicemail_greeting_edit')) {
				$list_row_url = "voicemail_greeting_edit.php?id=".urlencode($row['voicemail_greeting_uuid'])."&voicemail_id=".urlencode($voicemail_id);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('voicemail_greeting_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='voicemail_greetings[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='voicemail_greetings[$x][uuid]' value='".escape($row['voicemail_greeting_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td class='center no-link'>";
			$selected = ($row['greeting_id'] == $selected_greeting_id) ? true : false;
			echo 		"<input type='radio' onclick=\"window.location='".PROJECT_PATH."/app/voicemail_greetings/voicemail_greetings.php?id=".escape($voicemail_id)."&greeting_id=".escape($row['greeting_id'])."&action=set&order_by=".$order_by."&order=".$order."';\" name='greeting_id' value='".escape($row['greeting_id'])."' ".(($selected) ? "checked='checked'" : null)." style='display: block; width: 20px; height: auto; margin: auto calc(50% - 10px);'>\n";
			echo "	</td>\n";
			echo "	<td class='center'>".escape($row['greeting_id'])."</td>\n";
			echo "	<td class='no-wrap'>";
			if (permission_exists('voicemail_greeting_edit')) {
				echo "<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['greeting_name'])."</a>";
			}
			else {
				echo escape($row['greeting_name']);
			}
			echo "	</td>\n";
			if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
				echo "	<td class='hide-sm-dn'>".escape($row['greeting_filename'])."</td>\n";
			}
			if (permission_exists('voicemail_greeting_play') || permission_exists('voicemail_greeting_download')) {
				echo "	<td class='middle button center no-link no-wrap'>";
				if (permission_exists('voicemail_greeting_play')) {
					$greeting_file_path = $row['greeting_filename'];
					$greeting_file_name = strtolower(pathinfo($greeting_file_path, PATHINFO_BASENAME));
					$greeting_file_ext = pathinfo($greeting_file_name, PATHINFO_EXTENSION);
					switch ($greeting_file_ext) {
						case "wav" : $greeting_type = "audio/wav"; break;
						case "mp3" : $greeting_type = "audio/mpeg"; break;
						case "ogg" : $greeting_type = "audio/ogg"; break;
					}
					echo "<audio id='recording_audio_".escape($row['voicemail_greeting_uuid'])."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".escape($row['voicemail_greeting_uuid'])."')\" onended=\"recording_reset('".escape($row['voicemail_greeting_uuid'])."');\" src=\"voicemail_greetings.php?id=".escape($voicemail_id)."&a=download&type=rec&uuid=".escape($row['voicemail_greeting_uuid'])."\" type='".$greeting_type."'></audio>";
					echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.escape($row['voicemail_greeting_uuid']),'onclick'=>"recording_play('".escape($row['voicemail_greeting_uuid'])."')"]);
				}
				if (permission_exists('voicemail_greeting_download')) {
					echo button::create(['type'=>'button','title'=>$text['label-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'link'=>"voicemail_greetings.php?a=download&type=rec&t=bin&id=".urlencode($voicemail_id)."&uuid=".escape($row['voicemail_greeting_uuid'])]);
				}
				echo "	</td>\n";
			}
			if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
				$file_size = byte_convert($row['greeting_size']);
				echo "	<td class='center no-wrap hide-xs'>".$file_size."</td>\n";
			}
			else {
				$file_size = byte_convert(filesize($v_greeting_dir.'/'.$row['greeting_filename']));
				$file_date = date("M d, Y H:i:s", filemtime($v_greeting_dir.'/'.$row['greeting_filename']));
				echo "	<td class='center no-wrap hide-xs'>".$file_size."</td>\n";
				echo "	<td class='center no-wrap hide-xs'>".$file_date."</td>\n";
			}
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['greeting_description'])."&nbsp;</td>\n";
			if (permission_exists('voicemail_greeting_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($greetings);
	}

	echo "</table>\n";
	echo "<br />\n";
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
