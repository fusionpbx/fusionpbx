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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('sip_profile_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

if (count($_GET)>0) {
	$id = check_str($_GET["id"]);
}

if (strlen($id)>0) {
	//delete the sip profile settings
		$sql = "delete from v_sip_profile_settings ";
		$sql .= "where sip_profile_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

	//delete the sip profile
		$sql = "delete from v_sip_profiles ";
		$sql .= "where sip_profile_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($sql);

	//delete the xml sip profile and directory
		unlink($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile_name.".xml");
		unlink($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile_name);

	//save the sip profile xml
		save_sip_profile_xml();

	//apply settings reminder
		$_SESSION["reload_xml"] = true;

	//delete the sip profiles from memcache
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if ($fp) {
			$switch_cmd = "memcache delete configuration:sofia.conf";
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		}
}

//redirect the browser
	$_SESSION["message"] = $text['message-delete'];
	header("Location: sip_profiles.php");
	return;

?>