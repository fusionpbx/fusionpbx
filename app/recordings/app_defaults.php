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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//if the recordings directory doesn't exist then create it
	if (is_array($_SESSION['switch']['recordings']) && strlen($_SESSION['switch']['recordings']['dir']."/".$domain_name) > 0) {
		if (!is_readable($_SESSION['switch']['recordings']['dir']."/".$domain_name)) { event_socket_mkdir($_SESSION['switch']['recordings']['dir']."/".$domain_name,02770,true); }
	}

//process one time
	if ($domains_processed == 1) {

		//if base64, populate from existing recording files, then remove
			if (is_array($_SESSION['recordings']['storage_type']) && $_SESSION['recordings']['storage_type']['text'] == 'base64') {
				//get recordings without base64 in db
					$sql = "select recording_uuid, domain_uuid, recording_filename ";
					$sql .= "from v_recordings ";
					$sql .= "where recording_base64 is null ";
					$sql .= "or recording_base64 = '' ";
					$database = new database;
					$result = $database->select($sql, null, 'all');
					if (is_array($result) && @sizeof($result) != 0) {
						foreach ($result as &$row) {
							$recording_uuid = $row['recording_uuid'];
							$recording_domain_uuid = $row['domain_uuid'];
							$recording_filename = $row['recording_filename'];
							//set recording directory
								$recording_directory = $_SESSION['switch']['recordings']['dir'].'/'.$domain_name;
							//encode recording file (if exists)
								if (file_exists($recording_directory.'/'.$recording_filename)) {
									//build array
										$recording_base64 = base64_encode(file_get_contents($recording_directory.'/'.$recording_filename));
										$array['recordings'][0]['recording_uuid'] = $recording_uuid;
										$array['recordings'][0]['domain_uuid'] = $recording_domain_uuid;
										$array['recordings'][0]['recording_base64'] = $recording_base64;
									//grant temporary permissions
										$p = new permissions;
										$p->add('recording_edit', 'temp');
									//update recording record with base64
										$database = new database;
										$database->app_name = 'recordings';
										$database->app_uuid = '83913217-c7a2-9e90-925d-a866eb40b60e';
										$database->save($array);
										unset($array);
									//revoke temporary permissions
										$p->delete('recording_edit', 'temp');
									//remove local recording file
										@unlink($recording_directory.'/'.$recording_filename);
								}
						}
					}
					unset($sql, $result, $row);
			}
		//if not base64, decode to local files, remove base64 data from db
			else if (is_array($_SESSION['recordings']['storage_type']) && $_SESSION['recordings']['storage_type']['text'] != 'base64') {
				//get recordings with base64 in db
					$sql = "select recording_uuid, domain_uuid, recording_filename, recording_base64 ";
					$sql .= "from v_recordings ";
					$sql .= "where recording_base64 is not null ";
					$database = new database;
					$result = $database->select($sql, null, 'all');
					if (is_array($result) && @sizeof($result) != 0) {
						foreach ($result as &$row) {
							$recording_uuid = $row['recording_uuid'];
							$recording_domain_uuid = $row['domain_uuid'];
							$recording_filename = $row['recording_filename'];
							$recording_base64 = $row['recording_base64'];
							//set recording directory
								$recording_directory = $_SESSION['switch']['recordings']['dir'].'/'.$domain_name;
							//remove local file, if any
								if (file_exists($recording_directory.'/'.$recording_filename)) {
									@unlink($recording_directory.'/'.$recording_filename);
								}
							//decode base64, save to local file
								$recording_decoded = base64_decode($recording_base64);
								file_put_contents($recording_directory.'/'.$recording_filename, $recording_decoded);
							//build array
								$array['recordings'][0]['recording_uuid'] = $recording_uuid;
								$array['recordings'][0]['domain_uuid'] = $recording_domain_uuid;
								$array['recordings'][0]['recording_base64'] = null;
							//grant temporary permissions
								$p = new permissions;
								$p->add('recording_edit', 'temp');
							//update recording record
								$database = new database;
								$database->app_name = 'recordings';
								$database->app_uuid = '83913217-c7a2-9e90-925d-a866eb40b60e';
								$database->save($array);
								unset($array);
							//revoke temporary permissions
								$p->delete('recording_edit', 'temp');
						}
					}
					unset($sql, $result, $row);
			}
	}

?>
