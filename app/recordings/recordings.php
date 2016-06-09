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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
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
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//define order by default
	if ($order_by == '') {
		$order_by = "recording_name";
		$order = "asc";
	}

//download the recordings
	if ($_GET['a'] == "download" && (permission_exists('recording_play') || permission_exists('recording_download'))) {
		session_cache_limiter('public');
		if ($_GET['type'] = "rec") {
			$path = $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name'];

			//if from recordings, get recording details from db
				$recording_uuid = check_str($_GET['id']); //recordings
				if ($recording_uuid != '') {
					$sql = "select recording_filename, recording_base64 from v_recordings ";
					$sql .= "where domain_uuid = '".$domain_uuid."' ";
					$sql .= "and recording_uuid = '".$recording_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					if (count($result) > 0) {
						foreach($result as &$row) {
							$recording_filename = $row['recording_filename'];
							if ($_SESSION['recordings']['storage_type']['text'] == 'base64' && $row['recording_base64'] != '') {
								$recording_decoded = base64_decode($row['recording_base64']);
								file_put_contents($path.'/'.$recording_filename, $recording_decoded);
							}
							break;
						}
					}
					unset ($sql, $prep_statement, $result, $recording_decoded);
				}
			//if from xml_cdr, use file system
				else {
					$recording_filename = base64_decode($_GET['filename']); //xml_cdr
				}

			// build full path
				if(substr($recording_filename,0,1) == '/'){
					$full_recording_path = $path . $recording_filename;
				} else {
					$full_recording_path = $path . '/' . $recording_filename;
				}

			if (file_exists($full_recording_path)) {
				$fd = fopen($full_recording_path, "rb");
				if ($_GET['t'] == "bin") {
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					header("Content-Description: File Transfer");
				}
				else {
					$file_ext = substr($recording_filename, -3);
					if ($file_ext == "wav") {
						header("Content-Type: audio/x-wav");
					}
					if ($file_ext == "mp3") {
						header("Content-Type: audio/mpeg");
					}
				}
				header('Content-Disposition: attachment; filename="'.$recording_filename.'"');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header("Content-Length: " . filesize($full_recording_path));
				ob_clean();
				fpassthru($fd);
			}

			//if base64, remove temp recording file
			if ($_SESSION['recordings']['storage_type']['text'] == 'base64' && $row['recording_base64'] != '') {
				@unlink($full_recording_path);
			}
		}
		exit;
	}

//upload the recording
	if (permission_exists('recording_upload')) {
		if ($_POST['submit'] == $text['button-upload'] && $_POST['type'] == 'rec' && is_uploaded_file($_FILES['ulfile']['tmp_name'])) {
			$recording_filename = str_replace(" ", "_", $_FILES['ulfile']['name']);
			$recording_filename = str_replace("'", "", $recording_filename);
			move_uploaded_file($_FILES['ulfile']['tmp_name'], $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$recording_filename);

			$_SESSION['message'] = $text['message-uploaded'].": ".htmlentities($recording_filename);

			//set the file name to be inserted as the recording description
				$recording_description = base64_encode($_FILES['ulfile']['name']);
				header("Location: recordings.php?rd=".$recording_description);
				exit;
		}
	}

//check the permission
	if (permission_exists('recording_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get existing recordings
	$sql = "select recording_uuid, recording_filename, recording_base64 from v_recordings ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$array_recordings[$row['recording_uuid']] = $row['recording_filename'];
		$array_base64_exists[$row['recording_uuid']] = ($row['recording_base64'] != '') ? true : false;
		//if not base64, convert back to local files and remove base64 from db
		if ($_SESSION['recordings']['storage_type']['text'] != 'base64' && $row['recording_base64'] != '') {
			if (!file_exists($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$row['recording_filename'])) {
				$recording_decoded = base64_decode($row['recording_base64']);
				file_put_contents($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$row['recording_filename'], $recording_decoded);
				$sql = "update v_recordings set recording_base64 = null where domain_uuid = '".$domain_uuid."' and recording_uuid = '".$row['recording_uuid']."' ";
				$db->exec(check_sql($sql));
				unset($sql);
			}
		}
	}
	unset ($prep_statement);

//add recordings to the database
	if (is_dir($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/')) {
		if ($dh = opendir($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/')) {
			while (($recording_filename = readdir($dh)) !== false) {
				if (filetype($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$recording_filename) == "file") {

					if (!in_array($recording_filename, $array_recordings)) {
						//file not found in db, add it
						$recording_uuid = uuid();
						$recording_name = ucwords(str_replace('_', ' ', pathinfo($recording_filename, PATHINFO_FILENAME)));
						$recording_description = check_str(base64_decode($_GET['rd']));
						$sql = "insert into v_recordings ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "recording_uuid, ";
						$sql .= "recording_filename, ";
						$sql .= "recording_name, ";
						$sql .= "recording_description ";
						if ($_SESSION['recordings']['storage_type']['text'] == 'base64') {
							$sql .= ", recording_base64 ";
						}
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".$domain_uuid."', ";
						$sql .= "'".$recording_uuid."', ";
						$sql .= "'".$recording_filename."', ";
						$sql .= "'".$recording_name."', ";
						$sql .= "'".$recording_description."' ";
						if ($_SESSION['recordings']['storage_type']['text'] == 'base64') {
							$recording_base64 = base64_encode(file_get_contents($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$recording_filename));
							$sql .= ", '".$recording_base64."' ";
						}
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
					}
					else {
						//file found in db, check if base64 present
						if ($_SESSION['recordings']['storage_type']['text'] == 'base64') {
							$found_recording_uuid = array_search($recording_filename, $array_recordings);
							if (!$array_base64_exists[$found_recording_uuid]) {
								$recording_base64 = base64_encode(file_get_contents($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$recording_filename));
								$sql = "update v_recordings set ";
								$sql .= "recording_base64 = '".$recording_base64."' ";
								$sql .= "where domain_uuid = '".$domain_uuid."' ";
								$sql .= "and recording_uuid = '".$found_recording_uuid."' ";
								$db->exec(check_sql($sql));
								unset($sql);
							}
						}
					}

					//if base64, remove local file
					if ($_SESSION['recordings']['storage_type']['text'] == 'base64' && file_exists($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$recording_filename)) {
						@unlink($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$recording_filename);
					}

				}
			} //while
			closedir($dh);
		} //if
	} //if


//add paging
	require_once "resources/paging.php";

//include the header
	$document['title'] = $text['title'];
	require_once "resources/header.php";

//begin the content
	if (permission_exists('recording_upload')) {
		echo "<table cellpadding='0' cellspacing='0' border='0' align='right'>\n";
		echo "	<tr>\n";
		echo "		<td nowrap='nowrap'>\n";
		echo "			<form action='' method='post' enctype='multipart/form-data' name='frmUpload'>\n";
		echo "			<input name='type' type='hidden' value='rec'>\n";
		echo "			<input name='ulfile' type='file' class='formfld fileinput' style='width: 260px;' id='ulfile'>\n";
		echo "			<input name='submit' type='submit'  class='btn' id='upload' value=\"".$text['button-upload']."\">\n";
		echo "			</form>";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>";
	}
	echo "<b>".$text['title-recordings']."</b>";
	echo "<br /><br />\n";
	echo $text['description']."\n";
	echo "<br /><br />\n";

	$sql = "select * from v_recordings ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	if (strlen($order_by)> 0) { $sql .= "order by ".$order_by." ".$order." "; }
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$num_rows = count($result);
	unset ($prep_statement, $result, $sql);

	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&order_by=".$order_by."&order=".$order;
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

	$sql = "select * from v_recordings ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$sql .= "order by ".$order_by." ".$order." ";
	$sql .= "limit ".$rows_per_page." offset ".$offset." ";
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
	echo th_order_by('recording_name', $text['label-recording_name'], $order_by, $order);
	if ($_SESSION['recordings']['storage_type']['text'] != 'base64') {
		echo th_order_by('recording_filename', $text['label-file_name'], $order_by, $order);
		echo "<th class='listhdr' style='text-align: center;' nowrap>".$text['label-file-size']."</th>\n";
	}
	echo "<th class='listhdr' nowrap>".$text['label-tools']."</th>\n";
	echo th_order_by('recording_description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>&nbsp;</td>\n";
	echo "</tr>\n";

	//calculate colspan for progress bar
	$colspan = 5; //max
	if ($_SESSION['recordings']['storage_type']['text'] == 'base64') { $colspan = $colspan - 2; }
	if (!(permission_exists('recording_edit') || permission_exists('recording_delete'))) { $colspan = $colspan - 1; }

	if ($result_count > 0) {
		foreach($result as $row) {
			//playback progress bar
			if (permission_exists('recording_play')) {
				echo "<tr id='recording_progress_bar_".$row['recording_uuid']."' style='display: none;'><td class='".$row_style[$c]."' style='border: none; padding: 0;' colspan='".$colspan."'><span class='playback_progress_bar' id='recording_progress_".$row['recording_uuid']."'></span></td></tr>\n";
			}
			$tr_link = (permission_exists('recording_edit')) ? "href='recording_edit.php?id=".$row['recording_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['recording_name']."</td>\n";
			if ($_SESSION['recordings']['storage_type']['text'] != 'base64') {
				echo "	<td valign='top' class='".$row_style[$c]."'>".$row['recording_filename']."</td>\n";
				$file_name = $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/'.$row['recording_filename'];
				if (file_exists($file_name)) {
					$file_size = filesize($file_name);
					$file_size = byte_convert($file_size);
				}
				else {
					$file_size = '';
				}
				echo "	<td class='".$row_style[$c]."' style='text-align: center;'>".$file_size."</td>\n";
			}
			if (permission_exists('recording_play') || permission_exists('recording_download')) {
				echo "	<td valign='top' class='".$row_style[$c]." row_style_slim tr_link_void'>";
				if (permission_exists('recording_play')) {
					$recording_file_path = $row['recording_filename'];
					$recording_file_name = strtolower(pathinfo($recording_file_path, PATHINFO_BASENAME));
					$recording_file_ext = pathinfo($recording_file_name, PATHINFO_EXTENSION);
					switch ($recording_file_ext) {
						case "wav" : $recording_type = "audio/wav"; break;
						case "mp3" : $recording_type = "audio/mpeg"; break;
						case "ogg" : $recording_type = "audio/ogg"; break;
					}
					echo "<audio id='recording_audio_".$row['recording_uuid']."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$row['recording_uuid']."')\" onended=\"recording_reset('".$row['recording_uuid']."');\" src=\"".PROJECT_PATH."/app/recordings/recordings.php?a=download&type=rec&id=".$row['recording_uuid']."\" type='".$recording_type."'></audio>";
					echo "<span id='recording_button_".$row['recording_uuid']."' onclick=\"recording_play('".$row['recording_uuid']."')\" title='".$text['label-play']." / ".$text['label-pause']."'>".$v_link_label_play."</span>";
				}
				if (permission_exists('recording_download')) {
					echo "<a href=\"".PROJECT_PATH."/app/recordings/recordings.php?a=download&type=rec&t=bin&id=".$row['recording_uuid']."\" title='".$text['label-download']."'>".$v_link_label_download."</a>";
				}
				echo "	</td>\n";
			}
			echo "	<td valign='top' class='row_stylebg' width='30%'>".$row['recording_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('recording_edit')) {
				echo "<a href='recording_edit.php?id=".$row['recording_uuid']."' alt='edit'>$v_link_label_edit</a>";
			}
			if (permission_exists('recording_delete')) {
				echo "<a href='recording_delete.php?id=".$row['recording_uuid']."' alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";

			$c = ($c) ? 0 : 1;
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results
	echo "</table>\n";
	echo "<br />\n";

	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<br><br>\n";

//include the footer
	require_once "resources/footer.php";

?>