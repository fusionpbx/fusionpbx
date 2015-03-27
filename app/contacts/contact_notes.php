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
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('contact_note_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>".$text['label-contact_notes']."</b></td>\n";
	echo "<td width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	//get the contact list
		$sql = "select * from v_contact_notes ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and contact_uuid = '$contact_uuid' ";
		$sql .= "order by last_mod_date desc ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			unset ($prep_statement, $sql);
		}

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<th>".$text['label-note_content']."</th>\n";
	echo "<th style='text-align: right;'>".$text['label-note_user']."</th>\n";
	echo "<td class='list_control_icons'>";
	if (permission_exists('contact_note_add')) {
		echo "<a href='contact_note_edit.php?contact_uuid=".$_GET['id']."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<div id='contact_notes' style='width: 100%; overflow: auto; direction: rtl; text-align: right; margin-bottom: 23px;'>";
	echo "<table class='tr_hover' style='width: 100%; direction: ltr; padding-left: 1px' border='0' cellpadding='0' cellspacing='0'>\n";
	if ($result_count != 0) {
		foreach($result as $row) {
			$contact_note = $row['contact_note'];
			$contact_note = str_replace("\n","<br />",$contact_note);
			if (permission_exists('contact_note_add')) {
				$tr_link = "href='contact_note_edit.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_note_uuid']."'";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."' colspan='2'>";
			echo "		<div style='display: inline-block; float: right; margin: -5px -7px 5px 5px; padding: 3px 4px; font-size: 10px; background-color: #f0f2f6;'><span style='color: #000; font-weight: bold;'>".$row['last_mod_user']."</span>: ".date("j M Y @ H:i:s", strtotime($row['last_mod_date']))."</div>";
			echo 		$contact_note."&nbsp;";
			echo "	</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('contact_note_edit')) {
				echo "<a href='contact_note_edit.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_note_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('contact_note_delete')) {
				echo "<a href='contact_note_delete.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_note_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results
	echo "</table>";
	echo "</div>\n";

	echo "<script>if (document.getElementById('contact_notes').offsetHeight > 200) { document.getElementById('contact_notes').style.height = 200; }</script>\n";

?>