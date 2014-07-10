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

//check the permission
	if(defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/core\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		require_once "resources/require.php";
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		$response_format = 'text'; //html, text

		//add multi-lingual support
			require_once "app_languages.php";
			foreach($text as $key => $value) {
				$text[$key] = $value[$_SESSION['domain']['language']['code']];
			}
	}
	else {
		include "root.php";
		require_once "resources/require.php";
		require_once "resources/check_auth.php";
		if (permission_exists('upgrade_schema') || permission_exists('upgrade_svn') || if_group("superadmin")) {
			//echo "access granted";
		}
		else {
			echo "access denied";
			exit;
		}

		//add multi-lingual support
			require_once "app_languages.php";
			foreach($text as $key => $value) {
				$text[$key] = $value[$_SESSION['domain']['language']['code']];
			}

	}

//set the default
	if (!isset($response_output)) {
		$response_output = "echo";
	}

//include the header
	if ($response_output == "echo") {
		require_once "resources/header.php";
	}

if ($response_format == 'text') {
	echo "\n";
	echo $text['label-upgrade']."\n";
	echo "-----------------------------------------\n";
	echo "\n";
	echo $text['label-database']."\n";
}

//upgrade the database schema
	require_once "core/upgrade/upgrade_schema.php";

//show the content
	if ($response_format == 'html') {
		echo "<div align='center'>\n";
		echo "<table width='40%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>".$text['header-message']."</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'><strong>".$text['message-upgrade']."</strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";

		echo "<br />\n";
		echo "<br />\n";
		echo "<br />\n";
		echo "<br />\n";
		echo "<br />\n";
		echo "<br />\n";
		echo "<br />\n";
	}
	elseif ($response_format == 'text') {
		echo "\n";
	}

//include the footer
	if ($response_output == "echo") {
		require_once "resources/footer.php";
	}
?>