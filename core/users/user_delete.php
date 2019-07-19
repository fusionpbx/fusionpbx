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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('user_delete')) {
		//access allowed
	}
	else {
		echo "access denied";
		return;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	$user_uuid = $_GET["id"];

//validate the uuid
	if (is_uuid($user_uuid)) {
		//get the user's domain from v_users
			if (permission_exists('user_domain')) {
				$sql = "select domain_uuid from v_users ";
				$sql .= "where user_uuid = :user_uuid ";
				$parameters['user_uuid'] = $user_uuid;
				$database = new database;
				$domain_uuid = $database->select($sql, $parameters, 'column');
				unset($sql, $parameters);
			}
			else {
				$domain_uuid = $_SESSION['domain_uuid'];
			}

		//required to be a superadmin to delete a member of the superadmin group
			$superadmin_list = superadmin_list($db);
			if (if_superadmin($superadmin_list, $user_uuid)) {
				if (!if_group("superadmin")) {
					//access denied - do not delete the user
					header("Location: index.php");
					return;
				}
			}

		//delete the user settings
			$array['user_settings'][0]['user_uuid'] = $user_uuid;
			$array['user_settings'][0]['domain_uuid'] = $domain_uuid;

		//delete the groups the user is assigned to
			$array['user_groups'][0]['user_uuid'] = $user_uuid;
			$array['user_groups'][0]['domain_uuid'] = $domain_uuid;

		//delete the user
			$array['users'][0]['user_uuid'] = $user_uuid;
			$array['users'][0]['domain_uuid'] = $domain_uuid;

		//execute
			$p = new permissions;
			$p->add('user_setting_delete', 'temp');
			$p->add('user_group_delete', 'temp');

			$database = new database;
			$database->app_name = 'users';
			$database->app_uuid = '112124b3-95c2-5352-7e9d-d14c0b88f207';
			$database->delete($array);
			unset($array);

			$p->delete('user_setting_delete', 'temp');
			$p->delete('user_group_delete', 'temp');

		//set message
			message::add($text['message-delete']);
	}

//redirect the user
	header("Location: users.php");
	exit;

?>
