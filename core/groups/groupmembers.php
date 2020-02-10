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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('group_members_view') || if_group("superadmin")) {
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

		header('Location: groupmembers.php?group_uuid='.urlencode($group_uuid));
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

//define the if group members function
	function is_group_member($group_uuid, $user_uuid) {
		global $domain_uuid;
		$sql = "select count(*) from v_user_groups ";
		$sql .= "where user_uuid = :user_uuid ";
		$sql .= "and group_uuid = :group_uuid ";
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['user_uuid'] = $user_uuid;
		$parameters['group_uuid'] = $group_uuid;
		$parameters['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : $_SESSION['domain_uuid'];
		$database = new database;
		$num_rows = $database->select($sql, $parameters, 'column');
		return $num_rows == 0 ? true : false;
		unset($sql, $parameters, $num_rows);
	}
	//$exampledatareturned = example("apples", 1);

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
	$result = $database->select($sql, $parameters, 'all');
	$num_rows = is_array($result) && @sizeof($result) != 0 ? sizeof($result) : 0;
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-group_members'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-group_members']." <i>".$group_name."</i> (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'groups.php']);
	if (permission_exists('group_member_delete') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'collapse'=>'hide-xs','style'=>'margin-right: 15px;','onclick'=>"if (confirm('".$text['confirm-delete']."')) { list_action_set('delete'); list_form_submit('form_list'); } else { this.blur(); return false; }"]);
	}
	if (permission_exists('group_member_add')) {
		echo 	"<form class='inline' method='post' action='groupmemberadd.php'>\n";
		echo "	<select name='user_uuid' class='formfld'>\n";
		echo "		<option value=''>".$text['label-select']."...</option>\n";
		foreach ($users as $field) {
			if (is_group_member($group_uuid, $field['user_uuid'])) {
				echo "<option value='".escape($field['user_uuid'])."'>".escape($field['username'])."</option>\n";
			}
		}
		unset($sql, $users);
		echo "	</select>";
		echo 	"<input type='hidden' name='domain_uuid' value='".(is_uuid($domain_uuid) ? escape($domain_uuid) : $_SESSION['domain_uuid'])."'>";
		echo 	"<input type='hidden' name='group_uuid' value='".escape($group_uuid)."'>";
		echo 	"<input type='hidden' name='group_name' value='".escape($group_name)."'>";
		echo 	"<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>";
		echo button::create(['type'=>'submit','label'=>$text['button-add_member'],'icon'=>$_SESSION['theme']['button_icon_add'],'collapse'=>'hide-xs']);
		echo "	</form>\n";
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='group_uuid' value='".escape($group_uuid)."'>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('group_member_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($result ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if (permission_exists('user_all')) {
		echo "<th class='pct-30'>".$text['label-domain']."</th>\n";
	}
	echo "	<th>".$text['label-username']."</th>\n";
	echo "</tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		$x = 0;
		foreach ($result as &$row) {
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

// 			echo "<a href='groupmemberdelete.php?user_uuid=".$row["user_uuid"]."&group_name=".$group_name."&group_uuid=".$row["group_uuid"]."' onclick=\"return confirm('".$text['confirm-delete']."')\" alt='".$text['button-delete']."'>".$v_link_label_delete."</a>";

			$user_groups[] = $row["user_uuid"];
		}
	}

	echo "</table>\n";
	echo "<br />";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>