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

//if the recordings directory doesn't exist then create it
	if (strlen($_SESSION['switch']['recordings']['dir']) > 0) {
		if (!is_readable($_SESSION['switch']['recordings']['dir'])) { mkdir($_SESSION['switch']['recordings']['dir'],0777,true); }
	}

if ($domains_processed == 1) {

	//if base64, populate from existing recording files, then remove
		if ($_SESSION['recordings']['storage_type']['text'] == 'base64') {
			//get recordings without base64 in db
				$sql = "select recording_uuid, domain_uuid, recording_filename ";
				$sql .= "from v_recordings where recording_base64 is null or recording_base64 = '' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					foreach ($result as &$row) {
						$recording_uuid = $row['recording_uuid'];
						$recording_domain_uuid = $row['domain_uuid'];
						$recording_filename = $row['recording_filename'];
						//set recording directory
							$recording_directory = $_SESSION['switch']['recordings']['dir'];
						//encode recording file (if exists)
							if (file_exists($recording_directory.'/'.$recording_filename)) {
								$recording_base64 = base64_encode(file_get_contents($recording_directory.'/'.$recording_filename));
								//update recording record with base64
									$sql = "update v_recordings set ";
									$sql .= "recording_base64 = '".$recording_base64."' ";
									$sql .= "where domain_uuid = '".$recording_domain_uuid."' ";
									$sql .= "and recording_uuid = '".$recording_uuid."' ";
									$db->exec(check_sql($sql));
									unset($sql);
								//remove local recording file
									@unlink($recording_directory.'/'.$recording_filename);
							}
					}
				}
				unset($sql, $prep_statement, $result, $row);
		}
	//if not base64, decode to local files, remove base64 data from db
		else if ($_SESSION['recordings']['storage_type']['text'] != 'base64') {
			//get recordings with base64 in db
				$sql = "select recording_uuid, domain_uuid, recording_filename, recording_base64 ";
				$sql .= "from v_recordings where recording_base64 is not null ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					foreach ($result as &$row) {
						$recording_uuid = $row['recording_uuid'];
						$recording_domain_uuid = $row['domain_uuid'];
						$recording_filename = $row['recording_filename'];
						$recording_base64 = $row['recording_base64'];
						//set recording directory
							$recording_directory = $_SESSION['switch']['recordings']['dir'];
						//remove local file, if any
							if (file_exists($recording_directory.'/'.$recording_filename)) {
								@unlink($recording_directory.'/'.$recording_filename);
							}
						//decode base64, save to local file
							$recording_decoded = base64_decode($recording_base64);
							file_put_contents($recording_directory.'/'.$recording_filename, $recording_decoded);
							$sql = "update v_recordings ";
							$sql .= "set recording_base64 = null ";
							$sql .= "where domain_uuid = '".$recording_domain_uuid."' ";
							$sql .= "and recording_uuid = '".$recording_uuid."' ";
							$db->exec(check_sql($sql));
							unset($sql);
					}
				}
				unset($sql, $prep_statement, $result, $row);
		}

}

?>