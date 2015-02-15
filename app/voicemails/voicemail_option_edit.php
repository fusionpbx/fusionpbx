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
if (permission_exists('voicemail_add') || permission_exists('voicemail_edit')) {
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
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$voicemail_option_uuid = check_str($_REQUEST["id"]);
	}

//get the menu id
	if (strlen($_GET["voicemail_uuid"]) > 0) {
		$voicemail_uuid = check_str($_GET["voicemail_uuid"]);
	}

//get the http post variables and set them to php variables
	if (count($_POST)>0) {
		$voicemail_uuid = check_str($_POST["voicemail_uuid"]);
		$voicemail_option_digits = check_str($_POST["voicemail_option_digits"]);
		$voicemail_option_action = check_str($_POST["voicemail_option_action"]);
		$voicemail_option_param = check_str($_POST["voicemail_option_param"]);
		$voicemail_option_order = check_str($_POST["voicemail_option_order"]);
		$voicemail_option_description = check_str($_POST["voicemail_option_description"]);

		//set the default voicemail_option_action
			if (strlen($voicemail_option_action) == 0) {
				$voicemail_option_action = "menu-exec-app";
			}

		//seperate the action and the param
			$options_array = explode(":", $voicemail_option_param);
			$voicemail_option_action = array_shift($options_array);
			$voicemail_option_param = join(':', $options_array);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	$voicemail_option_uuid = check_str($_POST["voicemail_option_uuid"]);

	//check for all required data
		if (strlen($voicemail_option_digits) == 0) { $msg .= $text['message-required'].$text['label-option']."<br>\n"; }
		if (strlen($voicemail_option_order) == 0) { $msg .= $text['message-required'].$text['label-order']."<br>\n"; }
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

	//update the database
		if ($_POST["persistformvar"] != "true") {
			if (permission_exists('voicemail_edit')) {
				$sql = "update v_voicemail_options set ";
				$sql .= "voicemail_option_digits = '".$voicemail_option_digits."', ";
				$sql .= "voicemail_option_action = '".$voicemail_option_action."', ";
				$sql .= "voicemail_option_param = '".$voicemail_option_param."', ";
				$sql .= "voicemail_option_order = ".$voicemail_option_order.", ";
				$sql .= "voicemail_option_description = '".$voicemail_option_description."' ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and voicemail_option_uuid = '".$voicemail_option_uuid."' ";
				$db->exec(check_sql($sql));
				unset($sql);

				//redirect the user
					$_SESSION['message'] = $text['message-update'];
					header('Location: voicemail_edit.php?id='.$voicemail_uuid);
					return;
			}
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$voicemail_option_uuid = $_GET["id"];
		$sql = "select * from v_voicemail_options ";
		$sql .= "where voicemail_option_uuid = '".$voicemail_option_uuid."' ";
		$sql .= "and domain_uuid = '".$domain_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$voicemail_uuid = $row["voicemail_uuid"];
			$voicemail_option_digits = trim($row["voicemail_option_digits"]);
			$voicemail_option_action = $row["voicemail_option_action"];
			$voicemail_option_param = $row["voicemail_option_param"];

			//if admin show only the param
				if (if_group("admin")) {
					$voicemail_options_label = $voicemail_option_param;
				}

			//if superadmin show both the action and param
				if (if_group("superadmin")) {
					$voicemail_options_label = $voicemail_option_action.':'.$voicemail_option_param;
				}

			$voicemail_option_order = $row["voicemail_option_order"];
			$voicemail_option_description = $row["voicemail_option_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//send the content to the browser
	require_once "resources/header.php";
	$document['title'] = $text['title-voicemail_option'];

	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td align='left' width='30%' align='left' valign='top'>";
	echo "	<b>".$text['header-voicemail_option']."</b>";
	echo "	<br><br>";
	echo "</td>\n";
	echo "<td width='70%' align='right' nowrap='nowrap' valign='top'>";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='voicemail_edit.php?id=".$voicemail_uuid."'\" value='".$text['button-back']."'>";
	echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-option']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='voicemail_option_digits' maxlength='255' value='$voicemail_option_digits'>\n";
	echo "<br />\n";
	echo $text['description-option']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$tmp_select_value = '';
	if (strlen($voicemail_option_action.$voicemail_option_param) > 0) {
		$tmp_select_value = $voicemail_option_action.':'.$voicemail_option_param;
	}
	switch_select_destination("ivr", $voicemail_options_label, "voicemail_option_param", $tmp_select_value, "width: 350px;", $voicemail_option_action);
	unset($tmp_select_value);

	echo "<br />\n";
	echo $text['description-destination']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='voicemail_option_order' class='formfld'>\n";
	$i=0;
	while($i<=999) {
		$selected = ($voicemail_option_order == $i) ? "selected" : null;
		if (strlen($i) == 1) {
			echo "	<option value='00$i' ".$selected.">00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "	<option value='0$i' ".$selected.">0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "	<option value='$i' ".$selected.">$i</option>\n";
		}
		$i++;
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='voicemail_option_description' maxlength='255' value=\"".$voicemail_option_description."\">\n";
	echo "<br />\n";
	echo $text['description-description']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='voicemail_uuid' value='$voicemail_uuid'>\n";
	echo "			<input type='hidden' name='voicemail_option_uuid' value='$voicemail_option_uuid'>\n";
	echo "			<br>";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";

	echo "</table>\n";
	echo "</form>\n";

require_once "resources/footer.php";
?>