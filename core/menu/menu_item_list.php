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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('menu_add') || permission_exists('menu_edit')) {
	//access granted
}
else {
	echo "access denied";
	return;
}

$tmp_menu_item_order = 0;

function build_db_child_menu_list ($db, $menu_item_level, $menu_item_uuid, $c) {
	global $menu_uuid, $tmp_menu_item_order, $v_link_label_edit, $v_link_label_delete;

	//check for sub menus
		$menu_item_level = $menu_item_level+1;
		$sql = "select * from v_menu_items ";
		$sql .= "where menu_uuid = '".$menu_uuid."' ";
		$sql .= "and menu_item_parent_uuid = '".$menu_item_uuid."' ";
		$sql .= "order by menu_item_title, menu_item_order asc ";
		$prep_statement_2 = $db->prepare($sql);
		$prep_statement_2->execute();
		$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);

		$row_style["0"] = "row_style1";
		$row_style["1"] = "row_style1";

		if (count($result2) > 0) {
			if ($c == 0) { $c2 = 1; } else { $c2 = 0; }
			foreach($result2 as $row2) {
				//set the db values as php variables
					$menu_item_uuid = $row2['menu_item_uuid'];
					$menu_item_category = $row2['menu_item_category'];
					$menu_item_protected = $row2['menu_item_protected'];
					$menu_item_parent_uuid = $row2['menu_item_parent_uuid'];
					$menu_item_order = $row2['menu_item_order'];
					$menu_item_language = $row2['menu_item_language'];
					$menu_item_title = $row2[menu_item_title];
					$menu_item_link = $row2[menu_item_link];
				//get the groups that have been assigned to the menu
					$sql = "";
					$sql .= "select group_name from v_menu_item_groups ";
					$sql .= "where menu_uuid = '$menu_uuid' ";
					$sql .= "and menu_item_uuid = '".$menu_item_uuid."' ";
					$sub_prep_statement = $db->prepare(check_sql($sql));
					$sub_prep_statement->execute();
					$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);
					$group_list = "";
					$x = 0;
					foreach ($sub_result as &$sub_row) {
						if ($x == 0) {
							$group_list = $sub_row["group_name"];
						}
						else {
							$group_list .= ", ".$sub_row["group_name"];
						}
						$x++;
					}
					unset ($sub_prep_statement);
				//display the main body of the list
					switch ($menu_item_category) {
						case "internal":
							$menu_item_title = "<a href='".PROJECT_PATH . $menu_item_link."'>$menu_item_title</a>";
							break;
						case "external":
							if (substr($menu_item_link,0,1) == "/") {
								$menu_item_link = PROJECT_PATH . $menu_item_link;
							}
							$menu_item_title = "<a href='$menu_item_link' target='_blank'>$menu_item_title</a>";
							break;
						case "email":
							$menu_item_title = "<a href='mailto:$menu_item_link'>$menu_item_title</a>";
							break;
					}

				//display the content of the list
					echo "<tr'>\n";
					echo "<td valign='top' class='".$row_style[$c]."'>";
					echo "  <table cellpadding='0' cellspacing='0' border='0'>";
					echo "  <tr>";
					echo "      <td nowrap>";
					$i=0;
					while($i < $menu_item_level){
						echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
						$i++;
					}
					echo "       ".$menu_item_title."&nbsp;";

					echo "      </td>";
					echo "  </tr>";
					echo "  </table>";
					echo "</td>";
					//echo "<td valign='top'>&nbsp;".$menu_item_link."&nbsp;</td>";
					echo "<td valign='top' class='".$row_style[$c]."'>&nbsp;".$group_list."&nbsp;</td>";
					echo "<td valign='top' class='".$row_style[$c]."'>&nbsp;".$menu_item_category."&nbsp;</td>";
					//echo "<td valign='top'>".$row[menu_item_description]."</td>";
					//echo "<td valign='top'>&nbsp;".$row[menu_item_order]."&nbsp;</td>";
					if ($menu_item_protected == "true") {
						echo "<td valign='top' class='".$row_style[$c]."'>&nbsp; <strong>yes</strong> &nbsp;</td>";
					}
					else {
						echo "<td valign='top' class='".$row_style[$c]."'>&nbsp; no &nbsp;</td>";
					}
					echo "<td valign='top' align='center' nowrap class='".$row_style[$c]."'>";
					echo "	&nbsp;";
					//echo "  ".$row2[menu_item_order]."&nbsp;";
					echo "</td>";

					//echo "<td valign='top' align='center' class='".$row_style[$c]."'>";
					//if (permission_exists('menu_edit')) {
					//	echo "  <input type='button' class='btn' name='' onclick=\"window.location='menu_item_move_up.php?menu_uuid=".$menu_uuid."&menu_item_parent_uuid=".$row2['menu_item_parent_uuid']."&menu_item_uuid=".$row2[menu_item_uuid]."&menu_item_order=".$row2[menu_item_order]."'\" value='<' title='".$row2[menu_item_order].". Move Up'>";
					//	echo "  <input type='button' class='btn' name='' onclick=\"window.location='menu_item_move_down.php?menu_uuid=".$menu_uuid."&menu_item_parent_uuid=".$row2['menu_item_parent_uuid']."&menu_item_uuid=".$row2[menu_item_uuid]."&menu_item_order=".$row2[menu_item_order]."'\" value='>' title='".$row2[menu_item_order].". Move Down'>";
					//}
					//echo "</td>";

					echo "   <td valign='top' align='right' nowrap>\n";
					if (permission_exists('menu_edit')) {
						echo "		<a href='menu_item_edit.php?id=".$menu_uuid."&menu_item_uuid=".$row2['menu_item_uuid']."&menu_item_parent_uuid=".$row2['menu_item_parent_uuid']."' alt='edit'>$v_link_label_edit</a>\n";
					}
					if (permission_exists('menu_delete')) {
						echo "		<a href='menu_item_delete.php?id=".$menu_uuid."&menu_item_uuid=".$row2['menu_item_uuid']."' onclick=\"return confirm('Do you really want to delete this?')\" alt='delete'>$v_link_label_delete</a>\n";
					}
					echo "   </td>\n";
					echo "</tr>";

				//update the menu order
					if ($row2[menu_item_order] != $tmp_menu_item_order) {
						$sql  = "update v_menu_items set ";
						$sql .= "menu_item_title = '".$row2[menu_item_title]."', ";
						$sql .= "menu_item_order = '".$tmp_menu_item_order."' ";
						$sql .= "where menu_uuid = '".$menu_uuid."' ";
						$sql .= "and menu_item_uuid = '".$row2[menu_item_uuid]."' ";
						$count = $db->exec(check_sql($sql));
					}
					$tmp_menu_item_order++;

				//check for additional sub menus
					if (strlen($menu_item_uuid)> 0) {
						$c = build_db_child_menu_list($db, $menu_item_level, $menu_item_uuid, $c);
					}

				if ($c==0) { $c=1; } else { $c=0; }
			} //end foreach
			unset($sql, $result2, $row2);
		}
		return $c;
	//end check for children
}

require_once "includes/header.php";
$order_by = $_GET["order_by"];
$order = $_GET["order"];

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";

	echo "<table width='100%' border='0'><tr>";
	//echo "<td width='50%'><b>Menu Manager</b></td>";
	echo "<td width='50%' align='right'>\n";
	//if (permission_exists('menu_restore')) {
	//	echo "	<input type='button' class='btn' value='Restore Default' onclick=\"document.location.href='menu_restore_default.php';\" />";
	//}
	echo "</td>\n";
	echo "<td width='35' nowrap></td>\n";
	echo "</tr></table>";

	$sql = "select * from v_menu_items ";
	$sql .= "where menu_uuid = '".$menu_uuid."' ";
	$sql .= "and menu_item_parent_uuid is null ";
	if (strlen($order_by)> 0) {
		$sql .= "order by $order_by $order ";
	}
	else {
		$sql .= "order by menu_item_order asc ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style0";

	echo "<div align='left'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	if ($result_count == 0) {
		//no results
		echo "<tr><td>&nbsp;</td></tr>";
	}
	else {
		echo "<tr>";
		echo "<th align='left' nowrap>&nbsp; Title &nbsp; </th>";
		echo "<th align='left' nowrap>&nbsp; Groups &nbsp; </th>";
		echo "<th align='left'nowrap>&nbsp; Category &nbsp; </th>";
		echo "<th nowrap>&nbsp; Protected &nbsp; </th>";
		//echo "<th align='left'  width='55' nowrap>&nbsp; Order &nbsp;</th>";
		echo "<th nowrap width='70'>Order &nbsp; </th>";
		echo "<td align='right' width='42'>\n";
		if (permission_exists('menu_add')) {
			echo "	<a href='menu_item_edit.php?id=".$menu_uuid."' alt='add'>$v_link_label_add</a>\n";
		}
		echo "</td>\n";
		echo "</tr>";

		foreach($result as $row) {
			//set the db values as php variables
				$menu_item_uuid = $row['menu_item_uuid'];
				$menu_item_category = $row['menu_item_category'];
				$menu_item_title = $row['menu_item_title'];
				$menu_item_link = $row['menu_item_link'];
				$menu_item_protected = $row['menu_item_protected'];

			//get the groups that have been assigned to the menu
				$sql = "";
				$sql .= "select group_name from v_menu_item_groups ";
				$sql .= "where menu_uuid = '$menu_uuid' ";
				$sql .= "and menu_item_uuid = '$menu_item_uuid' ";
				$sub_prep_statement = $db->prepare(check_sql($sql));
				$sub_prep_statement->execute();
				$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);
				$group_list = "";
				$x = 0;
				foreach ($sub_result as &$sub_row) {
					if ($x == 0) {
						$group_list = $sub_row["group_name"];
					}
					else {
						$group_list .= ", ".$sub_row["group_name"];
					}
					$x++;
				}
				unset ($sub_prep_statement);

			//add the type link based on the typd of the menu
				switch ($menu_item_category) {
					case "internal":
						$menu_item_title = "<a href='".PROJECT_PATH . $menu_item_link."'>$menu_item_title</a>";
						break;
					case "external":
						if (substr($menu_item_link, 0,1) == "/") {
							$menu_item_link = PROJECT_PATH . $menu_item_link;
						}
						$menu_item_title = "<a href='$menu_item_link' target='_blank'>$menu_item_title</a>";
						break;
					case "email":
						$menu_item_title = "<a href='mailto:$menu_item_link'>$menu_item_title</a>";
						break;
				}

			//display the content of the list
				echo "<tr style='".$row_style[$c]."'>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>&nbsp; ".$menu_item_title."&nbsp;</td>";
				echo "<td valign='top' class='".$row_style[$c]."'>&nbsp; ".$group_list."&nbsp;</td>";
				//echo "<td valign='top' class='".$row_style[$c]."'>&nbsp;".$menu_item_link."&nbsp;</td>";
				echo "<td valign='top' class='".$row_style[$c]."'>&nbsp;".$menu_item_category."&nbsp;</td>";
				//echo "<td valign='top' class='".$row_style[$c]."'>".$row[menu_item_description]."</td>";
				//echo "<td valign='top' class='".$row_style[$c]."'>&nbsp;".$row['menu_item_parent_uuid']."&nbsp;</td>";
				//echo "<td valign='top' class='".$row_style[$c]."'>&nbsp;".$row['menu_item_order']."&nbsp;</td>";

				if ($menu_item_protected == "true") {
					echo "<td valign='top' class='".$row_style[$c]."'>&nbsp; <strong>yes</strong> &nbsp;</td>";
				}
				else {
					echo "<td valign='top' class='".$row_style[$c]."'>&nbsp; no &nbsp;</td>";
				}

				echo "<td valign='top' align='center' nowrap class='".$row_style[$c]."'>";
				echo "  ".$row[menu_item_order]."&nbsp;";
				echo "</td>";

				//echo "<td valign='top' align='center' nowrap class='".$row_style[$c]."'>";
				//if (permission_exists('menu_edit')) {
				//	echo "  <input type='button' class='btn' name='' onclick=\"window.location='menu_item_move_up.php?menu_uuid=".$menu_uuid."&menu_item_parent_uuid=".$row['menu_item_parent_uuid']."&menu_item_uuid=".$row['menu_item_uuid']."&menu_item_order=".$row['menu_item_order']."'\" value='<' title='".$row['menu_item_order'].". Move Up'>";
				//	echo "  <input type='button' class='btn' name='' onclick=\"window.location='menu_item_move_down.php?menu_uuid=".$menu_uuid."&menu_item_parent_uuid=".$row['menu_item_parent_uuid']."&menu_item_uuid=".$row['menu_item_uuid']."&menu_item_order=".$row['menu_item_order']."'\" value='>' title='".$row['menu_item_order'].". Move Down'>";
				//}
				//echo "</td>";

				echo "   <td valign='top' align='right' nowrap>\n";
				if (permission_exists('menu_edit')) {
					echo "		<a href='menu_item_edit.php?id=".$menu_uuid."&menu_item_uuid=".$row['menu_item_uuid']."&menu_uuid=".$menu_uuid."' alt='edit'>$v_link_label_edit</a>\n";
				}
				if (permission_exists('menu_delete')) {
					echo "		<a href='menu_item_delete.php?id=".$menu_uuid."&menu_item_uuid=".$row['menu_item_uuid']."&menu_uuid=".$menu_uuid."' onclick=\"return confirm('Do you really want to delete this?')\" alt='delete'>$v_link_label_delete</a>\n";
				}
				echo "   </td>\n";
				echo "</tr>";

			//update the menu order
				if ($row[menu_item_order] != $tmp_menu_item_order) {
					$sql  = "update v_menu_items set ";
					$sql .= "menu_item_title = '".$row['menu_item_title']."', ";
					$sql .= "menu_item_order = '".$tmp_menu_item_order."' ";
					$sql .= "where menu_uuid = '".$menu_uuid."' ";
					$sql .= "and menu_item_uuid = '".$row[menu_item_uuid]."' ";
					//$db->exec(check_sql($sql));
				}
				$tmp_menu_item_order++;

			//check for sub menus
				$menu_item_level = 0;
				if (strlen($row['menu_item_uuid']) > 0) {
					$c = build_db_child_menu_list($db, $menu_item_level, $row['menu_item_uuid'], $c);
				}

			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);

	} //end if results

	echo "<tr>\n";
	echo "<td colspan='6' align='left'>\n";
	echo "	<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	if (permission_exists('menu_add')) {
		echo "			<a href='menu_item_edit.php?id=".$menu_uuid."' alt='add'>$v_link_label_add</a>\n";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	</table>\n";

	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</div>\n";
	echo "<br><br>";

	echo "  </td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</div>";

	echo "<br><br>";
	require_once "includes/footer.php";

?>