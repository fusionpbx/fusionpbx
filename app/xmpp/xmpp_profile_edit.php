<?php
/* $Id$ */
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
	Ken Rice <krice@tollfreegateway.com>
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists('xmpp_add') || permission_exists('xmpp_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//add or update the database
if (isset($_REQUEST["id"])) {
	$action = "update";
	$profile_id = check_str($_REQUEST["id"]);
} else {
	$action = "add";
}

if ($action == "update") {
	$document['title'] = $text['title-xmpp-edit'];
}
else if ($action == "add") {
	$document['title'] = $text['title-xmpp-add'];
}

$domain_name = $_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name'];

if ($action == "update") {
	$sql = "";
	$sql .= "select * from v_xmpp ";
	$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and xmpp_profile_uuid = '$profile_id' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();

	$x = 0;
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$profiles_array[$x] = $row;
		$x++;
	}

	$profile = $profiles_array[0];
	unset ($prep_statement);
	$profile['profile_username'] = $profile['username'];
	$profile['profile_password'] = $profile['password'];
} else {
 	$profile['dialplan'] = "XML";
	$profile['context'] = $_SESSION['domain_name'];
	$profile['rtp_ip'] = '$${local_ip_v4}';
	$profile['ext_rtp_ip'] = '$${external_rtp_ip}';
 	$profile['auto_login'] = "true";
 	$profile['sasl_type'] = "md5";
 	$profile['tls_enable'] = "true";
 	$profile['use_rtp_timer'] = "true";
 	$profile['vad'] = "none";
	$profile['candidate_acl'] = "wan.auto";
 	$profile['local_network_acl'] = "localnet.auto";
}

if ((!isset($_REQUEST['submit'])) || ($_REQUEST['submit'] != $text['button-save'])) {
	// If we arent saving a Profile Display the form.
	require_once "resources/header.php";
	include "profile_edit.php";
	require_once "resources/footer.php";
	exit;
}

foreach ($_REQUEST as $field => $data){
	$request[$field] = check_str($data);
}


// check the data
$error = "";
if (strlen($request['profile_name']) < 1) $error .= $text['message-required'].$text['label-profile_name']."<br />\n";
if (strlen($request['profile_username']) < 1) $error .= $text['message-required'].$text['label-username']."<br />\n";
if (strlen($request['profile_password']) < 1) $error .= $text['message-required'].$text['label-password']."<br />\n";
if (strlen($request['default_exten']) < 1) $error .= $text['message-required'].$text['label-default_exten']."<br />\n";
if (strlen($error) > 0) {
	include "errors.php";
	$profile = $request;
	require_once "resources/header.php";
	include "profile_edit.php";
	require_once "resources/footer.php";
	exit;
}

// Save New Entry
if ($action == "add" && permission_exists('xmpp_add')) {
	$xmpp_profile_uuid = uuid();
	$sql = "";
	$sql .= "insert into v_xmpp (";
 	$sql .= "domain_uuid, ";
	$sql .= "xmpp_profile_uuid, ";
 	$sql .= "profile_name, ";
 	$sql .= "username, ";
 	$sql .= "password, ";
 	$sql .= "dialplan, ";
 	$sql .= "context, ";
 	$sql .= "rtp_ip, ";
 	$sql .= "ext_rtp_ip, ";
 	$sql .= "auto_login, ";
 	$sql .= "sasl_type, ";
 	$sql .= "xmpp_server, ";
 	$sql .= "tls_enable, ";
 	$sql .= "use_rtp_timer, ";
 	$sql .= "default_exten, ";
 	$sql .= "vad, ";
 	$sql .= "avatar, ";
 	$sql .= "candidate_acl, ";
 	$sql .= "local_network_acl, ";
	$sql .= "description, ";
	$sql .= "enabled ";
	$sql .= ") values (";
	$sql .= "'" . $_SESSION['domain_uuid'] . "', ";
	$sql .= "'" . $xmpp_profile_uuid . "', ";
	$sql .= "'" . $request['profile_name'] . "', ";
 	$sql .= "'" . $request['profile_username'] . "', ";
 	$sql .= "'" . $request['profile_password'] . "', ";
 	$sql .= "'" . $request['dialplan'] . "', ";
	if (if_group("superadmin") && $request['context']) {
		$sql .= "'" . $request['context'] . "', ";
	}
	else {
		$sql .= "'" . $_SESSION['context'] . "', ";
	}
 	$sql .= "'" . $request['rtp_ip'] . "', ";
 	$sql .= "'" . $request['ext_rtp_ip'] . "', ";
 	$sql .= "'" . $request['auto_login'] . "', ";
 	$sql .= "'" . $request['sasl_type'] . "', ";
 	$sql .= "'" . $request['xmpp_server'] . "', ";
 	$sql .= "'" . $request['tls_enable'] . "', ";
 	$sql .= "'" . $request['use_rtp_timer'] . "', ";
 	$sql .= "'" . $request['default_exten'] . "', ";
 	$sql .= "'" . $request['vad'] . "', ";
 	$sql .= "'" . $request['avatar'] . "', ";
 	$sql .= "'" . $request['candidate_acl'] . "', ";
 	$sql .= "'" . $request['local_network_acl'] . "', ";
	$sql .= "'" . $request['description'] . "', ";
	$sql .= "'" . $request['enabled'] . "' ";
	$sql .= ") ";
	$db->exec(check_sql($sql));

}
elseif ($action == "update" && permission_exists('xmpp_edit')) {
	$sql = "";
	$sql .= "UPDATE v_xmpp SET ";
	$sql .= "profile_name = '" . $request['profile_name'] . "', ";
	$sql .= "username = '" . $request['profile_username'] . "', ";
	$sql .= "password = '" . $request['profile_password'] . "', ";
	$sql .= "dialplan = '" . $request['dialplan'] . "', ";
	if (if_group("superadmin") && $request['context']) {
		$sql .= "context = '" . $request['context'] . "', ";
	}
	else {
		$sql .= "context = '" . $_SESSION["context"] . "', ";
	}
	$sql .= "rtp_ip = '" . $request['rtp_ip'] . "', ";
	$sql .= "ext_rtp_ip = '" . $request['ext_rtp_ip'] . "', ";
	$sql .= "auto_login = '" . $request['auto_login'] . "', ";
	$sql .= "sasl_type = '" . $request['sasl_type'] . "', ";
	$sql .= "xmpp_server = '" . $request['xmpp_server'] . "', ";
	$sql .= "tls_enable = '" . $request['tls_enable'] . "', ";
	$sql .= "use_rtp_timer = '" . $request['use_rtp_timer'] . "', ";
	$sql .= "default_exten = '" . $request['default_exten'] . "', ";
	$sql .= "vad = '" . $request['vad'] . "', ";
	$sql .= "avatar = '" . $request['avatar'] . "', ";
	$sql .= "candidate_acl = '" . $request['candidate_acl'] . "', ";
	$sql .= "local_network_acl = '" . $request['local_network_acl'] . "', ";
	$sql .= "description = '" . $request['description'] . "', ";
	$sql .= "enabled = '" . $request['enabled'] . "' ";
	$sql .= "where xmpp_profile_uuid = '" . $request['id'] . "' ";
	$db->exec(check_sql($sql));
	$xmpp_profile_uuid = $request['id'];
}

if ($request['enabled'] == "true") {
	//prepare the xml
	include "client_template.php";
	$xml = make_xmpp_xml($request);

	//write the xml
	$filename = $_SESSION['switch']['conf']['dir'] . "/jingle_profiles/" . "v_" . $_SESSION['domain_name'] . "_" . preg_replace("/[^A-Za-z0-9]/", "", $request['profile_name']) . "_" . $xmpp_profile_uuid . ".xml";
	$fh = fopen($filename,"w") or die("Unable to open the file");
	fwrite($fh, $xml);
	unset($file_name);
	fclose($fh);
}

$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if ($fp) {
	//reload the XML Configs
	$tmp_cmd = 'api reloadxml';
	$response = event_socket_request($fp, $tmp_cmd);
	unset($tmp_cmd);

	//Tell mod_dingaling to reload is config
	$tmp_cmd = 'api dingaling reload';
	$response = event_socket_request($fp, $tmp_cmd);
	unset($tmp_cmd);

	//close the connection
	fclose($fp);
}

include "update_complete.php";

?>
