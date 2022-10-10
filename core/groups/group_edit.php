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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('group_add') || permission_exists('group_edit')) {
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
		$group_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$group_uuid = $_POST["group_uuid"];
		$group_name = $_POST["group_name"];
		$group_name_previous = $_POST["group_name_previous"];
		$domain_uuid = $_POST["domain_uuid"];
		$group_level = $_POST["group_level"];
		$group_protected = $_POST["group_protected"];
		$group_description = $_POST["group_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//process the http post data by submitted action
			if ($_POST['action'] != '' && is_uuid($group_uuid)) {
				$array[0]['checked'] = 'true';
				$array[0]['uuid'] = $group_uuid;

				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('group_add')) {
							$obj = new groups;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('group_delete')) {
							$obj = new groups;
							$obj->delete($array);
						}
						break;
				}

				header('Location: groups.php');
				exit;
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: groups.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($group_name) == 0) { $msg .= $text['message-required']." ".$text['label-group_name']."<br>\n"; }
			//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			if (strlen($group_level) == 0) { $msg .= $text['message-required']." ".$text['label-group_level']."<br>\n"; }
			//if (strlen($group_protected) == 0) { $msg .= $text['message-required']." ".$text['label-group_protected']."<br>\n"; }
			//if (strlen($group_description) == 0) { $msg .= $text['message-required']." ".$text['label-group_description']."<br>\n"; }
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

		//add the group_uuid
			if (!is_uuid($_POST["group_uuid"])) {
				$group_uuid = uuid();
			}

		//prepare the array
			$array['groups'][0]['group_uuid'] = $group_uuid;
			$array['groups'][0]['group_name'] = $group_name;
			$array['groups'][0]['domain_uuid'] = $domain_uuid;
			$array['groups'][0]['group_level'] = $group_level;
			$array['groups'][0]['group_protected'] = $group_protected;
			$array['groups'][0]['group_description'] = $group_description;

		//save the data
			$database = new database;
			$database->app_name = 'Group Manager';
			$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
			$database->save($array);

		//update group name in group permissions if group name changed
			if ($group_name != $group_name_previous) {
				$sql = "update v_group_permissions ";
				$sql .= "set group_name = :group_name ";
				$sql .= "where group_name = :group_name_previous ";
				$sql .= "and group_uuid = :group_uuid ";
				$parameters['group_name'] = $group_name;
				$parameters['group_name_previous'] = $group_name_previous;
				$parameters['group_uuid'] = $group_uuid;
				$database = new database;
				$database->app_name = 'Group Manager';
				$database->app_uuid = '2caf27b0-540a-43d5-bb9b-c9871a1e4f84';
				$database->execute($sql, $parameters);
				unset($sql, $parameters, $database);
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: group_edit.php?id='.urlencode($group_uuid));
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$group_uuid = $_GET["id"];
		$sql = "select * from v_groups ";
		$sql .= "where group_uuid = :group_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['group_uuid'] = $group_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$group_name = $row["group_name"];
			$domain_uuid = $row["domain_uuid"];
			$group_permissions = $row["group_permissions"];
			$group_members = $row["group_members"];
			$group_level = $row["group_level"];
			$group_protected = $row["group_protected"];
			$group_description = $row["group_description"];
		}
		unset ($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-group'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-group']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'groups.php']);
	$button_margin = 'margin-left: 15px;';
	if (permission_exists('group_permission_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-permissions'],'icon'=>'key','style'=>$button_margin,'link'=>'group_permissions.php?group_uuid='.urlencode($group_uuid)]);
		unset($button_margin);
	}
	if (permission_exists('group_member_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-members'],'icon'=>'users','style'=>$button_margin,'link'=>'group_members.php?group_uuid='.urlencode($group_uuid)]);
		unset($button_margin);
	}
	$button_margin = 'margin-left: 15px;';
	if ($action == 'update' && permission_exists('group_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','style'=>$button_margin,'onclick'=>"modal_open('modal-copy','btn_copy');"]);
		unset($button_margin);
	}
	if ($action == 'update' && permission_exists('group_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>$button_margin,'onclick'=>"modal_open('modal-delete','btn_delete');"]);
		unset($button_margin);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update' && permission_exists('group_add')) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
	}
	if ($action == 'update' && permission_exists('group_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo $text['description-groups']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-group_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='group_name' maxlength='255' value='".escape($group_name)."'>\n";
	echo "	<input type='hidden' name='group_name_previous' value='".escape($group_name)."'>\n";
	echo "<br />\n";
	echo $text['description-group_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-domain_uuid']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='domain_uuid'>\n";
	if (strlen($domain_uuid) == 0) {
		echo "		<option value='' selected='selected'>".$text['label-global']."</option>\n";
	}
	else {
		echo "		<option value=''>".$text['label-global']."</option>\n";
	}
	foreach ($_SESSION['domains'] as $row) {
		if ($row['domain_uuid'] == $domain_uuid) {
			echo "		<option value='".$row['domain_uuid']."' selected='selected'>".escape($row['domain_name'])."</option>\n";
		}
		else {
			echo "		<option value='".$row['domain_uuid']."'>".$row['domain_name']."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-domain_uuid']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-group_level']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<select class='formfld' name='group_level'>\n";
		echo "		<option value=''></option>\n";
		for ($l = 10; $l <=90; $l += 10) {
			$selected = $group_level == $l ? "selected='selected'" : null;
			echo "		<option value='".$l."' ".$selected.">".$l."</option>\n";
		}
		echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-group_level']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-group_protected']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='group_protected'>\n";
	echo "		<option value='false'>".$text['label-false']."</option>\n";
	echo "		<option value='true' ".($group_protected == "true" ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-group_protected']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-group_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='group_description' maxlength='255' value='".escape($group_description)."'>\n";
	echo "<br />\n";
	echo $text['description-group_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='group_uuid' value='".escape($group_uuid)."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
