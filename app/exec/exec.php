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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('exec_command_line') || permission_exists('exec_php_command') || permission_exists('exec_switch')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the html values and set them as variables
	if (count($_POST)>0) {
		$shell_cmd = trim($_POST["shell_cmd"]);
		$php_cmd = trim($_POST["php_cmd"]);
		$switch_cmd = trim($_POST["switch_cmd"]);
	}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-command'];

//edit area
	echo "	<script language=\"javascript\" type=\"text/javascript\" src=\"".PROJECT_PATH."/resources/edit_area/edit_area_full.js\"></script>\n";
	echo "	<script language=\"Javascript\" type=\"text/javascript\">\n";
	echo "		// initialisation //load,\n";
	echo "		editAreaLoader.init({\n";
	echo "			id: \"shell_cmd\"	// id of the textarea to transform //, |, help\n";
	echo "			,start_highlight: false\n";
	echo "			,display: \"later\"\n";
	echo "			,font_size: \"8\"\n";
	echo "			,allow_toggle: true\n";
	echo "			,language: \"en\"\n";
	echo "			,syntax: \"html\"\n";
	echo "			,toolbar: \"search, go_to_line,|, fullscreen, |, undo, redo, |, select_font, |, syntax_selection, |, change_smooth_selection, highlight, reset_highlight, |, help\" //new_document,\n";
	echo "			,plugins: \"charmap\"\n";
	echo "			,charmap_default: \"arrows\"\n";
	echo "		});\n";
	echo "\n";
	echo "		editAreaLoader.init({\n";
	echo "			id: \"php_cmd\"	// id of the textarea to transform //, |, help\n";
	echo "			,start_highlight: false\n";
	echo "			,display: \"later\"\n";
	echo "			,font_size: \"8\"\n";
	echo "			,allow_toggle: true\n";
	echo "			,language: \"en\"\n";
	echo "			,syntax: \"php\"\n";
	echo "			,toolbar: \"search, go_to_line,|, fullscreen, |, undo, redo, |, select_font, |, syntax_selection, |, change_smooth_selection, highlight, reset_highlight, |, help\" //new_document,\n";
	echo "			,plugins: \"charmap\"\n";
	echo "			,charmap_default: \"arrows\"\n";
	echo "		});\n";
	echo "\n";
	echo "		editAreaLoader.init({\n";
	echo "			id: \"switch_cmd\"	// id of the textarea to transform //, |, help\n";
	echo "			,start_highlight: false\n";
	echo "			,display: \"later\"\n";
	echo "			,font_size: \"8\"\n";
	echo "			,allow_toggle: true\n";
	echo "			,language: \"en\"\n";
	echo "			,syntax: \"php\"\n";
	echo "			,toolbar: \"search, go_to_line,|, fullscreen, |, undo, redo, |, select_font, |, syntax_selection, |, change_smooth_selection, highlight, reset_highlight, |, help\" //new_document,\n";
	echo "			,plugins: \"charmap\"\n";
	echo "			,charmap_default: \"arrows\"\n";
	echo "		});\n";
	echo "	</script>";

//show the header
	echo "<b>".$text['label-execute']."</b>\n";
	echo "<br><br>";
	echo $text['description-execute']."\n";
	echo "<br><br>";


//show the result
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
	if (count($_POST)>0) {
		echo "	<tr>\n";
		echo "		<td colspan='2' align=\"left\">\n";

		//shell_cmd
		if (strlen($shell_cmd) > 0 && permission_exists('exec_command_line')) {
			echo "<b>$shell_cmd</b>\n";
			echo "<!--\n";
			$shell_result = shell_exec($shell_cmd);
			echo "-->\n";
			echo "<pre>";
			echo htmlentities($shell_result);
			echo "</pre>\n";
		}

		//php_cmd
		if (strlen($php_cmd) > 0 && permission_exists('exec_php_command')) {
			//echo "<b></b>\n";
			echo "<pre>";
			$php_result = eval($php_cmd);
			echo htmlentities($php_result);
			echo "</pre>\n";
		}

		//fs cmd
		if (strlen($switch_cmd) > 0 && permission_exists('exec_switch')) {
			echo "<b>$switch_cmd</b>\n";
			echo "<pre>";
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				//$switch_result = eval($switch_cmd);
				echo htmlentities($switch_result);
			}
			echo "</pre>\n";
		}
		echo "			<br />\n";
		echo "		</td>\n";
		echo "	</tr>";
	}

//html form
	if (permission_exists('exec_command_line')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-shell']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<textarea name='shell_cmd' id='shell_cmd' rows='2' class='formfld' style='width: 100%;' wrap='off'>$shell_cmd</textarea>\n";
		echo "	<br />\n";
		echo "	".$text['description-shell']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	if (permission_exists('exec_php_command')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-php']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<textarea name='php_cmd' id='php_cmd' rows='7' class='formfld' style='width: 100%;' wrap='off'>$php_cmd</textarea>\n";
		echo "	<br />\n";
		echo "	".$text['description-php']."</a>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	if (permission_exists('exec_switch')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-switch']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<textarea name='switch_cmd' id='switch_cmd' rows='2' class='formfld' style='width: 100%;' wrap='off'>$switch_cmd</textarea>\n";
		echo "	<br />\n";
		echo "	".$text['description-switch']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-execute']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//show the footer
	require_once "resources/footer.php";
?>