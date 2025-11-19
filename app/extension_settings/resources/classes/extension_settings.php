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
	Portions created by the Initial Developer are Copyright (C) 2021-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * extension_settings class
 */
class extension_settings {

	/**
	 * declare constant variables
	 */
	const app_name = 'extension_settings';
	const app_uuid = '1416a250-f6e1-4edc-91a6-5c9b883638fd';

	/**
	 * declare the public variables
	 */
	public $extension_uuid;

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
	 * declare the private variables
	 */
	private $name;
	private $table;
	private $toggle_field;
	private $toggle_values;
	private $description_field;
	private $location;

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
		$this->user_uuid   = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';

		//set objects
		$this->database = $setting_array['database'] ?? database::new();

		//assign the variables
		$this->name              = 'extension_setting';
		$this->table             = 'extension_settings';
		$this->toggle_field      = 'extension_setting_enabled';
		$this->toggle_values     = ['true', 'false'];
		$this->description_field = 'extension_setting_description';
		$this->location          = 'extension_settings.php';

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
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$array[$this->table][$x][$this->name . '_uuid'] = $record['uuid'];
						$array[$this->table][$x]['domain_uuid']         = $this->domain_uuid;

						if (empty($this->extension_uuid)) {
							$sql                       = "select " . $this->name . "_uuid as uuid, " . $this->toggle_field . " as toggle, extension_uuid ";
							$sql                       .= "from v_" . $this->table . " ";
							$sql                       .= "where " . $this->name . "_uuid in :uuid ";
							$sql                       .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
							$parameters['domain_uuid'] = $this->domain_uuid;
							$parameters['uuid']        = $record['uuid'];
							$rows                      = $this->database->select($sql, $parameters, 'all');
							if (is_array($rows) && @sizeof($rows) != 0) {
								$this->extension_uuid = $rows[0]['extension_uuid'];
							}
							unset($sql, $parameters);
						}
					}

					//increment the id
					$x++;
				}

				//delete the checked rows
				if (is_array($array) && @sizeof($array) != 0) {
					//execute delete
					$this->database->delete($array);
					unset($array);

					//clear the cache
					if (!empty($this->extension_uuid)) {
						$sql                          = "select extension, number_alias, user_context from v_extensions ";
						$sql                          .= "where extension_uuid = :extension_uuid ";
						$parameters['extension_uuid'] = $this->extension_uuid;
						$extension                    = $this->database->select($sql, $parameters, 'row');
						$cache                        = new cache;
						$cache->delete(gethostname() . ":directory:" . $extension["extension"] . "@" . $extension["user_context"]);
						$cache->delete(gethostname() . ":directory:" . $extension["number_alias"] . "@" . $extension["user_context"]);
					}

					//set message
					message::add($text['message-delete']);
				}
				unset($records);
			}
		}
	}

	/**
	 * Toggles the state of one or more records.
	 *
	 * @param array $records  An array of record IDs to delete, where each ID is an associative array
	 *                        containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                        whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function toggle($records) {
		if (permission_exists($this->name . '_edit')) {

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

			//toggle the checked records
			if (is_array($records) && @sizeof($records) != 0) {
				//get current toggle state
				foreach ($records as $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$uuids[] = "'" . $record['uuid'] . "'";
					}
				}
				if (is_array($uuids) && @sizeof($uuids) != 0) {
					$sql                       = "select " . $this->name . "_uuid as uuid, " . $this->toggle_field . " as toggle, extension_uuid from v_" . $this->table . " ";
					$sql                       .= "where " . $this->name . "_uuid in (" . implode(', ', $uuids) . ") ";
					$sql                       .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
					$parameters['domain_uuid'] = $this->domain_uuid;
					$rows                      = $this->database->select($sql, $parameters, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						$this->extension_uuid = $rows[0]['extension_uuid'];
						foreach ($rows as $row) {
							$states[$row['uuid']] = $row['toggle'];
						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				//build update array
				$x = 0;
				foreach ($states as $uuid => $state) {
					//create the array
					$array[$this->table][$x][$this->name . '_uuid'] = $uuid;
					$array[$this->table][$x][$this->toggle_field]   = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

					//increment the id
					$x++;
				}

				//save the changes
				if (is_array($array) && @sizeof($array) != 0) {
					//save the array

					$this->database->save($array);
					unset($array);

					//clear the cache
					$sql                          = "select extension, number_alias, user_context from v_extensions ";
					$sql                          .= "where extension_uuid = :extension_uuid ";
					$parameters['extension_uuid'] = $this->extension_uuid;
					$extension                    = $this->database->select($sql, $parameters, 'row');
					$cache                        = new cache;
					$cache->delete(gethostname() . ":directory:" . $extension["extension"] . "@" . $extension["user_context"]);
					$cache->delete(gethostname() . ":directory:" . $extension["number_alias"] . "@" . $extension["user_context"]);

					//set message
					message::add($text['message-toggle']);
				}
				unset($records, $states);
			}
		}
	}

	/**
	 * Copies one or more records
	 *
	 * @param array $records  An array of record IDs to delete, where each ID is an associative array
	 *                        containing 'uuid' and 'checked' keys. The 'checked' value indicates
	 *                        whether the corresponding checkbox was checked for deletion.
	 *
	 * @return void No return value; this method modifies the database state and sets a message.
	 */
	public function copy($records) {
		if (permission_exists($this->name . '_add')) {

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

			//copy the checked records
			if (is_array($records) && @sizeof($records) != 0) {

				//get checked records
				foreach ($records as $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$uuids[] = "'" . $record['uuid'] . "'";
					}
				}

				//create the array from existing data
				if (is_array($uuids) && @sizeof($uuids) != 0) {
					$sql                       = "select * from v_" . $this->table . " ";
					$sql                       .= "where " . $this->name . "_uuid in (" . implode(', ', $uuids) . ") ";
					$sql                       .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
					$parameters['domain_uuid'] = $this->domain_uuid;
					$rows                      = $this->database->select($sql, $parameters, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						foreach ($rows as $x => $row) {
							//convert boolean values to a string
							foreach ($row as $key => $value) {
								if (gettype($value) == 'boolean') {
									$value     = $value ? 'true' : 'false';
									$row[$key] = $value;
								}
							}

							//copy data
							$array[$this->table][$x] = $row;

							//overwrite
							$array[$this->table][$x][$this->name . '_uuid']    = uuid();
							$array[$this->table][$x][$this->name . '_enabled'] = $row['extension_setting_enabled'] === true ? 'true' : 'false';
							$array[$this->table][$x][$this->description_field] = trim($row[$this->description_field] ?? '') . ' (' . $text['label-copy'] . ')';

						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				//save the changes and set the message
				if (is_array($array) && @sizeof($array) != 0) {
					//save the array
					$this->database->save($array);
					unset($array);

					//set message
					message::add($text['message-copy']);
				}
				unset($records);
			}
		}
	}

}
