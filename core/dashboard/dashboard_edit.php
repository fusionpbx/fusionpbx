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
	Portions created by the Initial Developer are Copyright (C) 2021
	the Initial Developer. All Rights Reserved.
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dashboard_add') || permission_exists('dashboard_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$dashboard_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_REQUEST) > 0) {
		$dashboard_name = $_POST["dashboard_name"];
		$dashboard_path = $_POST["dashboard_path"];
		$dashboard_groups = $_POST["dashboard_groups"];
		$dashboard_column_span = $_POST["dashboard_column_span"];
		$dashboard_details_state = $_POST["dashboard_details_state"];
		$dashboard_order = $_POST["dashboard_order"];
		$dashboard_enabled = $_POST["dashboard_enabled"] ?: 'false';
		$dashboard_description = $_POST["dashboard_description"];
	}

//delete the group from the sub table
	if ($_REQUEST["a"] == "delete" && permission_exists("dashboard_group_delete") && is_uuid($_GET["dashboard_group_uuid"]) && is_uuid($_GET["dashboard_uuid"])) {
		//get the uuid
			$dashboard_group_uuid = $_GET["dashboard_group_uuid"];
			$dashboard_uuid = $_GET["dashboard_uuid"];
		//delete the group from the users
			$array['dashboard_groups'][0]['dashboard_group_uuid'] = $dashboard_group_uuid;
			$database = new database;
			$database->app_name = 'dashboard';
			$database->app_uuid = '55533bef-4f04-434a-92af-999c1e9927f7';
			$database->delete($array);
			unset($array);
		//redirect the user
			message::add($text['message-delete']);
			header("Location: dashboard_edit.php?id=".urlencode($dashboard_uuid));
			return;
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {
		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: dashboard.php');
				exit;
			}

		//process the http post data by submitted action
			if ($_POST['action'] != '' && strlen($_POST['action']) > 0) {

				//prepare the array(s)
				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('dashboard_add')) {
							$obj = new database;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('dashboard_delete')) {
							$obj = new database;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('dashboard_update')) {
							$obj = new database;
							$obj->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: dashboard_edit.php?id='.$id);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			//if (strlen($dashboard_name) == 0) { $msg .= $text['message-required']." ".$text['label-dashboard_name']."<br>\n"; }
			//if (strlen($dashboard_path) == 0) { $msg .= $text['message-required']." ".$text['label-dashboard_path']."<br>\n"; }
			//if (strlen($dashboard_groups) == 0) { $msg .= $text['message-required']." ".$text['label-dashboard_groups']."<br>\n"; }
			//if (strlen($dashboard_order) == 0) { $msg .= $text['message-required']." ".$text['label-dashboard_order']."<br>\n"; }
			//if (strlen($dashboard_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-dashboard_enabled']."<br>\n"; }
			//if (strlen($dashboard_description) == 0) { $msg .= $text['message-required']." ".$text['label-dashboard_description']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//add the dashboard_uuid
			if (!is_uuid($_POST["dashboard_uuid"])) {
				$dashboard_uuid = uuid();
			}

		//prepare the array
			$array['dashboard'][0]['dashboard_uuid'] = $dashboard_uuid;
			$array['dashboard'][0]['dashboard_name'] = $dashboard_name;
			$array['dashboard'][0]['dashboard_path'] = $dashboard_path;
			$array['dashboard'][0]['dashboard_column_span'] = $dashboard_column_span;
			$array['dashboard'][0]['dashboard_details_state'] = $dashboard_details_state;
			$array['dashboard'][0]['dashboard_order'] = $dashboard_order;
			$array['dashboard'][0]['dashboard_enabled'] = $dashboard_enabled;
			$array['dashboard'][0]['dashboard_description'] = $dashboard_description;
			$y = 0;
			if (is_array($dashboard_groups)) {
				foreach ($dashboard_groups as $row) {
					if (isset($row['group_uuid'])) {
						$array['dashboard'][0]['dashboard_groups'][$y]['dashboard_group_uuid'] = uuid();
						$array['dashboard'][0]['dashboard_groups'][$y]['group_uuid'] = $row["group_uuid"];
						$y++;
					}
				}
			}

		//save the data
			$database = new database;
			$database->app_name = 'dashboard';
			$database->app_uuid = '55533bef-4f04-434a-92af-999c1e9927f7';
			$database->save($array);
			//$result = $database->message;
			//view_array($result);
			//exit;

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: dashboard.php');
				header('Location: dashboard_edit.php?id='.urlencode($dashboard_uuid));
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "select ";
		$sql .= " dashboard_uuid, ";
		$sql .= " dashboard_name, ";
		$sql .= " dashboard_path, ";
		$sql .= " dashboard_column_span, ";
		$sql .= " dashboard_details_state, ";
		$sql .= " dashboard_order, ";
		$sql .= " dashboard_enabled, ";
		$sql .= " dashboard_description ";
		$sql .= "from v_dashboard ";
		$sql .= "where dashboard_uuid = :dashboard_uuid ";
		$parameters['dashboard_uuid'] = $dashboard_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$dashboard_name = $row["dashboard_name"];
			$dashboard_path = $row["dashboard_path"];
			$dashboard_groups = $row["dashboard_groups"];
			$dashboard_column_span = $row["dashboard_column_span"];
			$dashboard_details_state = $row["dashboard_details_state"];
			$dashboard_order = $row["dashboard_order"];
			$dashboard_enabled = $row["dashboard_enabled"] ?: 'false';
			$dashboard_description = $row["dashboard_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the child data
	if (is_uuid($dashboard_uuid)) {
		$sql = "select ";
		$sql .= " dashboard_group_uuid, ";
		$sql .= " group_uuid ";
		$sql .= "from v_dashboard_groups ";
		$sql .= "where dashboard_uuid = :dashboard_uuid ";
		$parameters['dashboard_uuid'] = $dashboard_uuid;
		$database = new database;
		$dashboard_groups = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $dashboard_group_uuid
	if (!is_uuid($dashboard_group_uuid)) {
		$dashboard_group_uuid = uuid();
	}

//add a default value to $dashboard_details_state
	if (!isset($dashboard_details_state)) {
		$dashboard_details_state = "expanded";
	}

//add an empty row
	$x = is_array($dashboard_groups) ? count($dashboard_groups) : 0;
	$dashboard_groups[$x]['dashboard_uuid'] = $dashboard_uuid;
	$dashboard_groups[$x]['dashboard_group_uuid'] = uuid();
	$dashboard_groups[$x]['group_uuid'] = '';

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-dashboard'];
	require_once "resources/header.php";

//get the child groups
	$sql = "select * from v_dashboard_groups as x, v_groups as g ";
	$sql .= "where x.dashboard_uuid = :dashboard_uuid ";
	$sql .= "and x.group_uuid = g.group_uuid ";
	$parameters['dashboard_uuid'] = $dashboard_uuid;
	$database = new database;
	$dashboard_groups = $database->select($sql, $parameters, 'all');
	unset ($sql, $parameters);

//get the groups
	$sql = "SELECT group_uuid, group_name FROM v_groups ";
	$sql .= "WHERE (domain_uuid = :domain_uuid or domain_uuid is null)";
	$sql .= "ORDER by group_name asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$groups = $database->execute($sql, $parameters, 'all');
	unset ($sql, $parameters);

//set the assigned_groups array
	if (is_array($dashboard_groups) && sizeof($dashboard_groups) != 0) {
		$assigned_groups = array();
		foreach ($dashboard_groups as $field) {
			if (strlen($field['group_name']) > 0) {
				if (is_uuid($field['group_uuid'])) {
					$assigned_groups[] = $field['group_uuid'];
				}
			}
		}
	}

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='dashboard_uuid' value='".escape($dashboard_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dashboard']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'dashboard.php']);
	if ($action == 'update') {
		if (permission_exists('dashboard_group_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('dashboard_group_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-dashboard']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('dashboard_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('dashboard_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dashboard_name' maxlength='255' value='".escape($dashboard_name)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_path']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dashboard_path' maxlength='255' value='".escape($dashboard_path)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_path']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_groups']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (is_array($dashboard_groups) && sizeof($dashboard_groups) != 0) {
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		foreach($dashboard_groups as $field) {
			if (strlen($field['group_name']) > 0) {
				echo "<tr>\n";
				echo "	<td class='vtable' style='white-space: nowrap; padding-right: 30px;' nowrap='nowrap'>\n";
				echo $field['group_name'].(($field['group_domain_uuid'] != '') ? "@".$_SESSION['domains'][$field['group_domain_uuid']]['domain_name'] : null);
				echo "	</td>\n";
				if (permission_exists('dashboard_group_delete') || if_group("superadmin")) {
					echo "	<td class='list_control_icons' style='width: 25px;'>\n";
					echo 		"<a href='dashboard_edit.php?id=".escape($field['dashboard_group_uuid'])."&dashboard_group_uuid=".escape($field['dashboard_group_uuid'])."&dashboard_uuid=".escape($dashboard_uuid)."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
					echo "	</td>\n";
				}
				echo "</tr>\n";
			}
		}
		echo "</table>\n";
	}
	if (is_array($groups)) {
		echo "<br />\n";
		echo "<select name='dashboard_groups[0][group_uuid]' class='formfld' style='width: auto; margin-right: 3px;'>\n";
		echo "	<option value=''></option>\n";
		foreach($groups as $row) {
			if ($field['group_level'] <= $_SESSION['user']['group_level']) {
				if (!in_array($row["group_uuid"], $assigned_groups)) {
					echo "	<option value='".$row['group_uuid']."'>".$row['group_name'].(($row['domain_uuid'] != '') ? "@".$_SESSION['domains'][$row['domain_uuid']]['domain_name'] : null)."</option>\n";
				}
			}
		}
		echo "</select>\n";
		echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add']]);
	}
	echo "<br />\n";
	echo $text['description-dashboard_groups']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_column_span']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='dashboard_column_span' class='formfld'>\n";
	$i=1;
	while ($i<=5) {
		$selected = ($i == $dashboard_column_span) ? "selected" : null;
		echo "		<option value='$i' ".$selected.">$i</option>\n";
		$i++;
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_column_span']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_details_state']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='dashboard_details_state' class='formfld'>\n";
	if ($dashboard_details_state == "expanded") {
		echo "		<option value='expanded' selected='selected'>".$text['option-expanded']."</option>\n";
	}
	else {
		echo "		<option value='expanded'>".$text['option-expanded']."</option>\n";
	}
	if ($dashboard_details_state == "contracted") {
		echo "		<option value='contracted' selected='selected'>".$text['option-contracted']."</option>\n";
	}
	else {
		echo "		<option value='contracted'>".$text['option-contracted']."</option>\n";
	}
	if ($dashboard_details_state == "hidden") {
		echo "		<option value='hidden' selected='selected'>".$text['option-hidden']."</option>\n";
	}
	else {
		echo "		<option value='hidden'>".$text['option-hidden']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_details_state']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select name='dashboard_order' class='formfld'>\n";
	$i=0;
	while ($i<=999) {
		$selected = ($i == $dashboard_order) ? "selected" : null;
		if (strlen($i) == 1) {
			echo "		<option value='00$i' ".$selected.">00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "		<option value='0$i' ".$selected.">0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "		<option value='$i' ".$selected.">$i</option>\n";
		}
		$i++;
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-dashboard_order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='dashboard_enabled' name='dashboard_enabled' value='true' ".($dashboard_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='dashboard_enabled' name='dashboard_enabled'>\n";
		echo "		<option value='false'>".$text['option-false']."</option>\n";
		echo "		<option value='true' ".($dashboard_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-dashboard_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-dashboard_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='dashboard_description' maxlength='255' value='".escape($dashboard_description)."'>\n";
	echo "<br />\n";
	echo $text['description-dashboard_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
