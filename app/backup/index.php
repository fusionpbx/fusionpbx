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
if (if_group("backup_download")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
//	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//download the backup
	if ($_GET['a'] == "download" && permission_exists('backup_download')) {
		//build the backup file
			$backup_path = '/tmp';
			$backup_name = 'backup.tgz';
			//system('cd /tmp;tar cvzf /tmp/backup.tgz backup);
			$i = 0;
			if (count($_SESSION['backup']['path']) > 0) {
				$cmd = 'tar --create --verbose --gzip --file '.$backup_path.'/'.$backup_name.' --directory ';
				foreach ($_SESSION['backup']['path'] as $value) {
					$cmd .= $value.' ';
					$i++;
				}
				//echo $cmd;
				system($cmd);
			}

		//download the file
			session_cache_limiter('public');
			if (file_exists($_SESSION['switch']['recordings']['dir'].'/'.base64_decode($_GET['filename']))) {
				$fd = fopen($_SESSION['switch']['recordings']['dir'].'/'.base64_decode($_GET['filename']), "rb");
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				//header("Content-Transfer-Encoding: binary");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
				header('Content-Disposition: attachment; filename=backup.tgz');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header("Content-Length: " . filesize($_SESSION['switch']['recordings']['dir'].'/'.base64_decode($_GET['filename'])));
				header("Pragma: no-cache");
				header("Expires: 0");
				ob_clean();
				fpassthru($fd);
			}
	}
	exit;
	}

//upload the backup
	if (permission_exists('backup_upload')) {
		if (($_POST['submit'] == "Upload") && is_uploaded_file($_FILES['ulfile']['tmp_name']) && permission_exists('recording_upload')) {
			//upload the file
				move_uploaded_file($_FILES['ulfile']['tmp_name'], $_SESSION['switch']['recordings']['dir'].'/'.$_FILES['ulfile']['name']);
				$savemsg = $text['message-uploaded']." ".$_SESSION['switch']['recordings']['dir']."/". htmlentities($_FILES['ulfile']['name']);
				//system('chmod -R 744 '.$_SESSION['switch']['recordings']['dir'].'*');
				unset($_POST['txtCommand']);
			//restore the backup
				system('tar -xvpzf /tmp/backup.tgz -C /tmp');
		}
	}

//add the header
	require_once "resources/header.php";

//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
	echo "<tr>\n";
	echo "	<th colspan='2' align='left'>Backup</th>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
	echo "	<a href='".PROJECT_PATH."/app/backup/index.php?a=download'>download</a>	\n";
	echo "	</td>\n";
	echo "	<td class=\"row_style1\">\n";
	echo "	<br />\n";
	echo "To backup your application click on the download link and then choose  \n";
	echo "a safe location on your computer to save the file. You may want to \n";
	echo "save the backup to more than one computer to prevent the backup from being lost. \n";
	echo "	<br />\n";
	echo "	<br />\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<span  class=\"\" >Restore Application</span><br>\n";
	echo "<div class='borderlight' style='padding:10px;'>\n";
	//Browse to  Backup File
	echo "Click on 'Browse' then locate and select the application backup file named '.bak'.  \n";
	echo "Then click on 'Restore.' \n";
	echo "<br><br>";

	echo "<div align='center'>";
	echo "<form name='frmrestore' method='post' action='restore2.php'>";
	echo "	<table border='0' cellpadding='0' cellspacing='0'>";
	echo "	<tr>\n";
	echo "		<td class='' colspan='2' nowrap='nowrap' align='left'>\n";
	echo "          <table width='200'><tr>";
	echo "			<td><input type='file' class='frm' onChange='frmrestore.fileandpath.value = frmrestore.filename.value;' style='font-family: verdana; font-size: 11px;' name='filename'></td>";
	echo "          <td>";
	echo "			<input type='hidden' name='fileandpath' value=''>\n";
	echo "			<input type='submit' class='btn' value='Restore'>\n";
	echo "          </td>";
	echo "          </tr></table>";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";
	echo "</form>\n";
	echo "</div>";

	echo "</div>";

 //show the footer
 	require_once "resources/footer.php";

?>