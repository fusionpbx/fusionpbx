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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/functions/save_phrases_xml.php";

//check permissions
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
	$phrase_uuid = $_GET["id"];

//delete the data
	if (is_uuid($phrase_uuid)) {
		//delete phrase details
			$sql = "delete from v_phrase_details ";
			$sql .= "where phrase_uuid = '".$phrase_uuid."' ";
			$sql .= "and domain_uuid = '".$domain_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);

		//delete phrase
			$sql = "delete from v_phrases ";
			$sql .= "where phrase_uuid = '".$phrase_uuid."' ";
			$sql .= "and domain_uuid = '".$domain_uuid."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			unset ($prep_statement);
	}

//save the xml
	save_phrases_xml();

//clear the cache
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	$cache = new cache;
	$cache->delete("languages:".$phrase_language);

//redirect the user
	message::add($text['message-delete']);
	header("Location: phrases.php");

?>
