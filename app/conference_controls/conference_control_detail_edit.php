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
	Portions created by the Initial Developer are Copyright (C) 2018-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('conference_control_detail_add') || permission_exists('conference_control_detail_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the defaults
	$control_digits = '';
	$control_action = '';
	$control_data = '';

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$conference_control_detail_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (!empty($_GET["conference_control_uuid"]) && is_uuid($_GET["conference_control_uuid"])) {
		$conference_control_uuid = $_GET["conference_control_uuid"];
	}

//get http post variables and set them to php variables
	if (!empty($_POST)) {
		$control_digits = $_POST["control_digits"];
		$control_action = $_POST["control_action"];
		$control_data = $_POST["control_data"];
		$control_enabled = $_POST["control_enabled"] ?? 'false';
	}

//process the http post
	if (!empty($_POST) && empty($_POST["persistformvar"])) {

		//get the uuid
			if ($action == "update") {
				$conference_control_detail_uuid = $_POST["conference_control_detail_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: conference_controls.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (empty($control_digits)) { $msg .= $text['message-required']." ".$text['label-control_digits']."<br>\n"; }
			if (empty($control_action)) { $msg .= $text['message-required']." ".$text['label-control_action']."<br>\n"; }
			//if (empty($control_data)) { $msg .= $text['message-required']." ".$text['label-control_data']."<br>\n"; }
			if (empty($control_enabled)) { $msg .= $text['message-required']." ".$text['label-control_enabled']."<br>\n"; }
			if (!empty($msg) && empty($_POST["persistformvar"])) {
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

		//add or update the database
			if (empty($_POST["persistformvar"])) {

				$array['conference_control_details'][0]['conference_control_uuid'] = $conference_control_uuid;
				$array['conference_control_details'][0]['control_digits'] = $control_digits;
				$array['conference_control_details'][0]['control_action'] = $control_action;
				$array['conference_control_details'][0]['control_data'] = $control_data;
				$array['conference_control_details'][0]['control_enabled'] = $control_enabled;

				if ($action == "add" && permission_exists('conference_control_detail_add')) {
					$array['conference_control_details'][0]['conference_control_detail_uuid'] = uuid();
					message::add($text['message-add']);
				}

				if ($action == "update" && permission_exists('conference_control_detail_edit')) {
					$array['conference_control_details'][0]['conference_control_detail_uuid'] = $conference_control_detail_uuid;
					message::add($text['message-update']);
				}

				if (is_uuid($array['conference_control_details'][0]['conference_control_detail_uuid'])) {
					$database = new database;
					$database->app_name = 'conference_controls';
					$database->app_uuid = 'e1ad84a2-79e1-450c-a5b1-7507a043e048';
					$database->save($array);
					unset($array);
				}

				header('Location: conference_control_edit.php?id='.$conference_control_uuid);
				exit;

			}
	}

//pre-populate the form
	if (!empty($_GET) && empty($_POST["persistformvar"])) {
		$conference_control_detail_uuid = $_GET["id"] ?? '';
		$sql = "select * from v_conference_control_details ";
		$sql .= "where conference_control_detail_uuid = :conference_control_detail_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['conference_control_detail_uuid'] = $conference_control_detail_uuid;
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$database = new database;
		$row = $database->select($sql, $parameters ?? null, 'row');
		if (!empty($row)) {
			$control_digits = $row["control_digits"];
			$control_action = $row["control_action"];
			$control_data = $row["control_data"];
			$control_enabled = $row["control_enabled"];
		}
		unset($sql, $parameters, $row);
	}

//set the defaults
	if (empty($control_enabled)) { $control_enabled = 'true'; }

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-conference_control_detail'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-conference_control_detail']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'conference_control_edit.php?id='.urlencode($conference_control_uuid ?? '')]);
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_digits']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='control_digits' maxlength='255' value='".escape($control_digits)."'>\n";
	echo "<br />\n";
	echo $text['description-control_digits']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_action']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='control_action' maxlength='255' value=\"".escape($control_action)."\">\n";
	echo "<br />\n";
	echo $text['description-control_action']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_data']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='control_data' maxlength='255' style='min-width: 50%;' value=\"".escape($control_data)."\">\n";
	echo "<br />\n";
	echo $text['description-control_data']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-control_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='control_enabled' name='control_enabled' value='true' ".($control_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='control_enabled' name='control_enabled'>\n";
		echo "		<option value='true' ".($control_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($control_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-control_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='conference_control_uuid' value='".escape($conference_control_uuid ?? '')."'>\n";
	if ($action == "update") {
		echo "<input type='hidden' name='conference_control_detail_uuid' value='".escape($conference_control_detail_uuid ?? '')."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
