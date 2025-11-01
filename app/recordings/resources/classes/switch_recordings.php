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
	Portions created by the Initial Developer are Copyright (C) 2016-2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Matthew Vale <github@mafoo.org>
*/

//define the switch_recordings class
	class switch_recordings {

		/**
		 * declare constant variables
		 */
		const app_name = 'recordings';
		const app_uuid = '83913217-c7a2-9e90-925d-a866eb40b60e';

		/**
		 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
		 * @var string
		 */
		public $domain_uuid;

		/**
		 * Set in the constructor. Must be a database object and cannot be null.
		 * @var database Database Object
		 */
		private $database;

		/**
		 * Settings object set in the constructor. Must be a settings object and cannot be null.
		 * @var settings Settings Object
		 */
		private $settings;

		/**
		 * User UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
		 * @var string
		 */
		private $user_uuid;

		/**
		 * Domain name set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
		 * @var string
		 */
		private $domain_name;

		/**
		 * declare private variables
		 */
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $toggle_field;
		private $toggle_values;

		/**
		 * called when the object is created
		 */
		public function __construct(array $setting_array = []) {
			//set domain and user UUIDs
			$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
			$this->domain_name = $setting_array['domain_name'] ?? $_SESSION['domain_name'] ?? '';
			$this->user_uuid = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';

			//set objects
			$this->database = $setting_array['database'] ?? database::new();
			$this->settings = $setting_array['settings'] ?? new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);

			//assign private variables
			$this->permission_prefix = 'recording_';
			$this->list_page = 'recordings.php';
			$this->table = 'recordings';
			$this->uuid_prefix = 'recording_';
		}

		/**
		 * list recordings
		 */
		public function list_recordings() {
			$sql = "select recording_uuid, recording_filename ";
			$sql .= "from v_recordings ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$result = $this->database->select($sql, $parameters, 'all');
			if (!empty($result)) {
				$switch_recordings_domain_dir = $this->settings->get('switch', 'recordings').'/'.$this->domain_name;
				foreach ($result as $row) {
					$recordings[$switch_recordings_domain_dir."/".$row['recording_filename']] = $row['recording_filename'];
				}
			}
			else {
				$recordings = false;
			}
			unset($sql, $parameters, $result, $row);
			return $recordings;
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

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//get recording filename, build delete array
							foreach ($records as $x => $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && !empty($record['uuid'])) {

									//get filename
										$sql = "select recording_filename from v_recordings ";
										$sql .= "where domain_uuid = :domain_uuid ";
										$sql .= "and recording_uuid = :recording_uuid ";
										$parameters['domain_uuid'] = $this->domain_uuid;
										$parameters['recording_uuid'] = $record['uuid'];
										$filenames[] = $this->database->select($sql, $parameters, 'column');
										unset($sql, $parameters);

									//build delete array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array[$this->table][$x]['domain_uuid'] = $this->domain_uuid;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$this->database->delete($array);
									unset($array);

								//delete recording files
									if (is_array($filenames) && @sizeof($filenames) != 0) {
										$switch_recordings_domain_dir = $this->settings->get('switch', 'recordings')."/".$this->domain_name;
										foreach ($filenames as $filename) {
											if (!empty($filename) && file_exists($switch_recordings_domain_dir."/".$filename)) {
												@unlink($switch_recordings_domain_dir."/".$filename);
											}
										}
									}

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		} //method

	} //class
