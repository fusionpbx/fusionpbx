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
	Portions created by the Initial Developer are Copyright (C) 2018-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('group_permission_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//action add or update
	if (is_uuid($_REQUEST["group_uuid"])) {
		$group_uuid = $_REQUEST["group_uuid"];
	}

//get the group_name
	if (is_uuid($group_uuid)) {
		$sql = "select group_name from v_groups ";
		$sql .= "where group_uuid = :group_uuid ";
		$parameters['group_uuid'] = $group_uuid;
		$database = new database;
		$group_name = $database->select($sql, $parameters, 'column');
		unset($sql, $parameters);
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (is_array($_POST['group_permissions'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$group_permissions = $_POST['group_permissions'];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
		//get the list
			$sql = "select * from v_group_permissions ";
			$sql .= "where group_uuid = :group_uuid ";
			$parameters['group_uuid'] = $group_uuid;
			$database = new database;
			$group_permissions = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters);

		//add or remove permissions from the group
			$x = 0;
			if (is_array($_POST['group_permissions'])) {
				foreach($_POST['group_permissions'] as $row) {
					//check to see if the group has been assigned the permission
					$in_database = false;
					foreach($group_permissions as $field) {
						if ($field['permission_name'] === $row['permission']) {
							$in_database = true;
							break;
						}
					}

					//add - checked on html form and not in the database
					if ($row['checked'] === 'true') {
						if (!$in_database) {
							if (isset($row['permission']) && strlen($row['permission']) > 0) {
								$array['add']['group_permissions'][$x]['group_permission_uuid'] = uuid();
								$array['add']['group_permissions'][$x]['permission_name'] = $row['permission'];
								$array['add']['group_permissions'][$x]['group_uuid'] = $group_uuid;
								$array['add']['group_permissions'][$x]['group_name'] = $group_name;
								//$array['add']['group_permissions'][$x]['permission_uuid'] = $row['uuid'];
								$x++;
							}
						}
					}

					//delete - unchecked on the form and in the database
					if ($row['checked'] !== 'true') {
						if ($in_database) {
							if (isset($row['permission']) && strlen($row['permission']) > 0) {
								$array['delete']['group_permissions'][$x]['permission_name'] = $row['permission'];
								$array['delete']['group_permissions'][$x]['group_uuid'] = $group_uuid;
								//$array['delete']['group_permissions'][$x]['group_name'] = $group_name;
								//$array['delete'][$x]['permission_uuid'] = $row['uuid'];
							}
							$x++;
						}
					}
				}
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: group_permissions.php?group_uuid='.urlencode($group_uuid).'&search='.urlencode($search));
				exit;
			}

		//save to the data
			if (is_array($array['add']) && @sizeof($array['add']) != 0) {
				$database = new database;
				$database->app_name = 'groups';
				$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
				$database->save($array['add']);
				$message = $database->message;
			}

		//delete the permissions
			if (is_array($array['delete']) && @sizeof($array['delete']) != 0) {
				if (permission_exists('group_permission_delete')) {
					$database = new database;
					$database->app_name = 'groups';
					$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
					$database->delete($array['delete']);
				}
			}

		//set the message
			message::add($text['message-update']);

		//redirect
			header('Location: group_permissions.php?group_uuid='.urlencode($group_uuid));
			exit;
	}

//get order and order by
	//$order_by = $_GET["order_by"];
	//$order = $_GET["order"];

//add the search string
	if (isset($_REQUEST["search"])) {
		$search =  strtolower($_REQUEST["search"]);
		$sql_search = " (";
		$sql_search .= "	lower(p.permission_name) like :search ";
		//$sql_search .= "	or lower(p.group_name) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the count
	/*
	$sql = "select count(group_permission_uuid) from v_group_permissions ";
	$sql .= "where group_uuid = :group_uuid ";
	$parameters['group_uuid'] = $group_uuid;
	if (isset($sql_search)) {
		$sql .= "where ".$sql_search;
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	*/

//get the list
	$sql = "select p.*, ";
	$sql .= "exists(select from v_group_permissions where permission_name = p.permission_name and group_name = :group_name) as permission_assigned ";
	$sql .= "from v_permissions as p ";
	$parameters['group_name'] = $group_name;
	if (isset($sql_search)) {
		$sql .= "where ".$sql_search;
	}
	$sql .= "order by application_name asc, permission_name asc ";
	$database = new database;
	$application_permissions = $database->select($sql, $parameters, 'all');
	if (is_array($application_permissions) && @sizeof($application_permissions) != 0) {
		foreach ($application_permissions as $x => $row) {
			$array[$row['application_uuid']]['name'] = $row['application_name'];
			$array[$row['application_uuid']]['permissions'][$x]['uuid'] = $row['permission_uuid'];
			$array[$row['application_uuid']]['permissions'][$x]['name'] = $row['permission_name'];
			$array[$row['application_uuid']]['permissions'][$x]['description'] = $row['permission_description'];
			$array[$row['application_uuid']]['permissions'][$x]['assigned'] = $row['permission_assigned'];
		}
		$application_permissions = $array;
		unset($array);
	}
	unset($sql, $parameters);
//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-group_permissions'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-group_permissions']." (".escape($group_name).")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-sm-dn','link'=>'groups.php']);
	if (permission_exists('group_member_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-members'],'icon'=>'users','style'=>'margin-left: 15px;','link'=>'groupmembers.php?group_uuid='.urlencode($group_uuid)]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='hidden' name='group_uuid' value='".escape($group_uuid)."'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','collapse'=>'hide-sm-dn','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','collapse'=>'hide-sm-dn','link'=>'group_permissions.php?group_uuid='.urlencode($group_uuid),'style'=>($search == '' ? 'display: none;' : null)]);
	if (permission_exists('group_permission_edit')) {
		echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-sm-dn','style'=>'margin-left: 15px;','onclick'=>"document.getElementById('form_list').submit();"]);
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-group_permissions']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "<input type='hidden' name='group_uuid' value='".escape($group_uuid)."'>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	if (is_array($application_permissions) && @sizeof($application_permissions) != 0) {
		foreach ($application_permissions as $application_uuid => $application) {

			//output application heading
				if (is_array($application['permissions']) && @sizeof($application['permissions']) != 0) {

					$application_name = strtolower($application['name']);
					$label_application_name = ucwords(str_replace(['_','-'], ' ', $application['name']));

					echo "<b>".escape($label_application_name)."</b><br />\n";

					echo "<table class='list' style='margin-bottom: 25px;'>\n";
					echo "<tr class='list-header'>\n";
					if (permission_exists('group_permission_edit')) {
						echo "	<th class='checkbox'>\n";
						echo "		<input type='checkbox' id='checkbox_all_".$application_name."' name='checkbox_all' onclick=\"list_all_toggle('".$application_name."');\">\n";
						echo "	</th>\n";
					}
					echo "<th>".$text['label-group_name']."</th>\n";
					echo "</tr>\n";

					//output permissions
						foreach ($application['permissions'] as $x => $permission) {
							echo "<tr class='list-row'>\n";
							if (permission_exists('group_permission_edit')) {
								echo "	<td class='checkbox'>\n";
								echo "		<input type='checkbox' name='group_permissions[$x][checked]' id='checkbox_".$x."' class='checkbox_".$application_name."' value='true' ".($permission['assigned'] === true ? "checked='checked'" : null)." onclick=\"if (!this.checked) { document.getElementById('checkbox_all_".$application_name."').checked = false; }\">\n";
								echo "		<input type='hidden' name='group_permissions[$x][uuid]' value='".escape($permission['uuid'])."' />\n";
								echo "		<input type='hidden' name='group_permissions[$x][permission]' value='".escape($permission['name'])."' />\n";
								echo "	</td>\n";
							}
							echo "	<td class='no-wrap' onclick=\"if (document.getElementById('checkbox_".$x."').checked) { document.getElementById('checkbox_".$x."').checked = false; document.getElementById('checkbox_all_".$application_name."').checked = false; } else { document.getElementById('checkbox_".$x."').checked = true; }\">".escape($permission['name'])."</td>\n";
							echo "</tr>\n";
						}

					echo "</table>\n";

				}
		}
		unset($application_permissions);
	}

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>