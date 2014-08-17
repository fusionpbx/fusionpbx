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

//add multi-lingual support
	require_once "core/user_settings/app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//get the http values and set as variables
	$path = check_str($_GET["path"]);
	$msg = check_str($_GET["msg"]);

//set a default login destination
	if (strlen($_SESSION['login']['destination']['url']) == 0) {
		$_SESSION['login']['destination']['url'] = PROJECT_PATH."/core/user_settings/user_dashboard.php";
	}

//add the header
	include "resources/header.php";

//show the message
	if (strlen($msg) > 0) {
		echo "<br><br>";
		echo "<div align='center'>\n";
		echo "<table width='50%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>Message</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'>\n";
		switch ($msg) {
			case "username required":
				echo "<strong>Please provide a username.</strong>";
				break;
			case "incorrect account information":
			   echo "<strong>The username or password was incorrect. Please try again.</strong>";
				break;
			case "install complete":
				echo "<br />\n";
				echo "Installation is complete. <br />";
				echo "<br /> ";
				echo  "<strong>Getting Started:</strong><br /> ";
				echo "<ul><li>There are two levels of admins 1. superadmin 2. admin.<br />";
				echo "<br />\n";
				echo "username: <strong>superadmin</strong> <br />password: <strong>fusionpbx</strong> <br />\n";
				echo "<br />\n";
				echo "username: <strong>admin</strong> <br />password: <strong>fusionpbx</strong> <br/><br/>\n";
				echo "</li>\n";
				echo "<li>\n";
				echo "The database connection settings have been saved to ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/config.php.<br />\n";
				echo "</li>\n";
				echo "</ul>\n";
				echo "<strong>\n";
				break;
		}
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
		echo "<br /><br />\n\n";
	}

//show the content
	echo "<br />\n";
	echo "<form name='login' method='post' action='".$_SESSION['login']['destination']['url']."'>\n";
	echo "<input type='hidden' name='path' value='".$path."'>\n";
	echo "<input type='text' class='formfld' style='text-align: center; min-width: 200px; width: 200px; margin-bottom: 8px;' name='username' id='username' placeholder=\"".$text['label-username']."\"><br />\n";
	echo "<input type='password' class='formfld' style='text-align: center; min-width: 200px; width: 200px; margin-bottom: 8px;' name='password' placeholder=\"".$text['label-password']."\"><br />\n";
	if ($_SESSION['login']['domain_name.visible']['boolean'] == "true") {
		if (count($_SESSION['login']['domain_name']) > 0) {
			echo "<select style='width: 200px; margin-bottom: 8px;' class='formfld' name='domain_name'>\n";
			echo "	<option value=''></option>\n";
			foreach ($_SESSION['login']['domain_name'] as &$row) {
				echo "	<option value='$row'>$row</option>\n";
			}
			echo "</select>\n";
			echo "<br />";
		}
		else {
			echo "<input type='text' class='formfld' style='text-align: center; min-width: 200px; width: 200px; margin-bottom: 8px;' name='domain_name' placeholder=\"".$text['label-domain']."\"><br />\n";
		}
	}
	echo "<br />";
	echo "<input type='submit' class='btn' style='width: 100px; margin-top: 15px;' value='".$text['button-login']."'>\n";
	echo "</form>";
	echo "<script>document.getElementById('username').focus();</script>";

//add the footer
	$default_login = true;
	include "resources/footer.php";

?>
