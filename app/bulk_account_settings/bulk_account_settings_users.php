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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	KonradSC <konrd@yahoo.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//include the class
	require_once "resources/check_auth.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('bulk_account_settings_users')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}
	
//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set them as variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);
	$option_selected = check_str($_GET["option_selected"]);
	
//handle search term
	$search = check_str($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_mod = "and ( ";
		$sql_mod .= "username ILIKE '%".$search."%' ";
		$sql_mod .= "or user_enabled ILIKE '%".$search."%' ";		
		$sql_mod .= "or user_status ILIKE '%".$search."%' ";
		$sql_mod .= ") ";
	}
	if (strlen($order_by) < 1) {
		$order_by = "username";
		$order = "ASC";
	}

	$domain_uuid = $_SESSION['domain_uuid'];
	
//get total extension count from the database
	$sql = "select count(*) as num_rows from v_users where domain_uuid = '".$_SESSION['domain_uuid']."' ".$sql_mod." ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$total_users = $row['num_rows'];
		if (($db_type == "pgsql") or ($db_type == "mysql")) {
			$numberic_users = $row['num_rows'];
		}
	}
	unset($prep_statement, $row);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if (!isset($_GET['page'])) { $_GET['page'] = 0; }
	$_GET['page'] = check_str($_GET['page']);
	list($paging_controls_mini, $rows_per_page, $var_3) = paging($total_users, $param, $rows_per_page, true); //top
	list($paging_controls, $rows_per_page, $var_3) = paging($total_users, $param, $rows_per_page); //bottom
	$offset = $rows_per_page * $_GET['page'];

//get all the users from the database
	$sql = "SELECT \n";
	$sql .= "username, \n";
	$sql .= "user_uuid, \n";
	$sql .= "user_status, \n";
	$sql .= "user_enabled \n";
	$sql .= "FROM v_users \n";
	$sql .= "WHERE domain_uuid = '$domain_uuid' and 1 = 1 \n";
	$sql .= $sql_mod; //add search mod from above
	$sql .= "ORDER BY ".$order_by." ".$order." \n";
	$sql .= "limit $rows_per_page offset $offset ";
	$database = new database;
	$database->select($sql);
	$directory = $database->result;
	unset($database,$result);

//get all the users' groups from the database
	$sql = "select ";
	$sql .= "	gu.*, g.domain_uuid as group_domain_uuid ";
	$sql .= "from ";
	$sql .= "	v_group_users as gu, ";
	$sql .= "	v_groups as g ";
	$sql .= "where ";
	$sql .= "	gu.group_uuid = g.group_uuid ";
	if (!(permission_exists('user_all') && $_GET['showall'] == 'true')) {
		$sql .= "	and gu.domain_uuid = '".$domain_uuid."' ";
	}
	$sql .= "order by ";
	$sql .= "	g.domain_uuid desc, ";
	$sql .= "	g.group_name asc ";
	$database = new database;
	$database->select($sql);
	$result = $database->result;

	if (count($result) > 0) {
		foreach($result as $row) {
			$user_groups[$row['user_uuid']][] = $row['group_name'].(($row['group_domain_uuid'] != '') ? "@".$_SESSION['domains'][$row['group_domain_uuid']]['domain_name'] : null);
		}
	}
	unset($database,$result);

//get all the users' timezones from the database
	$sql = "select ";
	$sql .= "	us.*, u.domain_uuid as setting_domain_uuid ";
	$sql .= "from ";
	$sql .= "	v_user_settings as us, ";
	$sql .= "	v_users as u ";
	$sql .= "where ";
	$sql .= "	us.user_uuid = u.user_uuid ";
	$sql .= "	and user_setting_subcategory = 'time_zone' ";
	$sql .= "order by ";
	$sql .= "	u.domain_uuid desc, ";
	$sql .= "	u.username asc ";
	$database = new database;
	$database->select($sql);
	$result = $database->result;

	if (count($result) > 0) {
		foreach($result as $row) {
			$user_time_zone[$row['user_uuid']][] = $row['user_setting_value'];
		}
	}
	unset($database,$result);

		
//additional includes
	require_once "resources/header.php";
	$document['title'] = $text['title-users_settings'];

//set the alternating styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//javascript for password
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

	
//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'>\n";
	echo "		<b>".$text['header-users']." (".$numberic_users.")</b><br>\n";

//options list
	echo "<form name='frm' method='get' id=option_selected>\n";
	echo "    <select class='formfld' name='option_selected'  onchange=\"this.form.submit();\">\n";
	echo "    <option value=''>".$text['label-extension_null']."</option>\n";
	if ($option_selected == "user_enabled") {
		echo "    <option value='user_enabled' selected='selected'>".$text['label-user_enabled']."</option>\n";
	}
	else {
		echo "    <option value='user_enabled'>".$text['label-user_enabled']."</option>\n";
	}
	if ($option_selected == "group") {
		echo "    <option value='group' selected='selected'>".$text['label-group']."</option>\n";
	}
	if ($option_selected == "password") {
		echo "    <option value='password' selected='selected'>".$text['label-password']."</option>\n";
	}
	else {
		echo "    <option value='password'>".$text['label-password']."</option>\n";
	}
	if ($option_selected == "user_status") {
		echo "    <option value='user_status' selected='selected'>".$text['label-user_status']."</option>\n";
	}
	else {
		echo "    <option value='user_status'>".$text['label-user_status']."</option>\n";
	}
	if ($option_selected == "time_zone") {
		echo "    <option value='time_zone' selected='selected'>".$text['label-time_zone']."</option>\n";
	}
	else {
		echo "    <option value='time_zone'>".$text['label-time_zone']."</option>\n";
	}
	echo "    </select>\n";
	echo "    </form>\n";
	echo "<br />\n";
	echo $text['description-user_settings_description']."\n";
	echo "</td>\n";

	echo "		<td align='right' width='100%' style='vertical-align: top;'>";
	echo "		<form method='get' action=''>\n";
	echo "			<td style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo "				<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='bulk_account_settings.php'\" value='".$text['button-back']."'>\n";	
	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".$search."'>";
	echo "				<input type='hidden' class='txt' style='width: 150px' name='option_selected' id='option_selected' value='".$option_selected."'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	if ($paging_controls_mini != '') {
		echo 			"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "			</td>\n";
	echo "		</form>\n";	
	echo "  </tr>\n";
	
	
	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo "			".$text['description-users_settings']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

	if (strlen($option_selected) > 0) {
		echo "<form name='users' method='post' action='bulk_account_settings_users_update.php'>\n";
		echo "<input class='formfld' type='hidden' name='option_selected' maxlength='255' value=\"$option_selected\">\n";
		echo "<table width='auto' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		//option is Password
		if($option_selected == 'password') {
			echo "<td class='vtable' align='left'>\n";
			echo "    <input class='formfld' type='password' name='new_setting' maxlength='255' value=\"$new_setting\">\n";
			echo "<br />\n";
			echo $text["description-".$option_selected.""]."\n";
			echo "</td>\n";
		}
		
		//option is Enabled
		if($option_selected == 'user_enabled') {
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='new_setting'>\n";
			echo "    <option value='true'>".$text['label-true']."</option>\n";
			echo "    <option value='false'>".$text['label-false']."</option>\n";
			echo "    </select>\n";
			echo "    <br />\n";
			echo $text["description-".$option_selected.""]."\n";
			echo "</td>\n";
		}
		//option is user_status
		if($option_selected == 'user_status') {
			echo "<td class='vtable' align='left'>\n";
			echo "		<select name='new_setting' class='formfld' style=''>\n";
			echo "			<option value=''></option>\n";
			echo "			<option value='Available'>".$text['option-available']."</option>\n";
			echo "			<option value='Available (On Demand)'>".$text['option-available_on_demand']."</option>\n";
			echo "			<option value='Logged Out'>".$text['option-logged_out']."</option>\n";
			echo "			<option value='On Break'>".$text['option-on_break']."</option>\n";
			echo "			<option value='Do Not Disturb'>".$text['option-do_not_disturb']."</option>\n";
			echo "		</select>\n";
			echo "    <br />\n";
			echo $text["description-".$option_selected.""]."\n";
			echo "</td>\n";
		}
		//option is user_time_zone
		if($option_selected == 'time_zone') {
			echo "<td class='vtable' align='left'>\n";
			echo "		<select name='new_setting' class='formfld' style=''>\n";
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

						echo "			<option value='".$row."'>".$row."</option>\n";

					$previous_category = $category;
					$x++;
				}
				echo "		</select>\n";
			echo "    <br />\n";
			echo $text["description-".$option_selected.""]."\n";
			echo "</td>\n";
		}
		//option is group
		if($option_selected == 'group') {
			echo "		<td class='vtable'>";
			$sql = "select * from v_groups ";
			$sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
			$sql .= "order by domain_uuid desc, group_name asc ";
			$database = new database;
			$database->select($sql);
			$result = $database->result;
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
		}
		echo "<td align='left'>\n";
		echo "<input type='button' class='btn' alt='".$text['button-submit']."' onclick=\"if (confirm('".$text['confirm-update']."')) { document.forms.users.submit(); }\" value='".$text['button-submit']."'; if (check_password_strength(document.getElementById('password').value)) { submit_form(); }>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>";
		echo "<br />";
	}
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if (is_array($directory)) {
		echo "<th style='width: 30px; text-align: center; padding: 0px;'><input type='checkbox' id='chk_all' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
	}
	echo th_order_by('username', $text['label-username'], $order_by,$order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo th_order_by('user_status', $text['label-user_status'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo th_order_by('username', $text['label-group'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo th_order_by('username', $text['label-time_zone'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo th_order_by('user_enabled', $text['label-user_enabled'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo "</tr>\n";

if (is_array($directory)) {

		foreach($directory as $key => $row) {
			$tr_link = (permission_exists('extension_edit')) ? " href='/core/users/user_edit.php?id=".$row['user_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; vertical-align: middle; padding: 0px;'>";
			echo "		<input type='checkbox' name='id[]' id='checkbox_".$row['user_uuid']."' value='".$row['user_uuid']."' onclick=\"if (!this.checked) { document.getElementById('chk_all').checked = false; }\">";
			echo "	</td>";
			$user_ids[] = 'checkbox_'.$row['user_uuid'];
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['username']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['user_status']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
				if (sizeof($user_groups[$row['user_uuid']]) > 0) {
					echo implode(', ', $user_groups[$row['user_uuid']]);
				}
				echo "&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
				if (sizeof($user_time_zone[$row['user_uuid']]) > 0) {
					echo implode(', ', $user_time_zone[$row['user_uuid']]);
				}
				echo "&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['user_enabled']."&nbsp;</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}
		unset($directory, $row);
	}
	echo "</table>";
	echo "</form>";

	if (strlen($paging_controls) > 0) {
		echo "<br />";
		echo $paging_controls."\n";
	}

	echo "<br /><br />".((is_array($directory)) ? "<br /><br />" : null);

	// check or uncheck all checkboxes
	if (sizeof($user_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what) {\n";
		echo "		document.getElementById('chk_all').checked = (what == 'all') ? true : false;\n";
		foreach ($user_ids as $user_id) {
			echo "		document.getElementById('".$user_id."').checked = (what == 'all') ? true : false;\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

	if (is_array($directory)) {
		// check all checkboxes
		key_press('ctrl+a', 'down', 'document', null, null, "check('all');", true);

		// delete checked
		key_press('delete', 'up', 'document', array('#search'), $text['confirm-delete'], 'document.forms.frm.submit();', true);
	}

//show the footer
	require_once "resources/footer.php";
?>