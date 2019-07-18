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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('call_recording_add') || permission_exists('call_recording_edit')) {
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
		$call_recording_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$call_recording_name = $_POST["call_recording_name"];
		$call_recording_path = $_POST["call_recording_path"];
		$call_recording_length = $_POST["call_recording_length"];
		$call_recording_date = $_POST["call_recording_date"];
		$call_direction = $_POST["call_direction"];
		$call_recording_description = $_POST["call_recording_description"];
		$call_recording_base64 = $_POST["call_recording_base64"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$call_recording_uuid = $_POST["call_recording_uuid"];
			}

		//check for all required data
			$msg = '';
			if (strlen($call_recording_name) == 0) { $msg .= $text['message-required']." ".$text['label-call_recording_name']."<br>\n"; }
			//if (strlen($call_recording_path) == 0) { $msg .= $text['message-required']." ".$text['label-call_recording_path']."<br>\n"; }
			if (strlen($call_recording_length) == 0) { $msg .= $text['message-required']." ".$text['label-call_recording_length']."<br>\n"; }
			if (strlen($call_recording_date) == 0) { $msg .= $text['message-required']." ".$text['label-call_recording_date']."<br>\n"; }
			if (strlen($call_direction) == 0) { $msg .= $text['message-required']." ".$text['label-call_direction']."<br>\n"; }
			//if (strlen($call_recording_description) == 0) { $msg .= $text['message-required']." ".$text['label-call_recording_description']."<br>\n"; }
			//if (strlen($call_recording_base64) == 0) { $msg .= $text['message-required']." ".$text['label-call_recording_base64']."<br>\n"; }
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

		//set the domain_uuid
				$_POST["domain_uuid"] = $_SESSION["domain_uuid"];

		//add the call_recording_uuid
			if (!is_uuid($_POST["call_recording_uuid"])) {
				$call_recording_uuid = uuid();
				$_POST["call_recording_uuid"] = $call_recording_uuid;
			}

		//prepare the array
			$array['call_recordings'][0] = $_POST;

		//save to the data
			$database = new database;
			$database->app_name = 'call_recordings';
			$database->app_uuid = null;
			if (strlen($call_recording_uuid) > 0) {
				$database->uuid($call_recording_uuid);
			}
			$database->save($array);
			$message = $database->message;

		//debug info
			//echo "<pre>";
			//print_r($message);
			//echo "</pre>";
			//exit;

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: call_recording_edit.php?id='.$call_recording_uuid);
				return;
			}
	} //(is_array($_POST) && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true" && is_uuid($_GET["id"])) {
		$call_recording_uuid = $_GET["id"];
		$sql = "select * from v_call_recordings ";
		$sql .= "where call_recording_uuid = :call_recording_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['call_recording_uuid'] = $call_recording_uuid;
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$call_recording_name = $row["call_recording_name"];
			$call_recording_path = $row["call_recording_path"];
			$call_recording_length = $row["call_recording_length"];
			$call_recording_date = $row["call_recording_date"];
			$call_direction = $row["call_direction"];
			$call_recording_description = $row["call_recording_description"];
			$call_recording_base64 = $row["call_recording_base64"];
		}
		unset($sql, $parameters, $row);
	}

//show the header
	require_once "resources/header.php";

//add the calendar
	echo "<script language='JavaScript' type='text/javascript'>\n";
	echo "	$(document).ready(function() {\n";
	echo "		apply_datetimepicker();\n";
	echo "	});\n";
	echo "	function apply_datetimepicker() {\n";
	echo "		$('.datetimepicker').datetimepicker({ format: 'YYYY-MM-DD HH:mm', showTodayButton: true, showClear: true, showClose: true });\n";
	echo "		$('.datepicker').datetimepicker({ format: 'YYYY-MM-DD', showClear: true, showClose: true });\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-call_recording']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='call_recordings.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_recording_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_recording_name' maxlength='255' value=\"".escape($call_recording_name)."\">\n";
	echo "<br />\n";
	echo $text['description-call_recording_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_recording_path']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_recording_path' maxlength='255' value=\"".escape($call_recording_path)."\">\n";
	echo "<br />\n";
	echo $text['description-call_recording_path']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_recording_length']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "  <input class='formfld' type='text' name='call_recording_length' maxlength='255' value='".escape($call_recording_length)."'>\n";
	echo "<br />\n";
	echo $text['description-call_recording_length']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_recording_date']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "			<input class='formfld datetimepicker' type='text' name='call_recording_date' maxlength='16' value=\"".escape($call_recording_date)."\">\n";
	echo "<br />\n";
	echo $text['description-call_recording_date']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_direction']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_direction' maxlength='255' value=\"".escape($call_direction)."\">\n";
	echo "<br />\n";
	echo $text['description-call_direction']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-call_recording_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_recording_description' maxlength='255' value=\"".escape($call_recording_description)."\">\n";
	echo "<br />\n";
	echo $text['description-call_recording_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='call_recording_uuid' value='".escape($call_recording_uuid)."'>\n";
	}
	echo "				<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
