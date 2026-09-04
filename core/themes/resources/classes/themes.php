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
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

/**
 * themes class
 */
if (!class_exists('themes')) {
	class themes {

		/**
		* declare the variables
		*/
		private $app_name;
		private $app_uuid;
		private $name;
		private $tables;
		private $toggle_field;
		private $toggle_values;
		private $description_field;
		private $location;

		/**
		 * called when the object is created
		 */
		public function __construct(array $setting_array = []) {
			//set objects
				$this->database = $setting_array['database'] ?? database::new();

			//assign the variables
				$this->app_name = 'themes';
				$this->app_uuid = '26b2a370-1769-4275-9ed7-a2e1a2b058bf';
				$this->name = 'theme';
				$this->tables[] = 'themes';
				$this->tables[] = 'theme_settings';
				$this->toggle_field = 'theme_enabled';
				$this->toggle_values = ['true','false'];
				$this->description_field = 'theme_description';
				$this->location = 'themes.php';
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
							foreach ($records as $x => $record) {
								//add to the array
									if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record[$this->name.'_uuid'])) {
										if (is_array($this->tables) && @sizeof($this->tables) != 0) {
											foreach ($this->tables as $table) {
												$array[$table][$x][$this->name.'_uuid'] = $record[$this->name.'_uuid'];
											}
										}
									}

								//increment the id
									$x++;
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
								if ($record['checked'] == 'true' && is_uuid($record[$this->name.'_uuid'])) {
									$uuids[] = "'".$record[$this->name.'_uuid']."'";
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
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create the array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select * from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
								$rows = $this->database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									$x = 0;
									foreach ($rows as $row) {
										//copy data
											$array[$this->table][$x] = $row;

										//add copy to the description
											$array[$this->table][$x][$this->name.'_uuid'] = uuid();
											$array[$this->table][$x][$this->description_field] = trim($row[$this->description_field]).' ('.$text['label-copy'].')';

										//increment the id
											$x++;
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

		/**
		 * Delete one or multiple theme settings
		 *
		 * This method deletes the specified theme settings and their associated
		 * setting group assignments based on their UUIDs. It validates permissions
		 * and token before performing the deletion.
		 *
		 * @param array $records An array of records to delete, where each element is an
		 *                       associative array containing:
		 *                       - 'theme_setting_uuid': The UUID of the setting to delete
		 *                       - 'checked': Boolean string ('true'/'false') indicating if selected
		 *
		 * @return bool Returns false if permission is denied, otherwise void
		 */
		public function delete_settings($records) {
			//assign the variables
			$this->name  = 'theme_setting';
			$this->table = 'theme_settings';

			//permission not found return false
			if (!permission_exists($this->name . '_delete')) {
				return false;
			}

			//add multi-lingual support
			$language = new text;
			$text     = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate('/core/themes/theme_edit.php')) {
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
					if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['theme_setting_uuid'])) {
						$array[$this->table][$x]['theme_setting_uuid']            = $record['theme_setting_uuid'];
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

		/**
		 * Toggle the enabled state of theme settings
		 *
		 * This method toggles the setting_enabled field between 'true' and 'false'
		 * for the specified setting records. It validates permissions and token
		 * before performing the toggle operation.
		 *
		 * @param array $records An array of records to toggle, where each element is an
		 *                       associative array containing:
		 *                       - 'theme_setting_uuid': The UUID of the setting to toggle
		 *                       - 'checked': Boolean string ('true'/'false') indicating if selected
		 *
		 * @return bool Returns false if permission is denied, otherwise void
		 */
		public function toggle_settings($records) {
			//assign the variables
			$this->name         = 'theme_setting';
			$this->table        = 'theme_settings';
			$this->toggle_field = 'theme_setting_enabled';

			//permission not found return false
			if (!permission_exists($this->name . '_edit')) {
				return false;
			}

			//add multi-lingual support
			$language = new text;
			$text     = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate('/core/themes/theme_edit.php')) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			//toggle the checked records
			if (is_array($records) && @sizeof($records) != 0) {
				//get current toggle state
				foreach ($records as $record) {
					if (isset($record['checked']) && $record['checked'] == 'true' && is_uuid($record['theme_setting_uuid'])) {
						$uuids[] = "'" . $record['theme_setting_uuid'] . "'";
					}
				}
				if (is_array($uuids) && @sizeof($uuids) != 0) {
					$sql  = "select " . $this->name . "_uuid as uuid, " . $this->toggle_field . " as toggle from v_" . $this->table . " ";
					$sql  .= "where " . $this->name . "_uuid in (" . implode(', ', $uuids) . ") ";
					$rows = $this->database->select($sql, $parameters ?? null, 'all');
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

		/**
		 * copy rows from the database
		 */
		public function copy_settings($records) {
			//assign the variables
			$this->name = 'theme_setting';
			$this->table = 'theme_settings';
			$this->description_field = 'theme_setting_description';

			//permission not found return false
			if (!permission_exists($this->name . '_add')) {
				return false;
			}

			//add multi-lingual support
			$language = new text;
			$text = $language->get();

			//validate the token
			$token = new token;
			if (!$token->validate('/core/themes/theme_edit.php')) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ' . $this->location);
				exit;
			}

			//copy the checked records
			if (is_array($records) && @sizeof($records) != 0) {
				//get current copy state
				foreach ($records as $record) {
					if (isset($record['checked']) && $record['checked'] == 'true' && is_uuid($record['theme_setting_uuid'])) {
						$uuids[] = "'" . $record['theme_setting_uuid'] . "'";
					}
				}

				//create the array from existing data
				if (is_array($uuids) && @sizeof($uuids) != 0) {
					$sql = "select * from v_".$this->table." ";
					$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
					$rows = $this->database->select($sql, $parameters, 'all');
					if (is_array($rows) && @sizeof($rows) != 0) {
						$x = 0;
						foreach ($rows as $row) {
							//copy data
							$array[$this->table][$x] = $row;

							//add copy to the description
							$array[$this->table][$x][$this->name.'_uuid'] = uuid();
							$array[$this->table][$x][$this->description_field] = trim($row[$this->description_field]).' ('.$text['label-copy'].')';

							//increment the id
							$x++;
						}
					}
					unset($sql, $parameters, $rows, $row);
				}

				//save the changes
				if (is_array($array) && @sizeof($array) != 0) {
					//save the array
					$this->database->save($array);
					unset($array);

					//set message
					message::add($text['message-copy']);
				}
				unset($records, $states);
			}
		}

	}
}

?>
