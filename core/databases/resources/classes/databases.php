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
 Portions created by the Initial Developer are Copyright (C) 2020-2023
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the databases class
class databases {

	/**
	 * declare constant variables
	 */
	const app_name = 'databases';
	const app_uuid = '8d229b6d-1383-fcec-74c6-4ce1682479e2';

	/**
	 * declare private variables
	 */

	private $database;
	private $permission_prefix;
	private $list_page;
	private $table;
	private $uuid_prefix;

	/**
	 * Initializes the object by setting default values and connecting to the database.
	 */
	public function __construct() {

		//assign private variables
		$this->permission_prefix = 'database_';
		$this->list_page         = 'databases.php';
		$this->table             = 'databases';
		$this->uuid_prefix       = 'database_';

		//connect to the database
		if (empty($this->database)) {
			$this->database = database::new();
		}

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

			//delete multiple records
			if (is_array($records) && @sizeof($records) != 0) {

				//build the delete array
				foreach ($records as $x => $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$array[$this->table][$x][$this->uuid_prefix . 'uuid'] = $record['uuid'];
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
		if (permission_exists($this->permission_prefix . 'add')) {

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

			//copy the checked records
			if (is_array($records) && @sizeof($records) != 0) {

				//get checked records
				foreach ($records as $x => $record) {
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['uuid'])) {
						$uuids[] = "'" . $record['uuid'] . "'";
					}
				}

				//create insert array from existing data
				if (is_array($uuids) && @sizeof($uuids) != 0) {
					$sql  = "select * from v_" . $this->table . " ";
					$sql  .= "where " . $this->uuid_prefix . "uuid in (" . implode(', ', $uuids) . ") ";
					$rows = $this->database->select($sql, $parameters ?? null, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						foreach ($rows as $x => $row) {

							//copy data
							$array[$this->table][$x] = $row;

							//overwrite
							$array[$this->table][$x][$this->uuid_prefix . 'uuid'] = uuid();
							$array[$this->table][$x]['database_description']      = trim($row['database_description'] . ' (' . $text['label-copy'] . ')');

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
