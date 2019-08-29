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

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dialplan_delete')
		|| permission_exists('inbound_route_delete')
		|| permission_exists('outbound_route_delete')
		|| permission_exists('fifo_delete')
		|| permission_exists('time_condition_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the variables
	$dialplan_detail_uuid = $_GET["id"];
	$dialplan_uuid = $_REQUEST["dialplan_uuid"];
	$app_uuid = $_REQUEST["app_uuid"];

//delete the dialplan detail
	if (is_uuid($dialplan_detail_uuid)) {
		//delete child data
			$array['dialplan_details'][0]['dialplan_detail_uuid'] = $dialplan_detail_uuid;
			//$array['dialplan_details'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

			$p = new permissions;
			$p->add('dialplan_detail_delete', 'temp');

			$database = new database;
			$database->app_name = 'dialplans';
			$database->app_uuid = '742714e5-8cdf-32fd-462c-cbe7e3d655db';
			$database->delete($array);
			unset($array);

			$p->delete('dialplan_detail_delete', 'temp');

		//synchronize the xml config
			save_dialplan_xml();

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["context"]);

		//update the dialplan xml
			$dialplans = new dialplan;
			$dialplans->source = "details";
			$dialplans->destination = "database";
			$dialplans->uuid = $dialplan_uuid;
			$dialplans->xml();

		//set message
			message::add($text['message-delete']);
	}

//redirect the browser
	header("Location: dialplan_edit.php?id=".$dialplan_uuid.(($app_uuid != '') ? "&app_uuid=".$app_uuid : null));
	exit;

?>
