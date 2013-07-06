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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('hunt_group_add') || permission_exists('hunt_group_edit') || permission_exists('hunt_group_call_forward')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

	//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

function destination_select($select_name, $select_value, $select_default) {
	if (strlen($select_value) == 0) { $select_value = $select_default; }
	echo "	<select class='formfld' style='width: 40px;' name='$select_name'>\n";
	echo "	<option value=''></option>\n";

	$i=5;
	while($i<=100) {
		if ($select_value == $i) {
			echo "	<option value='$i' selected='selected'>$i</option>\n";
		}
		else {
			echo "	<option value='$i'>$i</option>\n";
		}
		$i=$i+5;
	}
	echo "</select>\n";
}

//show the header
	require_once "resources/header.php";
	$page["title"] = $text['title-hunt-group_call_forward'];

//get the hunt_group_uuid
	$hunt_group_uuid = $_REQUEST["id"];

//hunt_group information used to determine if this is an add or an update
	$sql = "select * from v_hunt_groups ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and hunt_group_uuid = '$hunt_group_uuid' ";
	if (!(permission_exists('hunt_group_add') || permission_exists('hunt_group_edit'))) {
		$sql .= "and hunt_group_user_list like '%|".$_SESSION["username"]."|%' ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$hunt_group_uuid = $row["hunt_group_uuid"];
		$hunt_group_extension = $row["hunt_group_extension"];
	}
	unset ($prep_statement);

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	//get http post variables and set them to php variables
		if (count($_POST)>0) {
			$call_forward_enabled = check_str($_POST["call_forward_enabled"]);
			$call_forward_number = check_str($_POST["call_forward_number"]);
			$hunt_group_call_prompt = check_str($_POST["hunt_group_call_prompt"]);

			if (strlen($call_forward_number) > 0) {
				$call_forward_number = preg_replace("~[^0-9]~", "",$call_forward_number);
			}

			//set the default
				if (strlen($hunt_group_call_prompt) == 0) {
					$hunt_group_call_prompt = 'false';
				}
		}

	//check for all required data
		//if (strlen($call_forward_enabled) == 0) { $msg .= $text['message-required'].$text['label-call_forward']."<br>\n"; }
		//if (strlen($call_forward_number) == 0) { $msg .= $text['message-required'].$text['label-number']."<br>\n"; }
		//if (strlen($hunt_group_call_prompt) == 0) { $msg .= $text['message-required'].$text['label-call_prompt']."<br>\n"; }
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

	//call forward is enabled so disable the hunt group
		if ($call_forward_enabled == "true") {
			$sql = "update v_hunt_groups set hunt_group_enabled = 'false' ";
			$sql .= "where hunt_group_extension = '$hunt_group_extension' ";
			$sql .= "and (hunt_group_type = 'simultaneous' or hunt_group_type = 'sequentially') ";
			$db->exec(check_sql($sql));
		}

	//call forward is disabled so enable the hunt group
		if ($call_forward_enabled == "false" || $call_forward_enabled == "") {
			$sql = "update v_hunt_groups set hunt_group_enabled = 'true' ";
			$sql .= "where hunt_group_extension = '$hunt_group_extension' ";
			$sql .= "and (hunt_group_type = 'simultaneous' or hunt_group_type = 'sequentially') ";
			$db->exec(check_sql($sql));
		}

	//set the default action to add
		$call_forward_action = "add";

	//hunt_group information used to determine if this is an add or an update
		$sql = "select * from v_hunt_groups ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and hunt_group_type = 'call_forward' ";
		$sql .= "and hunt_group_extension in ( ";
		$sql .= "select hunt_group_extension from v_hunt_groups ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and hunt_group_uuid = '$hunt_group_uuid' ";
		if (!(permission_exists('hunt_group_add') || permission_exists('hunt_group_edit'))) {
			$sql .= "and hunt_group_user_list like '%|".$_SESSION["username"]."|%' ";
		}
		$sql .= ") ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			if ($row["hunt_group_type"] == 'call_forward') {
				$call_forward_action = "update";
				$call_forward_uuid = $row["hunt_group_uuid"];
			}
		}
		unset ($prep_statement);

	//call forward config
		$huntgroup_name = 'call_forward_'.$hunt_group_extension;
		$hunt_group_type = 'call_forward';
		$hunt_group_context = $_SESSION['context'];
		$hunt_group_timeout = '3600';
		$hunt_group_timeout_destination = $hunt_group_extension;
		$hunt_group_timeout_type = 'voicemail';
		$hunt_group_ring_back = 'us-ring';
		$hunt_group_cid_name_prefix = '';
		$hunt_group_pin = '';
		$huntgroup_caller_announce = 'false';
		$hunt_group_user_list = '';
		$hunt_group_enabled = $call_forward_enabled;
		$hunt_group_description = 'call forward '.$hunt_group_extension;

		if ($call_forward_action == "add" && permission_exists('hunt_group_add')) {
			$call_forward_uuid = uuid();
			$sql = "insert into v_hunt_groups ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "hunt_group_uuid, ";
			$sql .= "hunt_group_extension, ";
			$sql .= "hunt_group_name, ";
			$sql .= "hunt_group_type, ";
			$sql .= "hunt_group_context, ";
			$sql .= "hunt_group_timeout, ";
			$sql .= "hunt_group_timeout_destination, ";
			$sql .= "hunt_group_timeout_type, ";
			$sql .= "hunt_group_ringback, ";
			$sql .= "hunt_group_cid_name_prefix, ";
			$sql .= "hunt_group_pin, ";
			$sql .= "hunt_group_call_prompt, ";
			$sql .= "hunt_group_caller_announce, ";
			$sql .= "hunt_group_user_list, ";
			$sql .= "hunt_group_enabled, ";
			$sql .= "hunt_group_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$call_forward_uuid', ";
			$sql .= "'$hunt_group_extension', ";
			$sql .= "'$huntgroup_name', ";
			$sql .= "'$hunt_group_type', ";
			$sql .= "'$hunt_group_context', ";
			$sql .= "'$hunt_group_timeout', ";
			$sql .= "'$hunt_group_timeout_destination', ";
			$sql .= "'$hunt_group_timeout_type', ";
			$sql .= "'$hunt_group_ring_back', ";
			$sql .= "'$hunt_group_cid_name_prefix', ";
			$sql .= "'$hunt_group_pin', ";
			$sql .= "'$hunt_group_call_prompt', ";
			$sql .= "'$huntgroup_caller_announce', ";
			$sql .= "'$hunt_group_user_list', ";
			$sql .= "'$hunt_group_enabled', ";
			$sql .= "'$hunt_group_description' ";
			$sql .= ")";
			if ($v_debug) {
				echo $sql."<br />";
			}
			$db->exec(check_sql($sql));
			unset($sql);

		//delete related v_hunt_group_destinations
			$sql = "delete from v_hunt_group_destinations where hunt_group_uuid = '$call_forward_uuid' ";
			$db->exec(check_sql($sql));

		if (extension_exists($call_forward_number)) {
			$destination_data = $call_forward_number;
			$destination_type = 'extension';
		}
		$destination_profile = 'internal';
		$destination_timeout = '';
		$destination_order = '1';
		$destination_enabled = 'true';
		$destination_description = 'call forward';

		$hunt_group_destination_uuid = uuid();
		$sql = "insert into v_hunt_group_destinations ";
		$sql .= "(";
		$sql .= "domain_uuid, ";
		$sql .= "hunt_group_uuid, ";
		$sql .= "hunt_group_destination_uuid, ";
		$sql .= "destination_data, ";
		$sql .= "destination_type, ";
		$sql .= "destination_profile, ";
		$sql .= "destination_timeout, ";
		$sql .= "destination_order, ";
		$sql .= "destination_enabled, ";
		$sql .= "destination_description ";
		$sql .= ")";
		$sql .= "values ";
		$sql .= "(";
		$sql .= "'$domain_uuid', ";
		$sql .= "'$call_forward_uuid', ";
		$sql .= "'$hunt_group_destination_uuid', ";
		$sql .= "'$destination_data', ";
		$sql .= "'$destination_type', ";
		$sql .= "'$destination_profile', ";
		$sql .= "'$destination_timeout', ";
		$sql .= "'$destination_order', ";
		$sql .= "'$destination_enabled', ";
		$sql .= "'$destination_description' ";
		$sql .= ")";
		$db->exec(check_sql($sql));
		unset($sql);
	} //if ($call_forward_action == "add")

	if ($call_forward_action == "update" && permission_exists('hunt_group_call_forward')) {
		$sql = "update v_hunt_groups set ";
		$sql .= "hunt_group_extension = '$hunt_group_extension', ";
		$sql .= "hunt_group_name = '$huntgroup_name', ";
		$sql .= "hunt_group_type = '$hunt_group_type', ";
		$sql .= "hunt_group_context = '$hunt_group_context', ";
		$sql .= "hunt_group_timeout = '$hunt_group_timeout', ";
		$sql .= "hunt_group_timeout_destination = '$hunt_group_timeout_destination', ";
		$sql .= "hunt_group_timeout_type = '$hunt_group_timeout_type', ";
		$sql .= "hunt_group_ringback = '$hunt_group_ring_back', ";
		$sql .= "hunt_group_cid_name_prefix = '$hunt_group_cid_name_prefix', ";
		$sql .= "hunt_group_pin = '$hunt_group_pin', ";
		$sql .= "hunt_group_call_prompt = '$hunt_group_call_prompt', ";
		$sql .= "hunt_group_caller_announce = '$huntgroup_caller_announce', ";
		$sql .= "hunt_group_user_list = '$hunt_group_user_list', ";
		$sql .= "hunt_group_enabled = '$hunt_group_enabled', ";
		$sql .= "hunt_group_description = '$hunt_group_description' ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and hunt_group_uuid = '$call_forward_uuid'";
		$db->exec(check_sql($sql));
		unset($sql);

		//set the variables
			$destination_data = $call_forward_number;
			if (extension_exists($call_forward_number)) {
				$destination_type = 'extension';
			}
			else {
				$destination_type = 'sip uri';
			}
			$destination_profile = 'internal';
			$destination_timeout = '';
			$destination_order = '1';
			$destination_enabled = 'true';
			$destination_description = 'call forward';

		//delete related v_hunt_group_destinations
			$sql = "delete from v_hunt_group_destinations where hunt_group_uuid = '$call_forward_uuid' ";
			$db->exec(check_sql($sql));

		//insert the v_hunt_group_destinations
			$hunt_group_destination_uuid = uuid();
			$sql = "insert into v_hunt_group_destinations ";
			$sql .= "(";
			$sql .= "hunt_group_destination_uuid, ";
			$sql .= "domain_uuid, ";
			$sql .= "hunt_group_uuid, ";
			$sql .= "destination_data, ";
			$sql .= "destination_type, ";
			$sql .= "destination_profile, ";
			$sql .= "destination_timeout, ";
			$sql .= "destination_order, ";
			$sql .= "destination_enabled, ";
			$sql .= "destination_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$hunt_group_destination_uuid', ";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$call_forward_uuid', ";
			$sql .= "'$destination_data', ";
			$sql .= "'$destination_type', ";
			$sql .= "'$destination_profile', ";
			$sql .= "'$destination_timeout', ";
			$sql .= "'$destination_order', ";
			$sql .= "'$destination_enabled', ";
			$sql .= "'$destination_description' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
	} //if ($call_forward_action == "update")

	//synchronize the xml config
		save_hunt_group_xml();

	//synchronize the xml config
		save_dialplan_xml();

	//redirect the user
		require_once "resources/header.php";
		echo "<meta http-equiv=\"refresh\" content=\"3;url=".PROJECT_PATH."/app/hunt_group/hunt_group_call_forward.php\">\n";
		echo "<div align='center'>\n";
		echo $text['message-update']."<br />\n";
		echo "</div>\n";
		require_once "resources/footer.php";
		return;
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)



//pre-populate the form
	$sql = "select * from v_hunt_groups ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and hunt_group_type = 'call_forward' ";
	$sql .= "and hunt_group_extension = '$hunt_group_extension' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$hunt_group_uuid = $row["hunt_group_uuid"];
		$hunt_group_extension = $row["hunt_group_extension"];
		$huntgroup_name = $row["hunt_group_name"];
		$hunt_group_type = $row["hunt_group_type"];
		$hunt_group_context = $row["hunt_group_context"];
		$hunt_group_timeout = $row["hunt_group_timeout"];
		$hunt_group_timeout_destination = $row["hunt_group_timeout_destination"];
		$hunt_group_timeout_type = $row["hunt_group_timeout_type"];
		$hunt_group_ring_back = $row["hunt_group_ringback"];
		$hunt_group_cid_name_prefix = $row["hunt_group_cid_name_prefix"];
		$hunt_group_pin = $row["hunt_group_pin"];
		$hunt_group_call_prompt = $row["hunt_group_call_prompt"];
		$huntgroup_caller_announce = $row["hunt_group_caller_announce"];
		$hunt_group_user_list = $row["hunt_group_user_list"];
		$hunt_group_enabled = $row["hunt_group_enabled"];
		$hunt_group_description = $row["hunt_group_description"];

		if ($row["hunt_group_type"] == 'call_forward') {
			$call_forward_enabled = $hunt_group_enabled;
		}

		if ($row["hunt_group_type"] == 'call_forward') {
			$sql = "select * from v_hunt_group_destinations ";
			$sql .= "where hunt_group_uuid = '$hunt_group_uuid' ";
			$prep_statement_2 = $db->prepare(check_sql($sql));
			$prep_statement_2->execute();
			$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
			$x=1;
			foreach ($result2 as &$row2) {
				if ($row["hunt_group_type"] == 'call_forward') {
					if (strlen($row2["destination_data"]) > 0) {
						$call_forward_number = $row2["destination_data"];
					}
				}
			}
			unset ($prep_statement_2);
		}
	}
	unset ($prep_statement);

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap>\n";
	echo "	<b>".$text['header-hunt-group_call_forward']."</b>\n";
	echo "</td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='hunt_group_call_forward.php'\" value='".$text['button-back']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	".$text['description-hunt_group_call_forward_edit']." ".$hunt_group_extension."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	<strong>".$text['label-call_forward'].":</strong>\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if ($call_forward_enabled == "true") {
		echo "	<input type='radio' name='call_forward_enabled' value='true' checked='checked'/> ".$text['option-enabled']." \n";
	}
	else {
		echo "	<input type='radio' name='call_forward_enabled' value='true' /> ".$text['option-enabled']." \n";
	}
	if ($call_forward_enabled == "false" || $call_forward_enabled == "") {
		echo "	<input type='radio' name='call_forward_enabled' value='false' checked='checked' /> ".$text['option-disabled']." \n";
	}
	else {
		echo "	<input type='radio' name='call_forward_enabled' value='false' /> ".$text['option-disabled']." \n";
	}
	echo "<br />\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-number'].":\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='call_forward_number' maxlength='255' value=\"$call_forward_number\">\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='id' value='$call_forward_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//show the footer
	require_once "resources/footer.php";
?>
