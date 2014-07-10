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

if (if_group("admin") || if_group("superadmin")) {
	//access allowed
}
else {
	echo "access denied";
	return;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

$username = check_str($_POST["username"]);
$password = check_str($_POST["password"]);
$confirmpassword = check_str($_POST["confirmpassword"]);
$group_name = check_str($_POST["group_name"]);
$user_email = check_str($_POST["user_email"]);
$contact_organization = check_str($_POST["contact_organization"]);
$contact_name_given = check_str($_POST["contact_name_given"]);
$contact_name_family = check_str($_POST["contact_name_family"]);


if (count($_POST)>0 && check_str($_POST["persistform"]) != "1") {

	$msg = '';

	//--- begin captcha verification ---------------------
		//session_start(); //make sure sessions are started
		if (strtolower($_SESSION["captcha"]) != strtolower($_REQUEST["captcha"]) || strlen($_SESSION["captcha"]) == 0) {
			//$msg .= "Captcha Verification Failed<br>\n";
		}
		else {
			//echo "verified";
		}
	//--- end captcha verification -----------------------

	//username is already used.
	if (strlen($username) == 0) {
		$msg .= $text['message-required'].$text['label-username']."<br>\n";
	}
	else {
		$sql = "SELECT * FROM v_users ";
		$sql .= "WHERE username = '$username' ";
		if ($_SESSION["user"]["unique"]["text"] != "global") {
			$sql .= "AND domain_uuid = '$domain_uuid' ";
		}
		//$sql .= "and user_enabled = 'true' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		if (count($prep_statement->fetchAll(PDO::FETCH_NAMED)) > 0) {
			$msg .= "Please choose a different Username.<br>\n";
		}
	}

	if (strlen($password) == 0) { $msg .= $text['message-password_blank']."<br>\n"; }
	if ($password != $confirmpassword) { $msg .= $text['message-password_mismatch']."<br>\n"; }
	//if (strlen($contact_organization) == 0) { $msg .= $text['message-required'].$text['label-company_name']."<br>\n"; }
	//if (strlen($contact_name_given) == 0) { $msg .= $text['message-required'].$text['label-first_name']."<br>\n"; }
	//if (strlen($contact_name_family) == 0) { $msg .= $text['message-required'].$text['label-last_name']."<br>\n"; }
	if (strlen($user_email) == 0) { $msg .= $text['message-required'].$text['label-email']."<br>\n"; }

	if (strlen($msg) > 0) {
		require_once "resources/header.php";
		echo "<div align='center'>";
		echo "<table><tr><td>";
		echo $msg;
		echo "</td></tr></table>";
		require_once "resources/persist_form.php";
		echo persistform($_POST);
		echo "</div>";
		require_once "resources/footer.php";
		return;
	}

	//salt used with the password to create a one way hash
	$salt = generate_password('20', '4');

	//prepare the uuids
	$user_uuid = uuid();
	$group_user_uuid = uuid();
	$contact_uuid = uuid();

	//add the user
	$sql = "insert into v_users ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "user_uuid, ";
	$sql .= "contact_uuid, ";
	$sql .= "username, ";
	$sql .= "password, ";
	$sql .= "salt, ";
	$sql .= "add_date, ";
	$sql .= "add_user, ";
	$sql .= "user_enabled ";
	$sql .= ") ";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'$user_uuid', ";
	$sql .= "'$contact_uuid', ";
	$sql .= "'$username', ";
	$sql .= "'".md5($salt.$password)."', ";
	$sql .= "'".$salt."', ";
	$sql .= "now(), ";
	$sql .= "'".$_SESSION["username"]."', ";
	$sql .= "'true' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);

	//add the user to the group
	if (strlen($group_name) > 0) {
		if ( ($group_name == "superadmin" && if_group("superadmin")) || $group_name != "superadmin") {
			$sql = "insert into v_group_users ";
			$sql .= "( ";
			$sql .= "group_user_uuid, ";
			$sql .= "domain_uuid, ";
			$sql .= "group_name, ";
			$sql .= "user_uuid ";
			$sql .= ") ";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".$group_user_uuid."', ";
			$sql .= "'".$domain_uuid."', ";
			$sql .= "'".$group_name."', ";
			$sql .= "'".$user_uuid."' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}
	}

	//add to contacts
	$sql = "insert into v_contacts ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "contact_uuid, ";
	$sql .= "contact_type, ";
	$sql .= "contact_organization, ";
	$sql .= "contact_name_given, ";
	$sql .= "contact_name_family, ";
	$sql .= "contact_nickname, ";
	$sql .= "contact_email ";
	$sql .= ") ";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'$contact_uuid', ";
	$sql .= "'user', ";
	$sql .= "'$contact_organization', ";
	$sql .= "'$contact_name_given', ";
	$sql .= "'$contact_name_family', ";
	$sql .= "'$username', ";
	$sql .= "'$user_email' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);

	//log the success
	//$log_type = 'user'; $log_status='add'; $log_add_user=$_SESSION["username"]; $log_desc= "username: ".$username." user added.";
	//log_add($db, $log_type, $log_status, $log_desc, $log_add_user, $_SERVER["REMOTE_ADDR"]);

	$_SESSION["message"] = $text['message-add'];
	header("Location: index.php");
	return;
}

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['title-user_add'];

//show the content
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

	echo "<div align='center'>";

	$tablewidth ='width="100%"';
	echo "<form method='post' action=''>";
	echo "<div class='borderlight' style='padding:10px;'>\n";

	echo "<table border='0' $tablewidth cellpadding='6' cellspacing='0'>";
	echo "	<tr>\n";
	echo "		<td width='80%'>\n";
	echo "			<b>".$text['header-user_add']."</b>\n";
	echo "			<br><br>\n";
	echo "			".$text['description-user_add']."\n";
	echo "		</td>\n";
	echo "		<td width='20%' align='right'>\n";
	echo "			<input type='button' class='btn' name='back' alt='".$text['button-back']."' onclick=\"window.history.back()\" value='".$text['button-back']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	echo "<table border='0' $tablewidth cellpadding='6' cellspacing='0'>";
	echo "	<tr>";
	echo "		<td class='vncellreq' width='30%'>".$text['label-username'].":</td>";
	echo "		<td class='vtable' width='70%'><input type='text' class='formfld' autocomplete='off' name='username' value='$username'></td>";
	echo "	</tr>";

	echo "	<tr>";
	echo "		<td class='vncellreq'>".$text['label-password'].":</td>";
	echo "		<td class='vtable'><input type='password' class='formfld' autocomplete='off' name='password' id='password' value='$password' onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncellreq'>".$text['label-confirm_password'].":</td>";
	echo "		<td class='vtable'><input type='password' class='formfld' autocomplete='off' name='confirmpassword' id='confirmpassword' value='$confirmpassword' onfocus='compare_passwords();' onkeyup='compare_passwords();' onblur='compare_passwords();'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncellreq'>".$text['label-email'].":</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='user_email' value='$user_email'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell' valign='top'>".$text['label-group'].":</td>";
	echo "		<td class='vtable'>";
	$sql = "SELECT * FROM v_groups ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$sql .= "order by group_name asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	echo "			<select name=\"group_name\" class='formfld' style='width: auto; margin-right: 3px;'>\n";
	echo "				<option value=\"\"></option>\n";
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $field) {
		if ($field['group_name'] == "superadmin" && !if_group("superadmin")) { continue; }	//only show the superadmin group to other users in the superadmin group
		if (!in_array($field["group_name"], $assigned_groups)) {
			echo "		<option value='".$field['group_name']."'>".$field['group_name']."</option>\n";
		}
	}
	echo "			</select>";
	unset($sql, $result);
	echo "		</td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-first_name'].":</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='contact_name_given' value='$contact_name_given'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-last_name'].":</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='contact_name_family' value='$contact_name_family'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>".$text['label-company_name'].":</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='contact_organization' value='$contact_organization'></td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

	echo "<div class='' style='padding:10px;'>\n";
	echo "<table $tablewidth>";
	echo "	<tr>";
	echo "		<td colspan='2' align='right'>";
	echo "       <input type='submit' name='submit' class='btn' value='".$text['button-create_account']."'>";
	echo "		</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "</div>";

//show the footer
	require_once "resources/footer.php";
?>