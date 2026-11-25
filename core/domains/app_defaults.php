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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
	if ($domains_processed == 1) {

		//set domain enable to true
		$sql = "update v_domains set domain_enabled = true ";
		$sql .= "where domain_enabled is null ";
		$database->execute($sql, null);
		unset($sql);

		//domain settings - change the type from var or name to text
		$sql = "update v_domain_settings ";
		$sql .= "set domain_setting_name = 'text' ";
		$sql .= "where (domain_setting_name = 'var' or domain_setting_name = 'name') ";
		$database->execute($sql, null);
		unset($sql);

		//create the domains view, it shows the parent child relationship between domains.
		//the definition is kept in resources/database/views/domains.php so that the schema
		//update and this upgrade path both use the same definition.
			$view = [];
			$view_path = __DIR__ . '/resources/database/views/domains.php';
			if (file_exists($view_path)) {
				include $view_path;
			}
			if (!empty($view['name']) && !empty($view['sql'])) {
				$database->execute("DROP VIEW IF EXISTS " . $view['name'] . ";", null);
				$database->execute("CREATE VIEW " . $view['name'] . " AS (\n" . $view['sql'] . "\n);", null);
			}
			unset($view, $view_path);

		//add the reseller group, it manages child domains for a tenant
			$sql = "select group_uuid from v_groups ";
			$sql .= "where domain_uuid is null ";
			$sql .= "and group_name = :group_name ";
			$parameters['group_name'] = 'reseller';
			$reseller_group_uuid = $database->select($sql, $parameters, 'column');
			unset($sql, $parameters);

			if (empty($reseller_group_uuid)) {
				$reseller_group_uuid = uuid();

				//grant temporary permissions to add the group
					$p = permissions::new();
					$p->add('group_add', 'temp');

				//add the group
					$array['groups'][0]['group_uuid'] = $reseller_group_uuid;
					$array['groups'][0]['domain_uuid'] = null; //global
					$array['groups'][0]['group_name'] = 'reseller';
					$array['groups'][0]['group_level'] = '60';
					$array['groups'][0]['group_protected'] = 'false';
					$array['groups'][0]['group_description'] = 'Group for the domains application';
					$database->save($array);
					unset($array);

				//remove the temporary permission
					$p->delete('group_add', 'temp');
			}

		//add the reseller group permissions for the domains application
			if (!empty($reseller_group_uuid)) {
				$permission_names = ['domain_add', 'domain_edit', 'domain_select', 'domain_view'];

				//get the permissions the reseller group already has
					$sql = "select permission_name from v_group_permissions ";
					$sql .= "where domain_uuid is null ";
					$sql .= "and group_uuid = :group_uuid ";
					$parameters['group_uuid'] = $reseller_group_uuid;
					$result = $database->select($sql, $parameters, 'all');
					unset($sql, $parameters);
					$current_permissions = is_array($result) ? array_column($result, 'permission_name') : [];
					unset($result);

				//add any permission that is missing
					$x = 0;
					foreach ($permission_names as $permission_name) {
						if (!in_array($permission_name, $current_permissions, true)) {
							$array['group_permissions'][$x]['group_permission_uuid'] = uuid();
							$array['group_permissions'][$x]['domain_uuid'] = null; //global
							$array['group_permissions'][$x]['permission_name'] = $permission_name;
							$array['group_permissions'][$x]['permission_protected'] = 'false';
							$array['group_permissions'][$x]['permission_assigned'] = 'true';
							$array['group_permissions'][$x]['group_name'] = 'reseller';
							$array['group_permissions'][$x]['group_uuid'] = $reseller_group_uuid;
							$x++;
						}
					}
					unset($current_permissions, $permission_names);

				//save the permissions
					if (!empty($array)) {
						//grant temporary permissions to add the group permissions
							$p = permissions::new();
							$p->add('group_permission_add', 'temp');

						$database->save($array);
						unset($array);

						//remove the temporary permission
							$p->delete('group_permission_add', 'temp');
					}
			}

	}

?>
