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
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//download the backup
	if ($_GET['a'] == "backup" && permission_exists('backup_download')) {
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
				foreach ($_SESSION['backup']['path'] as $value) {
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

//show the content
	echo "<div align='center'>";

	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-backup']."</b></td>\n";
	echo "		<td width='50%' align='right'></td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>".$text['description-backup']."</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br><br>";

	echo "<div align='center'>";
	echo "<table>";
	echo "	<tr>";
	echo "		<td>".$text['label-file_format']."&nbsp;</td>";
	echo "		<td>";
	echo "			<select class='formfld' name='file_format' id='file_format'>";
	echo "				<option value='tgz' ".(($file_format == 'tgz') ? 'selected' : null).">TAR GZIP</option>";
	echo "				<option value='tbz' ".(($file_format == 'tbz') ? 'selected' : null).">TAR BZIP</option>";
	echo "				<option value='rar' ".(($file_format == 'rar') ? 'selected' : null).">RAR</option>";
	echo "				<option value='zip' ".(($file_format == 'zip') ? 'selected' : null).">ZIP</option>";
	echo "			</select>";
	echo "		</td>";
	echo "		<td><input type='button' class='btn' value='".$text['button-backup']."' onclick=\"document.location.href='".PROJECT_PATH."/app/backup/index.php?a=backup&file_format='+document.getElementById('file_format').options[document.getElementById('file_format').selectedIndex].value;\"></td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br><br>";

	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-restore']."</b></td>\n";
	echo "		<td width='50%' align='right'></td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>".$text['description-restore']."</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br><br>";

	echo "<div align='center'>";
	echo "<form name='frmrestore' method='post' enctype='multipart/form-data' action=''>";
	echo "<input type='hidden' name='a' value='restore'>";
	echo "<table>";
	echo "	<tr>";
	echo "		<td>".$text['label-select_backup']."&nbsp;</td>";
	echo "		<td><input type='file' class='formfld fileinput' name='backup_file'></td>";
	echo "		<td><input type='submit' class='btn' value='".$text['button-restore']."'></td>";
	echo "	</tr>";
	echo "</table>";
	echo "<br>";
	echo "<span style='font-weight: bold; text-decoration: underline; color: #000;'>".$text['description-restore_warning']."</span>";
	echo "</form>\n";
	echo "</div>";
	echo "<br><br><br>";

	echo "</div>";

 //show the footer
 	require_once "resources/footer.php";

?>