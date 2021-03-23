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

//define the conferences class
if (!class_exists('conferences')) {
	class conferences {

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
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'conferences';
				$this->app_uuid = 'b81412e8-7253-91f4-e48e-42fc2c9a38d9';
				$this->permission_prefix = 'conference_';
				$this->list_page = 'conferences.php';
				$this->table = 'conferences';
				$this->uuid_prefix = 'conference_';
				$this->toggle_field = 'conference_enabled';
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

						//build the delete array
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {

									//get the dialplan uuid
										$sql = "select dialplan_uuid from v_conferences ";
										$sql .= "where domain_uuid = :domain_uuid ";
										$sql .= "and conference_uuid = :conference_uuid ";
										$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
										$parameters['conference_uuid'] = $record['uuid'];
										$database = new database;
										$dialplan_uuid = $database->select($sql, $parameters, 'column');
										unset($sql, $parameters);

									//build array
										$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
										$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['conference_users'][$x]['conference_uuid'] = $record['uuid'];
										$array['conference_users'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['dialplans'][$x]['dialplan_uuid'] = $dialplan_uuid;
										$array['dialplans'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
										$array['dialplan_details'][$x]['dialplan_uuid'] = $dialplan_uuid;
										$array['dialplan_details'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];

								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('conference_user_delete', 'temp');
									$p->add('dialplan_detail_delete', 'temp');
									$p->add('dialplan_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('conference_user_delete', 'temp');
									$p->delete('dialplan_detail_delete', 'temp');
									$p->delete('dialplan_delete', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									$cache = new cache;
									$cache->delete("dialplan:".$_SESSION["domain_name"]);

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

						//get current toggle state
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle, dialplan_uuid from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$conferences[$row['uuid']]['state'] = $row['toggle'];
										$conferences[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach($conferences as $uuid => $conference) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $conference['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
								$array['dialplans'][$x]['dialplan_uuid'] = $conference['dialplan_uuid'];
								$array['dialplans'][$x]['dialplan_enabled'] = $conference['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
								$x++;
							}

						//save the changes
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('dialplan_edit', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('dialplan_edit', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									$cache = new cache;
									$cache->delete("dialplan:".$_SESSION["domain_name"]);

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
								$sql = "select * from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									$y = 0;
									foreach ($rows as $x => $row) {
										$new_conference_uuid = uuid();
										$new_dialplan_uuid = uuid();

										//copy data
											$array[$this->table][$x] = $row;

										//overwrite
											$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $new_conference_uuid;
											$array[$this->table][$x]['dialplan_uuid'] = $new_dialplan_uuid;
											$array[$this->table][$x]['conference_description'] = trim($row['conference_description'].' ('.$text['label-copy'].')');

										//conference users sub table
											$sql_2 = "select * from v_conference_users ";
											$sql_2 .= "where conference_uuid = :conference_uuid ";
											$sql_2 .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
											$parameters_2['conference_uuid'] = $row['conference_uuid'];
											$parameters_2['domain_uuid'] = $_SESSION['domain_uuid'];
											$database = new database;
											$conference_users = $database->select($sql_2, $parameters_2, 'all');
											if (is_array($conference_users) && @sizeof($conference_users) != 0) {
												foreach ($conference_users as $conference_user) {

													//copy data
														$array['conference_users'][$y] = $conference_user;

													//overwrite
														$array['conference_users'][$y]['conference_user_uuid'] = uuid();
														$array['conference_users'][$y]['conference_uuid'] = $new_conference_uuid;

													//increment
														$y++;

												}
											}
											unset($sql_2, $parameters_2, $conference_users, $conference_user);

										//conference dialplan record
											$sql_3 = "select * from v_dialplans where dialplan_uuid = :dialplan_uuid";
											$parameters_3['dialplan_uuid'] = $row['dialplan_uuid'];
											$database = new database;
											$dialplan = $database->select($sql_3, $parameters_3, 'row');
											if (is_array($dialplan) && @sizeof($dialplan) != 0) {

												//copy data
													$array['dialplans'][$x] = $dialplan;

												//overwrite
													$array['dialplans'][$x]['dialplan_uuid'] = $new_dialplan_uuid;
													$dialplan_xml = $dialplan['dialplan_xml'];
													$dialplan_xml = str_replace($row['conference_uuid'], $new_conference_uuid, $dialplan_xml); //replace source conference_uuid with new
													$dialplan_xml = str_replace($dialplan['dialplan_uuid'], $new_dialplan_uuid, $dialplan_xml); //replace source dialplan_uuid with new
													$array['dialplans'][$x]['dialplan_xml'] = $dialplan_xml;
													$array['dialplans'][$x]['dialplan_description'] = trim($dialplan['dialplan_description'].' ('.$text['label-copy'].')');

											}
											unset($sql_3, $parameters_3, $dialplan);
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('conference_user_add', 'temp');
									$p->add('dialplan_add', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('conference_user_add', 'temp');
									$p->delete('dialplan_add', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									$cache = new cache;
									$cache->delete("dialplan:".$_SESSION["domain_name"]);

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}
		}

	}
}

?>
