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

//get the uuid and voicemail_id
	if (strlen($_REQUEST["id"]) > 0) {
		$voicemail_uuid = check_str($_REQUEST["id"]);
		$sql = "select * from v_voicemails ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and voicemail_uuid = '$voicemail_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$voicemail_id = $row["voicemail_id"];
			$voicemail_uuid = $row["voicemail_uuid"];
		}
		unset ($prep_statement);
	}

//set the voicemail_id array
	if (strlen($_REQUEST["id"]) == 0) {
		foreach ($_SESSION['user']['extension'] as $value) {
			$voicemail_id[]['voicemail_id'] = $value['user'];
		}
	}

//download the message
	if (check_str($_REQUEST["action"]) == "download") {
		$voicemail_message_uuid = check_str($_REQUEST["uuid"]);
		require_once "resources/classes/voicemail.php";
		$voicemail = new voicemail;
		$voicemail->db = $db;
		$voicemail->domain_uuid = $_SESSION['domain_uuid'];
		$voicemail->voicemail_uuid = $voicemail_uuid;
		$voicemail->voicemail_id = $voicemail_id;
		$voicemail->voicemail_message_uuid = $voicemail_message_uuid;
		$result = $voicemail->message_download();
		unset($voicemail);
		exit;
	}

//get the html values and set them as variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

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
	echo "			".$text['description-voicemail_message']."<br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	$table_header = "<tr>\n";
	$table_header .= th_order_by('created_epoch', $text['label-created_epoch'], $order_by, $order);
	//$table_header .= th_order_by('read_epoch', $text['label-read_epoch'], $order_by, $order);
	$table_header .=  th_order_by('caller_id_name', $text['label-caller_id_name'], $order_by, $order);
	$table_header .=  th_order_by('caller_id_number', $text['label-caller_id_number'], $order_by, $order);
	$table_header .=  th_order_by('message_length', $text['label-message_length'], $order_by, $order);
	$table_header .=  "<th>".$text['label-message_size']."</th>\n";
	$table_header .=  "<th>".$text['label-tools']."</th>\n";
	//$table_header .=  th_order_by('message_priority', $text['label-message_priority'], $order_by, $order);
	$table_header .=  "<td align='right' width='21'>\n";
	$table_header .=  "	&nbsp;\n";
	$table_header .=  "</td>\n";
	$table_header .=  "<tr>\n";

	//get the voicemail messages
	require_once "app/voicemails/resources/classes/voicemail.php";
	$voicemail = new voicemail;
	$voicemail->db = $db;
	$voicemail->domain_uuid = $_SESSION['domain_uuid'];
	//$voicemail->voicemail_uuid = $voicemail_uuid;
	$voicemail->voicemail_id = $voicemail_id;
	$voicemail->order_by = $order_by;
	$voicemail->order = $order;
	$result = $voicemail->messages();
	$result_count = count($result);

	//loop throug the voicemail messages
	if ($result_count == 0) {
		echo "<tr><td colspan='5' align='left'>\n";
		echo "	<br /><br />\n";
		echo "	<b>".$text['label-mailbox'].": ".$voicemail_id."</b>&nbsp;\n";
		echo "	\n";
		echo "</td>\n";
		echo "<td valign='bottom' align='right'>\n";
		echo "	<input type='button' class='btn' name='' alt='greetings' onclick=\"window.location='".PROJECT_PATH."/app/voicemail_greetings/voicemail_greetings.php?id=".$voicemail_id."'\" value='".$text['button-greetings']."'>\n";
		echo "	<input type='button' class='btn' name='' alt='settings' onclick=\"window.location='".PROJECT_PATH."/app/voicemails/voicemail_edit.php?id=".$voicemail_uuid."'\" value='".$text['button-settings']."'>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo $table_header;
	}
	else {
		$previous_voicemail_id = '';
		foreach($result as $row) {
			if ($previous_voicemail_id != $row['voicemail_id']) {
				echo "<tr><td colspan='5' align='left'>\n";
				echo "	<br /><br />\n";
				echo "	<b>".$text['label-mailbox'].": ".$row['voicemail_id']."</b>&nbsp;\n";
				echo "	\n";
				echo "</td>\n";
				echo "<td valign='bottom' align='right'>\n";
				echo "	<input type='button' class='btn' name='' alt='greetings' onclick=\"window.location='".PROJECT_PATH."/app/voicemail_greetings/voicemail_greetings.php?id=".$row['voicemail_id']."'\" value='".$text['button-greetings']."'>\n";
				echo "	<input type='button' class='btn' name='' alt='settings' onclick=\"window.location='".PROJECT_PATH."/app/voicemails/voicemail_edit.php?id=".$row['voicemail_uuid']."'\" value='".$text['button-settings']."'>\n";
				echo "</td>\n";
				echo "</tr>\n";
				echo $table_header;
			}
			if ($row['message_status'] == '') { $style = "style=\"font-weight:bold;\""; } else { $style = ''; }
			echo "<td valign='top' class='".$row_style[$c]."' $style nowrap=\"nowrap\">";
			echo "	".$row['created_date'];
			echo "</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['read_epoch']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' $style>".$row['caller_id_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' $style>".$row['caller_id_number']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' $style>".$row['message_length_label']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."' $style>".$row['message_status']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' $style>".$row['file_size_label']."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' $style>\n";
			//echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('voicemail_msgs_play.php?action=download&type=vm&uuid=".$row['voicemail_message_uuid']."&id=".$row['voicemail_id']."&ext=".$row['file_ext']."&desc=".urlencode($row['cid_name']." ".$row['cid_number'])."', 'play',' width=420,height=40,menubar=no,status=no,toolbar=no')\">\n";
			//echo "			".$text['label-play']."\n";
			//echo "		</a>\n";
			echo "		&nbsp;&nbsp;\n";
			echo "		<a href=\"voicemail_messages.php?action=download&type=vm&t=bin&id=".$row['voicemail_uuid']."&uuid=".$row['voicemail_message_uuid']."\">\n";
			echo "			".$text['label-download']."\n";
			echo "		</a>\n";
			echo "	</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['message_priority']."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>\n";
			if (permission_exists('voicemail_message_delete')) {
				echo "		<a href='voicemail_message_delete.php?voicemail_uuid=".$row['voicemail_uuid']."&id=".$row['voicemail_message_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";

			$previous_voicemail_id = $row['voicemail_id'];
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $result_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='9' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	echo "			&nbsp;\n";
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