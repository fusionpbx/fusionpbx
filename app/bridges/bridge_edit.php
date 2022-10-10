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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2019
	the Initial Developer. All Rights Reserved.
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('bridge_add') && !permission_exists('bridge_edit')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$bridge_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$bridge_uuid = $_POST["bridge_uuid"];
		$bridge_name = $_POST["bridge_name"];
		$bridge_destination = $_POST["bridge_destination"];
		$bridge_enabled = $_POST["bridge_enabled"];
		$bridge_description = $_POST["bridge_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//delete the bridge
			if (permission_exists('bridge_delete')) {
				if ($_POST['action'] == 'delete' && is_uuid($bridge_uuid)) {
					//prepare
						$array[0]['checked'] = 'true';
						$array[0]['uuid'] = $bridge_uuid;
					//delete
						$obj = new bridges;
						$obj->delete($array);
					//redirect
						header('Location: bridges.php');
						exit;
				}
			}

		//get the uuid from the POST
			if ($action == "update") {
				$bridge_uuid = $_POST["bridge_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: bridges.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (strlen($bridge_name) == 0) { $msg .= $text['message-required']." ".$text['label-bridge_name']."<br>\n"; }
			if (strlen($bridge_destination) == 0) { $msg .= $text['message-required']." ".$text['label-bridge_destination']."<br>\n"; }
			if (strlen($bridge_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-bridge_enabled']."<br>\n"; }
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

		//add the bridge_uuid
			if (strlen($bridge_uuid) == 0) {
				$bridge_uuid = uuid();
			}

		//prepare the array
			$array['bridges'][0]['bridge_uuid'] = $bridge_uuid;
			$array['bridges'][0]['domain_uuid'] = $_SESSION["domain_uuid"];
			$array['bridges'][0]['bridge_name'] = $bridge_name;
			$array['bridges'][0]['bridge_destination'] = $bridge_destination;
			$array['bridges'][0]['bridge_enabled'] = $bridge_enabled;
			$array['bridges'][0]['bridge_description'] = $bridge_description;

		//save to the data
			$database = new database;
			$database->app_name = 'bridges';
			$database->app_uuid = 'a6a7c4c5-340a-43ce-bcbc-2ed9bab8659d';
			$database->save($array);
			$message = $database->message;

		//clear the destinations session array
			if (isset($_SESSION['destinations']['array'])) {
				unset($_SESSION['destinations']['array']);
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: bridges.php');
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$bridge_uuid = $_GET["id"];
		$sql = "select * from v_bridges ";
		$sql .= "where bridge_uuid = :bridge_uuid ";
		$parameters['bridge_uuid'] = $bridge_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$bridge_name = $row["bridge_name"];
			$bridge_destination = $row["bridge_destination"];
			$bridge_enabled = $row["bridge_enabled"];
			$bridge_description = $row["bridge_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-bridge'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-bridge']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'bridges.php']);
	if ($action == 'update' && permission_exists('bridge_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','name'=>'action','value'=>'save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update' && permission_exists('bridge_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-bridge_name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='bridge_name' maxlength='255' value='".escape($bridge_name)."'>\n";
	echo "<br />\n";
	echo $text['description-bridge_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-bridge_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='bridge_destination' maxlength='255' value='".escape($bridge_destination)."'>\n";
	echo "<br />\n";
	echo $text['description-bridge_destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-bridge_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='bridge_enabled'>\n";
	if ($bridge_enabled == "true") {
		echo "		<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($bridge_enabled == "false") {
		echo "		<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-bridge_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-bridge_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='bridge_description' maxlength='255' value=\"".escape($bridge_description)."\">\n";
	echo "<br />\n";
	echo $text['description-bridge_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	if ($action == "update") {
		echo "<input type='hidden' name='bridge_uuid' value='".escape($bridge_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>