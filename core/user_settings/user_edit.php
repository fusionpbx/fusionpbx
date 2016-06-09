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
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists("user_account_setting_view")) {
	//access granted
}
else {
	echo "access denied";
	return;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the username from v_users
	$username = $_SESSION["username"];
	$user_uuid = $_SESSION["user_uuid"];

//required to be a superadmin to update an account that is a member of the superadmin group
	$superadmin_list = superadmin_list($db);
	if (if_superadmin($superadmin_list, $user_uuid)) {
		if (!if_group("superadmin")) {
			echo "access denied";
			return;
		}
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

if (count($_POST)>0 && $_POST["persistform"] != "1") {

	//get the HTTP values and set as variables
		$password = check_str($_POST["password"]);
		$password_confirm = check_str($_POST["password_confirm"]);
		$user_status = check_str($_POST["user_status"]);
		$user_template_name = check_str($_POST["user_template_name"]);
		$user_language = check_str($_POST["user_language"]);
		$user_time_zone = check_str($_POST["user_time_zone"]);
		$group_member = check_str($_POST["group_member"]);

	//check required values
		if ($password != $password_confirm) { $msg_error = $text['message-password_mismatch']; }

		if ($msg_error != '') {
			$_SESSION["message"] = $msg_error;
			$_SESSION["message_mood"] = 'negative';
			header("Location: user_edit.php");
			exit;
		}

		if (!check_password_strength($password, $text)) {
			header("Location: user_edit.php");
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
				$sql .= "'".$_SESSION["domain_uuid"]."', ";
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

	//check to see if user time_zone is set
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

	//sql update
		$sql  = "update v_users set ";
		if (strlen($password) > 0 && $password_confirm == $password) {
			//salt used with the password to create a one way hash
				$salt = generate_password('20', '4');
			//set the password
				$sql .= "password = '".md5($salt.$password)."', ";
				$sql .= "salt = '".$salt."', ";
		}
		$sql .= "user_status = '$user_status' ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and user_uuid = '$user_uuid' ";
		if (permission_exists("user_account_setting_edit")) {
			$count = $db->exec(check_sql($sql));
		}

	//if call center app is installed then update the user_status
		if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/call_center')) {
			//update the user_status
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				$switch_cmd .= "callcenter_config agent set status ".$username."@".$_SESSION['domain_name']." '".$user_status."'";
				$switch_result = event_socket_request($fp, 'api '.$switch_cmd);

			//update the user state
				$cmd = "api callcenter_config agent set state ".$username."@".$_SESSION['domain_name']." Waiting";
				$response = event_socket_request($fp, $cmd);
		}

	//redirect the browser
		$_SESSION["message"] = $text['confirm-update'];
		header("Location: ".PROJECT_PATH."/core/user_settings/user_edit.php");
		return;
}
else {
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and user_uuid = '$user_uuid' ";
	$sql .= "and user_enabled = 'true' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as $row) {
		//$password = $row["password"];
		$user_status = $row["user_status"];
		break; //limit to 1 row
	}

	//get the groups the user is a member of
	//group_members function defined in config.php
	$group_members = group_members($db, $user_uuid);
}

//include the header
	require_once "resources/header.php";

//show the content
	$table_width ='width="100%"';

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

	$req['length'] = $_SESSION['security']['password_length']['numeric'];
	$req['number'] = ($_SESSION['security']['password_number']['boolean'] == 'true') ? true : false;
	$req['lowercase'] = ($_SESSION['security']['password_lowercase']['boolean'] == 'true') ? true : false;
	$req['uppercase'] = ($_SESSION['security']['password_uppercase']['boolean'] == 'true') ? true : false;
	$req['special'] = ($_SESSION['security']['password_special']['boolean'] == 'true') ? true : false;

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

	echo "	function show_strenth_meter() {\n";
	echo "		$('#pwstrength_progress').slideDown();\n";
	echo "	}\n";
	echo "</script>\n";

	echo "<form name='frm' id='frm' method='post' action=''>";

	echo "<table $table_width cellpadding='0' cellspacing='0' border='0'>";
	echo "<td align='left' width='100%' nowrap><b>".$text['title']."</b></td>\n";
	echo "<td nowrap='nowrap'>\n";
	if (strlen($_SESSION['login']['destination']['url']) > 0) {
		echo "	<input type='button' class='btn' onclick=\"window.location='".$_SESSION['login']['destination']['url']."'\" value='".$text['button-back']."'>";
	}
	echo "	<input type='button' class='btn' value='".$text['button-save']."' onclick='submit_form();'>";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "	".$text['description']." \n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<br />\n";

	echo "<table $table_width cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>\n";
	echo "	<th class='th' colspan='2' align='left'>".$text['table-title']."</th>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td width='30%' class='vncellreq' valign='top'>".$text['label-username']."</td>";
	echo "		<td width='70%' class='vtable'>";
	echo "			".$username."<input type='hidden' id='username' value='".$username."'>\n";
	echo "		</td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-password']."</td>";
	echo "		<td class='vtable'>";
	echo "			<input type='password' autocomplete='off' class='formfld' name='password' id='password' value='' onkeypress='show_strenth_meter();' onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();'>";
	echo "			<div id='pwstrength_progress' class='pwstrength_progress'></div>";
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-confirm-password']."</td>";
	echo "		<td class='vtable'>";
	echo "			<input type='password' autocomplete='off' class='formfld' name='password_confirm' id='password_confirm' value='' onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();'>";
	echo "		</td>";
	echo "	</tr>";

	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	echo "<br>";
	echo "<br>";

	echo "<table $table_width cellpadding='0' cellspacing='0'>";
	echo "	<tr>\n";
	echo "	<th class='th' colspan='2' align='left'>".$text['table2-title']."</th>\n";
	echo "	</tr>\n";

	if ($_SESSION['user_status_display'] == "false") {
		//hide the user_status when it is set to false
	}
	else {
		echo "	<tr>\n";
		echo "	<td width='30%' class=\"vncell\" valign='top'>\n";
		echo "		".$text['label-status']."\n";
		echo "	</td>\n";
		echo "	<td width='70%' class=\"vtable\" align='left'>\n";
		echo "		<select id='user_status' name='user_status' class='formfld' style=''>\n";
		echo "		<option value=''></option>\n";
		if ($user_status == "Available") {
			echo "		<option value='Available' selected='selected'>".$text['check-available-status']."</option>\n";
		}
		else {
			echo "		<option value='Available'>".$text['check-available-status']."</option>\n";
		}
		if ($user_status == "Available (On Demand)") {
			echo "		<option value='Available (On Demand)' selected='selected'>".$text['check-available-ondemand-status']."</option>\n";
		}
		else {
			echo "		<option value='Available (On Demand)'>".$text['check-available-ondemand-status']."</option>\n";
		}
		if ($user_status == "Logged Out") {
			echo "		<option value='Logged Out' selected='selected'>".$text['check-loggedout-status']."</option>\n";
		}
		else {
			echo "		<option value='Logged Out'>".$text['check-loggedout-status']."</option>\n";
		}
		if ($user_status == "On Break") {
			echo "		<option value='On Break' selected='selected'>".$text['check-onbreak-status']."</option>\n";
		}
		else {
			echo "		<option value='On Break'>".$text['check-onbreak-status']."</option>\n";
		}
		if ($user_status == "Do Not Disturb") {
			echo "		<option value='Do Not Disturb' selected='selected'>".$text['check-do-not-disturb-status']."</option>\n";
		}
		else {
			echo "		<option value='Do Not Disturb'>".$text['check-do-not-disturb-status']."</option>\n";
		}
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
	echo "		".$text['label-time']."\n";
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
	echo "		".$text['description-timezone']."<br />\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "</table>";
	echo "<br />";

	echo "<div align='right'><input type='button' class='btn' value='".$text['button-save']."' onclick=\"if (check_password_strength(document.getElementById('password').value)) { submit_form(); }\"></div>";
	echo "<br />";

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

//include the footer
	require_once "resources/footer.php";

?>