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
if (if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$app_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$app_enabled = check_str($_POST["app_enabled"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$app_uuid = check_str($_POST["app_uuid"]);
	}

	//check for all required data
		//if (strlen($app_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
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
			if ($action == "add") {
				$sql = "insert into v_apps ";
				$sql .= "(";
				$sql .= "app_uuid ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".uuid()."' ";
				$sql .= ")";
				//$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=apps.php\">\n";
				echo "<div align='center'>\n";
				echo "Add Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "add")

			if ($action == "update") {
				$sql = "update v_apps set ";
				$sql .= "app_uuid = '$app_uuid' ";
				$sql .= "where app_uuid = '$app_uuid'";
				//$db->exec(check_sql($sql));
				unset($sql);

				require_once "includes/header.php";
				echo "<meta http-equiv=\"refresh\" content=\"2;url=apps.php\">\n";
				echo "<div align='center'>\n";
				echo "Update Complete\n";
				echo "</div>\n";
				require_once "includes/footer.php";
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true") 
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//show the header
	require_once "includes/header.php";

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$app_uuid = $_GET["id"];
		//get the list of installed apps from the core and mod directories
		$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
		$x=0;
		foreach ($config_list as $config_path) {
			include($config_path);
			$x++;
		}
		foreach ($apps as &$row) {
			if ($row["uuid"] == $app_uuid) {
				$name = $row['name'];
				$category = $row['category'];
				$subcategory = $row['subcategory'];
				$version = $row['version'];
				$description = $row['description']['en-us'];
			}
		}
	}

//show the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>$name</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='apps.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "Manage the applications that are installed.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "			Category:\n";
	echo "		</td>\n";
	echo "		<td class='vtable' align='left'>\n";
	echo "			$category &nbsp;\n";
	echo "		</td>\n";
	echo "	</tr>";

	echo "	<tr>\n";
	echo "		<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "			Subcategory:\n";
	echo "		</td>\n";
	echo "		<td class='vtable' align='left'>\n";
	echo "			$subcategory &nbsp;\n";
	echo "		</td>\n";
	echo "	</tr>";

	echo "	<tr>\n";
	echo "		<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "			Version:\n";
	echo "		</td>\n";
	echo "		<td class='vtable' align='left'>\n";
	echo "				$version &nbsp;\n";
	echo "		</td>\n";
	echo "	</tr>";

	echo "	<tr>\n";
	echo "		<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "			Description:\n";
	echo "		</td>\n";
	echo "		<td class='vtable' align='left'>\n";
	echo "				$description &nbsp;\n";
	echo "		</td>\n";
	echo "	</tr>";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='app_uuid' value='$app_uuid'>\n";
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