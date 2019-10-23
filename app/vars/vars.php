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
	if (permission_exists('var_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//toggle enabled state
	if (is_uuid($_REQUEST['id']) && (strtolower($_REQUEST['enabled']) == 'true' || strtolower($_REQUEST['enabled']) == 'false')) {
		//build array
			$array['vars'][0]['var_uuid'] = $_REQUEST['id'];
			$array['vars'][0]['var_enabled'] = strtolower($_REQUEST['enabled']);

		//grant temporary permissions
			$p = new permissions;
			$p->add('var_edit', 'temp');

		//execute update
			$database = new database;
			$database->app_name = 'vars';
			$database->app_uuid = '54e08402-c1b8-0a9d-a30a-f569fc174dd8';
			$database->save($array);
			unset($array);

		//revoke temporary permissions
			$p->delete('var_edit', 'temp');

		//unset the user defined variables
			$_SESSION["user_defined_variables"] = "";

		//synchronize the configuration
			save_var_xml();

		//set message
			message::add($text['message-update']);

		//redirect
			header("Location: vars.php?id=".$_REQUEST['id']);
			exit;
	}

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-variables'];

//set http values as php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left'><b>".$text['header-variables']."</b><br>\n";
	echo "		".$text['description-variables']."\n";
	echo "	</td>\n";
	echo "  </tr>\n";
	echo "</table>\n";

	$sql = "select * from v_vars ";
	$sql .= $order_by != '' ? order_by($order_by, $order) : "order by var_category, var_order asc ";
	$database = new database;
	$result = $database->select($sql, null, 'all');
	unset($sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	$tmp_var_header = "<tr>\n";
	$tmp_var_header .= th_order_by('var_name', $text['label-name'], $order_by, $order);
	$tmp_var_header .= th_order_by('var_value', $text['label-value'], $order_by, $order);
	$tmp_var_header .= th_order_by('var_hostname', $text['label-hostname'], $order_by, $order);
	$tmp_var_header .= th_order_by('var_enabled', $text['label-enabled'], $order_by, $order);
	$tmp_var_header .= "<th>".$text['label-description']."</th>\n";
	$tmp_var_header .= "<td class='list_control_icons'>";
	if (permission_exists('var_add')) {
		$tmp_var_header .= "<a href='var_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	$tmp_var_header .= "</td>\n";
	$tmp_var_header .= "<tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		$prev_var_category = '';
		foreach($result as $row) {
			$var_value = $row['var_value'];
			$var_value = substr($var_value, 0, 50);
			if ($prev_var_category != $row['var_category']) {
				$c=0;
				if (strlen($prev_var_category) > 0) {
					echo "<tr>\n";
					echo "<td colspan='6'>\n";
					echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
					echo "	<tr>\n";
					echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
					echo "		<td width='33.3%' align='center' nowrap>&nbsp;</td>\n";
					echo "		<td width='33.3%' align='right'>";
					if (permission_exists('var_add')) {
						echo "<a href='var_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
					}
					echo "		</td>\n";
					echo "	</tr>\n";
					echo "	</table>\n";
					echo "</td>\n";
					echo "</tr>\n";
				}
				echo "<tr><td colspan='4' align='left'>\n";
				echo "	<br />\n";
				echo "	<br />\n";
				echo "	<b>".$row['var_category']."</b>&nbsp;</td></tr>\n";
				echo $tmp_var_header;
			}

			$tr_link = (permission_exists('var_edit')) ? "href='var_edit.php?id=".$row['var_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' align='left' class='".$row_style[$c]."'>";
			if (permission_exists('var_edit')) {
				echo "<a href='var_edit.php?id=".$row['var_uuid']."'>".substr($row['var_name'],0,32)."</a>";
			}
			else {
				echo substr($row['var_name'],0,32);
			}
			echo "	</td>\n";
			echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".substr($var_value,0,30)."</td>\n";
			echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".$row['var_hostname']."&nbsp;</td>\n";
			echo "	<td valign='top' align='left' class='".$row_style[$c]."'>";
			echo "		<a href='?id=".$row['var_uuid']."&enabled=".(($row['var_enabled'] == 'true') ? 'false' : 'true')."'>".(($row['var_enabled'] == 'true') ? $text['option-true'] : $text['option-false'])."</a>";
			echo "	</td>\n";
			$var_description = str_replace("\n", "<br />", trim(substr(base64_decode($row['var_description']),0,40)));
			$var_description = str_replace("   ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $var_description);
			echo "	<td valign='top' align='left' class='row_stylebg'>".$var_description."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>";
			if (permission_exists('var_edit')) {
				echo "<a href='var_edit.php?id=".escape($row['var_uuid'])."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('var_delete')) {
				echo "<a href='var_delete.php?id=".escape($row['var_uuid'])."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";

			$prev_var_category = $row['var_category'];
			$c = $c ? 0 : 1;
		}
	}
	unset($result, $row);

	echo "<tr>\n";
	echo "<td colspan='6' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td width='33.3%' class='list_control_icons'>";
	if (permission_exists('var_add')) {
		echo "<a href='var_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

//include the footer
	require_once "resources/footer.php";

?>
