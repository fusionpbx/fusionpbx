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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('recordings_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
require_once "includes/paging.php";

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
	if ($_GET['a'] == "download" && permission_exists('recordings_download')) {
		session_cache_limiter('public');
		if ($_GET['type'] = "rec") {
			if (file_exists($_SESSION['switch']['recordings']['dir'].'/'.base64_decode($_GET['filename']))) {
				$fd = fopen($_SESSION['switch']['recordings']['dir'].'/'.base64_decode($_GET['filename']), "rb");
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
				header("Content-Length: " . filesize($_SESSION['switch']['recordings']['dir'].'/'.base64_decode($_GET['filename'])));
				ob_clean();
				fpassthru($fd);
			}
		}
		exit;
	}

//upload the recording
	if (permission_exists('recordings_upload')) {
		if (($_POST['submit'] == "Upload") && is_uploaded_file($_FILES['ulfile']['tmp_name']) && permission_exists('recordings_upload')) {
			if ($_POST['type'] == 'rec') {
				move_uploaded_file($_FILES['ulfile']['tmp_name'], $_SESSION['switch']['recordings']['dir'].'/'.$_FILES['ulfile']['name']);
				$savemsg = $text['message-uploaded']." ".$_SESSION['switch']['recordings']['dir']."/". htmlentities($_FILES['ulfile']['name']);
				//system('chmod -R 744 '.$_SESSION['switch']['recordings']['dir'].'*');
				unset($_POST['txtCommand']);
			}
		}
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
						$sql .= "'auto' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
					}
				}
			}
			closedir($dh);
		}
	}

//include the header
	require_once "includes/header.php";

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

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "    <td align='left'><p><span class=\"vexpl\"><span class=\"red\"><strong>".$text['title'].":<br>\n";
	echo "        </strong></span>\n";
	echo $text['description']."\n";
	echo "        </span></p></td>\n";
	echo "  </tr>\n";
	echo "</table>";

	echo "<br />\n";

	echo "	<table border='0' width='100%'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' width='50%'>\n";
	if ($v_path_show) {
		echo "<b>location:</b> \n";
		echo $_SESSION['switch']['recordings']['dir'];
	}
	echo "		</td>\n";
	if (permission_exists('recordings_upload')) {
		echo "<form action=\"\" method=\"POST\" enctype=\"multipart/form-data\" name=\"frmUpload\" onSubmit=\"\">\n";
		echo "		<td valign=\"top\" class=\"label\">\n";
		echo "			<input name=\"type\" type=\"hidden\" value=\"rec\">\n";
		echo "		</td>\n";
		echo "		<td valign=\"top\" align='right' class=\"label\" nowrap>\n";
		echo "			".$text['label-upload']."\n";
		echo "			<input name=\"ulfile\" type=\"file\" class=\"btn\" id=\"ulfile\">\n";
		echo "			<input name=\"submit\" type=\"submit\"  class=\"btn\" id=\"upload\" value=\"".$text['button-upload']."\">\n";
		echo "		</td>\n";
		echo "</form>";
	}
	echo "	</tr>\n";
	echo "	</table>\n";

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

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('recording_filename', $text['label-file'], $order_by, $order);
	echo th_order_by('recording_name', $text['label-recording'], $order_by, $order);
	echo "<th width=\"10%\" class=\"listhdr\" nowrap>Size</th>\n";
	echo th_order_by('recording_description', $text['label-description'], $order_by, $order);
	echo "<td align='right' width='42'>\n";
	if (permission_exists('recordings_add')) {
		echo "	<a href='recordings_edit.php' alt='add'>$v_link_label_add</a>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tmp_filesize = filesize($_SESSION['switch']['recordings']['dir'].'/'.$row['recording_filename']);
			$tmp_filesize = byte_convert($tmp_filesize);

			echo "<tr >\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo "		<a href=\"recordings.php?a=download&type=rec&t=bin&filename=".base64_encode($row['recording_filename'])."\">\n";
			echo $row['recording_filename'];
			echo "	  </a>";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo "	  <a href=\"javascript:void(0);\" onclick=\"window.open('recordings_play.php?a=download&type=moh&filename=".base64_encode($row['recording_filename'])."', 'play',' width=420,height=40,menubar=no,status=no,toolbar=no')\">\n";
			echo $row['recording_name'];
			echo "	  </a>";
			echo 	"</td>\n";
			echo "	<td class='".$row_style[$c]."' ondblclick=\"\">\n";
			echo "	".$tmp_filesize;
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' width='30%'>".$row['recording_description']."</td>\n";
			echo "	<td valign='top' align='right'>\n";
			if (permission_exists('recordings_edit')) {
				echo "		<a href='recordings_edit.php?id=".$row['recording_uuid']."' alt='edit'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('recordings_delete')) {
				echo "		<a href='recordings_delete.php?id=".$row['recording_uuid']."' alt='delete' onclick=\"return confirm('".$text['message-delete']."')\">$v_link_label_delete</a>\n";
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
	echo "		<td width='33.3%' align='right'>\n";
	if (permission_exists('recordings_add')) {
		echo "			<a href='recordings_edit.php' alt='add'>$v_link_label_add</a>\n";
	}
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
	require_once "includes/footer.php";

?>