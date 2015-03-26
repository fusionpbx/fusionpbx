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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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
	if ($_REQUEST['id'] != '' && $_REQUEST['enabled'] != '') {
		$sql = "update v_vars set ";
		$sql .= "var_enabled = '".check_str($_REQUEST['enabled'])."' ";
		$sql .= "where var_uuid = '".check_str($_REQUEST['id'])."' ";
		$db->exec(check_sql($sql));
		unset($sql);

		//unset the user defined variables
		$_SESSION["user_defined_variables"] = "";

		//synchronize the configuration
		save_var_xml();

		$_SESSION["message"] = $text['message-update'];
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
	if (strlen($order_by)> 0) {
		$sql .= "order by $order_by $order ";
	}
	else {
		$sql .= "order by var_cat, var_order asc ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	$tmp_var_header = '';
	$tmp_var_header .= "<tr>\n";
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

	if ($result_count > 0) {
		$prev_var_cat = '';
		foreach($result as $row) {
			$var_value = $row[var_value];
			$var_value = substr($var_value, 0, 50);
			if ($prev_var_cat != $row[var_cat]) {
				$c=0;
				if (strlen($prev_var_cat) > 0) {
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
				echo "	<b>".$row['var_cat']."</b>&nbsp;</td></tr>\n";
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
			echo "	<td valign='top' align='left' class='row_stylebg'>".$row['var_hostname']."&nbsp;</td>\n";
			echo "	<td valign='top' align='left' class='".$row_style[$c]."'>";
			echo "		<a href='?id=".$row['var_uuid']."&enabled=".(($row['var_enabled'] == 'true') ? 'false' : 'true')."'>".(($row['var_enabled'] == 'true') ? $text['option-true'] : $text['option-false'])."</a>";
			echo "	</td>\n";
			$var_description = str_replace("\n", "<br />", trim(substr(base64_decode($row['var_description']),0,40)));
			$var_description = str_replace("   ", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $var_description);
			echo "	<td valign='top' align='left' class='row_stylebg'>".$var_description."&nbsp;</td>\n";
			echo "	<td valign='top' align='right'>";
			if (permission_exists('var_edit')) {
				echo "<a href='var_edit.php?id=".$row['var_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('var_delete')) {
				echo "<a href='var_delete.php?id=".$row['var_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";

			$prev_var_cat = $row[var_cat];
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

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