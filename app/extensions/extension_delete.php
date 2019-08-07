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
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('extension_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//check for the ids
	if (is_array($_REQUEST) && sizeof($_REQUEST) > 0) {

		$extension_uuids = $_REQUEST["id"];
		foreach($extension_uuids as $extension_uuid) {
			if ($extension_uuid != '') {
				//get the extensions array
					$sql = "select * from v_extensions ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and extension_uuid = :extension_uuid ";
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$parameters['extension_uuid'] = $extension_uuid;
					$database = new database;
					$row = $database->execute($sql, $parameters, 'row');
					if (is_array($row) && @sizeof($row) != 0) {
						$extension = $row["extension"];
						$number_alias = $row["number_alias"];
						$user_context = $row["user_context"];
						$follow_me_uuid = $row["follow_me_uuid"];
					}
					unset($sql, $parameters, $row);

				//delete the data
					$p = new permissions;
					$p->add('extension_user_delete', 'temp');
					$p->add('follow_me_destination_delete', 'temp');
					$p->add('follow_me_delete', 'temp');

					$array['extension_users'][]['extension_uuid'] = $extension_uuid;
					$array['follow_me_destinations'][]['follow_me_uuid'] = $follow_me_uuid;
					$array['follow_me'][]['follow_me_uuid'] = $follow_me_uuid;
					$array['extensions'][]['extension_uuid'] = $extension_uuid;
					$database = new database;
					$database->app_name = 'extensions';
					$database->app_uuid = 'e68d9689-2769-e013-28fa-6214bf47fca3';
					$database->delete($array);
					unset($array);

					$p->delete('extension_user_delete', 'temp');
					$p->delete('follow_me_destination_delete', 'temp');
					$p->delete('follow_me_delete', 'temp');

				//delete the ring group destinations
					if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ring_groups/app_config.php")) {
						$sql = "delete from v_ring_group_destinations ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and (destination_number = :extension or destination_number = :number_alias ";
						$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
						$parameters['extension'] = $extension;
						$parameters['number_alias'] = $number_alias;
						$database = new database;
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					}
			}
		}

		//clear the cache
			$cache = new cache;
			$cache->delete("directory:".$extension."@".$user_context);

		//synchronize configuration
			if (is_readable($_SESSION['switch']['extensions']['dir'])) {
				$extension = new extension;
				$extension->xml();
			}
	}

//redirect the browser
	message::add($text['message-delete']);
	header("Location: extensions.php");
	exit;

?>
