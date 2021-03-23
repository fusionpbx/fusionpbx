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
	Copyright (C) 2010-2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the time conditions class
	if (!class_exists('time_conditions')) {
		class time_conditions {

			/**
			* declare public/private properties
			*/
			private $app_name;
			private $app_uuid;
			private $permission_prefix;
			private $list_page;
			private $table;
			private $uuid_prefix;
			private $toggle_field;
			private $toggle_values;

			//class constructor
			public function __construct() {
				//set the default value
				$this->dialplan_global = false;

				//assign property defaults
					$this->app_name = 'time_conditions';
					$this->app_uuid = '4b821450-926b-175a-af93-a03c441818b1';
					$this->permission_prefix = 'time_condition_';
					$this->list_page = 'time_conditions.php';
					$this->table = 'dialplans';
					$this->uuid_prefix = 'dialplan_';
					$this->toggle_field = 'dialplan_enabled';
					$this->toggle_values = ['true','false'];
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

										//build delete array
											$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $record['uuid'];
											$array['dialplan_details'][$x]['dialplan_uuid'] = $record['uuid'];

										//get the dialplan context
											$sql = "select dialplan_context from v_dialplans ";
											$sql .= "where dialplan_uuid = :dialplan_uuid ";
											$parameters['dialplan_uuid'] = $record['uuid'];
											$database = new database;
											$dialplan_contexts[] = $database->select($sql, $parameters, 'column');
											unset($sql, $parameters);

									}
								}

							//delete the checked rows
								if (is_array($array) && @sizeof($array) != 0) {

									//grant temporary permissions
										$p = new permissions;
										$p->add('dialplan_delete', 'temp');
										$p->add('dialplan_detail_delete', 'temp');

									//execute delete
										$database = new database;
										$database->app_name = $this->app_name;
										$database->app_uuid = $this->app_uuid;
										$database->delete($array);

									//revoke temporary permissions
										$p->delete('dialplan_delete', 'temp');
										$p->delete('dialplan_detail_delete', 'temp');

									//clear the cache
										if (is_array($dialplan_contexts) && @sizeof($dialplan_contexts) != 0) {
											$dialplan_contexts = array_unique($dialplan_contexts, SORT_STRING);
											$cache = new cache;
											foreach ($dialplan_contexts as $dialplan_context) {
												$cache->delete("dialplan:".$dialplan_context);
											}
										}

									//clear the destinations session array
										if (isset($_SESSION['destinations']['array'])) {
											unset($_SESSION['destinations']['array']);
										}

									//set message
										message::add($text['message-delete'].': '.@sizeof($array[$this->table]));

								}
								unset($records, $array);

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
									$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle, dialplan_context from v_".$this->table." ";
									$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
									$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									$database = new database;
									$rows = $database->select($sql, $parameters, 'all');
									if (is_array($rows) && @sizeof($rows) != 0) {
										foreach ($rows as $row) {
											$states[$row['uuid']] = $row['toggle'];
											$dialplan_contexts[] = $row['dialplan_context'];
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

									//clear the cache
										if (is_array($dialplan_contexts) && @sizeof($dialplan_contexts) != 0) {
											$dialplan_contexts = array_unique($dialplan_contexts, SORT_STRING);
											$cache = new cache;
											foreach ($dialplan_contexts as $dialplan_context) {
												$cache->delete("dialplan:".$dialplan_context);
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
								foreach($records as $x => $record) {
									if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
										$uuids[] = "'".$record['uuid']."'";
									}
								}

							//create insert array from existing data
								if (is_array($uuids) && @sizeof($uuids) != 0) {

									//primary table
										$sql = "select * from v_".$this->table." ";
										$sql .= "where ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
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
													$array[$this->table][$x]['dialplan_description'] = trim($row['dialplan_description'].' ('.$text['label-copy'].')');

												//details sub table
													$sql_2 = "select * from v_dialplan_details where dialplan_uuid = :dialplan_uuid";
													$parameters_2['dialplan_uuid'] = $row['dialplan_uuid'];
													$database = new database;
													$rows_2 = $database->select($sql_2, $parameters_2, 'all');
													if (is_array($rows_2) && @sizeof($rows_2) != 0) {
														foreach ($rows_2 as $row_2) {

															//copy data
																$array['dialplan_details'][$y] = $row_2;

															//overwrite
																$array['dialplan_details'][$y]['dialplan_detail_uuid'] = uuid();
																$array['dialplan_details'][$y]['dialplan_uuid'] = $primary_uuid;

															//increment
																$y++;

														}
													}
													unset($sql_2, $parameters_2, $rows_2, $row_2);

												//get dialplan contexts
													$dialplan_contexts[] = $row['dialplan_context'];
											}
										}
										unset($sql, $parameters, $rows, $row);
								}

							//save the changes and set the message
								if (is_array($array) && @sizeof($array) != 0) {

									//grant temporary permissions
										$p = new permissions;
										$p->add('dialplan_detail_add', 'temp');

									//save the array
										$database = new database;
										$database->app_name = $this->app_name;
										$database->app_uuid = $this->app_uuid;
										$database->save($array);
										unset($array);

									//revoke temporary permissions
										$p->delete('dialplan_detail_add', 'temp');

									//clear the cache
										if (is_array($dialplan_contexts) && @sizeof($dialplan_contexts) != 0) {
											$dialplan_contexts = array_unique($dialplan_contexts, SORT_STRING);
											$cache = new cache;
											foreach ($dialplan_contexts as $dialplan_context) {
												$cache->delete("dialplan:".$dialplan_context);
											}
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
