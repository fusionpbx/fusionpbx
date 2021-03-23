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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('extension_setting_add') || permission_exists('extension_setting_edit')) {
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
		$extension_setting_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get the extension id
	if (is_uuid($_REQUEST["extension_setting_uuid"])) {
		$extension_setting_uuid = $_REQUEST["extension_setting_uuid"];
	}
	if (is_uuid($_REQUEST["extension_uuid"])) {
		$extension_uuid = $_REQUEST["extension_uuid"];
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$domain_uuid = $_POST["domain_uuid"];
		$extension_setting_type = $_POST["extension_setting_type"];
		$extension_setting_name = $_POST["extension_setting_name"];
		$extension_setting_value = $_POST["extension_setting_value"];
		$extension_setting_enabled = $_POST["extension_setting_enabled"];
		$extension_setting_description = $_POST["extension_setting_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: extension_settings.php?id='.$extension_uuid);
				exit;
			}

		//process the http post data by submitted action
			if ($_POST['action'] != '' && strlen($_POST['action']) > 0) {

				//prepare the array(s)
				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('extension_setting_add')) {
							$obj = new database;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('extension_setting_delete')) {
							$obj = new database;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('extension_setting_update')) {
							$obj = new database;
							$obj->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle')) && is_uuid($id) && is_uuid($extension_uuid)) {
					header('Location: extension_setting_edit.php?id='.$id.'&extension_uuid='.$extension_uuid);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
			if (strlen($extension_setting_type) == 0) { $msg .= $text['message-required']." ".$text['label-extension_setting_type']."<br>\n"; }
			if (strlen($extension_setting_name) == 0) { $msg .= $text['message-required']." ".$text['label-extension_setting_name']."<br>\n"; }
			//if (strlen($extension_setting_value) == 0) { $msg .= $text['message-required']." ".$text['label-extension_setting_value']."<br>\n"; }
			if (strlen($extension_setting_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-extension_setting_enabled']."<br>\n"; }
			//if (strlen($extension_setting_description) == 0) { $msg .= $text['message-required']." ".$text['label-extension_setting_description']."<br>\n"; }
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

		//add the extension_setting_uuid
			if (!is_uuid($extension_setting_uuid)) {
				$extension_setting_uuid = uuid();
			}

		//prepare the array
			$array['extension_settings'][0]['extension_setting_uuid'] = $extension_setting_uuid;
			$array['extension_settings'][0]['extension_uuid'] = $extension_uuid;
			$array['extension_settings'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			//$array['extension_settings'][0]['domain_uuid'] = $domain_uuid;
			$array['extension_settings'][0]['extension_setting_type'] = $extension_setting_type;
			$array['extension_settings'][0]['extension_setting_name'] = $extension_setting_name;
			$array['extension_settings'][0]['extension_setting_value'] = $extension_setting_value;
			$array['extension_settings'][0]['extension_setting_enabled'] = $extension_setting_enabled;
			$array['extension_settings'][0]['extension_setting_description'] = $extension_setting_description;

		//save the data
			$database = new database;
			$database->app_name = 'extension settings';
			$database->app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';
			$database->save($array);
		
		//clear the cache	
			$sql = "select extension, number_alias, user_context from v_extensions ";
			$sql .= "where extension_uuid = :extension_uuid ";
			$parameters['extension_uuid'] = $extension_uuid;
			$database = new database;
			$extension = $database->select($sql, $parameters, 'row');
			$cache = new cache;
			$cache->delete("directory:".$extension["extension"]."@".$extension["user_context"]);
			$cache->delete("directory:".$extension["number_alias"]."@".$extension["user_context"]);
		
		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: extension_settings.php');
				header('Location: extension_setting_edit.php?id='.urlencode($extension_setting_uuid).'&extension_uuid='.$extension_uuid);
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "select ";
		//$sql .= "extension_uuid, ";
		//$sql .= "domain_uuid, ";
		$sql .= "extension_setting_uuid, ";
		$sql .= "extension_setting_type, ";
		$sql .= "extension_setting_name, ";
		$sql .= "extension_setting_value, ";
		$sql .= "cast(extension_setting_enabled as text), ";
		$sql .= "extension_setting_description ";
		$sql .= "from v_extension_settings ";
		$sql .= "where extension_setting_uuid = :extension_setting_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['extension_setting_uuid'] = $extension_setting_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			if (is_uuid($row["extension_uuid"])) {
				$extension_uuid = $row["extension_uuid"];
			}
			//$domain_uuid = $row["domain_uuid"];
			$extension_setting_type = $row["extension_setting_type"];
			$extension_setting_name = $row["extension_setting_name"];
			$extension_setting_value = $row["extension_setting_value"];
			$extension_setting_enabled = $row["extension_setting_enabled"];
			$extension_setting_description = $row["extension_setting_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-extension_setting'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='extension_setting_uuid' value='".escape($extension_setting_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-extension_setting']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'extension_settings.php?id='.$extension_uuid]);
	if ($action == 'update') {
		if (permission_exists('_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-extension_settings']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('extension_setting_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('extension_setting_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	//echo "	".$text['label-domain_uuid']."\n";
	//echo "</td>\n";
	//echo "<td class='vtable' style='position: relative;' align='left'>\n";
	//echo "	<select class='formfld' name='domain_uuid'>\n";
	//if (strlen($domain_uuid) == 0) {
	//	echo "		<option value='' selected='selected'>".$text['select-global']."</option>\n";
	//}
	//else {
	//	echo "		<option value=''>".$text['label-global']."</option>\n";
	//}
	//foreach ($_SESSION['domains'] as $row) {
	//	if ($row['domain_uuid'] == $domain_uuid) {
	//		echo "		<option value='".$row['domain_uuid']."' selected='selected'>".escape($row['domain_name'])."</option>\n";
	//	}
	//	else {
	//		echo "		<option value='".$row['domain_uuid']."'>".$row['domain_name']."</option>\n";
	//	}
	//}
	//echo "	</select>\n";
	//echo "<br />\n";
	//echo $text['description-domain_uuid']."\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension_setting_type']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='extension_setting_type'>\n";
	echo "		<option value=''></option>\n";
	if ($extension_setting_type == "param") {
		echo "		<option value='param' selected='selected'>".$text['label-param']."</option>\n";
	}
	else {
		echo "		<option value='param'>".$text['label-param']."</option>\n";
	}
	if ($extension_setting_type == "variable") {
		echo "		<option value='variable' selected='selected'>".$text['label-variable']."</option>\n";
	}
	else {
		echo "		<option value='variable'>".$text['label-variable']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-extension_setting_type']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension_setting_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='extension_setting_name' maxlength='255' value='".escape($extension_setting_name)."'>\n";
	echo "<br />\n";
	echo $text['description-extension_setting_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension_setting_value']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='extension_setting_value' maxlength='255' value='".escape($extension_setting_value)."'>\n";
	echo "<br />\n";
	echo $text['description-extension_setting_value']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension_setting_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='extension_setting_enabled'>\n";
	if ($extension_setting_enabled == "true") {
		echo "		<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($extension_setting_enabled == "false") {
		echo "		<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-extension_setting_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension_setting_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='extension_setting_description' maxlength='255' value='".escape($extension_setting_description)."'>\n";
	echo "<br />\n";
	echo $text['description-extension_setting_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br /><br />\n";

	echo "<input type='hidden' name='extension_uuid' value='".$extension_uuid."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
