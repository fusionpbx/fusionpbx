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
	if (permission_exists('group_delete') || if_group("superadmin")) {
		//access allowed
	}
	else {
		echo "access denied";
		return;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//validate the uuid
	if (is_uuid($_GET["id"])) {
		$group_uuid = $_GET["id"];

		//get the group from v_groups
			$sql = "select domain_uuid, group_name from v_groups ";
			$sql .= "where group_uuid = :group_uuid ";
			if (!permission_exists('group_domain')) {
				$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
				$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			}
			$parameters['group_uuid'] = $group_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			unset($sql, $parameters);

			if (is_array($row) && sizeof($row) != 0) {

				$domain_uuid = $row["domain_uuid"];
				$group_name = $row["group_name"];

				//delete the user groups
					$array['user_groups'][0]['group_uuid'] = $group_uuid;

					$p = new permissions;
					$p->add('user_group_delete', 'temp');

					$database = new database;
					$database->app_name = 'groups';
					$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
					$database->delete($array);
					unset($array);

					$p->delete('user_group_delete', 'temp');

				//get the group permissions
					$sql = "select group_permission_uuid ";
					$sql .= "from v_group_permissions ";
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
					if (is_array($result) && sizeof($result) != 0) {
						foreach ($result as $index => $row) {
							//build array
								$array['group_permissions'][$index]['group_permission_uuid'] = $row['group_permission_uuid'];
								$array['group_permissions'][$index]['group_name'] = $group_name;
						}
						if (is_array($array) && sizeof($array) != 0) {
							//delete the group permissions
								$p = new permissions;
								$p->add('group_permission_delete', 'temp');

								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->delete($array);
								unset($array);

								$p->delete('group_permission_delete', 'temp');
						}
					}
					unset($sql, $parameters, $result, $row);

				//delete the group
					$array['groups'][0]['group_uuid'] = $group_uuid;
					if (is_uuid($domain_uuid)) {
						$array['groups'][0]['domain_uuid'] = $domain_uuid;
					}

					$database = new database;
					$database->app_name = 'groups';
					$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
					$database->delete($array);
					unset($array);

				//set message
					message::add($text['message-delete']);

			}
			unset($sql, $parameters, $row);
	}

//redirect the user
	header("Location: groups.php");

?>
