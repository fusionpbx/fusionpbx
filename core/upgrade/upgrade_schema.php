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

// set included, if not
	if (!isset($included)) { $included = false; }


//check the permission
	if(defined('STDIN')) {
		$document_root = str_replace("\\", "/", $_SERVER["PHP_SELF"]);
		preg_match("/^(.*)\/core\/.*$/", $document_root, $matches);
		$document_root = $matches[1];
		set_include_path($document_root);
		require_once "resources/require.php";
		$_SERVER["DOCUMENT_ROOT"] = $document_root;
		$response_format = 'text'; //html, text
	}
	else if (!$included) {
		include "root.php";
		require_once "resources/require.php";
		require_once "resources/check_auth.php";
		if (permission_exists('upgrade_schema') || if_group("superadmin")) {
			//echo "access granted";
		}
		else {
			echo "access denied";
			exit;
		}

		require_once "resources/header.php";
		$document['title'] = $text['title-upgrade_schema'];

		$response_format = 'html'; //html, text
	}


//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}


//set the default
	if (!isset($response_output)) {
		$response_output = "echo";
	}


//load the default database into memory and compare it with the active database
	require_once "resources/schema.php";
	$response_upgrade_schema = db_upgrade_schema($db, $db_type, $db_name, $response_output);
	unset($apps);


if (!$included && $response_output == 'echo' && $response_format == 'html') {
	echo "<br />\n";
	echo "<br />\n";
	require_once "resources/footer.php";
}

?>