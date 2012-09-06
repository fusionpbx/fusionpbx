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
if (permission_exists('menu_add') || permission_exists('menu_edit') || permission_exists('menu_delete')) {
	//access granted
}
else {
	echo "access denied";
	return;
}

//include the header
	require_once "includes/header.php";

//get the menu_uuid
	$menu_uuid = check_str($_REQUEST["id"]);
	$menu_item_uuid = check_str($_REQUEST['menu_item_uuid']);
	$group_name = check_str($_REQUEST['group_name']);

//delete the group from the user
	if ($_REQUEST["a"] == "delete" && permission_exists("menu_delete")) {
		//delete the group from the users
			$sql = "delete from v_menu_item_groups  ";
			$sql .= "where menu_uuid = '".$menu_uuid."' ";
			$sql .= "and menu_item_uuid = '".$menu_item_uuid."' ";
			$sql .= "and group_name = '".$group_name."' ";
			$db->exec(check_sql($sql));
		//redirect the browser
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=menu_item_edit.php?id=$menu_uuid&menu_item_uuid=$menu_item_uuid&menu_uuid=$menu_uuid\">\n";
			echo "<div align='center'>\n";
			echo "Delete Complete\n";
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
	}

//action add or update
	if (isset($_REQUEST["menu_item_uuid"])) {
		if (strlen($_REQUEST["menu_item_uuid"]) > 0) {
			$action = "update";
			$menu_item_uuid = check_str($_REQUEST["menu_item_uuid"]);
		}
		else {
			$action = "add";
		}
	}
	else {
		$action = "add";
	}

//clear the menu session so it will rebuild with the update
	$_SESSION["menu"] = "";

//get the HTTP POST variables and set them as PHP variables
	if (count($_POST)>0) {
		$menu_uuid = check_str($_POST["menu_uuid"]);
		$menu_item_uuid = check_str($_POST["menu_item_uuid"]);
		$menu_item_title = check_str($_POST["menu_item_title"]);
		$menu_item_link = check_str($_POST["menu_item_link"]);
		$menu_item_category = check_str($_POST["menu_item_category"]);
		$menu_item_description = check_str($_POST["menu_item_description"]);
		$menu_item_protected = check_str($_POST["menu_item_protected"]);
		//$menu_item_uuid = check_str($_POST["menu_item_uuid"]);
		$menu_item_parent_uuid = check_str($_POST["menu_item_parent_uuid"]);
		$menu_item_order = check_str($_POST["menu_item_order"]);
	}

//when a HTTP POST is available then process it
	if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

		if ($action == "update") {
			$menu_item_uuid = check_str($_POST["menu_item_uuid"]);
		}

		//check for all required data
			$msg = '';
			if (strlen($menu_item_title) == 0) { $msg .= "Please provide: title<br>\n"; }
			if (strlen($menu_item_category) == 0) { $msg .= "Please provide: category<br>\n"; }
			//if (strlen($menu_item_link) == 0) { $msg .= "Please provide: menu_item_link<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "includes/header.php";
				require_once "includes/persistformvar.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			}

		//add or update the database
		if ($_POST["persistformvar"] != "true") {
			//add a menu item
				if ($action == "add" && permission_exists('menu_add')) {
					if (strlen($menu_item_parent_uuid) == 0) {
						$sql = "SELECT menu_item_order FROM v_menu_items ";
						$sql .= "where menu_uuid = '$menu_uuid' ";
						$sql .= "and menu_item_parent_uuid = '$menu_item_parent_uuid' ";
						$sql .= "order by menu_item_order desc ";
						$sql .= "limit 1 ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
						$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
						foreach ($result as &$row) {
							$highest_menu_item_order = $row['menu_item_order'];
						}
						unset($prep_statement);
					}

					$menu_item_uuid = uuid();
					$sql = "insert into v_menu_items ";
					$sql .= "(";
					$sql .= "menu_uuid, ";
					$sql .= "menu_item_title, ";
					$sql .= "menu_item_link, ";
					$sql .= "menu_item_category, ";
					$sql .= "menu_item_description, ";
					$sql .= "menu_item_protected, ";
					$sql .= "menu_item_uuid, ";
					$sql .= "menu_item_parent_uuid, ";
					if (strlen($menu_item_parent_uuid) == 0) {
						$sql .= "menu_item_order, ";
					}
					$sql .= "menu_item_add_user, ";
					$sql .= "menu_item_add_date ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$menu_uuid', ";
					$sql .= "'$menu_item_title', ";
					$sql .= "'$menu_item_link', ";
					$sql .= "'$menu_item_category', ";
					$sql .= "'$menu_item_description', ";
					$sql .= "'$menu_item_protected', ";
					$sql .= "'".$menu_item_uuid."', ";
					if (strlen($menu_item_parent_uuid) == 0) {
						$sql .= "null, ";
						$sql .= "'".($highest_menu_item_order+1)."', ";
					}
					else {
						$sql .= "'$menu_item_parent_uuid', ";
					}
					$sql .= "'".$_SESSION["username"]."', ";
					$sql .= "now() ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
				}

			//update the menu item
				if ($action == "update" && permission_exists('menu_edit')) {
					$sql  = "update v_menu_items set ";
					$sql .= "menu_item_title = '$menu_item_title', ";
					$sql .= "menu_item_link = '$menu_item_link', ";
					$sql .= "menu_item_category = '$menu_item_category', ";
					$sql .= "menu_item_description = '$menu_item_description', ";
					$sql .= "menu_item_protected = '$menu_item_protected', ";
					if (strlen($menu_item_parent_uuid) == 0) {
						$sql .= "menu_item_parent_uuid = null, ";
						$sql .= "menu_item_order = '$menu_item_order', ";
					}
					else {
						$sql .= "menu_item_parent_uuid = '$menu_item_parent_uuid', ";
					}
					$sql .= "menu_item_mod_user = '".$_SESSION["username"]."', ";
					$sql .= "menu_item_mod_date = now() ";
					$sql .= "where menu_uuid = '$menu_uuid' ";
					$sql .= "and menu_item_uuid = '$menu_item_uuid' ";
					$count = $db->exec(check_sql($sql));
				}

			//add a group to the menu
				if ($_REQUEST["a"] != "delete" && strlen($group_name) > 0 && permission_exists('menu_add')) {
					//add the group to the menu
						if (strlen($menu_item_uuid) > 0) {
							$sql_insert = "insert into v_menu_item_groups ";
							$sql_insert .= "(";
							$sql_insert .= "menu_uuid, ";
							$sql_insert .= "menu_item_uuid, ";
							$sql_insert .= "group_name ";
							$sql_insert .= ")";
							$sql_insert .= "values ";
							$sql_insert .= "(";
							$sql_insert .= "'".$menu_uuid."', ";
							$sql_insert .= "'".$menu_item_uuid."', ";
							$sql_insert .= "'".$group_name."' ";
							$sql_insert .= ")";
							$db->exec($sql_insert);
						}
				}

			//redirect the user
				require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=menu_item_edit.php?id=$menu_uuid&menu_item_uuid=$menu_item_uuid&menu_uuid=$menu_uuid\">\n";
					echo "<div align='center'>\n";
					if ($action == "add") {
						echo "Add Complete\n";
					}
					if ($action == "update") {
						echo "Edit Complete\n";
					}
					echo "</div>\n";
					require_once "includes/footer.php";
					return;
		} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$menu_item_uuid = $_GET["menu_item_uuid"];

		$sql = "select * from v_menu_items ";
		$sql .= "where menu_uuid = '$menu_uuid' ";
		$sql .= "and menu_item_uuid = '$menu_item_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$menu_item_uuid = $row["menu_item_uuid"];
			$menu_item_title = $row["menu_item_title"];
			$menu_item_link = $row["menu_item_link"];
			$menu_item_category = $row["menu_item_category"];
			$menu_item_description = $row["menu_item_description"];
			$menu_item_protected = $row["menu_item_protected"];
			$menu_item_parent_uuid = $row["menu_item_parent_uuid"];
			$menu_item_order = $row["menu_item_order"];
			$menu_item_add_user = $row["menu_item_add_user"];
			$menu_item_add_date = $row["menu_item_add_date"];
			//$menu_item_del_user = $row["menu_item_del_user"];
			//$menu_item_del_date = $row["menu_item_del_date"];
			$menu_item_mod_user = $row["menu_item_mod_user"];
			$menu_item_mod_date = $row["menu_item_mod_date"];
			break; //limit to 1 row
		}
	}

//show the content
	require_once "includes/header.php";
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' action=''>";
	echo "<table width='100%' cellpadding='6' cellspacing='0'>";
	echo "<tr>\n";
	echo "<td width='30%' align='left' valign='top' nowrap><b>Menu Item Edit</b></td>\n";
	echo "<td width='70%' align='right' valign='top'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='menu_edit.php?id=".$menu_uuid."'\" value='Back'><br /><br /></td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncellreq'>Title:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_title' value='$menu_item_title'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncellreq'>Link:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_link' value='$menu_item_link'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncellreq'>Category:</td>";
	echo "		<td class='vtable'>";
	echo "            <select name=\"menu_item_category\" class='formfld'>\n";
	echo "            <option value=\"\"></option>\n";
	if ($menu_item_category == "internal") { echo "<option value=\"internal\" selected>internal</option>\n"; } else { echo "<option value=\"internal\">internal</option>\n"; }
	if ($menu_item_category == "external") { echo "<option value=\"external\" selected>external</option>\n"; } else { echo "<option value=\"external\">external</option>\n"; }
	if ($menu_item_category == "email") { echo "<option value=\"email\" selected>email</option>\n"; } else { echo "<option value=\"email\">email</option>\n"; }
	echo "            </select>";
	echo "        </td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell'>Parent Menu:</td>";
	echo "		<td class='vtable'>";
	$sql = "SELECT * FROM v_menu_items ";
	$sql .= "where menu_uuid = '$menu_uuid' ";
	$sql .= "order by menu_item_title asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	echo "<select name=\"menu_item_parent_uuid\" class='formfld'>\n";
	echo "<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $field) {
			if ($menu_item_parent_uuid == $field['menu_item_uuid']) {
				echo "<option value='".$field['menu_item_uuid']."' selected>".$field['menu_item_title']."</option>\n";
			}
			else {
				echo "<option value='".$field['menu_item_uuid']."'>".$field['menu_item_title']."</option>\n";
			}
	}
	echo "</select>";
	unset($sql, $result);
	echo "        </td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>Groups:</td>";
	echo "		<td class='vtable'>";

	echo "<table width='52%'>\n";
	$sql = "SELECT * FROM v_menu_item_groups ";
	$sql .= "where menu_uuid=:menu_uuid ";
	$sql .= "and menu_item_uuid=:menu_item_uuid ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->bindParam(':menu_uuid', $menu_uuid);
	$prep_statement->bindParam(':menu_item_uuid', $menu_item_uuid);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	foreach($result as $field) {
		if (strlen($field['group_name']) > 0) {
			echo "<tr>\n";
			echo "	<td class='vtable'>".$field['group_name']."</td>\n";
			echo "	<td>\n";
			if (permission_exists('group_member_delete') || if_group("superadmin")) {
				echo "		<a href='menu_item_edit.php?id=".$field['menu_uuid']."&group_name=".$field['group_name']."&menu_item_uuid=".$menu_item_uuid."&menu_item_parent_uuid=".$menu_item_parent_uuid."&a=delete' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
		}
	}
	echo "</table>\n";

	echo "<br />\n";
	$sql = "SELECT * FROM v_groups ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	echo "<select name=\"group_name\" class='frm'>\n";
	echo "<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $field) {
		if ($field['group_name'] == "superadmin") {
			//only show the superadmin group to other users in the superadmin group
			if (if_group("superadmin")) {
				echo "<option value='".$field['group_name']."'>".$field['group_name']."</option>\n";
			}
		}
		else {
			echo "<option value='".$field['group_name']."'>".$field['group_name']."</option>\n";
		}
	}
	echo "</select>";
	echo "<input type=\"submit\" class='btn' value=\"Add\">\n";
	unset($sql, $result);
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Protected:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='menu_item_protected'>\n";
	echo "    <option value=''></option>\n";
	if ($menu_item_protected == "true") { 
		echo "    <option value='true' selected='selected' >true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($menu_item_protected == "false") { 
		echo "    <option value='false' selected='selected' >false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select><br />\n";
	echo "Protect this item in the menu so that is is not removed by 'Restore Default.'<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	if ($action == "update") {
		if ($menu_item_parent_uuid == "") {
			echo "	<tr>";
			echo "		<td class='vncell'>Menu Order:</td>";
			echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_order' value='$menu_item_order'></td>";
			echo "	</tr>";
		}
		//echo "	<tr>";
		//echo "		<td class='vncell'>Added By:</td>";
		//echo "		<td class='vtable'>$menu_item_add_user &nbsp;</td>";
		//echo "	</tr>";
		//echo "	<tr>";
		//echo "		<td class='vncell'>Add Date:</td>";
		//echo "		<td class='vtable'>$menu_item_add_date &nbsp;</td>";
		//echo "	</tr>";
		//echo "	<tr>";
		//echo "		<td class='vncell'>menu_item_del_user:</td>";
		//echo "		<td><input type='text' name='menu_item_del_user' value='$menu_item_del_user'></td>";
		//echo "	</tr>";
		//echo "	<tr>";
		//echo "		<td class='vncell'>menu_item_del_date:</td>";
		//echo "		<td><input type='text' name='menu_item_del_date' value='$menu_item_del_date'></td>";
		//echo "	</tr>";
		//echo "	<tr>";
		//echo "		<td class='vncell'>Modified By:</td>";
		//echo "		<td class='vtable'>$menu_item_mod_user &nbsp;</td>";
		//echo "	</tr>";
		//echo "	<tr>";
		//echo "		<td class='vncell'>Modified Date:</td>";
		//echo "		<td class='vtable'>$menu_item_mod_date &nbsp;</td>";
		//echo "	</tr>";
	}

	echo "	<tr>";
	echo "		<td class='vncell'>Description:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='menu_item_description' value='$menu_item_description'></td>";
	echo "	</tr>";

	if (permission_exists('menu_add') || permission_exists('menu_edit')) {
		echo "	<tr>\n";
		echo "		<td colspan='2' align='right'>\n";
		echo "			<table width='100%'>";
		echo "			<tr>";
		echo "			<td align='left'>";
		echo "			</td>\n";
		echo "			<td align='right'>";
		if ($action == "update") {
			echo "				<input type='hidden' name='menu_item_uuid' value='$menu_item_uuid'>";
		}
		echo "				<input type='hidden' name='menu_uuid' value='$menu_uuid'>";
		echo "				<input type='hidden' name='menu_item_uuid' value='$menu_item_uuid'>";
		echo "				<input type='submit' class='btn' name='submit' value='Save'>\n";
		echo "			</td>";
		echo "			</tr>";
		echo "			</table>";
		echo "		</td>";
		echo "	</tr>";
	}
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
  require_once "includes/footer.php";
?>