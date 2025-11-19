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
 Portions created by the Initial Developer are Copyright (C) 2008-2023
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the voicemail greetings class
class voicemail_greetings {

	/**
	 * declare constant variables
	 */
	const app_name = 'voicemail_greetings';
	const app_uuid = 'e4b4fbee-9e4d-8e46-3810-91ba663db0c2';

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 *
	 * @var database Database Object
	 */
	private $database;

	/**
	 * Settings object set in the constructor. Must be a settings object and cannot be null.
	 *
	 * @var settings Settings Object
	 */
	private $settings;

	/**
	 * User UUID set in the constructor. This can be passed in through the $settings_array associative array or set in
	 * the session global array
	 *
	 * @var string
	 */
	private $user_uuid;

	/**
	 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set
	 * in the session global array
	 *
	 * @var string
	 */
	private $domain_uuid;

	/**
	 * Domain name set in the constructor. This can be passed in through the $settings_array associative array or set
	 * in the session global array
	 *
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

	/**
	 * declare public variables
	 */
	public $voicemail_id;

	/**
	 * Initializes the object with setting array.
	 *
	 * @param array $setting_array An array containing settings for domain, user, and database connections. Defaults to
	 *                             an empty array.
	 *
	 * @return void
	 */
	public function __construct(array $setting_array = []) {
		//set domain and user UUIDs
		$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
		$this->domain_name = $setting_array['domain_name'] ?? $_SESSION['domain_name'] ?? '';
		$this->user_uuid   = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';

		//set objects
		$this->database = $setting_array['database'] ?? database::new();
		$this->settings = $setting_array['settings'] ?? new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);

		//assign private variables
		$this->permission_prefix = 'voicemail_greeting_';
		if (is_numeric($this->voicemail_id)) {
			$this->list_page = 'voicemail_greetings.php?id=' . urlencode($this->voicemail_id) . '&back=' . urlencode(PROJECT_PATH . '/app/voicemail/voicemails.php');
		} else {
			$this->list_page = PROJECT_PATH . '/app/voicemails/voicemails.php';
		}
		$this->table       = 'voicemail_greetings';
		$this->uuid_prefix = 'voicemail_greeting_';
	}

	/**
	 * Deletes one or more records.
	 *
	 * @param array $records An array of record IDs to delete, where each ID is an associative array
	 *                       containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                       whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function delete($records) {
		if (permission_exists($this->permission_prefix . 'delete')) {

			//add multi-lingual support
			$language = new text;
			$text     = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->list_page);
				exit;
			}

			//check voicemail id
			if (!is_numeric($this->voicemail_id)) {
				header('Location: ' . $this->list_page);
				exit;
			}

			//delete multiple records
			if (is_array($records) && @sizeof($records) != 0) {

				//filter out unchecked records
				foreach ($records as $x => $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$uuids[] = "'" . $record['uuid'] . "'";
					}
				}

				//get necessary greeting details
				if (is_array($uuids) && @sizeof($uuids) != 0) {
					$sql  = "select " . $this->uuid_prefix . "uuid as uuid, greeting_filename, greeting_id from v_" . $this->table . " ";
					$sql  .= "where " . $this->uuid_prefix . "uuid in (" . implode(', ', $uuids) . ") ";
					$rows = $this->database->select($sql, $parameters ?? null, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						foreach ($rows as $row) {
							$greeting_filenames[$row['uuid']]  = $row['greeting_filename'];
							$greeting_ids[$this->voicemail_id] = $row['greeting_id'];
						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				//set the greeting directory
				$greeting_directory = $this->settings->get('switch', 'storage') . '/voicemail/default/' . $this->domain_name . '/' . $this->voicemail_id;

				//loop through greetings
				if (is_array($greeting_filenames) && @sizeof($greeting_filenames) != 0) {
					$x = 0;
					foreach ($greeting_filenames as $voicemail_greeting_uuid => $greeting_filename) {
						//delete the recording file
						@unlink($greeting_directory . '/' . $greeting_filename);
						//build the delete array
						$array[$this->table][$x][$this->uuid_prefix . 'uuid'] = $voicemail_greeting_uuid;
						$array[$this->table][$x]['domain_uuid']               = $this->domain_uuid;
						$x++;
					}
				}

				//reset voicemail box(es) to default (null) if deleted greeting(s) were assigned
				if (is_array($array) && @sizeof($array) != 0 && is_array($greeting_ids) && @sizeof($greeting_ids)) {
					foreach ($greeting_ids as $voicemail_id => $greeting_id) {
						if (is_numeric($voicemail_id) && is_numeric($greeting_id)) {
							$sql                        = "update v_voicemails set greeting_id = null ";
							$sql                        .= "where domain_uuid = :domain_uuid ";
							$sql                        .= "and voicemail_id = :voicemail_id ";
							$sql                        .= "and greeting_id = :greeting_id ";
							$parameters['domain_uuid']  = $this->domain_uuid;
							$parameters['voicemail_id'] = $voicemail_id;
							$parameters['greeting_id']  = $greeting_id;
							$this->database->execute($sql, $parameters);
							unset($sql, $parameters);
						}
					}
				}

				//delete the checked rows
				if (is_array($array) && @sizeof($array) != 0) {
					//execute delete
					$this->database->delete($array);
					unset($array);
					//set message
					message::add($text['message-delete']);
				}
				unset($records);

			}
		}
	} //method

} //class
