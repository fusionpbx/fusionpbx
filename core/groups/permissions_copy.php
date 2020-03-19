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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Sérgio Reis <uc@wavecom.pt>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('group_permission_add')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include paging
	require_once "resources/paging.php";

//set the http get/post variable(s) to a php variable
	if (is_uuid($_REQUEST["id"]) && isset($_REQUEST["new_group_name"])) {

		//get HTTP values and set as variables
			$group_uuid = $_REQUEST["id"];
			$new_group_name = $_REQUEST["new_group_name"];
			$new_group_desc = $_REQUEST["new_group_desc"];

		//get the source groups data
			$sql = "select * from v_groups ";
			$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$sql .= "and group_uuid = :group_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['group_uuid'] = $group_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && sizeof($row) != 0) {
				$domain_uuid = $row["domain_uuid"];
				$group_name = $row["group_name"];
			}
			unset($sql, $parameters, $row);

		//create new target group
			$new_group_uuid = uuid();
			$array['groups'][0]['group_uuid'] = $new_group_uuid;
			if (is_uuid($domain_uuid)) {
				$array['groups'][0]['domain_uuid'] = $domain_uuid;
			}
			$array['groups'][0]['group_name'] = $new_group_name;
			$array['groups'][0]['group_description'] = $new_group_desc;
			$database = new database;
			$database->app_name = 'groups';
			$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
			$database->save($array);
			unset($array);

		//get the source group permissions data
			$sql = "select * from v_group_permissions ";
			$sql .= "where group_name = :group_name ";
			if (is_uuid($domain_uuid)) {
				$sql .= "and domain_uuid = :domain_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
			}
			else {
				$sql .= "and domain_uuid is null ";
			}
			$parameters['group_name'] = $group_name;
			$database = new database;
			$result = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters);

			if (is_array($result) && sizeof($result) != 0) {
				foreach ($result as $x => &$row) {
					//define group permissions values
						$domain_uuid = $row["domain_uuid"];
						$permission_name = $row["permission_name"];
						$group_name = $row["group_name"];
					//build insert array
						$array['group_permissions'][$x]['group_permission_uuid'] = uuid();
						if (is_uuid($domain_uuid)) {
							$array['group_permissions'][$x]['domain_uuid'] = $domain_uuid;
						}
						$array['group_permissions'][$x]['permission_name'] = $permission_name;
						$array['group_permissions'][$x]['group_name'] = $new_group_name;
						$array['group_permissions'][$x]['group_uuid'] = $new_group_uuid;
				}
				if (is_array($array) && sizeof($array) != 0) {
					//grant temporary permissions
						$p = new permissions;
						$p->add('group_permission_add', 'temp');
					//execute insert
						$database = new database;
						$database->app_name = 'groups';
						$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
						$database->save($array);
						unset($array);
					//revoke temporary permissions
						$p->delete('group_permission_add', 'temp');
					//set message
						message::add($text['message-copy']);
				}
			}
			unset($result, $row);
	}

//redirect
	header("Location: groups.php");

?>