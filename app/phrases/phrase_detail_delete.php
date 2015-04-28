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
require_once "resources/functions/save_phrases_xml.php";

if (permission_exists('phrase_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get values
	$phrase_detail_uuid = check_str($_GET["pdid"]);
	$phrase_uuid = check_str($_GET["pid"]);
	$phrase_language = check_str($_GET["lang"]);

//delete the detail entry
	if ($phrase_detail_uuid != '' && $phrase_uuid != '') {
		$sql = "delete from v_phrase_details ";
		$sql .= " where phrase_detail_uuid = '".$phrase_detail_uuid."'";
		$sql .= " and phrase_uuid = '".$phrase_uuid."' ";
		$sql .= " and domain_uuid = '".$domain_uuid."' ";
		$db->exec(check_sql($sql));
		unset($sql);
	}

//save the xml to the file system if the phrase directory is set
	save_phrases_xml();

//delete the phrase from memcache
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		$switch_cmd .= "memcache delete languages:".$phrase_language;
		$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
	}

//redirect the user
	$_SESSION['message'] = $text['message-delete'];
	header('Location: phrase_edit.php?id='.$phrase_uuid);

?>