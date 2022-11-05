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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('setting_view') || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the number of rows in v_extensions
	$sql = " select count(*) from v_settings ";
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//set the action
	$action = $num_rows == 0 ? "add" : "update";

//get the http values and set them as php variables
	if (count($_POST)>0) {
		//$numbering_plan = $_POST["numbering_plan"];
		//$default_gateway = $_POST["default_gateway"];
		$setting_uuid = $_POST["setting_uuid"];
		$event_socket_ip_address = $_POST["event_socket_ip_address"];
		if (strlen($event_socket_ip_address) == 0) { $event_socket_ip_address = '127.0.0.1'; }
		$event_socket_port = $_POST["event_socket_port"];
		$event_socket_password = $_POST["event_socket_password"];
		$event_socket_acl = $_POST["event_socket_acl"];
		$xml_rpc_http_port = $_POST["xml_rpc_http_port"];
		$xml_rpc_auth_realm = $_POST["xml_rpc_auth_realm"];
		$xml_rpc_auth_user = $_POST["xml_rpc_auth_user"];
		$xml_rpc_auth_pass = $_POST["xml_rpc_auth_pass"];
		//$admin_pin = $_POST["admin_pin"];
		$mod_shout_decoder = $_POST["mod_shout_decoder"];
		$mod_shout_volume = $_POST["mod_shout_volume"];
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	//check for all required data
		$msg = '';
		//if (strlen($numbering_plan) == 0) { $msg .= "Please provide: Numbering Plan<br>\n"; }
		//if (strlen($default_gateway) == 0) { $msg .= "Please provide: Default Gateway<br>\n"; }
		if (strlen($event_socket_port) == 0) { $msg .= "Please provide: Event Socket Port<br>\n"; }
		if (strlen($event_socket_password) == 0) { $msg .= "Please provide: Event Socket Password<br>\n"; }
		//if (strlen($event_socket_acl) == 0) { $msg .= "Please provide: Event Socket ACL<br>\n"; }
		//if (strlen($xml_rpc_http_port) == 0) { $msg .= "Please provide: XML RPC HTTP Port<br>\n"; }
		//if (strlen($xml_rpc_auth_realm) == 0) { $msg .= "Please provide: XML RPC Auth Realm<br>\n"; }
		//if (strlen($xml_rpc_auth_user) == 0) { $msg .= "Please provide: XML RPC Auth User<br>\n"; }
		//if (strlen($xml_rpc_auth_pass) == 0) { $msg .= "Please provide: XML RPC Auth Password<br>\n"; }
		//if (strlen($admin_pin) == 0) { $msg .= "Please provide: Admin PIN Number<br>\n"; }
		//if (strlen($mod_shout_decoder) == 0) { $msg .= "Please provide: Mod Shout Decoder<br>\n"; }
		//if (strlen($mod_shout_volume) == 0) { $msg .= "Please provide: Mod Shout Volume<br>\n"; }
		if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
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
			if (permission_exists('setting_edit')) {
				//build array
					$array['settings'][0]['setting_uuid'] = $action == "add" ? uuid() : $setting_uuid;
					$array['settings'][0]['event_socket_ip_address'] = $event_socket_ip_address;
					$array['settings'][0]['event_socket_port'] = $event_socket_port;
					$array['settings'][0]['event_socket_password'] = $event_socket_password;
					$array['settings'][0]['event_socket_acl'] = $event_socket_acl;
					$array['settings'][0]['xml_rpc_http_port'] = $xml_rpc_http_port;
					$array['settings'][0]['xml_rpc_auth_realm'] = $xml_rpc_auth_realm;
					$array['settings'][0]['xml_rpc_auth_user'] = $xml_rpc_auth_user;
					$array['settings'][0]['xml_rpc_auth_pass'] = $xml_rpc_auth_pass;
					$array['settings'][0]['mod_shout_decoder'] = $mod_shout_decoder;
					$array['settings'][0]['mod_shout_volume'] = $mod_shout_volume;
				//grant temporary permissions
					$p = new permissions;
					if ($action == 'add') {
						$p->add('setting_add', 'temp');
					}
					else if ($action == 'update') {
						$p->add('setting_edit', 'temp');
					}
				//execute insert
					$database = new database;
					$database->app_name = 'settings';
					$database->app_uuid = 'b6b1b2e5-4ba5-044c-8a5c-18709a15eb60';
					$database->save($array);
					unset($array);
				//revoke temporary permissions
					$p->delete('setting_add', 'temp');
					$p->delete('setting_edit', 'temp');
				//synchronize settings
					save_setting_xml();
				//set message
					if ($action == 'add') {
						message::add($text['message-add']);
					}
					else if ($action == 'update') {
						message::add($text['message-update']);
					}
				//redirect
					header("Location: setting_edit.php");
					exit;
			}
		}
	}

//pre-populate the form
	if ($_POST["persistformvar"] != "true") {
		$sql = "select * from v_settings ";
		$database = new database;
		$row = $database->select($sql, null, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$setting_uuid = $row['setting_uuid'];
			$event_socket_ip_address = $row["event_socket_ip_address"];
			$event_socket_port = $row["event_socket_port"];
			$event_socket_password = $row["event_socket_password"];
			$event_socket_acl = $row["event_socket_acl"];
			$xml_rpc_http_port = $row["xml_rpc_http_port"];
			$xml_rpc_auth_realm = $row["xml_rpc_auth_realm"];
			$xml_rpc_auth_user = $row["xml_rpc_auth_user"];
			$xml_rpc_auth_pass = $row["xml_rpc_auth_pass"];
			$mod_shout_decoder = $row["mod_shout_decoder"];
			$mod_shout_volume = $row["mod_shout_volume"];
		}
		unset($sql, $row);
	}

//show the header
	if ($action == "add") {
		$document['title'] = $text['title-settings_add'];
	}
	else if ($action == "update") {
		$document['title'] = $text['title-settings_update'];
	}
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($action == "add") {
		echo "<b>".$text['title-settings_add']."</b>";
	}
	else if ($action == "update") {
		echo "<b>".$text['title-settings_update']."</b>";
	}
	echo "	</div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'never','onclick'=>'submit_form();']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-event_socket_ip']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='event_socket_ip_address' maxlength='255' value=\"".escape($event_socket_ip_address)."\">\n";
	echo "<br />\n";
	echo $text['description-event_socket_ip']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-event_socket_port']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='event_socket_port' maxlength='255' autocomplete='new-password' value=\"".escape($event_socket_port)."\">\n";
	echo "    <input type='text' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
	echo "<br />\n";
	echo $text['description-event_socket_port']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-event_socket_password']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input type='password' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
	echo "    <input class='formfld' type='password' name='event_socket_password' id='event_socket_password' autocomplete='new-password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='50' value=\"".escape($event_socket_password)."\">\n";
	echo "<br />\n";
	echo $text['description-event_socket_password']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-event_socket_acl']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='event_socket_acl' id='event_socket_acl' maxlength='50' value=\"".escape($event_socket_acl)."\">\n";
	echo "<br />\n";
	echo $text['description-event_socket_acl']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-xml_rpc_http_port']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='xml_rpc_http_port' maxlength='255' value=\"".escape($xml_rpc_http_port)."\">\n";
	echo "<br />\n";
	echo $text['description-xml_rpc_http_port']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-xml_rpc_auth_realm']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='xml_rpc_auth_realm' maxlength='255' value=\"".escape($xml_rpc_auth_realm)."\">\n";
	echo "<br />\n";
	echo $text['description-xml_rpc_auth_realm']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-xml_rpc_auth_user']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='xml_rpc_auth_user' maxlength='255' autocomplete='new-password' value=\"".escape($xml_rpc_auth_user)."\">\n";
	echo "    <input type='text' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
	echo "<br />\n";
	echo $text['description-xml_rpc_auth_user']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-xml_rpc_auth_pass']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input type='password' style='display: none;' disabled='disabled'>\n"; //help defeat browser auto-fill
	echo "    <input class='formfld' type='password' name='xml_rpc_auth_pass' id='xml_rpc_auth_pass' autocomplete='new-password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='50' value=\"".escape($xml_rpc_auth_pass)."\">\n";
	echo "<br />\n";
	echo $text['description-xml_rpc_auth_pass']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-mod_shout_decoder']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='mod_shout_decoder'>\n";
	echo "    <option value=''></option>\n";
	if ($mod_shout_decoder == "i486") {
		echo "    <option value='i486' selected='selected'>i486</option>\n";
	}
	else {
		echo "    <option value='i486'>i486</option>\n";
	}
	if ($mod_shout_decoder == "i586") {
		echo "    <option value='i586' selected='selected'>i586</option>\n";
	}
	else {
		echo "    <option value='i586'>i586</option>\n";
	}
	if ($mod_shout_decoder == "i686") {
		echo "    <option value='i686' selected='selected'>i686</option>\n";
	}
	else {
		echo "    <option value='i686'>i686</option>\n";
	}
	if ($mod_shout_decoder == "amd64") {
		echo "    <option value='amd64' selected='selected'>amd64</option>\n";
	}
	else {
		echo "    <option value='amd64'>amd64</option>\n";
	}
	if ($mod_shout_decoder == "generic") {
		echo "    <option value='generic' selected='selected'>generic</option>\n";
	}
	else {
		echo "    <option value='generic'>generic</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['description-mod_shout_decoder']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-mod_shout_volume']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='mod_shout_volume' maxlength='255' value=\"$mod_shout_volume\">\n";
	echo "<br />\n";
	echo $text['description-mod_shout_volume']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	echo "<input type='hidden' name='setting_uuid' value='".$setting_uuid."'>\n";
	echo "</form>";

	echo "<script>\n";
//hide password fields before submit
	echo "	function submit_form() {\n";
	echo "		hide_password_fields();\n";
	echo "		$('form#frm').submit();\n";
	echo "	}\n";
	echo "</script>\n";

//show the footer
	require_once "resources/footer.php";

?>