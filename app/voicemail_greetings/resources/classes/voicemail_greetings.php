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

//define the voicemail greetings class
if (!class_exists('voicemail_greetings')) {
	class voicemail_greetings {

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;

		/**
		 * declare public variables
		 */
		public $voicemail_id;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'voicemail_greetings';
				$this->app_uuid = 'e4b4fbee-9e4d-8e46-3810-91ba663db0c2';
				$this->permission_prefix = 'voicemail_greeting_';
				if (is_numeric($this->voicemail_id)) {
					$this->list_page = 'voicemail_greetings.php?id='.urlencode($this->voicemail_id).'&back='.urlencode(PROJECT_PATH.'/app/voicemail/voicemails.php');
				}
				else {
					$this->list_page = PROJECT_PATH.'/app/voicemails/voicemails.php';
				}
				$this->table = 'voicemail_greetings';
				$this->uuid_prefix = 'voicemail_greeting_';

		}

		/**
		 * called when there are no references to a particular object
		 * unset the variables used in the class
		 */
		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		/**
		 * delete records
		 */
		public function delete($records) {
			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->list_page);
						exit;
					}

				//check voicemail id
					if (!is_numeric($this->voicemail_id)) {
						header('Location: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked records
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary greeting details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, greeting_filename, greeting_id from v_".$this->table." ";
								$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$greeting_filenames[$row['uuid']] = $row['greeting_filename'];
										$greeting_ids[$this->voicemail_id] = $row['greeting_id'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//set the greeting directory
							$greeting_directory = $_SESSION['switch']['storage']['dir'].'/voicemail/default/'.$_SESSION['domains'][$_SESSION['domain_uuid']]['domain_name'].'/'.$this->voicemail_id;

						//loop through greetings
							if (is_array($greeting_filenames) && @sizeof($greeting_filenames) != 0) {
								$x = 0;
								foreach ($greeting_filenames as $voicemail_greeting_uuid => $greeting_filename) {
									//delete the recording file
										@unlink($greeting_directory.'/'.$greeting_filename);
									//build the delete array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $voicemail_greeting_uuid;
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$x++;
								}
							}

						//reset voicemail box(es) to default (null) if deleted greeting(s) were assigned
							if (is_array($array) && @sizeof($array) != 0 && is_array($greeting_ids) && @sizeof($greeting_ids)) {
								foreach ($greeting_ids as $voicemail_id => $greeting_id) {
									if (is_numeric($voicemail_id) && is_numeric($greeting_id)) {
										$sql = "update v_voicemails set greeting_id = null ";
										$sql .= "where domain_uuid = :domain_uuid ";
										$sql .= "and voicemail_id = :voicemail_id ";
										$sql .= "and greeting_id = :greeting_id ";
										$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
										$parameters['voicemail_id'] = $voicemail_id;
										$parameters['greeting_id'] = $greeting_id;
										$database = new database;
										$database->app_name = $this->app_name;
										$database->app_uuid = $this->app_uuid;
										$database->execute($sql, $parameters);
										unset($sql, $parameters);
									}
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);
								//set message
									message::add($text['message-delete']);
							}
							unset($records);

					}
			}
		} //method

	} //class
}

?>