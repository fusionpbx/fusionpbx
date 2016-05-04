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

if ($domains_processed == 1) {
	//if greeting filename field empty, copy greeting name field value
		$sql = "update v_voicemail_greetings ";
		$sql .= "set greeting_filename = greeting_name ";
		$sql .= "where greeting_filename is null ";
		$sql .= "or greeting_filename = '' ";
		$db->exec(check_sql($sql));
		unset($sql);

	//populate greeting id number if empty
		$sql = "select voicemail_greeting_uuid, greeting_filename ";
		$sql .= "from v_voicemail_greetings ";
		$sql .= "where greeting_id is null ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$voicemail_greeting_uuid = $row['voicemail_greeting_uuid'];
			$greeting_id = preg_replace('{\D}', '', $row['greeting_filename']);
			$sqlu = "update v_voicemail_greetings ";
			$sqlu .= "set greeting_id = ".$greeting_id." ";
			$sqlu .= "where voicemail_greeting_uuid = '".$voicemail_greeting_uuid."' ";
			$db->exec(check_sql($sqlu));
			unset($sqlu, $voicemail_greeting_uuid, $greeting_id);
		}
		unset ($sql, $prep_statement);

	//if base64, populate from existing greeting files, then remove
		if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
			//get greetings without base64 in db
				$sql = "select voicemail_greeting_uuid, domain_uuid, voicemail_id, greeting_filename ";
				$sql .= "from v_voicemail_greetings where greeting_base64 is null or greeting_base64 = '' ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					foreach ($result as &$row) {
						$voicemail_greeting_uuid = $row['voicemail_greeting_uuid'];
						$greeting_domain_uuid = $row['domain_uuid'];
						$voicemail_id = $row['voicemail_id'];
						$greeting_filename = $row['greeting_filename'];
						//set greeting directory
							$greeting_directory = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$greeting_domain_uuid]['domain_name'].'/'.$voicemail_id;
						//encode greeting file (if exists)
							if (file_exists($greeting_directory.'/'.$greeting_filename)) {
								$greeting_base64 = base64_encode(file_get_contents($greeting_directory.'/'.$greeting_filename));
								//update greeting record with base64
									$sql = "update v_voicemail_greetings set ";
									$sql .= "greeting_base64 = '".$greeting_base64."' ";
									$sql .= "where domain_uuid = '".$greeting_domain_uuid."' ";
									$sql .= "and voicemail_greeting_uuid = '".$voicemail_greeting_uuid."' ";
									$db->exec(check_sql($sql));
									unset($sql);
								//remove local greeting file
									@unlink($greeting_directory.'/'.$greeting_filename);
							}
					}
				}
				unset($sql, $prep_statement, $result, $row);
		}
	//if not base64, decode to local files, remove base64 data from db
		else if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
			//get greetings with base64 in db
				$sql = "select voicemail_greeting_uuid, domain_uuid, voicemail_id, greeting_filename, greeting_base64 ";
				$sql .= "from v_voicemail_greetings where greeting_base64 is not null ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					foreach ($result as &$row) {
						$voicemail_greeting_uuid = $row['voicemail_greeting_uuid'];
						$greeting_domain_uuid = $row['domain_uuid'];
						$voicemail_id = $row['voicemail_id'];
						$greeting_filename = $row['greeting_filename'];
						$greeting_base64 = $row['greeting_base64'];
						//set greeting directory
							$greeting_directory = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$greeting_domain_uuid]['domain_name'].'/'.$voicemail_id;
						//remove local file, if any
							if (file_exists($greeting_directory.'/'.$greeting_filename)) {
								@unlink($greeting_directory.'/'.$greeting_filename);
							}
						//decode base64, save to local file
							$greeting_decoded = base64_decode($greeting_base64);
							file_put_contents($greeting_directory.'/'.$greeting_filename, $greeting_decoded);
							$sql = "update v_voicemail_greetings ";
							$sql .= "set greeting_base64 = null ";
							$sql .= "where domain_uuid = '".$greeting_domain_uuid."' ";
							$sql .= "and voicemail_greeting_uuid = '".$voicemail_greeting_uuid."' ";
							$db->exec(check_sql($sql));
							unset($sql);
					}
				}
				unset($sql, $prep_statement, $result, $row);
		}
}

?>