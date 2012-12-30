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
require_once "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('voicemail_message_view')) {
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

//get the uuid
	$voicemail_uuid = check_str($_REQUEST["id"]);

//get the voicemail_id
	$sql = "select * from v_voicemails ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and voicemail_uuid = '$voicemail_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$voicemail_id = $row["voicemail_id"];
	}
	unset ($prep_statement);

//download the voicemail
	if ($_GET['a'] == "download") {

		session_cache_limiter('public');
		$voicemail_message_uuid = check_str($_GET["uuid"]);

		$sql = "select * from v_voicemail_messages ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and voicemail_message_uuid = '$voicemail_message_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$voicemail_uuid = $row["voicemail_uuid"];
			$created_epoch = $row["created_epoch"];
			$read_epoch = $row["read_epoch"];
			$caller_id_name = $row["caller_id_name"];
			$caller_id_number = $row["caller_id_number"];
			$message_length = $row["message_length"];
			$message_status = $row["message_status"];
			$message_priority = $row["message_priority"];
		}
		unset ($prep_statement);

		if ($_GET['type'] = "vm") {
			$file_path = $_SESSION['switch']['storage']['dir']."/voicemail/default/".$_SESSION['domain_name']."/".$voicemail_id."/msg_".$voicemail_message_uuid.".wav";
			if (file_exists($file_path)) {
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

//get the html values and set them as variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);
	if (strlen($_GET["id"]) > 0) {
		$voicemail_uuid = check_str($_GET["id"]);
	}

//additional includes
	require_once "includes/header.php";
	require_once "includes/paging.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br />";

	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-voicemail_messages']."</b></td>\n";
	echo "		<td width='50%' align='right'>&nbsp;</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-voicemail_message']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_voicemail_messages ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and voicemail_uuid = '$voicemail_uuid' ";
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$prep_statement = $db->prepare($sql);
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

	//prepare to page the results
		$rows_per_page = 150;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; } 
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page); 
		$offset = $rows_per_page * $page; 

	//get the list
		$sql = "select * from v_voicemail_messages ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and voicemail_uuid = '$voicemail_uuid' ";
		if (strlen($order_by) == 0) {
			$sql .= "order by created_epoch desc ";
		}
		else {
			$sql .= "order by $order_by $order ";
		}
		$sql .= "limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	//echo th_order_by('voicemail_uuid', $text['label-voicemail_uuid'], $order_by, $order);
	echo th_order_by('created_epoch', $text['label-created_epoch'], $order_by, $order);
	//echo th_order_by('read_epoch', $text['label-read_epoch'], $order_by, $order);
	echo th_order_by('caller_id_name', $text['label-caller_id_name'], $order_by, $order);
	echo th_order_by('caller_id_number', $text['label-caller_id_number'], $order_by, $order);
	echo th_order_by('message_length', $text['label-message_length'], $order_by, $order);
	echo th_order_by('message_status', $text['label-message_status'], $order_by, $order);
	echo "<th>".$text['label-message_size']."</th>\n";
	echo "<th>".$text['label-tools']."</th>\n";
	//echo th_order_by('message_priority', $text['label-message_priority'], $order_by, $order);
	echo "<td align='right' width='42'>\n";
	if (permission_exists('voicemail_message_add')) {
		echo "	<a href='voicemail_message_edit.php?voicemail_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
	}
	else {
		echo "	&nbsp;\n";
	}
	echo "</td>\n";
	echo "<tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {

			//set the greeting directory
			$file_path = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domain_name'].'/'.$voicemail_id.'/msg_'.$row['voicemail_message_uuid'].'.wav';
			$file_size = filesize($file_path);
			$file_size = byte_convert($file_size);
			$file_ext = substr($row['file_path'], -3);

			$message_length = $row['message_length'];
			if ($message_length < 60 ) {
				$message_length = $message_length. " sec";
			}
			else {
				$message_length = round(($message_length/60), 2). " min";
			}

			echo "<tr >\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['voicemail_uuid']."&nbsp;</td>\n";
			echo "<td valign='top' class='".$row_style[$c]."' $style nowrap=\"nowrap\">";
			echo "	".date("j M Y g:i a",$row['created_epoch']);
			echo "</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['read_epoch']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['caller_id_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['caller_id_number']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$message_length."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['message_status']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$file_size."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>\n";
			//echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('voicemail_msgs_play.php?a=download&type=vm&uuid=".$row['voicemail_message_uuid']."&id=".$row['voicemail_id']."&ext=".$file_ext."&desc=".urlencode($row['cid_name']." ".$row['cid_number'])."', 'play',' width=420,height=40,menubar=no,status=no,toolbar=no')\">\n";
			//echo "			".$text['label-play']."\n";
			//echo "		</a>\n";
			echo "		&nbsp;&nbsp;\n";
			echo "		<a href=\"voicemail_messages.php?a=download&type=vm&t=bin&id=".$row['voicemail_uuid']."&uuid=".$row['voicemail_message_uuid']."\">\n";
			echo "			".$text['label-download']."\n";
			echo "		</a>\n";
			echo "	</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['message_priority']."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>\n";
			if (permission_exists('voicemail_message_edit')) {
				echo "		<a href='voicemail_message_edit.php?voicemail_uuid=".$row['voicemail_uuid']."&id=".$row['voicemail_message_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>\n";
			}
			if (permission_exists('voicemail_message_delete')) {
				echo "		<a href='voicemail_message_delete.php?voicemail_uuid=".$row['voicemail_uuid']."&id=".$row['voicemail_message_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='9' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	if (permission_exists('voicemail_message_add')) {
		echo "			<a href='voicemail_message_edit.php?voicemail_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
	}
	else {
		echo "			&nbsp;\n";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//include the footer
	require_once "includes/footer.php";
?>