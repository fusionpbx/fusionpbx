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

include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('music_on_hold_view') || permission_exists('music_on_hold_default_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include paging
	require_once "resources/paging.php";

//set the music on hold directory
	if (file_exists('/var/lib/fusionpbx/sounds/music')) {
		$music_on_hold_dir = $_SESSION['switch']['sounds']['dir'].'/music/fusionpbx';
	}
	else {
		$music_on_hold_dir = $_SESSION['switch']['sounds']['dir'].'/music';
	}
	ini_set(max_execution_time,7200);

//set the order by
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//download moh file
	if ($_GET['a'] == "download") {
		$slashes = array("/", "\\");
		$_GET['category']  = str_replace($slashes, "", $_GET['category']);
		$_GET['file_name']  = str_replace($slashes, "", $_GET['file_name']);

		$category_dir = $_GET['category'];
		$sampling_rate_dir = $_GET['sampling_rate'];

		if ($category_dir != '') {
			$path_mod = $category_dir."/";
			if (count($_SESSION['domains']) > 1) {
				$path_mod = $_SESSION["domain_name"]."/".$path_mod;
			}
		}

		session_cache_limiter('public');
		if ($_GET['type'] = "moh") {
			if (file_exists($music_on_hold_dir."/".$path_mod.$sampling_rate_dir."/".base64_decode($_GET['file_name']))) {
				$fd = fopen($music_on_hold_dir."/".$path_mod.$sampling_rate_dir."/".base64_decode($_GET['file_name']), "rb");
				if ($_GET['t'] == "bin") {
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					header("Content-Description: File Transfer");
				}
				else {
					$file_ext = substr(base64_decode($_GET['file_name']), -3);
					if ($file_ext == "wav") {
						header("Content-Type: audio/x-wav");
					}
					if ($file_ext == "mp3") {
						header("Content-Type: audio/mpeg");
					}
				}
				header('Content-Disposition: attachment; filename="'.base64_decode($_GET['file_name']).'"');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header("Content-Length: " . filesize($music_on_hold_dir."/".$path_mod.$sampling_rate_dir."/".base64_decode($_GET['file_name'])));
				fpassthru($fd);
			}
		}
		exit;
	}

//upload moh file
	if (is_uploaded_file($_FILES['upload_file']['tmp_name'])) {
		$file_ext = strtolower(pathinfo($_FILES['upload_file']['name'], PATHINFO_EXTENSION));
		if ($file_ext == 'wav' || $file_ext == 'mp3') {
			if ($_POST['type'] == 'moh' && permission_exists('music_on_hold_add')) {

				//remove the slashes
					$slashes = array("/", "\\");
					$_POST['upload_category_new']  = str_replace($slashes, "", $_POST['upload_category_new']);
					$_FILES['upload_file']['name']  = str_replace($slashes, "", $_FILES['upload_file']['name']);

				//replace any spaces in the file_name with dashes
					$new_file_name = str_replace(' ', '-', $_FILES['upload_file']['name']);

				//convert sampling rate from value passed by form
					if ($file_ext == 'mp3') {
						$sampling_rate_dirs = Array(8000, 16000, 32000, 48000);
					}
					else {
						$sampling_rate_dirs[] = $_POST['upload_sampling_rate'] * 1000;
					}

				//if multi-tenant, modify directory paths
					if (count($_SESSION['domains']) > 1) {
						$path_mod = $_SESSION["domain_name"]."/";
					}

				//create new category, if necessary
					if ($_POST['upload_category'] == '_NEW_CAT_' && $_POST['upload_category_new'] != '') {
						$new_category_name = str_replace(' ', '_', $_POST['upload_category_new']);
						//process sampling rate(s)
							if (isset($sampling_rate_dirs)) foreach ($sampling_rate_dirs as $sampling_rate_dir) {
								if (!is_dir($music_on_hold_dir."/".$path_mod.$new_category_name."/".$sampling_rate_dir)) {
									@mkdir($music_on_hold_dir."/".$path_mod.$new_category_name."/".$sampling_rate_dir, 0777, true);
								}
								if (is_dir($music_on_hold_dir."/".$path_mod.$new_category_name."/".$sampling_rate_dir)) {
									copy($_FILES['upload_file']['tmp_name'], $music_on_hold_dir."/".$path_mod.$new_category_name."/".$sampling_rate_dir."/".$new_file_name);
									$target_dir = $music_on_hold_dir."/".$path_mod.$new_category_name."/".$sampling_rate_dir;
								}
							}
						//delete temp file
							@unlink($_FILES['upload_file']['tmp_name']);
					}
				//use existing category directory
					else if ($_POST['upload_category'] != '' && $_POST['upload_category'] != '_NEW_CAT_') {
						//process sampling rate(s)
							if (isset($sampling_rate_dirs)) foreach ($sampling_rate_dirs as $sampling_rate_dir) {
								if (!is_dir($music_on_hold_dir."/".$path_mod.$_POST['upload_category']."/".$sampling_rate_dir)) {
									@mkdir($music_on_hold_dir."/".$path_mod.$_POST['upload_category']."/".$sampling_rate_dir, 0777, true);
								}
								if (is_dir($music_on_hold_dir."/".$path_mod.$_POST['upload_category']."/".$sampling_rate_dir)) {
									copy($_FILES['upload_file']['tmp_name'], $music_on_hold_dir."/".$path_mod.$_POST['upload_category']."/".$sampling_rate_dir."/".$new_file_name);
									$target_dir = $music_on_hold_dir."/".$path_mod.$_POST['upload_category']."/".$sampling_rate_dir;
								}
							}
						//delete temp file
							@unlink($_FILES['upload_file']['tmp_name']);
					}
				//use default directory
					else if ($_POST['upload_category'] == '') {
						if (permission_exists('music_on_hold_default_add')) {
							//process sampling rate(s)
								if (isset($sampling_rate_dirs)) foreach ($sampling_rate_dirs as $sampling_rate_dir) {
									if (!is_dir($music_on_hold_dir."/".$sampling_rate_dir)) {
										@mkdir($music_on_hold_dir."/".$sampling_rate_dir, 0777, true);
									}
									if (is_dir($music_on_hold_dir."/".$sampling_rate_dir)) {
										copy($_FILES['upload_file']['tmp_name'], $music_on_hold_dir."/".$sampling_rate_dir."/".$new_file_name);
										$target_dir = $music_on_hold_dir."/".$sampling_rate_dir;
									}
								}
							//delete temp file
								@unlink($_FILES['upload_file']['tmp_name']);
						}
					}
					else {
						//delete temp file and exit
							@unlink($_FILES['upload_file']['tmp_name']);
							exit();
					}

				//build and save the XML
					require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
					$moh = new switch_music_on_hold;
					$moh->xml();
					$moh->save();

				//set an upload message
					$save_msg = "Uploaded file to ".$target_dir."/".htmlentities($_FILES['upload_file']['name']);
			}
		}

		$_SESSION['message'] = $text['message-upload_completed'];
		header("Location: music_on_hold.php");
		exit;
	}

//define valid sampling rates
	$sampling_rate_dirs = Array(8000, 16000, 32000, 48000);

//delete moh file
	if ($_GET['act'] == "del" && permission_exists('music_on_hold_delete')) {
		if ($_GET['type'] == 'moh') {
			//remove the slashes
				$slashes = array("/", "\\");
				$_GET['category']  = str_replace($slashes, "", $_GET['category']);
				$_GET['file_name']  = str_replace($slashes, "", $_GET['file_name']);
			//set the variables
				$sampling_rate_dir = $_GET['sampling_rate'];
				$category_dir = $_GET['category'];
			//default category
				if ($category_dir == "") {
					if (!permission_exists('music_on_hold_default_delete')) {
						echo "access denied";
						exit;
					}
				}
			//other categories
				if ($category_dir != "") {
					$path_mod = $category_dir."/";

					if (count($_SESSION['domains']) > 1) {
						$path_mod = $_SESSION["domain_name"]."/".$path_mod;
					}
				}
			//remove the directory
				unlink($music_on_hold_dir."/".$path_mod.$sampling_rate_dir."/".base64_decode($_GET['file_name']));

			//build and save the XML
				require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
				$moh = new switch_music_on_hold;
				$moh->xml();
				$moh->save();

			//redirect the browser
				header("Location: music_on_hold.php");
				exit;
		}

		if ($_GET['type'] == 'cat') {
			$category_dir = $_GET['category'];
			if (strlen($category_dir) > 0) {
				// adjus the path for multiple domains
					if (count($_SESSION['domains']) > 1) {
						$path_mod = $_SESSION["domain_name"]."/";
					}

				// remove sampling rate directory (if any)
					if (isset($sampling_rate_dirs)) foreach ($sampling_rate_dirs as $sampling_rate_dir) {
						rmdir($music_on_hold_dir."/".$path_mod.(base64_decode($category_dir))."/".$sampling_rate_dir);
					}

				// remove category directory
					if (rmdir($music_on_hold_dir."/".$path_mod.(base64_decode($category_dir)))) {
						sleep(5); // allow time for the OS to catch up (at least Windows, anyway)
					}
			}

			//build and save the XML
				require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
				$moh = new switch_music_on_hold;
				$moh->xml();
				$moh->save();

			//redirect the browser
				header("Location: music_on_hold.php");
				exit;
		}
	}

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-moh'];

	echo "<script language='JavaScript' type='text/javascript'>\n";
	echo "	function check_filetype(file_input) {\n";
	echo "		file_ext = file_input.value.substr((~-file_input.value.lastIndexOf('.') >>> 0) + 2);\n";
	echo "		if (file_ext != 'mp3' && file_ext != 'wav' && file_ext != '') {\n";
	echo "			display_message(\"".$text['message-unsupported_file_type']."\", 'negative', '2750');\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			if (file_ext == 'mp3') {\n";
	echo "				document.getElementById('sampling_rate').style.display='none';\n";
	echo "			}\n";
	echo "			else {\n";
	echo "				document.getElementById('sampling_rate').style.display='';\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";
	echo "<script language='JavaScript' type='text/javascript' src='".PROJECT_PATH."/resources/javascript/reset_file_input.js'></script>\n";

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left'>\n";
	echo "			<b>".$text['label-moh']."</b>";
	echo "			<br /><br />\n";
	echo "			".$text['desc-moh']."\n";
	echo "			<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>\n";

//show the upload form
	if (permission_exists('music_on_hold_add')) {
		echo "<b>".$text['label-upload-moh']."</b>\n";
		echo "<br><br>\n";
		echo "<form action='' method='POST' enctype='multipart/form-data' name='frmUpload' id='frmUpload' onSubmit=''>\n";
		echo "<input name='type' type='hidden' value='moh'>\n";
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		echo "	<tr>\n";
		echo "		<td style='padding-right: 15px; white-space: nowrap'>\n";
		echo "			".$text['label-file-path'];
		echo "			<input name='upload_file' id='upload_file' type='file' class='formfld fileinput' style='width: 300px; margin-right: 3px;' onchange=\"check_filetype(this);\">";
		echo 			"<input type='button' class='btn' value='".$text['button-clear']."' onclick=\"reset_file_input('upload_file'); document.getElementById('sampling_rate').style.display='inline';\">\n";
		echo "		</td>\n";
		echo "		<td id='sampling_rate' style='padding-right: 15px;' nowrap>";
		echo "			".$text['label-sampling'];
		echo "			<select id='upload_sampling_rate' name='upload_sampling_rate' class='formfld' style='width: auto;'>\n";
		echo "				<option value='8'>8 kHz</option>\n";
		echo "				<option value='16'>16 kHz</option>\n";
		echo "				<option value='32'>32 kHz</option>\n";
		echo "				<option value='48'>48 kHz</option>\n";
		echo "			</select>\n";
		echo "		</td>\n";
		echo "		<td nowrap>".$text['label-category']."";
		echo "			<select id='upload_category' name='upload_category' class='formfld' style='width: auto;' onchange=\"if (this.options[this.selectedIndex].value == '_NEW_CAT_') { this.style.display='none'; document.getElementById('upload_category_new').style.display=''; document.getElementById('upload_category_return').style.display=''; document.getElementById('upload_category_new').focus(); }\">\n";
		if (permission_exists('music_on_hold_default_add')) {
			echo "				<option value='' style='font-style: italic;'>".$text['opt-default']."</option>\n";
		}

		if (count($_SESSION['domains']) > 1) {
			$music_on_hold_category_parent_dir = $music_on_hold_dir."/".$_SESSION['domain_name'];
		}
		else {
			$music_on_hold_category_parent_dir = $music_on_hold_dir;
		}

		if ($handle = opendir($music_on_hold_category_parent_dir)) {
			while (false !== ($directory = readdir($handle))) {
				if (
					$directory != "." &&
					$directory != ".." &&
					$directory != "8000" &&
					$directory != "16000" &&
					$directory != "32000" &&
					$directory != "48000" &&
					is_dir($music_on_hold_category_parent_dir."/".$directory)
					) {
					echo "<option value='".$directory."'>".(str_replace('_', ' ', $directory))."</option>\n";
					$category_dirs[] = $directory; // array used to output category directory contents below
				}
			}
			closedir($handle);
		}

		echo "				<option value='_NEW_CAT_' style='font-style: italic;'>".$text['opt-new']."</option>\n";
		echo "			</select>\n";
		echo "			<input class='formfld' style='width: 150px; display: none;' type='text' name='upload_category_new' id='upload_category_new' maxlength='255' value=''>";
		echo "		</td>\n";
		echo "		<td><input id='upload_category_return' type='button' class='button' style='display: none; margin-left: 3px;' value='&#9665;' onclick=\"this.style.display='none'; document.getElementById('upload_category_new').style.display='none'; document.getElementById('upload_category_new').value=''; document.getElementById('upload_category').style.display=''; document.getElementById('upload_category').selectedIndex = 0;\" title='".$text['message-click-select']."'></td>\n";
		echo "		<td style='padding-left: 15px;'>\n";
		echo "			<input name='submit' type='submit' class='btn' id='upload' value='".$text['button-upload']."'>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "</form>\n";
		echo "<br><br>\n";
	}

//set the row styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the default category
	if (permission_exists('music_on_hold_default_view')) {
		echo "<b><i>".$text['label-default']."</i></b>\n";
		if (count($_SESSION['domains']) > 1) {
			echo "&nbsp;&nbsp;- ".$text['message-available-to-all']."\n";
		}

		echo "<br><br>\n";
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin-bottom: 3px;\">\n";
		echo "	<tr>\n";
		echo "		<th class=\"listhdr\">".$text['label-file_name']."</th>\n";
		echo "		<th class=\"listhdr\">".$text['label-tools']."</th>\n";
		echo "		<th class=\"listhdr\" style='text-align: right;'>".$text['label-uploaded']."</th>\n";
		echo "		<th class=\"listhdr\" style='text-align: right;' nowrap=\"nowrap\">".$text['label-file-size']."</th>\n";
		echo "		<th class=\"listhdr\" style='text-align: right;' nowrap=\"nowrap\">".$text['label-sampling']."</th>\n";
		echo "		<td width='22px' align=\"center\"></td>\n";
		echo "	</tr>";

		if (isset($sampling_rate_dirs)) foreach ($sampling_rate_dirs as $sampling_rate_dir) {
			$directory = $music_on_hold_dir."/".$sampling_rate_dir;
			if (file_exists($directory) && $handle = opendir($directory)) {
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != ".." && is_file($music_on_hold_dir."/".$sampling_rate_dir."/".$file)) {
						$row_uuid = uuid();

						$file_size = filesize($music_on_hold_dir."/".$sampling_rate_dir."/".$file);
						$file_size = byte_convert($file_size);

						//playback progress bar
						echo "<tr id='recording_progress_bar_".$row_uuid."' style='display: none;'><td colspan='5'><span id='recording_progress_".$row_uuid."' style='background-color: #c43e42; height:1px; display: inline-block;'></span></td></tr>\n";

						echo "<tr>\n";
						echo "	<td class='".$row_style[$c]."'>".$file."</td>\n";
						if (strlen($file) > 0) {
							echo "	<td valign='top' class='".$row_style[$c]." row_style_slim tr_link_void'>";
							$recording_file_path = $file;
							$recording_file_name = strtolower(pathinfo($recording_file_path, PATHINFO_BASENAME));
							$recording_file_ext = pathinfo($recording_file_name, PATHINFO_EXTENSION);
							switch ($recording_file_ext) {
								case "wav" : $recording_type = "audio/wav"; break;
								case "mp3" : $recording_type = "audio/mpeg"; break;
								case "ogg" : $recording_type = "audio/ogg"; break;
							}
							echo "<audio id='recording_audio_".$row_uuid."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$row_uuid."')\" onended=\"recording_reset('".$row_uuid."');\" src=\"".PROJECT_PATH."/app/music_on_hold/music_on_hold.php?a=download&sampling_rate=".$sampling_rate_dir."&type=moh&file_name=".base64_encode($recording_file_path)."\" type='".$recording_type."'></audio>";
							echo "<span id='recording_button_".$row_uuid."' onclick=\"recording_play('".$row_uuid."')\" title='".$text['label-play']." / ".$text['label-pause']."'>".$v_link_label_play."</span>";
							echo "<a href=\"".PROJECT_PATH."/app/music_on_hold/music_on_hold.php?a=download&sampling_rate=".$sampling_rate_dir."&type=moh&t=bin&file_name=".base64_encode($recording_file_path)."\" title='".$text['label-download']."'>".$v_link_label_download."</a>";
						}
						else {
							echo "	<td valign='top' class='".$row_style[$c]."'>";
							echo "&nbsp;";
						}
						echo "	</td>\n";
						echo "	<td class='".$row_style[$c]."' style='text-align: right;'>".date ("F d Y H:i:s", filemtime($music_on_hold_dir."/".$sampling_rate_dir."/".$file))."</td>\n";
						echo "	<td class='".$row_style[$c]."' style='text-align: right;'>".$file_size."</td>\n";
						echo "	<td class='".$row_style[$c]."' style='text-align: right;'>".($sampling_rate_dir / 1000)." kHz</td>\n";
						echo "	<td class='list_control_icon'>\n";
						if (permission_exists('music_on_hold_default_delete')) {
							echo "<a href=\"music_on_hold.php?type=moh&act=del&sampling_rate=".$sampling_rate_dir."&file_name=".base64_encode($file)."\" onclick=\"return confirm('Do you really want to delete this file?')\">$v_link_label_delete</a>";
						}
						echo "	</td>\n";
						echo "</tr>\n";
						$c = ($c==0) ? 1 : 0;
					}
				}
				closedir($handle);
			}
		}
		echo "</table>\n";
	}

	if ($v_path_show) {
		echo "<div style='font-size: 10px; text-align: right; margin-right: 25px;'><strong>".$text['label-location'].":</strong> ".$music_on_hold_dir."</div>\n";
	}
	echo "<br><br>\n";

//show additional categories
	if (isset($category_dirs)) foreach ($category_dirs as $category_number => $category_dir) {
		$c = 0;

		echo "<b>".(str_replace('_', ' ', $category_dir))."</b>\n";
		echo "<br><br>\n";
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin-bottom: 3px;\">\n";
		echo "	<tr>\n";
		echo "		<th class=\"listhdr\">".$text['label-file_name']."</th>\n";
		echo "		<th class=\"listhdr\">".$text['label-tools']."</th>\n";
		echo "		<th class=\"listhdr\" style='text-align: right;'>".$text['label-uploaded']."</th>\n";
		echo "		<th class=\"listhdr\" style='text-align: right;' nowrap=\"nowrap\">".$text['label-file-size']."</th>\n";
		echo "		<th class=\"listhdr\" style='text-align: right;' nowrap=\"nowrap\">".$text['label-sampling']."</th>\n";
		echo "		<td width='22px' align=\"center\" style=\"padding: 2px;\"><span id='category_".$category_number."_delete_icon'></span></td>\n";
		echo "	</tr>";

		$moh_found = false;

		if (isset($sampling_rate_dirs)) foreach ($sampling_rate_dirs as $sampling_rate_dir) {
			$directory = $music_on_hold_category_parent_dir."/".$category_dir."/".$sampling_rate_dir;
			if (file_exists($directory) && $handle = opendir($directory)) {
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != ".." && is_file($directory."/".$file)) {
						$row_uuid = uuid();

						$file_size = filesize($directory."/".$file);
						$file_size = byte_convert($file_size);

						//playback progress bar
						echo "<tr id='recording_progress_bar_".$row_uuid."' style='display: none;'><td colspan='5'><span class='playback_progress_bar' id='recording_progress_".$row_uuid."'></span></td></tr>\n";

						echo "<tr>\n";
						echo "	<td class='".$row_style[$c]."'>".$file."</td>\n";
						if (strlen($file) > 0) {
							echo "	<td valign='top' class='".$row_style[$c]." row_style_slim tr_link_void'>";
							$recording_file_path = $file;
							$recording_file_name = strtolower(pathinfo($row['recording_filename'], PATHINFO_BASENAME));
							$recording_file_ext = pathinfo($recording_file_name, PATHINFO_EXTENSION);
							switch ($recording_file_ext) {
								case "wav" : $recording_type = "audio/wav"; break;
								case "mp3" : $recording_type = "audio/mpeg"; break;
								case "ogg" : $recording_type = "audio/ogg"; break;
							}
							echo "<audio id='recording_audio_".$row_uuid."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".$row_uuid."')\" onended=\"recording_reset('".$row_uuid."');\" src=\"".PROJECT_PATH."/app/music_on_hold/music_on_hold.php?a=download&category=".$category_dir."&sampling_rate=".$sampling_rate_dir."&type=moh&file_name=".base64_encode($recording_file_path)."\" type='".$recording_type."'></audio>";
							echo "<span id='recording_button_".$row_uuid."' onclick=\"recording_play('".$row_uuid."')\" title='".$text['label-play']." / ".$text['label-pause']."'>".$v_link_label_play."</span>";
							echo "<a href=\"".PROJECT_PATH."/app/music_on_hold/music_on_hold.php?a=download&category=".$category_dir."&sampling_rate=".$sampling_rate_dir."&type=moh&t=bin&file_name=".base64_encode($recording_file_path)."\" title='".$text['label-download']."'>".$v_link_label_download."</a>";
						}
						else {
							echo "	<td valign='top' class='".$row_style[$c]."'>";
							echo "&nbsp;";
						}
						echo "	</td>\n";
						echo "	<td class='".$row_style[$c]."' style='text-align: right;'>".date ("F d Y H:i:s", filemtime($music_on_hold_category_parent_dir."/".$category_dir."/".$sampling_rate_dir."/".$file))."</td>\n";
						echo "	<td class='".$row_style[$c]."' style='text-align: right;'>".$file_size."</td>\n";
						echo "	<td class='".$row_style[$c]."' style='text-align: right;'>".($sampling_rate_dir / 1000)." kHz</td>\n";
						echo "	<td class='list_control_icon'>";
						if (permission_exists('music_on_hold_delete')) {
							echo "<a href=\"music_on_hold.php?type=moh&act=del&category=".$category_dir."&sampling_rate=".$sampling_rate_dir."&file_name=".base64_encode($file)."\" onclick=\"return confirm('".$text['message-delete']."')\">$v_link_label_delete</a>";
						}
						echo "	</td>\n";
						echo "</tr>\n";
						$c = ($c==0) ? 1 : 0;

						$moh_found = true;
					}
				}
				closedir($handle);
			}
		}

		if (!$moh_found) {
			echo "<tr>\n";
			echo "	<td colspan='5' align='left' class='".$row_style[$c]."'>\n";
			echo "		".$text['message-nofiles']."";
			echo "		<script>document.getElementById('category_".$category_number."_delete_icon').innerHTML = \"<a href='music_on_hold.php?type=cat&act=del&category=".base64_encode($category_dir)."' title='".$text['label-delete-category']."'>".$v_link_label_delete."</a>\";</script>\n";
			echo "	</td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";
		if ($v_path_show) {
			echo "<div style='font-size: 10px; text-align: right; margin-right: 25px;'><strong>".$text['label-location'].":</strong> ".$music_on_hold_category_parent_dir."/".$category_dir."</div>\n";
		}
		echo "<br><br>\n";
	}

//include the footer
	require_once "resources/footer.php";

?>