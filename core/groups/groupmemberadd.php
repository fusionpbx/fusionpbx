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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('group_member_add') || if_group("superadmin")) {
		//access allowed
	}
	else {
		echo "access denied";
		return;
	}

//requires a superadmin to add a user to the superadmin group
	if (!if_group("superadmin") && $_GET["group_name"] == "superadmin") {
		echo "access denied";
		return;
	}

//get the http values and set them as variables
	$domain_uuid = $_POST["domain_uuid"];
	$group_uuid = $_POST["group_uuid"];
	$group_name = $_POST["group_name"];
	$user_uuid = $_POST["user_uuid"];

//validate the token
	$token = new token;
	if (!$token->validate('/core/groups/group_members.php')) {
		message::add($text['message-invalid_token'],'negative');
		header('Location: groups.php');
		exit;
	}

//add the user to the group
	if (is_uuid($user_uuid) && is_uuid($group_uuid) && strlen($group_name) > 0) {
		$array['user_groups'][0]['user_group_uuid'] = uuid();
		$array['user_groups'][0]['domain_uuid'] = $domain_uuid;
		$array['user_groups'][0]['group_uuid'] = $group_uuid;
		$array['user_groups'][0]['group_name'] = $group_name;
		$array['user_groups'][0]['user_uuid'] = $user_uuid;

		$p = new permissions;
		$p->add('user_group_add', 'temp');

		$database = new database;
		$database->app_name = 'groups';
		$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
		$database->save($array);
		unset($array);

		$p->delete('user_group_add', 'temp');

		message::add($text['message-update']);
	}

//redirect the user
	header("Location: group_members.php?group_uuid=".urlencode($group_uuid)."&group_name=".urlencode($group_name));

?>
