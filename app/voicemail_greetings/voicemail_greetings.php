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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('voicemail_greeting_view')) {
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

//additional includes
	require_once "resources/paging.php";

//set the max php execution time
	ini_set(max_execution_time,7200);

//get the http get values and set them as php variables
	$voicemail_id = check_str($_REQUEST["id"]);
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//used to search the array to determin if an extension is assigned to the user
	function is_extension_assigned($number) {
		$result = false;
		foreach ($_SESSION['user']['extension'] as $row) {
			if ($row['user'] == $number) {
				$result = true;
			}
		}
		return $result;
	}

//allow admins, superadmins and users that are assigned to the extension to view the page
	if (if_group("superadmin") || if_group("admin")) {
		//access granted
	}
	else {
		//deny access if the user extension is not assigned
		if (!is_extension_assigned($voicemail_id)) {
			echo "access denied";
			return;
		}
	}

//set the greeting directory
	$v_greeting_dir = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$domain_uuid]['domain_name'].'/'.$voicemail_id;

//upload the recording
	if (($_POST['submit'] == $text['button-upload']) && is_uploaded_file($_FILES['file']['tmp_name']) && permission_exists('voicemail_greeting_upload')) {
		if ($_POST['type'] == 'rec') {
			for($i = 1; $i < 10; $i++){
				$tmp_greeting = 'greeting_'.$i.'.wav';
				if (!file_exists($v_greeting_dir.'/'.$tmp_greeting)) {
					$_REQUEST['greeting'] = $tmp_greeting;
					break;
				}
			}
			unset($tmp_greeting);
			if ($_REQUEST['greeting']) {
				mkdir($v_greeting_dir, 0777, true);
				move_uploaded_file($_FILES['file']['tmp_name'], $v_greeting_dir.'/'.$_REQUEST['greeting']);
				$_SESSION["message"] = $text['message-uploaded'].": ".$_REQUEST['greeting'];
			}
		}
	}

//save the selected greeting
	if ($_REQUEST['submit'] == $text['button-upload']) {
		//save the greeting_id to a variable
			$greeting_id = check_str($_REQUEST['greeting_id']);

		//set the greeting_id
			$sql = "update v_voicemails ";
			$sql .= "set greeting_id = '$greeting_id' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and voicemail_id = '$voicemail_id' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($prep_statement);
	}

//download the voicemail greeting
	if ($_GET['a'] == "download") { // && permission_exists('voicemail_greeting_download')) {
		session_cache_limiter('public');
		if ($_GET['type'] = "rec") {
			if (file_exists($v_greeting_dir.'/'.base64_decode($_GET['filename']))) {
				$fd = fopen($v_greeting_dir.'/'.base64_decode($_GET['filename']), "rb");
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
				header("Content-Length: " . filesize($v_greeting_dir.'/'.base64_decode($_GET['filename'])));
				fpassthru($fd);
			}
		}
		exit;
	}

//build a list of voicemail greetings
	$config_voicemail_greeting_list = '|';
	$i = 0;
	$sql = "select * from v_voicemail_greetings ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and voicemail_id = '$voicemail_id' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$config_greeting_list = "|";
	foreach ($result as &$row) {
		$config_greeting_list .= $row['greeting_name']."|";
	}
	unset ($prep_statement);

//add recordings to the database
	if (is_dir($v_greeting_dir.'/')) {
		if ($dh = opendir($v_greeting_dir.'/')) {
			while (($file = readdir($dh)) !== false) {
				if (filetype($v_greeting_dir."/".$file) == "file") {
					if (strpos($config_greeting_list, "|".$file) === false) {
						if (substr($file, 0, 8) == "greeting") {
							//file not found add it to the database
							$a_file = explode("\.", $file);
							$voicemail_greeting_uuid = uuid();
							$sql = "insert into v_voicemail_greetings ";
							$sql .= "(";
							$sql .= "domain_uuid, ";
							$sql .= "voicemail_greeting_uuid, ";
							$sql .= "voicemail_id, ";
							$sql .= "greeting_name, ";
							$sql .= "greeting_description ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'$domain_uuid', ";
							$sql .= "'$voicemail_greeting_uuid', ";
							$sql .= "'$voicemail_id', ";
							$sql .= "'".$a_file[0]."', ";
							$sql .= "'' ";
							$sql .= ")";
							$db->exec(check_sql($sql));
							unset($sql);
						}
					}
					else {
						//echo "The $file was found.<br/>";
					}
				}
			}
			closedir($dh);
		}
	}

//get the number of rows in v_extensions
	$sql = "select greeting_id from v_voicemails ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and voicemail_id = '$voicemail_id' ";
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$greeting_id = $row['greeting_id'];
	}
	unset($prep_statement, $result);

//include the header
	require_once "resources/header.php";

//begin the content
	echo "<script>\n";
	echo "function EvalSound(soundobj) {\n";
	echo "	var thissound= eval(\"document.\"+soundobj);\n";
	echo "	thissound.Play();\n";
	echo "}\n";
	echo "</script>";

	echo "<form action=\"\" method=\"POST\" enctype=\"multipart/form-data\" name=\"frm\" onSubmit=\"\">\n";
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left' nowrap>\n";
	echo "			<span class='title'>".$text['title']."</span><br>\n";
	echo "		</td>";

	if (permission_exists('voicemail_greeting_upload')) {
		echo "	<td align='right' nowrap>\n";
		echo "		<input type='button' class='btn' name='' alt='back' onclick=\"javascript:history.back();\" value='".$text['button-back']."'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
		echo "		<input name=\"file\" type=\"file\" class=\"formfld fileinput\" id=\"file\">\n";
		echo "		<input name=\"type\" type=\"hidden\" value=\"rec\">\n";
		echo "		<input name=\"submit\" type=\"submit\" class=\"btn\" id=\"upload\" value=\"".$text['button-upload']."\">\n";
		echo "	</td>\n";
	}

	echo "	</tr>";
	echo "	<tr>";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description']." $voicemail_id. <br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<br />\n";

	//get the number of rows in v_voicemail_greetings
		$sql = "select count(*) as num_rows from v_voicemail_greetings ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and voicemail_id = '$voicemail_id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] > 0) {
				$num_rows = $row['num_rows'];
			}
			else {
				$num_rows = '0';
			}
		}
		unset($prep_statement, $result);

	//prepare to page the results
		$rows_per_page = 100;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var_3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the greetings list
		$sql = "select * from v_voicemail_greetings ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and voicemail_id = '$voicemail_id' ";
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
	echo "<th>".$text['table-choose']."</th>\n";
	echo th_order_by('greeting_name', $text['table-name'], $order_by, $order);
	echo "<th align='right'>".$text['label-tools']."</th>\n";
	echo "<th width=\"50px\" class=\"listhdr\" nowrap=\"nowrap\">".$text['table-size']."</th>\n";
	echo th_order_by('greeting_description', $text['table-description'], $order_by, $order);
	echo "<td align='right' width='21'>\n";
	//if (permission_exists('voicemail_greeting_add')) {
	//	echo "	<a href='voicemail_greeting_edit.php?&voicemail_id=".$voicemail_id."' alt='add'>$v_link_label_add</a>\n";
	//}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tmp_filesize = filesize($v_greeting_dir.'/'.$row['greeting_name']);
			$tmp_filesize = byte_convert($tmp_filesize);
			$tr_link = (permission_exists('voicemail_greeting_edit')) ? "href='voicemail_greeting_edit.php?id=".$row['voicemail_greeting_uuid']."&voicemail_id=".$voicemail_id."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td class='".$row_style[$c]." tr_link_void' width='30px;' valign='top'>\n";
			if (preg_replace('{\D}', '', $row['greeting_name']) == $greeting_id) {
				echo "		<input type=\"radio\" name=\"greeting_id\" value=\"".preg_replace('{\D}', '', $row['greeting_name'])."\" checked=\"checked\">\n";
			}
			else {
				echo "		<input type=\"radio\" name=\"greeting_id\" value=\"".preg_replace('{\D}', '', $row['greeting_name'])."\">\n";
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['greeting_name']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";

			if (permission_exists('voicemail_greeting_download')) {
				echo "	<a href=\"voicemail_greetings.php?id=$voicemail_id&a=download&type=rec&t=bin&filename=".base64_encode($row['greeting_name'])."\">".$text['table-download']."</a>";
			}
				//echo "&nbsp;\n";
				//echo "<a href=\"javascript:void(0);\" onclick=\"window.open('voicemail_greeting_play.php?id=$voicemail_id&a=download&type=rec&filename=".base64_encode($row['greeting_name'])."', 'play',' width=420,height=40,menubar=no,status=no,toolbar=no')\">\n";
				//echo "play";
				//echo "</a>";

			echo "	</td>\n";
			echo "	<td class='".$row_style[$c]."' nowrap>".$tmp_filesize."</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['greeting_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>\n";
			if (permission_exists('voicemail_greeting_edit')) {
				echo "<a href='voicemail_greeting_edit.php?id=".$row['voicemail_greeting_uuid']."&voicemail_id=".$voicemail_id."' alt='edit'>$v_link_label_edit</a>";
			}
			if (permission_exists('voicemail_greeting_delete')) {
				echo "<a href='voicemail_greeting_delete.php?id=".$row['voicemail_greeting_uuid']."&voicemail_id=".$voicemail_id."' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>";
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
	echo "		<td width='33.3%' align='right'>&nbsp;</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";

	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>";
	echo "</div>";
	echo "<input type='hidden' name='id' value='$voicemail_id'>\n";
	echo "</form>";

	echo "<br>\n";
	echo "<br>\n";
	echo "<br>\n";
	echo "<br>\n";

//include the footer
	require_once "resources/footer.php";

?>