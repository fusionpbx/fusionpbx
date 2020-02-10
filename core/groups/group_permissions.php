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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permisions
	if (permission_exists('group_permissions') || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the list of installed apps from the core and mod directories
	$config_list = glob($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/*/*/app_config.php");
	$x = 0;
	foreach ($config_list as &$config_path) {
		include($config_path);
		$x++;
	}

//get the group uuid passed
	$group_uuid = $_REQUEST['group_uuid'];

//if there are no permissions listed in v_group_permissions then set the default permissions
	$sql = "select count(*) from v_group_permissions ";
	$database = new database;
	$group_permission_count = $database->select($sql, null, 'column');
	if ($group_permission_count == 0) {
		//no permissions found add the defaults
		foreach ($apps as $app) {
			foreach ($app['permissions'] as $row) {
				foreach ($row['groups'] as $index => $group_name) {
					//add the record
					$array['group_permissions'][$index]['group_permission_uuid'] = uuid();
					$array['group_permissions'][$index]['permission_name'] = $row['name'];
					$array['group_permissions'][$index]['group_name'] = $group_name;
					$array['group_permissions'][$index]['group_uuid'] = $group_uuid;
				}
				if (is_array($array) && sizeof($array) != 0) {
					$database = new database;
					$database->app_name = 'groups';
					$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
					$database->save($array);
					unset($array);
				}
			}
		}
	}
	unset($sql, $group_name);

//lookup domain uuid (if any) and name
	$sql = "select domain_uuid, group_name from v_groups ";
	$sql .= "where group_uuid = :group_uuid ";
	$parameters['group_uuid'] = $group_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && sizeof($row) != 0) {
		$domain_uuid = $row["domain_uuid"];
		$group_name = $row["group_name"];
	}
	unset($sql, $parameters, $row);

//add the search string
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search .= " and lower(permission_name) like :search ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the permissions assigned to this group
	$sql = "select * from v_group_permissions ";
	$sql .= "where group_name = :group_name ";
	if (is_uuid($domain_uuid)) {
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	else {
		$sql .= "and domain_uuid is null ";
	}
	$sql .= $sql_search;
	$parameters['group_name'] = $group_name;
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	if (is_array($result) && sizeof($result) != 0) {
		foreach ($result as &$row) {
			$permissions_db[$row["permission_name"]] = "true";
		}
	}
	unset($sql, $parameters, $result, $row);

//list all the permissions in the database
	foreach ($apps as $app) {
		if (isset($app['permissions'])) foreach ($app['permissions'] as $row) {
			if ($permissions_db[$row['name']] == "true") {
				$permissions_db_checklist[$row['name']] = "true";
			}
			else {
				$permissions_db_checklist[$row['name']] = "false";
			}
		}
	}

//process the http post
	if (count($_POST)>0) {

		foreach ($_POST['permissions_form'] as $permission) {
			$permissions_form[$permission] = "true";
		}

		//list all the permissions
			foreach ($apps as $app) {
				if (is_array($app['permissions']) && @sizeof($app['permissions']) != 0) {
					foreach ($app['permissions'] as $row) {
						if ($permissions_form[$row['name']] == "true") {
							$permissions_form_checklist[$row['name']] = "true";
						}
						else {
							$permissions_form_checklist[$row['name']] = "false";
						}
					}
				}
			}

		//list all the permissions
			foreach ($apps as $app) {
				if (is_array($app['permissions']) && @sizeof($app['permissions']) != 0) {
					foreach ($app['permissions'] as $row) {
						$permission = $row['name'];
						if ($permissions_db_checklist[$permission] == "true" && $permissions_form_checklist[$permission] == "true") {
							//matched do nothing
						}
						if ($permissions_db_checklist[$permission] == "false" && $permissions_form_checklist[$permission] == "false") {
							//matched do nothing
						}
						if ($permissions_db_checklist[$permission] == "true" && $permissions_form_checklist[$permission] == "false") {
							//delete the record
								$array['group_permissions'][0]['group_name'] = $group_name;
								$array['group_permissions'][0]['permission_name'] = $permission;
								$array['group_permissions'][0]['group_uuid'] = $group_uuid;
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->delete($array);
								unset($array);

							foreach ($apps as $app) {
								if (is_array($app['permissions']) && @sizeof($app['permissions']) != 0) {
									foreach ($app['permissions'] as $row) {
										if ($row['name'] == $permission) {

											$array['menu_item_groups'][0]['menu_item_uuid'] = $row['menu']['uuid'];
											$array['menu_item_groups'][0]['group_name'] = $group_name;
											$array['menu_item_groups'][0]['menu_uuid'] = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';

											$p = new permissions;
											$p->add('menu_item_group_delete', 'temp');

											$database = new database;
											$database->app_name = 'groups';
											$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
											$database->delete($array);
											unset($array);

											$p->delete('menu_item_group_delete', 'temp');

											$sql = "select menu_item_parent_uuid from v_menu_items ";
											$sql .= "where menu_item_uuid = :menu_item_uuid ";
											$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
											$parameters['menu_item_uuid'] = $row['menu']['uuid'];
											$database = new database;
											$menu_item_parent_uuid = $database->select($sql, $parameters, 'column');
											unset($sql, $parameters);

											$sql = "select count(*) from v_menu_items as i, v_menu_item_groups as g  ";
											$sql .= "where i.menu_item_uuid = g.menu_item_uuid ";
											$sql .= "and i.menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
											$sql .= "and i.menu_item_parent_uuid = :menu_item_parent_uuid ";
											$sql .= "and g.group_name = :group_name ";
											$parameters['menu_item_parent_uuid'] = $menu_item_parent_uuid;
											$parameters['group_name'] = $group_name;
											$database = new database;
											$result_count = $database->select($sql, $parameters, 'column');

											if ($result_count == 0) {
												$array['menu_item_groups'][0]['menu_item_uuid'] = $menu_item_parent_uuid;
												$array['menu_item_groups'][0]['group_name'] = $group_name;
												$array['menu_item_groups'][0]['menu_uuid'] = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';

												$p = new permissions;
												$p->add('menu_item_group_delete', 'temp');

												$database = new database;
												$database->app_name = 'groups';
												$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
												$database->delete($array);
												unset($array);

												$p->delete('menu_item_group_delete', 'temp');
											}
											unset($sql, $parameters, $result_count);
										}
									}
								}
							}
							//set the permission to false in the permissions_db_checklist
								$permissions_db_checklist[$permission] = "false";
						}
						if ($permissions_db_checklist[$permission] == "false" && $permissions_form_checklist[$permission] == "true") {
							//add the record
								$array['group_permissions'][0]['group_permission_uuid'] = uuid();
								if (is_uuid($domain_uuid)) {
									$array['group_permissions'][0]['domain_uuid'] = $domain_uuid;
								}
								$array['group_permissions'][0]['permission_name'] = $permission;
								$array['group_permissions'][0]['group_name'] = $group_name;
								$array['group_permissions'][0]['group_uuid'] = $group_uuid;
								$database = new database;
								$database->app_name = 'groups';
								$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
								$database->save($array);
								unset($array);

							foreach ($apps as $app) {
								if (is_array($app['permissions']) && @sizeof($app['permissions']) != 0) {
									foreach ($app['permissions'] as $row) {
										if ($row['name'] == $permission) {

											$array['menu_item_groups'][0]['menu_uuid'] = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';
											$array['menu_item_groups'][0]['menu_item_uuid'] = $row['menu']['uuid'];
											$array['menu_item_groups'][0]['group_name'] = $group_name;

											$p = new permissions;
											$p->add('menu_item_group_add', 'temp');

											$database = new database;
											$database->app_name = 'groups';
											$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
											$database->save($array);
											unset($array);

											$p->delete('menu_item_group_add', 'temp');

											$sql = "select menu_item_parent_uuid from v_menu_items ";
											$sql .= "where menu_item_uuid = :menu_item_uuid ";
											$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
											$parameters['menu_item_uuid'] = $row['menu']['uuid'];
											$database = new database;
											$menu_item_parent_uuid = $database->select($sql, $parameters, 'column');
											unset($sql, $parameters);

											$sql = "select count(*) from v_menu_item_groups ";
											$sql .= "where menu_item_uuid = :menu_item_uuid ";
											$sql .= "and group_name = :group_name ";
											$sql .= "and menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286' ";
											$parameters['menu_item_uuid'] = $menu_item_parent_uuid;
											$parameters['group_name'] = $group_name;
											$database = new database;
											$result_count = $database->select($sql, $parameters, 'column');

											if ($result_count == 0) {
												$array['menu_item_groups'][0]['menu_uuid'] = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';
												$array['menu_item_groups'][0]['menu_item_uuid'] = $menu_item_parent_uuid;
												$array['menu_item_groups'][0]['group_name'] = $group_name;

												$p = new permissions;
												$p->add('menu_item_group_add', 'temp');

												$database = new database;
												$database->app_name = 'groups';
												$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
												$database->save($array);
												unset($array);

												$p->delete('menu_item_group_add', 'temp');
											}

											unset($sql, $parameters, $result_count);
										}
									}
								}
							}
							//set the permission to true in the permissions_db_checklist
								$permissions_db_checklist[$permission] = "true";
						}
					}
				}
			}

		message::add($text['message-update']);
		header("Location: groups.php");
		return;
	}

//include the header
	$document['title'] = $text['title-group_permissions'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-group_permissions'].'<i>'.escape($group_name)."</i></b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'collapse'=>'hide-sm-dn','link'=>'groups.php']);
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='hidden' name='group_uuid' value='".escape($group_uuid)."'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','collapse'=>'hide-sm-dn','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','collapse'=>'hide-sm-dn','link'=>'group_permissions.php?group_uuid='.urlencode($group_uuid),'style'=>($search == '' ? 'display: none;' : null)]);
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'collapse'=>'hide-sm-dn','style'=>'margin-left: 15px;','onclick'=>"document.getElementById('frm').submit();"]);
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-group_permissions']."\n";
	echo "<br /><br />\n";

	echo "<form method='post' name='frm' id='frm'>\n";

	foreach ($apps as $app_index => $app) {

		//skip apps for which there are no permissions
			if (!is_array($app['permissions']) || sizeof($app['permissions']) == 0) { continue; }

		//skip apps for which search doesn't match at least one permission
			if ($search) {
				$permission_matched = false;
				foreach ($app['permissions'] as $row) {
					if (substr_count(strtolower($row['name']), strtolower($search)) > 0) {
						$permission_matched = true;
						break;
					}
				}
				if (!$permission_matched) { continue; }
			}

		$app_name = $app['name'];
		$description = $app['description']['en-us'];

		//used to hide apps, even if permissions don't exist
			$array_apps_unique[] = str_replace(' ','_',strtolower($app['name']));

		echo "<b>".$app_name."</b><br />\n";
		if ($description != '') { echo $description."<br />\n"; }
		echo "<br>";

		echo "<table class='list'>\n";
		echo "	<tr class='list-header'>\n";
		echo "		<th class='checkbox'>\n";
		echo "			<input type='checkbox' id='checkbox_all_".$app_index."' name='checkbox_all' onclick=\"list_all_toggle('".$app_index."');\">\n";
		echo "		</th>\n";
		echo "		<th class='pct-60'>".$text['label-permission_permissions']."</th>\n";
		echo "		<th class='pct-40 hide-xs'>".$text['label-permission_description']."&nbsp;</th>\n";
		echo "	<tr>\n";

		foreach ($app['permissions'] as $permission_index => $row) {
			//skip permission if doesn't match search
				if ($search && substr_count(strtolower($row['name']), strtolower($search)) == 0) { continue; }

			$checked = ($permissions_db_checklist[$row['name']] == "true") ? "checked='checked'" : null;
			echo "<tr class='list-row'>\n";
			echo "	<td class='checkbox'>\n";
			echo "		<input type='checkbox' name='permissions_form[]' id='perm_".$app_index."_".$permission_index."' class='checkbox_".$app_index."' ".$checked." value='".escape($row['name'])."' onclick=\"if (!this.checked) { document.getElementById('checkbox_all_".$app_index."').checked = false; }\">\n";
			echo "	</td>\n";
			echo "	<td class='no-wrap' onclick=\"if (document.getElementById('perm_".$app_index."_".$permission_index."').checked) { document.getElementById('perm_".$app_index."_".$permission_index."').checked = false; document.getElementById('checkbox_all_".$app_index."').checked = false; } else { document.getElementById('perm_".$app_index."_".$permission_index."').checked = true; }\">".escape($row['name'])."</td>\n";
			echo "	<td class='description overflow hide-xs' onclick=\"if (document.getElementById('perm_".$app_index."_".$permission_index."').checked) { document.getElementById('perm_".$app_index."_".$permission_index."').checked = false; document.getElementById('checkbox_all_".$app_index."').checked = false; } else { document.getElementById('perm_".$app_index."_".$permission_index."').checked = true; }\">".escape($row['description'])."&nbsp;</td>\n";
			echo "</tr>\n";

			//populate search/filter arrays
				$array_apps[] = str_replace(' ','_',strtolower($app['name']));
				$array_apps_original[] = $app['name'];
				$array_permissions[] = $row['name'];
				$array_descriptions[] = str_replace('"','\"',$row['description']);

			$app_permissions[$app_index][] = "perm_".$app_index."_".$permission_index;
		}

		echo "</table>\n";
		echo "<br /><br />\n";

	}

	echo "<input type='hidden' name='group_uuid' value='".escape($group_uuid)."'>\n";
	echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>\n";

	echo "</form>\n";

//show the footer
	require_once "resources/footer.php";

?>