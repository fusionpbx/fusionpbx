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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('contact_time_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>".$text['header_contact_times']."</b></td>\n";
	echo "<td width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	//get the contact list
		$sql = "select ct.*, u.username, u.domain_uuid as user_domain_uuid ";
		$sql .= "from v_contact_times as ct, v_users as u ";
		$sql .= "where ct.user_uuid = u.user_uuid ";
		$sql .= "and ct.domain_uuid = '".$domain_uuid."' ";
		$sql .= "and ct.contact_uuid = '".$contact_uuid."' ";
		$sql .= "order by ct.time_start desc ";
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
	echo "<th id='th_filler' style='display: none; padding: 0px;'>".img_spacer('21px', '1px')."</th>\n";
	echo "<th width='20%'>".$text['label-time_user']."</th>\n";
	echo "<th width='20%'>".$text['label-time_start']."</th>\n";
	echo "<th width='20%'>".$text['label-time_duration']."</th>\n";
	echo "<th width='40%'>".$text['label-time_description']."</th>\n";
	echo "<td class='list_control_icons' nowrap>";
	echo 	img_spacer('25px', '1px');
	if (permission_exists('contact_time_add')) {
		echo "<a href='contact_time_edit.php?contact_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	else {
		echo img_spacer('25px', '1px');
	}
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<div id='div_contact_times' style='width: 100%; overflow: auto; direction: rtl; text-align: right; margin-bottom: 23px;'>";
	echo "<table id='table_contact_times' class='tr_hover' style='width: 100%; direction: ltr;' border='0' cellpadding='0' cellspacing='0'>\n";
	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('contact_time_edit') && $row['user_uuid'] == $_SESSION["user"]["user_uuid"]) ? "href='contact_time_edit.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_time_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			if ($row["time_start"] != '' && $row['time_stop'] != '') {
				$time_start = strtotime($row["time_start"]);
				$time_stop = strtotime($row['time_stop']);
				$time = gmdate("H:i:s", ($time_stop - $time_start));
			}
			else { unset($time); }
			$tmp = explode(' ', $row['time_start']);
			$time_start = $tmp[0];
			echo "	<td valign='top' class='".$row_style[$c]."' width='20%'><span ".(($row['user_domain_uuid'] != $domain_uuid) ? "title='".$_SESSION['domains'][$row['user_domain_uuid']]['domain_name']."' style='cursor: help;'" : null).">".$row["username"]."</span>&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' width='20%'>".$time_start."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."' width='20%'>".$time."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg' style='width: 40%; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>".$row['time_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons' nowrap>";
			if (permission_exists('contact_time_edit')) {
				if ($row['user_uuid'] == $_SESSION["user"]["user_uuid"]) {
					echo "<a href='contact_time_edit.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_time_uuid']."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
				}
				else {
					echo "<span onclick=\"alert('".$text['message-access_denied']."');\" alt='".$text['button-edit']."'>".str_replace("list_control_icon", "list_control_icon_disabled", $v_link_label_edit)."</span>";
				}
			}
			if (permission_exists('contact_time_delete')) {
				if ($row['user_uuid'] == $_SESSION["user"]["user_uuid"]) {
					echo "<a href='contact_time_delete.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_time_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
				}
				else {
					echo "<span onclick=\"alert('".$text['message-access_denied']."');\" alt='".$text['button-delete']."'>".str_replace("list_control_icon", "list_control_icon_disabled", $v_link_label_delete)."</span>";
				}
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results
	echo "</table>";
	echo "</div>\n";

	echo "<script>";
	echo "	var div_times = document.getElementById('div_contact_times');";
	echo "	var table_times = document.getElementById('table_contact_times');";
	echo "	var th_filler = document.getElementById('th_filler');";

	echo "	if (div_times.offsetHeight > 200) { ";
	echo "		div_times.style.height = 200; ";
	echo "	}";
	echo "	else {";
	echo "		div_times.style.height = div_times.scrollHeight + 1; ";
	echo "	}";

	echo "	if (div_times.scrollHeight > div_times.clientHeight) {";
	echo "		th_filler.style.display = ''; ";
	echo "		table_times.style.paddingLeft = 1;";
	echo "	}";
	echo "	else {";
	echo "		th_filler.style.display = 'none'; ";
	echo "		table_times.style.paddingLeft = 0;";
	echo "	}";
	echo "</script>\n";

?>