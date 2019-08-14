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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('time_condition_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the dialplan uuid
	$dialplan_uuids = $_REQUEST["id"];

//delete the dialplans
	if (is_array($dialplan_uuids) && @sizeof($dialplan_uuids) != 0) {

		//get dialplan contexts for each
			foreach ($dialplan_uuids as $dialplan_uuid) {
				if (is_uuid($dialplan_uuid)) {
					$sql = "select dialplan_context from v_dialplans ";
					$sql .= "where dialplan_uuid = :dialplan_uuid ";
					$parameters['dialplan_uuid'] = $dialplan_uuid;
					$database = new database;
					$dialplan_contexts[] = $database->select($sql, $parameters, 'column');
					unset($sql, $parameters);
				}
			}

		//delete dialplan and details
			$dialplans_deleted = 0;
			foreach ($dialplan_uuids as $x => $dialplan_uuid) {
				//build delete array of child data
					$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplan_details'][$x]['domain_uuid'] = $domain_uuid;

				//build delete array of parent data
					$array['dialplans'][$x]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplans'][$x]['domain_uuid'] = $domain_uuid;
					$array['dialplans'][$x]['app_uuid'] = '4b821450-926b-175a-af93-a03c441818b1';

				//grant temporary permissions
					$p = new permissions;
					$p->add('dialplan_detail_delete', 'temp');
					$p->add('dialplan_delete', 'temp');

				//execute delete
					$database = new database;
					$database->app_name = 'time_conditions';
					$database->app_uuid = '4b821450-926b-175a-af93-a03c441818b1';
					$database->delete($array);
					unset($array);

				//revoke temporary permissions
					$p->delete('dialplan_detail_delete', 'temp');
					$p->delete('dialplan_delete', 'temp');

				//count the time conditions that were deleted
					$dialplans_deleted++;
			}

		//synchronize the xml config
			save_dialplan_xml();

		//strip duplicate contexts
			$dialplan_contexts = array_unique($dialplan_contexts, SORT_STRING);

		//clear the cache
			$cache = new cache;
			if (sizeof($dialplan_contexts) > 0) {
				foreach($dialplan_contexts as $dialplan_context) {
					$cache->delete("dialplan:".$dialplan_context);
				}
			}

		//set message
			message::add($text['message-delete'].($dialplans_deleted > 1 ? ": ".$dialplans_deleted : null));

	}

//redirect the browser
	header("Location: time_conditions.php");

?>