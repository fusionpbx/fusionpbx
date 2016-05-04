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
 Portions created by the Initial Developer are Copyright (C) 2008-2016
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('voicemail_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//retrieve order by
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);

//set the voicemail id and voicemail uuid arrays
	if (isset($_SESSION['user']['extension'])) foreach ($_SESSION['user']['extension'] as $index => $row) {
		if (strlen($row['number_alias']) > 0) {
			$voicemail_ids[$index]['voicemail_id'] = $row['number_alias'];
		}
		else {
			$voicemail_ids[$index]['voicemail_id'] = $row['user'];
		}
	}
	if (isset($_SESSION['user']['voicemail'])) foreach ($_SESSION['user']['voicemail'] as $row) {
		if (strlen($row['voicemail_uuid']) > 0) {
			$voicemail_uuids[]['voicemail_uuid'] = $row['voicemail_uuid'];
		}
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//prepare to page the results
	$sql = "select count(*) as num_rows from v_voicemails ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= "	voicemail_id like '%".$search."%' ";
		$sql .= " 	or voicemail_mail_to like '%".$search."%' ";
		$sql .= " 	or voicemail_local_after_email like '%".$search."%' ";
		$sql .= " 	or voicemail_enabled like '%".$search."%' ";
		$sql .= " 	or voicemail_description like '%".$search."%' ";
		$sql .= ") ";
	}
	if (!permission_exists('voicemail_delete')) {
		$x = 0;
		if (count($voicemail_uuids) > 0) {
			$sql .= "and (";
			foreach($voicemail_uuids as $row) {
				if ($x == 0) {
					$sql .= "voicemail_uuid = '".$row['voicemail_uuid']."' ";
				}
				else {
					$sql .= " or voicemail_uuid = '".$row['voicemail_uuid']."'";
				}
				$x++;
			}
			$sql .= ")";
		}
		else {
			$sql .= "and voicemail_uuid is null ";
		}
	}
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
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "";
	if ($search != '') { $param .= "&search=".$search; }
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*) as num_rows', '*', $sql);
	if (strlen($order_by) > 0) {
		$sql .= ($order_by == 'voicemail_id') ? "order by voicemail_id ".$order." " : "order by ".$order_by." ".$order." ";
	}
	else {
		$sql .= "order by voicemail_id asc ";
	}
	$sql .= "limit ".$rows_per_page." offset ".$offset." ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$voicemails = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap' valign='top'>";
	echo "			<b>".$text['title-voicemails']." (".$num_rows.")</b>";
	echo "			<br /><br />";
	echo "			".$text['description-voicemail'];
	echo "			<br /><br />";
	echo "		</td>\n";
	echo "			<td width='30%' align='right' valign='top'>\n";
	echo "				<form method='get' action=''>\n";
	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".$search."'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "				</form>\n";
	echo "			</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<form name='frm' method='post' action='voicemail_delete.php'>\n";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if (permission_exists('voicemail_delete') && $num_rows > 0) {
		echo "<th style='width: 30px; text-align: center; padding: 0px;'><input type='checkbox' id='chk_all' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
	}
	echo th_order_by('voicemail_id', $text['label-voicemail_id'], $order_by, $order);
	echo th_order_by('voicemail_mail_to', $text['label-voicemail_mail_to'], $order_by, $order);
	echo th_order_by('voicemail_file', $text['label-voicemail_file_attached'], $order_by, $order);
	echo th_order_by('voicemail_local_after_email', $text['label-voicemail_local_after_email'], $order_by, $order);
	echo "<th>".$text['label-tools']."</th>\n";
	echo th_order_by('voicemail_enabled', $text['label-voicemail_enabled'], $order_by, $order);
	echo th_order_by('voicemail_description', $text['label-voicemail_description'], $order_by, $order);
	echo "<td class='list_control_icon'>";
	if (permission_exists('voicemail_add') || permission_exists('voicemail_edit')) {
		echo "<a href='voicemail_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
	}
	if (permission_exists('voicemail_delete') && $num_rows > 0) {
		echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	if ($num_rows > 0) {

		foreach($voicemails as $row) {
			$tr_link = (permission_exists('voicemail_edit')) ? "href='voicemail_edit.php?id=".$row['voicemail_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			if (permission_exists('voicemail_delete')) {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; vertical-align: middle; padding: 0px;'>";
				echo "		<input type='checkbox' name='id[]' id='checkbox_".$row['voicemail_uuid']."' value='".$row['voicemail_uuid']."' onclick=\"if (!this.checked) { document.getElementById('chk_all').checked = false; }\">";
				echo "	</td>";
				$vm_ids[] = 'checkbox_'.$row['voicemail_uuid'];
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('voicemail_edit')) {
				echo "<a href='voicemail_edit.php?id=".$row['voicemail_uuid']."'>".$row['voicemail_id']."</a>";
			}
			else {
				echo $row['voicemail_id'];
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['voicemail_mail_to']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".(($row['voicemail_file'] == 'attach') ? $text['label-true'] : $text['label-false'])."</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['voicemail_local_after_email'])."&nbsp;</td>\n";
			echo "	<td valign='middle' class='".$row_style[$c]."' style='white-space: nowrap;'>\n";
			if (permission_exists('voicemail_message_view')) {
				echo "		<a href='voicemail_messages.php?id=".$row['voicemail_uuid']."'>".$text['label-messages']."</a>&nbsp;&nbsp;\n";
			}
			if (permission_exists('voicemail_greeting_view')) {
				echo "		<a href='".PROJECT_PATH."/app/voicemail_greetings/voicemail_greetings.php?id=".$row['voicemail_id']."'>".$text['label-greetings']."</a>\n";
			}
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$text['label-'.$row['voicemail_enabled']]."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='30%'>".$row['voicemail_description']."&nbsp;</td>\n";
			if (permission_exists('voicemail_edit') || permission_exists('voicemail_delete')) {
				echo "	<td class='list_control_icons' style='width: 25px;'>";
				if (permission_exists('voicemail_edit')) {
					echo "<a href='voicemail_edit.php?id=".$row['voicemail_uuid']."' alt='".$text['button-edit']."'>".$v_link_label_edit."</a>";
				}
				if (permission_exists('voicemail_delete')) {
					echo "<a href='voicemail_delete.php?id[]=".$row['voicemail_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>";
				}
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}
		unset($voicemails, $row);

	}

	if ($num_rows > 0) {
		echo "<tr>\n";
		echo "	<td colspan='20' class='list_control_icons'>\n";
		if (permission_exists('voicemail_add')) {
			echo "<a href='voicemail_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
		if (permission_exists('voicemail_delete')) {
			echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
		}
		echo "	</td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
	echo "</form>";

	if (strlen($paging_controls) > 0) {
		echo "<center>".$paging_controls."</center>\n";
	}

	echo "<br /><br />".(($num_rows == 0) ? "<br /><br />" : null);

	// check or uncheck all checkboxes
	if (sizeof($vm_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what) {\n";
		echo "		document.getElementById('chk_all').checked = (what == 'all') ? true : false;\n";
		foreach ($vm_ids as $vm_id) {
			echo "		document.getElementById('".$vm_id."').checked = (what == 'all') ? true : false;\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

	if ($num_rows > 0) {
		// check all checkboxes
		key_press('ctrl+a', 'down', 'document', null, null, "check('all');", true);

		// delete checked
		key_press('delete', 'up', 'document', array('#search'), $text['confirm-delete'], 'document.forms.frm.submit();', true);
	}

//include the footer
	require_once "resources/footer.php";
?>