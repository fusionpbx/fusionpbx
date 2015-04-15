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
	Copyright (C) 2013
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the permission class
	class permission {

		//delete the permissions
			function delete() {
				//set the variables
					$db = $this->db;
				//get unprotected groups and their domain uuids (if any)
					$sql = "select group_name, domain_uuid from v_groups where group_protected <> 'true' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					$result_count = count($result);
					if ($result_count > 0) {
						foreach($result as $row) {
							$unprotected_groups[$row['group_name']] = $row['domain_uuid'];
						}
					}
					unset ($prep_statement, $sql, $result, $result_count);
				//delete unprotected group permissions
					if (is_array($unprotected_groups) && sizeof($unprotected_groups) > 0) {
						foreach ($unprotected_groups as $unprotected_group_name => $unprotected_domain_uuid) {
							$sql = "delete from v_group_permissions where ";
							$sql .= "group_name = '".$unprotected_group_name."' ";
							$sql .= "and domain_uuid ".(($unprotected_domain_uuid != '') ? " = '".$unprotected_domain_uuid."' " : " is null ");
							if (!$db->exec($sql)) {
								//echo $db->errorCode() . "<br>";
								$info = $db->errorInfo();
								print_r($info);
								// $info[0] == $db->errorCode() unified error code
								// $info[1] is the driver specific error code
								// $info[2] is the driver specific error string
							}
						}
					}
			}

		//restore the permissions
			function restore() {
				//set the variables
					$db = $this->db;

				//delete the group permisisons
					$this->delete();

				//get the $apps array from the installed apps from the core and mod directories
					$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
					$x=0;
					foreach ($config_list as &$config_path) {
						include($config_path);
						$x++;
					}

				//restore default permissions
					foreach($apps as $row) {
						foreach ($row['permissions'] as $permission) {
							//set the variables
							if ($permission['groups']) {
								foreach ($permission['groups'] as $group) {
									//check group protection
									$sql = "select * from v_groups ";
									$sql .= "where group_name = '".$group."' ";
									$sql .= "and group_protected = 'true'";
									$prep_statement = $db->prepare(check_sql($sql));
									if ($prep_statement) {
										$prep_statement->execute();
										$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
										unset ($prep_statement);
										if (count($result) == 0) {
											//if the item uuid is not currently in the db then add it
											$sql = "select * from v_group_permissions ";
											$sql .= "where permission_name = '".$permission['name']."' ";
											$sql .= "and group_name = '$group' ";
											$prep_statement = $db->prepare(check_sql($sql));
											if ($prep_statement) {
												$prep_statement->execute();
												$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
												unset ($prep_statement);
												if (count($result) == 0) {
													//insert the default permissions into the database
													$sql = "insert into v_group_permissions ";
													$sql .= "(";
													$sql .= "group_permission_uuid, ";
													$sql .= "permission_name, ";
													$sql .= "group_name ";
													$sql .= ") ";
													$sql .= "values ";
													$sql .= "(";
													$sql .= "'".uuid()."', ";
													$sql .= "'".$permission['name']."', ";
													$sql .= "'".$group."' ";
													$sql .= ");";
													$db->exec(check_sql($sql));
													unset($sql);
												} // if count
											} // if prepared statement
										} // if count
									} // if prepared statement
								} // foreach group permission
							} // if permission
						} // foreach permission
					} // foreach app

			} // function
	} // class

?>