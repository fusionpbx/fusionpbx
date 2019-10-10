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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('contact_attachment_view')) {
		echo "access denied"; exit;
	}

//get the contact attachment list
	$sql = "select *, length(decode(attachment_content,'base64')) as attachment_size from v_contact_attachments ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by attachment_primary desc, attachment_filename asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_attachments = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//styles
	echo "<style>\n";

	echo "	#contact_attachment_layer {\n";
	echo "		z-index: 999999;\n";
	echo "		position: absolute;\n";
	echo "		left: 0px;\n";
	echo "		top: 0px;\n";
	echo "		right: 0px;\n";
	echo "		bottom: 0px;\n";
	echo "		text-align: center;\n";
	echo "		vertical-align: middle;\n";
	echo "	}\n";

	echo "</style>\n";

//ticket attachment layer
	echo "<div id='contact_attachment_layer' style='display: none;'></div>\n";

//show the content
	echo "<b>".$text['label-attachments']."</b>\n";

	echo "<table class='tr_hover' style='margin-bottom: 20px;' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>".$text['label-attachment_filename']."</th>\n";
	echo "<th>".$text['label-attachment_size']."</th>\n";
	echo "<th>".$text['label-attachment_description']."</th>\n";
	echo "<td class='list_control_icons'>";
	if (permission_exists('contact_attachment_add')) {
		echo "<a href='contact_attachment_edit.php?contact_uuid=".escape($_GET['id'])."' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";
	if (is_array($contact_attachments) && @sizeof($contact_attachments) != 0) {
		foreach($contact_attachments as $row) {
			if (permission_exists('contact_attachment_edit')) {
				$tr_link = "href='contact_attachment_edit.php?contact_uuid=".escape($row['contact_uuid'])."&id=".escape($row['contact_attachment_uuid'])."'";
			}
			echo "<tr ".$tr_link." ".((escape($row['attachment_primary'])) ? "style='font-weight: bold;'" : null).">\n";
			$attachment_type = strtolower(pathinfo($row['attachment_filename'], PATHINFO_EXTENSION));
			if ($attachment_type == 'jpg' || $attachment_type == 'jpeg' || $attachment_type == 'gif' || $attachment_type == 'png') {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='cursor: pointer;' onclick=\"display_attachment('".escape($row['contact_attachment_uuid'])."');\">";
			}
			else {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='cursor: pointer;' onclick=\"window.location='contact_attachment.php?id=".escape($row['contact_attachment_uuid'])."&action=download';\">";
			}
			echo "		<a>".escape($row['attachment_filename'])."</a>\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".strtoupper(byte_convert($row['attachment_size']))."</td>\n";
			echo "	<td valign='top' class='row_stylebg'>".escape($row['attachment_description'])."</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('contact_attachment_edit')) {
				echo "<a href='contact_attachment_edit.php?contact_uuid=".escape($row['contact_uuid'])."&id=".escape($row['contact_attachment_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('contact_attachment_delete')) {
				echo "<a href='contact_attachment_delete.php?contact_uuid=".escape($row['contact_uuid'])."&id=".escape($row['contact_attachment_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			$c = $c ?: 1;
		}
	}
	unset($contact_attachments, $row);

	echo "</table>";

//javascript
	echo "<script>\n";

	echo "	function display_attachment(id) {\n";
	echo "		$('#contact_attachment_layer').load('contact_attachment.php?id=' + id + '&action=display', function(){\n";
	echo "			$('#contact_attachment_layer').fadeIn(200);\n";
	echo "		});\n";
	echo "	}\n";

	echo "</script>\n";

?>
