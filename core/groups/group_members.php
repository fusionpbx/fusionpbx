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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('group_member_view') || if_group("superadmin")) {
		//access allowed
	}
	else {
		echo "access denied";
		return;
	}

//requires a superadmin to view members of the superadmin group
	if (!if_group("superadmin") && $_GET["group_name"] == "superadmin") {
		echo "access denied";
		return;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (is_array($_POST['group_members'])) {
		$action = $_POST['action'];
		$group_uuid = $_POST['group_uuid'];
		$group_members = $_POST['group_members'];
	}

//process the http post data by action
	if ($action != '' && is_array($group_members) && @sizeof($group_members) != 0) {
		switch ($action) {
			case 'delete':
				if (permission_exists('group_member_delete') && is_uuid($group_uuid)) {
					$obj = new groups;
					$obj->group_uuid = $group_uuid;
					$obj->delete_members($group_members);
				}
				break;
		}

		header('Location: group_members.php?group_uuid='.urlencode($group_uuid));
		exit;
	}

//get the group uuid, lookup domain uuid (if any) and name
	$group_uuid = $_REQUEST['group_uuid'];
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

//get the the users array
	if (permission_exists('group_member_add')) {
		$sql = "select * from v_users where ";
		$sql .= "domain_uuid = :domain_uuid ";
		$sql .= "order by username ";
		$parameters['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : $_SESSION['domain_uuid'];
		$database = new database;
		$users = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
	}

//get the groups users
	$sql = "select u.user_uuid, u.username, ug.user_group_uuid, ug.domain_uuid, ug.group_uuid ";
	$sql .= "from v_user_groups as ug, v_users as u, v_domains as d ";
	$sql .= "where ug.user_uuid = u.user_uuid ";
	$sql .= "and ug.domain_uuid = d.domain_uuid ";
	if (is_uuid($domain_uuid)) {
		$sql .= "and ug.domain_uuid = :domain_uuid_ug ";
		$parameters['domain_uuid_ug'] = $domain_uuid;
	}
	if (!permission_exists('user_all')) {
		$sql .= "and u.domain_uuid = :domain_uuid_u ";
		$parameters['domain_uuid_u'] = $_SESSION['domain_uuid'];
	}
	$sql .= "and ug.group_uuid = :group_uuid ";
	$sql .= "order by d.domain_name asc, u.username asc ";
	$parameters['group_uuid'] = $group_uuid;
	$database = new database;
	$user_groups = $database->select($sql, $parameters, 'all');
	$num_rows = is_array($user_groups) && @sizeof($user_groups) != 0 ? sizeof($user_groups) : 0;
	unset($sql, $parameters);

//add group_member to the users array
	foreach ($users as &$field) {
		$field['group_member'] = 'false';
		foreach($user_groups as $row) {
			if ($row['user_uuid'] == $field['user_uuid']) {
				$field['group_member'] = 'true';
				break;
			}
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-group_members'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-group_members']." (".$group_name.": ".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'groups.php']);
	if (permission_exists('group_permission_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-permissions'],'icon'=>'key','style'=>'margin-right: 15px;','link'=>'group_permissions.php?group_uuid='.urlencode($group_uuid)]);
	}

	if (permission_exists('group_member_add')) {
		echo 	"<form class='inline' method='post' action='groupmemberadd.php'>\n";
		echo "	<select name='user_uuid' class='formfld'>\n";
		echo "		<option value=''>".$text['label-select']."...</option>\n";
		foreach ($users as $row) {
			if ($row['group_member'] === 'false') {
				echo "<option value='".escape($row['user_uuid'])."'>".escape($row['username'])."</option>\n";
			}
		}
		echo "	</select>\n";
		echo 	"<input type='hidden' name='domain_uuid' value='".(is_uuid($domain_uuid) ? escape($domain_uuid) : $_SESSION['domain_uuid'])."'>\n";
		echo 	"<input type='hidden' name='group_uuid' value='".escape($group_uuid)."'>\n";
		echo 	"<input type='hidden' name='group_name' value='".escape($group_name)."'>\n";
		echo 	"<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
		echo button::create(['type'=>'submit','label'=>$text['button-add_member'],'icon'=>$_SESSION['theme']['button_icon_add'],'collapse'=>'hide-xs']);
		echo "	</form>\n";
	}
	if (permission_exists('group_member_delete') && $user_groups) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','collapse'=>'hide-xs','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('group_member_delete') && $user_groups) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='group_uuid' value='".escape($group_uuid)."'>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('group_member_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($user_groups ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if (permission_exists('user_all')) {
		echo "<th class='pct-30'>".$text['label-domain']."</th>\n";
	}
	echo "	<th>".$text['label-username']."</th>\n";
	echo "</tr>\n";

	if (is_array($user_groups) && @sizeof($user_groups) != 0) {
		$x = 0;
		foreach ($user_groups as &$row) {
			echo "<tr class='list-row' href='".$list_row_url."'>";
			if (permission_exists('group_member_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='group_members[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='group_members[$x][uuid]' value='".escape($row['user_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if (permission_exists('user_all')) {
				echo "<td class='no-wrap' onclick=\"if (document.getElementById('checkbox_".$x."').checked) { document.getElementById('checkbox_".$x."').checked = false; document.getElementById('checkbox_all').checked = false; } else { document.getElementById('checkbox_".$x."').checked = true; }\">".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."</td>\n";
			}
			echo "<td class='no-wrap' onclick=\"if (document.getElementById('checkbox_".$x."').checked) { document.getElementById('checkbox_".$x."').checked = false; document.getElementById('checkbox_all').checked = false; } else { document.getElementById('checkbox_".$x."').checked = true; }\">".$row["username"]."</td>\n";
			echo "</tr>\n";
			$x++;
		}
	}

	echo "</table>\n";
	echo "<br />";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
