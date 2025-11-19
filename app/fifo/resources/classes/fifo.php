<?php

/**
 * fifo class
 */
class fifo {

	/**
	 * declare constant variables
	 */
	const app_name = 'fifo';
	const app_uuid = '16589224-c876-aeb3-f59f-523a1c0801f7';

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 *
	 * @var database Database Object
	 */
	private $database;

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
	private $description_field;
	private $location;
	private $uuid_prefix;

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

		//set objects
		$this->database = $setting_array['database'] ?? database::new();

		//assign the variables
		$this->name              = 'fifo';
		$this->table             = 'fifo';
		$this->uuid_prefix       = 'fifo_';
		$this->toggle_field      = 'fifo_enabled';
		$this->toggle_values     = ['true', 'false'];
		$this->description_field = 'fifo_description';
		$this->location          = 'fifo.php';
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

				//filter out unchecked queues, build where clause for below
				$uuids = [];
				foreach ($records as $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && !empty($record['uuid']) && is_uuid($record['uuid'])) {
						$uuids[] = "'" . $record['uuid'] . "'";
					}
				}

				//get necessary fifo queue details
				if (!empty($uuids) && is_array($uuids) && @sizeof($uuids) != 0) {
					$sql                       = "select " . $this->uuid_prefix . "uuid as uuid, dialplan_uuid from v_" . $this->table . " ";
					$sql                       .= "where domain_uuid = :domain_uuid ";
					$sql                       .= "and " . $this->uuid_prefix . "uuid in (" . implode(', ', $uuids) . ") ";
					$parameters['domain_uuid'] = $this->domain_uuid;
					$rows                      = $this->database->select($sql, $parameters, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						foreach ($rows as $row) {
							$fifos[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				//build the delete array
				$x = 0;
				foreach ($fifos as $fifo_uuid => $fifo) {
					//add to the array
					$array[$this->table][$x][$this->name . '_uuid'] = $fifo_uuid;
					$array[$this->table][$x]['domain_uuid']         = $this->domain_uuid;
					$array['fifo_members'][$x]['fifo_uuid']         = $fifo_uuid;
					$array['fifo_members'][$x]['domain_uuid']       = $this->domain_uuid;
					$array['dialplans'][$x]['dialplan_uuid']        = $fifo['dialplan_uuid'];
					$array['dialplans'][$x]['domain_uuid']          = $this->domain_uuid;

					//increment the id
					$x++;
				}

				//delete the checked rows
				if (is_array($array) && @sizeof($array) != 0) {
					//grant temporary permissions
					$p = permissions::new();
					$p->add('fifo_member_delete', 'temp');
					$p->add('dialplan_delete', 'temp');

					//execute delete
					$this->database->delete($array);
					unset($array);

					//revoke temporary permissions
					$p->delete('fifo_member_delete', 'temp');
					$p->delete('dialplan_delete', 'temp');

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
					if (!empty($record['checked']) && $record['checked'] == 'true' && !empty($record['uuid']) && is_uuid($record['uuid'])) {
						$uuids[] = "'" . $record['uuid'] . "'";
					}
				}
				if (!empty($uuids) && is_array($uuids) && @sizeof($uuids) != 0) {
					$sql                       = "select " . $this->name . "_uuid as uuid, " . $this->toggle_field . " as toggle from v_" . $this->table . " ";
					$sql                       .= "where " . $this->name . "_uuid in (" . implode(', ', $uuids) . ") ";
					$sql                       .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
					$parameters['domain_uuid'] = $this->domain_uuid;
					$rows                      = $this->database->select($sql, $parameters, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
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
					if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
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
						$x = 0;
						foreach ($rows as $row) {
							//copy data
							$array[$this->table][$x] = $row;

							//add copy to the description
							$array[$this->table][$x][$this->name . '_uuid']    = uuid();
							$array[$this->table][$x][$this->description_field] = trim($row[$this->description_field]) . ' (' . $text['label-copy'] . ')';

							//increment the id
							$x++;
						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				//save the changes and set the message
				if (is_array($array) && @sizeof($array) != 0) {

					//grant temporary permissions
					$p = permissions::new();
					$p->add('fifo_member_add', 'temp');

					//save the array

					$this->database->save($array);
					unset($array);

					//revoke temporary permissions
					$p->delete('fifo_member_add', 'temp');

					//set message
					message::add($text['message-copy']);
				}
				unset($records);
			}
		}
	}

}
