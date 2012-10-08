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
include "root.php";
if (!file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/includes/config.php")){
	header("Location: ".PROJECT_PATH."/install.php");
	exit;
}
require_once "includes/require.php";
require_once "includes/checkauth.php";
require_once "includes/header.php";

echo "<br />";
echo "<br />";

//add multi-lingual support
	echo "<!--\n";
	require_once "app_languages.php";
	echo "-->\n";
	foreach($content as $key => $value) {
		$content[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//information
	//echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
	//echo "  <tr>\n";
	//echo "	<td align='left'><b>Information</b><br>\n";
	//echo "		The following links are for convenience access to the user account settings, and voicemail.<br />\n";
	//echo "	</td>\n";
	//echo "  </tr>\n";
	//echo "</table>\n";
	//echo "<br />\n";

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"7\" cellspacing=\"0\">\n";
	echo "<tr>\n";
	echo "	<th class='th' colspan='2' align='left'>".$content['title-table']."&nbsp;</th>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
	echo "		".$content['label-name'].": \n";
	echo "	</td>\n";
	echo "	<td class=\"row_style1\">\n";
	echo "		<a href='".PROJECT_PATH."/app/users/usersupdate.php'>".$_SESSION["username"]."</a> \n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
	echo "		".$content['label-voicemail'].": \n";
	echo "	</td>\n";
	echo "	<td class=\"row_style1\">\n";
	echo "		<a href='".PROJECT_PATH."/app/voicemail_msgs/v_voicemail_msgs.php'>".$content['label-view-messages']."</a> \n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<br />\n";
	echo "<br />\n";

//call forward, follow me and dnd
	if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/calls/v_calls.php")) {
		if (permission_exists('follow_me') || permission_exists('call_forward') || permission_exists('do_not_disturb')) {
			$is_included = "true";
			require_once "app/calls/v_calls.php";
		}
	}

//call forward, follow me and dnd
	if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/hunt_group/v_hunt_group_call_forward.php")) {
		if (permission_exists('hunt_group_call_forward')) {
			$is_included = "true";
			require_once "app/hunt_group/v_hunt_group_call_forward.php";
		}
	}

//show the footer
	require_once "includes/footer.php";
?>