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
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('conference_center_delete')) {
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
	if (isset($_GET["id"]) &&  is_uuid($_GET["id"])) {
		$id = $_GET["id"];
	}

//get the domain_uuid
	$domain_uuid = null;
	if (isset($_SESSION['domain_uuid']) &&  is_uuid($_SESSION['domain_uuid'])) {
		$domain_uuid = $_SESSION['domain_uuid'];
	}

//delete the data
	if (isset($id) && is_uuid($id)) {
		//get the dialplan uuid
			$sql = "select dialplan_uuid from v_conference_centers ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and conference_center_uuid = :conference_center_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['conference_center_uuid'] = $id;
			$database = new database;
			$dialplan_uuid = $database->select($sql, $parameters, 'column');
			unset ($parameters);

		//delete the conference center
			$sql = "delete from v_conference_centers ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and conference_center_uuid = '$id' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			unset($sql);

		//delete the dialplan entry
			$sql = "delete from v_dialplans ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
			$db->query($sql);
			unset($sql);

		//delete the dialplan details
			$sql = "delete from v_dialplan_details ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
			$db->query($sql);
			unset($sql);

		//clear the cache
			$cache = new cache;
			$cache->delete("dialplan:".$_SESSION["context"]);

		//syncrhonize configuration
			save_dialplan_xml();

		//apply settings reminder
			$_SESSION["reload_xml"] = true;
	}

//redirect the browser
	message::add($text['message-delete']);
	header("Location: conference_centers.php");
	return;

?>
