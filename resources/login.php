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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//add multi-lingual support
	$language = new text;
	$text = $language->get(null,'core/user_settings');

//get the http values and set as variables
	if (isset($_GET["msg"])) { $msg = check_str($_GET["msg"]); } else { $msg = null; }

//set variable if not set
	if (!isset($_SESSION['login']['domain_name_visible']['boolean'])) { $_SESSION['login']['domain_name_visible']['boolean'] = null; }

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
	echo "<div id='login_form'>\n";
	echo "<form name='login' method='post' action='".$_SESSION['login']['destination']['url']."'>\n";
	echo "<input type='text' class='txt login' style='text-align: center; min-width: 200px; width: 200px; margin-bottom: 8px;' name='username' id='username' placeholder=\"".$text['label-username']."\"><br />\n";
	echo "<input type='password' class='txt login' style='text-align: center; min-width: 200px; width: 200px; margin-bottom: 8px;' name='password' placeholder=\"".$text['label-password']."\"><br />\n";
	if ($_SESSION['login']['domain_name_visible']['boolean'] == "true") {
		if (count($_SESSION['login']['domain_name']) > 0) {
			$click_change_color = ($_SESSION['theme']['login_input_text_color']['text'] != '') ? $_SESSION['theme']['login_input_text_color']['text'] : (($_SESSION['theme']['input_text_color']['text'] != '') ? $_SESSION['theme']['input_text_color']['text'] : '#000000');
			$placeholder_color = ($_SESSION['theme']['login_input_text_placeholder_color']['text'] != '') ? 'color: '.$_SESSION['theme']['login_input_text_placeholder_color']['text'].';' : 'color: #999999;';
			echo "<select name='domain_name' class='txt login' style='".$placeholder_color." width: 200px; text-align: center; text-align-last: center; margin-bottom: 8px;' onclick=\"this.style.color='".$click_change_color."';\" onchange=\"this.style.color='".$click_change_color."';\">\n";
			echo "	<option value='' disabled selected hidden>".$text['label-domain']."</option>\n";
			sort($_SESSION['login']['domain_name']);
			foreach ($_SESSION['login']['domain_name'] as &$row) {
				echo "	<option value='$row'>$row</option>\n";
			}
			echo "</select><br />\n";
		}
		else {
			echo "<input type='text' name='domain_name' class='txt login' style='text-align: center; min-width: 200px; width: 200px; margin-bottom: 8px;' placeholder=\"".$text['label-domain']."\"><br />\n";
		}
	}
	echo "<input type='submit' id='btn_login' class='btn' style='width: 100px; margin-top: 15px;' value='".$text['button-login']."'>\n";

	echo "</form>";
	echo "<script>document.getElementById('username').focus();</script>";
	echo "</div>";

//add the footer
	$default_login = true;
	include "resources/footer.php";

?>
