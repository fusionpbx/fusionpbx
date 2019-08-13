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
	Portions created by the Initial Developer are Copyright (C) 2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('ring_group_add') || permission_exists('ring_group_edit')) {
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
		$ring_group_destination_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//set the parent uuid
	if (is_uuid($_GET["ring_group_uuid"])) {
		$ring_group_uuid = $_GET["ring_group_uuid"];
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$ring_group_uuid = $_POST["ring_group_uuid"];
		$destination_number = $_POST["destination_number"];
		$destination_delay = $_POST["destination_delay"];
		$destination_timeout = $_POST["destination_timeout"];
		$destination_prompt = $_POST["destination_prompt"];
	}

//define the destination_select function
	function destination_select($select_name, $select_value, $select_default) {
		if (strlen($select_value) == 0) { $select_value = $select_default; }
		echo "	<select class='formfld' name='$select_name'>\n";
		echo "	<option value=''></option>\n";
		$i = 0;
		while ($i <= 100) {
			if ($select_value == $i) {
				echo "	<option value='$i' selected='selected'>$i</option>\n";
			}
			else {
				echo "	<option value='$i'>$i</option>\n";
			}
			$i = $i + 5;
		}
		echo "</select>\n";
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$ring_group_destination_uuid = $_POST["ring_group_destination_uuid"];
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-domain_uuid']."<br>\n"; }
		//if (strlen($ring_group_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-ring_group_uuid']."<br>\n"; }
		//if (strlen($destination_number) == 0) { $msg .= $text['message-required']." ".$text['label-destination_number']."<br>\n"; }
		//if (strlen($destination_delay) == 0) { $msg .= $text['message-required']." ".$text['label-destination_delay']."<br>\n"; }
		//if (strlen($destination_timeout) == 0) { $msg .= $text['message-required']." ".$text['label-destination_timeout']."<br>\n"; }
		//if (strlen($destination_prompt) == 0) { $msg .= $text['message-required']." ".$text['label-destination_prompt']."<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "resources/header.php";
			require_once "resources/persistformvar.php";
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
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('ring_group_add')) {
				//begin array
					$ring_group_destination_uuid = uuid();
					$array['ring_group_destinations'][0]['ring_group_destination_uuid'] = $ring_group_destination_uuid;
				//grant temporary permissions
					$p = new permissions;
					$p->add('ring_group_destination_add', 'temp');
				//set message
					message::add($text['message-add']);
			}

			if ($action == "update" && permission_exists('ring_group_edit')) {
				//begin array
					$array['ring_group_destinations'][0]['ring_group_destination_uuid'] = $ring_group_destination_uuid;
				//grant temporary permissions
					$p = new permissions;
					$p->add('ring_group_destination_edit', 'temp');
				//set message
					message::add($text['message-update']);
			}

			if (is_array($array) && @sizeof($array) != 0) {
				//add common array elements
					$array['ring_group_destinations'][0]['domain_uuid'] = $domain_uuid;
					$array['ring_group_destinations'][0]['ring_group_uuid'] = $ring_group_uuid;
					$array['ring_group_destinations'][0]['destination_number'] = $destination_number;
					$array['ring_group_destinations'][0]['destination_delay'] = $destination_delay;
					$array['ring_group_destinations'][0]['destination_timeout'] = $destination_timeout;
					$array['ring_group_destinations'][0]['destination_prompt'] = strlen($destination_prompt) != 0 ? $destination_prompt : null;
				//execute insert/update
					$database = new database;
					$database->app_name = 'ring_groups';
					$database->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
					$database->save($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('ring_group_destination_add', 'temp');
					$p->delete('ring_group_destination_edit', 'temp');
				//redirect
					header("Location: ring_group_edit.php?id=".$ring_group_uuid);
					exit;
			}
		}

}

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$ring_group_destination_uuid = $_GET["id"];
		$sql = "select * from v_ring_group_destinations ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and ring_group_destination_uuid = :ring_group_destination_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['ring_group_destination_uuid'] = $ring_group_destination_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$ring_group_uuid = $row["ring_group_uuid"];
			$destination_number = $row["destination_number"];
			$destination_delay = $row["destination_delay"];
			$destination_timeout = $row["destination_timeout"];
			$destination_prompt = $row["destination_prompt"];
		}
		unset($sql, $parameters, $row);
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>".$text['title-ring_group_destination']."</b></td>\n";
	echo "<td width='70%' align='right'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='ring_group_edit.php?id=".escape($ring_group_uuid)."'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_number' maxlength='255' value=\"".escape($destination_number)."\">\n";
	echo "<br />\n";
	echo $text['description-destination_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_delay']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	destination_select('destination_delay', $destination_delay, '0');
	//echo "  <input class='formfld' type='text' name='destination_delay' maxlength='255' value='".escape($destination_delay)."'>\n";
	echo "<br />\n";
	echo $text['description-destination_delay']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_timeout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	destination_select('destination_timeout', $destination_timeout, '30');
	//echo "  <input class='formfld' type='text' name='destination_timeout' maxlength='255' value='".escape($destination_timeout)."'>\n";
	echo "<br />\n";
	echo $text['description-destination_timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('ring_group_prompt')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destination_prompt']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "					<select class='formfld' name='destination_prompt'>\n";
		echo "					<option value=''></option>\n";
		if ($destination_prompt == "1") {
			echo "					<option value='1' selected='selected'>".$text['label-destination_prompt_confirm']."</option>\n";
		}
		else {
			echo "					<option value='1'>".$text['label-destination_prompt_confirm']."</option>\n";
		}
		//if ($destination_prompt == "2") {
			//echo "					<option value='2'>".$text['label-destination_prompt_announce]."</option>\n";
		//}
		//else {
			//echo "					<option value='2'>".$text['label-destination_prompt_announce]."</option>\n";
		//}
		echo "					</select>\n";
		echo "<br />\n";
		echo $text['description-destination_prompt']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='ring_group_uuid' value='".escape($ring_group_uuid)."'>\n";
	if ($action == "update") {
		echo "		<input type='hidden' name='ring_group_destination_uuid' value='".escape($ring_group_destination_uuid)."'>\n";
	}
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>