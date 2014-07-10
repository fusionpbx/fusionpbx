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

//include the root directory
	include "root.php";

//if config.php file does not exist then redirect to the install page
	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/resources/config.php")) {
		//do nothing
	} elseif (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/resources/config.php")) {
		//original directory
	} elseif (file_exists("/etc/fusionpbx/config.php")){
		//linux
	} elseif (file_exists("/usr/local/etc/fusionpbx/config.php")){
		//bsd
	} else {
		header("Location: ".PROJECT_PATH."/resources/install.php");
		exit;
	}

//additional includes
	require_once "resources/check_auth.php";
	load_extensions();

//disable login message
	if ($_GET['msg'] == 'dismiss') {
		unset($_SESSION['login']['message']['text']);

		$sql = "update v_default_settings ";
		$sql .= "set default_setting_enabled = 'false' ";
		$sql .= "where ";
		$sql .= "default_setting_category = 'login' ";
		$sql .= "and default_setting_subcategory = 'message' ";
		$sql .= "and default_setting_name = 'text' ";
		$db->exec(check_sql($sql));
		unset($sql);
	}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

// load header
	require_once "resources/header.php";
	$document['title'] = $text['title-user_dashboard'];

	echo "<br><b>".$text['header-user_dashboard']."</b><br>";
	echo $text['description-user_dashboard'];


//display login message
	if (if_group("superadmin") && $_SESSION['login']['message']['text'] != '') {
		echo "<br /><br /><br />";
		echo "<div class='login_message' width='100%'><b>".$text['login-message_attention']."</b>&nbsp;&nbsp;".$_SESSION['login']['message']['text']."&nbsp;&nbsp;(<a href='?msg=dismiss'>".$text['login-message_dismiss']."</a>)</div>";
	}

//start the user table
	echo "<br />";
	echo "<br />";
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
	echo "<tr>\n";
	echo "	<th class='th' colspan='2' align='left'>".$text['title-user-settings']." &nbsp;</th>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
	echo "		".$text['label-username'].": \n";
	echo "	</td>\n";
	echo "	<td class=\"row_style1\">\n";
	echo "		<a href='".PROJECT_PATH."/core/user_settings/user_edit.php'>".$_SESSION["username"]."</a> \n";
	echo "	</td>\n";
	echo "</tr>\n";

//voicemail
	if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/voicemails/voicemail_messages.php")) {
		echo "<tr>\n";
		echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
		echo "		".$text['label-voicemail'].": \n";
		echo "	</td>\n";
		echo "	<td class=\"row_style1\">\n";
		echo "		<a href='".PROJECT_PATH."/app/voicemails/voicemail_messages.php'>".$text['label-view-messages']."</a> \n";
		echo "	</td>\n";
		echo "</tr>\n";
	}

//end the table
	echo "</table>\n";
	echo "<br />\n";
	echo "<br />\n";

//call forward, follow me and dnd
	if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/calls/calls.php")) {
		if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
			$is_included = "true";
			require_once "app/calls/calls.php";
		}
	}

//ring group forward
	if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/ring_groups/ring_group_forward.php")) {
		if (permission_exists('ring_group_forward')) { //ring_group_forward
			$is_included = "true";
			require_once "app/ring_groups/ring_group_forward.php";
		}
	}

//show the footer
	require_once "resources/footer.php";
?>