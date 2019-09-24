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
	Portions created by the Initial Developer are Copyright (C) 2017-2019
	the Initial Developer. All Rights Reserved.
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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
		$id = $_REQUEST["id"];
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


		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: number_translations.php');
				exit;
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

		//add the number_translation_uuid
			if (!is_uuid($_POST["number_translation_uuid"])) {
				$number_translation_uuid = uuid();
			}

		//prepare the array
			$array['number_translations'][0]["number_translation_uuid"] = $number_translation_uuid;
			$array['number_translations'][0]["number_translation_name"] = $number_translation_name;
			$array['number_translations'][0]["number_translation_enabled"] = $number_translation_enabled;
			$array['number_translations'][0]["number_translation_description"] = $number_translation_description;
			$y = 0;
			foreach ($number_translation_details as $row) {
				if (strlen($row['number_translation_detail_regex']) > 0) {
					$array['number_translations'][0]['number_translation_details'][$y]["number_translation_detail_uuid"] = $row["number_translation_detail_uuid"];
					$array['number_translations'][0]['number_translation_details'][$y]["number_translation_detail_regex"] = $row["number_translation_detail_regex"];
					$array['number_translations'][0]['number_translation_details'][$y]["number_translation_detail_replace"] = $row["number_translation_detail_replace"];
					$array['number_translations'][0]['number_translation_details'][$y]["number_translation_detail_order"] = $row["number_translation_detail_order"];
					$y++;
				}
			}

		//save to the data
			$database = new database;
			$database->app_name = 'Number Translations';
			$database->app_uuid = '6ad54de6-4909-11e7-a919-92ebcb67fe33';
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: number_translation_edit.php?id='.urlencode($number_translation_uuid));
				return;
			}
	} //(is_array($_POST) && strlen($_POST["persistformvar"]) == 0)

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
		unset ($sql, $parameters, $row);
	}

//get the child data
	if (is_uuid($number_translation_uuid)) {
		$sql = "select * from v_number_translation_details ";
		$sql .= "where number_translation_uuid = :number_translation_uuid ";
		$parameters['number_translation_uuid'] = $number_translation_uuid;
		$database = new database;
		$number_translation_details = $database->select($sql, $parameters, 'all');
		unset ($sql, $parameters);
	}

//add the $number_translation_detail_uuid
	if (!is_uuid($number_translation_detail_uuid)) {
		$number_translation_detail_uuid = uuid();
	}

//add an empty row
	$x = count($number_translation_details);
	$number_translation_details[$x]['number_translation_uuid'] = $number_translation_uuid;
	$number_translation_details[$x]['number_translation_detail_uuid'] = uuid();
	$number_translation_details[$x]['number_translation_detail_regex'] = '';
	$number_translation_details[$x]['number_translation_detail_replace'] = '';
	$number_translation_details[$x]['number_translation_detail_order'] = '';

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap' valign='top'><b>".$text['title-number_translation']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='number_translations.php'\" value='".$text['button-back']."'>";
	//echo "	<input type='button' class='btn' name='' alt='".$text['button-copy']."' onclick=\"window.location='number_translation_copy.php'\" value='".$text['button-copy']."'>";
	echo "	<input type='submit' class='btn' value='".$text['button-save']."'>";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	".$text['description-number_translations']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-number_translation_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='number_translation_name' maxlength='255' value='".escape($number_translation_name)."'>\n";
	echo "<br />\n";
	echo $text['description-number_translation_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-number_translation_details']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<table>\n";
	echo "		<tr>\n";
	echo "			<th class='vtablereq'>".$text['label-number_translation_detail_regex']."</th>\n";
	echo "			<th class='vtablereq'>".$text['label-number_translation_detail_replace']."</th>\n";
	echo "			<th class='vtablereq'>".$text['label-number_translation_detail_order']."</th>\n";
	echo "			<td></td>\n";
	echo "		</tr>\n";
	$x = 0;
	foreach($number_translation_details as $row) {
		echo "		<tr>\n";
		echo "			<input type='hidden' name='number_translation_details[$x][number_translation_uuid]' value=\"".escape($row["number_translation_uuid"])."\">\n";
		echo "			<input type='hidden' name='number_translation_details[$x][number_translation_detail_uuid]' value=\"".escape($row["number_translation_detail_uuid"])."\">\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='number_translation_details[$x][number_translation_detail_regex]' maxlength='255' value=\"".escape($row["number_translation_detail_regex"])."\">\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<input class='formfld' type='text' name='number_translation_details[$x][number_translation_detail_replace]' maxlength='255' value=\"".escape($row["number_translation_detail_replace"])."\">\n";
		echo "			</td>\n";
		echo "			<td>\n";
		echo "				<select name='number_translation_details[$x][number_translation_detail_order]' class='formfld'>\n";
		$i=0;
		while ($i<=999) {
			$selected = ($i == $row["number_translation_detail_order"]) ? "selected" : null;
			if (strlen($i) == 1) {
				echo "					<option value='00$i' ".$selected.">00$i</option>\n";
			}
			if (strlen($i) == 2) {
				echo "					<option value='0$i' ".$selected.">0$i</option>\n";
			}
			if (strlen($i) == 3) {
				echo "					<option value='$i' ".$selected.">$i</option>\n";
			}
			$i++;
		}
		echo "				</select>\n";
		echo "			</td>\n";
		echo "			<td class='list_control_icons' style='width: 25px;'>\n";
		if ($x < sizeof($number_translation_details) - 1) {
			echo "				<a href='number_translation_delete.php?number_translation_detail_uuid=".escape($row['number_translation_detail_uuid'])."&a=delete' alt=\"".$text['button-delete']."\" onclick=\"return confirm('".$text['confirm-delete']."');\">".$v_link_label_delete."</a>\n";
		}
		echo "			</td>\n";
		echo "		</tr>\n";
		$x++;
	}
	echo "	</table>\n";
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
	echo "	<input class='formfld' type='text' name='number_translation_description' maxlength='255' value='".escape($number_translation_description)."'>\n";
	echo "<br />\n";
	echo $text['description-number_translation_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='number_translation_uuid' value='".escape($number_translation_uuid)."'>\n";
	echo "			<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "			<br />\n";
	echo "			<input type='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";

?>
