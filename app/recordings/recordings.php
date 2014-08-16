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
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//set the max php execution time
	ini_set(max_execution_time,7200);

//get the http get values and set them as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//download the recordings
	if ($_GET['a'] == "download" && permission_exists('recording_download')) {
		session_cache_limiter('public');
		if ($_GET['type'] = "rec") {
			if (file_exists($_SESSION['switch']['recordings']['dir'].'/'.base64_decode($_GET['filename']))) {
				$fd = fopen($_SESSION['switch']['recordings']['dir'].'/'.base64_decode($_GET['filename']), "rb");
				if ($_GET['t'] == "bin") {
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					header("Content-Description: File Transfer");
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
				header('Content-Disposition: attachment; filename="'.base64_decode($_GET['filename']).'"');
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header("Content-Length: " . filesize($_SESSION['switch']['recordings']['dir'].'/'.base64_decode($_GET['filename'])));
				ob_clean();
				fpassthru($fd);
			}
		}
		exit;
	}

//upload the recording
	if (permission_exists('recording_upload')) {
		if (($_POST['submit'] == "Upload") && is_uploaded_file($_FILES['ulfile']['tmp_name']) && permission_exists('recording_upload')) {
			if ($_POST['type'] == 'rec') {
				move_uploaded_file($_FILES['ulfile']['tmp_name'], $_SESSION['switch']['recordings']['dir'].'/'.$_FILES['ulfile']['name']);
				$savemsg = $text['message-uploaded']." ".$_SESSION['switch']['recordings']['dir']."/". htmlentities($_FILES['ulfile']['name']);
				//system('chmod -R 744 '.$_SESSION['switch']['recordings']['dir'].'*');
				unset($_POST['txtCommand']);
			}
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

//build a list of recordings
	$config_recording_list = '|';
	$i = 0;
	$sql = "select * from v_recordings ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$config_recording_list .= $row['recording_filename']."|";
	}
	unset ($prep_statement);

//add recordings to the database
	if (is_dir($_SESSION['switch']['recordings']['dir'].'/')) {
		if ($dh = opendir($_SESSION['switch']['recordings']['dir'].'/')) {
			while (($file = readdir($dh)) !== false) {
				if (filetype($_SESSION['switch']['recordings']['dir']."/".$file) == "file") {
					if (strpos($config_recording_list, "|".$file) === false) {
						//echo "The $file was not found<br/>";
						//file not found add it to the database
						$a_file = explode("\.", $file);
						$recording_uuid = uuid();
						$sql = "insert into v_recordings ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "recording_uuid, ";
						$sql .= "recording_filename, ";
						$sql .= "recording_name, ";
						$sql .= "recording_description ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'$domain_uuid', ";
						$sql .= "'$recording_uuid', ";
						$sql .= "'$file', ";
						$sql .= "'".$a_file[0]."', ";
						$sql .= "'' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
					}
				}
			}
			closedir($dh);
		}
	}

//add paging
	require_once "resources/paging.php";

//include the header
	require_once "resources/header.php";

//begin the content
	echo "<script>\n";
	echo "function EvalSound(soundobj) {\n";
	echo "  var thissound= eval(\"document.\"+soundobj);\n";
	echo "  thissound.Play();\n";
	echo "}\n";
	echo "</script>";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "      <br>";

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left'>\n";
	echo "			<span class=\"title\"><strong>".$text['title']."</strong></span><br />\n";
	echo "			".stripslashes($text['description'])."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>";

	echo "<br />\n";

	echo "	<table border='0' width='100%' cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left' width='50%'>\n";
	echo "			&nbsp;";
	echo "		</td>\n";
	if (permission_exists('recording_upload')) {
		echo "		<td valign=\"top\" align='right' class=\"label\" nowrap>\n";
		echo "			<form action=\"\" method=\"POST\" enctype=\"multipart/form-data\" name=\"frmUpload\" onSubmit=\"\">\n";
		echo "			<input name=\"type\" type=\"hidden\" value=\"rec\">\n";
		echo "			".$text['label-upload']."\n";
		echo "			<input name=\"ulfile\" type=\"file\" class=\"formfld fileinput\" style=\"width: 260px;\" id=\"ulfile\">\n";
		echo "			<input name=\"submit\" type=\"submit\"  class=\"btn\" id=\"upload\" value=\"".$text['button-upload']."\">\n";
		echo "			</form>";
		echo "		</td>\n";
	}
	echo "	</tr>\n";
	echo "	</table><br><br>\n";

	$sql = "select * from v_recordings ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$num_rows = count($result);
	unset ($prep_statement, $result, $sql);

	$rows_per_page = 100;
	$param = "";
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

	$sql = "select * from v_recordings ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
	$sql .= " limit $rows_per_page offset $offset ";
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
	echo th_order_by('recording_filename', $text['label-file_name'], $order_by, $order);
	echo "<th class=\"listhdr\" nowrap>".$text['label-tools']."</th>\n";
	echo "<th class=\"listhdr\" nowrap>".$text['label-file-size']."</th>\n";
	echo th_order_by('recording_description', $text['label-description'], $order_by, $order);
	echo "<td class='list_control_icons'>&nbsp;</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tmp_filesize = filesize($_SESSION['switch']['recordings']['dir'].'/'.$row['recording_filename']);
			$tmp_filesize = byte_convert($tmp_filesize);

			$tr_link = (permission_exists('recording_edit')) ? "href='recording_edit.php?id=".$row['recording_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo 		$row['recording_name'];
			echo 	"</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo "		\n";
			echo $row['recording_filename'];
			echo "	  </a>";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void'>";
			echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('recording_play.php?a=download&type=rec&filename=".base64_encode($row['recording_filename'])."', 'play',' width=420,height=40,menubar=no,status=no,toolbar=no')\">".$text['label-play']."</a>&nbsp;&nbsp;&nbsp;";
			echo "		<a href=\"recordings.php?a=download&type=rec&t=bin&filename=".base64_encode($row['recording_filename'])."\">".$text['label-download']."</a>";
			echo "	</td>\n";
			echo "	<td class='".$row_style[$c]."'>\n";
			echo "	".$tmp_filesize;
			echo "	</td>\n";
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

			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results
	echo "</table>\n";

	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";

	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "</div>";

	echo "<br>\n";
	echo "<br>\n";
	echo "<br>\n";
	echo "<br>\n";

//include the footer
	require_once "resources/footer.php";

?>