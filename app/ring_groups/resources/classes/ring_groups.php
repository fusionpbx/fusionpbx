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
 Portions created by the Initial Developer are Copyright (C) 2010-2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the ring groups class
if (!class_exists('ring_groups')) {
	class ring_groups {

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
		public $ring_group_uuid;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'ring_groups';
				$this->app_uuid = '1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2';
				$this->permission_prefix = 'ring_group_';
				$this->list_page = 'ring_groups.php';
				$this->table = 'ring_groups';
				$this->uuid_prefix = 'ring_group_';
				$this->toggle_field = 'ring_group_enabled';
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

						//filter out unchecked ring groups, build where clause for below
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary ring group details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, dialplan_uuid, ring_group_context from v_".$this->table." ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$ring_groups[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
										$ring_group_contexts[] = $row['ring_group_context'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build the delete array
							$x = 0;
							foreach ($ring_groups as $ring_group_uuid => $ring_group) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $ring_group_uuid;
								$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['ring_group_users'][$x][$this->uuid_prefix.'uuid'] = $ring_group_uuid;
								$array['ring_group_users'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['ring_group_destinations'][$x][$this->uuid_prefix.'uuid'] = $ring_group_uuid;
								$array['ring_group_destinations'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['dialplans'][$x]['dialplan_uuid'] = $ring_group['dialplan_uuid'];
								$array['dialplan_details'][$x]['dialplan_uuid'] = $ring_group['dialplan_uuid'];
								$x++;
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('ring_group_user_delete', 'temp');
									$p->add('ring_group_destination_delete', 'temp');
									$p->add('dialplan_delete', 'temp');
									$p->add('dialplan_detail_delete', 'temp');

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('ring_group_user_delete', 'temp');
									$p->delete('ring_group_destination_delete', 'temp');
									$p->delete('dialplan_delete', 'temp');
									$p->delete('dialplan_detail_delete', 'temp');

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($ring_group_contexts) && @sizeof($ring_group_contexts) != 0) {
										$ring_group_contexts = array_unique($ring_group_contexts);
										$cache = new cache;
										foreach ($ring_group_contexts as $ring_group_context) {
											$cache->delete("dialplan:".$ring_group_context);
										}
									}

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

		public function delete_destinations($records) {

			//assign private variables
				$this->permission_prefix = 'ring_group_destination_';
				$this->table = 'ring_group_destinations';
				$this->uuid_prefix = 'ring_group_destination_';

			if (permission_exists($this->permission_prefix.'delete')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate($_SERVER['PHP_SELF'])) {
						message::add($text['message-invalid_token'],'negative');
						header('L$ring_group_uuidocation: '.$this->list_page);
						exit;
					}

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter out unchecked ring groups, build where clause for below
							foreach ($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = $record['uuid'];
								}
							}

						//get ring group context
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ring_group_context from v_ring_groups ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ring_group_uuid = :ring_group_uuid ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$parameters['ring_group_uuid'] = $this->ring_group_uuid;
								$database = new database;
								$ring_group_context = $database->select($sql, $parameters, 'column');
								unset($sql, $parameters);
							}

						//build the delete array
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$x = 0;
								foreach ($uuids as $uuid) {
									$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
									$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
									$x++;
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//execute delete
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->delete($array);
									unset($array);

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if ($ring_group_context) {
										$cache = new cache;
										$cache->delete("dialplan:".$ring_group_context);
									}

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
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle, dialplan_uuid, ring_group_context from v_".$this->table." ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$ring_groups[$row['uuid']]['state'] = $row['toggle'];
										$ring_groups[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
										$ring_group_contexts[] = $row['ring_group_context'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach ($ring_groups as $uuid => $ring_group) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $ring_group['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
								$array['dialplans'][$x]['dialplan_uuid'] = $ring_group['dialplan_uuid'];
								$array['dialplans'][$x]['dialplan_enabled'] = $ring_group['state'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
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
									if (is_array($ring_group_contexts) && @sizeof($ring_group_contexts) != 0) {
										$ring_group_contexts = array_unique($ring_group_contexts);
										$cache = new cache;
										foreach ($ring_group_contexts as $ring_group_context) {
											$cache->delete("dialplan:".$ring_group_context);
										}
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
							foreach($records as $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//create insert array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {

								//primary table
									$sql = "select * from v_".$this->table." ";
									$sql .= "where domain_uuid = :domain_uuid ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										$y = $z = 0;
										foreach ($rows as $x => $row) {
											$new_ring_group_uuid = uuid();
											$new_dialplan_uuid = uuid();

											//copy data
												$array[$this->table][$x] = $row;

											//overwrite
												$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $new_ring_group_uuid;
												$array[$this->table][$x]['dialplan_uuid'] = $new_dialplan_uuid;
												$array[$this->table][$x]['ring_group_description'] = trim($row['ring_group_description'].' ('.$text['label-copy'].')');

											//users sub table
												$sql_2 = "select * from v_ring_group_users where ring_group_uuid = :ring_group_uuid";
												$parameters_2['ring_group_uuid'] = $row['ring_group_uuid'];
												$database = new database;
												$rows_2 = $database->select($sql_2, $parameters_2, 'all');
												if (is_array($rows_2) && @sizeof($rows_2) != 0) {
													foreach ($rows_2 as $row_2) {

														//copy data
															$array['ring_group_users'][$y] = $row_2;

														//overwrite
															$array['ring_group_users'][$y]['ring_group_user_uuid'] = uuid();
															$array['ring_group_users'][$y]['ring_group_uuid'] = $new_ring_group_uuid;

														//increment
															$y++;

													}
												}
												unset($sql_2, $parameters_2, $rows_2, $row_2);

											//destinations sub table
												$sql_3 = "select * from v_ring_group_destinations where ring_group_uuid = :ring_group_uuid";
												$parameters_3['ring_group_uuid'] = $row['ring_group_uuid'];
												$database = new database;
												$rows_3 = $database->select($sql_3, $parameters_3, 'all');
												if (is_array($rows_3) && @sizeof($rows_3) != 0) {
													foreach ($rows_3 as $row_3) {

														//copy data
															$array['ring_group_destinations'][$z] = $row_3;

														//overwrite
															$array['ring_group_destinations'][$z]['ring_group_destination_uuid'] = uuid();
															$array['ring_group_destinations'][$z]['ring_group_uuid'] = $new_ring_group_uuid;

														//increment
															$z++;

													}
												}
												unset($sql_3, $parameters_3, $rows_3, $row_3);

											//ring group dialplan record
												$sql_4 = "select * from v_dialplans where dialplan_uuid = :dialplan_uuid";
												$parameters_4['dialplan_uuid'] = $row['dialplan_uuid'];
												$database = new database;
												$dialplan = $database->select($sql_4, $parameters_4, 'row');
												if (is_array($dialplan) && @sizeof($dialplan) != 0) {

													//copy data
														$array['dialplans'][$x] = $dialplan;

													//overwrite
														$array['dialplans'][$x]['dialplan_uuid'] = $new_dialplan_uuid;
														$dialplan_xml = $dialplan['dialplan_xml'];
														$dialplan_xml = str_replace($row['ring_group_uuid'], $new_ring_group_uuid, $dialplan_xml); //replace source ring_group_uuid with new
														$dialplan_xml = str_replace($dialplan['dialplan_uuid'], $new_dialplan_uuid, $dialplan_xml); //replace source dialplan_uuid with new
														$array['dialplans'][$x]['dialplan_xml'] = $dialplan_xml;
														$array['dialplans'][$x]['dialplan_description'] = trim($dialplan['dialplan_description'].' ('.$text['label-copy'].')');

												}
												unset($sql_4, $parameters_4, $dialplan);

											//create ring group context array
												$ring_group_contexts = $row['ring_group_context'];
										}
									}
									unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('ring_group_user_add', 'temp');
									$p->add('ring_group_destination_add', 'temp');
									$p->add("dialplan_add", "temp");

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('ring_group_user_add', 'temp');
									$p->delete('ring_group_destination_add', 'temp');
									$p->delete("dialplan_add", "temp");

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($ring_group_contexts) && @sizeof($ring_group_contexts) != 0) {
										$ring_group_contexts = array_unique($ring_group_contexts);
										$cache = new cache;
										foreach ($ring_group_contexts as $ring_group_context) {
											$cache->delete("dialplan:".$ring_group_context);
										}
									}

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
