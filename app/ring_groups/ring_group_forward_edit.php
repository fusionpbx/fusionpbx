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

//check permissions
	if (permission_exists('ring_group_add') || permission_exists('ring_group_edit') || permission_exists('ring_group_forward')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//show the header
	require_once "resources/header.php";
	//$document['title'] = $text['title-ring_group_forward'];

//get the hunt_group_uuid
	$ring_group_uuid = check_str($_REQUEST["id"]);

//process the HTTP post
	if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {
		//get http post variables and set them to php variables
			if (count($_POST)>0) {
				$ring_group_forward_enabled = check_str($_POST["ring_group_forward_enabled"]);
				$ring_group_forward_destination = check_str($_POST["ring_group_forward_destination"]);
				if (strlen($ring_group_forward_destination) > 0) {
					$ring_group_forward_destination = preg_replace("~[^0-9]~", "",$ring_group_forward_destination);
				}
			}

		//check for all required data
			//if (strlen($ring_group_forward_enabled) == 0) { $msg .= $text['message-required'].$text['label-call_forward']."<br>\n"; }
			//if (strlen($ring_group_forward_destination) == 0) { $msg .= $text['message-required'].$text['label-number']."<br>\n"; }
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

		//update the ring group
			$sql = "update v_ring_groups set ";
			$sql .= "ring_group_forward_enabled = '$ring_group_forward_enabled', ";
			$sql .= "ring_group_forward_destination = '$ring_group_forward_destination' ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and ring_group_uuid = '$ring_group_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);

		//redirect the user
			$_SESSION["message"] = $text['message-update'];
			header("Location: ".$_REQUEST['return_url']);
			return;
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$ring_group_uuid = $_GET["id"];
		$sql = "select * from v_ring_groups ";
		$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and ring_group_uuid = '$ring_group_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$ring_group_name = $row["ring_group_name"];
			$ring_group_extension = $row["ring_group_extension"];
			$ring_group_forward_enabled = $row["ring_group_forward_enabled"];
			$ring_group_forward_destination = $row["ring_group_forward_destination"];
			$ring_group_description = $row["ring_group_description"];
		}
		unset ($prep_statement);
	}

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<input type='hidden' name='return_url' value='".$_REQUEST['return_url']."'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap>\n";
	echo "	<b>".$text['header-ring-group-forward']."</b>\n";
	echo "</td>\n";
	echo "<td width='70%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='".$_REQUEST['return_url']."';\" value='".$text['button-back']."'>\n";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>\n";
	echo "	".$text['description-ring-group-forward']." ".$ring_group_extension."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	<strong>".$text['label-call-forward']."</strong>\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if ($ring_group_forward_enabled == "true") {
		echo "	<input type='radio' name='ring_group_forward_enabled' value='true' checked='checked'/> ".$text['option-enabled']." \n";
	}
	else {
		echo "	<input type='radio' name='ring_group_forward_enabled' value='true' /> ".$text['option-enabled']." \n";
	}
	if ($ring_group_forward_enabled == "false" || $ring_group_forward_enabled == "") {
		echo "	<input type='radio' name='ring_group_forward_enabled' value='false' checked='checked' /> ".$text['option-disabled']." \n";
	}
	else {
		echo "	<input type='radio' name='ring_group_forward_enabled' value='false' /> ".$text['option-disabled']." \n";
	}
	echo "<br />\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-forward_destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_forward_destination' maxlength='255' value=\"$ring_group_forward_destination\">\n";
	echo "<br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='id' value='$ring_group_uuid'>\n";
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//show the footer
	require_once "resources/footer.php";
?>