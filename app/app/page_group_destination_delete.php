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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('page_group_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the http values as variables
	$page_group_destination_uuid = $_GET["id"];
	$page_group_uuid = $_GET["page_group_uuid"];

//delete the page destination
	if (is_uuid($page_group_destination_uuid) && is_uuid($page_group_uuid)) {

		//add the destination delete permission
			$p = new permissions;
			$p->add("page_group_destination_delete","temp");
		
		//delete the data
			$array['page_group_destinations'][]['page_group_destination_uuid'] = $page_group_destination_uuid;
			$database = new database;
			$database->app_name = 'paging_roups';
			$database->app_uuid = 'e3a6a2e9-340b-4f38-b0cc-550a15f59a68';
			$database->delete($array);
			$message = $database->message;
		
			$p->delete("page_group_destination_delete","temp");

		//set message
			message::add($text['message-delete']);

		//redirect the user
			header('Location: page_group_edit.php?id='.$page_group_uuid);
			exit;
	}

//default redirect
	header('Location: page_groups.php');
	exit;

?>
