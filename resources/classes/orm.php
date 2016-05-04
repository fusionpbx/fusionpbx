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
	Copyright (C) 2014
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//define the orm class
	if (!class_exists('orm')) {
		class orm extends database {
			//factory - sets the model_name
			//set - sets the array
				public $name;
			//get - get the results
				public $result;
			//find
				public $uuid;
				//public $name;
				public $where;
				public $limit;
				public $offset;
			//save
				//public $uuid;
				//public $name;
				public $message;
				public $debug;
			//delete
				//public $uuid;
				//public $name;
				//public $where;
				//public $message;

			public function factory($name) {
				$this->name = $name;
				return $this;
			}

			public function name($name) {
				$this->name = $name;
				return $this;
			}

			public function uuid($uuid) {
				$this->uuid = $uuid;
				return $this;
			}

			public function set($array) {
				foreach ($array as $key => $value) {
					//public $this->$$key = $value;
				}
				return $this;
			}

			public function get() {
				return $this->result;
			}

			public function find() {

				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}
				//set the name
					if (isset($array['name'])) {
						$this->name = $array['name'];
					}
				//set the uuid
					if (isset($array['uuid'])) {
						$this->uuid = $array['uuid'];
					}
				//build the query
					$sql = "SELECT * FROM v_".$this->name." ";
					if (isset($this->uuid)) {
						//get the specific uuid
							$sql .= "WHERE ".$this->singular($this->name)."_uuid = '".$this->uuid."' ";
					}
					else {
						//where
							if (is_array($array['where'])) {
								$i = 0;
								foreach($array['where'] as $row) {
									if ($i == 0) {
										$sql .= "WHERE ".$row['name']." ".$row['operator']." '".$row['value']."' ";
									}
									else {
										$sql .= "AND ".$row['name']." ".$row['operator']." '".$row['value']."' ";
									}
									$i++;
								}
							}
						//order by
							if (is_array($array['order_by'])) {
								$sql .= "order by ".$array['order_by']." ";
							}
						//limit
							if (isset($array['limit'])) {
								$sql .= "LIMIT ".$array['limit']." ";
							}
						//offset
							if (isset($array['offset'])) {
								$sql .= "OFFSET ".$array['offset']." ";
							}
					}
				//execute the query, and return the results
					try {
						$prep_statement = $this->db->prepare(check_sql($sql));
						$prep_statement->execute();
						$message["message"] = "OK";
						$message["code"] = "200";
						$message["details"][$m]["name"] = $this->name;
						$message["details"][$m]["message"] = "OK";
						$message["details"][$m]["code"] = "200";
						if ($this->debug["sql"]) {
							$message["details"][$m]["sql"] = $sql;
						}
						$this->message = $message;
						$this->result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
						unset($prep_statement);
						$m++;
						return $this;
					}
					catch(PDOException $e) {
						$message["message"] = "Bad Request";
						$message["code"] = "400";
						$message["details"][$m]["name"] = $this->name;
						$message["details"][$m]["message"] = $e->getMessage();
						$message["details"][$m]["code"] = "400";
						if ($this->debug["sql"]) {
							$message["details"][$m]["sql"] = $sql;
						}
						$this->message = $message;
						$this->result = '';
						$m++;
						return $this;
					}
			}

			public function delete($uuid = null, $array = null) {
				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}

				//delete a specific uuid
					if (permission_exists($this->singular($this->name).'_delete')) {
						if (isset($api_uuid)) {
							//start the atomic transaction
								$this->db->beginTransaction();
							//delete the primary data
								$primary_key_name = $this->singular($this->name)."_uuid";
								$sql = "DELETE FROM v_".$this->name." ";
								$sql .= "WHERE ".$this->singular($this->name)."_uuid = '".$uuid."' ";
								$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
								try {
									$this->db->query(check_sql($sql));
									$message["message"] = "OK";
									$message["code"] = "200";
									$message["details"][$m]["name"] = $this->name;
									$message["details"][$m]["message"] = "OK";
									$message["details"][$m]["code"] = "200";
									if ($this->debug["sql"]) {
										$message["details"][$m]["sql"] = $sql;
									}
									$this->message = $message;
									$m++;
								}
								catch(PDOException $e) {
									$message["message"] = "Bad Request";
									$message["code"] = "400";
									$message["details"][$m]["name"] = $this->name;
									$message["details"][$m]["message"] = $e->getMessage();
									$message["details"][$m]["code"] = "400";
									if ($this->debug["sql"]) {
										$message["details"][$m]["sql"] = $sql;
									}
									$this->message = $message;
									$m++;
								}
							//delete the related data
								$relations = $this->get_relations($this->name);
								foreach ($relations as &$row) {
									$schema_name = $row['table'];
									if (substr($schema_name, 0,2) == "v_") {
										$schema_name = substr($schema_name, 2);
									}
									if (permission_exists($this->singular($schema_name).'_delete')) {
										$sql = "DELETE FROM ".$row['table']." ";
										$sql .= "WHERE ".$row['key']['field']." = '".$uuid."' ";
										$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
										try {
											$this->db->query(check_sql($sql));
											$message["details"][$m]["name"] = $schema_name;
											$message["details"][$m]["message"] = "OK";
											$message["details"][$m]["code"] = "200";
											if ($this->debug["sql"]) {
												$message["details"][$m]["sql"] = $sql;
											}
											$this->message = $message;
											$m++;
										}
										catch(PDOException $e) {
											if ($message["code"] = "200") {
												$message["message"] = "Bad Request";
												$message["code"] = "400";
											}
											$message["details"][$m]["name"] = $schema_name;
											$message["details"][$m]["message"] = $e->getMessage();
											$message["details"][$m]["code"] = "400";
											if ($this->debug["sql"]) {
												$message["details"][$m]["sql"] = $sql;
											}
											$this->message = $message;
											$m++;
										}
										unset ($sql);
									}
								}
							//commit the atomic transaction
								if ($message["code"] == "200") {
									$this->db->commit();
								}
						}
					}
					else {
						$message["name"] = $this->name;
						$message["message"] = "Forbidden";
						$message["code"] = "403";
						$this->message = $message;
						$m++;
					}
			}

			public function save($array) {
				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}

				//debug sql
					$this->debug["sql"] = true;

				//set the variables
					$table_name = "v_".$this->name;
					$parent_key_name = $this->singular($this->name)."_uuid";

				//get the number of rows
					if (isset($this->uuid)) {
						$sql = "SELECT count(*) AS num_rows FROM ".$table_name." ";
						$sql .= "WHERE ".$parent_key_name." = '".$this->uuid."' ";
						$prep_statement = $this->db->prepare($sql);
						if ($prep_statement) {
							$prep_statement->execute();
							$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
							if ($row['num_rows'] > 0) {
								$action = "update";
							}
							else {
								$action = "add";
							}
						}
						unset($prep_statement);
					}
					else {
						$action = "add";
					}

				//add a record
        				//set the message index
					$m = 0;
					if ($action == "add") {
						if (permission_exists($this->singular($this->name).'_add')) {
							//start the atomic transaction
								$this->db->beginTransaction();


							//parent data
								if (isset($this->uuid)) {
									$parent_key_value = $this->uuid;
								}
								else {
									$parent_key_value = uuid();
								}
								$sql = "INSERT INTO v_".$this->name." ";
								$sql .= "(";
								$sql .= $parent_key_name.", ";
								foreach ($array as $key => $value) {
									if (!is_array($value)) {
										$sql .= check_str($key).", ";
									}
								}
								$sql .= ") ";
								$sql .= "VALUES ";
								$sql .= "(";
								$sql .= "'".$parent_key_value."', ";
								foreach ($array as $key => $value) {
									if (!is_array($value)) {
										if (strlen($value) == 0) {
											$sql .= "null, ";
										}
										else {
											$sql .= "'".check_str($value)."', ";
										}
									}
								}
								$sql .= ");";
								$sql = str_replace(", )", ")", $sql);
								$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
								try {
									$this->db->query(check_sql($sql));

									$message["message"] = "OK";
									$message["code"] = "200";
									$message["uuid"] = $parent_key_value;
									$message["details"][$m]["name"] = $this->name;
									$message["details"][$m]["message"] = "OK";
									$message["details"][$m]["code"] = "200";
									$message["details"][$m]["uuid"] = $parent_key_value;
									if ($this->debug["sql"]) {
										$message["details"][$m]["sql"] = $sql;
									}
									$this->message = $message;
									$m++;
								}
								catch(PDOException $e) {
									$message["message"] = "Bad Request";
									$message["code"] = "400";
									$message["details"][$m]["name"] = $this->name;
									$message["details"][$m]["message"] = $e->getMessage();
									$message["details"][$m]["code"] = "400";
									if ($this->debug["sql"]) {
										$message["details"][$m]["sql"] = $sql;
									}
									$this->message = $message;
									$m++;
								}
								unset($sql);

							//child data
								foreach ($array as $key => $value) {
									if (is_array($value)) {
										if (permission_exists($this->singular($key).'_add')) {
											$table_name = "v_".$key;
											foreach ($value as $row) {
												//prepare the variables
													$child_key_name = $this->singular($key)."_uuid";
												//uuid_exists true / false
													$uuid_exists = false;
													$child_key_value = uuid();
													foreach ($row as $k => $v) {
														if ($child_key_name == $k) {
															if (strlen($v) > 0) {
																$child_key_value = $v;
																$uuid_exists = true;
																break;
															}
														}
														else {
															$uuid_exists = false;
														}
													}
												//add the data
													$sql = "INSERT INTO ".$table_name." ";
													$sql .= "(";
													$sql .= $parent_key_name.", ";
													$sql .= $child_key_name.", ";
													foreach ($row as $k => $v) {
														if (!is_array($v)) {
															if ($k != $child_key_name) {
																$sql .= check_str($k).", ";
															}
														}
													}
													$sql .= ") ";
													$sql .= "VALUES ";
													$sql .= "(";
													$sql .= "'".$parent_key_value."', ";
													$sql .= "'".$child_key_value."', ";
													foreach ($row as $k => $v) {
														if (!is_array($v)) {
															if ($k != $child_key_name) {
																if (strlen($v) == 0) {
																	$sql .= "null, ";
																}
																else {
																	$sql .= "'".check_str($v)."', ";
																}
															}
														}
													}
													$sql .= ");";
													$sql = str_replace(", )", ")", $sql);
													$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
													try {
														$this->db->query(check_sql($sql));
														$message["details"][$m]["name"] = $key;
														$message["details"][$m]["message"] = "OK";
														$message["details"][$m]["code"] = "200";
														$message["details"][$m]["uuid"] = $child_key_value;
														if ($this->debug["sql"]) {
															$message["details"][$m]["sql"] = $sql;
														}
														$this->message = $message;
														$m++;
														unset($sql);
													}
													catch(PDOException $e) {
														if ($message["code"] = "200") {
															$message["message"] = "Bad Request";
															$message["code"] = "400";
														}
														$message["details"][$m]["name"] = $key;
														$message["details"][$m]["message"] = $e->getMessage();
														$message["details"][$m]["code"] = "400";
														if ($this->debug["sql"]) {
															$message["details"][$m]["sql"] = $sql;
														}
														$this->message = $message;
													}
											}
										}
									}
								}

							//commit the atomic transaction
								if ($message["code"] == "200") {
									$this->db->commit();
								}
						}
						else {
							$message["name"] = $this->name;
							$message["message"] = "Forbidden";
							$message["code"] = "403";
							$this->message = $message;
							$m++;
						}
					}

				//edit a specific uuid
					if ($action == "update") {
						if (permission_exists($this->singular($this->name).'_edit')) {

							//start the atomic transaction
								$this->db->beginTransaction();

							//parent data
								$parent_key_value = $this->uuid;
								$sql = "UPDATE v_".$this->name." SET ";
								foreach ($array as $key => $value) {
									if (!is_array($value) && $key != $parent_key_name) {
										if (strlen($value) == 0) {
											$sql .= check_str($key)." = null, ";
										}
										else {
											$sql .= check_str($key)." = '".check_str($value)."', ";
										}
									}
								}
								$sql .= "WHERE ".$parent_key_name." = '".$parent_key_value."' ";
								$sql = str_replace(", WHERE", " WHERE", $sql);
								$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
								try {
									$this->db->query(check_sql($sql));
									$message["message"] = "OK";
									$message["code"] = "200";
									$message["details"][$m]["name"] = $this->name;
									$message["details"][$m]["message"] = "OK";
									$message["details"][$m]["code"] = "200";
									if ($this->debug["sql"]) {
										$message["details"][$m]["sql"] = $sql;
									}
									$this->message = $message;
									$m++;
									unset($sql);
								}
								catch(PDOException $e) {
									$message["message"] = "Bad Request";
									$message["code"] = "400";
									$message["details"][$m]["name"] = $this->name;
									$message["details"][$m]["message"] = $e->getMessage();
									$message["details"][$m]["code"] = "400";
									if ($this->debug["sql"]) {
										$message["details"][$m]["sql"] = $sql;
									}
									$this->message = $message;
									$m++;
								}

							//child data
								foreach ($array as $key => $value) {
									if (is_array($value)) {
										$table_name = "v_".$key;
										foreach ($value as $row) {
											//prepare the variables
												$child_name = $this->singular($key);
												$child_key_name = $child_name."_uuid";

											//uuid_exists true / false
												$uuid_exists = false;
												$child_key_value = uuid();
												foreach ($row as $k => $v) {
													if ($child_key_name == $k) {
														if (strlen($v) > 0) {
															$child_key_value = $v;
															$uuid_exists = true;
															break;
														}
													}
													else {
														$uuid_exists = false;
													}
												}

											//update the data
												if ($uuid_exists) {
													//if (permission_exists($child_name.'_edit')) {
														$sql = "UPDATE ".$table_name." SET ";
														foreach ($row as $k => $v) {
															if (!is_array($v) && $k != $child_key_name) {
																if (strlen($v) == 0) {
																	$sql .= check_str($k)." = null, ";
																}
																else {
																	$sql .= check_str($k)." = '".check_str($v)."', ";
																}
															}
														}
														$sql .= "WHERE ".$parent_key_name." = '".$this->uuid."' ";
														$sql .= "AND ".$child_key_name." = '".$child_key_value."' ";
														$sql = str_replace(", WHERE", " WHERE", $sql);
														$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	//													if (strlen($child_key_value) > 0) {
															try {
																$this->db->query(check_sql($sql));
																$message["details"][$m]["name"] = $key;
																$message["details"][$m]["message"] = "OK";
																$message["details"][$m]["code"] = "200";
																if ($this->debug["sql"]) {
																	$message["details"][$m]["sql"] = $sql;
																}
																$this->message = $message;
																$m++;
															}
															catch(PDOException $e) {
																if ($message["code"] = "200") {
																	$message["message"] = "Bad Request";
																	$message["code"] = "400";
																}
																$message["details"][$m]["name"] = $key;
																$message["details"][$m]["message"] = $e->getMessage();
																$message["details"][$m]["code"] = "400";
																if ($this->debug["sql"]) {
																	$message["details"][$m]["sql"] = $sql;
																}
																$this->message = $message;
																$m++;
															}
	//													}
													//}
												}

											//add the data
												if (!$uuid_exists) {
													if (permission_exists($child_name.'_add')) {
														$sql = "INSERT INTO ".$table_name." ";
														$sql .= "(";
														$sql .= $this->singular($parent_key_name).", ";
														$sql .= $this->singular($child_key_name).", ";
														foreach ($row as $k => $v) {
															if (!is_array($v)) {
																$sql .= check_str($k).", ";
															}
														}
														$sql .= ") ";
														$sql .= "VALUES ";
														$sql .= "(";
														$sql .= "'".$parent_key_value."', ";
														$sql .= "'".$child_key_value."', ";
														foreach ($row as $k => $v) {
															if (!is_array($v)) {
																if (strlen($v) == 0) {
																	$sql .= "null, ";
																}
																else {
																	$sql .= "'".check_str($v)."', ";
																}
															}
														}
														$sql .= ");";
														$sql = str_replace(", )", ")", $sql);
														$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
														try {
															$this->db->query(check_sql($sql));
															$message["details"][$m]["name"] = $key;
															$message["details"][$m]["message"] = "OK";
															$message["details"][$m]["code"] = "200";
															$message["details"][$m]["uuid"] = $child_key_value;
															if ($this->debug["sql"]) {
																$message["details"][$m]["sql"] = $sql;
															}
															$this->message = $message;
															$m++;
														}
														catch(PDOException $e) {
															if ($message["code"] = "200") {
																$message["message"] = "Bad Request";
																$message["code"] = "400";
															}
															$message["details"][$m]["name"] = $key;
															$message["details"][$m]["message"] = $e->getMessage();
															$message["details"][$m]["code"] = "400";
															if ($this->debug["sql"]) {
																$message["details"][$m]["sql"] = $sql;
															}
															$this->message = $message;
															$m++;
														}
													}
												}

											//unset the sql variable
												unset($sql);
										}
									}
								}

							//commit the atomic transaction
								if ($message["code"] == "200") {
									$this->db->commit();
								}
						}
						else {
							$message["name"] = $this->name;
							$message["message"] = "Forbidden";
							$message["code"] = "403";
							$this->message = $message;
							$m++;
						}
					}
			}

			//define singular function to convert a word in english to singular
				private function singular($word) {
					//"-es" is used for words that end in "-x", "-s", "-z", "-sh", "-ch" in which case you add
					if (substr($word, -2) == "es") {
						if (substr($word, -3, 1) == "x") {
							return substr($word,0,-2);
						}
						if (substr($word, -3, 1) == "s") {
							return substr($word,0,-2);
						}
						elseif (substr($word, -3, 1) == "z") {
							return substr($word,0,-2);
						}
						elseif (substr($word, -4, 2) == "sh") {
							return substr($word,0,-2);
						}
						elseif (substr($word, -4, 2) == "ch") {
							return substr($word,0,-2);
						}
						else {
							return rtrim($word, "s");
						}
					}
					else {
						return rtrim($word, "s");
					}
				}

			public function get_apps() {
				//get the $apps array from the installed apps from the core and mod directories
					$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
					$x = 0;
					foreach ($config_list as &$config_path) {
						include($config_path);
						$x++;
					}
					$_SESSION['apps'] = $apps;
			}

			public function domain_uuid_exists($name) {
				//get the $apps array from the installed apps from the core and mod directories
					if (!is_array($_SESSION['apps'])) {
						$this->get_apps();
					}
				//search through all fields to see if domain_uuid exists
					foreach ($_SESSION['apps'] as $x => &$app) {
						foreach ($app['db'] as $y => &$row) {
							if ($row['table'] == $name) {
								foreach ($row['fields'] as $z => $field) {
									if ($field['name'] == "domain_uuid") {
										return true;
									}
								}
							}
						}
					}
				//not found
					return false;
			}
		}
	}

	//examples
		/*
		//get records
			$orm = new orm();
			$result = $orm->name('dialplans')->find()->get();
			print_r($result);

		//get a single record
			$orm = new orm();
			$orm->name('dialplans')
			$orm->uuid('a8363085-8318-4dee-b87f-0818be0d6318');
			$orm->find();
			$result = $orm->get();
			print_r($result);

		//get a single record
			$array['name'] = "dialplans";
			$array['uuid'] = "2d27e4a4-c954-4f8a-b734-88b0e1054b86";
			$orm = new orm();
			$result = $orm->find($array)->get();
			print_r($result);

		//get limited records with limit and offset
			$array['name'] = "dialplans";
			$array['limit'] = "10";
			$array['offset'] = "2";
			$orm = new orm();
			$result = $orm->find($array)->get();
			print_r($result);
		*/

?>