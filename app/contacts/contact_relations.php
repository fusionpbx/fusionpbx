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
if (permission_exists('contact_relation_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-contact_relations']."</b></td>\n";
	echo "<td width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	//get the related contacts
		$sql = "select ";
		$sql .= "cr.contact_relation_uuid, ";
		$sql .= "cr.relation_label, ";
		$sql .= "c.contact_uuid, ";
		$sql .= "c.contact_organization, ";
		$sql .= "c.contact_name_given, ";
		$sql .= "c.contact_name_family ";
		$sql .= "from ";
		$sql .= "v_contact_relations as cr, ";
		$sql .= "v_contacts as c ";
		$sql .= "where ";
		$sql .= "cr.relation_contact_uuid = c.contact_uuid ";
		$sql .= "and cr.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and cr.contact_uuid = '".$contact_uuid."' ";
		$sql .= "order by ";
		$sql .= "c.contact_organization desc, ";
		$sql .= "c.contact_name_given asc, ";
		$sql .= "c.contact_name_family asc ";
		//echo $sql."<br><br>";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' style='margin-bottom: 20px;' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<th>".$text['label-contact_relation_label']."</th>\n";
	echo "<th>".$text['label-contact_relation_organization']."</th>\n";
	echo "<th>".$text['label-contact_relation_name']."</th>\n";
	echo "<td class='list_control_icons'>";
	if (permission_exists('contact_relation_add')) {
		echo "<a href='contact_relation_edit.php?contact_uuid=".$contact_uuid."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			if (permission_exists('contact_relation_edit')) {
				$tr_link = "href='contact_relation_edit.php?contact_uuid=".$row['contact_uuid']."&id=".$row['contact_relation_uuid']."' ";
			}
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['relation_label']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void'><a href='contact_edit.php?id=".$row['contact_uuid']."'>".$row['contact_organization']."</a>&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void'><a href='contact_edit.php?id=".$row['contact_uuid']."'>".$row['contact_name_given'].(($row['contact_name_given'] != '' && $row['contact_name_family'] != '') ? ' ' : null).$row['contact_name_family']."</a>&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('contact_relation_edit')) {
				echo "<a href='contact_relation_edit.php?contact_uuid=".$contact_uuid."&id=".$row['contact_relation_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('contact_relation_delete')) {
				echo "<a href='contact_relation_delete.php?contact_uuid=".$contact_uuid."&id=".$row['contact_relation_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "</table>";

?>