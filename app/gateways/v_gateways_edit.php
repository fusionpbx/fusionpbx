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
if (permission_exists('gateways_add') || permission_exists('gateways_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add or update the database
	if (isset($_REQUEST["id"])) {
		$action = "update";
		$gateway_uuid = check_str($_REQUEST["id"]);
	}
	else {
		$action = "add";
	}

//get the http values and set them as php variables
	if (count($_POST)>0) {
		//$domain_uuid = check_str($_POST["domain_uuid"]);
		$gateway = check_str($_POST["gateway"]);
		$username = check_str($_POST["username"]);
		$password = check_str($_POST["password"]);
		$distinct_to = check_str($_POST["distinct_to"]);
		$auth_username = check_str($_POST["auth_username"]);
		$realm = check_str($_POST["realm"]);
		$from_user = check_str($_POST["from_user"]);
		$from_domain = check_str($_POST["from_domain"]);
		$proxy = check_str($_POST["proxy"]);
		$register_proxy = check_str($_POST["register_proxy"]);
		$outbound_proxy = check_str($_POST["outbound_proxy"]);
		$expire_seconds = check_str($_POST["expire_seconds"]);
		$register = check_str($_POST["register"]);
		$register_transport = check_str($_POST["register_transport"]);
		$retry_seconds = check_str($_POST["retry_seconds"]);
		$extension = check_str($_POST["extension"]);
		$ping = check_str($_POST["ping"]);
		$caller_id_in_from = check_str($_POST["caller_id_in_from"]);
		$supress_cng = check_str($_POST["supress_cng"]);
		$sip_cid_type = check_str($_POST["sip_cid_type"]);
		$extension_in_contact = check_str($_POST["extension_in_contact"]);
		$context = check_str($_POST["context"]);
		$profile = check_str($_POST["profile"]);
		$enabled = check_str($_POST["enabled"]);
		$description = check_str($_POST["description"]);
	}

if (count($_POST)>0 && strlen($_POST["persistformvar"]) == 0) {

	$msg = '';
	if ($action == "update") {
		$gateway_uuid = check_str($_POST["gateway_uuid"]);
	}

	//check for all required data
		//if (strlen($domain_uuid) == 0) { $msg .= "Please provide: domain_uuid<br>\n"; }
		if (strlen($gateway) == 0) { $msg .= "Please provide: Gateway<br>\n"; }
		if ($register == "true") {
			if (strlen($username) == 0) { $msg .= "Please provide: Username<br>\n"; }
			if (strlen($password) == 0) { $msg .= "Please provide: Password<br>\n"; }
		}
		//if (strlen($distinct_to) == 0) { $msg .= "Please provide: Distinct To<br>\n"; }
		//if (strlen($auth_username) == 0) { $msg .= "Please provide: Auth username<br>\n"; }
		//if (strlen($realm) == 0) { $msg .= "Please provide: Realm<br>\n"; }
		//if (strlen($from_user) == 0) { $msg .= "Please provide: From user<br>\n"; }
		//if (strlen($from_domain) == 0) { $msg .= "Please provide: From domain<br>\n"; }
		//if (strlen($proxy) == 0) { $msg .= "Please provide: Proxy<br>\n"; }
		if (strlen($expire_seconds) == 0) { $msg .= "Please provide: Expire seconds<br>\n"; }
		if (strlen($register) == 0) { $msg .= "Please provide: Register<br>\n"; }
		//if (strlen($register_transport) == 0) { $msg .= "Please provide: Register transport<br>\n"; }
		if (strlen($retry_seconds) == 0) { $msg .= "Please provide: Retry seconds<br>\n"; }
		//if (strlen($extension) == 0) { $msg .= "Please provide: Extension<br>\n"; }
		//if (strlen($ping) == 0) { $msg .= "Please provide: Ping<br>\n"; }
		//if (strlen($caller_id_in_from) == 0) { $msg .= "Please provide: Caller ID in from<br>\n"; }
		//if (strlen($supress_cng) == 0) { $msg .= "Please provide: Supress CNG<br>\n"; }
		//if (strlen($sip_cid_type) == 0) { $msg .= "Please provide: SIP CID Type<br>\n"; }
		//if (strlen($extension_in_contact) == 0) { $msg .= "Please provide: Extension in Contact<br>\n"; }
		if (strlen($context) == 0) { $msg .= "Please provide: Context<br>\n"; }
		//if (strlen($profile) == 0) { $msg .= "Please provide: Profile<br>\n"; }
		if (strlen($enabled) == 0) { $msg .= "Please provide: Enabled<br>\n"; }
		//if (strlen($description) == 0) { $msg .= "Please provide: Gateway Description<br>\n"; }
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

	//remove the invalid characters from the gateway name
		$gateway = str_replace(" ", "_", $gateway);
		$gateway = str_replace("/", "", $gateway);

	//add or update the database
		if ($_POST["persistformvar"] != "true") {
			if ($action == "add" && permission_exists('gateways_add')) {
				$gateway_uuid = uuid();
				$sql = "insert into v_gateways ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "gateway_uuid, ";
				$sql .= "gateway, ";
				$sql .= "username, ";
				$sql .= "password, ";
				$sql .= "distinct_to, ";
				$sql .= "auth_username, ";
				$sql .= "realm, ";
				$sql .= "from_user, ";
				$sql .= "from_domain, ";
				$sql .= "proxy, ";
				$sql .= "register_proxy, ";
				$sql .= "outbound_proxy, ";
				$sql .= "expire_seconds, ";
				$sql .= "register, ";
				$sql .= "register_transport, ";
				$sql .= "retry_seconds, ";
				$sql .= "extension, ";
				$sql .= "ping, ";
				$sql .= "caller_id_in_from, ";
				$sql .= "supress_cng, ";
				$sql .= "sip_cid_type, ";
				$sql .= "extension_in_contact, ";
				$sql .= "context, ";
				$sql .= "profile, ";
				$sql .= "enabled, ";
				$sql .= "description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$domain_uuid', ";
				$sql .= "'$gateway_uuid', ";
				$sql .= "'$gateway', ";
				$sql .= "'$username', ";
				$sql .= "'$password', ";
				$sql .= "'$distinct_to', ";
				$sql .= "'$auth_username', ";
				$sql .= "'$realm', ";
				$sql .= "'$from_user', ";
				$sql .= "'$from_domain', ";
				$sql .= "'$proxy', ";
				$sql .= "'$register_proxy', ";
				$sql .= "'$outbound_proxy', ";
				$sql .= "'$expire_seconds', ";
				$sql .= "'$register', ";
				$sql .= "'$register_transport', ";
				$sql .= "'$retry_seconds', ";
				$sql .= "'$extension', ";
				$sql .= "'$ping', ";
				$sql .= "'$caller_id_in_from', ";
				$sql .= "'$supress_cng', ";
				$sql .= "'$sip_cid_type', ";
				$sql .= "'$extension_in_contact', ";
				$sql .= "'$context', ";
				$sql .= "'$profile', ";
				$sql .= "'$enabled', ";
				$sql .= "'$description' ";
				$sql .= ")";
				$db->exec(check_sql($sql));
				unset($sql);

				//syncrhonize configuration
					save_gateway_xml();

			} //if ($action == "add")

			if ($action == "update" && permission_exists('gateways_edit')) {
				$sql = "update v_gateways set ";
				//$sql .= "domain_uuid = '$domain_uuid', ";
				$sql .= "gateway = '$gateway', ";
				$sql .= "username = '$username', ";
				$sql .= "password = '$password', ";
				$sql .= "distinct_to = '$distinct_to', ";
				$sql .= "auth_username = '$auth_username', ";
				$sql .= "realm = '$realm', ";
				$sql .= "from_user = '$from_user', ";
				$sql .= "from_domain = '$from_domain', ";
				$sql .= "proxy = '$proxy', ";
				$sql .= "register_proxy = '$register_proxy', ";
				$sql .= "outbound_proxy = '$outbound_proxy', ";
				$sql .= "expire_seconds = '$expire_seconds', ";
				$sql .= "register = '$register', ";
				$sql .= "register_transport = '$register_transport', ";
				$sql .= "retry_seconds = '$retry_seconds', ";
				$sql .= "extension = '$extension', ";
				$sql .= "ping = '$ping', ";
				$sql .= "caller_id_in_from = '$caller_id_in_from', ";
				$sql .= "supress_cng = '$supress_cng', ";
				$sql .= "sip_cid_type = '$sip_cid_type', ";
				$sql .= "extension_in_contact = '$extension_in_contact', ";
				$sql .= "context = '$context', ";
				$sql .= "profile = '$profile', ";
				$sql .= "enabled = '$enabled', ";
				$sql .= "description = '$description' ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and gateway_uuid = '$gateway_uuid'";
				$db->exec(check_sql($sql));
				unset($sql);

				//syncrhonize configuration
					save_gateway_xml();

				//synchronize the xml config
					save_dialplan_xml();

			} //if ($action == "update")

			//rescan the external profile to look for new or stopped gateways
				//create the event socket connection
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					$tmp_cmd = 'api sofia profile external rescan';
					$response = event_socket_request($fp, $tmp_cmd);
					unset($tmp_cmd);
					usleep(1000);
				//close the connection
					fclose($fp);
				//clear the apply settings reminder
					$_SESSION["reload_xml"] = false;
		} //if ($_POST["persistformvar"] != "true")

	//redirect the user
		if (isset($action)) {
			require_once "includes/header.php";
			echo "<meta http-equiv=\"refresh\" content=\"2;url=v_gateways.php\">\n";
			echo "<div align='center'>\n";
			if ($action == "add") {
				echo "Add Complete\n";
			}
			if ($action == "update") {
				echo "Edit Complete\n";
			}
			echo "</div>\n";
			require_once "includes/footer.php";
			return;
		}
} //(count($_POST)>0 && strlen($_POST["persistformvar"]) == 0)

//pre-populate the form
	if (count($_GET)>0 && $_POST["persistformvar"] != "true") {
		$gateway_uuid = $_GET["id"];
		$sql = "select * from v_gateways ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and gateway_uuid = '$gateway_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$domain_uuid = $row["domain_uuid"];
			$gateway = $row["gateway"];
			$username = $row["username"];
			$password = $row["password"];
			$distinct_to = $row["distinct_to"];
			$auth_username = $row["auth_username"];
			$realm = $row["realm"];
			$from_user = $row["from_user"];
			$from_domain = $row["from_domain"];
			$proxy = $row["proxy"];
			$register_proxy = $row["register_proxy"];
			$outbound_proxy = $row["outbound_proxy"];
			$expire_seconds = $row["expire_seconds"];
			$register = $row["register"];
			$register_transport = $row["register_transport"];
			$retry_seconds = $row["retry_seconds"];
			$extension = $row["extension"];
			$ping = $row["ping"];
			$caller_id_in_from = $row["caller_id_in_from"];
			$supress_cng = $row["supress_cng"];
			$sip_cid_type = $row["sip_cid_type"];
			$extension_in_contact = $row["extension_in_contact"];
			$context = $row["context"];
			$profile = $row["profile"];
			$enabled = $row["enabled"];
			$description = $row["description"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

//show the header
	require_once "includes/header.php";

//show the content
	echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
	echo "\n";
	echo "function enable_change(enable_over) {\n";
	echo "	var endis;\n";
	echo "	endis = !(document.iform.enable.checked || enable_over);\n";
	echo "	document.iform.range_from.disabled = endis;\n";
	echo "	document.iform.range_to.disabled = endis;\n";
	echo "}\n";
	echo "\n";
	echo "function show_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"block\";\n";
	echo "}\n";
	echo "\n";
	echo "function hide_advanced_config() {\n";
	echo "	document.getElementById(\"show_advanced_box\").innerHTML='';\n";
	echo "	aodiv = document.getElementById('show_advanced');\n";
	echo "	aodiv.style.display = \"block\";\n";
	echo "}\n";
	echo "</script>";

	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";

	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "      <br>";

	echo "<form method='post' name='ifrm' action=''>\n";
	echo "<div align='center'>\n";
	echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td colspan='2'>\n";

	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "		<td align='left' width=\"50%\">\n";
	echo "			<strong>Gateway Edit</strong><br>\n";
	echo "		</td>";
	echo "		<td width='50%' align='right'>\n";
	echo "			<input type='submit' name='submit' class='btn' value='Save'>\n";
	echo "			<input type='button' class='btn' name='' alt='copy' onclick=\"if (confirm('Do you really want to copy this?')){window.location='v_gateways_copy.php?id=".$gateway_uuid."';}\" value='Copy'>\n";
	echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='v_gateways.php'\" value='Back'>\n";
	echo "		</td>\n";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td align='left' colspan='2'>\n";
	echo "			Defines a connections to a SIP Provider or another SIP server. <br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />\n";

	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td width=\"30%\" class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Gateway:\n";
	echo "</td>\n";
	echo "<td width=\"70%\" class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='gateway' maxlength='255' value=\"$gateway\">\n";
	echo "<br />\n";
	echo "Enter the gateway name here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Username:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='username' maxlength='255' value=\"$username\">\n";
	echo "<br />\n";
	echo "Enter the username here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Password:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='password' name='password' id='password' maxlength='50' onfocus=\"document.getElementById('show_password').innerHTML = 'Password: '+document.getElementById('password').value;\" value=\"$password\">\n";
	echo "<br />\n";
	echo "<span onclick=\"document.getElementById('show_password').innerHTML = ''\">Enter the password here. </span><span id='show_password'></span>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    From user:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='from_user' maxlength='255' value=\"$from_user\">\n";
	echo "<br />\n";
	echo "Enter the from-user here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    From domain:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='from_domain' maxlength='255' value=\"$from_domain\">\n";
	echo "<br />\n";
	echo "Enter the from-domain here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Proxy:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='proxy' maxlength='255' value=\"$proxy\">\n";
	echo "<br />\n";
	echo "Enter the domain or IP address of the proxy.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Realm:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='realm' maxlength='255' value=\"$realm\">\n";
	echo "<br />\n";
	echo "Enter the realm here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Expire seconds:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (strlen($expire_seconds) == 0) { $expire_seconds = "800"; }
	echo "  <input class='formfld' type='text' name='expire_seconds' maxlength='255' value='$expire_seconds'>\n";
	echo "<br />\n";
	echo "Enter the expire-seconds here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Register:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='register'>\n";
	echo "    <option value=''></option>\n";
	if ($register == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($register == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Choose whether to register. \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Retry seconds:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (strlen($retry_seconds) == 0) { $retry_seconds = "60"; }
	echo "  <input class='formfld' type='text' name='retry_seconds' maxlength='255' value='$retry_seconds'>\n";
	echo "<br />\n";
	echo "Enter the retry-seconds here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	//--- begin: show_advanced -----------------------
	echo "<tr>\n";
	echo "<td style='padding: 0px;' colspan='2' class='' valign='top' align='left' nowrap>\n";

	echo "	<div id=\"show_advanced_box\">\n";
	echo "		<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";
	echo "		<tr>\n";
	echo "		<td width=\"30%\" valign=\"top\" class=\"vncell\">Show Advanced</td>\n";
	echo "		<td width=\"70%\" class=\"vtable\">\n";
	echo "			<input type=\"button\" onClick=\"show_advanced_config()\" value=\"Advanced\"></input></a>\n";
	echo "		</td>\n";
	echo "		</tr>\n";
	echo "		</table>\n";
	echo "	</div>\n";

	echo "	<div id=\"show_advanced\" style=\"display:none\">\n";
	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"6\" cellspacing=\"0\">\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Distinct To:\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='distinct_to'>\n";
	echo "    <option value=''></option>\n";
	if ($distinct_to == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($distinct_to == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Enter the distinct_to here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Auth username:\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='auth_username' maxlength='255' value=\"$auth_username\">\n";
	echo "<br />\n";
	echo "Enter the auth-username here.\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Extension:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='extension' maxlength='255' value=\"$extension\">\n";
	echo "<br />\n";
	echo "Enter the extension here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Register transport:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='register_transport'>\n";
	echo "    <option value=''></option>\n";
	if ($register_transport == "udp") { 
		echo "    <option value='udp' selected='selected'>udp</option>\n";
	}
	else {
		echo "    <option value='udp'>udp</option>\n";
	}
	if ($register_transport == "tcp") { 
		echo "    <option value='tcp' selected='selected'>tcp</option>\n";
	}
	else {
		echo "    <option value='tcp'>tcp</option>\n";
	}
	if ($register_transport == "tls") { 
		echo "    <option value='tls' selected='selected'>tls</option>\n";
	}
	else {
		echo "    <option value='tls'>tls</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Choose whether to register-transport. \n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Register Proxy:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='register_proxy' maxlength='255' value=\"$register_proxy\">\n";
	echo "<br />\n";
	echo "Enter the register proxy here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Outbound Proxy:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='outbound_proxy' maxlength='255' value=\"$outbound_proxy\">\n";
	echo "<br />\n";
	echo "Enter the outbound proxy here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	<tr>\n";
	echo "	<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "		Caller ID in from:\n";
	echo "	</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<select class='formfld' name='caller_id_in_from'>\n";
	echo "		<option value=''></option>\n";
	if ($caller_id_in_from == "true") { 
		echo "		<option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "		<option value='true'>true</option>\n";
	}
	if ($caller_id_in_from == "false") { 
		echo "		<option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "		<option value='false'>false</option>\n";
	}
	echo "	</select>\n";
	echo "<br />\n";
	echo "Enter the caller-id-in-from.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Supress CNG:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='supress_cng'>\n";
	echo "    <option value=''></option>\n";
	if ($supress_cng == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($supress_cng == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Enter the supress-cng.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    SIP CID Type:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='sip_cid_type' maxlength='255' value=\"$sip_cid_type\">\n";
	echo "<br />\n";
	echo "Enter the sip_cid_type: none, pid, and rpid.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Extension in Contact:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='extension_in_contact'>\n";
	echo "    <option value=''></option>\n";
	if ($extension_in_contact == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($extension_in_contact == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "Enter the extension_in_contact.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Ping:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='ping' maxlength='255' value=\"$ping\">\n";
	echo "<br />\n";
	echo "Enter the ping interval here in seconds.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "	</table>\n";
	echo "	</div>";

	echo "</td>\n";
	echo "</tr>\n";
	//--- end: show_advanced -----------------------

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Context:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (strlen($context) == 0) { $context = "public"; }
	echo "    <input class='formfld' type='text' name='context' maxlength='255' value=\"$context\">\n";
	echo "<br />\n";
	echo "Enter the context here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Profile:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	if (strlen($profile) == 0) { $profile = "external"; }
	echo "    <input class='formfld' type='text' name='profile' maxlength='255' value=\"$profile\">\n";
	echo "<br />\n";
	echo "Enter the profile here.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    Enabled:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='enabled'>\n";
	echo "    <option value=''></option>\n";
	if ($enabled == "true") { 
		echo "    <option value='true' selected='selected'>true</option>\n";
	}
	else {
		echo "    <option value='true'>true</option>\n";
	}
	if ($enabled == "false") { 
		echo "    <option value='false' selected='selected'>false</option>\n";
	}
	else {
		echo "    <option value='false'>false</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo "\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    Gateway Description:\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <input class='formfld' type='text' name='description' value='$description'>\n";
	echo "<br />\n";
	echo "Enter the description of the gateway here.\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "	<tr>\n";
	echo "		<td colspan='2' align='right'>\n";
	if ($action == "update") {
		echo "				<input type='hidden' name='gateway_uuid' value='$gateway_uuid'>\n";
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

//show footer
	require_once "includes/footer.php";
?>