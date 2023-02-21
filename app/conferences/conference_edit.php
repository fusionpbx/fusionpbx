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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('conference_add') || permission_exists('conference_edit')) {
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
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$conference_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (count($_POST) > 0) {
		$dialplan_uuid = $_POST["dialplan_uuid"];
		$conference_name = $_POST["conference_name"];
		$conference_extension = $_POST["conference_extension"];
		$conference_pin_number = $_POST["conference_pin_number"];
		$conference_profile = $_POST["conference_profile"];
		$conference_flags = $_POST["conference_flags"];
		$conference_email_address = $_POST["conference_email_address"];
		$conference_account_code = $_POST["conference_account_code"];
		$conference_order = $_POST["conference_order"];
		$conference_description = $_POST["conference_description"];
		$conference_enabled = $_POST["conference_enabled"] ?: 'false';

		//sanitize the conference name
		$conference_name = preg_replace("/[^A-Za-z0-9\- ]/", "", $conference_name);
		//$conference_name = str_replace(" ", "-", $conference_name);
	}

//delete the user from the v_conference_users
	if ($_GET["a"] == "delete" && permission_exists("conference_delete")) {

		$user_uuid = $_REQUEST["user_uuid"];
		$conference_uuid = $_REQUEST["id"];

		$p = new permissions;
		$p->add('conference_user_delete', 'temp');

		$array['conference_users'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
		$array['conference_users'][0]['conference_uuid'] = $conference_uuid;
		$array['conference_users'][0]['user_uuid'] = $user_uuid;

		$database = new database;
		$database->app_name = 'conferences';
		$database->app_uuid = 'b81412e8-7253-91f4-e48e-42fc2c9a38d9';
		$database->delete($array);
		$response = $database->message;
		unset($array);

		$p->delete('conference_user_delete', 'temp');

		message::add($text['confirm-delete']);
		header("Location: conference_edit.php?id=".$conference_uuid);
		exit;
	}

//add the user to the v_conference_users
	if (is_uuid($_REQUEST["user_uuid"]) && is_uuid($_REQUEST["id"]) && $_GET["a"] != "delete") {
		//set the variables
			$user_uuid = $_REQUEST["user_uuid"];
			$conference_uuid = $_REQUEST["id"];

		//assign the user to the extension
			$array['conference_users'][0]['conference_user_uuid'] = uuid();
			$array['conference_users'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			$array['conference_users'][0]['conference_uuid'] = $conference_uuid;
			$array['conference_users'][0]['user_uuid'] = $user_uuid;

			$p = new permissions;
			$p->add('conference_user_add', 'temp');

			$database = new database;
			$database->app_name = 'conferences';
			$database->app_uuid = 'b81412e8-7253-91f4-e48e-42fc2c9a38d9';
			$database->save($array);
			$response = $database->message;
			unset($array);

			$p->delete('conference_user_add', 'temp');

		//send a message
			message::add($text['confirm-add']);
			header("Location: conference_edit.php?id=".urlencode($conference_uuid));
			exit;
	}

//process http post variables
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//get the conference id
			if ($action == "add") {
				$conference_uuid = uuid();
				$dialplan_uuid = uuid();
			}
			if ($action == "update") {
				$conference_uuid = $_POST["conference_uuid"];
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: conferences.php');
				exit;
			}

		//check for all required data
			$msg = '';
			//if (strlen($dialplan_uuid) == 0) { $msg .= "Please provide: Dialplan UUID<br>\n"; }
			if (strlen($conference_name) == 0) { $msg .= "".$text['confirm-name']."<br>\n"; }
			if (strlen($conference_extension) == 0) { $msg .= "".$text['confirm-extension']."<br>\n"; }
			//if (strlen($conference_pin_number) == 0) { $msg .= "Please provide: Pin Number<br>\n"; }
			if (strlen($conference_profile) == 0) { $msg .= "".$text['confirm-profile']."<br>\n"; }
			//if (strlen($conference_flags) == 0) { $msg .= "Please provide: Flags<br>\n"; }
			//if (strlen($conference_order) == 0) { $msg .= "Please provide: Order<br>\n"; }
			//if (strlen($conference_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
			if (strlen($conference_enabled) == 0) { $msg .= "".$text['confirm-enabled']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				$document['title'] = $text['title-conference'];
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

		//add or update the database
			if ($_POST["persistformvar"] != "true") {

				//update the conference extension
					$array['conferences'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					$array['conferences'][0]['conference_uuid'] = $conference_uuid;
					$array['conferences'][0]['dialplan_uuid'] = $dialplan_uuid;
					$array['conferences'][0]['conference_name'] = $conference_name;
					$array['conferences'][0]['conference_extension'] = $conference_extension;
					$array['conferences'][0]['conference_pin_number'] = $conference_pin_number;
					$array['conferences'][0]['conference_profile'] = $conference_profile;
					$array['conferences'][0]['conference_flags'] = $conference_flags;
					if (permission_exists('conference_email_address')) {
						$array['conferences'][0]['conference_email_address'] = $conference_email_address;
					}
					if (permission_exists('conference_account_code')) {
						$array['conferences'][0]['conference_account_code'] = $conference_account_code;
					}
					$array['conferences'][0]['conference_order'] = $conference_order;
					$array['conferences'][0]['conference_description'] = $conference_description;
					$array['conferences'][0]['conference_enabled'] = $conference_enabled;

				//conference pin number
					$pin_number = (strlen($conference_pin_number) > 0) ? '+'.$conference_pin_number : '';

				//build the xml
					$dialplan_xml = "<extension name=\"".$conference_name."\" continue=\"\" uuid=\"".$dialplan_uuid."\">\n";
					$dialplan_xml .= "	<condition field=\"destination_number\" expression=\"^".$conference_extension."$\">\n";
					$dialplan_xml .= "		<action application=\"answer\" data=\"\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"conference_uuid=".$conference_uuid."\" inline=\"true\"/>\n";
					//$dialplan_xml .= "		<action application=\"set\" data=\"conference_name=".$conference_name."\" inline=\"true\"/>\n";
					$dialplan_xml .= "		<action application=\"set\" data=\"conference_extension=".$conference_extension."\" inline=\"true\"/>\n";
					$dialplan_xml .= "		<action application=\"conference\" data=\"".$conference_extension."@".$_SESSION['domain_name']."@".$conference_profile.$pin_number."+flags{'".$conference_flags."'}\"/>\n";
					$dialplan_xml .= "	</condition>\n";
					$dialplan_xml .= "</extension>\n";

				//update the conference dialplan
					$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
					$array['dialplans'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
					$array['dialplans'][0]['dialplan_name'] = $conference_name;
					$array['dialplans'][0]['dialplan_number'] = $conference_extension;
					$array['dialplans'][0]['app_uuid'] = 'b81412e8-7253-91f4-e48e-42fc2c9a38d9';
					$array['dialplans'][0]['dialplan_xml'] = $dialplan_xml;
					$array['dialplans'][0]['dialplan_order'] = '333';
					$array['dialplans'][0]['dialplan_context'] = $_SESSION['domain_name'];
					$array['dialplans'][0]['dialplan_enabled'] = $conference_enabled;
					$array['dialplans'][0]['dialplan_description'] = $conference_description;

					$p = new permissions;
					$p->add('dialplan_add', 'temp');
					$p->add('dialplan_edit', 'temp');

					$database = new database;
					$database->app_name = 'conferences';
					$database->app_uuid = 'b81412e8-7253-91f4-e48e-42fc2c9a38d9';
					$database->save($array);
					$response = $database->message;
					unset($array);

					$p->delete('dialplan_add', 'temp');
					$p->delete('dialplan_edit', 'temp');

				//delete the dialplan details
					$sql = "delete from v_dialplan_details ";
					$sql .= "where dialplan_uuid = :dialplan_uuid ";
					//$sql .= "and domain_uuid = :domain_uuid ";
					//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$parameters['dialplan_uuid'] = $dialplan_uuid;
					$database = new database;
					$database->execute($sql, $parameters);
					unset($sql, $parameters);

				//add the message
					message::add($text['confirm-update']);

				//apply settings reminder
					$_SESSION["reload_xml"] = true;

				//clear the cache
					$cache = new cache;
					$cache->delete("dialplan:".$_SESSION["domain_name"]);

				//clear the destinations session array
					if (isset($_SESSION['destinations']['array'])) {
						unset($_SESSION['destinations']['array']);
					}

				//redirect the browser
					header("Location: conferences.php");
					exit;

			}
	}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$conference_uuid = $_GET["id"];
		$sql = "select * from v_conferences ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and conference_uuid = :conference_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['conference_uuid'] = $conference_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && sizeof($row) != 0) {
			$dialplan_uuid = $row["dialplan_uuid"];
			$conference_name = $row["conference_name"];
			$conference_extension = $row["conference_extension"];
			$conference_pin_number = $row["conference_pin_number"];
			$conference_profile = $row["conference_profile"];
			$conference_flags = $row["conference_flags"];
			$conference_email_address = $row["conference_email_address"];
			$conference_account_code = $row["conference_account_code"];
			$conference_order = $row["conference_order"];
			$conference_description = $row["conference_description"];
			$conference_enabled = $row["conference_enabled"];
			$conference_name = str_replace("-", " ", $conference_name);
		}
		unset($sql, $parameters, $row);
	}

//set the defaults
	if (strlen($conference_enabled) == 0) { $conference_enabled = 'true'; }

//get the conference profiles
	$sql = "select * ";
	$sql .= "from v_conference_profiles ";
	$sql .= "where profile_enabled = 'true' ";
	$sql .= "and profile_name <> 'sla' ";
	$database = new database;
	$conference_profiles = $database->select($sql, null, 'all');
	unset($sql);

//get conference users
	$sql = "select * from v_conference_users as e, v_users as u ";
	$sql .= "where e.user_uuid = u.user_uuid  ";
	$sql .= "and u.user_enabled = 'true' ";
	$sql .= "and e.domain_uuid = :domain_uuid ";
	$sql .= "and e.conference_uuid = :conference_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['conference_uuid'] = $conference_uuid;
	$database = new database;
	$conference_users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the users
	$sql = "select * from v_users ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and user_enabled = 'true' ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$users = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//set the default
	if ($conference_profile == "") { $conference_profile = "default"; }

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-conference'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	echo "		<b>".$text['title-conference']."</b>";
	echo "	</div>\n";
	echo "	<div class='actions'>\n";

	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'conferences.php']);
	if ($action == 'update') {
		if (permission_exists('conference_cdr_view')) {
			echo button::create(['type'=>'button','label'=>$text['button-cdr'],'icon'=>'list','link'=>PROJECT_PATH.'/app/conference_cdr/conference_cdr.php?id='.urlencode($conference_uuid)]);
		}
		if (permission_exists('conference_interactive_view')) {
			echo button::create(['type'=>'button','label'=>$text['button-view'],'icon'=>$_SESSION['theme']['button_icon_view'],'style'=>'','link'=>'../conferences_active/conference_interactive.php?c='.urlencode($conference_extension)]);
		}
		else if (permission_exists('conference_active_view')) {
			echo button::create(['type'=>'button','label'=>$text['button-view'],'icon'=>$_SESSION['theme']['button_icon_view'],'style'=>'','link'=>'../conferences_active/conferences_active.php']);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description']."\n";
	echo "<br /><br />\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_name' maxlength='255' value=\"".escape($conference_name)."\">\n";
	echo "<br />\n";
	echo "".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-extension']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_extension' maxlength='255' value=\"".escape($conference_extension)."\">\n";
	echo "<br />\n";
	echo "".$text['description-extension']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-pin']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='conference_pin_number' maxlength='255' value=\"".escape($conference_pin_number)."\">\n";
	echo "<br />\n";
	echo "".$text['description-pin']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('conference_user_add') || permission_exists('conference_user_edit')) {
		if ($action == "update") {
			echo "	<tr>";
			echo "		<td class='vncell' valign='top'>".$text['label-user_list']."</td>";
			echo "		<td class='vtable'>";

			if (is_array($conference_users) && @sizeof($conference_users) != 0) {
				echo "		<table width='50%'>\n";
				foreach ($conference_users as $field) {
					echo "		<tr>\n";
					echo "			<td class='vtable'>".escape($field['username'])."</td>\n";
					echo "			<td>\n";
					echo "				<a href='conference_edit.php?id=".urlencode($conference_uuid)."&domain_uuid=".$_SESSION['domain_uuid']."&user_uuid=".urlencode($field['user_uuid'])."&a=delete' alt='delete' onclick=\"return confirm('".$text['confirm-delete-2']."')\">$v_link_label_delete</a>\n";
					echo "			</td>\n";
					echo "		</tr>\n";
				}
				echo "		</table>\n";
				echo "		<br />\n";
			}

			echo "			<select name=\"user_uuid\" class='formfld'>\n";
			echo "			<option value=\"\"></option>\n";
			foreach ($users as $field) {
				echo "			<option value='".escape($field['user_uuid'])."'>".escape($field['username'])."</option>\n";
			}
			echo "			</select>";
			echo button::create(['type'=>'submit','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add']]);

			echo "			<br>\n";
			echo "			".$text['description-user-add']."\n";
			echo "			<br />\n";
			echo "		</td>";
			echo "	</tr>";
		}
	}

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['table-profile']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select class='formfld' name='conference_profile'>\n";
	foreach ($conference_profiles as $row) {
		if ($conference_profile === $row['profile_name']) {
			echo "		<option value='".escape($row['profile_name'])."' selected='selected'>".escape($row['profile_name'])."</option>\n";
		}
		else {
			echo "		<option value='".escape($row['profile_name'])."'>".escape($row['profile_name'])."</option>\n";
		}
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "".$text['description-profile']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-flags']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_flags' maxlength='255' value=\"".escape($conference_flags)."\">\n";
	echo "<br />\n";
	echo "".$text['description-flags']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (permission_exists('conference_email_address')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-email_address']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='conference_email_address' maxlength='255' value=\"".escape($conference_email_address)."\">\n";
		echo "<br />\n";
		echo "".$text['description-email_address']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	if (permission_exists('conference_account_code')) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-account_code']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='conference_account_code' maxlength='255' value=\"".escape($conference_account_code)."\">\n";
		echo "<br />\n";
		echo "".$text['description-account_code']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-order']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<select name='conference_order' class='formfld'>\n";
	if (strlen(htmlspecialchars($dialplan_order))> 0) {
		echo "		<option selected='selected' value='".htmlspecialchars($dialplan_order)."'>".htmlspecialchars($dialplan_order)."</option>\n";
	}
	$i=0;
	while($i<=999) {
		if (strlen($i) == 1) { echo "		<option value='00$i'>00$i</option>\n"; }
		if (strlen($i) == 2) { echo "		<option value='0$i'>0$i</option>\n"; }
		if (strlen($i) == 3) { echo "		<option value='$i'>$i</option>\n"; }
		$i++;
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "".$text['description-order']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['table-enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='conference_enabled' name='conference_enabled' value='true' ".($conference_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='conference_enabled' name='conference_enabled'>\n";
		echo "		<option value='true' ".($conference_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($conference_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo "".$text['description-conference-enable']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='conference_description' maxlength='255' value=\"".escape($conference_description)."\">\n";
	echo "<br />\n";
	echo "".$text['description-info']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='dialplan_uuid' value='".escape($dialplan_uuid)."'>\n";
		echo "<input type='hidden' name='conference_uuid' value='".escape($conference_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
