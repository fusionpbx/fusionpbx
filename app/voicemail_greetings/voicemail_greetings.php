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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the max php execution time
	ini_set(max_execution_time,7200);

//get the http get values and set them as php variables
	$voicemail_id = check_str($_REQUEST["id"]);
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//define order by default
	if ($order_by == '') {
		$order_by = "greeting_name";
		$order = "asc";
	}

//deny access if the user extension is not assigned
	if (!permission_exists('voicemail_greeting_view')) {
		if (!is_extension_assigned($voicemail_id)) {
			echo "access denied";
			return;
		}
	}

//used (above) to search the array to determine if an extension is assigned to the user
	function is_extension_assigned($number) {
		$result = false;
		foreach ($_SESSION['user']['extension'] as $row) {
			if ($row['user'] == $number) {
				$result = true;
			}
		}
		return $result;
	}

//get currently selected greeting
	$sql = "select greeting_id from v_voicemails ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$sql .= "and voicemail_id = '".$voicemail_id."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$selected_greeting_id = $row['greeting_id'];
	}
	unset($prep_statement, $row);

//define greeting directory
	$v_greeting_dir = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$domain_uuid]['domain_name'].'/'.$voicemail_id;

//download the greeting
	if ($_GET['a'] == "download" && (permission_exists('voicemail_greeting_play') || permission_exists('voicemail_greeting_download'))) {
		session_cache_limiter('public');
		if ($_GET['type'] = "rec") {
			$voicemail_greeting_uuid = check_str($_GET['uuid']);
			//get voicemail greeting details from db
			$sql = "select greeting_filename, greeting_base64, greeting_id from v_voicemail_greetings ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and voicemail_greeting_uuid = '".$voicemail_greeting_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			if (count($result) > 0) {
				foreach($result as &$row) {
					$greeting_filename = $row['greeting_filename'];
					$greeting_id = $row['greeting_id'];
					if ($_SESSION['voicemail']['storage_type']['text'] == 'base64' && $row['greeting_base64'] != '') {
						$greeting_decoded = base64_decode($row['greeting_base64']);
						file_put_contents($v_greeting_dir.'/'.$greeting_filename, $greeting_decoded);
					}
					break;
				}
			}
			unset ($sql, $prep_statement, $result, $greeting_decoded);

			if (file_exists($v_greeting_dir.'/'.$greeting_filename)) {
				$fd = fopen($v_greeting_dir.'/'.$greeting_filename, "rb");
				if ($_GET['t'] == "bin") {
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					header("Content-Description: File Transfer");
				}
				else {
					$file_ext = substr($greeting_filename, -3);
					if ($file_ext == "wav") {
						header("Content-Type: audio/x-wav");
					}
					if ($file_ext == "mp3") {
						header("Content-Type: audio/mpeg");
					}
				}
				header('Content-Disposition: attachment; filename="'.$greeting_filename.'"');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header("Content-Length: " . filesize($v_greeting_dir.'/'.$greeting_filename));
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
	if (permission_exists('voicemail_greeting_upload')) {
		if ($_POST['submit'] == $text['button-upload'] && $_POST['type'] == 'rec' && is_uploaded_file($_FILES['file']['tmp_name'])) {
			//find the next available
				for ($i = 1; $i < 10; $i++) {
					$file_name = 'greeting_'.$i.'.wav';
					//check the database
					$sql = "select voicemail_greeting_uuid from v_voicemail_greetings ";
					$sql .= "where domain_uuid = '".$domain_uuid."' ";
					$sql .= "and voicemail_id = '".$voicemail_id."' ";
					$sql .= "and greeting_filename = '".$file_name."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					if (count($result) == 0 && !file_exists($v_greeting_dir.'/'.$file_name)) {
						//move the uploaded greeting
							mkdir($v_greeting_dir, 0777, true);
							move_uploaded_file($_FILES['file']['tmp_name'], $v_greeting_dir.'/'.$file_name);
						//set newly uploaded greeting as active greeting for voicemail box
							$sql = "update v_voicemails ";
							$sql .= "set greeting_id = '".$i."' ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and voicemail_id = '".$voicemail_id."' ";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
							unset($prep_statement);

						$_SESSION["message"] = $text['message-uploaded'].": ".$_FILES['file']['name'];
						break;
					}
					else {
						continue;
					}
					unset ($prep_statement);
				}

			//set the file name to be inserted as the greeting description
				$greeting_description = base64_encode($_FILES['file']['name']);
				header("Location: voicemail_greetings.php?id=".$voicemail_id."&order_by=".$order_by."&order=".$order."&gd=".$greeting_description);
				exit;
		}
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
			$greeting_id = check_str($_REQUEST['greeting_id']);

		//set the greeting_id
			$sql = "update v_voicemails ";
			$sql .= "set greeting_id = '".$greeting_id."' ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and voicemail_id = '".$voicemail_id."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($prep_statement);

		$_SESSION["message"] = $text['message-greeting_selected'];
		header("Location: voicemail_greetings.php?id=".$voicemail_id."&order_by=".$order_by."&order=".$order);
		exit;
	}

//get existing greetings
	$sql = "select voicemail_greeting_uuid, greeting_filename, greeting_base64 from v_voicemail_greetings ";
	$sql .= "where domain_uuid = '".$domain_uuid."' and voicemail_id = '".$voicemail_id."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$array_greetings[$row['voicemail_greeting_uuid']] = $row['greeting_filename'];
		$array_base64_exists[$row['voicemail_greeting_uuid']] = ($row['greeting_base64'] != '') ? true : false;
		//if not base64, convert back to local files and remove base64 from db
		if ($_SESSION['voicemail']['storage_type']['text'] != 'base64' && $row['greeting_base64'] != '') {
			if (file_exists($v_greeting_dir.'/'.$row['greeting_filename'])) {
				@unlink($v_greeting_dir.'/'.$row['greeting_filename']);
			}
			$greeting_decoded = base64_decode($row['greeting_base64']);
			file_put_contents($v_greeting_dir.'/'.$row['greeting_filename'], $greeting_decoded);
			$sql = "update v_voicemail_greetings ";
			$sql .= "set greeting_base64 = null ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and voicemail_greeting_uuid = '".$row['voicemail_greeting_uuid']."' ";
			$db->exec(check_sql($sql));
			unset($sql);
		}
	}
	unset ($prep_statement);

//add greetings to the database
	if (is_dir($v_greeting_dir.'/')) {
		if ($dh = opendir($v_greeting_dir.'/')) {
			while (($file = readdir($dh)) !== false) {
				if (filetype($v_greeting_dir."/".$file) == "file" && substr($file, 0, 8) == "greeting" && substr($file, 10, 4) != ".tmp") {
					$greeting_number = preg_replace('{\D}', '', $file);
					if (!in_array($file, $array_greetings)) {
						//file not found, add to database
						$greeting_name = $text['label-greeting'].' '.$greeting_number;
						$greeting_description = base64_decode($_GET['gd']);
						$voicemail_greeting_uuid = uuid();
						$sql = "insert into v_voicemail_greetings ";
						$sql .= "( ";
						$sql .= "voicemail_greeting_uuid, ";
						$sql .= "domain_uuid, ";
						$sql .= "voicemail_id, ";
						$sql .= "greeting_name, ";
						$sql .= "greeting_filename, ";
						$sql .= "greeting_description, ";
						if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
							$sql .= "greeting_base64, ";
						}
						$sql .= "greeting_id ";
						$sql .= ") ";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".$voicemail_greeting_uuid."', ";
						$sql .= "'".$domain_uuid."', ";
						$sql .= "'".$voicemail_id."', ";
						$sql .= "'".$greeting_name."', ";
						$sql .= "'".$file."', ";
						$sql .= "'".$greeting_description."', ";
						if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
							$greeting_base64 = base64_encode(file_get_contents($v_greeting_dir.'/'.$file));
							$sql .= "'".$greeting_base64."', ";
						}
						$sql .= $greeting_number." ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
					}
					else {
						//file found, check if base64 present
						if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
							$found_greeting_uuid = array_search($file, $array_greetings);
							if (!$array_base64_exists[$found_greeting_uuid]) {
								$greeting_base64 = base64_encode(file_get_contents($v_greeting_dir.'/'.$file));
								$sql = "update v_voicemail_greetings set ";
								$sql .= "greeting_base64 = '".$greeting_base64."' ";
								$sql .= "where domain_uuid = '".$domain_uuid."' ";
								$sql .= "and voicemail_greeting_uuid = '".$found_greeting_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
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
			} //while
			closedir($dh);
		} //if
	} //if


//include the header
	$document['title'] = $text['title'];
	require_once "resources/header.php";

//begin the content
	echo "<script>\n";
	echo "function EvalSound(soundobj) {\n";
	echo "	var thissound= eval(\"document.\"+soundobj);\n";
	echo "	thissound.Play();\n";
	echo "}\n";
	echo "</script>";

	echo "<form name='frm' method='POST' enctype='multipart/form-data' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' nowrap valign='top'>\n";
	echo "			<span class='title'>".$text['title']."</span>\n";
	echo "			<br><br>\n";
	echo "			".$text['description']." <strong>".$voicemail_id."</strong>\n";
	echo "		</td>";
	if (permission_exists('voicemail_greeting_upload')) {
		echo "		<td align='right' nowrap valign='top'>\n";
		echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='".PROJECT_PATH."/app/voicemails/voicemails.php';\" value='".$text['button-back']."'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
		echo "			<input name='file' type='file' class='formfld fileinput' id='file'>\n";
		echo "			<input name='type' type='hidden' value='rec'>\n";
		echo "			<input name='submit' type='submit' class='btn' id='upload' value=\"".$text['button-upload']."\">\n";
		echo "		</td>\n";
	}
	echo "	</tr>";
	echo "</table>\n";
	echo "<br />\n";

	//get the greetings list
		$sql = "select * from v_voicemail_greetings ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and voicemail_id = '".$voicemail_id."' ";
		$sql .= "order by ".$order_by." ".$order." ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th width='2'>&nbsp;</th>\n";
	echo th_order_by('greeting_id', $text['table-number'], $order_by, $order, '', "width='20'", "id=".$voicemail_id);
	echo th_order_by('greeting_name', $text['table-name'], $order_by, $order, '', '', "id=".$voicemail_id);
	if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
		echo th_order_by('greeting_filename', $text['table-filename'], $order_by, $order, '', '', "id=".$voicemail_id);
		echo "<th class='listhdr' style='text-align: right;' nowrap='nowrap'>".$text['table-size']."</th>\n";
	}
	if (permission_exists('voicemail_greeting_play') || permission_exists('voicemail_greeting_download')) {
		echo "<th>".$text['label-tools']."</th>\n";
	}
	echo th_order_by('greeting_description', $text['table-description'], $order_by, $order, '', '', "id=".$voicemail_id);
	echo "<td align='right' width='21'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	//calculate colspan for progress bar
	$colspan = 7; //max
	if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') { $colspan = $colspan - 2; }
	if (!(permission_exists('voicemail_greeting_edit') || permission_exists('voicemail_greeting_delete'))) { $colspan = $colspan - 1; }

	if ($result_count > 0) {
		foreach($result as $row) {
			//playback progress bar
			if (permission_exists('voicemail_greeting_play')) {
				echo "<tr id='recording_progress_bar_".$row['voicemail_greeting_uuid']."' style='display: none;'><td colspan='".$colspan."'><span class='playback_progress_bar' id='recording_progress_".$row['voicemail_greeting_uuid']."'></span></td></tr>\n";
			}
			$tr_link = (permission_exists('voicemail_greeting_edit')) ? "href='voicemail_greeting_edit.php?id=".$row['voicemail_greeting_uuid']."&voicemail_id=".$voicemail_id."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td class='".$row_style[$c]." tr_link_void' width='30px;' valign='top'>";
			$selected = ($row['greeting_id'] == $selected_greeting_id) ? true : false;
			echo 		"<input type='radio' onclick=\"window.location='".PROJECT_PATH."/app/voicemail_greetings/voicemail_greetings.php?id=".$voicemail_id."&greeting_id=".$row['greeting_id']."&action=set&order_by=".$order_by."&order=".$order."';\" name='greeting_id' value='".$row['greeting_id']."' ".(($selected) ? "checked='checked'" : null).">\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['greeting_id']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['greeting_name']."</td>\n";
			if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
				echo "	<td valign='top' class='".$row_style[$c]."'>".$row['greeting_filename']."</td>\n";
				$tmp_filesize = filesize($v_greeting_dir.'/'.$row['greeting_filename']);
				$tmp_filesize = byte_convert($tmp_filesize);
				echo "	<td class='".$row_style[$c]."' style='text-align: right;' nowrap>".$tmp_filesize."</td>\n";
			}
			if (permission_exists('voicemail_greeting_play') || permission_exists('voicemail_greeting_download')) {
				echo "	<td valign='top' class='".$row_style[$c]." row_style_slim tr_link_void'>";
				if (permission_exists('voicemail_greeting_play')) {
					$greeting_file_path = $row['greeting_filename'];
					$greeting_file_name = strtolower(pathinfo($greeting_file_path, PATHINFO_BASENAME));
					$greeting_file_ext = pathinfo($greeting_file_name, PATHINFO_EXTENSION);
					switch ($greeting_file_ext) {
						case "wav" : $greeting_type = "audio/wav"; break;
						case "mp3" : $greeting_type = "audio/mpeg"; break;
						case "ogg" : $greeting_type = "audio/ogg"; break;
					}
					echo "<audio id='recording_audio_".$row['voicemail_greeting_uuid']."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$row['voicemail_greeting_uuid']."')\" onended=\"recording_reset('".$row['voicemail_greeting_uuid']."');\" src=\"voicemail_greetings.php?id=".$voicemail_id."&a=download&type=rec&uuid=".$row['voicemail_greeting_uuid']."\" type='".$greeting_type."'></audio>";
					echo "<span id='recording_button_".$row['voicemail_greeting_uuid']."' onclick=\"recording_play('".$row['voicemail_greeting_uuid']."')\" title='".$text['label-play']." / ".$text['label-pause']."'>".$v_link_label_play."</span>";
				}
				if (permission_exists('voicemail_greeting_download')) {
					echo "<a href=\"voicemail_greetings.php?a=download&type=rec&t=bin&id=".$voicemail_id."&uuid=".$row['voicemail_greeting_uuid']."\" title='".$text['label-download']."'>".$v_link_label_download."</a>";
				}
				echo "	</td>\n";
			}
			echo "	<td width='30%' valign='top' class='row_stylebg'>".$row['greeting_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>\n";
			if (permission_exists('voicemail_greeting_edit')) {
				echo "<a href='voicemail_greeting_edit.php?id=".$row['voicemail_greeting_uuid']."&voicemail_id=".$voicemail_id."' alt='edit'>$v_link_label_edit</a>";
			}
			if (permission_exists('voicemail_greeting_delete')) {
				echo "<a href='voicemail_greeting_delete.php?id=".$row['voicemail_greeting_uuid']."&voicemail_id=".$voicemail_id."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";

			$c = ($c) ? 0 : 1;
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results
	echo "</table>\n";
	echo "<br />\n";

	echo "<input type='hidden' name='id' value='$voicemail_id'>\n";
	echo "</form>";

	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";
?>