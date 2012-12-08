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
require "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('voicemail_view')) {
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

//download the voicemail
	if ($_GET['a'] == "download") {

		//pdo voicemail database connection
			include "includes/lib_pdo_vm.php";

		session_cache_limiter('public');

		$uuid = $_GET["uuid"];
		$sql = "select * from voicemail_msgs ";
		$sql .= "where domain = '".$_SESSION['domain_name']."' ";
		$sql .= "and uuid = '$uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$created_epoch = $row["created_epoch"];
			$read_epoch = $row["read_epoch"];
			$username = $row["username"];
			$uuid = $row["uuid"];
			$cid_name = $row["cid_name"];
			$cid_number = $row["cid_number"];
			$in_folder = $row["in_folder"];
			$file_path = $row["file_path"];
			$message_len = $row["message_len"];
			$flags = $row["flags"];
			$read_flags = $row["read_flags"];
			break; //limit to 1 row
		}
		unset ($prep_statement);

		if ($_GET['type'] = "vm") {
			if  (file_exists($file_path)) {
				$fd = fopen($file_path, "rb");
				if ($_GET['t'] == "bin") {
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: application/download");
					header("Content-Description: File Transfer");
					$file_ext = substr($file_path, -3);
					if ($file_ext == "wav") {
						header('Content-Disposition: attachment; filename="voicemail.wav"');
					}
					if ($file_ext == "mp3") {
						header('Content-Disposition: attachment; filename="voicemail.mp3"');
					}
				}
				else {
					$file_ext = substr($file_path, -3);
					if ($file_ext == "wav") {
						header("Content-Type: audio/x-wav");
					}
					if ($file_ext == "mp3") {
						header("Content-Type: audio/mp3");
					}
				}
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past
				header("Content-Length: " . filesize($file_path));
				fpassthru($fd);
			}
			return;
		}
	}

//get the includes
	require "includes/require.php";
	require_once "includes/header.php";
	require_once "includes/paging.php";

//get the http values and set them as variables
	if (isset($_GET["order_by"])) {
		$order_by = check_str($_GET["order_by"]);
		$order = check_str($_GET["order"]);
	}
	else {
		$order_by = '';
		$order = '';
	}

//pdo voicemail database connection
	include "includes/lib_pdo_vm.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='50%' nowrap><b>".$text['title']."</b></td>\n";
	echo "<td align='left' width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2' align='left'>\n";
	echo "".$text['description']." \n";
	if (if_group("admin") || if_group("superadmin")) {
		echo "".$text['description-2']." \n";
		echo "".$text['description-3']." \n";
		echo "	<br />\n";
		echo "	<br />\n";
	}
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	$tmp_msg_header = '';
	$tmp_msg_header .= "<tr>\n";
	$tmp_msg_header .= th_order_by('created_epoch', $text['label-created'], $order_by, $order);
	//$tmp_msg_header .= th_order_by('read_epoch', 'Read', $order_by, $order);
	//$tmp_msg_header .= th_order_by('username', 'Ext', $order_by, $order);
	//$tmp_msg_header .= th_order_by('domain', 'Domain', $order_by, $order);
	//$tmp_msg_header .= th_order_by('uuid', 'UUID', $order_by, $order);
	$tmp_msg_header .= th_order_by('cid_name', $text['label-caller-id-name'], $order_by, $order);
	$tmp_msg_header .= th_order_by('cid_number', $text['label-caller-id-number'], $order_by, $order);
	$tmp_msg_header .= th_order_by('in_folder', $text['label-folder'], $order_by, $order);
	//$tmp_msg_header .= "<th>Options</th>\n";
	//$tmp_msg_header .= th_order_by('file_path', 'File Path', $order_by, $order);
	$tmp_msg_header .= th_order_by('message_len', $text['label-length'], $order_by, $order);
	$tmp_msg_header .= "<th nowrap>".$text['label-size']."</th>\n";
	//$tmp_msg_header .= th_order_by('flags', 'Flags', $order_by, $order);
	//$tmp_msg_header .= th_order_by('read_flags', 'Read Flags', $order_by, $order);
	$tmp_msg_header .= "<td align='right' width='22'>\n";
	//$tmp_msg_header .= "  <input type='button' class='btn' name='' alt='add' onclick=\"window.location='voicemail_msgs_edit.php'\" value='+'>\n";
	$tmp_msg_header .= "</td>\n";
	$tmp_msg_header .= "<tr>\n";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='2' cellspacing='0'>\n";
	if (!isset($_SESSION['user']['extension'])) {
		echo $tmp_msg_header;
	}
	else {
		foreach ($_SESSION['user']['extension'] as $value) {
			if (strlen($value['user']) > 0) {
				echo "<tr><td colspan='5' align='left'>\n";
				echo "	<br />\n";
				echo "	<b>".$text['table-mailbox'].": ".$value['user']."</b>&nbsp;\n";
				echo "	\n";
				echo "</td>\n";
				echo "<td valign='bottom' align='right'>\n";
				echo "	<input type='button' class='btn' name='' alt='greetings' onclick=\"window.location='".PROJECT_PATH."/app/voicemail_greetings/voicemail_greetings.php?id=".$value['user']."'\" value='".$text['button-greetings']."'>\n";
				echo "	<input type='button' class='btn' name='' alt='settings' onclick=\"window.location='voicemail_msgs_password.php?id=".$value['extension_uuid']."'\" value='".$text['button-settings']."'>\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo $tmp_msg_header;

				$sql = "select * from voicemail_msgs ";
				if (count($_SESSION['domains']) > 1) {
					$sql .= "where domain = '".$_SESSION['domain_name']."' ";
					$sql .= "and username = '".$value['user']."' ";
				}
				else {
					$sql .= "where username = '".$value['user']."' ";
				}
				if (isset($order_by)) {
					if (strlen($order_by) > 0) {
						$sql .= "order by $order_by $order ";
					}
				}
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$result_count = count($result);
				unset ($prep_statement, $sql);

				$c = 0;
				$row_style["0"] = "row_style0";
				$row_style["1"] = "row_style1";

				if ($result_count > 0) {
					$prevextension = '';
					foreach($result as $row) {
						$extension_uuid = '';
						foreach($_SESSION['user']['extension'] as $value) {
							if ($value['user'] == $row['username']) {
								$extension_uuid = $value['extension_uuid'];
								break;
							}
						}

						$tmp_filesize = filesize($row['file_path']);
						$tmp_filesize = byte_convert($tmp_filesize);
						$file_ext = substr($row['file_path'], -3);

						$tmp_message_len = $row['message_len'];
						if ($tmp_message_len < 60 ) {
							$tmp_message_len = $tmp_message_len. " sec";
						}
						else {
							$tmp_message_len = round(($tmp_message_len/60), 2). " min";
						}

						if ($row['read_epoch'] == 0) {
							$style = "style=\"font-weight: bold;\"";
						}
						else {
							$style = "";
						}

						echo "<tr >\n";
						echo "   <td valign='top' class='".$row_style[$c]."' $style nowrap=\"nowrap\">";
						echo date("j M Y g:i a",$row['created_epoch']);
						echo "</td>\n";
						//echo "   <td valign='top' class='".$row_style[$c]."'>".$row['read_epoch']."</td>\n";
						//echo "   <td valign='top' class='".$row_style[$c]."'>".$row['username']."</td>\n";
						//echo "   <td valign='top' class='".$row_style[$c]."'>".$row['domain']."</td>\n";
						//echo "   <td valign='top' class='".$row_style[$c]."'>".$row['uuid']."</td>\n";
						echo "   <td valign='top' class='".$row_style[$c]."' $style nowrap=\"nowrap\">".$row['cid_name']."</td>\n";
						echo "   <td valign='top' class='".$row_style[$c]."' $style>".$row['cid_number']."</td>\n";
						echo "   <td valign='top' class='".$row_style[$c]."' $style>".$row['in_folder']."</td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."' $style>\n";
						echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('voicemail_msgs_play.php?a=download&type=vm&uuid=".$row['uuid']."&id=".$row['username']."&ext=".$file_ext."&desc=".urlencode($row['cid_name']." ".$row['cid_number'])."', 'play',' width=420,height=40,menubar=no,status=no,toolbar=no')\">\n";
						echo "		$tmp_message_len";
						echo "		</a>";
						echo "	</td>\n";
						//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[flags]."&nbsp;</td>\n";
						//echo "	<td valign='top' class='".$row_style[$c]."'>".$row[read_flags]."</td>\n";
						echo "	<td valign='top' class='".$row_style[$c]."'  $style nowrap=\"nowrap\">";
						echo "		<a href=\"voicemail_msgs.php?a=download&type=vm&t=bin&uuid=".$row['uuid']."\">\n";
						echo $tmp_filesize;
						echo "		</a>";
						echo 	"</td>\n";
						echo "   <td valign='top' align='center' nowrap>\n";
						//echo "		<a href='voicemail_msgs_edit.php?id=".$row[voicemail_msg_id]."' alt='edit'>$v_link_label_edit</a>\n";
						echo "			&nbsp;&nbsp;<a href='voicemail_msgs_delete.php?uuid=".$row['uuid']."&id=".$row['username']."' alt='delete message' title='delete message' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
						echo "   </td>\n";
						echo "</tr>\n";

						$prevextension = $row['username'];
						unset($tmp_message_len, $tmp_filesize);
						if ($c==0) { $c=1; } else { $c=0; }
					} //end foreach
					unset($sql, $result, $row_count);
				} //end if results
			}
		}
	}

	echo "</table>";
	echo "</div>";
	echo "<br><br>";
	echo "<br><br>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br><br>";

//show the footer
	require "includes/require.php";
	require_once "includes/footer.php";

?>