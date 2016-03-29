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
	Portions created by the Initial Developer are Copyright (C) 2008-2013
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get data from the db
	if (strlen($_REQUEST["id"]) > 0) {
		$user_uuid = $_REQUEST["id"];
	}

//required to be a superadmin to update an account that is a member of the superadmin group
	$superadmins = superadmin_list($db);
	if (if_superadmin($superadmins, $user_uuid)) {
		if (!if_group("superadmin")) {
			echo "access denied";
			exit;
		}
	}

//delete the group from the user
	if ($_GET["a"] == "delete" && permission_exists("user_delete")) {
		//set the variables
			$group_uuid = check_str($_GET["group_uuid"]);
		//delete the group from the users
			$sql = "delete from v_group_users where 1 = 1 ";
			$sql .= "and group_uuid = '".$group_uuid."' ";
			$sql .= "and user_uuid = '".$user_uuid."' ";
			$db->exec(check_sql($sql));
		//redirect the user
			$_SESSION["message"] = $text['message-update'];
			header("Location: usersupdate.php?id=".$user_uuid);
			return;
	}

//get the user settings
	$sql = "select * from v_user_settings ";
	$sql .= "where user_uuid = '".$user_uuid."' ";
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

if (count($_POST) > 0 && $_POST["persistform"] != "1") {

	//get the HTTP values and set as variables
		$user_uuid = $_REQUEST["id"];
		$domain_uuid = check_str($_POST["domain_uuid"]);
		$username_old = check_str($_POST["username_old"]);
		$username = check_str($_POST["username"]);
		$password = check_str($_POST["password"]);
		$confirm_password = check_str($_POST["confirm_password"]);
		$user_status = check_str($_POST["user_status"]);
		$user_language = check_str($_POST["user_language"]);
		$user_time_zone = check_str($_POST["user_time_zone"]);
		$contact_uuid = check_str($_POST["contact_uuid"]);
		$group_member = check_str($_POST["group_member"]);
		$user_enabled = check_str($_POST["user_enabled"]);
		$api_key = check_str($_POST["api_key"]);

	//check required values
		if ($username != $username_old) {
			$sql = "select count(*) as num_rows from v_users where domain_uuid = '".$domain_uuid."' and username = '".$username."'";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
				if (0 < $row['num_rows']) {
					$msg_error = $text['message-username_exists'];
				}
			}
			unset($sql);
		}

		if ($password != $confirm_password) { $msg_error = $text['message-password_mismatch']; }

		if ($msg_error) {
			$_SESSION["message"] = $msg_error;
			$_SESSION["message_mood"] = 'negative';
			header("Location: usersupdate.php?id=".$user_uuid);
			exit;
		}

	//check to see if user language is set
		$sql = "select count(*) as num_rows from v_user_settings ";
		$sql .= "where user_setting_category = 'domain' ";
		$sql .= "and user_setting_subcategory = 'language' ";
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
				$sql .= "'".$domain_uuid."', ";
				$sql .= "'".$user_setting_uuid."', ";
				$sql .= "'domain', ";
				$sql .= "'language', ";
				$sql .= "'code', ";
				$sql .= "'".$user_language."', ";
				$sql .= "'true', ";
				$sql .= "'".$user_uuid."' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
			}
			else {
				if (strlen($user_language) == 0) {
					$sql = "delete from v_user_settings ";
					$sql .= "where user_setting_category = 'domain' ";
					$sql .= "and user_setting_subcategory = 'language' ";
					$sql .= "and user_uuid = '".$user_uuid."' ";
					$db->exec(check_sql($sql));
					unset($sql);
				}
				else {
					$sql  = "update v_user_settings set ";
					$sql .= "user_setting_value = '".$user_language."', ";
					$sql .= "user_setting_enabled = 'true' ";
					$sql .= "where user_setting_category = 'domain' ";
					$sql .= "and user_setting_subcategory = 'language' ";
					$sql .= "and user_uuid = '".$user_uuid."' ";
					$db->exec(check_sql($sql));
				}
			}
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
				$sql .= "'".$domain_uuid."', ";
				$sql .= "'".$user_setting_uuid."', ";
				$sql .= "'domain', ";
				$sql .= "'time_zone', ";
				$sql .= "'name', ";
				$sql .= "'".$user_time_zone."', ";
				$sql .= "'true', ";
				$sql .= "'".$user_uuid."' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);
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
					unset($sql);
				}
			}
		}

	//assign the user to the group
		if (strlen($_REQUEST["group_uuid_name"]) > 0) {
			$group_data = explode('|', $_REQUEST["group_uuid_name"]);
			$group_uuid = $group_data[0];
			$group_name = $group_data[1];
			$sql_insert = "insert into v_group_users ";
			$sql_insert .= "(";
			$sql_insert .= "group_user_uuid, ";
			$sql_insert .= "domain_uuid, ";
			$sql_insert .= "group_name, ";
			$sql_insert .= "group_uuid, ";
			$sql_insert .= "user_uuid ";
			$sql_insert .= ") ";
			$sql_insert .= "values ";
			$sql_insert .= "( ";
			$sql_insert .= "'".uuid()."', ";
			$sql_insert .= "'".$domain_uuid."', ";
			$sql_insert .= "'".$group_name."', ";
			$sql_insert .= "'".$group_uuid."', ";
			$sql_insert .= "'".$user_uuid."' ";
			$sql_insert .= ")";
			//only a superadmin can add other superadmins or admins, admins can only add other admins
			switch ($group_name) {
				case "superadmin" :
					if (!if_group("superadmin")) { break; }
				case "admin" :
					if (!if_group("superadmin") && !if_group("admin")) { break; }
				default :
					$db->exec($sql_insert);
			}
		}

	//change domain_uuid in group users and user settings tables, and unassign any foreign domain groups
		if (permission_exists('user_domain')) {
			$sql = "update v_group_users set ";
			$sql .= "domain_uuid = '".$domain_uuid."' ";
			$sql .= "where user_uuid = '".$user_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);

			$sql = "update v_user_settings set ";
			$sql .= "domain_uuid = '".$domain_uuid."' ";
			$sql .= "where user_uuid = '".$user_uuid."' ";
			$db->exec(check_sql($sql));
			unset($sql);

			$sql = "delete from v_group_users where ";
			$sql .= "domain_uuid = '".$domain_uuid."' ";
			$sql .= "and user_uuid = '".$user_uuid."' ";
			$sql .= "and group_uuid not in (";
			$sql .= "	select group_uuid from v_groups where domain_uuid = '".$domain_uuid."' or domain_uuid is null ";
			$sql .= ") ";
			$db->exec(check_sql($sql));
			unset($sql);
		}

	//sql update
		$sql  = "update v_users set ";
		if (permission_exists('user_domain')) {
			$sql .= "domain_uuid = '".$domain_uuid."', ";
		}
		if (strlen($username) > 0 && $username != $username_old) {
			$sql .= "username = '".$username."', ";
		}
		if (strlen($password) > 0 && $confirm_password == $password) {
			//salt used with the password to create a one way hash
				$salt = uuid();
			//set the password
				$sql .= "password = '".md5($salt.$password)."', ";
				$sql .= "salt = '".$salt."', ";
		}
		if (strlen($api_key) > 0) {
			$sql .= "api_key = '".$api_key."', ";
		}
		else {
			$sql .= "api_key = null, ";
		}
		$sql .= "user_status = '".$user_status."', ";
		$sql .= "user_enabled = '".$user_enabled."', ";
		if (strlen($contact_uuid) == 0) {
			$sql .= "contact_uuid = null ";
		}
		else {
			$sql .= "contact_uuid = '".$contact_uuid."' ";
		}
		$sql .= "where ";
		if (!permission_exists('user_domain')) {
			$sql .= "domain_uuid = '".$domain_uuid."' and ";
		}
		$sql .= "user_uuid = '".$user_uuid."' ";
		$db->exec(check_sql($sql));


	// if call center installed
	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_center/app_config.php")) {

		// update agent and tiers tables
			$sql  = "update v_call_center_agents set agent_name = '".$username."' where domain_uuid = '".$domain_uuid."' and agent_name = '".$username_old."' ";
			$db->exec(check_sql($sql));
			unset($sql);

			$sql  = "update v_call_center_tiers set agent_name = '".$username."' where domain_uuid = '".$domain_uuid."' and agent_name = '".$username_old."' ";
			$db->exec(check_sql($sql));
			unset($sql);

		//syncrhonize the configuration
			save_call_center_xml();

		//update the user_status
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			$switch_cmd .= "callcenter_config agent set status ".$username."@".$_SESSION['domains'][$domain_uuid]['domain_name']." '".$user_status."'";
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);

		//update the user state
			$cmd = "api callcenter_config agent set state ".$username."@".$_SESSION['domains'][$domain_uuid]['domain_name']." Waiting";
			$response = event_socket_request($fp, $cmd);

	}

	//redirect the browser
		$_SESSION["message"] = $text['message-update'];
		if ($_REQUEST['action'] == $text['button-add']) {
			header("Location: usersupdate.php?id=".$user_uuid);
		}
		else {
			header("Location: index.php");
		}
		return;

}


//pre-populate the form
	$sql = "select * from v_users ";
	$sql .= "where user_uuid = '".$user_uuid."' ";
 	if (!permission_exists('user_all')) {
 		$sql .= "and domain_uuid = '".$domain_uuid."' ";
 	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$domain_uuid = $row["domain_uuid"];
		$user_uuid = $row["user_uuid"];
		$username = $row["username"];
		$password = $row["password"];
		$api_key = $row["api_key"];
		$user_enabled = $row["user_enabled"];
		$contact_uuid = $row["contact_uuid"];
		$user_status = $row["user_status"];
	}

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-user_edit'];

//show the content
	$table_width ='width="100%"';

	echo "<script>";
	echo "	function compare_passwords() {";
	echo "		if (document.getElementById('password') === document.activeElement || document.getElementById('confirmpassword') === document.activeElement) {";
	echo "			if (document.getElementById('password').value != '' || document.getElementById('confirmpassword').value != '') {";
	echo "				if (document.getElementById('password').value != document.getElementById('confirmpassword').value) {";
	echo "					$('#password').removeClass('formfld_highlight_good');";
	echo "					$('#confirmpassword').removeClass('formfld_highlight_good');";
	echo "					$('#password').addClass('formfld_highlight_bad');";
	echo "					$('#confirmpassword').addClass('formfld_highlight_bad');";
	echo "				}";
	echo "				else {";
	echo "					$('#password').removeClass('formfld_highlight_bad');";
	echo "					$('#confirmpassword').removeClass('formfld_highlight_bad');";
	echo "					$('#password').addClass('formfld_highlight_good');";
	echo "					$('#confirmpassword').addClass('formfld_highlight_good');";
	echo "				}";
	echo "			}";
	echo "		}";
	echo "		else {";
	echo "			if (document.getElementById('password').value == document.getElementById('confirmpassword').value) {";
	echo "				$('#password').removeClass('formfld_highlight_bad');";
	echo "				$('#confirmpassword').removeClass('formfld_highlight_bad');";
	echo "				$('#password').removeClass('formfld_highlight_good');";
	echo "				$('#confirmpassword').removeClass('formfld_highlight_good');";
	echo "			}";
	echo "		}";
	echo "	}";
	echo "</script>";

	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input type='hidden' name='action' id='action' value=''>\n";
	echo "<table $table_width cellpadding='0' cellspacing='0' border='0'>";
	echo "<td align='left' width='90%' nowrap><b>".$text['header-user_edit']."</b></td>\n";
	echo "<td align='right' nowrap>\n";
	echo "	<input type='button' class='btn' onclick=\"window.location='index.php'\" value='".$text['button-back']."'>";
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick=\"document.getElementById('action').value = '".$text['button-save']."'; submit_form();\">";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "	".$text['description-user_edit']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<br />\n";

	echo "<table $table_width cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>\n";
	echo "	<th class='th' colspan='2' align='left'>".$text['label-user_info']."</th>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td width='30%' class='vncellreq' valign='top'>".$text['label-username']."</td>";
	echo "		<td width='70%' class='vtable'>";
	if (if_group("admin") || if_group("superadmin")) {
		echo "		<input type='txt' class='formfld' name='username' value='".$username."' required='required'>";
	}
	else {
		echo "		".$username;
	}
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-password']."</td>";
	echo "		<td class='vtable'><input style='display:none;' type='password' name='autocomplete'><input type='password' autocomplete='off' class='formfld' name='password' id='password' value='' onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-confirm_password']."</td>";
	echo "		<td class='vtable'><input type='password' autocomplete='off' class='formfld' name='confirm_password' id='confirmpassword' value='' onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();'></td>";
	echo "	</tr>";

	if (permission_exists('user_domain')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-domain']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "    <select class='formfld' name='domain_uuid'>\n";
		foreach ($_SESSION['domains'] as $row) {
			echo "	<option value='".$row['domain_uuid']."' ".(($row['domain_uuid'] == $domain_uuid) ? "selected='selected'" : null).">".$row['domain_name']."</option>\n";
		}
		echo "    </select>\n";
		echo "<br />\n";
		echo $text['description-domain_name']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else {
		echo "<input type='hidden' name='domain_uuid' value='".$domain_uuid."'>";
	}

	echo "	<tr>";
	echo "		<td class='vncellreq' valign='top'>".$text['label-groups']."</td>";
	echo "		<td class='vtable'>";

	$sql = "select ";
	$sql .= "	gu.*, g.domain_uuid as group_domain_uuid ";
	$sql .= "from ";
	$sql .= "	v_group_users as gu, ";
	$sql .= "	v_groups as g ";
	$sql .= "where ";
	$sql .= "	gu.group_uuid = g.group_uuid ";
	$sql .= "	and (";
	$sql .= "		g.domain_uuid = :domain_uuid ";
	$sql .= "		or g.domain_uuid is null ";
	$sql .= "	) ";
	$sql .= "	and gu.domain_uuid = :domain_uuid ";
	$sql .= "	and gu.user_uuid = :user_uuid ";
	$sql .= "order by ";
	$sql .= "	g.domain_uuid desc, ";
	$sql .= "	g.group_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->bindParam(':domain_uuid', $domain_uuid);
	$prep_statement->bindParam(':user_uuid', $user_uuid);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	if ($result_count > 0) {
		echo "<table cellpadding='0' cellspacing='0' border='0'>\n";
		foreach($result as $field) {
			if (strlen($field['group_name']) > 0) {
				echo "<tr>\n";
				echo "	<td class='vtable' style='white-space: nowrap; padding-right: 30px;' nowrap='nowrap'>";
				echo $field['group_name'].(($field['group_domain_uuid'] != '') ? "@".$_SESSION['domains'][$field['group_domain_uuid']]['domain_name'] : null);
				echo "	</td>\n";
				if ($result_count > 1) {
					if (permission_exists('group_member_delete') || if_group("superadmin")) {
						echo "	<td class='list_control_icons' style='width: 25px;'>\n";
						echo "		<a href='usersupdate.php?id=".$user_uuid."&domain_uuid=".$domain_uuid."&group_uuid=".$field['group_uuid']."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>\n";
						echo "	</td>\n";
					}
				}
				echo "</tr>\n";
				$assigned_groups[] = $field['group_uuid'];
			}
		}
		echo "</table>\n";
	}
	unset($sql, $prep_statement, $result, $result_count);

	$sql = "select * from v_groups ";
	$sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
	if (sizeof($assigned_groups) > 0) {
		$sql .= "and group_uuid not in ('".implode("','",$assigned_groups)."') ";
	}
	$sql .= "order by domain_uuid desc, group_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	if ($result_count > 0) {
		echo "<br />\n";
		echo "<select name='group_uuid_name' class='formfld' style='width: auto; margin-right: 3px;'>\n";
		echo "	<option value=''></option>\n";
		foreach($result as $field) {
			if ($field['group_name'] == "superadmin" && !if_group("superadmin")) { continue; }	//only show the superadmin group to other superadmins
			if ($field['group_name'] == "admin" && (!if_group("superadmin") && !if_group("admin") )) { continue; }	//only show the admin group to other admins
			if (!in_array($field["group_uuid"], $assigned_groups)) {
				echo "	<option value='".$field['group_uuid']."|".$field['group_name']."'>".$field['group_name'].(($field['domain_uuid'] != '') ? "@".$_SESSION['domains'][$field['domain_uuid']]['domain_name'] : null)."</option>\n";
			}
		}
		echo "</select>";
		echo "<input type='button' class='btn' value=\"".$text['button-add']."\" onclick=\"document.getElementById('action').value = '".$text['button-add']."'; submit_form();\">\n";
	}
	unset($sql, $prep_statement, $result);

	echo "		</td>";
	echo "	</tr>";
	echo "</table>";

	echo "<br>";
	echo "<br>";

	echo "<table $table_width cellpadding='0' cellspacing='0'>";
	echo "	<tr>\n";
	echo "	<th class='th' colspan='2' align='left'>".$text['label-additional_info']."</th>\n";
	echo "	</tr>\n";

	echo "	<tr>";
	echo "		<td width='30%' class='vncell' valign='top'>".$text['label-contact']."</td>";
	echo "		<td width='70%' class='vtable'>\n";
	$sql = " select contact_uuid, contact_organization, contact_name_given, contact_name_family from v_contacts ";
	$sql .= " where domain_uuid = '".$domain_uuid."' ";
	$sql .= " order by contact_organization desc, contact_name_family asc, contact_name_given asc ";
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
	echo $text['description-contact']."\n";
	if (strlen($contact_uuid) > 0) {
		echo "			<a href=\"".PROJECT_PATH."/app/contacts/contact_edit.php?id=$contact_uuid\">".$text['description-contact_view']."</a>\n";
	}
	echo "		</td>";
	echo "	</tr>";

	if ($_SESSION['user_status_display'] == "false") {
		//hide the user_status when it is set to false
	}
	else {
		echo "	<tr>\n";
		echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
		echo "		".$text['label-status']."\n";
		echo "	</td>\n";
		echo "	<td class=\"vtable\">\n";
		$cmd = "'".PROJECT_PATH."/app/calls_active/v_calls_exec.php?cmd=callcenter_config+agent+set+status+".$username."@".$_SESSION['domains'][$domain_uuid]['domain_name']."+'+this.value";
		echo "		<select id='user_status' name='user_status' class='formfld' style='' onchange=\"send_cmd($cmd);\">\n";
		echo "			<option value=''></option>\n";
		echo "			<option value='Available' ".(($user_status == "Available") ? "selected='selected'" : null).">".$text['option-available']."</option>\n";
		echo "			<option value='Available (On Demand)' ".(($user_status == "Available (On Demand)") ? "selected='selected'" : null).">".$text['option-available_on_demand']."</option>\n";
		echo "			<option value='Logged Out' ".(($user_status == "Logged Out") ? "selected='selected'" : null).">".$text['option-logged_out']."</option>\n";
		echo "			<option value='On Break' ".(($user_status == "On Break") ? "selected='selected'" : null).">".$text['option-on_break']."</option>\n";
		echo "			<option value='Do Not Disturb' ".(($user_status == "Do Not Disturb") ? "selected='selected'" : null).">".$text['option-do_not_disturb']."</option>\n";
		echo "		</select>\n";
		echo "		<br />\n";
		echo "		".$text['description-status']."<br />\n";
		echo "	</td>\n";
		echo "	</tr>\n";
	}

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-user_language']."\n";
	echo "	</td>\n";
	echo "	<td class=\"vtable\" align='left'>\n";
	echo "		<select id='user_language' name='user_language' class='formfld' style=''>\n";
	echo "		<option value=''></option>\n";
	//get all language codes from database
	$sql = "select * from v_languages order by language asc";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$language_codes[$row["code"]] = $row["language"];
	}
	unset($prep_statement, $result, $row);
	foreach ($_SESSION['app']['languages'] as $code) {
		$selected = ($code == $user_settings['domain']['language']['code']) ? "selected='selected'" : null;
		echo "	<option value='".$code."' ".$selected.">".$language_codes[$code]." [".$code."]</option>\n";
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-user_language']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	echo "	<tr>\n";
	echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
	echo "		".$text['label-time_zone']."\n";
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
	echo "		".$text['description-time_zone']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/api/app_config.php')) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-api_key']."</td>";
		echo "		<td class='vtable'>\n";
		echo "			<input type=\"text\" class='formfld' name=\"api_key\" id='api_key' value=\"".$api_key."\" >";
		echo "			<input type='button' class='btn' value='".$text['button-generate']."' onclick=\"getElementById('api_key').value='".uuid()."';\">";
		if (strlen($text['description-api_key']) > 0) {
			echo "			<br />".$text['description-api_key']."<br />\n";
		}
		echo "		</td>";
		echo "	</tr>";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='user_enabled'>\n";
	echo "		<option value='true'>".$text['option-true']."</option>\n";
	echo "		<option value='false' ".(($user_enabled == "false") ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "	</select>\n";
	echo "<br />\n";
	echo $text['description-enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td colspan='2' align='right'>";
	echo "			<input type='hidden' name='id' value=\"$user_uuid\">";
	echo "			<input type='hidden' name='username_old' value=\"$username\">";
	echo "			<br>";
	echo "			<input type='button' class='btn' value='".$text['button-save']."' onclick=\"document.getElementById('action').value = '".$text['button-save']."'; submit_form();\">";
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

	echo "<script>\n";
//capture enter key to submit form
	echo "	$(window).keypress(function(event){\n";
	echo "		if (event.which == 13) { submit_form(); }\n";
	echo "	});\n";
// convert password fields to
	echo "	function submit_form() {\n";
	echo "		$('input:password').css('visibility','hidden');\n";
	echo "		$('input:password').attr({type:'text'});\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

	if (permission_exists('user_setting_view')) {
		require "user_settings.php";
	}

//include the footer
	require_once "resources/footer.php";

?>
