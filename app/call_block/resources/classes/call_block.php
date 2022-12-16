<?php

/**
 * call block class
 *
 * @method null download
 */
if (!class_exists('call_block')) {
	class call_block {

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
		public $call_block_direction;
		public $extension_uuid;
		public $call_block_app;
		public $call_block_data;

		/**
		 * called when the object is created
		 */
		public function __construct() {

			//assign private variables
				$this->app_name = 'call_block';
				$this->app_uuid = '9ed63276-e085-4897-839c-4f2e36d92d6c';
				$this->permission_prefix = 'call_block_';
				$this->list_page = 'call_block.php';
				$this->table = 'call_block';
				$this->uuid_prefix = 'call_block_';
				$this->toggle_field = 'call_block_enabled';
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

						//filter out unchecked, build where clause for below
							foreach($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get necessary call block details
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, call_block_number from v_".$this->table." ";
								$sql .= "where domain_uuid = :domain_uuid ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$call_block_numbers[$row['uuid']] = $row['call_block_number'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build the delete array
							$x = 0;
							foreach ($call_block_numbers as $call_block_uuid => $call_block_number) {
								$array[$this->table][$x][$this->uuid_prefix.'uuid'] = $call_block_uuid;
								$array[$this->table][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
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

								//clear the cache
									$cache = new cache;
									foreach ($call_block_numbers as $call_block_number) {
										$cache->delete("app:call_block:".$_SESSION['domain_name'].":".$call_block_number);
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
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select ".$this->uuid_prefix."uuid as uuid, ".$this->toggle_field." as toggle, call_block_number from v_".$this->table." ";
								$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
								$sql .= "and ".$this->uuid_prefix."uuid in (".implode(', ', $uuids).") ";
								$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								if (is_array($rows) && @sizeof($rows) != 0) {
									foreach ($rows as $row) {
										$states[$row['uuid']] = $row['toggle'];
										$call_block_numbers[] = $row['call_block_number'];
									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//build update array
							$x = 0;
							foreach ($states as $uuid => $state) {
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
									$cache = new cache;
									foreach ($call_block_numbers as $call_block_number) {
										$cache->delete("app:call_block:".$_SESSION['domain_name'].":".$call_block_number);
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
							foreach ($records as $x => $record) {
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
									foreach ($rows as $x => $row) {

										//copy data
											$array[$this->table][$x] = $row;

										//overwrite
											$array[$this->table][$x][$this->uuid_prefix.'uuid'] = uuid();
											$array[$this->table][$x]['call_block_description'] = trim($row['call_block_description'].' ('.$text['label-copy'].')');

									}
								}
								unset($sql, $parameters, $rows, $row);
							}

						//save the changes and set the message
							if (is_array($array) && @sizeof($array) != 0) {

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									unset($array);

								//set message
									message::add($text['message-copy']);

							}
							unset($records);
					}

			}
		}

		/**
		 * add records
		 */
		public function add($records) {
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

				//add the checked records
					if (is_array($records) && @sizeof($records) != 0) {

						//filter checked records
							foreach ($records as $x => $record) {
								if ($record['checked'] == 'true' && is_uuid($record['uuid'])) {
									$uuids[] = "'".$record['uuid']."'";
								}
							}

						//get the caller id info from cdrs
							if (is_array($uuids) && @sizeof($uuids) != 0) {
								$sql = "select caller_id_name, caller_id_number, caller_destination from v_xml_cdr ";
								$sql .= "where xml_cdr_uuid in (".implode(', ', $uuids).") ";
								$database = new database;
								$rows = $database->select($sql, $parameters, 'all');
								unset($sql);
							}

						//loop through records
							if (is_array($rows) && @sizeof($rows) != 0) {
								foreach ($rows as $x => $row) {

									//build insert array
										if (permission_exists('call_block_all')) {
											$array['call_block'][$x]['call_block_uuid'] = uuid();
											$array['call_block'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
											$array['call_block'][$x]['call_block_direction'] = $this->call_block_direction;
											if (is_uuid($this->extension_uuid)) {
												$array['call_block'][$x]['extension_uuid'] = $this->extension_uuid;
											}
											if ($this->call_block_direction == 'inbound') {
												//remove e.164 and country code
												if (trim($row["caller_id_number"])[0] == "+") {
													//format e.164
													$call_block_number = str_replace("+".trim($_SESSION['domain']['country_code']['numeric']), "", trim($row["caller_id_number"]));
												} else {
													//remove the country code if its the first in the string
													$call_block_number = ltrim(trim($row["caller_id_number"]),$_SESSION['domain']['country_code']['numeric']);
												}
												//build the array
												$array['call_block'][$x]['call_block_country_code'] = trim($_SESSION['domain']['country_code']['numeric']);
												$array['call_block'][$x]['call_block_name'] = trim($row["caller_id_name"]);
												$array['call_block'][$x]['call_block_number'] = $call_block_number;
												$array['call_block'][$x]['call_block_description'] = trim($row["caller_id_name"]);
											}
											if ($this->call_block_direction == 'outbound') {
												$array['call_block'][$x]['call_block_number'] = trim($row["caller_destination"]);
											}
											$array['call_block'][$x]['call_block_count'] = 0;
											$array['call_block'][$x]['call_block_app'] = $this->call_block_app;
											$array['call_block'][$x]['call_block_data'] = $this->call_block_data;
											$array['call_block'][$x]['call_block_enabled'] = 'true';
											$array['call_block'][$x]['date_added'] = time();
											$x++;
										}
										else {
											if (is_array($_SESSION['user']['extension'])) {
												foreach ($_SESSION['user']['extension'] as $field) {
													if (is_uuid($field['extension_uuid'])) {
														$array['call_block'][$x]['call_block_uuid'] = uuid();
														$array['call_block'][$x]['domain_uuid'] = $_SESSION['domain_uuid'];
														$array['call_block'][$x]['call_block_direction'] = $this->call_block_direction;
														$array['call_block'][$x]['extension_uuid'] = $field['extension_uuid'];
														if ($this->call_block_direction == 'inbound') {
															//remove e.164 and country code
															$call_block_number = str_replace("+".trim($_SESSION['domain']['country_code']['numeric']), "", trim($row["caller_id_number"]));

															//build the array
															$array['call_block'][$x]['call_block_name'] = trim($row["caller_id_name"]);
															$array['call_block'][$x]['call_block_number'] = $call_block_number;
															$array['call_block'][$x]['call_block_description'] = trim($row["caller_id_name"]);
														}
														if ($this->call_block_direction == 'outbound') {
															$array['call_block'][$x]['call_block_number'] = trim($row["caller_destination"]);
														}
														$array['call_block'][$x]['call_block_count'] = 0;
														$array['call_block'][$x]['call_block_app'] = $this->call_block_app;
														$array['call_block'][$x]['call_block_data'] = $this->call_block_data;
														$array['call_block'][$x]['call_block_enabled'] = 'true';
														$array['call_block'][$x]['date_added'] = time();
														$x++;
													}
												}
											}
										}

								}
							}

						//add records
							if (is_array($array) && @sizeof($array) != 0) {

								//ensure call block is enabled in the dialplan (build update array)
									$sql = "select dialplan_uuid from v_dialplans ";
									$sql .= "where domain_uuid = :domain_uuid ";
									$sql .= "and app_uuid = '".$this->app_uuid."' ";
									$sql .= "and dialplan_enabled <> 'true' ";
									$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
									$database = new database;
									$rows = $database->select($sql, $parameters);
									if (is_array($rows) && @sizeof($rows) != 0) {
										foreach ($rows as $x => $row) {
											$array['dialplans'][$x]['dialplan_uuid'] = $row['dialplan_uuid'];
											$array['dialplans'][$x]['dialplan_enabled'] = 'true';
										}
									}
									unset($rows, $parameters);

								//grant temporary permissions
									$p = new permissions;
									$p->add('dialplan_edit', 'temp');

								//save the array
									$database = new database;
									$database->app_name = $this->app_name;
									$database->app_uuid = $this->app_uuid;
									$database->save($array);
									$response = $database->message;
									unset($array);

								//revoke temporary permissions
									$p->delete('dialplan_edit', 'temp');

								//set message
									message::add($text['message-add']);

							}

					}

			}
		} //method

	} //class
}

?>
