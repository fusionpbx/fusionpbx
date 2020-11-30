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

//define the phrases class
if (!class_exists('phrases')) {
	class phrases {

		/**
		 * declare private variables
		 */
		private $app_name;
		private $app_uuid;
		private $permission_prefix;
		private $list_page;
		private $table;
		private $uuid_prefix;
		private $toggle_field;
		private $toggle_values;

		/**
		 * declare public variables
		 */
		public $phrase_uuid;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'phrases';
				$this->app_uuid = '5c6f597c-9b78-11e4-89d3-123b93f75cba';
				$this->permission_prefix = 'phrase_';
				$this->list_page = 'phrases.php';
				$this->table = 'phrases';
				$this->uuid_prefix = 'phrase_';
				$this->toggle_field = 'phrase_enabled';
				$this->toggle_values = ['true','false'];

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

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked phrases, build where clause for below
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get phrase languages
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, phrase_language as lang from v_".$this->table." ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$phrase_languages[$row['uuid']] = $row['lang'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build the delete array
							if (is_array($phrase_languages) && @sizeof($phrase_languages) != 0) {
								$x = 0;
								foreach ($phrase_languages as $phrase_uuid => $phrase_language) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $phrase_uuid;
									$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									$array['phrase_details'][$x][$this->uuid_prefix.'uuid'] = $phrase_uuid;
									$array['phrase_details'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									$x++;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('phrase_detail_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('phrase_detail_delete', 'temp');

								//clear the cache
									$phrase_languages = array_unique($phrase_languages);
									$cache = new cache;
									foreach ($phrase_languages as $phrase_language) {
										$cache->delete("languages:".$phrase_language);
									}

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-delete']);
							}
							unset($records, $phrase_languages);
					}
			}
		}

		public function delete_details($records) {
			//assign private variables
				$this->permission_prefix = 'phrase_';
				$this->list_page = 'phrase_edit.php?id='.$this->phrase_uuid;
				$this->table = 'phrase_details';
				$this->uuid_prefix = 'phrase_detail_';

			if (permission_exists($this->permission_prefix.'edit')) {

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

						//filter out unchecked phrases, build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
									$array[$this->table][$x]['phrase_uuid'] = $this->phrase_uuid;
									$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								}
							}

						//get phrase languages
							if (is_array($array) && @sizeof($array) != 0) {
								$sql = "select phrase_language as lang from v_phrases ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and phrase_uuid = :phrase_uuid ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$parameters['phrase_uuid'] = $this->phrase_uuid;
								$database = new database;
								$phrase_language = $database->select($sql, $parameters, 'column');
								unset($sql, $parameters, $rows, $row);
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('phrase_detail_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('phrase_detail_delete', 'temp');

								//clear the cache
									if ($phrase_language != '') {
										$cache = new cache;
										$cache->delete("languages:".$phrase_language);
									}

							}
							unset($records, $phrase_language);
					}
			}
		}

		/**
		 * toggle records
		 */
		public function toggle($records) {
			if (permission_exists($this->permission_prefix.'edit')) {

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

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get current toggle state and language
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle, phrase_language as lang from v_".$this->table." ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
										$phrase_languages[] = $row['lang'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($states as $uuid => $state) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $state == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
								$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//clear the cache
									$phrase_languages = array_unique($phrase_languages);
									$cache = new cache;
									foreach ($phrase_languages as $phrase_language) {
										$cache->delete("languages:".$phrase_language);
									}

								//clear the destinations session array
									if (isset($_SESSION['destinations']['array'])) {
										unset($_SESSION['destinations']['array']);
									}

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $states);
					}

			}
		}

		/**
		 * copy records
		 */
		public function copy($records) {
			if (permission_exists($this->permission_prefix.'add')) {

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

				//copy the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//get checked records
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create insert array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {

								//primary table
									$sql = "select * from v_".$this->table." ";
									$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										$y = 0;
										foreach ($rows as $x => $row) {
											$primary_uuid = uuid();

											//copy data
												$array[$this->table][$x] = $row;

											//overwrite
												$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $primary_uuid;
												$array[$this->table][$x]['phrase_description'] = trim($row['phrase_description'].' ('.$text['label-copy'].')');

											//details sub table
												$sql_2 = "select * from v_phrase_details where phrase_uuid = :phrase_uuid";
												$parameters_2['phrase_uuid'] = $row['phrase_uuid'];
												$database = new database;
												$rows_2 = $database->select($sql_2, $parameters_2, 'all');
												if (is_array($rows_2) && @sizeof($rows_2) != 0) {
													foreach ($rows_2 as $row_2) {

														//copy data
															$array['phrase_details'][$y] = $row_2;

														//overwrite
															$array['phrase_details'][$y]['phrase_detail_uuid'] = uuid();
															$array['phrase_details'][$y]['phrase_uuid'] = $primary_uuid;

														//increment
															$y++;

													}
												}
												unset($sql_2, $parameters_2, $rows_2, $row_2);

											//create array of languages
												$phrase_languages[] = $row['phrase_languages'];
										}
									}
									unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('phrase_detail_add', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('phrase_detail_add', 'temp');

								//clear the cache
									$phrase_languages = array_unique($phrase_languages);
									$cache = new cache;
									foreach ($phrase_languages as $phrase_language) {
										$cache->delete("languages:".$phrase_language);
									}

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}
		} //method

	} //class
}

?>
