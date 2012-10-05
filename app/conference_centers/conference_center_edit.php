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
if (permission_exists('conference_center_add') || permission_exists('conference_center_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$conference_center_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
		$conference_center_name = check_str($_POST["conference_center_name"]);
		$conference_center_extension = check_str($_POST["conference_center_extension"]);
		$conference_center_profile = check_str($_POST["conference_center_profile"]);
		$conference_center_description = check_str($_POST["conference_center_description"]);
		$conference_center_enabled = check_str($_POST["conference_center_enabled"]);

		//sanitize the conference name
		$conference_center_name = preg_replace("/[^A-Za-z0-9\- ]/", "", $conference_center_name);
		$conference_center_name = str_replace(" ", "-", $conference_center_name);
	}

/*
//delete the user from the v_conference_center_users
	if ($_GET["a"] == "delete" && permission_exists("conference_center_delete")) {
		//set the variables
			$user_uuid = check_str($_REQUEST["user_uuid"]);
			$conference_center_uuid = check_str($_REQUEST["id"]);
		//delete the group from the users
			$sql = "delete from v_conference_center_users ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and conference_center_uuid = '".$conference_center_uuid."' ";
			$sql .= "and user_uuid = '".$user_uuid."' ";
			$db->exec(check_sql($sql));
		//redirect the browser
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=conference_center_edit.php?id=$conference_center_uuid\">\n";
			echo "<div align='center'>Delete Complete</div>";
			require_once "includes/footer.php";
			return;
	}

//add the user to the v_conference_center_users
	if (strlen($_REQUEST["user_uuid"]) > 0 && strlen($_REQUEST["id"]) > 0 && $_GET["a"] != "delete") {
		//set the variables
			$user_uuid = check_str($_REQUEST["user_uuid"]);
			$conference_center_uuid = check_str($_REQUEST["id"]);
		//assign the user to the extension
			$sql_insert = "insert into v_conference_center_users ";
			$sql_insert .= "(";
			$sql_insert .= "conference_user_uuid, ";
			$sql_insert .= "domain_uuid, ";
			$sql_insert .= "conference_center_uuid, ";
			$sql_insert .= "user_uuid ";
			$sql_insert .= ")";
			$sql_insert .= "values ";
			$sql_insert .= "(";
			$sql_insert .= "'".uuid()."', ";
			$sql_insert .= "'".$_SESSION['domain_uuid']."', ";
			$sql_insert .= "'".$conference_center_uuid."', ";
			$sql_insert .= "'".$user_uuid."' ";
			$sql_insert .= ")";
			$db->exec($sql_insert);
		//redirect the browser
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=conference_center_edit.php?id=$conference_center_uuid\">\n";
			echo "<div align='center'>Add Complete</div>";
			require_once "includes/footer.php";
			return;
	}
*/

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$conference_center_uuid = check_str($_POST["conference_center_uuid"]);
	}

	//check for all required data
		//if (strlen($dialplan_uuid) == 0) { $msg .= "Please provide: Dialplan UUID<br>\n"; }
		if (strlen($conference_center_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		if (strlen($conference_center_extension) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		if (strlen($conference_center_profile) == 0) { $msg .= "Please provide: Profile<br>\n"; }
		//if (strlen($conference_center_order) == 0) { $msg .= "Please provide: Order<br>\n"; }
		//if (strlen($conference_center_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		if (strlen($conference_center_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
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
			if ($action == "add") {
				//prepare the uuids
					$conference_center_uuid = uuid();
					$dialplan_uuid = uuid();
				//add the conference
					$sql = "insert into v_conference_centers ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "conference_center_uuid, ";
					$sql .= "dialplan_uuid, ";
					$sql .= "conference_center_name, ";
					$sql .= "conference_center_extension, ";
					$sql .= "conference_center_profile, ";
					$sql .= "conference_center_description, ";
					$sql .= "conference_center_enabled ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'$domain_uuid', ";
					$sql .= "'$conference_center_uuid', ";
					$sql .= "'$dialplan_uuid', ";
					$sql .= "'$conference_center_name', ";
					$sql .= "'$conference_center_extension', ";
					$sql .= "'$conference_center_profile', ";
					$sql .= "'$conference_center_description', ";
					$sql .= "'$conference_center_enabled' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);

				//create the dialplan entry
					$dialplan_name = $conference_center_name;
					$dialplan_order ='333';
					$dialplan_context = $_SESSION['context'];
					$dialplan_enabled = 'true';
					$dialplan_description = $conference_center_description;
					$app_uuid = 'b81412e8-7253-91f4-e48e-42fc2c9a38d9';
					dialplan_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid);

					//<condition destination_number="500" />
					$dialplan_detail_tag = 'condition'; //condition, action, antiaction
					$dialplan_detail_type = 'destination_number';
					$dialplan_detail_data = '^'.$conference_center_extension.'$';
					$dialplan_detail_order = '010';
					$dialplan_detail_group = '2';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

					//<action application="lua" />
					$dialplan_detail_tag = 'action'; //condition, action, antiaction
					$dialplan_detail_type = 'lua';
					$dialplan_detail_data = 'conference_center.lua';
					$dialplan_detail_order = '020';
					$dialplan_detail_group = '2';
					dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

				//save the xml
					save_dialplan_xml();

				//apply settings reminder
					$_SESSION["reload_xml"] = true;

				//redirect the browser
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=conference_centers.php\">\n";
					echo "<div align='center'>\n";
					echo "Add Complete\n";
					echo "</div>\n";
					require_once "includes/footer.php";
					return;
			} //if ($action == "add")

			if ($action == "update") {
				//update the conference center extension
					$sql = "update v_conference_centers set ";
					$sql .= "conference_center_name = '$conference_center_name', ";
					$sql .= "conference_center_extension = '$conference_center_extension', ";
					$sql .= "conference_center_profile = '$conference_center_profile', ";
					$sql .= "conference_center_order = '$conference_center_order', ";
					$sql .= "conference_center_description = '$conference_center_description', ";
					$sql .= "conference_center_enabled = '$conference_center_enabled' ";
					$sql .= "where domain_uuid = '$domain_uuid' ";
					$sql .= "and conference_center_uuid = '$conference_center_uuid'";
					$db->exec(check_sql($sql));
					unset($sql);

				//udpate the conference center dialplan
					$sql = "update v_dialplans set ";
					$sql .= "dialplan_name = '$conference_center_name', ";
					if (strlen($dialplan_order) > 0) {
						$sql .= "dialplan_order = '333', ";
					}
					$sql .= "dialplan_context = '".$_SESSION['context']."', ";
					$sql .= "dialplan_enabled = 'true', ";
					$sql .= "dialplan_description = '$conference_center_description' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
					$db->query($sql);
					unset($sql);

				//update dialplan detail condition
					$sql = "update v_dialplan_details set ";
					$sql .= "dialplan_detail_data = '^".$conference_center_extension."$' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_detail_tag = 'condition' ";
					$sql .= "and dialplan_detail_type = 'destination_number' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
					$db->query($sql);
					unset($sql);

				//update dialplan detail action
					$dialplan_detail_type = 'lua';
					$dialplan_detail_data = 'conference_center.lua';
					$sql = "update v_dialplan_details set ";
					$sql .= "dialplan_detail_type = '".$dialplan_detail_type."', ";
					$sql .= "dialplan_detail_data = '".$dialplan_detail_data."' ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_detail_tag = 'action' ";
					$sql .= "and dialplan_detail_type = 'lua' ";
					$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
					$db->query($sql);

				//save the xml
					save_dialplan_xml();

				//apply settings reminder
					$_SESSION["reload_xml"] = true;

				//redirect the browser
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=conference_centers.php\">\n";
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
		$conference_center_uuid = $_GET["id"];
		$sql = "select * from v_conference_centers ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and conference_center_uuid = '$conference_center_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll();
		foreach ($result as &$row) {
			$dialplan_uuid = $row["dialplan_uuid"];
			$conference_center_name = $row["conference_center_name"];
			$conference_center_extension = $row["conference_center_extension"];
			$conference_center_profile = $row["conference_center_profile"];
			$conference_center_order = $row["conference_center_order"];
			$conference_center_description = $row["conference_center_description"];
			$conference_center_enabled = $row["conference_center_enabled"];
			$conference_center_name = str_replace("-", " ", $conference_center_name);
		}
		unset ($prep_statement);
	}

//set defaults
	if (strlen($conference_center_enabled) == 0) { $conference_center_enabled = "true"; }

//show the header
	require_once "includes/header.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing=''>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "	  <br>";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='30%' nowrap='nowrap'><b>Conference Center</b></td>\n";
	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='conference_centers.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "Conference Center is used to setup one or more conference rooms with a name, description, and optional pin number. \n";
	echo "Click on <a href='".PROJECT_PATH."/app/conferences_active/conference_interactive.php?c=".str_replace(" ", "-", $conference_center_name)."'>Active Conference</a> \n";
	echo "to monitor and interact with the conference room.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_name' maxlength='255' value=\"$conference_center_name\">\n";
	echo "<br />\n";
	echo "Enter the conference center name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Extension:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_extension' maxlength='255' value=\"$conference_center_extension\">\n";
	echo "<br />\n";
	echo "Enter the conference center extension number.\n";
	echo "</td>\n";
	echo "</tr>\n";

	/*
	if (if_group("admin") || if_group("superadmin")) {
		if ($action == "update") {
			echo "	<tr>";
			echo "		<td class='vncell' valign='top'>User List:</td>";
			echo "		<td class='vtable'>";

			echo "			<table width='52%'>\n";
			$sql = "SELECT * FROM v_conference_center_users as e, v_users as u ";
			$sql .= "where e.user_uuid = u.user_uuid  ";
			$sql .= "and u.user_enabled = 'true' ";
			$sql .= "and e.domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and e.conference_center_uuid = '".$conference_center_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			$result_count = count($result);
			foreach($result as $field) {
				echo "			<tr>\n";
				echo "				<td class='vtable'>".$field['username']."</td>\n";
				echo "				<td>\n";
				echo "					<a href='conference_center_edit.php?id=".$conference_center_uuid."&domain_uuid=".$_SESSION['domain_uuid']."&user_uuid=".$field['user_uuid']."&a=delete' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
				echo "				</td>\n";
				echo "			</tr>\n";
			}
			echo "			</table>\n";

			echo "			<br />\n";
			$sql = "SELECT * FROM v_users ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and user_enabled = 'true' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			echo "			<select name=\"user_uuid\" class='frm'>\n";
			echo "			<option value=\"\"></option>\n";
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $field) {
				echo "			<option value='".$field['user_uuid']."'>".$field['username']."</option>\n";
			}
			echo "			</select>";
			echo "			<input type=\"submit\" class='btn' value=\"Add\">\n";
			unset($sql, $result);
			echo "			<br>\n";
			echo "			Assign the users that are can manage this conference center extension.\n";
			echo "			<br />\n";
			echo "		</td>";
			echo "	</tr>";
		}
	}
	*/

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Profile:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='conference_center_profile'>\n";
	//if the profile has no value set it to default
	if ($conference_center_profile == "") { $conference_center_profile = "default"; }
	if ($conference_center_profile == "default") { echo "<option value='default' selected='selected'>default</option>\n"; } else {	echo "<option value='default'>default</option>\n"; }
	if ($conference_center_profile == "wideband") { echo "<option value='wideband' selected='selected'>wideband</option>\n"; } else {	echo "<option value='wideband'>wideband</option>\n"; }
	if ($conference_center_profile == "ultrawideband") { echo "<option value='ultrawideband' selected='selected'>ultrawideband</option>\n"; } else {	echo "<option value='ultrawideband'>ultrawideband</option>\n"; }
	if ($conference_center_profile == "cdquality") { echo "<option value='cdquality' selected='selected'>cdquality</option>\n"; } else {	echo "<option value='cdquality'>cdquality</option>\n"; }
	echo "    </select>\n";
	echo "<br />\n";
	echo "Conference Profile is a collection of settings for the conference center.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='conference_center_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($conference_center_enabled == "true") { 
		echo "	<option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($conference_center_enabled == "false") { 
		echo "	<option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "	<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select whether to enable or disable the conference center.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_center_description' maxlength='255' value=\"$conference_center_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='dialplan_uuid' value=\"$dialplan_uuid\">\n";
		echo "				<input type='hidden' name='conference_center_uuid' value='$conference_center_uuid'>\n";
	}
	echo "				<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

//include the footer
	require_once "includes/footer.php";
?>