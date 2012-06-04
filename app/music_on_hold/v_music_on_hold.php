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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('music_on_hold_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

require_once "includes/paging.php";

$dir_music_on_hold_8000 = $_SESSION['switch']['sounds']['dir'].'/music/8000';
ini_set(max_execution_time,7200);

$order_by = $_GET["order_by"];
$order = $_GET["order"];

if ($_GET['a'] == "download") {
	session_cache_limiter('public');
	if ($_GET['type'] = "moh") {
		if (file_exists($dir_music_on_hold_8000."/".base64_decode($_GET['filename']))) {
			$fd = fopen($dir_music_on_hold_8000."/".base64_decode($_GET['filename']), "rb");
			if ($_GET['t'] == "bin") {
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
				header('Content-Disposition: attachment; filename="'.base64_decode($_GET['filename']).'"');
			}
			else {
				$file_ext = substr(base64_decode($_GET['filename']), -3);
				if ($file_ext == "wav") {
					header("Content-Type: audio/x-wav");
				}
				if ($file_ext == "mp3") {
					header("Content-Type: audio/mp3");
				}
			}
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			header("Content-Length: " . filesize($dir_music_on_hold_8000."/".base64_decode($_GET['filename'])));
			fpassthru($fd);
		}
	}
	exit;
}


if (($_POST['submit'] == "Upload") && is_uploaded_file($_FILES['ulfile']['tmp_name'])) {
	if ($_POST['type'] == 'moh' && permission_exists('music_on_hold_add')) {
		move_uploaded_file($_FILES['ulfile']['tmp_name'], $dir_music_on_hold_8000."/".$_FILES['ulfile']['name']);
		$savemsg = "Uploaded file to ".$dir_music_on_hold_8000."/".htmlentities($_FILES['ulfile']['name']);
		//system('chmod -R 744 $dir_music_on_hold_8000*');
		unset($_POST['txtCommand']);
	}
}


if ($_GET['act'] == "del" && permission_exists('music_on_hold_delete')) {
	if ($_GET['type'] == 'moh') {
		unlink($dir_music_on_hold_8000."/".base64_decode($_GET['filename']));
		header("Location: v_music_on_hold.php");
		exit;
	}
}

//include the header
	require_once "includes/header.php";

//begin the content
	echo "<script>\n";
	echo "function EvalSound(soundobj) {\n";
	echo "	var thissound= eval(\"document.\"+soundobj);\n";
	echo "	thissound.Play();\n";
	echo "}\n";
	echo "</script>";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr>\n";
	echo "<td>\n";
	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
	echo "		<tr>\n";
	echo "			<td align='left'>\n";
	echo "				<p><span class=\"vexpl\">\n";
	echo "				<strong>Music on Hold</strong><br>\n";
	echo "				Music on hold can be in WAV or MP3 format. To play an MP3 file you must have\n";
	echo "				mod_shout enabled on the 'Modules' tab. You can adjust the volume of the MP3\n";
	echo "				audio from the 'Settings' tab. For best performance upload 16bit 8khz/16khz Mono WAV files.\n";
	echo "				</span></p>\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "\n";
	echo "	<br />\n";
	echo "\n";
	if (permission_exists('music_on_hold_add')) {
		echo "		<form action=\"\" method=\"POST\" enctype=\"multipart/form-data\" name=\"frmUpload\" onSubmit=\"\">\n";
		echo "		<table width='100%' border='0'>\n";
		echo "			<tr>\n";
		echo "			<td align='left' width='50%'>";

		if ($v_path_show) {
			echo "<b>location:</b> ";
			echo $dir_music_on_hold_8000;
		}

		echo "			</td>\n";
		echo "			<td valign=\"top\" class=\"label\">\n";
		echo "				<input name=\"type\" type=\"hidden\" value=\"moh\">\n";
		echo "			</td>\n";
		echo "			<td valign=\"top\" align='right' class=\"label\" nowrap>\n";
		echo "				File to upload:\n";
		echo "				<input name=\"ulfile\" type=\"file\" class=\"button\" id=\"ulfile\">\n";
		echo "				<input name=\"submit\" type=\"submit\"  class=\"btn\" id=\"upload\" value=\"Upload\">\n";
		echo "			</td>\n";
		echo "			</tr>\n";
		echo "		</table>\n";
		echo "		</form>\n";
		echo "\n";
		echo "\n";
	}

	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<th width=\"30%\" class=\"listhdrr\">File Name (download)</th>\n";
	echo "		<th width=\"30%\" class=\"listhdrr\">Name (play)</th>\n";
	echo "		<th width=\"30%\" class=\"listhdr\">Last Modified</th>\n";
	echo "		<th width=\"10%\" class=\"listhdr\" nowrap>Size</th>\n";
	echo "		<td width='22px' class=\"\" nowrap>&nbsp;</td>\n";	
	echo "	</tr>";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	if ($handle = opendir($dir_music_on_hold_8000)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && is_file($dir_music_on_hold_8000."/".$file)) {

				$tmp_filesize = filesize($dir_music_on_hold_8000."/".$file);
				$tmp_filesize = byte_convert($tmp_filesize);

				echo "<tr>\n";
				echo "	<td class='".$row_style[$c]."' ondblclick=\"\">\n";
				echo "		<a href=\"v_music_on_hold.php?a=download&type=moh&t=bin&filename=".base64_encode($file)."\">\n";
				echo "		$file";
				echo "		</a>";
				echo "	</td>\n";
				echo "	<td class='".$row_style[$c]."' ondblclick=\"\">\n";
				echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('v_music_on_hold_play.php?a=download&type=moh&filename=".base64_encode($file)."', 'play',' width=420,height=40,menubar=no,status=no,toolbar=no')\">\n";
				$tmp_file_array = explode("\.",$file);
				echo "		".$tmp_file_array[0];
				echo "		</a>";
				echo "	</td>\n";
				echo "	<td class='".$row_style[$c]."' ondblclick=\"\">\n";
				echo 		date ("F d Y H:i:s", filemtime($dir_music_on_hold_8000."/".$file));
				echo "	</td>\n";
				echo "	<td class='".$row_style[$c]."' ondblclick=\"\">\n";
				echo "	".$tmp_filesize;
				echo "	</td>\n";
				echo "	<td valign=\"middle\" width='22' nowrap class=\"list\">\n";
				echo "		<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\">\n";
				echo "		<tr>\n";
				//echo "			<td valign=\"middle\"><a href=\"v_music_on_hold.php?id=$i\"><img src=\"/themes/".$g['theme']."/images/icons/icon_e.gif\" width=\"17\" height=\"17\" border=\"0\"></a></td>\n";
				if (permission_exists('music_on_hold_delete')) {
					echo "			<td><a href=\"v_music_on_hold.php?type=moh&act=del&filename=".base64_encode($file)."\" onclick=\"return confirm('Do you really want to delete this file?')\">$v_link_label_delete</a></td>\n";
				}
				echo "		</tr>\n";
				echo "		</table>\n";
				echo "	</td>\n";
				echo "</tr>\n";
				if ($c==0) { $c=1; } else { $c=0; }

			}
		}
		closedir($handle);
	}

	echo "	<tr>\n";
	echo "		<td class=\"list\" colspan=\"3\"></td>\n";
	echo "		<td class=\"list\"></td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";

	echo "\n";
	echo "<br>\n";
	echo "<br>\n";
	echo "<br>\n";
	echo "<br>\n";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br><br>";

//include the footer
	require_once "includes/footer.php";

?>