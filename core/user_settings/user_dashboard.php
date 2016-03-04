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
	if (file_exists($_SERVER["PROJECT_ROOT"]."/resources/config.php")) {
		//do nothing
	} elseif (file_exists($_SERVER["PROJECT_ROOT"]."/resources/config.php")) {
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
	$language = new text;
	$text = $language->get();

//load header
	require_once "resources/header.php";
	$document['title'] = $text['title-user_dashboard'];

	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
	echo "	<tr>\n";
	echo "		<td valign='top'>";
	echo "			<b>".$text['header-user_dashboard']."</b><br />";
	echo "			".$text['description-user_dashboard'];
	echo "		</td>\n";
	echo "		<td valign='top' style='text-align: right; white-space: nowrap;'>\n";
	echo "			<a href='".PROJECT_PATH."/core/user_settings/user_edit.php'>".$_SESSION["username"]."</a>";
	if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/voicemails/voicemail_messages.php")) {
		echo "		<input type='button' class='btn' value='".$text['button-voicemail_messages']."' style='margin-left: 15px;' onclick=\"document.location.href='".PROJECT_PATH."/app/voicemails/voicemail_messages.php';\">";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br /><br />";

//display login message
	if (if_group("superadmin") && $_SESSION['login']['message']['text'] != '') {
		echo "<br /><br /><br />";
		echo "<div class='login_message' width='100%'><b>".$text['login-message_attention']."</b>&nbsp;&nbsp;".$_SESSION['login']['message']['text']."&nbsp;&nbsp;(<a href='?msg=dismiss'>".$text['login-message_dismiss']."</a>)</div>";
	}

	/*
	$text['title-user-settings']
	$text['label-username']
	$text['label-voicemail']
	*/

//call routing
	if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/calls/calls.php")) {
		if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
			$is_included = "true";
			echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
			echo "	<tr>\n";
			echo "		<td valign='top'><b>".$text['header-call_routing']."</b><br><br></td>\n";
			echo "		<td valign='top' style='text-align: right;'><input id='btn_viewall_callrouting' type='button' class='btn' style='display: none;' value='".$text['button-view_all']."' onclick=\"document.location.href='".PROJECT_PATH."/app/calls/calls.php';\"></td>\n";
			echo "	</tr>\n";
			echo "</table>\n";
			require_once "app/calls/calls.php";
			echo "<br>\n";
		}
	}

//reload language values
	$language = new text;
	$text = $language->get();

//ring group forward
	if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/ring_groups/ring_group_forward.php")) {
		if (permission_exists('ring_group_forward')) { //ring_group_forward
			$is_included = "true";
			echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
			echo "	<tr>\n";
			echo "		<td valign='top'><b>".$text['header-ring_groups']."</b><br><br></td>\n";
			echo "		<td valign='top' style='text-align: right;'><input id='btn_viewall_ringgroups' type='button' class='btn' style='display: none;' value='".$text['button-view_all']."' onclick=\"document.location.href='".PROJECT_PATH."/app/ring_groups/ring_group_forward.php';\"></td>\n";
			echo "	</tr>\n";
			echo "</table>\n";
			require_once "app/ring_groups/ring_group_forward.php";
			echo "<br>\n";
		}
	}

//show the footer
	require_once "resources/footer.php";
?>