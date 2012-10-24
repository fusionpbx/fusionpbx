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
if (if_group("admin") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//delete the user from the v_extension_users
	if ($_GET["a"] == "delete" && permission_exists("user_delete")) {
		//set the variables
			$ring_group_extension_uuid = check_str($_REQUEST["id"]);
			$ring_group_uuid = check_str($_REQUEST["ring_group_uuid"]);
		//delete the extension from the ring_group
			$sql = "delete from v_ring_group_extensions ";
			$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and ring_group_extension_uuid = '$ring_group_extension_uuid' ";
			$db->exec(check_sql($sql));
			unset($sql);
		//redirect the browser
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=ring_groups_edit.php?id=$ring_group_uuid\">\n";
			echo "<div align='center'>Delete Complete</div>";
			require_once "includes/footer.php";
			return;
	}

//action add or update
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$ring_group_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST)>0) {
		//set variables from http values
			$ring_group_name = check_str($_POST["ring_group_name"]);
			$ring_group_extension = check_str($_POST["ring_group_extension"]);
			$ring_group_context = check_str($_POST["ring_group_context"]);
			$ring_group_strategy = check_str($_POST["ring_group_strategy"]);
			$ring_group_timeout_sec = check_str($_POST["ring_group_timeout_sec"]);
			$ring_group_timeout_action = check_str($_POST["ring_group_timeout_action"]);
			$ring_group_cid_name_prefix = check_str($_POST["ring_group_cid_name_prefix"]);
			$ring_group_ringback = check_str($_POST["ring_group_ringback"]);
			$ring_group_enabled = check_str($_POST["ring_group_enabled"]);
			$ring_group_description = check_str($_POST["ring_group_description"]);
			$dialplan_uuid = check_str($_POST["dialplan_uuid"]);
			//$ring_group_timeout_action = "transfer:1001 XML default";
			$ring_group_timeout_array = explode(":", $ring_group_timeout_action);
			$ring_group_timeout_app = array_shift($ring_group_timeout_array);
			$ring_group_timeout_data = join(':', $ring_group_timeout_array);
			$extension_uuid = check_str($_POST["extension_uuid"]);

		//set the context for users that are not in the superadmin group
			if (!if_group("superadmin")) {
				if (count($_SESSION["domains"]) > 1) {
					$ring_group_context = $_SESSION['domain_name'];
				}
				else {
					$ring_group_context = "default";
				}
			}

	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$ring_group_uuid = check_str($_POST["ring_group_uuid"]);
	}

	//check for all required data
		if (strlen($ring_group_name) == 0) { $msg .= "Please provide: Name<br>\n"; }
		if (strlen($ring_group_extension) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		if (strlen($ring_group_strategy) == 0) { $msg .= "Please provide: Strategy<br>\n"; }
		if (strlen($ring_group_timeout_sec) == 0) { $msg .= "Please provide: Timeout<br>\n"; }
		if (strlen($ring_group_timeout_app) == 0) { $msg .= "Please provide: Timeout Action<br>\n"; }
		//if (strlen($ring_group_cid_name_prefix) == 0) { $msg .= "Please provide: Caller ID Prefix<br>\n"; }
		//if (strlen($ring_group_ringback) == 0) { $msg .= "Please provide: Ringback<br>\n"; }
		if (strlen($ring_group_enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($ring_group_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
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
					$ring_group_uuid = uuid();
					$dialplan_uuid = uuid();
				//add the ring group
					$sql = "insert into v_ring_groups ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "ring_group_uuid, ";
					$sql .= "ring_group_name, ";
					$sql .= "ring_group_extension, ";
					$sql .= "ring_group_context, ";
					$sql .= "ring_group_strategy, ";
					$sql .= "ring_group_timeout_sec, ";
					$sql .= "ring_group_timeout_app, ";
					$sql .= "ring_group_timeout_data, ";
					$sql .= "ring_group_cid_name_prefix, ";
					$sql .= "ring_group_ringback, ";
					$sql .= "ring_group_enabled, ";
					$sql .= "ring_group_description, ";
					$sql .= "dialplan_uuid ";
					$sql .= ")";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$_SESSION['domain_uuid']."', ";
					$sql .= "'".$ring_group_uuid."', ";
					$sql .= "'$ring_group_name', ";
					$sql .= "'$ring_group_extension', ";
					$sql .= "'$ring_group_context', ";
					$sql .= "'$ring_group_strategy', ";
					$sql .= "'$ring_group_timeout_sec', ";
					$sql .= "'$ring_group_timeout_app', ";
					$sql .= "'$ring_group_timeout_data', ";
					$sql .= "'$ring_group_cid_name_prefix', ";
					$sql .= "'$ring_group_ringback', ";
					$sql .= "'$ring_group_enabled', ";
					$sql .= "'$ring_group_description', ";
					$sql .= "'$dialplan_uuid' ";
					$sql .= ")";
					$db->exec(check_sql($sql));
					unset($sql);
			} //if ($action == "add")

			if ($action == "update") {
				$sql = "update v_ring_groups set ";
				$sql .= "ring_group_name = '$ring_group_name', ";
				$sql .= "ring_group_extension = '$ring_group_extension', ";
				if (if_group("superadmin")) {
					$sql .= "ring_group_context = '$ring_group_context', ";
				}
				$sql .= "ring_group_strategy = '$ring_group_strategy', ";
				$sql .= "ring_group_timeout_sec = '$ring_group_timeout_sec', ";
				$sql .= "ring_group_timeout_app = '$ring_group_timeout_app', ";
				$sql .= "ring_group_timeout_data = '$ring_group_timeout_data', ";
				$sql .= "ring_group_cid_name_prefix = '$ring_group_cid_name_prefix', ";
				$sql .= "ring_group_ringback = '$ring_group_ringback', ";
				$sql .= "ring_group_enabled = '$ring_group_enabled', ";
				$sql .= "ring_group_description = '$ring_group_description' ";
				//$sql .= "dialplan_uuid = '$dialplan_uuid' ";
				$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "and ring_group_uuid = '$ring_group_uuid' ";
				$db->exec(check_sql($sql));
				unset($sql);
			}

			if ($action == "update" || $action == "add") {
				//if extension_uuid then add it to ring group extensions
					if (strlen($extension_uuid) > 0) {
						$ring_group_extension_uuid = uuid();
						$sql = "insert into v_ring_group_extensions ";
						$sql .= "(";
						$sql .= "domain_uuid, ";
						$sql .= "ring_group_uuid, ";
						$sql .= "ring_group_extension_uuid, ";
						$sql .= "extension_uuid ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".$_SESSION['domain_uuid']."', ";
						$sql .= "'$ring_group_uuid', ";
						$sql .= "'$ring_group_extension_uuid', ";
						$sql .= "'$extension_uuid' ";
						$sql .= ")";
						$db->exec(check_sql($sql));
						unset($sql);
					}

				//if it does not exist in the dialplan then add it
					$sql = "select count(*) as num_rows from v_dialplans ";
					$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
					$db->exec(check_sql($sql));
					$prep_statement = $db->prepare(check_sql($sql));
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						if ($row['num_rows'] == 0) {
							//add the dialplan entry
								$dialplan_name = $ring_group_name;
								$dialplan_order ='333';
								$dialplan_context = $ring_group_context;
								$dialplan_enabled = 'true';
								$dialplan_description = $ring_group_description;
								$app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
								dialplan_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid);

								//<condition destination_number="500" />
								$dialplan_detail_tag = 'condition'; //condition, action, antiaction
								$dialplan_detail_type = 'destination_number';
								$dialplan_detail_data = '^'.$ring_group_extension.'$';
								$dialplan_detail_order = '000';
								$dialplan_detail_group = '1';
								dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

								//<action application="set" data="ring_group_uuid="/>
								$dialplan_detail_tag = 'action'; //condition, action, antiaction
								$dialplan_detail_type = 'set';
								$dialplan_detail_data = 'ring_group_uuid='.$ring_group_uuid;
								$dialplan_detail_order = '010';
								$dialplan_detail_group = '1';
								dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

								//<action application="lua" data="ring_group.lua"/>
								$dialplan_detail_tag = 'action'; //condition, action, antiaction
								//$dialplan_detail_type = 'transfer';
								//$dialplan_detail_data = $ring_group_extension . ' LUA ring_group.lua';
								$dialplan_detail_type = 'lua';
								$dialplan_detail_data = 'ring_group.lua';
								$dialplan_detail_order = '030';
								$dialplan_detail_group = '1';
								dialplan_detail_add($_SESSION['domain_uuid'], $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data);

							//save the xml
								save_dialplan_xml();

							//apply settings reminder
								$_SESSION["reload_xml"] = true;
						}
					}

				//redirect the browser
					require_once "includes/header.php";
					echo "<meta http-equiv=\"refresh\" content=\"2;url=ring_groups_edit.php?id=$ring_group_uuid\">\n";
					echo "<div align='center'>\n";
					if ($action == "add") {
						echo "Add Complete\n";
					}
					if ($action == "update") {
						echo "Update Complete\n";
					}
					echo "</div>\n";
					require_once "includes/footer.php";
					exit;
			}
		} //if ($_POST["persistformvar"] != "true") 
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
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
			$ring_group_context = $row["ring_group_context"];
			$ring_group_strategy = $row["ring_group_strategy"];
			$ring_group_timeout_sec = $row["ring_group_timeout_sec"];
			$ring_group_timeout_app = $row["ring_group_timeout_app"];
			$ring_group_timeout_data = $row["ring_group_timeout_data"];
			$ring_group_cid_name_prefix = $row["ring_group_cid_name_prefix"];
			$ring_group_ringback = $row["ring_group_ringback"];
			$ring_group_enabled = $row["ring_group_enabled"];
			$ring_group_description = $row["ring_group_description"];
			$dialplan_uuid = $row["dialplan_uuid"];
		}
		unset ($prep_statement);
		if (strlen($ring_group_timeout_app) > 0) {
			$ring_group_timeout_action = $ring_group_timeout_app.":".$ring_group_timeout_data;
		}
	}

//set defaults
	if (strlen($ring_group_timeout_sec) == 0) { $ring_group_timeout_sec = '30'; }
	if (strlen($ring_group_enabled) == 0) { $ring_group_enabled = 'true'; }

//set the context for users that are not in the superadmin group
	if (strlen($ring_group_context) == 0) {
		if (count($_SESSION["domains"]) > 1) {
			$ring_group_context = $_SESSION['domain_name'];
		}
		else {
			$ring_group_context = "default";
		}
	}

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
	echo "<td align='left' width='30%' nowrap='nowrap'><b>Ring Group</b></td>\n";

	echo "<td width='70%' align='right'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='ring_groups.php'\" value='Back'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "A ring group is a set of extensions that can be called with a ring strategy.<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Name:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_name' maxlength='255' value=\"$ring_group_name\">\n";
	echo "<br />\n";
	echo "Enter the name.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Extension:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_extension' maxlength='255' value=\"$ring_group_extension\">\n";
	echo "<br />\n";
	echo "Enter the extension.\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (if_group("superadmin")) {
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	Context:\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='ring_group_context' maxlength='255' value=\"$ring_group_context\">\n";
		echo "<br />\n";
		echo "Enter the context.\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Strategy:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_strategy'>\n";
	echo "	<option value=''></option>\n";
	if ($ring_group_strategy == "sequence") { 
		echo "	<option value='sequence' selected='selected'>sequence</option>\n";
	}
	else {
		echo "	<option value='sequence'>sequence</option>\n";
	}
	if ($ring_group_strategy == "simultaneous") { 
		echo "	<option value='simultaneous' selected='selected'>simultaneous</option>\n";
	}
	else {
		echo "	<option value='simultaneous'>simultaneous</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select the strategy.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>Extensions:</td>";
	echo "		<td class='vtable' align='left'>";
	if ($action == "update") {
		echo "			<table width='52%'>\n";
		$sql = "SELECT g.ring_group_extension_uuid, e.extension_uuid, e.extension ";
		$sql .= "FROM v_ring_groups as r, v_ring_group_extensions as g, v_extensions as e ";
		$sql .= "where g.ring_group_uuid = r.ring_group_uuid  ";
		$sql .= "and g.domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$sql .= "and g.ring_group_uuid = '".$ring_group_uuid."' ";
		$sql .= "and e.extension_uuid = g.extension_uuid ";
		$sql .= "order by e.extension asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		foreach($result as $field) {
			echo "			<tr>\n";
			echo "				<td class='vtable'>".$field['extension']."</td>\n";
			echo "				<td>\n";
			echo "					<a href='ring_groups_edit.php?id=".$field['ring_group_extension_uuid']."&ring_group_uuid=".$ring_group_uuid."&a=delete' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			echo "				</td>\n";
			echo "			</tr>\n";
		}
		echo "			</table>\n";
	}
	echo "			<br />\n";
	$sql = "SELECT * FROM v_extensions ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "order by extension asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	echo "			<select name=\"extension_uuid\" class='frm'>\n";
	echo "			<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $field) {
		echo "			<option value='".$field['extension_uuid']."'>".$field['extension']."</option>\n";
	}
	echo "			</select>";
	if ($action == "update") {
		echo "			<input type=\"submit\" class='btn' value=\"Add\">\n";
	}
	unset($sql, $result);
	echo "			<br>\n";
	echo "			Add the extensions to the ring group.\n";
	echo "			<br />\n";
	echo "		</td>";
	echo "	</tr>";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Timeout:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ring_group_timeout_sec' maxlength='255' value='$ring_group_timeout_sec'>\n";
	echo "<br />\n";
	echo "Enter the timeout in seconds.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Destination:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	//switch_select_destination(select_type, select_label, select_name, select_value, select_style, action);
	switch_select_destination("dialplan", "", "ring_group_timeout_action", $ring_group_timeout_action, "", "");
	echo "	<br />\n";
	echo "	Select the timeout destination.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	CID Prefix:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "  <input class='formfld' type='text' name='ring_group_cid_name_prefix' maxlength='255' value='$ring_group_cid_name_prefix'>\n";
	echo "<br />\n";
	echo "Set a prefix on the caller ID name. \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	 Ring Back:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	$select_options = "";
	if ($ring_group_ringback == "\${us-ring}" || $ring_group_ringback == "us-ring") { 
		$select_options .= "		<option value='\${us-ring}' selected='selected'>us-ring</option>\n";
	}
	else {
		$select_options .= "		<option value='\${us-ring}'>us-ring</option>\n";
	}
	if ($ring_group_ringback == "\${fr-ring}" || $ring_group_ringback == "fr-ring") {
		$select_options .= "		<option value='\${fr-ring}' selected='selected'>fr-ring</option>\n";
	}
	else {
		$select_options .= "		<option value='\${fr-ring}'>fr-ring</option>\n";
	}
	if ($ring_group_ringback == "\${uk-ring}" || $ring_group_ringback == "uk-ring") { 
		$select_options .= "		<option value='\${uk-ring}' selected='selected'>uk-ring</option>\n";
	}
	else {
		$select_options .= "		<option value='\${uk-ring}'>uk-ring</option>\n";
	}
	if ($ring_group_ringback == "\${rs-ring}" || $ring_group_ringback == "rs-ring") { 
		$select_options .= "		<option value='\${rs-ring}' selected='selected'>rs-ring</option>\n";
	}
	else {
		$select_options .= "		<option value='\${rs-ring}'>rs-ring</option>\n";
	}
	require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
	$moh = new switch_music_on_hold;
	$moh->select_name = "ring_group_ringback";
	$moh->select_value = $ring_group_ringback;
	$moh->select_options = $select_options;
	echo $moh->select();

	echo "<br />\n";
	echo "Defines what the caller will hear while the destination is being called.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='ring_group_enabled'>\n";
	echo "	<option value=''></option>\n";
	if ($ring_group_enabled == "true") { 
		echo "	<option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "	<option value='true'>true</option>\n";
	}
	if ($ring_group_enabled == "false") { 
		echo "	<option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "	<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Select enable or disable the ring group.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='ring_group_description' maxlength='255' value=\"$ring_group_description\">\n";
	echo "<br />\n";
	echo "Enter the description.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='dialplan_uuid' value='$dialplan_uuid'>\n";
		echo "				<input type='hidden' name='ring_group_uuid' value='$ring_group_uuid'>\n";
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