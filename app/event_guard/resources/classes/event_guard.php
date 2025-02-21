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
	Portions created by the Initial Developer are Copyright (C) 2019-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * event_guard_logs class
 *
 * @method null delete
 * @method null toggle
 * @method null copy
 */
if (!class_exists('event_guard')) {
	class event_guard {

		/**
		* declare the variables
		*/
		private $app_name;
		private $app_uuid;
		private $name;
		private $table;
		private $toggle_field;
		private $toggle_values;
		private $location;

		private $database;
		private $config;

		/**
		 * called when the object is created
		 */
		public function __construct($params = []) {
			//assign the variables
			$this->app_name = 'event_guard';
			$this->app_uuid = 'c5b86612-1514-40cb-8e2c-3f01a8f6f637';
			$this->name = 'event_guard_log';
			$this->table = 'event_guard_logs';
			$this->toggle_field = '';
			$this->toggle_values = ['block','pending'];
			$this->location = 'event_guard_logs.php';
			$this->config = config::load();
			$this->database = database::new(['config' => $config]);
		}

		/**
		 * delete rows from the database
		 */
		public function delete($records) {
			if (permission_exists($this->name.'_delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								//add to the array
									if ($record['checked'] == 'true' && is_uuid($record['event_guard_log_uuid'])) {
										$array[$this->table][$x]['event_guard_log_uuid'] = $record['event_guard_log_uuid'];
									}

								//increment the id
									$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
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
		 * update rows from the database change status to pending
		 */
		public function unblock($records) {
			if (permission_exists($this->name.'_unblock')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								//add to the array
									if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['event_guard_log_uuid'])) {
										$array[$this->table][$x]['event_guard_log_uuid'] = $record['event_guard_log_uuid'];
										$array[$this->table][$x]['log_status'] = 'pending';
									}

								//increment the id
									$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {
								//execute delete
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
									$this->database->save($array);
									unset($array);

								//initialize the settings object
									$setting = new settings(["category" => 'switch']);

								//send unblock event
									$cmd = "sendevent CUSTOM\n";
									$cmd .= "Event-Name: CUSTOM\n";
									$cmd .= "Event-Subclass: event_guard:unblock\n";
									$esl = event_socket::create();
									$switch_result = event_socket::command($cmd);

								//set message
									message::add($text['message-delete']);
							}
							unset($records);
					}
			}
		}

		/**
		 * toggle a field between two values
		 */
		public function toggle($records) {
			if (permission_exists($this->name.'_edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {
						//get current toggle state
							foreach($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['event_guard_log_uuid'])) {
									$uuids[] = "'".$record['event_guard_log_uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
								$rows = $this->database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($states as $uuid => $state) {
								//create the array
									$array[$this->table][$x][$this->name.'_uuid'] = $uuid;
									$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];

								//increment the id
									$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
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
		 * copy rows from the database
		 */
		public function copy($records) {
			if (permission_exists($this->name.'_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['event_guard_log_uuid'])) {
									$uuids[] = "'".$record['event_guard_log_uuid']."'";
								}
							}

						//create the array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select * from v_".$this->table." ";
								$sql .= "where event_guard_log_uuid in (".implode(', ', $uuids).") ";
								$rows = $this->database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									$x = 0;
									foreach ($rows as $row) {
										//copy data
											$array[$this->table][$x] = $row;

										//add copy to the description
											$array[$this->table][$x][event_guard_log.'_uuid'] = uuid();

										//increment the id
											$x++;
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {
								//save the array
									$this->database->app_name = $this->app_name;
									$this->database->app_uuid = $this->app_uuid;
									$this->database->save($array);
									unset($array);

								//set message
									message::add($text['message-copy']);
							}
							unset($records);
					}
			}
		}

		/**
		 * Removes all duplicate IPs from the logs leaving the most recent entries. If there are many IPs then this could be a heavy operation.
		 * @return null
		 */
		public function sweep() {
			$driver = $this->config->get('database.0.driver');
			$prefix = database::TABLE_PREFIX;
			if ($driver === 'pgsql') {
				$sql = "DELETE FROM {$prefix}event_guard_logs";
				$sql .= " WHERE event_guard_log_uuid IN (";
				$sql .= "	SELECT event_guard_log_uuid FROM (";
				$sql .= "		SELECT event_guard_log_uuid,";
				$sql .= "			   ROW_NUMBER() OVER (PARTITION BY ip_address ORDER BY insert_date DESC) AS row_num";
				$sql .= "		FROM {$prefix}event_guard_logs";
				$sql .= "	) subquery";
				$sql .= "	WHERE row_num > 1";
				$sql .= ");";
			}
			if ($driver === 'mysql') {
				$sql .= "DELETE t FROM {$prefix}event_guard_logs t";
				$sql .= "	JOIN (";
				$sql .= "		SELECT event_guard_log_uuid";
				$sql .= "		FROM (";
				$sql .= "			SELECT event_guard_log_uuid,";
				$sql .= "				   ROW_NUMBER() OVER (PARTITION BY ip_address ORDER BY insert_date DESC) AS row_num";
				$sql .= "			FROM {$prefix}event_guard_logs";
				$sql .= "		) subquery";
				$sql .= "		WHERE row_num > 1";
				$sql .= "	) to_delete";
				$sql .= "	ON t.event_guard_log_uuid = to_delete.event_guard_log_uuid";
			}
			if (!empty($sql)) {
				$this->database->execute($sql);
			}
			return;
		}

	}
}
