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
	if (permission_exists('ivr_menu_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	$ivr_menu_uuid = $_GET["id"];

//delete the ivr menu
	if (is_uuid($ivr_menu_uuid)) {

		//get the dialplan_uuid
			$sql = "select * from v_ivr_menus ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and ivr_menu_uuid = :ivr_menu_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['ivr_menu_uuid'] = $ivr_menu_uuid;
			$database = new database;
			$result = $database->select($sql, $parameters);
			if (is_array($result)) {
				foreach ($result as &$row) {
					$dialplan_uuid = $row["dialplan_uuid"];
					$ivr_menu_context = $row["ivr_menu_context"];
				}
			}
			unset($sql, $parameters, $result, $row);

		//add the dialplan permission
			$p = new permissions;
			$p->add('dialplan_delete', 'temp');

		//delete the data
			$array['dialplans'][]['dialplan_uuid'] = $dialplan_uuid;
			$array['ivr_menu_options'][]['ivr_menu_uuid'] = $ivr_menu_uuid;
			$array['ivr_menus'][]['ivr_menu_uuid'] = $ivr_menu_uuid;
			$database = new database;
			$database->app_name = 'ivr_menus';
			$database->app_uuid = 'a5788e9b-58bc-bd1b-df59-fff5d51253ab';
			$database->delete($array);
			//$message = $database->message;

		//remove the temporary permission
			$p->delete('dialplan_delete', 'temp');

		//synchronize the xml config
			save_dialplan_xml();

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$ivr_menu_context);

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header("Location: ivr_menus.php");
	exit;

?>
