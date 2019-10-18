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

//get posted values
	$xml_cdr_uuids = $_REQUEST["id"];
	if (is_array($xml_cdr_uuids) && @sizeof($xml_cdr_uuids) != 0) {
		$records_deleted = 0;
		foreach ($xml_cdr_uuids as $x => $xml_cdr_uuid) {
			if (is_uuid($xml_cdr_uuid)) {
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

				//build cdr delete array
					$array['xml_cdr'][$x]['xml_cdr_uuid'] = $xml_cdr_uuid;
				//build call recording delete array
					$array['call_recordings'][$x]['call_recording_uuid'] = $xml_cdr_uuid;
				//increment counter
					$records_deleted++;
			}
		}
		if (is_array($array) && @sizeof($array) != 0) {
			//grant temporary permissions
				$p = new permissions;
				$p->add('xml_cdr_delete', 'temp');
				$p->add('call_recording_delete', 'temp');
			//execute delete
				$database = new database;
				$database->app_name = 'xml_cdr';
				$database->app_uuid = '4a085c51-7635-ff03-f67b-86e834422848';
				$database->delete($array);
				unset($array);
			//revoke temporary permissions
				$p->delete('xml_cdr_delete', 'temp');
				$p->delete('call_recording_delete', 'temp');
			//set message
				$_SESSION["message"] = $text['message-delete'].": ".$records_deleted;
		}
	}

//redirect
	header("Location: xml_cdr.php");

?>