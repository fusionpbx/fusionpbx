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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
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
		$format = 'text'; //html, text
	}
	else {
		include "root.php";
		require_once "resources/require.php";
		require_once "resources/check_auth.php";
		if (permission_exists('upgrade_schema') || permission_exists('upgrade_source') || if_group("superadmin")) {
			//echo "access granted";
		}
		else {
			echo "access denied";
			exit;
		}
		$format = 'html';
	}

//add multi-lingual support
	require_once "resources/classes/text.php";
	$language = new text;
	$text = $language->get();

//show the title
	if ($format == 'text') {
		echo "\n";
		echo $text['label-upgrade']."\n";
		echo "-----------------------------------------\n";
		echo "\n";
		echo $text['label-database']."\n";
	}

//make sure the database schema and installation have performed all necessary tasks
	require_once "resources/classes/schema.php";
	$obj = new schema;
	echo $obj->schema("text");

//run all app_defaults.php files
	require_once "resources/classes/domains.php";
	$domain = new domains;
	$domain->upgrade();

//show the content
	if ($format == 'html') {
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
	elseif ($format == 'text') {
		echo "\n";
	}

//include the footer
	if ($format == "html") {
		require_once "resources/footer.php";
	}

?>