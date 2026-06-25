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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2020
	the Initial Developer. All Rights Reserved.
*/


// includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

// check permissions
	if (!(permission_exists('service_add') || permission_exists('service_edit'))) {
		echo "access denied";
		exit;
	}

// add multi-lingual support
	$language = new text;
	$text = $language->get();

// add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);

// set from session variables
	$button_icon_back = $settings->get('theme', 'button_icon_back', '');
	$button_icon_copy = $settings->get('theme', 'button_icon_copy', '');
	$button_icon_delete = $settings->get('theme', 'button_icon_delete', '');
	$button_icon_save = $settings->get('theme', 'button_icon_save', '');
	$input_toggle_style = $settings->get('theme', 'input_toggle_style', 'switch round');

// action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$service_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

// get http post variables and set them to php variables
	if (!empty($_POST)) {
		$service_name = $_POST["service_name"];
		$service_category = $_POST["service_category"];
		$service_enabled = $_POST["service_enabled"];
		$service_description = $_POST["service_description"];
	}

// process the data and save it to the database
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		// validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: services.php');
				exit;
			}

		// process the http post data by submitted action
			if ($_POST['action'] != '' && strlen($_POST['action']) > 0) {

				// prepare the array(s)
				switch ($_POST['action']) {
					case 'delete':
						if (permission_exists('service_delete')) {
							$obj = new services;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('service_update')) {
							$obj = new services;
							$obj->toggle($array);
						}
						break;
				}

				// redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle'))) {
					header('Location: service_edit.php?id='.$id);
					exit;
				}
			}

		// check for all required data
			$msg = '';
			if (strlen($service_name) == 0) { $msg .= $text['message-required']." ".$text['label-service_name']."<br>\n"; }
			if (strlen($service_category) == 0) { $msg .= $text['message-required']." ".$text['label-service_category']."<br>\n"; }
			if (strlen($service_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-service_enabled']."<br>\n"; }
			// if (strlen($service_description) == 0) { $msg .= $text['message-required']." ".$text['label-service_description']."<br>\n"; }
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

		// add the service_uuid
			if (!is_uuid($_POST["service_uuid"])) {
				$service_uuid = uuid();
			}

		// prepare the array
			$array['services'][0]['service_uuid'] = $service_uuid;
			$array['services'][0]['service_name'] = $service_name;
			$array['services'][0]['service_category'] = $service_category;
			$array['services'][0]['service_enabled'] = $service_enabled;
			$array['services'][0]['service_description'] = $service_description;

		// save the data
			$database->save($array);

		// redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				// header('Location: services.php');
				header('Location: service_edit.php?id='.urlencode($service_uuid));
				return;
			}
	}

// pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "select ";
		$sql .= " service_uuid, ";
		$sql .= " service_name, ";
		$sql .= " service_category, ";
		$sql .= " service_enabled , ";
		$sql .= " service_description ";
		$sql .= "from v_services ";
		$sql .= "where service_uuid = :service_uuid ";
		$parameters['service_uuid'] = $service_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$service_name = $row["service_name"];
			$service_category = $row["service_category"];
			$service_enabled = $row["service_enabled"];
			$service_description = $row["service_description"];
		}
		unset($sql, $parameters, $row);
	}

// create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

// show the header
	$document['title'] = $text['title-service'];
	require_once "resources/header.php";

// show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='service_uuid' value='".escape($service_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-service']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$button_icon_back,'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'services.php']);
	if ($action == 'update') {
		if (permission_exists('_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$button_icon_copy,'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$button_icon_delete,'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$button_icon_save,'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-services']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('service_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('service_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-service_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='service_name' maxlength='255' value='".escape($service_name)."'>\n";
	echo "<br />\n";
	echo $text['description-service_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-service_category']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='service_category' maxlength='255' value='".escape($service_category)."'>\n";
	echo "<br />\n";
	echo $text['description-service_category']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-service_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if ($input_toggle_style_switch) {
		echo "	<span class='switch'>\n";
	}
	echo "	<select class='formfld' id='service_enabled' name='service_enabled'>\n";
	echo "		<option value='true' ".($service_enabled == true ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "		<option value='false' ".($service_enabled == false ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	if ($input_toggle_style_switch) {
		echo "		<span class='slider'></span>\n";
		echo "	</span>\n";
	}
	echo "<br />\n";
	echo $text['description-service_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-service_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='service_description' maxlength='255' value='".escape($service_description)."'>\n";
	echo "<br />\n";
	echo $text['description-service_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

// include the footer
	require_once "resources/footer.php";

?>