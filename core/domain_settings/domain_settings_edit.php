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
if (permission_exists('domain_setting_add') || permission_exists('domain_setting_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$domain_setting_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

if (strlen($_GET["domain_uuid"]) > 0) {
	$domain_uuid = check_str($_GET["domain_uuid"]);
}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$domain_setting_category = check_str($_POST["domain_setting_category"]);
		$domain_setting_subcategory = check_str($_POST["domain_setting_subcategory"]);
		$domain_setting_name = check_str($_POST["domain_setting_name"]);
		$domain_setting_value = check_str($_POST["domain_setting_value"]);
		$domain_setting_enabled = check_str($_POST["domain_setting_enabled"]);
		$domain_setting_description = check_str($_POST["domain_setting_description"]);		
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$domain_setting_uuid = check_str($_POST["domain_setting_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($domain_setting_category) == 0) { $msg .= "Please provide: Category<br>\n"; }
		//if (strlen($domain_setting_subcategory) == 0) { $msg .= "Please provide: Subcategory<br>\n"; }
		//if (strlen($domain_setting_name) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($domain_setting_value) == 0) { $msg .= "Please provide: Value<br>\n"; }
		//if (strlen($domain_setting_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($domain_setting_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
			if ($action == "add" && permission_exists('domain_setting_add')) {
				$sql = "insert into v_domain_settings ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "domain_setting_uuid, ";
				$sql .= "domain_setting_category, ";
				$sql .= "domain_setting_subcategory, ";
				$sql .= "domain_setting_name, ";
				$sql .= "domain_setting_value, ";
				$sql .= "domain_setting_enabled, ";
				$sql .= "domain_setting_description ";	
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'".uuid()."', ";
				$sql .= "'$domain_setting_category', ";
				$sql .= "'$domain_setting_subcategory', ";
				$sql .= "'$domain_setting_name', ";
				$sql .= "'$domain_setting_value', ";
				$sql .= "'$domain_setting_enabled', ";
				$sql .= "'$domain_setting_description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=domains_edit.php?id=$domain_uuid\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('domain_setting_edit')) {
				$sql = "update v_domain_settings set ";
				$sql .= "domain_setting_category = '$domain_setting_category', ";
				$sql .= "domain_setting_subcategory = '$domain_setting_subcategory', ";
				$sql .= "domain_setting_name = '$domain_setting_name', ";
				$sql .= "domain_setting_value = '$domain_setting_value', ";
				$sql .= "domain_setting_enabled = '$domain_setting_enabled', ";
				$sql .= "domain_setting_description = '$domain_setting_description' ";	
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and domain_setting_uuid = '$domain_setting_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=domains_edit.php?id=$domain_uuid\">\n";
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
		$domain_setting_uuid = check_str($_GET["id"]);
		$sql = "select * from v_domain_settings ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and domain_setting_uuid = '$domain_setting_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_setting_category = $row["domain_setting_category"];
			$domain_setting_subcategory = $row["domain_setting_subcategory"];
			$domain_setting_name = $row["domain_setting_name"];
			$domain_setting_value = $row["domain_setting_value"];
			$domain_setting_enabled = $row["domain_setting_enabled"];
			$domain_setting_description = $row["domain_setting_description"];
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
	echo "<td align='left' width='30%' nowrap='nowrap'><b>Domain Setting</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='domains_edit.php?id=$domain_uuid'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "Settings used for each domain.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Category:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='domain_setting_category' maxlength='255' value=\"$domain_setting_category\">\n";
	echo "<br />\n";
	echo "Enter the category.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Subcategory:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='domain_setting_subcategory' maxlength='255' value=\"$domain_setting_subcategory\">\n";
	echo "<br />\n";
	echo "Enter the subcategory.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Type:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='domain_setting_name' maxlength='255' value=\"$domain_setting_name\">\n";
	echo "<br />\n";
	echo "Enter the type.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Value:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	$category = $row['domain_setting_category'];
	$subcategory = $row['domain_setting_subcategory'];
	$name = $row['domain_setting_name'];
	if ($category == "domain" && $subcategory == "menu" && $name == "uuid" ) {
		echo "		<select id='domain_setting_value' name='domain_setting_value' class='formfld' style=''>\n";
		echo "		<option value=''></option>\n";
		$sql = "";
		$sql .= "select * from v_menus ";
		$sql .= "order by menu_language, menu_name asc ";
		$sub_prep_statement = $db->prepare(check_sql($sql));
		$sub_prep_statement->execute();
		$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($sub_result as $sub_row) {
			if (strtolower($row['domain_setting_value']) == strtolower($sub_row["menu_uuid"])) {
				echo "		<option value='".strtolower($sub_row["menu_uuid"])."' selected='selected'>".$sub_row["menu_language"]." - ".$sub_row["menu_name"]."\n";
			}
			else {
				echo "		<option value='".strtolower($sub_row["menu_uuid"])."'>".$sub_row["menu_language"]." - ".$sub_row["menu_name"]."</option>\n";
			}
		}
		unset ($sub_prep_statement);
		echo "		</select>\n";
	} elseif ($category == "domain" && $subcategory == "template" && $name == "name" ) {
		echo "		<select id='domain_setting_value' name='domain_setting_value' class='formfld' style=''>\n";
		echo "		<option value=''></option>\n";
		//add all the themes to the list
		$theme_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';
		if ($handle = opendir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes')) {
			while (false !== ($dir_name = readdir($handle))) {
				if ($dir_name != "." && $dir_name != ".." && $dir_name != ".svn" && is_dir($theme_dir.'/'.$dir_name)) {
					$dir_label = str_replace('_', ' ', $dir_name);
					$dir_label = str_replace('-', ' ', $dir_label);
					if ($dir_name == $row['domain_setting_value']) {
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
	} elseif ($category == "domain" && $subcategory == "time" && $name == "zone" ) {
			echo "		<select id='domain_setting_value' name='domain_setting_value' class='formfld' style=''>\n";
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
				if ($val == $row['domain_setting_value']) {
					echo "			<option value='".$val."' selected='selected'>".$val."</option>\n";
				}
				else {
					echo "			<option value='".$val."'>".$val."</option>\n";
				}
				$previous_category = $category;
				$x++;
			}
			echo "		</select>\n";
			break;
	}
	elseif ($category == "email" && $subcategory == "smtp_password" && $name == "var" ) {
		echo "	<input class='formfld' type='password' name='default_setting_value' maxlength='255' value=\"".$row['default_setting_value']."\">\n";
	}
	elseif ($category == "provision" && $subcategory == "password" && $name == "var" ) {
		echo "	<input class='formfld' type='password' name='default_setting_value' maxlength='255' value=\"".$row['default_setting_value']."\">\n";
	} else {
			echo "	<input class='formfld' type='text' name='domain_setting_value' maxlength='255' value=\"$domain_setting_value\">\n";
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
	echo "    <select class='formfld' name='domain_setting_enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($domain_setting_enabled == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($domain_setting_enabled == "false") { 
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
	echo "	<input class='formfld' type='text' name='domain_setting_description' maxlength='255' value=\"$domain_setting_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "				<input type='hidden' name='domain_uuid' value='$domain_uuid'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='domain_setting_uuid' value='$domain_setting_uuid'>\n";
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