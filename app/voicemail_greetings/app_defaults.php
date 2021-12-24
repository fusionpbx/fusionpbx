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
		$database = new database;
		$database->execute($sql);
		unset($sql);

	//populate greeting id number if empty
		$sql = "select voicemail_greeting_uuid, greeting_filename ";
		$sql .= "from v_voicemail_greetings ";
		$sql .= "where greeting_id is null ";
		$database = new database;
		$result = $database->select($sql, null, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $x => &$row) {
				$voicemail_greeting_uuid = $row['voicemail_greeting_uuid'];
				$greeting_id = preg_replace('{\D}', '', $row['greeting_filename']);
				//build update array
				$array['voicemail_greetings'][$x]['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
				$array['voicemail_greetings'][$x]['greeting_id'] = $greeting_id;
				unset($voicemail_greeting_uuid, $greeting_id);
			}
			if (is_array($array) && @sizeof($array) != 0) {
				//grant temporary permissions
					$p = new permissions;
					$p->add('voicemail_greeting_edit', 'temp');
				//execute update
					$database = new database;
					$database->app_name = 'voicemail_greetings';
					$database->app_uuid = 'e4b4fbee-9e4d-8e46-3810-91ba663db0c2';
					$database->save($array, false);
					unset($array);
				//revoke temporary permissions
					$p->delete('voicemail_greeting_edit', 'temp');
			}
		}
		unset($sql, $result, $x, $row);

	//if base64, populate from existing greeting files, then remove
		if ($_SESSION['voicemail']['storage_type']['text'] == 'base64') {
			//get greetings without base64 in db
				$sql = "select voicemail_greeting_uuid, domain_uuid, voicemail_id, greeting_filename ";
				$sql .= "from v_voicemail_greetings ";
				$sql .= "where greeting_base64 is null ";
				$sql .= "or greeting_base64 = '' ";
				$database = new database;
				$result = $database->select($sql, null, 'all');
				if (is_array($result) && @sizeof($result) != 0) {
					foreach ($result as $x => &$row) {
						$voicemail_greeting_uuid = $row['voicemail_greeting_uuid'];
						$greeting_domain_uuid = $row['domain_uuid'];
						$voicemail_id = $row['voicemail_id'];
						$greeting_filename = $row['greeting_filename'];
						//set greeting directory
							$greeting_directory = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$greeting_domain_uuid]['domain_name'].'/'.$voicemail_id;
						//encode greeting file (if exists)
							if (file_exists($greeting_directory.'/'.$greeting_filename)) {
								//build update array
									$array['voicemail_greetings'][$x]['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
									$array['voicemail_greetings'][$x]['greeting_base64'] = base64_encode(file_get_contents($greeting_directory.'/'.$greeting_filename));
								//remove local greeting file
									@unlink($greeting_directory.'/'.$greeting_filename);
							}
					}
					if (is_array($array) && @sizeof($array) != 0) {
						//grant temporary permissions
							$p = new permissions;
							$p->add('voicemail_greeting_edit', 'temp');
						//execute update
							$database = new database;
							$database->app_name = 'voicemail_greetings';
							$database->app_uuid = 'e4b4fbee-9e4d-8e46-3810-91ba663db0c2';
							$database->save($array, false);
							unset($array);
						//revoke temporary permissions
							$p->delete('voicemail_greeting_edit', 'temp');
					}
				}
				unset($sql, $result, $row);
		}

	//if not base64, decode to local files, remove base64 data from db
		else if ($_SESSION['voicemail']['storage_type']['text'] != 'base64') {
			//get greetings with base64 in db
				$sql = "select voicemail_greeting_uuid, domain_uuid, voicemail_id, greeting_filename, greeting_base64 ";
				$sql .= "from v_voicemail_greetings ";
				$sql .= "where greeting_base64 is not null ";
				$database = new database;
				$result = $database->select($sql, null, 'all');
				if (is_array($result) && @sizeof($result) != 0) {
					foreach ($result as $x => &$row) {
						$voicemail_greeting_uuid = $row['voicemail_greeting_uuid'];
						$greeting_domain_uuid = $row['domain_uuid'];
						$voicemail_id = $row['voicemail_id'];
						$greeting_filename = $row['greeting_filename'];
						$greeting_base64 = $row['greeting_base64'];
						//set greeting directory
							$greeting_directory = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$greeting_domain_uuid]['domain_name'].'/'.$voicemail_id;
						//remove local file, if any
							@unlink($greeting_directory.'/'.$greeting_filename);
						//build update array
							$array['voicemail_greetings'][$x]['voicemail_greeting_uuid'] = $voicemail_greeting_uuid;
							$array['voicemail_greetings'][$x]['greeting_base64'] = null;
						//decode base64, save to local file
							file_put_contents($greeting_directory.'/'.$greeting_filename, base64_decode($greeting_base64));
					}
					if (is_array($array) && @sizeof($array) != 0) {
						//grant temporary permissions
							$p = new permissions;
							$p->add('voicemail_greeting_edit', 'temp');
						//execute update
							$database = new database;
							$database->app_name = 'voicemail_greetings';
							$database->app_uuid = 'e4b4fbee-9e4d-8e46-3810-91ba663db0c2';
							$database->save($array, false);
							unset($array);
						//revoke temporary permissions
							$p->delete('voicemail_greeting_edit', 'temp');
					}
				}
				unset($sql, $result, $row);
		}
}

?>
