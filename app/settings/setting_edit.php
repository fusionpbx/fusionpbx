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
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
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
	$sql = " select count(*) as num_rows from v_settings ";
	$prep_statement = $db->prepare(check_sql($sql));
	$num_rows = 0;
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		if ($row['num_rows'] > 0) {
			$num_rows = $row['num_rows'];
		}
		else {
			$num_rows = 0;
		}
	}
	unset($prep_statement, $result);

//set the action
	if ($num_rows == 0) {
		$action = "add";
	}
	else {
		$action = "update";
	}

//get the http values and set them as php variables
	if (count($_POST)>0) {
		//$numbering_plan = check_str($_POST["numbering_plan"]);
		//$default_gateway = check_str($_POST["default_gateway"]);
		$event_socket_ip_address = check_str($_POST["event_socket_ip_address"]);
		if (strlen($event_socket_ip_address) == 0) { $event_socket_ip_address = '127.0.0.1'; }
		$event_socket_port = check_str($_POST["event_socket_port"]);
		$event_socket_password = check_str($_POST["event_socket_password"]);
		$xml_rpc_http_port = check_str($_POST["xml_rpc_http_port"]);
		$xml_rpc_auth_realm = check_str($_POST["xml_rpc_auth_realm"]);
		$xml_rpc_auth_user = check_str($_POST["xml_rpc_auth_user"]);
		$xml_rpc_auth_pass = check_str($_POST["xml_rpc_auth_pass"]);
		//$admin_pin = check_str($_POST["admin_pin"]);
		//$smtp_host = check_str($_POST["smtp_host"]);
		//$smtp_secure = check_str($_POST["smtp_secure"]);
		//$smtp_auth = check_str($_POST["smtp_auth"]);
		//$smtp_username = check_str($_POST["smtp_username"]);
		//$smtp_password = check_str($_POST["smtp_password"]);
		//$smtp_from = check_str($_POST["smtp_from"]);
		//$smtp_from_name = check_str($_POST["smtp_from_name"]);
		$mod_shout_decoder = check_str($_POST["mod_shout_decoder"]);
		$mod_shout_volume = check_str($_POST["mod_shout_volume"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	//check for all required data
		$msg = '';
		//if (strlen($numbering_plan) == 0) { $msg .= "Please provide: Numbering Plan<br>\n"; }
		//if (strlen($default_gateway) == 0) { $msg .= "Please provide: Default Gateway<br>\n"; }
		if (strlen($event_socket_port) == 0) { $msg .= "Please provide: Event Socket Port<br>\n"; }
		if (strlen($event_socket_password) == 0) { $msg .= "Please provide: Event Socket Password<br>\n"; }
		//if (strlen($xml_rpc_http_port) == 0) { $msg .= "Please provide: XML RPC HTTP Port<br>\n"; }
		//if (strlen($xml_rpc_auth_realm) == 0) { $msg .= "Please provide: XML RPC Auth Realm<br>\n"; }
		//if (strlen($xml_rpc_auth_user) == 0) { $msg .= "Please provide: XML RPC Auth User<br>\n"; }
		//if (strlen($xml_rpc_auth_pass) == 0) { $msg .= "Please provide: XML RPC Auth Password<br>\n"; }
		//if (strlen($admin_pin) == 0) { $msg .= "Please provide: Admin PIN Number<br>\n"; }
		//if (strlen($smtp_host) == 0) { $msg .= "Please provide: SMTP Host<br>\n"; }
		//if (strlen($smtp_secure) == 0) { $msg .= "Please provide: SMTP Secure<br>\n"; }
		//if (strlen($smtp_auth) == 0) { $msg .= "Please provide: SMTP Auth<br>\n"; }
		//if (strlen($smtp_username) == 0) { $msg .= "Please provide: SMTP Username<br>\n"; }
		//if (strlen($smtp_password) == 0) { $msg .= "Please provide: SMTP Password<br>\n"; }
		//if (strlen($smtp_from) == 0) { $msg .= "Please provide: SMTP From<br>\n"; }
		//if (strlen($smtp_from_name) == 0) { $msg .= "Please provide: SMTP From Name<br>\n"; }
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
			if ($action == "add" && permission_exists('setting_edit')) {
				$sql = "insert into v_settings ";
				$sql .= "(";
				$sql .= "event_socket_ip_address, ";
				$sql .= "event_socket_port, ";
				$sql .= "event_socket_password, ";
				$sql .= "xml_rpc_http_port, ";
				$sql .= "xml_rpc_auth_realm, ";
				$sql .= "xml_rpc_auth_user, ";
				$sql .= "xml_rpc_auth_pass, ";
				$sql .= "mod_shout_decoder, ";
				$sql .= "mod_shout_volume ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$event_socket_ip_address', ";
				$sql .= "'$event_socket_port', ";
				$sql .= "'$event_socket_password', ";
				$sql .= "'$xml_rpc_http_port', ";
				$sql .= "'$xml_rpc_auth_realm', ";
				$sql .= "'$xml_rpc_auth_user', ";
				$sql .= "'$xml_rpc_auth_pass', ";
				$sql .= "'$mod_shout_decoder', ";
				$sql .= "'$mod_shout_volume' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				//synchronize settings
				save_setting_xml();

				$_SESSION["message"] = $text['message-add'];
				header("Location: setting_edit.php");
				return;
			} //if ($action == "add")

			if ($action == "update" && permission_exists('setting_edit')) {
				$sql = "update v_settings set ";
				$sql .= "event_socket_ip_address = '$event_socket_ip_address', ";
				$sql .= "event_socket_port = '$event_socket_port', ";
				$sql .= "event_socket_password = '$event_socket_password', ";
				$sql .= "xml_rpc_http_port = '$xml_rpc_http_port', ";
				$sql .= "xml_rpc_auth_realm = '$xml_rpc_auth_realm', ";
				$sql .= "xml_rpc_auth_user = '$xml_rpc_auth_user', ";
				$sql .= "xml_rpc_auth_pass = '$xml_rpc_auth_pass', ";
				$sql .= "mod_shout_decoder = '$mod_shout_decoder', ";
				$sql .= "mod_shout_volume = '$mod_shout_volume' ";
				$db->exec(check_sql($sql));
				unset($sql);

				//synchronize settings
				save_setting_xml();

				$_SESSION["message"] = $text['message-update'];
				header("Location: setting_edit.php");
				return;
			} //if ($action == "update")
		} //if ($_POST["persistformvar"] != "true")
	} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if ($_POST["persistformvar"] != "true") {
		$sql = "";
		$sql .= "select * from v_settings ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach ($result as &$row) {
				$event_socket_ip_address = $row["event_socket_ip_address"];
				$event_socket_port = $row["event_socket_port"];
				$event_socket_password = $row["event_socket_password"];
				$xml_rpc_http_port = $row["xml_rpc_http_port"];
				$xml_rpc_auth_realm = $row["xml_rpc_auth_realm"];
				$xml_rpc_auth_user = $row["xml_rpc_auth_user"];
				$xml_rpc_auth_pass = $row["xml_rpc_auth_pass"];
				$mod_shout_decoder = $row["mod_shout_decoder"];
				$mod_shout_volume = $row["mod_shout_volume"];
				break; //limit to 1 row
			}
			unset ($prep_statement);
		}
	}

//show the header
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	if ($action == "add") {
	echo "<td align='left' width='30%' nowrap><b>".$text['title-settings-add']."</b><br><br></td>\n";
	}
	if ($action == "update") {
	echo "<td align='left' width='30%' nowrap><b>".$text['title-settings-update']."</b><br><br></td>\n";
	}
	echo "<td width='70%' align='right'>";
	if (permission_exists('setting_edit')) {
		echo "	<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
	}
	echo "<br><br></td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-event-socket-ip']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='event_socket_ip_address' maxlength='255' value=\"$event_socket_ip_address\">\n";
	echo "<br />\n";
	echo $text['description-event-socket-ip']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-event-socket-port']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='event_socket_port' maxlength='255' value=\"$event_socket_port\">\n";
	echo "<br />\n";
	echo $text['description-event-socket-port']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-event-socket-pw']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='password' name='event_socket_password' id='event_socket_password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='50' value=\"$event_socket_password\">\n";
	echo "<br />\n";
	echo $text['description-event-socket-pw']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-xml-rpc-port']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='xml_rpc_http_port' maxlength='255' value=\"$xml_rpc_http_port\">\n";
	echo "<br />\n";
	echo $text['description-xml-rpc-port']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-xml-rpc-realm']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='xml_rpc_auth_realm' maxlength='255' value=\"$xml_rpc_auth_realm\">\n";
	echo "<br />\n";
	echo $text['description-xml-rpc-realm']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-xml-rpc-user']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='xml_rpc_auth_user' maxlength='255' value=\"$xml_rpc_auth_user\">\n";
	echo "<br />\n";
	echo $text['description-xml-rpc-user']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-xml-rpc-pw']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='password' name='xml_rpc_auth_pass' id='xml_rpc_auth_pass' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='50' value=\"$xml_rpc_auth_pass\">\n";
	echo "<br />\n";
	echo $text['description-xml-rpc-pw']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	/*
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    SMTP Host\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='smtp_host' maxlength='255' value=\"$smtp_host\">\n";
	echo "<br />\n";
	echo "Enter the SMTP host address. TLS example: smtp.gmail.com:587\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    SMTP Secure\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='smtp_secure'>\n";
	echo "    <option value=''></option>\n";
	if ($smtp_secure == "none") {
	echo "    <option value='none' SELECTED >none</option>\n";
	}
	else {
	echo "    <option value='none'>none</option>\n";
	}
	if ($smtp_secure == "tls") {
	echo "    <option value='tls' SELECTED >tls</option>\n";
	}
	else {
	echo "    <option value='tls'>tls</option>\n";
	}
	if ($smtp_secure == "ssl") {
	echo "    <option value='ssl' SELECTED >ssl</option>\n";
	}
	else {
	echo "    <option value='ssl'>ssl</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Select the SMTP security. None, TLS, SSL\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    SMTP Auth\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='smtp_auth'>\n";
	echo "    <option value=''></option>\n";
	if ($smtp_auth == "true") {
	echo "    <option value='true' SELECTED >true</option>\n";
	}
	else {
	echo "    <option value='true'>true</option>\n";
	}
	if ($smtp_auth == "false") {
	echo "    <option value='false' SELECTED >false</option>\n";
	}
	else {
	echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Use SMTP Authentication true or false.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    SMTP Username\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='smtp_username' maxlength='255' value=\"$smtp_username\">\n";
	echo "<br />\n";
	echo "Enter the SMTP authentication username.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    SMTP Password\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='password' name='smtp_password' id='smtp_password' onmouseover=\"this.type='text';\" onfocus=\"this.type='text';\" onmouseout=\"if (!$(this).is(':focus')) { this.type='password'; }\" onblur=\"this.type='password';\" maxlength='50' value=\"$smtp_password\">\n";
	echo "<br />\n";
	echo "Enter the SMTP authentication password.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    SMTP From\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='smtp_from' maxlength='255' value=\"$smtp_from\">\n";
	echo "<br />\n";
	echo "Enter the SMTP From email address.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    SMTP From Name\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='smtp_from_name' maxlength='255' value=\"$smtp_from_name\">\n";
	echo "<br />\n";
	echo "Enter the SMTP From Name.\n";
	echo "</td>\n";
	echo "</tr>\n";
	*/

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-shout-decoder']."\n";
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
	echo $text['description-shout-decoder']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-shout-volume']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='mod_shout_volume' maxlength='255' value=\"$mod_shout_volume\">\n";
	echo "<br />\n";
	echo $text['description-shout-volume']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	if (permission_exists('setting_edit')) {
		echo "	<tr>\n";
		echo "		<td colspan='2' align='right'>\n";
		echo "			<br>";
		echo "			<input type='submit' name='submit' class='btn' value='".$text['button-save']."'>\n";
		echo "		</td>\n";
		echo "	</tr>";
	}
	echo "</table>";
	echo "<br><br>";
	echo "</form>";

//show the footer
	require_once "resources/footer.php";
?>