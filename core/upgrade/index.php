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

set_time_limit(600); //sec (10 min)

include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check the permission
if (
	!permission_exists('upgrade_svn') &&
	!permission_exists('upgrade_schema') &&
	!permission_exists('upgrade_domains') &&
	!permission_exists('upgrade_datatypes') &&
	!permission_exists('menu_restore') &&
	!permission_exists('group_edit')
	) {
	echo "access denied";
	exit;
}

if (sizeof($_POST) > 0) {

	$do = $_POST['do'];

	// run svn update
	if ($do["svn"] && permission_exists("upgrade_svn")) {

		$response_message[] = "SVN Updated";
	}

	// load the default database into memory and compare it with the active database
	if ($do["schema"] && permission_exists("upgrade_schema")) {
		$included = true;
		$response_output = "return";
		$response_format = "html";
		require_once "core/upgrade/upgrade_schema.php";
		if ($response_upgrade_schema != '') {
			$_SESSION["response_upgrade_schema"] = $response_upgrade_schema;
		}
		unset($apps);
		$response_message[] = "Schema Upgraded";
	}

	// upgrade the domains
	if ($do["domains"] && permission_exists("upgrade_domains")) {
		$included = true;
		$domain_language_code = $_SESSION['domain']['language']['code'];
		require_once "core/upgrade/upgrade_domains.php";
		$_SESSION['domain']['language']['code'] = $domain_language_code;
		unset($domain_language_code);
		$response_message[] = "Domain(s) Upgraded";
	}

	// syncronize data types
	if ($do["datatypes"] && permission_exists("upgrade_datatypes")) {

		$response_message[] = "Data Types Syncronized";
	}

	// restore defaults of the selected menu
	if ($do["menu"] && permission_exists("menu_restore")) {
		$sel_menu = explode('|', $_POST["sel_menu"]);
		$menu_uuid = $sel_menu[0];
		$menu_language = $sel_menu[1];
		$included = true;
		require_once("core/menu/menu_restore_default.php");
		unset($sel_menu);
		$response_message[] = "Menu Restored";
	}

	// restore default permissions
	if ($do["permissions"] && permission_exists("group_edit")) {
		$included = true;
		require_once("core/users/permissions_default.php");
		$response_message[] = "Permissions Restored";
	}

	$_SESSION["message"] = implode("<br />", $response_message);
	header("Location: ".PROJECT_PATH."/core/upgrade/index.php");
	exit;

} // if


//add multi-lingual support
require_once "app_languages.php";
foreach($text as $key => $value) {
	$text[$key] = $value[$_SESSION['domain']['language']['code']];
}

require_once "resources/header.php";
$document['title'] = $text['title-upgrade'];

echo "<br />";
echo "<b>".$text['header-upgrade']."</b><br>";
echo "Select the upgrade/update/restore actions below you wish to perform.";
echo "<br><br><br>";

echo "<form name='frm' method='post' action=''>\n";
echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

if (permission_exists("upgrade_svn")) {
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		SVN Update";
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<input type='checkbox' class='formfld' name='do[svn]' id='do_svn' value='1'>";
	echo "		<br />\n";
	echo "	</td>\n";
	echo "</tr>\n";
}

if (permission_exists("upgrade_schema")) {
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		Upgrade Schema";
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<input type='checkbox' class='formfld' name='do[schema]' id='do_schema' value='1'>";
	echo "		<br />\n";
	echo "	</td>\n";
	echo "</tr>\n";
}

if (permission_exists("upgrade_domains")) {
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		Upgrade Domain(s)";
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<input type='checkbox' class='formfld' name='do[domains]' id='do_domains' value='1'>";
	echo "		<br />\n";
	echo "	</td>\n";
	echo "</tr>\n";
}

if (permission_exists("upgrade_datatypes")) {
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		Syncronize Data Types";
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<input type='checkbox' class='formfld' name='do[datatypes]' id='do_datatypes' value='1'>";
	echo "		<br />\n";
	echo "	</td>\n";
	echo "</tr>\n";
}

if (permission_exists("menu_restore")) {
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		Restore Menu Defaults";
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<table cellpadding='0' cellspacing='0' border='0'>";
	echo "			<tr><td>";
	echo "				<input type='checkbox' class='formfld' name='do[menu]' id='do_menu' value='1' onclick=\"$('#sel_menu').fadeToggle('fast');\">";
	echo "			</td><td>";
	echo "				<select name='sel_menu' id='sel_menu' class='formfld' style='display: none; margin-left: 15px;'>\n";
		$sql = "select * from v_menus ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			echo "<option value='".$row["menu_uuid"]."|".$row["menu_language"]."'>".$row["menu_name"]."</option>\n";
		}
		unset ($sql, $result, $prep_statement);
	echo "				</select>\n";
	echo "			</td></tr>";
	echo "		</table>";
	echo "	</td>\n";
	echo "</tr>\n";
}

if (permission_exists("group_edit")) {
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		Restore Permission Defaults";
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<input type='checkbox' class='formfld' name='do[permissions]' id='do_permissions' value='1'>";
	echo "		<br />\n";
	echo "	</td>\n";
	echo "</tr>\n";
}

echo "<tr>\n";
echo "	<td colspan='2' style='text-align: right;'>\n";
echo "		<input type='submit' class='btn' value='Execute'>\n";
echo "	</td>\n";
echo "</tr>\n";


echo "</table>\n";
echo "</form>\n";


// output result of upgrade schema
if ($_SESSION["response_upgrade_schema"] != '') {
	echo "<br /><br /><br />";
	echo "<b>".$text['header-upgrade_schema_results']."</b>";
	echo "<br /><br />";
	echo $_SESSION["response_upgrade_schema"];
	unset($_SESSION["response_upgrade_schema"]);
}

require_once "resources/footer.php";
?>