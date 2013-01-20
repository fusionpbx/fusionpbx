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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('default_setting_add') || permission_exists('default_setting_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$default_setting_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$default_setting_category = check_str($_POST["default_setting_category"]);
		$default_setting_subcategory = check_str($_POST["default_setting_subcategory"]);
		$default_setting_name = check_str($_POST["default_setting_name"]);
		$default_setting_value = check_str($_POST["default_setting_value"]);
		$default_setting_enabled = check_str($_POST["default_setting_enabled"]);
		$default_setting_description = check_str($_POST["default_setting_description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$default_setting_uuid = check_str($_POST["default_setting_uuid"]);
	}

	//check for all required data
		//if (strlen($default_setting_category) == 0) { $msg .= "Please provide: Category<br>\n"; }
		//if (strlen($default_setting_subcategory) == 0) { $msg .= "Please provide: Subcategory<br>\n"; }
		//if (strlen($default_setting_name) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($default_setting_value) == 0) { $msg .= "Please provide: Value<br>\n"; }
		//if (strlen($default_setting_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($default_setting_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
			if ($action == "add" && permission_exists('default_setting_add')) {
				$sql = "insert into v_default_settings ";
				$sql .= "(";
				$sql .= "default_setting_uuid, ";
				$sql .= "default_setting_category, ";
				$sql .= "default_setting_subcategory, ";
				$sql .= "default_setting_name, ";
				$sql .= "default_setting_value, ";
				$sql .= "default_setting_enabled, ";
				$sql .= "default_setting_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".uuid()."', ";
				$sql .= "'$default_setting_category', ";
				$sql .= "'$default_setting_subcategory', ";
				$sql .= "'$default_setting_name', ";
				$sql .= "'$default_setting_value', ";
				$sql .= "'$default_setting_enabled', ";
				$sql .= "'$default_setting_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=default_settings.php\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('default_setting_edit')) {
				$sql = "update v_default_settings set ";
				$sql .= "default_setting_category = '$default_setting_category', ";
				$sql .= "default_setting_subcategory = '$default_setting_subcategory', ";
				$sql .= "default_setting_name = '$default_setting_name', ";
				$sql .= "default_setting_value = '$default_setting_value', ";
				$sql .= "default_setting_enabled = '$default_setting_enabled', ";
				$sql .= "default_setting_description = '$default_setting_description' ";
				$sql .= "where default_setting_uuid = '$default_setting_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=default_settings.php\">\n";
				echo "<div align='center'>\n";
				echo "Update Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true") 
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$default_setting_uuid = check_str($_GET["id"]);
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_uuid = '$default_setting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$default_setting_category = $row["default_setting_category"];
			$default_setting_subcategory = $row["default_setting_subcategory"];
			$default_setting_name = $row["default_setting_name"];
			$default_setting_value = $row["default_setting_value"];
			$default_setting_enabled = $row["default_setting_enabled"];
			$default_setting_description = $row["default_setting_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Default Setting Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap'><b>Default Setting Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='default_settings.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "Settings used for all domains.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Category:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='default_setting_category' maxlength='255' value=\"$default_setting_category\">\n";
	echo "<br />\n";
	echo "Enter the category.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Subcategory:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='default_setting_subcategory' maxlength='255' value=\"$default_setting_subcategory\">\n";
	echo "<br />\n";
	echo "Enter the subcategory.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='default_setting_name' maxlength='255' value=\"$default_setting_name\">\n";
	echo "<br />\n";
	echo "Enter the name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Value:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$category = $row['default_setting_category'];
	$subcategory = $row['default_setting_subcategory'];
	$name = $row['default_setting_name'];
	if ($category == "domain" && $subcategory == "menu" && $name == "uuid" ) {
		echo "		<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		echo "		<option value=''></option>\n";
		$sql = "";
		$sql .= "select * from v_menus ";
		$sql .= "order by menu_language, menu_name asc ";
		$sub_prep_statement = $db->prepare(check_sql($sql));
		$sub_prep_statement->execute();
		$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($sub_result as $sub_row) {
			if (strtolower($row['default_setting_value']) == strtolower($sub_row["menu_uuid"])) {
				echo "		<option value='".strtolower($sub_row["menu_uuid"])."' selected='selected'>".$sub_row["menu_language"]." - ".$sub_row["menu_name"]."\n";
			}
			else {
				echo "		<option value='".strtolower($sub_row["menu_uuid"])."'>".$sub_row["menu_language"]." - ".$sub_row["menu_name"]."</option>\n";
			}
		}
		unset ($sub_prep_statement);
		echo "		</select>\n";
	} elseif ($category == "domain" && $subcategory == "template" && $name == "name" ) {
		echo "		<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		echo "		<option value=''></option>\n";
		//add all the themes to the list
		$theme_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';
		if ($handle = opendir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes')) {
			while (false !== ($dir_name = readdir($handle))) {
				if ($dir_name != "." && $dir_name != ".." && $dir_name != ".svn" && is_dir($theme_dir.'/'.$dir_name)) {
					$dir_label = str_replace('_', ' ', $dir_name);
					$dir_label = str_replace('-', ' ', $dir_label);
					if ($dir_name == $row['default_setting_value']) {
						echo "		<option value='$dir_name' selected='selected'>$dir_label</option>\n";
					}
					else {
						echo "		<option value='$dir_name'>$dir_label</option>\n";
					}
				}
			}
			closedir($handle);
		}
		echo "		</select>\n";
	} elseif ($category == "email" && $subcategory == "smtp_auth" && $name == "var" ) {
		echo "    <select class='formfld' name='default_setting_value'>\n";
		echo "    <option value=''></option>\n";
		if ($row['default_setting_value'] == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
		}
		else {
		echo "    <option value='true'>true</option>\n";
		}
		if ($row['default_setting_value'] == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
		}
		else {
		echo "    <option value='false'>false</option>\n";
		}
		echo "    </select>\n";
	} elseif ($category == "email" && $subcategory == "smtp_secure" && $name == "var" ) {
		echo "    <select class='formfld' name='default_setting_value'>\n";
		echo "    <option value=''></option>\n";
		if ($row['default_setting_value'] == "none") { 
		echo "    <option value='none' selected='selected'>none</option>\n";
		}
		else {
		echo "    <option value='none'>none</option>\n";
		}
		if ($row['default_setting_value'] == "tls") { 
		echo "    <option value='tls' selected='selected'>tls</option>\n";
		}
		else {
		echo "    <option value='tls'>tls</option>\n";
		}
		if ($row['default_setting_value'] == "ssl") { 
		echo "    <option value='ssl' selected='selected'>ssl</option>\n";
		}
		else {
		echo "    <option value='ssl'>ssl</option>\n";
		}
		echo "    </select>\n";
	} elseif ($category == "domain" && $subcategory == "time_zone" && $name == "name" ) {
		echo "		<select id='default_setting_value' name='default_setting_value' class='formfld' style=''>\n";
		echo "		<option value=''></option>\n";
		//$list = DateTimeZone::listAbbreviations();
		$time_zone_identifiers = DateTimeZone::listIdentifiers();
		$previous_category = '';
		$x = 0;
		foreach ($time_zone_identifiers as $key => $val) {
			$time_zone = explode("/", $val);
			$category = $time_zone[0];
			if ($category != $previous_category) {
				if ($x > 0) {
					echo "		</optgroup>\n";
				}
				echo "		<optgroup label='".$category."'>\n";
			}
			if (strlen($val) > 0) {
				$time_zone_offset = get_time_zone_offset($val)/3600;
				$time_zone_offset_hours = floor($time_zone_offset);
				$time_zone_offset_minutes = ($time_zone_offset - $time_zone_offset_hours) * 60;
				$time_zone_offset_minutes = number_pad($time_zone_offset_minutes, 2);
				if ($time_zone_offset > 0) {
					$time_zone_offset_hours = number_pad($time_zone_offset_hours, 2);
					$time_zone_offset_hours = "+".$time_zone_offset_hours;
				}
				else {
					$time_zone_offset_hours = str_replace("-", "", $time_zone_offset_hours);
					$time_zone_offset_hours = "-".number_pad($time_zone_offset_hours, 2);
				}
			}
			if ($val == $row['default_setting_value']) {
				echo "			<option value='".$val."' selected='selected'>(UTC ".$time_zone_offset_hours.":".$time_zone_offset_minutes.") ".$val."</option>\n";
			}
			else {
				echo "			<option value='".$val."'>(UTC ".$time_zone_offset_hours.":".$time_zone_offset_minutes.") ".$val."</option>\n";
			}
			$previous_category = $category;
			$x++;
		}
		echo "		</select>\n";
	}
	elseif ($category == "email" && $subcategory == "smtp_password" && $name == "var" ) {
		echo "	<input class='formfld' type='password' name='default_setting_value' maxlength='255' value=\"".$row['default_setting_value']."\">\n";
	}
	elseif ($category == "provision" && $subcategory == "password" && $name == "var" ) {
		echo "	<input class='formfld' type='password' name='default_setting_value' maxlength='255' value=\"".$row['default_setting_value']."\">\n";
	} else {
		echo "	<input class='formfld' type='text' name='default_setting_value' maxlength='255' value=\"".$row['default_setting_value']."\">\n";
	}
	echo "<br />\n";
	echo "Enter the value.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='default_setting_enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($default_setting_enabled == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($default_setting_enabled == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Choose to enable or disable the value.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='default_setting_description' maxlength='255' value=\"$default_setting_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='default_setting_uuid' value='$default_setting_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "includes/footer.php";
?>