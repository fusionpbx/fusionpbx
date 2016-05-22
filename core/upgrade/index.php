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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
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
	!permission_exists('upgrade_source') &&
	!permission_exists('upgrade_schema') &&
	!permission_exists('upgrade_apps') &&
	!permission_exists('menu_restore') &&
	!permission_exists('group_edit')
	) {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

if (sizeof($_POST) > 0) {

	$do = $_POST['do'];

	// run source update
	if ($do["source"] && permission_exists("upgrade_source") && !is_dir("/usr/share/examples/fusionpbx")) {
		chdir($_SERVER["PROJECT_ROOT"]);
		exec("git pull", $response_source_update);
		$update_failed = true;
		if (sizeof($response_source_update) > 0) {
			$_SESSION["response_source_update"] = $response_source_update;
			foreach ($response_source_update as $response_line) {
				if (substr_count($response_line, "Updating ") > 0 || substr_count($response_line, "Already up-to-date.") > 0) {
					$update_failed = false;
				}
			}
		}
		if ($update_failed) {
			$_SESSION["message_delay"] = 3500;
			$_SESSION["message_mood"] = 'negative';
			$response_message = $text['message-upgrade_source_failed'];
		}
	}

	// load an array of the database schema and compare it with the active database
	if ($do["schema"] && permission_exists("upgrade_schema")) {
		$response_message = $text['message-upgrade_schema'];

		$upgrade_data_types = check_str($do["data_types"]);
		require_once "resources/classes/schema.php";
		$obj = new schema();
		$_SESSION["schema"]["response"] = $obj->schema("html");
	}

	// process the apps defaults
	if ($do["apps"] && permission_exists("upgrade_apps")) {
		$response_message = $text['message-upgrade_apps'];

		require_once "resources/classes/domains.php";
		$domain = new domains;
		$domain->upgrade();
	}

	// restore defaults of the selected menu
	if ($do["menu"] && permission_exists("menu_restore")) {
		$sel_menu = explode('|', check_str($_POST["sel_menu"]));
		$menu_uuid = $sel_menu[0];
		$menu_language = $sel_menu[1];
		$included = true;
		require_once("core/menu/menu_restore_default.php");
		unset($sel_menu);
		$response_message = $text['message-upgrade_menu'];
	}

	// restore default permissions
	if ($do["permissions"] && permission_exists("group_edit")) {
		$included = true;
		require_once("core/users/permissions_default.php");
		$response_message = "Permission Defaults Restored";
	}

	if (sizeof($_POST['do']) > 1) {
		$response_message = $text['message-upgrade'];
	}

	$_SESSION["message"] = $response_message;
	header("Location: ".PROJECT_PATH."/core/upgrade/index.php");
	exit;

} // end if


require_once "resources/header.php";
$document['title'] = $text['title-upgrade'];

echo "<b>".$text['header-upgrade']."</b>";
echo "<br><br>";
echo $text['description-upgrade'];
echo "<br><br>";

echo "<form name='frm' method='post' action=''>\n";

if (permission_exists("upgrade_source") && !is_dir("/usr/share/examples/fusionpbx") && is_writeable($_SERVER["PROJECT_ROOT"]."/.git")) {
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		".$text['label-upgrade_source'];
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<label for='do_source'><input type='checkbox' name='do[source]' id='do_source' value='1'> ".$text['description-upgrade_source']."</label>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

if (permission_exists("upgrade_schema")) {
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		".$text['label-upgrade_schema'];
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<label for='do_schema'><input type='checkbox' name='do[schema]' id='do_schema' value='1' onchange=\"$('#do_data_types').prop('checked', false); $('#tr_data_types').slideToggle('fast');\"> ".$text['description-upgrade_schema']."</label>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<div id='tr_data_types' style='display: none;'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		".$text['label-upgrade_data_types'];
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<label for='do_data_types'><input type='checkbox' name='do[data_types]' id='do_data_types' value='true'> ".$text['description-upgrade_data_types']."</label>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</div>\n";
}

if (permission_exists("upgrade_apps")) {
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		".$text['label-upgrade_apps'];
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<label for='do_apps'><input type='checkbox' name='do[apps]' id='do_apps' value='1'> ".$text['description-upgrade_apps']."</label>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

if (permission_exists("menu_restore")) {
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		".$text['label-upgrade_menu'];
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<label for='do_menu'>";
	echo 			"<input type='checkbox' name='do[menu]' id='do_menu' value='1' onchange=\"$('#sel_menu').fadeToggle('fast');\">";
	echo 			"<select name='sel_menu' id='sel_menu' class='formfld' style='display: none; vertical-align: middle; margin-left: 5px;'>";
	$sql = "select * from v_menus ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		echo "<option value='".$row["menu_uuid"]."|".$row["menu_language"]."'>".$row["menu_name"]."</option>";
	}
	unset ($sql, $result, $prep_statement);
	echo 			"</select>";
	echo 			" ".$text['description-upgrade_menu'];
	echo 		"</label>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

if (permission_exists("group_edit")) {
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td width='30%' class='vncell'>\n";
	echo "		".$text['label-upgrade_permissions'];
	echo "	</td>\n";
	echo "	<td width='70%' class='vtable' style='height: 50px;'>\n";
	echo "		<label for='do_permissions'><input type='checkbox' name='do[permissions]' id='do_permissions' value='1'> ".$text['description-upgrade_permissions']."</label>\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

echo "<br>";
echo "<div style='text-align: right;'><input type='submit' class='btn' value='".$text['button-upgrade_execute']."'></div>";
echo "<br><br>";
echo "</form>\n";

// output result of source update
if (sizeof($_SESSION["response_source_update"]) > 0) {
	echo "<br />";
	echo "<b>".$text['header-source_update_results']."</b>";
	echo "<br /><br />";
	echo "<pre>";
	echo implode("\n", $_SESSION["response_source_update"]);
	echo "</pre>";
	echo "<br /><br />";
	unset($_SESSION["response_source_update"]);
}

// output result of upgrade schema
if ($_SESSION["schema"]["response"] != '') {
	echo "<br />";
	echo "<b>".$text['header-upgrade_schema_results']."</b>";
	echo "<br /><br />";
	echo $_SESSION["schema"]["response"];
	unset($_SESSION["schema"]["response"]);
}

require_once "resources/footer.php";
?>