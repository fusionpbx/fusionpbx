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
	Copyright (C) 2014-2016
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
			//application 
				public $app_name;
				public $app_uuid;

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
								$sql .= "ORDER BY ".$array['order_by']." ";
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
						$message["message"] = "Forbidden, does not have '".$this->singular($this->name)."_delete'";
						$message["code"] = "403";
						$message["line"] = __line__;
						$this->message = $message;
						$m++;
					}
			}

			private function normalize_array($array, $name) {
				//get the depth of the array
					$depth = $this->array_depth($array);
				//before normalizing the array
					//echo "before: ".$depth."<br />\n";
					//echo "<pre>\n";
					//print_r($array);
					//echo "</pre>\n";
				//normalize the array
					if ($depth == 1) {
						$return_array[$name][] = $array;
					} else if ($depth == 2) {
						$return_array[$name] = $array;
					//} else if ($depth == 3) {
					//	$return_array[$name][] = $array;
					} else {
						$return_array = $array;
					}
					unset($array);
				//after normalizing the array
					$depth = $this->array_depth($new_array);
					//echo "after: ".$depth."<br />\n";
					//echo "<pre>\n";
					//print_r($new_array);
					//echo "</pre>\n";
				//return the array
					return $return_array;
			}

			public function save($array) {

				//return the array
					if (!is_array($array)) { echo "not an array"; return false; }

				//set the message id
					$m = 0;

				//set the app name
					if (!isset($this->app_name)) {
						$this->app_name = $this->name;
					}

				//normalize the array structure
					//$new_array = $this->normalize_array($array, $this->name);
					//unset($array);
					$new_array = $array;

				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}

				//debug sql
					$this->debug["sql"] = true;

				//start the atomic transaction
//					$this->db->beginTransaction();

				//debug info
					//echo "<pre>\n";
					//print_r($new_array);
					//echo "</pre>\n";
					//exit;

				//loop through the array
					foreach ($new_array as $schema_name => $schema_array) {

						$this->name = $schema_name;
						foreach ($schema_array as $schema_id => $array) {

							//set the variables
								$table_name = "v_".$this->name;
								$parent_key_name = $this->singular($this->name)."_uuid";

							//if the uuid is set then set parent key exists and value 
								//determine if the parent_key_exists
								$parent_key_exists = false;
								if (isset($array[$parent_key_name])) {
									$this->uuid = $array[$parent_key_name];
									$parent_key_value = $this->uuid;
									$parent_key_exists = true;
								}
								else {
									if (isset($this->uuid)) {
										$parent_key_exists = true;
										$parent_key_value = $this->uuid;
									}
									else {
										$parent_key_value = uuid();
									}
								}

							//get the parent field names
								$parent_field_names = array();
								foreach ($array as $key => $value) {
									if (!is_array($value)) {
										$parent_field_names[] = $key;
									}
								}

							//determine action update or delete and get the original data
								if ($parent_key_exists) {
									$sql = "SELECT ".implode(", ", $parent_field_names)." FROM ".$table_name." ";
									$sql .= "WHERE ".$parent_key_name." = '".$this->uuid."' ";
									$prep_statement = $this->db->prepare($sql);
									if ($prep_statement) {
										//get the data
											try {
												$prep_statement->execute();
												$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
											}
											catch(PDOException $e) {
												echo 'Caught exception: ',  $e->getMessage(), "<br/><br/>\n";
												echo $sql;
												exit;
											}

										//set the action
											if (count($result) > 0) {
												$action = "update";
												$old_array[$schema_name] = $result;
											}
											else {
												$action = "add";
											}
									}
									unset($prep_statement);
									unset($result);
								}
								else {
									$action = "add";
								}

							//add a record
								if ($action == "add") {

									if (permission_exists($this->singular($this->name).'_add')) {

											$sql = "INSERT INTO v_".$this->name." ";
											$sql .= "(";
											if (!$parent_key_exists) {
												$sql .= $parent_key_name.", ";
											}
											//foreach ($parent_field_names as $field_name) {
											//		$sql .= check_str($field_name).", ";
											//}
											foreach ($array as $array_key => $array_value) {
												if (!is_array($array_value)) {
													$sql .= check_str($array_key).", ";
												}
											}
											$sql .= ") ";
											$sql .= "VALUES ";
											$sql .= "(";
											if (!$parent_key_exists) {
												$sql .= "'".$parent_key_value."', ";
											}
											foreach ($array as $array_key => $array_value) {
												if (!is_array($array_value)) {
													if (strlen($array_value) == 0) {
														$sql .= "null, ";
													}
													else {
														$sql .= "'".check_str($array_value)."', ";
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
									}
									else {
										$message["name"] = $this->name;
										$message["message"] = "Forbidden, does not have '".$this->singular($this->name)."_add'";
										$message["code"] = "403";
										$message["line"] = __line__;
										$this->message[] = $message;
										$m++;
									}
								}

							//edit a specific uuid
								if ($action == "update") {
									if (permission_exists($this->singular($this->name).'_edit')) {

										//parent data
											$sql = "UPDATE v_".$this->name." SET ";
											foreach ($array as $array_key => $array_value) {
												if (!is_array($array_value) && $array_key != $parent_key_name) {
													if (strlen($array_value) == 0) {
														$sql .= check_str($array_key)." = null, ";
													}
													else {
														$sql .= check_str($array_key)." = '".check_str($array_value)."', ";
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
									}
									else {
										$message["name"] = $this->name;
										$message["message"] = "Forbidden, does not have '".$this->singular($this->name)."_edit'";
										$message["code"] = "403";
										$message["line"] = __line__;
										$this->message = $message;
										$m++;
									}
								}

							//unset the variables
								unset($sql, $action);

							//child data
								foreach ($array as $key => $value) {

									if (is_array($value)) {
											$table_name = "v_".$key;

											foreach ($value as $id => $row) {
												//prepare the variables
													$child_name = $this->singular($key);
													$child_key_name = $child_name."_uuid";
			
												//determine if the parent key exists in the child array
													$parent_key_exists = false;
													if (!isset($array[$parent_key_name])) {
														$parent_key_exists = true;
													}

												//determine if the uuid exists
													$uuid_exists = false;
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

												//get the child field names
													$child_field_names = array();
													foreach ($row as $k => $v) {
														if (!is_array($v)) {
															$child_field_names[] = $k;
														}
													}

												//determine sql update or delete and get the original data
													if ($uuid_exists) {
														$sql = "SELECT ". implode(", ", $child_field_names)." FROM ".$table_name." ";
														$sql .= "WHERE ".$child_key_name." = '".$child_key_value."' ";
														$prep_statement = $this->db->prepare($sql);
														if ($prep_statement) {
															//get the data
																$prep_statement->execute();
																$child_array = $prep_statement->fetch(PDO::FETCH_ASSOC);
															//set the action
																if (is_array($child_array)) {
																	$action = "update";
																}
																else {
																	$action = "add";
																}
															//add to the parent array
																if (is_array($child_array)) {
																	$old_array[$schema_name][$schema_id][$key][] = $child_array;
																}
														}
														unset($prep_statement);
													}
													else {
														$action = "add";
													}

												//update the data
													if ($action == "update") {
														if (permission_exists($child_name.'_edit')) {
															$sql = "UPDATE ".$table_name." SET ";
															foreach ($row as $k => $v) {
																//if (!is_array($v) && $k != $child_key_name) { //original
																if (!is_array($v) && ($k != $parent_key_name || $k != $child_key_name)) {
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
														else {
															$message["name"] = $child_name;
															$message["message"] = "Forbidden, does not have '${child_name}_edit'";
															$message["code"] = "403";
															$message["line"] = __line__;
															$this->message = $message;
															$m++;
														}
													} //action update

											//add the data
												if ($action == "add") {
													if (permission_exists($child_name.'_add')) {
														//determine if child or parent key exists
														$child_key_name = $this->singular($child_name).'_uuid';
														$parent_key_exists = false;
														$child_key_exists = false;
														foreach ($row as $k => $v) {
															if ($k == $parent_key_name) {
																$parent_key_exists = true; 
															}
															if ($k == $child_key_name) {
																$child_key_exists = true;
																$child_key_value = $v;
															}
														}
														if (!$child_key_value) {
															$child_key_value = uuid();
														}
														//build the insert
														$sql = "INSERT INTO ".$table_name." ";
														$sql .= "(";
														if (!$parent_key_exists) {
															$sql .= $this->singular($parent_key_name).", ";
														}
														if (!$child_key_exists) {
															$sql .= $this->singular($child_key_name).", ";
														}
														foreach ($row as $k => $v) {
															if (!is_array($v)) {
																$sql .= check_str($k).", ";
															}
														}
														$sql .= ") ";
														$sql .= "VALUES ";
														$sql .= "(";
														if (!$parent_key_exists) {
															$sql .= "'".$parent_key_value."', ";
														}
														if (!$child_key_exists) {
															$sql .= "'".$child_key_value."', ";
														}
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
													else {
														$message["name"] = $child_name;
														$message["message"] = "Forbidden, does not have '${child_name}_add'";
														$message["code"] = "403";
														$message["line"] = __line__;
														$this->message = $message;
														$m++;
													}
												} //action add

											//unset the variables
												unset($sql, $action, $child_key_name, $child_key_value);
										} // foreach value

									} //is array
								} //foreach array

						} // foreach schema_array
					}  // foreach main array

				//return the before and after data
					//log this in the future
					if (is_array($old_array)) {
						//normalize the array structure
							//$old_array = $this->normalize_array($old_array, $this->name);

						//debug info
							//echo "<pre>\n";
							//print_r($old_array);
							//echo "</pre>\n";
							//exit;
					}
					//$message["new"] = $new_array;
					//$message["new"]["md5"] = md5(json_encode($new_array));
					$this->message = $message;

				//commit the atomic transaction
//					$this->db->commit();

				//get the domain uuid
					$domain_uuid = $_SESSION['domain_uuid'];

				//log the transaction results
					if (file_exists($_SERVER["PROJECT_ROOT"]."/app/database_transactions/app_config.php")) {
						$sql = "insert into v_database_transactions ";
						$sql .= "(";
						$sql .= "database_transaction_uuid, ";
						$sql .= "domain_uuid, ";
						$sql .= "user_uuid, ";
						if (isset($this->app_uuid)) {
							$sql .= "app_uuid, ";
						}
						$sql .= "app_name, ";
						$sql .= "transaction_code, ";
						$sql .= "transaction_address, ";
						//$sql .= "transaction_type, ";
						$sql .= "transaction_date, ";
						$sql .= "transaction_old, ";
						$sql .= "transaction_new, ";
						$sql .= "transaction_result ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".uuid()."', ";
						$sql .= "'".$domain_uuid."', ";
						$sql .= "'".$_SESSION['user_uuid']."', ";
						if (isset($this->app_uuid)) {
							$sql .= "'".$this->app_uuid."', ";
						}
						$sql .= "'".$this->app_name."', ";
						$sql .= "'".$message["code"]."', ";
						$sql .= "'".$_SERVER['REMOTE_ADDR']."', ";
						//$sql .= "'$transaction_type', ";
						$sql .= "now(), ";
						$sql .= "'".check_str(json_encode($old_array, JSON_PRETTY_PRINT))."', ";
						$sql .= "'".check_str(json_encode($new_array, JSON_PRETTY_PRINT))."', ";
						$sql .= "'".check_str(json_encode($this->message, JSON_PRETTY_PRINT))."' ";
						$sql .= ")";
						$this->db->exec(check_sql($sql));
						unset($sql);
					}
			} //save method

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

			public function array_depth($array) {
				if (is_array($array)) {
					foreach ($array as $value) {
						if (!isset($depth)) { $depth = 1; }
						if (is_array($value)) {
							$depth = $this->array_depth($value) + 1;
						}
					}
				}
				else {
					$depth = 0;
				}
				return $depth;
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
