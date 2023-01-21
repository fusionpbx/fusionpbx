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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

// set included, if not
	if (!isset($included)) { $included = false; }

//check the permission
	if(defined('STDIN')) {
		//set the include path
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);

		//includes files
		require_once "resources/require.php";
		require_once "resources/functions.php";

		//set the format
		$format = 'text'; //html, text
	}
	else if (!$included) {
		//set the include path
		$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
		set_include_path(parse_ini_file($conf[0])['document.root']);

		//includes files
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

		//set the title and format
		$document['title'] = $text['title-upgrade_schema'];
		$format = 'html'; //html, text
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the database schema put it into an array then compare and update the database as needed.
	require_once "resources/classes/schema.php";
	$obj = new schema;
	if (isset($argv[1]) && $argv[1] == 'data_types') {
		$obj->data_types = true;
	}
	echo $obj->schema($format);

//formatting for html
	if (!$included && $format == 'html') {
		echo "<br />\n";
		echo "<br />\n";
		require_once "resources/footer.php";
	}

?>
