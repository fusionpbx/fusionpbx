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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('extension_delete')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	if (count($_GET) > 0) {
		$id = check_str($_GET["id"]);
	}

//delete the hot desking information
	if (strlen($id) > 0) {
		$sql = "update v_extensions set ";
		$sql .= "unique_id = null, ";
		$sql .= "dial_user = null, ";
		$sql .= "dial_domain = null, ";
		$sql .= "dial_string = null ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and extension_uuid = '$id' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		unset($prep_statement, $sql);
	}

//get the extension
	$sql = "select extension from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and extension_uuid = '$id' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$extension = $row["extension"];
	}
	unset ($prep_statement);

//clear the cache
	$cache = new cache;
	$cache->delete("directory:".$extension."@".$_SESSION['domain_name']);

//redirect the user
	$_SESSION["message"] = $text['message-delete'];
	header("Location: index.php");
	return;

?>