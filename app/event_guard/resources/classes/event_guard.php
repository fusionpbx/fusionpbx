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
	Portions created by the Initial Developer are Copyright (C) 2019-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * event_guard_logs class
 */
class event_guard {

	/**
	 * declare constant variables
	 */
	const app_name = 'event_guard';
	const app_uuid = 'c5b86612-1514-40cb-8e2c-3f01a8f6f637';

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
	 * declare the variables
	 */
	private $name;
	private $table;
	private $toggle_field;
	private $toggle_values;
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
		// Set domain and user UUIDs
		$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
		$this->user_uuid   = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';

		// Set the objects
		$this->database = $setting_array['database'] ?? database::new();

		// Assign the variables
		$this->name          = 'event_guard_log';
		$this->table         = 'event_guard_logs';
		$this->toggle_field  = '';
		$this->toggle_values = ['block', 'pending'];
		$this->location      = 'event_guard_logs.php';
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

			// Add multi-lingual support
			$language = new text;
			$text     = $language->get();

			// Validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			// Delete multiple records
			if (is_array($records) && @sizeof($records) != 0) {
				// Build the delete array
				$x = 0;
				foreach ($records as $record) {
					// Add to the array
					if ($record['checked'] == 'true' && is_uuid($record['event_guard_log_uuid'])) {
						$array[$this->table][$x]['event_guard_log_uuid'] = $record['event_guard_log_uuid'];
					}

					// Increment the id
					$x++;
				}

				// Delete the checked rows
				if (is_array($array) && @sizeof($array) != 0) {
					// Execute delete
					$this->database->delete($array);
					unset($array);

					// Set the message
					message::add($text['message-delete']);
				}
				unset($records);
			}
		}
	}

	/**
	 * Unblocks multiple records.
	 *
	 * @param array $records An array of records to unblock, each containing 'event_guard_log_uuid' and 'checked' keys.
	 *
	 * @return void
	 */
	public function unblock($records) {
		if (permission_exists($this->name . '_unblock')) {

			// Add multi-lingual support
			$language = new text;
			$text     = $language->get();

			// Validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			// Delete multiple records
			if (is_array($records) && @sizeof($records) != 0) {
				//build the delete array
				$x = 0;
				foreach ($records as $record) {
					//add to the array
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['event_guard_log_uuid'])) {
						$array[$this->table][$x]['event_guard_log_uuid'] = $record['event_guard_log_uuid'];
						$array[$this->table][$x]['log_status']           = 'pending';
					}

					//increment the id
					$x++;
				}

				// Delete the checked rows
				if (is_array($array) && @sizeof($array) != 0) {
					// Execute delete
					$this->database->save($array);
					unset($array);

					// Initialize the settings object
					$setting = new settings(["category" => 'switch']);

					// Send unblock event
					$cmd = "sendevent CUSTOM\n";
					$cmd .= "Event-Name: CUSTOM\n";
					$cmd .= "Event-Subclass: event_guard:unblock\n";
					$esl = event_socket::create();
					$switch_result = event_socket::command($cmd);

					// Set the message
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

			// Add multi-lingual support
			$language = new text;
			$text     = $language->get();

			// Validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			// Toggle the checked records
			if (is_array($records) && @sizeof($records) != 0) {
				// Get current toggle state
				foreach ($records as $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['event_guard_log_uuid'])) {
						$uuids[] = "'" . $record['event_guard_log_uuid'] . "'";
					}
				}
				if (is_array($uuids) && @sizeof($uuids) != 0) {
					$sql  = "select " . $this->name . "_uuid as uuid, " . $this->toggle_field . " as toggle from v_" . $this->table . " ";
					$sql  .= "where " . $this->name . "_uuid in (" . implode(', ', $uuids) . ") ";
					$rows = $this->database->select($sql, null, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						foreach ($rows as $row) {
							$states[$row['uuid']] = $row['toggle'];
						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				// Build update array
				$x = 0;
				foreach ($states as $uuid => $state) {
					// Create the array
					$array[$this->table][$x][$this->name . '_uuid'] = $uuid;
					$array[$this->table][$x][$this->toggle_field]   = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

					// Increment the id
					$x++;
				}

				// Save the changes
				if (is_array($array) && @sizeof($array) != 0) {
					// Save the array
					$this->database->save($array);
					unset($array);

					// Set the message
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

			// Add multi-lingual support
			$language = new text;
			$text     = $language->get();

			// Validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			// Copy the checked records
			if (is_array($records) && @sizeof($records) != 0) {

				// Get checked records
				foreach ($records as $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['event_guard_log_uuid'])) {
						$uuids[] = "'" . $record['event_guard_log_uuid'] . "'";
					}
				}

				// Create the array from existing data
				if (is_array($uuids) && @sizeof($uuids) != 0) {
					$sql  = "select * from v_" . $this->table . " ";
					$sql  .= "where event_guard_log_uuid in (" . implode(', ', $uuids) . ") ";
					$rows = $this->database->select($sql, null, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						$x = 0;
						foreach ($rows as $row) {
							// Convert boolean values to a string
							foreach ($row as $key => $value) {
								if (gettype($value) == 'boolean') {
									$value     = $value ? 'true' : 'false';
									$row[$key] = $value;
								}
							}

							// Copy data
							$array[$this->table][$x] = $row;

							// Add copy to the description
							$array[$this->table][$x]['event_guard_log_uuid'] = uuid();

							// Increment the id
							$x++;
						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				// Save the changes and set the message
				if (is_array($array) && @sizeof($array) != 0) {
					// Save the array
					$this->database->save($array);
					unset($array);

					// Set the message
					message::add($text['message-copy']);
				}
				unset($records);
			}
		}
	}
}
