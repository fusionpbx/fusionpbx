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
 Portions created by the Initial Developer are Copyright (C) 2008 - 2019
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the page_groups class
if (!class_exists('page_groups')) {
	class page_groups {

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
				$this->app_name = 'page_groups';
				$this->app_uuid = 'e3a6a2e9-340b-4f38-b0cc-550a15f59a68';
				$this->permission_prefix = 'page_group_';
				$this->list_page = 'page_groups.php';
				$this->table = 'page_groups';
				$this->uuid_prefix = 'page_group_';
				$this->toggle_field = 'page_group_enabled';
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

						//filter out unchecked call flows, build where clause for below
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary call flow details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, dialplan_uuid, page_group_context from v_".$this->table." ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$page_groups[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
										$page_group_contexts[] = $row['page_group_context'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build the delete array
							$x = 0;
							foreach ($page_groups as $page_group_uuid => $page_group) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $page_group_uuid;
								$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['dialplans'][$x]['dialplan_uuid'] = $page_group['dialplan_uuid'];
								$array['dialplans'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$array['dialplan_details'][$x]['dialplan_uuid'] = $page_group['dialplan_uuid'];
								$array['dialplan_details'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
								$x++;
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
									unset($array);

								//revoke temporary permissions
									$p->delete('dialplan_delete', 'temp');
									$p->delete('dialplan_detail_delete', 'temp');

								//syncrhonize configuration
									save_dialplan_xml();

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($page_group_contexts) && @sizeof($page_group_contexts) != 0) {
										$page_group_contexts = array_unique($page_group_contexts);
										$cache = new cache;
										foreach ($page_group_contexts as $page_group_context) {
											$cache->delete("dialplan:".$page_group_context);
										}
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

						//get current toggle enable
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle, dialplan_uuid, page_group_context from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$page_groups[$row['uuid']]['page_group_enabled'] = $row['toggle'];
										$page_groups[$row['uuid']]['dialplan_uuid'] = $row['dialplan_uuid'];
										$page_group_contexts[] = $row['page_group_context'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}
							
						//build update array
							$x = 0;
							foreach($page_groups as $uuid => $page_group) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $uuid;
								$array[$this->table][$x][$this->toggle_field] = $page_group['page_group_enabled'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
								$array['dialplans'][$x]['dialplan_uuid'] = $page_group['dialplan_uuid'];
								$array['dialplans'][$x]['dialplan_enabled'] = $page_group['page_group_enabled'] == $this->toggle_values[0] ? $this->toggle_values[1] : $this->toggle_values[0];
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

								//syncrhonize configuration
									save_dialplan_xml();

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($page_group_contexts) && @sizeof($page_group_contexts) != 0) {
										$page_group_contexts = array_unique($page_group_contexts);
										$cache = new cache;
										foreach ($page_group_contexts as $page_group_context) {
											$cache->delete("dialplan:".$page_group_context);
										}
									}

								//set message
									message::add($text['message-toggle']);
							}
							unset($records, $page_groups);
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
										foreach ($rows as $x => $row) {
											$new_page_group_uuid = uuid();
											$new_dialplan_uuid = uuid();

											//copy data
												$array[$this->table][$x] = $row;

											//overwrite
												$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $new_page_group_uuid;
												$array[$this->table][$x]['dialplan_uuid'] = $new_dialplan_uuid;
												$array[$this->table][$x]['page_group_description'] = trim($row['page_group_description'].' ('.$text['label-copy'].')');

											//call flow dialplan record
												$sql_2 = "select * from v_dialplans where dialplan_uuid = :dialplan_uuid";
												$parameters_2['dialplan_uuid'] = $row['dialplan_uuid'];
												$database = new database;
												$dialplan = $database->select($sql_2, $parameters_2, 'row');
												if (is_array($dialplan) && @sizeof($dialplan) != 0) {

													//copy data
														$array['dialplans'][$x] = $dialplan;

													//overwrite
														$array['dialplans'][$x]['dialplan_uuid'] = $new_dialplan_uuid;
														$dialplan_xml = $dialplan['dialplan_xml'];
														$dialplan_xml = str_replace($row['page_group_uuid'], $new_page_group_uuid, $dialplan_xml); //replace source page_group_uuid with new
														$dialplan_xml = str_replace($dialplan['dialplan_uuid'], $new_dialplan_uuid, $dialplan_xml); //replace source dialplan_uuid with new
														$array['dialplans'][$x]['dialplan_xml'] = $dialplan_xml;
														$array['dialplans'][$x]['dialplan_description'] = trim($dialplan['dialplan_description'].' ('.$text['label-copy'].')');

												}
												unset($sql_2, $parameters_2, $dialplan);

											//create call flow context array
												$page_group_contexts = $row['page_group_context'];
										}
									}
									unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temporary permissions
									$p = new permissions;
									$p->add('dialplan_add', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//revoke temporary permissions
									$p->delete('dialplan_add', 'temp');

								//syncrhonize configuration
									save_dialplan_xml();

								//apply settings reminder
									$_SESSION["reload_xml"] = true;

								//clear the cache
									if (is_array($page_group_contexts) && @sizeof($page_group_contexts) != 0) {
										$page_group_contexts = array_unique($page_group_contexts);
										$cache = new cache;
										foreach ($page_group_contexts as $page_group_context) {
											$cache->delete("dialplan:".$page_group_context);
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