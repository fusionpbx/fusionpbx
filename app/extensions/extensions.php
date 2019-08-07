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
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('extension_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the registrations
	if (permission_exists('extension_registered')) {
		$obj = new registrations;
		$registrations = $obj->get('all');
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set them as variables
	$search = $_GET["search"];
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//handle search term
	$search = $_GET["search"];
	if (strlen($search) > 0) {
		$search = strtolower($search);
		$sql_search = "and ( ";
		$sql_search .= "lower(extension) like :search ";
		$sql_search .= "or lower(call_group) like :search ";
		$sql_search .= "or lower(user_context) like :search ";
		$sql_search .= "or lower(enabled) like :search ";
		$sql_search .= "or lower(description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//additional includes
	require_once "resources/header.php";
	$document['title'] = $text['title-extensions'];
	require_once "resources/paging.php";

//get total extension count
	$sql_1 = "select count(*) from v_extensions ";
	if (!($_GET['show'] == "all" && permission_exists('extension_all'))) {
		$sql_1 .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	$sql_1 .= $sql_search;
	$database = new database;
	$total_extensions = $database->select($sql_1, $parameters, 'column');

//get total numeric extension count
	if ($db_type == "pgsql" || $db_type == "mysql") {
		$sql_2 = $sql_1." and extension ~ '^[0-9]+$' ";
		$database = new database;
		$numeric_extensions = $database->select($sql_2, $parameters, 'column');
	}
	unset($sql_2);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".escape($search);
	if (!isset($_GET['page'])) { $_GET['page'] = 0; }
	$_GET['page'] = check_str($_GET['page']);
	list($paging_controls_mini, $rows_per_page, $var_3) = paging($total_extensions, $param, $rows_per_page, true); //top
	list($paging_controls, $rows_per_page, $var_3) = paging($total_extensions, $param, $rows_per_page); //bottom
	$offset = $rows_per_page * $_GET['page'];

//to cast or not to cast
	$order_text = $db_type == "pgsql" && $total_extensions == $numeric_extensions ? 'cast(extension as bigint)' : 'extension';

//get the extensions
	$sql_3 = str_replace('count(*)', '*', $sql_1);
	$sql_3 .= $order_by == '' || $order_by == 'extension' ? ' order by '.$order_text.' '.$order.' ' : order_by($order_by, $order);
	$sql_3 .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$extensions = $database->select($sql_3, $parameters, 'all');
	unset($sql_1, $sql_3, $parameters);

//set the alternating styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'>\n";
	echo "		<b>".$text['header-extensions']." (".$total_extensions.")</b><br>\n";
	echo "	</td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	if (permission_exists('extension_all')) {
		if ($_GET['show'] == 'all') {
			echo "	<input type='hidden' name='show' value='all'>";
		}
		else {
			echo "	<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='extensions.php?show=all';\">\n";
		}
	}
	if (permission_exists('extension_import')) {
		echo 				"<input type='button' class='btn' alt='".$text['button-import']."' onclick=\"window.location='extension_imports.php'\" value='".$text['button-import']."'>\n";
	}
	if (permission_exists('extension_export')) {
		echo "				<input type='button' class='btn' value='".$text['button-export']."' onclick=\"window.location.href='extension_download.php'\">\n";
	}
	echo "				<input type='text' class='txt' style='width: 150px; margin-left: 15px;' name='search' id='search' value='".escape($search)."'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	if ($paging_controls_mini != '') {
		echo 			"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "			</td>\n";
	echo "		</form>\n";
	echo "  </tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo "			".$text['description-extensions']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

	echo "<form name='frm' method='post' action='extension_delete.php'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if (permission_exists('extension_delete') && is_array($extensions)) {
		echo "<th style='width: 30px; text-align: center; padding: 0px;'><input type='checkbox' id='chk_all' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
	}
	if ($_GET['show'] == "all" && permission_exists('extension_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param);
	}
	echo th_order_by('extension', $text['label-extension'], $order_by, $order);
	echo th_order_by('call_group', $text['label-call_group'], $order_by, $order);
	//echo th_order_by('voicemail_mail_to', $text['label-voicemail_mail_to'], $order_by, $order);
	echo th_order_by('user_context', $text['label-user_context'], $order_by, $order);
	if (permission_exists('extension_registered')) {
		echo "<th>".$text['label-is_registered']."</th>\n";
 	}
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order);
	echo th_order_by('description', $text['label-description'], $order_by, $order);

	echo "<td class='list_control_icon'>\n";
	if (permission_exists('extension_add')) {
		if ($_SESSION['limit']['extensions']['numeric'] == '' || ($_SESSION['limit']['extensions']['numeric'] != '' && $total_extensions < $_SESSION['limit']['extensions']['numeric'])) {
			echo "<a href='extension_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
		}
	}
	if (permission_exists('extension_delete') && is_array($extensions)) {
		echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if (is_array($extensions)) {
		foreach($extensions as $row) {
			$tr_link = (permission_exists('extension_edit')) ? " href='extension_edit.php?id=".escape($row['extension_uuid'])."'" : null;
			echo "<tr ".$tr_link.">\n";
			if (permission_exists('extension_delete')) {
				echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; vertical-align: middle; padding: 0px;'>";
				echo "		<input type='checkbox' name='id[]' id='checkbox_".escape($row['extension_uuid'])."' value='".escape($row['extension_uuid'])."' onclick=\"if (!this.checked) { document.getElementById('chk_all').checked = false; }\">";
				echo "	</td>";
				$ext_ids[] = 'checkbox_'.$row['extension_uuid'];
			}
			if ($_GET['show'] == "all" && permission_exists('extension_all')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('extension_edit')) {
				echo "<a href='extension_edit.php?id=".escape($row['extension_uuid'])."'>".escape($row['extension'])."</a>";
			}
			else {
				echo escape($row['extension']);
			}
			echo "</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['call_group'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['voicemail_mail_to']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['user_context'])."</td>\n";

			if (permission_exists('extension_registered')) {
				echo "	<td valign='top' class='".$row_style[$c]."'>";
				$extension_number = $row['extension'].'@'.$_SESSION['domain_name'];
				$extension_number_alias = $row['number_alias'];
				if(strlen($extension_number_alias) > 0) {
					$extension_number_alias .= '@'.$_SESSION['domain_name'];
				}
				$found_count = 0;
				foreach ($registrations as $array) {
					if (
						($extension_number == $array['user']) ||
						($extension_number_alias != '' &&
							$extension_number_alias == $array['user']
						)
					) {
						$found_count++;
					}
				}
				if ($found_count > 0) {
					echo "Yes ($found_count)";
				} else {
					echo "No";
				}
				unset($extension_number, $extension_number_alias, $found_count, $array);
				echo "&nbsp;</td>\n";
			}

			echo "	<td valign='top' class='".$row_style[$c]."'>".escape(ucwords($row['enabled']))."</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='30%'>".escape($row['description'])."&nbsp;</td>\n";

			echo "	<td class='list_control_icons'>";
			if (permission_exists('extension_edit')) {
				echo "<a href='extension_edit.php?id=".escape($row['extension_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('extension_delete')) {
				echo "<a href='extension_delete.php?id[]=".escape($row['extension_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}
	}
	unset($extensions, $row);

	if (is_array($extensions)) {
		echo "<tr>\n";
		echo "	<td colspan='20' class='list_control_icons'>\n";
		if (permission_exists('extension_add')) {
			if ($_SESSION['limit']['extensions']['numeric'] == '' || ($_SESSION['limit']['extensions']['numeric'] != '' && $total_extensions < $_SESSION['limit']['extensions']['numeric'])) {
				echo "<a href='extension_edit.php' alt='".$text['button-add']."'>".$v_link_label_add."</a>";
			}
		}
		if (permission_exists('extension_delete')) {
			echo "<a href='javascript:void(0);' onclick=\"if (confirm('".$text['confirm-delete']."')) { document.forms.frm.submit(); }\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";
		}
		echo "	</td>\n";
		echo "</tr>\n";
	}

	echo "</table>";
	echo "</form>";

	if (strlen($paging_controls) > 0) {
		echo "<br />";
		echo $paging_controls."\n";
	}

	echo "<br /><br />".((is_array($extensions)) ? "<br /><br />" : null);

	// check or uncheck all checkboxes
	if (sizeof($ext_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what) {\n";
		echo "		document.getElementById('chk_all').checked = (what == 'all') ? true : false;\n";
		foreach ($ext_ids as $ext_id) {
			echo "		document.getElementById('".$ext_id."').checked = (what == 'all') ? true : false;\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

	if (is_array($extensions)) {
		// check all checkboxes
		key_press('ctrl+a', 'down', 'document', null, null, "check('all');", true);

		// delete checked
		key_press('delete', 'up', 'document', array('#search'), $text['confirm-delete'], 'document.forms.frm.submit();', true);
	}

//show the footer
	require_once "resources/footer.php";

?>
