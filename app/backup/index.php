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
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists("backup_download")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//download the backup
	if ($_GET['a'] == "download" && permission_exists('backup_download')) {
		$file_format = $_GET['file_format'];
		$file_format = ($file_format != '') ? $file_format : 'tgz';

		//build the backup file
			$backup_path = ($_SESSION['server']['backup']['path'] != '') ? $_SESSION['server']['backup']['path'] : '/tmp';
			$backup_file = 'backup_'.date('Ymd_His').'.'.$file_format;
			if (count($_SESSION['backup']['path']) > 0) {
				//determine compression method
				switch ($file_format) {
					case "rar" : $cmd = 'rar a -ow -r '; break;
					case "zip" : $cmd = 'zip -r '; break;
					case "tbz" : $cmd = 'tar -jvcf '; break;
					default : $cmd = 'tar -zvcf ';
				}
				$cmd .= $backup_path.'/'.$backup_file.' ';
				if (isset($_SESSION['backup']['path'])) foreach ($_SESSION['backup']['path'] as $value) {
					$cmd .= $value.' ';
				}
				exec($cmd);

			//download the file
				session_cache_limiter('public');
				if (file_exists($backup_path."/".$backup_file)) {
					$fd = fopen($backup_path."/".$backup_file, 'rb');
					header("Content-Type: application/octet-stream");
					header("Content-Transfer-Encoding: binary");
					header("Content-Description: File Transfer");
					header('Content-Disposition: attachment; filename='.$backup_file);
					header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
					header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
					header("Content-Length: ".filesize($backup_path."/".$backup_file));
					header("Pragma: no-cache");
					header("Expires: 0");
					ob_clean();
					fpassthru($fd);
					exit;
				}
				else {
					//set response message
					$_SESSION["message"] = $text['message-backup_failed_format'];
					header("Location: ".$_SERVER['PHP_SELF']);
					exit;
				}
			}
			else {
				//set response message
				$_SESSION["message"] = $text['message-backup_failed_paths'];
				header("Location: ".$_SERVER['PHP_SELF']);
				exit;
			}
	}

//script a backup (cron)
	if ($_GET['a'] == "script" && permission_exists('backup_download')) {
		$file_format = $_GET['file_format'];
		$target_type = "script";

		$backup = new backup;
		$command = $backup->command("backup", $file_format);
	}

//restore a backup
	if ($_POST['a'] == "restore" && permission_exists('backup_upload')) {

		$backup_path = ($_SESSION['server']['backup']['path'] != '') ? $_SESSION['server']['backup']['path'] : '/tmp';
		$backup_file = $_FILES['backup_file']['name'];

		if (is_uploaded_file($_FILES['backup_file']['tmp_name'])) {
			//move temp file to backup path
			move_uploaded_file($_FILES['backup_file']['tmp_name'], $backup_path.'/'.$backup_file);
			//determine file format and restore backup
			$file_format = pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION);
			$valid_format = true;
			switch ($file_format) {
				case "rar" : $cmd = 'rar x -ow -o+ '.$backup_path.'/'.$backup_file.' /'; break;
				case "zip" : $cmd = 'umask 755; unzip -o -qq -X -K '.$backup_path.'/'.$backup_file.' -d /'; break;
				case "tbz" : $cmd = 'tar -xvpjf '.$backup_path.'/'.$backup_file.' -C /'; break;
				case "tgz" : $cmd = 'tar -xvpzf '.$backup_path.'/'.$backup_file.' -C /'; break;
				default: $valid_format = false;
			}
			if (!$valid_format) {
				@unlink($backup_path.'/'.$backup_file);
				$_SESSION["message"] = $text['message-restore_failed_format'];
				header("Location: ".$_SERVER['PHP_SELF']);
				exit;
			}
			else {
				exec($cmd);
				//set response message
				$_SESSION["message"] = $text['message-restore_completed'];
				header("Location: ".$_SERVER['PHP_SELF']);
				exit;
			}
		}
		else {
			//set response message
			$_SESSION["message"] = $text['message-restore_failed_upload'];
			header("Location: ".$_SERVER['PHP_SELF']);
			exit;
		}
	}

//add the header
	require_once "resources/header.php";
	$document['title'] = $text['title-destinations'];

// backup type switch javascript
	echo "<script language='javascript' type='text/javascript'>";
	echo "	var fade_speed = 400;";
	echo "	function toggle_target(first_elem, second_elem) {";
	echo "		$('#command').fadeOut(fade_speed);";
	echo "		$('#'+first_elem).fadeToggle(fade_speed, function() {";
	echo "			$('#command').slideUp(fade_speed, function() {";
	echo "				$('#'+second_elem).fadeToggle(fade_speed);";
	echo "			});";
	echo "		});";
	echo "	}";
	echo "</script>";

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' valign='top'>\n";

	echo "<b>".$text['header-backup']."</b>\n";
	echo "<br><br>";
	echo $text['description-backup']."\n";
	echo "<br><br><br>";
	echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-source_paths']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	if (isset($_SESSION['backup']['path'])) foreach ($_SESSION['backup']['path'] as $backup_path) {
		echo $backup_path."<br>\n";
	}
	echo "</td>";
	echo "</tr>";
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-file_format']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='file_format' id='file_format'>";
	echo "		<option value='tgz' ".(($file_format == 'tgz') ? 'selected' : null).">TAR GZIP</option>";
	echo "		<option value='tbz' ".(($file_format == 'tbz') ? 'selected' : null).">TAR BZIP</option>";
	echo "		<option value='rar' ".(($file_format == 'rar') ? 'selected' : null).">RAR</option>";
	echo "		<option value='zip' ".(($file_format == 'zip') ? 'selected' : null).">ZIP</option>";
	echo "	</select>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-target_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='target_type' id='target_type' onchange=\"(this.selectedIndex == 0) ? toggle_target('btn_script','btn_download') : toggle_target('btn_download','btn_script');\">";
	echo "		<option value='download'>".$text['option-file_download']."</option>";
	echo "		<option value='script' ".(($target_type == 'script') ? 'selected' : null).">".$text['option-command']."</option>";
	echo "	</select>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<div id='command' ".(($command == '') ? "style='display: none;'" : null).">";
	echo "<table border='0' cellpadding='0' cellspacing='0' width='100%'>\n";
	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-command']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<textarea class='formfld' style='width: 100%; height: 200px; font-family: courier;'>".$command."</textarea>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br>";
	echo "<div align='right'>";
	echo "<input type='button' id='btn_script' class='btn' ".(($target_type != 'script') ? "style='display: none;'" : null)." value='".$text['button-generate']."' onclick=\"document.location.href='".PROJECT_PATH."/app/backup/index.php?a=script&file_format='+document.getElementById('file_format').options[document.getElementById('file_format').selectedIndex].value;\">";
	echo "<input type='button' id='btn_download' class='btn' ".(($target_type == 'script') ? "style='display: none;'" : null)." value='".$text['button-download']."' onclick=\"document.location.href='".PROJECT_PATH."/app/backup/index.php?a=download&file_format='+document.getElementById('file_format').options[document.getElementById('file_format').selectedIndex].value;\">";
	echo "</div>";
	echo "<br><br>";

	if (permission_exists("backup_upload")) {
		echo "		</td>\n";
		echo "		<td width='20'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
		echo "		<td width='50%' valign='top'>\n";
		echo "			<b>".$text['header-restore']."</b>\n";
		echo "			<br><br>";
		echo $text['description-restore']."\n";
		echo "			<br><br><br>";
		echo "			<div align='center'>";
		echo "				<form name='frmrestore' method='post' enctype='multipart/form-data' action=''>";
		echo "				<input type='hidden' name='a' value='restore'>";
		echo "				<table>";
		echo "				<tr>";
		echo "					<td nowrap>".$text['label-select_backup']."&nbsp;</td>";
		echo "					<td><input type='file' class='formfld fileinput' name='backup_file'></td>";
		echo "					<td><input type='submit' class='btn' value='".$text['button-restore']."'></td>";
		echo "				</tr>";
		echo "				</table>";
		echo "				<br>";
		echo "				<span style='font-weight: bold; text-decoration: underline; color: #000;'>".$text['description-restore_warning']."</span>";
		echo "				</form>\n";
		echo "			</div>";
		echo "		</td>\n";
	}

	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br><br>";

 //show the footer
 	require_once "resources/footer.php";

?>