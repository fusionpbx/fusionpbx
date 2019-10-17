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
	if (!permission_exists('conference_center_delete')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

	//delete the data
	if (is_uuid($_GET["id"])) {

		$conference_center_uuid = $_GET["id"];

		//get the dialplan uuid
			$sql = "select dialplan_uuid from v_conference_centers ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and conference_center_uuid = :conference_center_uuid ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['conference_center_uuid'] = $conference_center_uuid;
			$database = new database;
			$dialplan_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

		//delete the conference center
			$array['conference_centers'][0]['conference_center_uuid'] = $conference_center_uuid;
			$array['conference_centers'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
		//delete the dialplan details
			$array['dialplan_details'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplan_details'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
		//delete the dialplan entry
			$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
			$array['dialplans'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

			$p = new permissions;
			$p->add('dialplan_detail_delete', 'temp');
			$p->add('dialplan_delete', 'temp');

			$database = new database;
			$database->app_name = 'conference_centers';
			$database->app_uuid = '8d083f5a-f726-42a8-9ffa-8d28f848f10e';
			$database->delete($array);
			unset($array);

			$p->delete('dialplan_detail_delete', 'temp');
			$p->delete('dialplan_delete', 'temp');

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["context"]);

		//syncrhonize configuration
			save_dialplan_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;

		//set message
			message::add($text['message-delete']);
	}

//redirect the browser
	header("Location: conference_centers.php");
	return;

?>
