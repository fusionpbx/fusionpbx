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
	Portions created by the Initial Developer are Copyright (C) 2017-2018
	the Initial Developer. All Rights Reserved.
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('number_translation_add') || permission_exists('number_translation_edit')) {
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
		$number_translation_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$number_translation_uuid = $_POST["number_translation_uuid"];
		$number_translation_name = $_POST["number_translation_name"];
		$number_translation_details = $_POST["number_translation_details"];
		$number_translation_enabled = $_POST["number_translation_enabled"];
		$number_translation_description = $_POST["number_translation_description"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the uuid from the POST
			if ($action == "update") {
				$number_translation_uuid = $_POST["number_translation_uuid"];
			}

		//check for all required data
			$msg = '';
			if (strlen($number_translation_name) == 0) { $msg .= $text['message-required']." ".$text['label-number_translation_name']."<br>\n"; }
			//if (strlen($number_translation_details) == 0) { $msg .= $text['message-required']." ".$text['label-number_translation_details']."<br>\n"; }
			if (strlen($number_translation_enabled) == 0) { $msg .= $text['message-required']." ".$text['label-number_translation_enabled']."<br>\n"; }
			//if (strlen($number_translation_description) == 0) { $msg .= $text['message-required']." ".$text['label-number_translation_description']."<br>\n"; }
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

		//cleanup the array
			$x = 0;
			foreach ($_POST["number_translation_details"] as $row) {
				//unset the empty row
					if (strlen($_POST["number_translation_details"][$x]["number_translation_detail_regex"]) == 0) {
						unset($_POST["number_translation_details"][$x]);
					}
					if (strlen($_POST["number_translation_details"][$x]["number_translation_detail_replace"]) == 0) {
						unset($_POST["number_translation_details"][$x]);
					}
					if (strlen($_POST["number_translation_details"][$x]["number_translation_detail_order"]) == 0) {
						unset($_POST["number_translation_details"][$x]);
					}
				//increment the row
					$x++;
			}

		//add the number_translation_uuid
			if (!is_uuid($_POST["number_translation_uuid"])) {
				$number_translation_uuid = uuid();
				$_POST["number_translation_uuid"] = $number_translation_uuid;
			}

		//prepare the array
			$array['number_translations'][0] = $_POST;

		//save to the data
			$database = new database;
			$database->app_name = 'number_translations';
			$database->app_uuid = '6ad54de6-4909-11e7-a919-92ebcb67fe33';
			if (is_uuid($number_translation_uuid)) {
				$database->uuid($number_translation_uuid);
			}
			$database->save($array);
			$message = $database->message;

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: number_translation_edit.php?id='.escape($number_translation_uuid));
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$number_translation_uuid = $_GET["id"];
		$sql = "select * from v_number_translations ";
		$sql .= "where number_translation_uuid = :number_translation_uuid ";
		$parameters['number_translation_uuid'] = $number_translation_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$number_translation_name = $row["number_translation_name"];
			$number_translation_details = $row["number_translation_details"];
			$number_translation_enabled = $row["number_translation_enabled"];
			$number_translation_description = $row["number_translation_description"];
		}
		unset($sql, $parameters, $row);
	}

//get the child data
	if (is_uuid($number_translation_uuid)) {
		$sql = "select * from v_number_translation_details ";
		$sql .= "where number_translation_uuid = :number_translation_uuid ";
		$parameters['number_translation_uuid'] = $number_translation_uuid;
		$database = new database;
		$number_translation_details = $database->select($sql, $parameters, 'all');
	}

//add the $number_translation_uuid
	if (!is_uuid($number_translation_uuid)) {
		$number_translation_uuid = uuid();
	}

//add an empty row
	if (is_array($number_translation_details)) {
		$x = count($number_translation_details);
	}
	else {
		$number_translation_details = [];
		$x = 0;
	}
	$number_translation_details[$x]['number_translation_uuid'] = $number_translation_uuid;
	$number_translation_details[$x]['number_translation_detail_uuid'] = uuid();
	$number_translation_details[$x]['number_translation_detail_regex'] = '';
	$number_translation_details[$x]['number_translation_detail_replace'] = '';
	$number_translation_details[$x]['number_translation_detail_order'] = '';

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-number_translation']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='number_translations.php'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-number_translation_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='number_translation_name' maxlength='255' value=\"".escape($number_translation_name)."\">\n";
	echo "<br />\n";
	echo $text['description-number_translation_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-number_translation_details']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "			<table>\n";
	echo "				<tr>\n";
	echo "					<td class='vtable'>".$text['label-number_translation_detail_regex']."</td>\n";
	echo "					<td class='vtable'>".$text['label-number_translation_detail_replace']."</td>\n";
	echo "					<td class='vtable'>".$text['label-number_translation_detail_order']."</td>\n";
	echo "					<td class='vtable'></td>\n";
	echo "				</tr>\n";
	$x = 0;
	foreach($number_translation_details as $row) {
		echo "			<tr>\n";
		echo "				<input type='hidden' name='number_translation_details[$x][number_translation_uuid]' value=\"".escape($row["number_translation_uuid"])."\">\n";
		echo "				<input type='hidden' name='number_translation_details[$x][number_translation_detail_uuid]' value=\"".escape($row["number_translation_detail_uuid"])."\">\n";
		echo "				<td>\n";
		echo "					<input class='formfld' type='text' name='number_translation_details[$x][number_translation_detail_regex]' maxlength='255' value=\"".escape($row["number_translation_detail_regex"])."\">\n";
		echo "				</td>\n";
		echo "				<td>\n";
		echo "					<input class='formfld' type='text' name='number_translation_details[$x][number_translation_detail_replace]' maxlength='255' value=\"".escape($row["number_translation_detail_replace"])."\">\n";
		echo "				</td>\n";
		echo "				<td>\n";
		echo "					<input class='formfld' type='text' name='number_translation_details[$x][number_translation_detail_order]' maxlength='255' value=\"".escape($row["number_translation_detail_order"])."\">\n";
		echo "				</td>\n";
		echo "				<td class='list_control_icons' style='width: 25px;'>\n";
		if ($x+1 != @sizeof($number_translation_details)) {
			echo "				<a href=\"number_translation_delete.php?number_translation_detail_uuid=".escape($row["number_translation_detail_uuid"])."\" alt='delete' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
		}
		echo "				</td>\n";
		echo "			</tr>\n";
		$x++;
	}
	echo "			</table>\n";
	echo "<br />\n";
	echo $text['description-number_translation_detail_order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-number_translation_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<select class='formfld' name='number_translation_enabled'>\n";
	if ($number_translation_enabled == "true") {
		echo "		<option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "		<option value='true'>".$text['label-true']."</option>\n";
	}
	if ($number_translation_enabled == "false") {
		echo "		<option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "		<option value='false'>".$text['label-false']."</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-number_translation_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-number_translation_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='number_translation_description' maxlength='255' value=\"".escape($number_translation_description)."\">\n";
	echo "<br />\n";
	echo $text['description-number_translation_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='number_translation_uuid' value='".escape($number_translation_uuid)."'>\n";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
