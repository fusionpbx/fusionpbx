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
include "root.php";
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists("user_add") ||
	permission_exists("user_edit") || 
	permission_exists("user_delete") ||
	if_group("superadmin")) {
	//access allowed
}
else {
	echo "access denied";
	return;
}

//get data from the db
	if (strlen($_REQUEST["id"])> 0) {
		$user_uuid = $_REQUEST["id"];
	}
	else {
		if (strlen($_SESSION["username"]) > 0) {
			$username = $_SESSION["username"];
		}
	}

//get the username from v_users
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and user_uuid = '$user_uuid' ";
	$sql .= "and user_enabled = 'true' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$username = $row["username"];
		break; //limit to 1 row
	}
	unset ($prep_statement);

//required to be a superadmin to update an account that is a member of the superadmin group
	$superadmin_list = superadmin_list($db);
	if (if_superadmin($superadmin_list, $_SESSION['user_uuid'])) {
		if (!if_group("superadmin")) { 
			echo "access denied";
			return;
		}
	}

//delete the group from the user
	if ($_GET["a"] == "delete" && permission_exists("user_delete")) {
		//set the variables
			$group_name = check_str($_GET["group_name"]);
		//delete the group from the users
			$sql = "delete from v_group_users ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and group_name = '$group_name' ";
			$sql .= "and user_uuid = '$user_uuid' ";
			$db->exec(check_sql($sql));
		//redirect the user
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=usersupdate.php?id=$user_uuid\">\n";
			echo "<div align='center'>Update Complete</div>";
			require_once "includes/footer.php";
			return;
	}

//get the user settings
	$sql = "select * from v_user_settings ";
	$sql .= "where user_uuid = '".$_SESSION["user_uuid"]."' ";
	$sql .= "and user_setting_enabled = 'true' ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach($result as $row) {
			$name = $row['user_setting_name'];
			$category = $row['user_setting_category'];
			$subcategory = $row['user_setting_subcategory'];
			if (strlen($subcategory) == 0) {
				//$$category[$name] = $row['domain_setting_value'];
				$user_settings[$category][$name] = $row['user_setting_value'];
			}
			else {
				$user_settings[$category][$subcategory][$name] = $row['user_setting_value'];
			}
		}
	}

if (count($_POST)>0 && $_POST["persistform"] != "1") {
	$user_uuid = $_REQUEST["id"];
	$password = check_str($_POST["password"]);
	$confirm_password = check_str($_POST["confirm_password"]);
	$user_status = check_str($_POST["user_status"]);
	//$user_template_name = check_str($_POST["user_template_name"]);
	$user_time_zone = check_str($_POST["user_time_zone"]);
	$contact_uuid = check_str($_POST["contact_uuid"]);
	$group_member = check_str($_POST["group_member"]);
	$user_enabled = check_str($_POST["user_enabled"]);

	//if (strlen($password) == 0) { $msg_error .= "Password cannot be blank.<br>\n"; }
	//if (strlen($username) == 0) { $msg_error .= "Please provide the username.<br>\n"; }
	if ($password != $confirm_password) { $msg_error .= "Passwords did not match.<br>\n"; }
	//if (strlen($contact_uuid) == 0) { $msg_error .= "Please provide an email.<br>\n"; }
	//if (strlen($user_time_zone) == 0) { $msg_error .= "Please provide an time zone.<br>\n"; }
	if (strlen($user_enabled) == 0) { $msg_error .= "Please provide an enable or disable the user.<br>\n"; }

	if ($msg_error) {
		require_once "includes/header.php";
		echo "<div align='center'>";
		echo "<table><tr><td>";
		echo $msg_error;
		echo "</td></tr></table>";
		echo "<br />\n";
		require_once "includes/persistform.php";
		echo persistform($_POST);
		echo "</div>";
		require_once "includes/footer.php";
		return;
	}

	//get the number of rows in v_user_settings 
		$sql = "select count(*) as num_rows from v_user_settings ";
		$sql .= "where user_setting_category = 'domain' ";
		$sql .= "and user_setting_subcategory = 'time_zone' ";
		$sql .= "and user_uuid = '".$user_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] == 0) {
				$user_setting_uuid = uuid();
				$sql = "insert into v_user_settings ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "user_setting_uuid, ";
				$sql .= "user_setting_category, ";
				$sql .= "user_setting_subcategory, ";
				$sql .= "user_setting_name, ";
				$sql .= "user_setting_value, ";
				$sql .= "user_setting_enabled, ";
				$sql .= "user_uuid ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$_SESSION["domain_uuid"]."', ";
				$sql .= "'".$user_setting_uuid."', ";
				$sql .= "'domain', ";
				$sql .= "'time_zone', ";
				$sql .= "'name', ";
				$sql .= "'".$user_time_zone."', ";
				$sql .= "'true', ";
				$sql .= "'".$user_uuid."' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
			}
			else {
				if (strlen($user_time_zone) == 0) {
					$sql = "delete from v_user_settings ";
					$sql .= "where user_setting_category = 'domain' ";
					$sql .= "and user_setting_subcategory = 'time_zone' ";
					$sql .= "and user_uuid = '".$user_uuid."' ";
					$db->exec(check_sql($sql));
					unset($sql);
				}
				else {
					$sql  = "update v_user_settings set ";
					$sql .= "user_setting_value = '".$user_time_zone."', ";
					$sql .= "user_setting_enabled = 'true' ";
					$sql .= "where user_setting_category = 'domain' ";
					$sql .= "and user_setting_subcategory = 'time_zone' ";
					$sql .= "and user_uuid = '".$user_uuid."' ";
					$db->exec(check_sql($sql));
				}
			}
		}

	//assign the user to the group
		if (strlen($_REQUEST["group_name"]) > 0) {
			$sql_insert = "insert into v_group_users ";
			$sql_insert .= "(";
			$sql_insert .= "group_user_uuid, ";
			$sql_insert .= "domain_uuid, ";
			$sql_insert .= "group_name, ";
			$sql_insert .= "user_uuid ";
			$sql_insert .= ")";
			$sql_insert .= "values ";
			$sql_insert .= "(";
			$sql_insert .= "'".uuid()."', ";
			$sql_insert .= "'$domain_uuid', ";
			$sql_insert .= "'".$_REQUEST["group_name"]."', ";
			$sql_insert .= "'$user_uuid' ";
			$sql_insert .= ")";
			if ($_REQUEST["group_name"] == "superadmin") {
				//only a user in the superadmin group can add other users to that group
				if (if_group("superadmin")) {
					$db->exec($sql_insert);
				}
			}
			else {
				$db->exec($sql_insert);
			}
		}

	//if the template has not been assigned by the superadmin
		//if (strlen($_SESSION['domain']['template']['name']) == 0) {
			//set the session theme for the active user
		//	if ($_SESSION["username"] == $username) {
		//		$_SESSION['domain']['template']['name'] = $user_template_name;
		//	}
		//}

	//sql update
		$sql  = "update v_users set ";
		if (if_group("admin") && strlen($_POST["username"])> 0) {
			$sql .= "username = '$username', ";
		}
		if (strlen($password) > 0 && $confirm_password == $password) {
			//salt used with the password to create a one way hash
				$salt = generate_password('20', '4');
			//set the password
				$sql .= "password = '".md5($salt.$password)."', ";
				$sql .= "salt = '".$salt."', ";
		}
		$sql .= "user_status = '$user_status', ";
		$sql .= "user_enabled = '$user_enabled', ";
		if (strlen($contact_uuid) == 0) {
			$sql .= "contact_uuid = null ";
		}
		else {
			$sql .= "contact_uuid = '$contact_uuid' ";
		}
		if (strlen($user_uuid)> 0) {
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and user_uuid = '$user_uuid' ";
		}
		else {
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and username = '$username' ";
		}
		$db->exec(check_sql($sql));

	//update the user_status
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		$switch_cmd .= "callcenter_config agent set status ".$username."@".$_SESSION['domain_name']." '".$user_status."'";
		$switch_result = event_socket_request($fp, 'api '.$switch_cmd);

	//update the user state
		$cmd = "api callcenter_config agent set state ".$username."@".$_SESSION['domain_name']." Waiting";
		$response = event_socket_request($fp, $cmd);

	//clear the template so it will rebuild in case the template was changed
		//$_SESSION["template_content"] = '';

	//redirect the browser
		require_once "includes/header.php";
		if (if_group("admin")) {
			echo "<meta http-equiv=\"refresh\" content=\"2;url=usersupdate.php?id=$user_uuid\">\n";
		}
		else {
			echo "<meta http-equiv=\"refresh\" content=\"2;url=usersupdate.php?id=$user_uuid\">\n";
		}
		echo "<div align='center'>Update Complete</div>";
		require_once "includes/footer.php";
		return;
}
else {
	$sql = "select * from v_users ";
	//allow admin access
	if (if_group("admin") || if_group("superadmin")) {
		if (strlen($user_uuid)> 0) {
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and user_uuid = '$user_uuid' ";
		}
		else {
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and username = '$username' ";
		}
	}
	else {
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and username = '$username' ";
	}
	$sql .= "and user_enabled = 'true' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$user_uuid = $row["user_uuid"];
		if (if_group("admin")) {
			$username = $row["username"];
		}
		$password = $row["password"];
		$contact_uuid = $row["contact_uuid"];
		$user_status = $row["user_status"];
		break; //limit to 1 row
	}

	//get the groups the user is a member of
	//group_members function defined in config.php
	$group_members = group_members($db, $user_uuid);
}

//include the header
	require_once "includes/header.php";

//show the content
	$table_width ='width="100%"';
	echo "<form method='post' action=''>";
	echo "<br />\n";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr>\n";
	echo "<td>\n";

	echo "<table $table_width cellpadding='3' cellspacing='0' border='0'>";
	echo "<td align='left' width='90%' nowrap><b>User Manager</b></td>\n";
	echo "<td nowrap='nowrap'>\n";
	echo "	<input type='submit' name='submit' class='btn' value='Save'>";
	echo "	<input type='button' class='btn' onclick=\"window.location='index.php'\" value='Back'>";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "	Edit user information and group membership. \n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<br />\n";

	echo "<table $table_width cellpadding='6' cellspacing='0' border='0'>";
	echo "<tr>\n";
	echo "	<th class='th' colspan='2' align='left'>User Info</th>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td width='30%' class='vncellreq'>Username:</td>";
	echo "		<td width='70%' class='vtable'>$username</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell'>Password:</td>";
	echo "		<td class='vtable'><input type='password' autocomplete='off' class='formfld' name='password' value=\"\"></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Confirm Password:</td>";
	echo "		<td class='vtable'><input type='password' autocomplete='off' class='formfld' name='confirm_password' value=\"\"></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>Groups:</td>";
	echo "		<td class='vtable'>";

	echo "<table width='52%'>\n";
	$sql = "SELECT * FROM v_group_users ";
	$sql .= "where domain_uuid=:domain_uuid ";
	$sql .= "and user_uuid=:user_uuid ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->bindParam(':domain_uuid', $domain_uuid);
	$prep_statement->bindParam(':user_uuid', $user_uuid);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	foreach($result as $field) {
		if (strlen($field['group_name']) > 0) {
			echo "<tr>\n";
			echo "	<td class='vtable'>".$field['group_name']."</td>\n";
			echo "	<td>\n";
			if (permission_exists('group_member_delete') || if_group("superadmin")) {
				echo "		<a href='usersupdate.php?id=".$user_uuid."&domain_uuid=".$domain_uuid."&group_name=".$field['group_name']."&a=delete' alt='delete' onclick=\"return confirm('Do you really want to delete this?')\">$v_link_label_delete</a>\n";
			}
			echo "	</td>\n";
			echo "</tr>\n";
		}
	}
	echo "</table>\n";

	echo "<br />\n";
	$sql = "SELECT * FROM v_groups ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	echo "<select name=\"group_name\" class='frm'>\n";
	echo "<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $field) {
		if ($field['group_name'] == "superadmin") {
			//only show the superadmin group to other users in the superadmin group
			if (if_group("superadmin")) {
				echo "<option value='".$field['group_name']."'>".$field['group_name']."</option>\n";
			}
		}
		else {
			echo "<option value='".$field['group_name']."'>".$field['group_name']."</option>\n";
		}
	}
	echo "</select>";
	echo "<input type=\"submit\" class='btn' value=\"Add\">\n";
	unset($sql, $result);
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";

	echo "<br>";
	echo "<br>";

	echo "<table $table_width cellpadding='6' cellspacing='0'>";
	echo "	<tr>\n";
	echo "	<th class='th' colspan='2' align='left'>Additional Info</th>\n";
	echo "	</tr>\n";

	echo "	<tr>";
	echo "		<td width='30%' class='vncell'>Contact:</td>";
	echo "		<td width='70%' class='vtable'>\n";
	$sql = " select contact_uuid, contact_organization, contact_name_given, contact_name_family from v_contacts ";
	$sql .= " where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= " order by contact_organization asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);
	echo "<select name=\"contact_uuid\" id=\"contact_uuid\" class=\"formfld\">\n";
	echo "<option value=\"\"></option>\n";
	foreach($result as $row) {
			$contact_name = '';
			if (strlen($row['contact_organization']) > 0) {
					$contact_name = $row['contact_organization'];
			}
			if (strlen($row['contact_name_family']) > 0) {
					if (strlen($contact_name) > 0) { $contact_name .= ", "; }
					$contact_name .= $row['contact_name_family'];
			}
			if (strlen($row['contact_name_given']) > 0) {
					if (strlen($contact_name) > 0) { $contact_name .= ", "; }
					$contact_name .= $row['contact_name_given'];
			}
			if ($row['contact_uuid'] == $contact_uuid) {
					echo "<option value=\"".$row['contact_uuid']."\" selected=\"selected\">".$contact_name."</option>\n";
			}
			else {
					echo "<option value=\"".$row['contact_uuid']."\">".$contact_name."</option>\n";
			}
	}
	unset($sql, $result, $row_count);
	echo "</select>\n";
	echo "<br />\n";
	echo "Assign a contact to this user account.\n";
	if (strlen($contact_uuid) > 0) {
		echo "			<a href=\"/app/contacts/contacts_edit.php?id=$contact_uuid\">View</a>\n";
	}
	echo "		</td>";
	echo "	</tr>";

	if ($_SESSION['user_status_display'] == "false") {
		//hide the user_status when it is set to false
	}
	else {
		echo "	<tr>\n";
		echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
		echo "		Status:\n";
		echo "	</td>\n";
		echo "	<td class=\"vtable\">\n";
		$cmd = "'".PROJECT_PATH."/app/calls_active/v_calls_exec.php?cmd=callcenter_config+agent+set+status+".$_SESSION['username']."@".$_SESSION['domain_name']."+'+this.value";
		echo "		<select id='user_status' name='user_status' class='formfld' style='' onchange=\"send_cmd($cmd);\">\n";
		echo "		<option value=''></option>\n";
		if ($user_status == "Available") {
			echo "		<option value='Available' selected='selected'>Available</option>\n";
		}
		else {
			echo "		<option value='Available'>Available</option>\n";
		}
		if ($user_status == "Available (On Demand)") {
			echo "		<option value='Available (On Demand)' selected='selected'>Available (On Demand)</option>\n";
		}
		else {
			echo "		<option value='Available (On Demand)'>Available (On Demand)</option>\n";
		}
		if ($user_status == "Logged Out") {
			echo "		<option value='Logged Out' selected='selected'>Logged Out</option>\n";
		}
		else {
			echo "		<option value='Logged Out'>Logged Out</option>\n";
		}
		if ($user_status == "On Break") {
			echo "		<option value='On Break' selected='selected'>On Break</option>\n";
		}
		else {
			echo "		<option value='On Break'>On Break</option>\n";
		}
		if ($user_status == "Do Not Disturb") {
			echo "		<option value='Do Not Disturb' selected='selected'>Do Not Disturb</option>\n";
		}
		else {
			echo "		<option value='Do Not Disturb'>Do Not Disturb</option>\n";
		}
		echo "		</select>\n";
		echo "		<br />\n";
		echo "		Select a the user status.<br />\n";
		echo "	</td>\n";
		echo "	</tr>\n";
	}

	//if the template has not been assigned by the superadmin
		/*
		if (strlen($_SESSION['domain']['template']['name']) == 0) {
			echo "	<tr>\n";
			echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
			echo "		Template: \n";
			echo "	</td>\n";
			echo "	<td class=\"vtable\">\n";
			echo "		<select id='user_template_name' name='user_template_name' class='formfld' style=''>\n";
			echo "		<option value=''></option>\n";
			$theme_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes';
			if ($handle = opendir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/themes')) {
				while (false !== ($dir_name = readdir($handle))) {
					if ($dir_name != "." && $dir_name != ".." && $dir_name != ".svn" && is_dir($theme_dir.'/'.$dir_name)) {
						$dir_label = str_replace('_', ' ', $dir_name);
						$dir_label = str_replace('-', ' ', $dir_label);
						if ($dir_name == $user_settings['domain']['template']['name']) {
							echo "		<option value='$dir_name' selected='selected'>$dir_label</option>\n";
						}
						else {
							echo "		<option value='$dir_name'>$dir_label</option>\n";
						}
					}
				}
				closedir($handle);
			}
			echo "	</select>\n";
			echo "	<br />\n";
			echo "	Select a template to set as the default and then press save.<br />\n";
			echo "	</td>\n";
			echo "	</tr>\n";
		}
		*/

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" style='text-align: left;'>\n";
	echo "		Time Zone: \n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "		<select id='user_time_zone' name='user_time_zone' class='formfld' style=''>\n";
	echo "		<option value=''></option>\n";
	//$list = DateTimeZone::listAbbreviations();
    $time_zone_identifiers = DateTimeZone::listIdentifiers();
	$previous_category = '';
	$x = 0;
	foreach ($time_zone_identifiers as $key => $row) {
		$time_zone = explode("/", $row);
		$category = $time_zone[0];
		if ($category != $previous_category) {
			if ($x > 0) {
				echo "		</optgroup>\n";
			}
			echo "		<optgroup label='".$category."'>\n";
		}
		if ($row == $user_settings['domain']['time_zone']['name']) {
			echo "			<option value='".$row."' selected='selected'>".$row."</option>\n";
		}
		else {
			echo "			<option value='".$row."'>".$row."</option>\n";
		}
		$previous_category = $category;
		$x++;
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		Select the default time zone.<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='user_enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($user_enabled == "true" || $user_enabled == "") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($user_enabled == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Enable/disable this user.\n";
	echo "</td>\n";
	echo "</tr>\n";	

	echo "	</table>";
	echo "<br>";

	echo "<div class='' style='padding:10px;'>\n";
	echo "<table $table_width>";
	echo "	<tr>";
	echo "		<td colspan='2' align='right'>";
	echo "			<input type='hidden' name='id' value=\"$user_uuid\">";
	echo "			<input type='hidden' name='username' value=\"$username\">";
	echo "			<input type='submit' name='submit' class='btn' value='Save'>";
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";
	echo "</form>";

//include the footer
	require_once "includes/footer.php";

?>