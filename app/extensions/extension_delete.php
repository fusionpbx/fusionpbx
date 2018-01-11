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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
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
			$extension_uuid = check_str($extension_uuid);
			if ($extension_uuid != '') {
				//get the extensions array
					$sql = "select * from v_extensions ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and extension_uuid = '".$extension_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$extensions = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					if (is_array($extensions)) { 
						foreach ($extensions as &$row) {
							$extension = $row["extension"];
							$number_alias = $row["number_alias"];
							$user_context = $row["user_context"];
							$follow_me_uuid = $row["follow_me_uuid"];
						}
						unset ($prep_statement);
					}

				//get the $xtension_users array
					$sql = "select * from v_extension_users ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and extension_uuid = '".$extension_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$extension_users = $prep_statement->fetchAll(PDO::FETCH_NAMED);

				//build the array
					$old_array['extensions'] = $extensions;
					$old_array['extension_users'] = $extension_users;

				//log the transaction results
					if (file_exists($_SERVER["PROJECT_ROOT"]."/app/database_transactions/app_config.php")) {
						//set the variables
							$app_name = "extensions";
							$app_uuid = "e68d9689-2769-e013-28fa-6214bf47fca3";
							$code = "200";
							$new_array = array();

						//add the insert into database transactions
							$sql = "insert into v_database_transactions ";
							$sql .= "(";
							$sql .= "database_transaction_uuid, ";
							$sql .= "domain_uuid, ";
							$sql .= "user_uuid, ";
							$sql .= "app_uuid, ";
							$sql .= "app_name, ";
							$sql .= "transaction_code, ";
							$sql .= "transaction_address, ";
							//$sql .= "transaction_type, ";
							$sql .= "transaction_date, ";
							$sql .= "transaction_old, ";
							//$sql .= "transaction_new, ";
							$sql .= "transaction_result ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".uuid()."', ";
							$sql .= "'".$_SESSION['domain_uuid']."', ";
							$sql .= "'".$_SESSION['user_uuid']."', ";
							$sql .= "'".$app_uuid."', ";
							$sql .= "'".$app_name."', ";
							$sql .= "'".$code."', ";
							$sql .= "'".$_SERVER['REMOTE_ADDR']."', ";
							//$sql .= "'$transaction_type', ";
							$sql .= "now(), ";
							$sql .= "'".json_encode($old_array, JSON_PRETTY_PRINT)."', ";
							//$sql .= "'".json_encode($new_array, JSON_PRETTY_PRINT)."', ";
							$sql .= "null ";
							$sql .= ")";
							$db->exec(check_sql($sql));
							unset($sql);
					}

				//delete the extension
					$sql = "delete from v_extensions ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and extension_uuid = '".$extension_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					unset($prep_statement, $sql);

					$sql = "delete from v_extension_users ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and extension_uuid = '".$extension_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					unset($prep_statement, $sql);

				//delete the follow-me
					$sql = "delete from v_follow_me_destinations ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and follow_me_uuid = '".$follow_me_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					unset($prep_statement, $sql);

					$sql = "delete from v_follow_me ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and follow_me_uuid = '".$follow_me_uuid."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					unset($prep_statement, $sql);

				//delete the ring group destinations
					if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ring_groups/app_config.php")) {
						$sql = "delete from v_ring_group_destinations ";
						$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
						$sql .= "and (destination_number = '".$extension."' or destination_number = '".$number_alias."') ";
						$db->exec(check_sql($sql));
						unset($sql);
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
	messages::add($text['message-delete']);
	header("Location: extensions.php");
	return;

?>
