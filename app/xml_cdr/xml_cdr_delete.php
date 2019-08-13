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
	Copyright (C) 2008-2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permisions
	if (permission_exists('xml_cdr_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get posted values, if any
	if (sizeof($_REQUEST) > 0) {
		$xml_cdr_uuids = $_REQUEST["id"];
		if (sizeof($xml_cdr_uuids) > 0) {
			foreach ($xml_cdr_uuids as $index => $xml_cdr_uuid) {
				if (is_uuid($xml_cdr_uuid)) {
					// delete record from v_xml_cdr
					$sql = "delete from v_xml_cdr ";
					$sql .= "where xml_cdr_uuid = :xml_cdr_uuid ";
					$parameters['xml_cdr_uuid'] = $xml_cdr_uuid;
					$database = new database;
					$database->execute($sql, $parameters);
					unset($sql, $parameters);

					//get the call recordings
					$sql = "select * from v_call_recordings ";
					$sql .= "where call_recording_uuid = :xml_cdr_uuid ";
					$parameters['xml_cdr_uuid'] = $xml_cdr_uuid;
					$database = new database;
					$row = $database->select($sql, $parameters, 'row');
					unset($sql, $parameters);

					//delete the call recording
					$call_recording_path = realpath($row['call_recording_path']);
					$call_recording_name = $row['call_recording_name'];
					if (file_exists($call_recording_path.'/'.$call_recording_name)) {
						@unlink($call_recording_path.'/'.$call_recording_name);
					}

					//delete the call recording meta data
					$sql = "delete from v_call_recordings ";
					$sql .= "where call_recording_uuid = :xml_cdr_uuid ";
					$parameters['xml_cdr_uuid'] = $xml_cdr_uuid;
					$database = new database;
					$database->execute($sql, $parameters);
					unset($sql, $parameters);

				}
			}
		}
	}

//set message and redirect the user
	$_SESSION["message"] = $text['message-delete'].": ".sizeof($xml_cdr_uuids);
	header("Location: xml_cdr.php");

?>
