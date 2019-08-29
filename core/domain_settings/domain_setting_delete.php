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
 Portions created by the Initial Developer are Copyright (C) 2008-2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('domain_setting_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//delete the record
	if (is_uuid($_GET["id"]) && is_uuid($_GET["domain_uuid"])) {
		//set the variables
			$domain_setting_uuid = $_GET["id"];
			$domain_uuid = $_GET["domain_uuid"];

		//delete domain_setting
			$array['domain_settings'][0]['domain_setting_uuid'] = $domain_setting_uuid;
			$array['domain_settings'][0]['domain_uuid'] = $domain_uuid;

			$database = new database;
			$database->app_name = 'domain_settings';
			$database->app_uuid = 'b31e723a-bf70-670c-a49b-470d2a232f71';
			$database->delete($array);
			unset($array);

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header("Location: ".PROJECT_PATH."core/domains/domain_edit.php?id=".$domain_uuid);
	return;

?>
