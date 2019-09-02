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
				//get unprotected groups and their domain uuids (if any)
					$sql = "select group_name, domain_uuid ";
					$sql .= "from v_groups ";
					$sql .= "where group_protected <> 'true' ";
					$database = new database;
					$result = $database->select($sql, null, 'all');
					if (is_array($result) && @sizeof($result) != 0) {
						foreach($result as $row) {
							$unprotected_groups[$row['group_name']] = $row['domain_uuid'];
						}
					}
					unset($sql, $result, $row);
				//delete unprotected group permissions
					if (is_array($unprotected_groups) && sizeof($unprotected_groups) > 0) {
						$x = 0;
						foreach ($unprotected_groups as $unprotected_group_name => $unprotected_domain_uuid) {
							//build delete array
								$array['group_permissions'][$x]['group_name'] = $unprotected_group_name;
								$array['group_permissions'][$x]['domain_uuid'] = $unprotected_domain_uuid != '' ? $unprotected_domain_uuid : null;
							$x++;
						}
						if (is_array($array) && @sizeof($array) != 0) {
							//grant temporary permissions
								$p = new permissions;
								$p->add('group_permission_delete', 'temp');
							//execute delete
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->delete($array);
								unset($array);
							//revoke temporary permissions
								$p->delete('group_permission_delete', 'temp');
						}
					}
			}

		//restore the permissions
			function restore() {
				//delete the group permisisons
					$this->delete();

				//get the $apps array from the installed apps from the core and mod directories
					$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
					$x = 0;
					foreach ($config_list as &$config_path) {
						include($config_path);
						$x++;
					}

				//restore default permissions
					$x = 0;
					foreach ($apps as $row) {
						foreach ($row['permissions'] as $permission) {
							//set the variables
							if ($permission['groups']) {
								foreach ($permission['groups'] as $group) {
									//check group protection
									$sql = "select count(*) from v_groups ";
									$sql .= "where group_name = :group_name ";
									$sql .= "and group_protected = 'true'";
									$parameters['group_name'] = $group;
									$database = new database;
									$num_rows = $database->select($sql, $parameters, 'column');
									unset($sql, $parameters);

									if ($num_rows == 0) {
										//if the item uuid is not currently in the db then add it
										$sql = "select count(*) from v_group_permissions ";
										$sql .= "where permission_name = :permission_name ";
										$sql .= "and group_name = :group_name ";
										$parameters['permission_name'] = $permission['name'];
										$parameters['group_name'] = $group;
										$database = new database;
										$num_rows = $database->select($sql, $parameters, 'column');
										unset($sql, $parameters);

										if ($num_rows == 0) {
											//build default permissions insert array
												$array['group_permissions'][$x]['group_permission_uuid'] = uuid();
												$array['group_permissions'][$x]['permission_name'] = $permission['name'];
												$array['group_permissions'][$x]['group_name'] = $group;
											$x++;
										}
									}
								}
							}
						}
					}
					if (is_array($array) && @sizeof($array)) {
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
					}

			}

	}

?>