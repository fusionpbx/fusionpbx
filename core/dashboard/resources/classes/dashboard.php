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
 * dashboard class
 */
	class dashboard {

		/**
		 * declare constant variables
		 */
		const app_name = 'dashboard';
		const app_uuid = '55533bef-4f04-434a-92af-999c1e9927f7';

		/**
		* declare the variables
		*/
		private $database;
		private $name;
		private $table;
		private $tables;
		private $toggle_field;
		private $toggle_values;
		private $description_field;
		private $location;
		private $uuid_prefix;

		/**
		 * called when the object is created
		 */
		public function __construct(array $setting_array = []) {
			//set objects
			$this->database = $setting_array['database'] ?? database::new();

			//assign the variables
			$this->tables[] = 'dashboards';
			$this->tables[] = 'dashboard_widgets';
			$this->tables[] = 'dashboard_widget_groups';
			$this->toggle_field = 'dashboard_enabled';
			$this->toggle_values = ['true','false'];
			$this->description_field = 'dashboard_description';
			$this->location = 'dashboard.php';
			$this->uuid_prefix = 'dashboard_';
		}

		/**
		 * delete rows from the database
		 */
		public function delete($records) {
			//assign the variables
				$this->name = 'dashboard';
				$this->table = 'dashboards';

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
							foreach ($records as $x => $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['dashboard_uuid'])) {
									if (is_array($this->tables) && @sizeof($this->tables) != 0) {
										foreach ($this->tables as $table) {
											$array[$table][$x][$this->uuid_prefix.'uuid'] = $record['dashboard_uuid'];
										}
									}
								}
							}

						//delete the checked rows
							if (is_array($array) && @sizeof($array) != 0) {

								//grant temp permissions
									$p = permissions::new();
									foreach ($this->tables as $table) {
										$p->add(database::singular($table).'_delete', 'temp');
									}

								//execute delete
									$this->database->delete($array);
									unset($array);

								//revoke temp permissions
									foreach ($this->tables as $table) {
										$p->delete(database::singular($table).'_delete', 'temp');
									}

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
			//assign the variables
				$this->name = 'dashboard';
				$this->table = 'dashboards';

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
								if (isset($record['checked']) && $record['checked'] == 'true' && is_uuid($record['dashboard_uuid'])) {
									$uuids[] = "'".$record['dashboard_uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
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
			//assign the variables
				$this->name = 'dashboard';
				$this->table = 'dashboards';

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
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['dashboard_uuid'])) {
									$uuids[] = "'".$record['dashboard_uuid']."'";
								}
							}

						//create the array from existing data
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								foreach ($uuids as $uuid) {
									$dashboard_uuid = uuid();
									$widget_uuids = [];

									foreach ($this->tables as $table) {
										$sql = "select * from v_".$table." ";
										$sql .= "where dashboard_uuid = ".$uuid." ";
										$database = new database;
										$rows = $database->select($sql, $parameters ?? null, 'all');
										if (is_array($rows) && @sizeof($rows) != 0) {
											$x = 0;
											foreach ($rows as $row) {
												//skip child widgets
													if (!empty($row['dashboard_widget_parent_uuid'])) {
														continue;
													}

												//prevent copying these fields
													unset($row['insert_date'], $row['insert_user']);
													unset($row['update_date'], $row['update_user']);

												//convert boolean values to a string
													foreach($row as $key => $value) {
														if (gettype($value) == 'boolean') {
															$value = $value ? 'true' : 'false';
															$row[$key] = $value;
														}
													}

												//copy data
													$array[$table][$x] = $row;

												//add copy to the description
													$array[$table][$x]['dashboard_uuid'] = $dashboard_uuid;
													if ($table === $this->table) {
														$array[$table][$x][$this->description_field] = trim($row[$this->description_field]).' ('.$text['label-copy'].')';
													}

												//handle widget uuid
													if (isset($row['dashboard_widget_uuid']) && !isset($row['dashboard_widget_group_uuid'])) {
														$widget_uuid = uuid();
														$widget_uuids[$array[$table][$x]['dashboard_widget_uuid']] = $widget_uuid;
														$array[$table][$x]['dashboard_widget_uuid'] = $widget_uuid;
														//add child widgets under parent widget
														if ($row['widget_path'] === 'dashboard/parent') {
															$x++;
															foreach ($rows as $child) {
																if ($child['dashboard_widget_parent_uuid'] == $row['dashboard_widget_uuid']) {
																	unset($child['insert_date'], $child['insert_user']);
																	unset($child['update_date'], $child['update_user']);

																	$array[$table][$x] = $child;
																	$array[$table][$x]['dashboard_uuid'] = $dashboard_uuid;

																	$child_uuid = uuid();
																	$widget_uuids[$array[$table][$x]['dashboard_widget_uuid']] = $child_uuid;
																	$array[$table][$x]['dashboard_widget_uuid'] = $child_uuid;
																	$array[$table][$x]['dashboard_widget_parent_uuid'] = $widget_uuids[$array[$table][$x]['dashboard_widget_parent_uuid']] ?? '';
																}
																$x++;
															}
														}
													}

												//handle widget group uuid
													if (isset($row['dashboard_widget_group_uuid'])) {
														$array[$table][$x]['dashboard_widget_group_uuid'] = uuid();
														$array[$table][$x]['dashboard_widget_uuid'] = $widget_uuids[$array[$table][$x]['dashboard_widget_uuid']];
													}

												//increment the id
													$x++;
											}
										}
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

		public function delete_widgets($records) {
			//assign the variables
				$this->name = 'dashboard_widget';
				$this->table = 'dashboard_widgets';

			if (permission_exists($this->name.'_delete')) {

				//validate the token
					$token = new token;
					if (!$token->validate('/core/dashboard/dashboard_widget_list.php')) {
						message::add($this->text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//delete multiple records
					if (is_array($records) && @sizeof($records) != 0) {
						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								//add to the array
									if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['dashboard_widget_uuid'])) {
										$array[$this->table][$x]['dashboard_widget_uuid'] = $record['dashboard_widget_uuid'];
										$array[$this->name.'_groups'][$x]['dashboard_widget_uuid'] = $record['dashboard_widget_uuid'];
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
		}

		public function toggle_widgets($records) {
			//assign the variables
				$this->name = 'dashboard_widget';
				$this->table = 'dashboard_widgets';
				$this->toggle_field = 'widget_enabled';

			if (permission_exists($this->name.'_edit')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/core/dashboard/dashboard_widget_list.php')) {
						message::add($this->text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

				//toggle the checked records
					if (is_array($records) && @sizeof($records) != 0) {
						//get current toggle state
							foreach($records as $record) {
								if (isset($record['checked']) && $record['checked'] == 'true' && is_uuid($record['dashboard_widget_uuid'])) {
									$uuids[] = "'".$record['dashboard_widget_uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->name."_uuid as uuid, ".$this->toggle_field." as toggle from v_".$this->table." ";
								$sql .= "where ".$this->name."_uuid in (".implode(', ', $uuids).") ";
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

		public function assign_widgets($records, $dashboard_uuid, $group_uuid) {
			//assign the variables
				$this->name = 'dashboard_widget';
				$this->table = 'dashboard_widgets';

			if (permission_exists($this->name.'_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/core/dashboard/dashboard_widget_list.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

					//assign multiple records
					if (is_array($records) && @sizeof($records) != 0 && !empty($group_uuid)) {

						//define the group_name and group_uuid
							if (!empty($records) && @sizeof($records) != 0) {
								$sql = "select group_name, group_uuid from v_groups	";
								$sql .= "where group_uuid = :group_uuid	";
								$parameters['group_uuid'] = $group_uuid;
								$group = $this->database->select($sql, $parameters, 'row');
							}

						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['dashboard_widget_uuid'])) {
									//build array
										$uuids[] = "'".$record['dashboard_widget_uuid']."'";
									//assign dashboard widget groups
										$array[$this->name.'_groups'][$x][$this->name.'_group_uuid'] = uuid();
										$array[$this->name.'_groups'][$x]['dashboard_uuid'] = $dashboard_uuid;
										$array[$this->name.'_groups'][$x][$this->name.'_uuid'] = $record['dashboard_widget_uuid'];
										$array[$this->name.'_groups'][$x]['group_uuid'] = $group['group_uuid'];
									//increment
										$x++;
								}
							}

							unset($records);

						//exlude exist rows
						if (!empty($array) && @sizeof($array) != 0) {
							$sql = "select dashboard_uuid, ".$this->name."_uuid, ";
							$sql .= "group_uuid from v_".$this->name."_groups ";
							$dashboard_widget_groups = $this->database->select($sql, null, 'all');
							$array[$this->name.'_groups'] = array_filter($array[$this->name.'_groups'], function($ar) use ($dashboard_widget_groups) {
								foreach ($dashboard_widget_groups as $existing_array_item) {
									if ($ar['dashboard_uuid'] == $existing_array_item['dashboard_uuid'] && $ar[$this->name.'_uuid'] == $existing_array_item[$this->name.'_uuid'] && $ar['group_uuid'] == $existing_array_item['group_uuid']) {
										return false;
									}
								}
								return true;
							});
							unset($dashboard_widget_groups);
						}

						//add the checked rows from group
							if (!empty($array) && is_array($array) && @sizeof($array) != 0) {
								//execute save

									$this->database->save($array);
									unset($array);
								//set message
									message::add($text['message-add']);
							}
					}
			}
		}

		public function unassign_widgets($records, $dashboard_uuid, $group_uuid) {
			//assign the variables
				$this->name = 'dashboard_widget';
				$this->table = 'dashboard_widgets';

			if (permission_exists($this->name.'_add')) {

				//add multi-lingual support
					$language = new text;
					$text = $language->get();

				//validate the token
					$token = new token;
					if (!$token->validate('/core/dashboard/dashboard_widget_list.php')) {
						message::add($text['message-invalid_token'],'negative');
						header('Location: '.$this->location);
						exit;
					}

					//assign multiple records
					if (is_array($records) && @sizeof($records) != 0 && !empty($group_uuid)) {

						//define the group_name and group_uuid
							if (!empty($records) && @sizeof($records) != 0) {
								$sql = "select group_name, group_uuid from v_groups	";
								$sql .= "where group_uuid = :group_uuid	";
								$parameters['group_uuid'] = $group_uuid;
								$group = $this->database->select($sql, $parameters, 'row');
							}

						//build the delete array
							$x = 0;
							foreach ($records as $record) {
								if (!empty($record['checked']) && $record['checked'] == 'true' && is_uuid($record['dashboard_widget_uuid'])) {
									//build array
										$uuids[] = "'".$record['dashboard_widget_uuid']."'";
									//assign dashboard widget groups
											$array[$this->name.'_groups'][$x]['dashboard_uuid'] = $dashboard_uuid;
											$array[$this->name.'_groups'][$x][$this->name.'_uuid'] = $record['dashboard_widget_uuid'];
											$array[$this->name.'_groups'][$x]['group_uuid'] = $group['group_uuid'];
									//increment
											$x++;
								}
							}

							unset($records);

						//include child dashboard widgets and their dasboard_uuid too
							if (!empty($uuids) && @sizeof($uuids) != 0) {
								$sql = "select dashboard_uuid, ".$this->name."_uuid from v_".$this->table." ";
								$sql .= "where ".$this->name."_parent_uuid in (".implode(', ', $uuids).") ";
								$rows = $this->database->select($sql, null, 'all');
								if (!empty($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										//assign dashboard widget groups
											$array[$this->name.'_groups'][$x]['dashboard_uuid'] = $row['dashboard_uuid'];
											$array[$this->name.'_groups'][$x][$this->name.'_uuid'] = $row['dashboard_widget_uuid'];
											$array[$this->name.'_groups'][$x]['group_uuid'] = $group['group_uuid'];
										//increment
											$x++;
									}
								}
							}

							unset($uuids);

						//add the checked rows from group
							if (!empty($array) && is_array($array) && @sizeof($array) != 0) {
							//grant temporary permissions
								$p = new permissions;
								$p->add('dashboard_widget_group_delete', 'temp');

							//execute delete
								$this->database->delete($array);
								unset($array);

							//revoke temporary permissions
								$p->delete('dashboard_widget_group_delete', 'temp');

							//set message
								message::add($text['message-delete']);
							}
					}
			}
		}

	}
