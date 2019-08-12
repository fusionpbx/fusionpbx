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
if (permission_exists('recording_delete')) {
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
	$recording_uuid = $_GET["id"];

if (is_uuid($recording_uuid)) {
	//get filename
		$sql = "select recording_filename from v_recordings ";
		$sql .= "where recording_uuid = :recording_uuid ";
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['recording_uuid'] = $recording_uuid;
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$filename = $database->select($sql, $parameters, 'column');
		unset($prep_statement);

	//build array
		$array['recordings'][0]['recording_uuid'] = $recording_uuid;
		$array['recordings'][0]['domain_uuid'] = $domain_uuid;

	//delete recording from the database
		$database = new database;
		$database->app_name = 'recordings';
		$database->app_uuid = '83913217-c7a2-9e90-925d-a866eb40b60e';
		$database->delete($array);
		unset($array);

	//delete the recording
		if (file_exists($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$filename)) {
			@unlink($_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/".$filename);
		}

	//set message
		message::add($text['message-delete']);
}

//redirect the user
	header("Location: recordings.php");
	exit;

?>