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
	Portions created by the Initial Developer are Copyright (C) 2019-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * user_logs class
 */
class user_logs {

	/**
	 * declare constant variables
	 */
	const app_name = 'user_logs';
	const app_uuid = '582a13cf-7d75-4ea3-b2d9-60914352d76e';

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
	 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set
	 * in the session global array
	 *
	 * @var string
	 */
	private $domain_uuid;

	/**
	 * declare private variables
	 */
	private $name;
	private $table;
	private $toggle_field;
	private $toggle_values;
	private $location;

	/**
	 * Constructor for the class.
	 *
	 * This method initializes the object with setting_array and session data.
	 *
	 * @param array $setting_array An optional array of settings to override default values. Defaults to [].
	 */
	public function __construct(array $setting_array = []) {
		//set domain and user UUIDs
		$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';

		//set objects
		$this->database = $setting_array['database'] ?? database::new();

		//assign the variables
		$this->name          = 'user_log';
		$this->table         = 'user_logs';
		$this->toggle_field  = '';
		$this->toggle_values = ['true', 'false'];
		$this->location      = 'user_logs.php';
	}

	/**
	 * Adds a new log entry to the database.
	 *
	 * This method creates a database object, prepares an array of data for insertion,
	 * saves the data, and removes any temporary permissions created during the process.
	 *
	 * @param array  $result  An associative array containing user login result details.
	 * @param string $details Optional additional details for failed logins. Defaults to ''.
	 */
	public static function add($result, $details = '') {

		//create the database object
		$database = database::new();

		//prepare the array
		$array                                   = [];
		$array['user_logs'][0]["timestamp"]      = 'now()';
		$array['user_logs'][0]["domain_uuid"]    = $result['domain_uuid'];
		$array['user_logs'][0]["user_uuid"]      = $result['user_uuid'];
		$array['user_logs'][0]["username"]       = $result['username'];
		$array['user_logs'][0]["hostname"]       = gethostname();
		$array['user_logs'][0]["type"]           = $result['type'] ?? 'login';
		$array['user_logs'][0]["remote_address"] = $_SERVER['REMOTE_ADDR'];
		$array['user_logs'][0]["user_agent"]     = $_SERVER['HTTP_USER_AGENT'];
		$array['user_logs'][0]["session_id"]     = session_id();
		if ($result["authorized"]) {
			$array['user_logs'][0]["result"] = 'success';
		} else {
			$array['user_logs'][0]["result"] = 'failure';
			$array['user_logs'][0]["detail"] = $details;
		}

		//add the dialplan permission
		$p = permissions::new();
		$p->add("user_log_add", 'temp');

		//save to the data
		$database->save($array, false);
		$message = $database->message;

		//remove the temporary permission
		$p->delete("user_log_add", 'temp');
	}

	/**
	 * Deletes one or multiple records.
	 *
	 * @param array $records An array of record IDs to delete, where each ID is an associative array
	 *                       containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                       whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function delete($records) {
		if (permission_exists($this->name . '_delete')) {

			//add multi-lingual support
			$language = new text;
			$text     = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			//delete multiple records
			if (is_array($records) && @sizeof($records) != 0) {
				//build the delete array
				$x = 0;
				foreach ($records as $record) {
					//add to the array
					if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$array[$this->table][$x][$this->name . '_uuid'] = $record['uuid'];
						$array[$this->table][$x]['domain_uuid']         = $this->domain_uuid;
					}

					//increment the id
					$x++;
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
	}

}
