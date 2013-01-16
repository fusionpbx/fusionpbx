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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('ivr_menu_add') || permission_exists('ivr_menu_edit')) {
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

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$ivr_menu_option_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the menu id
	if (strlen($_GET["ivr_menu_uuid"]) > 0) {
		$ivr_menu_uuid = check_str($_GET["ivr_menu_uuid"]);
	}

//get the http post variables and set them to php variables
	if (count($_POST)>0) {
		//$domain_uuid = check_str($_POST["domain_uuid"]);
		$ivr_menu_uuid = check_str($_POST["ivr_menu_uuid"]);
		$ivr_menu_option_digits = check_str($_POST["ivr_menu_option_digits"]);
		$ivr_menu_option_action = check_str($_POST["ivr_menu_option_action"]);
		$ivr_menu_option_param = check_str($_POST["ivr_menu_option_param"]);
		$ivr_menu_option_order = check_str($_POST["ivr_menu_option_order"]);
		$ivr_menu_option_description = check_str($_POST["ivr_menu_option_description"]);

		//set the default ivr_menu_option_action
			if (strlen($ivr_menu_option_action) == 0) {
				$ivr_menu_option_action = "menu-exec-app";
			}

		//seperate the action and the param
			$options_array = explode(":", $ivr_menu_option_param);
			$ivr_menu_option_action = array_shift($options_array);
			$ivr_menu_option_param = join(':', $options_array);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$ivr_menu_option_uuid = check_str($_POST["ivr_menu_option_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		//if (strlen($ivr_menu_uuid) == 0) { $msg .= "Please provide: ivr_menu_uuid<br>\n"; }
		if (strlen($ivr_menu_option_digits) == 0) { $msg .= "Please provide: Option<br>\n"; }
		//if (strlen($ivr_menu_option_action) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($ivr_menu_option_param) == 0) { $msg .= "Please provide: Destination<br>\n"; }
		if (strlen($ivr_menu_option_order) == 0) { $msg .= "Please provide: Order<br>\n"; }
		//if (strlen($ivr_menu_option_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
			require_once "includes/header.php";
			require_once "includes/persistformvar.php";
			echo "<div align='center'>\n";
			echo "<table><tr><td>\n";
			echo $msg."<br />";
			echo "</td></tr></table>\n";
			persistformvar($_POST);
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		}

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			//create the object
				require_once "includes/classes/database.php";
				require_once "resources/classes/switch_ivr_menu.php";
				$ivr = new switch_ivr_menu;
				$ivr->domain_uuid = $_SESSION["domain_uuid"];
				$ivr->ivr_menu_uuid = $ivr_menu_uuid;
				$ivr->ivr_menu_option_uuid = $ivr_menu_option_uuid;
				$ivr->ivr_menu_option_digits = $ivr_menu_option_digits;
				$ivr->ivr_menu_option_action = $ivr_menu_option_action;
				$ivr->ivr_menu_option_param = $ivr_menu_option_param;
				$ivr->ivr_menu_option_order = $ivr_menu_option_order;
				$ivr->ivr_menu_option_description = $ivr_menu_option_description;

			if ($action == "add" && permission_exists('ivr_menu_add')) {
				//run the add method in the ivr menu class
					$ivr_menu_option_uuid = uuid();
					$ivr->ivr_menu_option_uuid = $ivr_menu_option_uuid;
					$ivr->add();

				//redirect the user
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=ivr_menu_edit.php?id=$ivr_menu_uuid\">\n";
					echo "<div align='center'>\n";
					echo "Add Complete\n";
					echo "</div>\n";
					require_once "includes/footer.php";
					return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('ivr_menu_edit')) {
				//run the update method in the ivr menu class
					$ivr->ivr_menu_option_uuid = $ivr_menu_option_uuid;
					$ivr->update();

				//redirect the user
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=ivr_menu_edit.php?id=$ivr_menu_uuid\">\n";
					echo "<div align='center'>\n";
					echo "Update Complete\n";
					echo "</div>\n";
					require_once "includes/footer.php";
					return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$ivr_menu_option_uuid = $_GET["id"];
		$sql = "select * from v_ivr_menu_options ";
		$sql .= "where ivr_menu_option_uuid = '$ivr_menu_option_uuid' ";
		$sql .= "and domain_uuid = '$domain_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$ivr_menu_uuid = $row["ivr_menu_uuid"];
			$ivr_menu_option_digits = $row["ivr_menu_option_digits"];
			$ivr_menu_option_action = $row["ivr_menu_option_action"];
			$ivr_menu_option_param = $row["ivr_menu_option_param"];

			//if admin show only the param
				if (if_group("admin")) {
					$ivr_menu_options_label = $ivr_menu_option_param;
				}

			//if superadmin show both the action and param
				if (if_group("superadmin")) {
					$ivr_menu_options_label = $ivr_menu_option_action.':'.$ivr_menu_option_param;
				}

			$ivr_menu_option_order = $row["ivr_menu_option_order"];
			$ivr_menu_option_description = $row["ivr_menu_option_description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//send the content to the browser
	require_once "includes/header.php";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($action == "add") {
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>IVR Menu Option Add</b></td>\n";
	}
	if ($action == "update") {
		echo "<td align='left' width='30%' nowrap='nowrap' align='left'><b>IVR Menu Option Edit</b></td>\n";
	}
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='ivr_menu_edit.php?id=$ivr_menu_uuid'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='2' align='left'>\n";
	echo "The recording presents options to the caller. Options match key presses (DTMF digits) from the caller which directs the call to the destinations. <br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Option:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ivr_menu_option_digits' maxlength='255' value='$ivr_menu_option_digits'>\n";
	echo "<br />\n";
	echo "Any number between 1-5 digits or regular expressions.\n";
	echo "</td>\n";
	echo "</tr>\n";

	/*
	if (if_group("superadmin")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Type:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";

		echo "		<select name='ivr_menu_option_action' class='formfld'>\n";
		echo "		<option></option>\n";
		if (strlen($ivr_menu_option_action) == 0) {
			echo "		<option value='menu-exec-app' selected='selected'>menu-exec-app</option>\n";
		}
		else {
			if ($ivr_menu_option_action == "menu-exec-app") {
				echo "		<option value='menu-exec-app' selected='selected'>menu-exec-app</option>\n";
			}
			else {
				echo "		<option value='menu-exec-app'>menu-exec-app</option>\n";
			}
		}
		if ($ivr_menu_option_action == "menu-sub") {
			echo "		<option value='menu-sub' selected='selected'>menu-sub</option>\n";
		}
		else {
			echo "		<option value='menu-sub'>menu-sub</option>\n";
		}
		if ($ivr_menu_option_action == "menu-exec-app") {
			echo "		<option value='menu-exec-app' selected='selected'>menu-exec-app</option>\n";
		}
		else {
			echo "		<option value='menu-exec-app'>menu-exec-app</option>\n";
		}
		if ($ivr_menu_option_action == "menu-top") {
			echo "		<option value='menu-top' selected='selected'>menu-top</option>\n";
		}
		else {
			echo "		<option value='menu-top'>menu-top</option>\n";
		}
		if ($ivr_menu_option_action == "menu-playback") {
			echo "		<option value='menu-playback' selected='selected'>menu-playback</option>\n";
		}
		else {
			echo "		<option value='menu-playback'>menu-playback</option>\n";
		}
		if ($ivr_menu_option_action == "menu-exit") {
			echo "		<option value='menu-exit' selected='selected'>menu-exit</option>\n";
		}
		else {
			echo "		<option value='menu-exit'>menu-exit</option>\n";
		}
		echo "		</select>\n";

		echo "<br />\n";
		echo "The type is required when a custom destination is defined. \n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	*/

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Destination:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	//switch_select_destination($select_type, $select_label, $select_name, $select_value, $select_style, $action='')
	$tmp_select_value = '';
	if (strlen($ivr_menu_option_action.$ivr_menu_option_param) > 0) {
		$tmp_select_value = $ivr_menu_option_action.':'.$ivr_menu_option_param;
	}
	switch_select_destination("ivr", $ivr_menu_options_label, "ivr_menu_option_param", $tmp_select_value, "", $ivr_menu_option_action);
	unset($tmp_select_value);

	echo "<br />\n";
	echo "Select the destination.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	Order:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='ivr_menu_option_order' class='formfld'>\n";
	//echo "	<option></option>\n";
	if (strlen(htmlspecialchars($ivr_menu_option_order))> 0) {
		echo "	<option selected='yes' value='".htmlspecialchars($ivr_menu_option_order)."'>".htmlspecialchars($ivr_menu_option_order)."</option>\n";
	}
	$i=0;
	while($i<=999) {
		if (strlen($i) == 1) {
			echo "	<option value='00$i'>00$i</option>\n";
		}
		if (strlen($i) == 2) {
			echo "	<option value='0$i'>0$i</option>\n";
		}
		if (strlen($i) == 3) {
			echo "	<option value='$i'>$i</option>\n";
		}
		$i++;
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select the order.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ivr_menu_option_description' maxlength='255' value=\"$ivr_menu_option_description\">\n";
	echo "<br />\n";
	echo "Enter a description here for your reference.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	echo "			<input type='hidden' name='ivr_menu_uuid' value='$ivr_menu_uuid'>\n";
	if ($action == "update") {
		echo "			<input type='hidden' name='ivr_menu_option_uuid' value='$ivr_menu_option_uuid'>\n";
	}
	echo "			<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";

	echo "</table>\n";
	echo "</div>\n";
	echo "</form>\n";

	echo "	</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "</div>\n";

require_once "includes/footer.php";
?>