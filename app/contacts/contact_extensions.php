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
if (permission_exists('contact_extension_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//javascript function: send_cmd
	echo "<script type=\"text/javascript\">\n";
	echo "function send_cmd(url) {\n";
	echo "	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari\n";
	echo "		xmlhttp=new XMLHttpRequest();\n";
	echo "	}\n";
	echo "	else {// code for IE6, IE5\n";
	echo "		xmlhttp=new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
	echo "	}\n";
	echo "	xmlhttp.open(\"GET\",url,true);\n";
	echo "	xmlhttp.send(null);\n";
	echo "	document.getElementById('cmd_reponse').innerHTML=xmlhttp.responseText;\n";
	echo "}\n";
	echo "</script>\n";

//show the content
	echo "<table width='100%' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>".$text['label-contact_extensions']."</b></td>\n";
	echo "<td width='50%' align='right'>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	//get the extension list
		$sql = "select e.extension_uuid, e.extension, e.enabled, e.description ";
		$sql .= " from v_extensions e, v_extension_users eu, v_users u ";
		$sql .= " where e.extension_uuid = eu.extension_uuid ";
		$sql .= " and u.user_uuid = eu.user_uuid ";
		$sql .= " and e.domain_uuid = '$domain_uuid' ";
		$sql .= " and u.contact_uuid = '$contact_uuid' ";
		$sql .= "order by e.extension asc ";
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
	echo "<th>".$text['label-extension']."</th>\n";
	echo "<th>".$text['label-enabled']."</th>\n";
	echo "<th>".$text['label-description']."</th>\n";
	echo "<td class='list_control_icons'>";
	if (permission_exists('extension_add')) {
		echo "<a href='".PROJECT_PATH."/app/extensions/extension_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>\n";
	}
	echo "</td>\n";
	echo "</tr>\n";
	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('extension_edit')) ? "href='/app/extensions/extension_edit.php?id=".$row['extension_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('extension_edit')) {
				echo 	"<a href='".PROJECT_PATH."/app/extensions/extension_edit.php?id=".$row['extension_uuid']."'>".$row['extension']."</a>";
			}
			else {
				echo $row['extension'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-'.$row['enabled']]."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".$row['description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('extension_edit')) {
				echo "<a href='".PROJECT_PATH."/app/extensions/extension_edit.php?id=".$row['extension_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('extension_delete')) {
				echo "<a href='".PROJECT_PATH."/app/extensions/extension_delete.php?id=".$row['extension_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "</table>";

?>