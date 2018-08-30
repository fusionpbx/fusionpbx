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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get user uuid
	if (
		(is_uuid($_REQUEST["id"]) && permission_exists('user_edit')) ||
		(is_uuid($_REQUEST["id"]) && $_REQUEST["id"] == $_SESSION['user_uuid'])
		)  {
		$user_uuid = check_str($_REQUEST["id"]);
		$action = 'edit';
	}
	else if (permission_exists('user_add') && $_REQUEST["id"] == '') {
		$user_uuid = uuid();
		$action = 'add';
	}
	else {
		// load users own account
			header("Location: user_edit.php?id=".$_SESSION['user_uuid']);
			exit;
	}

//get total user count from the database, check limit, if defined
	if (permission_exists('user_add') && $action == 'add' && $_SESSION['limit']['users']['numeric'] != '') {
		$sql = "select count(user_uuid) as num_rows from v_users where domain_uuid = '".$_SESSION['domain_uuid']."' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			$total_users = $row['num_rows'];
		}
		unset($prep_statement, $row);
		if ($total_users >= $_SESSION['limit']['users']['numeric']) {
			messages::add($text['message-maximum_users'].' '.$_SESSION['limit']['users']['numeric'], 'negative');
			header('Location: users.php');
			exit;
		}
	}

//required to be a superadmin to update an account that is a member of the superadmin group
	if (permission_exists('user_edit') && $action == 'edit') {
		$superadmins = superadmin_list($db);
		if (if_superadmin($superadmins, $user_uuid)) {
			if (!if_group("superadmin")) {
				echo "access denied";
				exit;
			}
		}
	}

//delete the group from the user
	if ($_GET["a"] == "delete" && permission_exists("user_delete")) {
		//set the variables
			$group_uuid = check_str($_GET["group_uuid"]);
		//delete the group from the users
			$sql = "delete from v_group_users ";
			$sql .= "where group_uuid = '".$group_uuid."' ";
			$sql .= "and user_uuid = '".$user_uuid."' ";
			$db->exec(check_sql($sql));
		//redirect the user
			messages::add($text['message-update']);
			header("Location: user_edit.php?id=".$user_uuid);
			return;
	}

if (count($_POST) > 0 && $_POST["persistform"] != "1") {

	//get the HTTP values and set as variables
		if (permission_exists('user_edit') && $action == 'edit') {
			$user_uuid = $_REQUEST["id"];
			$username_old = check_str($_POST["username_old"]);
		}
		$domain_uuid = check_str($_POST["domain_uuid"]);
		$username = check_str($_POST["username"]);
		$password = check_str($_POST["password"]);
		$password_confirm = check_str($_POST["password_confirm"]);
		$user_status = check_str($_POST["user_status"]);
		$user_language = check_str($_POST["user_language"]);
		$user_time_zone = check_str($_POST["user_time_zone"]);
		if (permission_exists('user_edit') && $action == 'edit') {
			$contact_uuid = check_str($_POST["contact_uuid"]);
		}
		else if (permission_exists('user_add') && $action == 'add') {
			$user_email = check_str($_POST["user_email"]);
			$contact_organization = check_str($_POST["contact_organization"]);
			$contact_name_given = check_str($_POST["contact_name_given"]);
			$contact_name_family = check_str($_POST["contact_name_family"]);
		}
		$group_uuid_name = check_str($_POST["group_uuid_name"]);
		$user_enabled = check_str($_POST["user_enabled"]);
		$api_key = check_str($_POST["api_key"]);

	//check required values
		if ($username == '') { $msg_error = $text['message-required'].$text['label-username']; }
		if (permission_exists('user_edit') && $action == 'edit') {
			if ($username != $username_old && $username != '') {
				$sql = "select count(*) as num_rows from v_users where username = '".$username."'";
				if ($_SESSION["user"]["unique"]["text"] != "global"){
					$sql .= " and domain_uuid = '".$domain_uuid."'";
				}
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
		}
		if ($password != '' && $password != $password_confirm) { $msg_error = $text['message-password_mismatch']; }
		if (permission_exists('user_add') && $action == 'add') {
			if ($password == '') { $msg_error = $text['message-password_blank']; }
			if ($user_email == '') { $msg_error = $text['message-required'].$text['label-email']; }
			if ($group_uuid_name == '') { $msg_error = $text['message-required'].$text['label-group']; }
		}

		if ($msg_error != '') {
			messages::add($msg_error, 'negative');
			if ($action == 'edit') {
				header("Location: user_edit.php?id=".$user_uuid);
			}
			else {
				header("Location: user_edit.php");
			}
			exit;
		}

		if (!check_password_strength($password, $text, 'user')) {
			if ($action == 'edit') {
				header("Location: user_edit.php?id=".$user_uuid);
			}
			else {
				header("Location: user_edit.php");
			}
			exit;
		}

	//set initial array indexes
		$i = $n = $x = $c = 0;

	//check to see if user language is set
		$sql = "select user_setting_uuid, user_setting_value from v_user_settings ";
		$sql .= "where user_setting_category = 'domain' ";
		$sql .= "and user_setting_subcategory = 'language' ";
		$sql .= "and user_uuid = '".$user_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['user_setting_uuid'] == '' && $user_language != '') {
				//add user setting to array for insert
					$array['user_settings'][$i]['user_setting_uuid'] = uuid();
					$array['user_settings'][$i]['user_uuid'] = $user_uuid;
					$array['user_settings'][$i]['domain_uuid'] = $domain_uuid;
					$array['user_settings'][$i]['user_setting_category'] = 'domain';
					$array['user_settings'][$i]['user_setting_subcategory'] = 'language';
					$array['user_settings'][$i]['user_setting_name'] = 'code';
					$array['user_settings'][$i]['user_setting_value'] = $user_language;
					$array['user_settings'][$i]['user_setting_enabled'] = 'true';
					$i++;
			}
			else {
				if ($row['user_setting_value'] == '' || $user_language == '') {
					$sql = "delete from v_user_settings ";
					$sql .= "where user_setting_category = 'domain' ";
					$sql .= "and user_setting_subcategory = 'language' ";
					$sql .= "and user_uuid = '".$user_uuid."' ";
					$db->exec(check_sql($sql));
					unset($sql);
				}
				else {
					//add user setting to array for update
						$array['user_settings'][$i]['user_setting_uuid'] = $row['user_setting_uuid'];
						$array['user_settings'][$i]['user_uuid'] = $user_uuid;
						$array['user_settings'][$i]['domain_uuid'] = $domain_uuid;
						$array['user_settings'][$i]['user_setting_category'] = 'domain';
						$array['user_settings'][$i]['user_setting_subcategory'] = 'language';
						$array['user_settings'][$i]['user_setting_name'] = 'code';
						$array['user_settings'][$i]['user_setting_value'] = $user_language;
						$array['user_settings'][$i]['user_setting_enabled'] = 'true';
						$i++;
				}
			}
		}
		unset($sql, $prep_statement, $row);

	//check to see if user time zone is set
		$sql = "select user_setting_uuid, user_setting_value from v_user_settings ";
		$sql .= "where user_setting_category = 'domain' ";
		$sql .= "and user_setting_subcategory = 'time_zone' ";
		$sql .= "and user_uuid = '".$user_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['user_setting_uuid'] == '' && $user_time_zone != '') {
				//add user setting to array for insert
					$array['user_settings'][$i]['user_setting_uuid'] = uuid();
					$array['user_settings'][$i]['user_uuid'] = $user_uuid;
					$array['user_settings'][$i]['domain_uuid'] = $domain_uuid;
					$array['user_settings'][$i]['user_setting_category'] = 'domain';
					$array['user_settings'][$i]['user_setting_subcategory'] = 'time_zone';
					$array['user_settings'][$i]['user_setting_name'] = 'name';
					$array['user_settings'][$i]['user_setting_value'] = $user_time_zone;
					$array['user_settings'][$i]['user_setting_enabled'] = 'true';
					$i++;
			}
			else {
				if ($row['user_setting_value'] == '' || $user_time_zone == '') {
					$sql = "delete from v_user_settings ";
					$sql .= "where user_setting_category = 'domain' ";
					$sql .= "and user_setting_subcategory = 'time_zone' ";
					$sql .= "and user_uuid = '".$user_uuid."' ";
					$db->exec(check_sql($sql));
					unset($sql);
				}
				else {
					//add user setting to array for update
						$array['user_settings'][$i]['user_setting_uuid'] = $row['user_setting_uuid'];
						$array['user_settings'][$i]['user_uuid'] = $user_uuid;
						$array['user_settings'][$i]['domain_uuid'] = $domain_uuid;
						$array['user_settings'][$i]['user_setting_category'] = 'domain';
						$array['user_settings'][$i]['user_setting_subcategory'] = 'time_zone';
						$array['user_settings'][$i]['user_setting_name'] = 'name';
						$array['user_settings'][$i]['user_setting_value'] = $user_time_zone;
						$array['user_settings'][$i]['user_setting_enabled'] = 'true';
						$i++;
				}
			}
		}

	//assign the user to the group
		if ((permission_exists('user_add') || permission_exists('user_edit')) && $_REQUEST["group_uuid_name"] != '') {
			$group_data = explode('|', $group_uuid_name);
			$group_uuid = $group_data[0];
			$group_name = $group_data[1];
			//only a superadmin can add other superadmins or admins, admins can only add other admins
				switch ($group_name) {
					case "superadmin": if (!if_group("superadmin")) { break; }
					case "admin": if (!if_group("superadmin") && !if_group("admin")) { break; }
					default: //add group user to array for insert
						$array['group_users'][$n]['group_user_uuid'] = uuid();
						$array['group_users'][$n]['domain_uuid'] = $domain_uuid;
						$array['group_users'][$n]['group_name'] = $group_name;
						$array['group_users'][$n]['group_uuid'] = $group_uuid;
						$array['group_users'][$n]['user_uuid'] = $user_uuid;
						$n++;
				}
		}

	//update domain, if changed
		if ((permission_exists('user_add') || permission_exists('user_edit')) && permission_exists('user_domain')) {
			//adjust group user records
				$sql = "select group_user_uuid from v_group_users ";
				$sql .= "where user_uuid = '".$user_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					foreach ($result as $row) {
						//add group user to array for update
							$array['group_users'][$n]['group_user_uuid'] = $row['group_user_uuid'];
							$array['group_users'][$n]['domain_uuid'] = $domain_uuid;
							$n++;
					}
				}
				unset($sql, $prep_statement, $result, $row);
			//adjust user setting records
				$sql = "select user_setting_uuid from v_user_settings ";
				$sql .= "where user_uuid = '".$user_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					foreach ($result as $row) {
						//add user setting to array for update
							$array['user_settings'][$i]['user_setting_uuid'] = $row['user_setting_uuid'];
							$array['user_settings'][$i]['domain_uuid'] = $domain_uuid;
							$i++;
					}
				}
				unset($sql, $prep_statement, $result, $row);
			//unassign any foreign domain groups
				$sql = "delete from v_group_users where ";
				$sql .= "domain_uuid = '".$domain_uuid."' ";
				$sql .= "and user_uuid = '".$user_uuid."' ";
				$sql .= "and group_uuid not in (";
				$sql .= "	select group_uuid from v_groups where domain_uuid = '".$domain_uuid."' or domain_uuid is null ";
				$sql .= ") ";
				$db->exec(check_sql($sql));
				unset($sql);
		}

	//add contact to array for insert
		if ($action == 'add' && permission_exists('user_add') && permission_exists('contact_add')) {
			$contact_uuid = uuid();
			$array['contacts'][$c]['domain_uuid'] = $domain_uuid;
			$array['contacts'][$c]['contact_uuid'] = $contact_uuid;
			$array['contacts'][$c]['contact_type'] = 'user';
			$array['contacts'][$c]['contact_organization'] = $contact_organization;
			$array['contacts'][$c]['contact_name_given'] = $contact_name_given;
			$array['contacts'][$c]['contact_name_family'] = $contact_name_family;
			$array['contacts'][$c]['contact_nickname'] = $username;
			$c++;
			if (permission_exists('contact_email_add')) {
				$contact_email_uuid = uuid();
				$array['contact_emails'][$c]['contact_email_uuid'] = $contact_email_uuid;
				$array['contact_emails'][$c]['domain_uuid'] = $domain_uuid;
				$array['contact_emails'][$c]['contact_uuid'] = $contact_uuid;
				$array['contact_emails'][$c]['email_address'] = $user_email;
				$array['contact_emails'][$c]['email_primary'] = '1';
				$c++;
			}
		}

	//add user setting to array for update
		$array['users'][$x]['user_uuid'] = $user_uuid;
		$array['users'][$x]['domain_uuid'] = $domain_uuid;
		if ($username != '' && $username != $username_old) {
			$array['users'][$x]['username'] = $username;
		}
		if ($password != '' && $password == $password_confirm) {
			$salt = uuid();
			$array['users'][$x]['password'] = md5($salt.$password);
			$array['users'][$x]['salt'] = $salt;
		}
		$array['users'][$x]['user_status'] = $user_status;
		if (permission_exists('user_add') || permission_exists('user_edit')) {
			$array['users'][$x]['api_key'] = ($api_key != '') ? $api_key : null;
			$array['users'][$x]['user_enabled'] = $user_enabled;
			$array['users'][$x]['contact_uuid'] = ($contact_uuid != '') ? $contact_uuid : null;
			if ($action == 'add') {
				$array['users'][$x]['add_user'] = $_SESSION["user"]["username"];
				$array['users'][$x]['add_date'] = date("Y-m-d H:i:s.uO");
			}
		}
		$x++;

	//add the user_edit permission
		$p = new permissions;
		$p->add("user_setting_add", "temp");
		$p->add("user_setting_edit", "temp");
		$p->add("user_edit", "temp");

	//save the data
		$database = new database;
		$database->app_name = 'users';
		$database->app_uuid = '112124b3-95c2-5352-7e9d-d14c0b88f207';
		$database->save($array);
		//$message = $database->message;

	//remove the temporary permission
		$p->delete("user_setting_add", "temp");
		$p->delete("user_setting_edit", "temp");
		$p->delete("user_edit", "temp");

	//if call center installed
		if ($action == 'edit' && permission_exists('user_edit') && file_exists($_SERVER["PROJECT_ROOT"]."/app/call_centers/app_config.php")) {
			//get the call center agent uuid
				$sql = "select call_center_agent_uuid from v_call_center_agents ";
				$sql .= "where domain_uuid = '".$domain_uuid."' ";
				$sql .= "and user_uuid = '".$user_uuid."' ";
				$prep_statement = $db->prepare(check_sql($sql));
				if ($prep_statement) {
					$prep_statement->execute();
					$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
					$call_center_agent_uuid = $row['call_center_agent_uuid'];
				}
				unset($sql, $prep_statement, $result);

			//update the user_status
				if (isset($call_center_agent_uuid)) {
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					$switch_cmd .= "callcenter_config agent set status ".$call_center_agent_uuid." '".$user_status."'";
					$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
				}

			//update the user state
				if (isset($call_center_agent_uuid)) {
					$cmd = "api callcenter_config agent set state ".$call_center_agent_uuid." Waiting";
					$response = event_socket_request($fp, $cmd);
				}
		}

	//redirect the browser
		messages::add($text['message-update']);
		if ($_REQUEST['action'] == $text['button-add'] || !permission_exists('user_edit')) {
			header("Location: user_edit.php?id=".$user_uuid);
		}
		else {
			header("Location: users.php");
		}
		return;

}

//pre-populate the form
	if ($action == 'edit') {
		//get user data
			$sql = "select * from v_users where user_uuid = '".$user_uuid."' ";
			if (!permission_exists('user_all')) {
				$sql .= "and domain_uuid = '".$domain_uuid."' ";
			}
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_NAMED);
			if (is_array($row) && sizeof($row) > 0) {
				$domain_uuid = $row["domain_uuid"];
				$user_uuid = $row["user_uuid"];
				$username = $row["username"];
				$password = $row["password"];
				$api_key = $row["api_key"];
				$user_enabled = $row["user_enabled"];
				$contact_uuid = $row["contact_uuid"];
				$user_status = $row["user_status"];
			}
			else {
				header("Location: user_edit.php?id=".$_SESSION['user_uuid']);
				exit;
			}
			unset($sql, $prep_statement, $row);

		//get user settings
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
	}

//include the header
	require_once "resources/header.php";
	$document['title'] = $text['title-user_edit'];

//show the content
	echo "<script>\n";
	echo "	function compare_passwords() {\n";
	echo "		if (document.getElementById('password') === document.activeElement || document.getElementById('password_confirm') === document.activeElement) {\n";
	echo "			if ($('#password').val() != '' || $('#password_confirm').val() != '') {\n";
	echo "				if ($('#password').val() != $('#password_confirm').val()) {\n";
	echo "					$('#password').removeClass('formfld_highlight_good');\n";
	echo "					$('#password_confirm').removeClass('formfld_highlight_good');\n";
	echo "					$('#password').addClass('formfld_highlight_bad');\n";
	echo "					$('#password_confirm').addClass('formfld_highlight_bad');\n";
	echo "				}\n";
	echo "				else {\n";
	echo "					$('#password').removeClass('formfld_highlight_bad');\n";
	echo "					$('#password_confirm').removeClass('formfld_highlight_bad');\n";
	echo "					$('#password').addClass('formfld_highlight_good');\n";
	echo "					$('#password_confirm').addClass('formfld_highlight_good');\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			$('#password').removeClass('formfld_highlight_bad');\n";
	echo "			$('#password_confirm').removeClass('formfld_highlight_bad');\n";
	echo "			$('#password').removeClass('formfld_highlight_good');\n";
	echo "			$('#password_confirm').removeClass('formfld_highlight_good');\n";
	echo "		}\n";
	echo "	}\n";

	$req['length'] = $_SESSION['user']['password_length']['numeric'];
	$req['number'] = ($_SESSION['user']['password_number']['boolean'] == 'true') ? true : false;
	$req['lowercase'] = ($_SESSION['user']['password_lowercase']['boolean'] == 'true') ? true : false;
	$req['uppercase'] = ($_SESSION['user']['password_uppercase']['boolean'] == 'true') ? true : false;
	$req['special'] = ($_SESSION['user']['password_special']['boolean'] == 'true') ? true : false;

	echo "	function check_password_strength(pwd) {\n";
	echo "		if ($('#password').val() != '' || $('#password_confirm').val() != '') {\n";
	echo "			var msg_errors = [];\n";
	if (is_numeric($req['length']) && $req['length'] != 0) {
		echo "		var re = /.{".$req['length'].",}/;\n"; //length
		echo "		if (!re.test(pwd)) { msg_errors.push('".$req['length']."+ ".$text['label-characters']."'); }\n";
	}
	if ($req['number']) {
		echo "		var re = /(?=.*[\d])/;\n";  //number
		echo "		if (!re.test(pwd)) { msg_errors.push('1+ ".$text['label-numbers']."'); }\n";
	}
	if ($req['lowercase']) {
		echo "		var re = /(?=.*[a-z])/;\n";  //lowercase
		echo "		if (!re.test(pwd)) { msg_errors.push('1+ ".$text['label-lowercase_letters']."'); }\n";
	}
	if ($req['uppercase']) {
		echo "		var re = /(?=.*[A-Z])/;\n";  //uppercase
		echo "		if (!re.test(pwd)) { msg_errors.push('1+ ".$text['label-uppercase_letters']."'); }\n";
	}
	if ($req['special']) {
		echo "		var re = /(?=.*[\W])/;\n";  //special
		echo "		if (!re.test(pwd)) { msg_errors.push('1+ ".$text['label-special_characters']."'); }\n";
	}
	echo "			if (msg_errors.length > 0) {\n";
	echo "				var msg = '".$text['message-password_requirements'].": ' + msg_errors.join(', ');\n";
	echo "				display_message(msg, 'negative', '6000');\n";
	echo "				return false;\n";
	echo "			}\n";
	echo "			else {\n";
	echo "				return true;\n";
	echo "			}\n";
	echo "		}\n";
	echo "		else {\n";
	echo "			return true;\n";
	echo "		}\n";
	echo "	}\n";

	echo "	function show_strength_meter() {\n";
	echo "		$('#pwstrength_progress').slideDown();\n";
	echo "	}\n";
	echo "</script>\n";

	echo "<form name='frm' id='frm' method='post'>\n";
	echo "<input type='hidden' name='action' id='action' value=''>\n";

	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
	echo "<tr>\n";
	echo "<td align='left' width='90%' valign='top' nowrap><b>".$text['header-user_edit']."</b></td>\n";
	echo "<td align='right' nowrap>\n";
	if (permission_exists('user_add') || permission_exists('user_edit')) {
		echo "	<input type='button' class='btn' onclick=\"window.location='users.php'\" value='".$text['button-back']."'>";
	}
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

	echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>";

	echo "	<tr>";
	echo "		<td width='30%' class='vncellreq' valign='top'>".$text['label-username']."</td>";
	echo "		<td width='70%' class='vtable'>";
	if (permission_exists("user_edit")) {
		echo "		<input type='text' class='formfld' name='username' id='username' value='".escape($username)."' required='required'>\n";
	}
	else {
		echo "		".escape($username)."\n";
		echo "		<input type='hidden' name='username' id='username' value='".escape($username)."'>\n";
	}
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell".(($action == 'add') ? 'req' : null)."' valign='top'>".$text['label-password']."</td>";
	echo "		<td class='vtable'>";
	echo "			<input style='display: none;' type='password'>";
	echo "			<input type='password' autocomplete='off' class='formfld' name='password' id='password' value='' onkeypress='show_strength_meter();' onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();'>";
	echo "			<div id='pwstrength_progress' class='pwstrength_progress'></div>";
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell".(($action == 'add') ? 'req' : null)."' valign='top'>".$text['label-confirm_password']."</td>";
	echo "		<td class='vtable'>";
	echo "			<input type='password' autocomplete='off' class='formfld' name='password_confirm' id='password_confirm' value='' onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();'>";
	echo "		</td>";
	echo "	</tr>";

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
		echo "	<option value='".escape($code)."' ".escape($selected).">".escape($language_codes[$code])." [".escape($code)."]</option>\n";
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
			echo "			<option value='".escape($row)."' selected='selected'>".escape($row)."</option>\n";
		}
		else {
			echo "			<option value='".escape($row)."'>".escape($row)."</option>\n";
		}
		$previous_category = $category;
		$x++;
	}
	echo "		</select>\n";
	echo "		<br />\n";
	echo "		".$text['description-time_zone']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";

	if ($_SESSION['user_status_display'] != "false") {
		echo "	<tr>\n";
		echo "	<td width='20%' class=\"vncell\" valign='top'>\n";
		echo "		".$text['label-status']."\n";
		echo "	</td>\n";
		echo "	<td class=\"vtable\">\n";
		$cmd = "'".PROJECT_PATH."/app/calls_active/v_calls_exec.php?cmd=callcenter_config+agent+set+status+".escape($username)."@".$_SESSION['domains'][$domain_uuid]['domain_name']."+'+this.value";
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

	if ($action == 'edit' && permission_exists("user_edit")) {
		echo "	<tr>";
		echo "		<td class='vncell' valign='top'>".$text['label-contact']."</td>";
		echo "		<td class='vtable'>\n";
		$sql = " select contact_uuid, contact_organization, contact_name_given, contact_name_family, contact_nickname from v_contacts ";
		$sql .= " where domain_uuid = '".escape($domain_uuid)."' ";
		$sql .= " order by contact_organization desc, contact_name_family asc, contact_name_given asc, contact_nickname asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		unset ($prep_statement, $sql);
		echo "<select name=\"contact_uuid\" id=\"contact_uuid\" class=\"formfld\">\n";
		echo "<option value=\"\"></option>\n";
		foreach($result as $row) {
			$contact_name = array();
			if ($row['contact_organization'] != '') { $contact_name[] = $row['contact_organization']; }
			if ($row['contact_name_family'] != '') { $contact_name[] = $row['contact_name_family']; }
			if ($row['contact_name_given'] != '') { $contact_name[] = $row['contact_name_given']; }
			if ($row['contact_name_family'] == '' && $row['contact_name_family'] == '' && $row['contact_nickname'] != '') { $contact_name[] = $row['contact_nickname']; }
			echo "<option value='".escape($row['contact_uuid'])."' ".(($row['contact_uuid'] == $contact_uuid) ? "selected='selected'" : null).">".escape(implode(', ', $contact_name))."</option>\n";
		}
		unset($sql, $result, $row_count);
		echo "</select>\n";
		echo "<br />\n";
		echo $text['description-contact']."\n";
		if (strlen($contact_uuid) > 0) {
			echo "			<a href=\"".PROJECT_PATH."/app/contacts/contact_edit.php?id=".escape($contact_uuid)."\">".$text['description-contact_view']."</a>\n";
		}
		echo "		</td>";
		echo "	</tr>";
	}
	else if ($action == 'add' && permission_exists("user_add")) {
		echo "	<tr>";
		echo "		<td class='vncellreq'>".$text['label-email']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='user_email' value='".escape($user_email)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-first_name']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='contact_name_given' value='".escape($contact_name_given)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-last_name']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='contact_name_family' value='".escape($contact_name_family)."'></td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td class='vncell'>".$text['label-company_name']."</td>";
		echo "		<td class='vtable'><input type='text' class='formfld' name='contact_organization' value='".escape($contact_organization)."'></td>";
		echo "	</tr>";
	}

	if ((permission_exists("user_add") && $action == 'add') || (permission_exists("user_edit") && $action == 'edit')) {
		echo "	<tr>";
		echo "		<td class='vncellreq' valign='top'>".$text['label-group'.(($action == 'edit') ? 's' : null)]."</td>";
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
					echo escape($field['group_name']).(($field['group_domain_uuid'] != '') ? "@".$_SESSION['domains'][$field['group_domain_uuid']]['domain_name'] : null);
					echo "	</td>\n";
					if ($result_count > 1) {
						if (permission_exists('group_member_delete') || if_group("superadmin")) {
							echo "	<td class='list_control_icons' style='width: 25px;'>\n";
							echo "		<a href='user_edit.php?id=".escape($user_uuid)."&domain_uuid=".escape($domain_uuid)."&group_uuid=".escape($field['group_uuid'])."&a=delete' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">".$v_link_label_delete."</a>\n";
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
			if (isset($assigned_groups)) { echo "<br />\n"; }
			echo "<select name='group_uuid_name' class='formfld' style='width: auto; margin-right: 3px;'>\n";
			echo "	<option value=''></option>\n";
			foreach($result as $field) {
				if ($field['group_name'] == "superadmin" && !if_group("superadmin")) { continue; }	//only show the superadmin group to other superadmins
				if ($field['group_name'] == "admin" && (!if_group("superadmin") && !if_group("admin") )) { continue; }	//only show the admin group to other admins
				if ( !isset($assigned_groups) || (isset($assigned_groups) && !in_array($field["group_uuid"], $assigned_groups)) ) {
					echo "	<option value='".$field['group_uuid']."|".$field['group_name']."'>".$field['group_name'].(($field['domain_uuid'] != '') ? "@".$_SESSION['domains'][$field['domain_uuid']]['domain_name'] : null)."</option>\n";
				}
			}
			echo "</select>";
			if ($action == 'edit') {
				echo "<input type='button' class='btn' value=\"".$text['button-add']."\" onclick=\"document.getElementById('action').value = '".$text['button-add']."'; submit_form();\">\n";
			}
		}
		unset($sql, $prep_statement, $result);

		echo "		</td>";
		echo "	</tr>";

		if (permission_exists('user_domain')) {
			echo "<tr>\n";
			echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
			echo "	".$text['label-domain']."\n";
			echo "</td>\n";
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='domain_uuid'>\n";
			foreach ($_SESSION['domains'] as $row) {
				echo "	<option value='".escape($row['domain_uuid'])."' ".(($row['domain_uuid'] == $domain_uuid) ? "selected='selected'" : null).">".escape($row['domain_name'])."</option>\n";
			}
			echo "    </select>\n";
			echo "<br />\n";
			echo $text['description-domain_name']."\n";
			echo "</td>\n";
			echo "</tr>\n";
		}
		else {
			echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>";
		}

		if (permission_exists('api_key')) {
			echo "	<tr>";
			echo "		<td class='vncell' valign='top'>".$text['label-api_key']."</td>";
			echo "		<td class='vtable'>\n";
			echo "			<input type=\"text\" class='formfld' name=\"api_key\" id='api_key' value=\"".escape($api_key)."\" >";
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
		echo "		<option value='false' ".(($user_enabled != "true") ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-enabled']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else {
		echo "<input type='hidden' name='domain_uuid' value='".escape($domain_uuid)."'>";
	}

	echo "	<tr>";
	echo "		<td colspan='2' align='right'>";
	if ($action == 'edit') {
		echo "		<input type='hidden' name='id' value=\"".escape($user_uuid)."\">";
		if (permission_exists("user_edit")) {
			echo "	<input type='hidden' name='username_old' value=\"".escape($username)."\">";
		}
	}
	echo "			<br>";
	echo "			<input type='button' class='btn' value='".$text['button-save']."' onclick=\"document.getElementById('action').value = '".$text['button-save']."'; if (check_password_strength(document.getElementById('password').value)) { submit_form(); }\">";
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
	// convert password fields to text
		echo "	function submit_form() {\n";
		echo "		$('input:password').css('visibility','hidden');\n";
		echo "		$('input:password').attr({type:'text'});\n";
		echo "		$('form#frm').submit();\n";
		echo "	}\n";
	echo "</script>\n";

	if (permission_exists("user_edit") && permission_exists('user_setting_view') && $action == 'edit') {
		require "user_settings.php";
	}

//include the footer
	require_once "resources/footer.php";

?>
