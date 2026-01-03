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
  Copyright (C) 2013 - 2026
  All Rights Reserved.

  Contributor(s):
  Mark J Crane <markjcrane@fusionpbx.com>
 */

//define the schema class
class schema {

	//define the public variables
	public $data_types;
	public $result;

	//define private variables
	private $database;
	private $applications;
	private $db_type;
	private $db_name;
	private $schema_info;

	/**
	 * Constructor for the class.
	 *
	 * This method initializes the object with setting_array and session data.
	 *
	 * @param array $setting_array An optional array of settings to override default values. Defaults to [].
	 */
	public function __construct($setting_array = []) {

		//set the global variables
		global $db_type, $db_name;

		//includes files
		require dirname(__DIR__, 2) . "/resources/require.php";

		//open a database connection
		$this->database = $setting_array['database'] ?? database::new();

		//get the list of installed apps from the core and mod directories
		$config_list = glob(dirname(__DIR__, 2) . "/*/*/app_config.php");
		$x = 0;
		foreach ($config_list as $config_path) {
			try {
				include($config_path);
			} catch (Exception $e) {
				//echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
			$x++;
		}
		$this->applications = $apps;

		//set the db_type
		$this->db_type = $db_type;

		//set the db_name
		$this->db_name = $db_name;

		//get the table info
		if ($this->db_type == "pgsql") {
			$sql = "SELECT *, ordinal_position, ";
			$sql .= "table_name, ";
			$sql .= "column_name, ";
			$sql .= "data_type, ";
			$sql .= "column_default, ";
			$sql .= "is_nullable, ";
			$sql .= "character_maximum_length, ";
			$sql .= "numeric_precision ";
			$sql .= "FROM information_schema.columns ";
			$sql .= "WHERE table_catalog = '" . $db_name . "' ";
			$sql .= "and table_schema not in ('pg_catalog', 'information_schema') ";
			$sql .= "ORDER BY ordinal_position; ";
			$schema = $this->database->select($sql, null, 'all');
			foreach ($schema as $row) {
				$this->schema_info[$row['table_name']][] = $row;
			}
		}
	}

	/**
	 * Generate SQL statements for creating tables.
	 *
	 * This method loops through the list of applications and generates CREATE TABLE
	 * SQL statements based on the table definitions provided in each application's database settings.
	 *
	 * @return array An array containing the generated SQL statements.
	 */
	public function sql() {
		$sql = '';
		$sql_schema = '';
		foreach ($this->applications as $app) {
			if (isset($app['db']) && count($app['db'])) {
				foreach ($app['db'] as $row) {
					//create the sql string
					$table_name = $row['table']['name'];
					$sql = "CREATE TABLE " . $row['table']['name'] . " (\n";
					$field_count = 0;
					foreach ($row['fields'] as $field) {
						if (!empty($field['deprecated']) and ($field['deprecated'] == "true")) {
							//skip this field
						} else {
							if ($field_count > 0) {
								$sql .= ",\n";
							}
							if (is_array($field['name'])) {
								$sql .= $field['name']['text'] . " ";
							} else {
								$sql .= $field['name'] . " ";
							}
							if (is_array($field['type'])) {
								$sql .= $field['type'][$this->db_type];
							} else {
								$sql .= $field['type'];
							}
							if (isset($field['key']) && isset($field['key']['type']) && ($field['key']['type'] == "primary")) {
								$sql .= " PRIMARY KEY";
							}
							if (isset($field['key']) && isset($field['key']['type']) && ($field['key']['type'] == "foreign")) {
								if ($this->db_type == "pgsql") {
									//$sql .= " references ".$field['key']['reference']['table']."(".$field['key']['reference']['field'].")";
								}
								if ($this->db_type == "sqlite") {
									//$sql .= " references ".$field['key']['reference']['table']."(".$field['key']['reference']['field'].")";
								}
								if ($this->db_type == "mysql") {
									//$sql .= " references ".$field['key']['reference']['table']."(".$field['key']['reference']['field'].")";
								}
							}
							$field_count++;
						}
					}
					if ($this->db_type == "mysql") {
						$sql .= ") ENGINE=INNODB;";
					} else {
						$sql .= ");";
					}
					$this->result['sql'][] = $sql;
					unset($sql);
				}
			}
		}
	}

	/**
	 * Executes the SQL queries in the result array.
	 *
	 * This method iterates over the SQL queries stored in the 'sql' key of the result array,
	 * executes each query, and commits the changes to the database. Any errors encountered
	 * during execution are caught and logged to the console.
	 *
	 * @return void
	 */
	public function exec() {
		foreach ($this->result['sql'] as $sql) {
			//start the sql transaction
			$this->database->beginTransaction();
			//execute the sql query
			try {
				$this->database->execute($sql, null);
			} catch (PDOException $error) {
				echo "error: " . $error->getMessage() . " sql: $sql<br/>";
			}
			//complete the transaction
			$this->database->commit();
		}
	}

	/**
	 * Generates the schema for the provided applications.
	 *
	 * This method iterates through the application configurations and database
	 * definitions to determine the table and field existence. The results are stored
	 * in the application configurations.
	 *
	 * @param string $format The output format (default: '').
	 *
	 * @return string
	 */
	public function schema($format = '') {

		//set the global variable
		global $text, $output_format;

		if ($format == '') {
			$format = $output_format;
		}

		//get the db variables
		//$config = new config;
		//$config_exists = $config->exists();
		//$config_path = $config->find();
		//$config->get();
		//$this->db_type = $config->db_type;
		//$this->db_name = $config->db_name;
		//$db_username = $config->db_username;
		//$db_password = $config->db_password;
		//$db_host = $config->db_host;
		//$db_path = $config->db_path;
		//$db_port = $config->db_port;
		//includes files

		//add multi-lingual support
		if (!isset($text)) {
			$language = new text;
			$text = $language->get(null, 'core/upgrade');
		}

		//PHP PDO check if table or column exists
		//check if table exists
		// SELECT * FROM sqlite_master WHERE type='table' AND name='v_cdr'
		//check if column exists
		// SELECT * FROM sqlite_master WHERE type='table' AND name='v_cdr' AND sql LIKE '%caller_id_name TEXT,%'
		//aditional information
		// http://www.sqlite.org/faq.html#q9
		//postgresql
		//list all tables in the database
		// SELECT table_name FROM pg_tables WHERE schemaname='public';
		//check if table exists
		// SELECT * FROM pg_tables WHERE schemaname='public' AND table_name = 'v_groups'
		//check if column exists
		// SELECT attname FROM pg_attribute WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = 'v_cdr') AND attname = 'caller_id_name';
		//mysql
		//list all tables in the database
		// SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = 'fusionpbx'
		//check if table exists
		// SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = 'fusionpbx' AND TABLE_NAME = 'v_groups'
		//check if column exists
		// SELECT * FROM information_schema.COLUMNS where TABLE_SCHEMA = 'fusionpbx' AND TABLE_NAME = 'v_cdr' AND COLUMN_NAME = 'context'
		//oracle
		//check if table exists
		// SELECT TABLE_NAME FROM ALL_TABLES
		//update the app db array add exists true or false
		$sql = '';
		foreach ($this->applications as $x => $app) {
			if (isset($app['db'])) {
				foreach ($app['db'] as $y => $row) {
					if (isset($row['table']['name'])) {
						if (is_array($row['table']['name'])) {
							$table_name = $row['table']['name']['text'];
						} else {
							$table_name = $row['table']['name'];
						}
					} else {
						//old array syntax
						if (is_array($row['table'])) {
							$table_name = $row['table']['text'];
						} else {
							$table_name = $row['table'];
						}
					}
					if (!empty($table_name)) {

						//check if the table exists
						if ($this->table_exists($table_name)) {
							$this->applications[$x]['db'][$y]['exists'] = 'true';
						} else {
							$this->applications[$x]['db'][$y]['exists'] = 'false';
						}
						//check if the column exists
						foreach ($row['fields'] as $z => $field) {
							if (!empty($field['deprecated']) && $field['deprecated'] == "true") {
								//skip this field
							} else {
								if (is_array($field['name'])) {
									$field_name = $field['name']['text'];
								} else {
									$field_name = $field['name'];
								}
								if (!empty($field_name)) {
									if ($this->column_exists($table_name, $field_name)) {
										//found
										$this->applications[$x]['db'][$y]['fields'][$z]['exists'] = 'true';
									} else {
										//not found
										$this->applications[$x]['db'][$y]['fields'][$z]['exists'] = 'false';
									}
								}
								unset($field_name);
							}
						}
						unset($table_name);
					}
				}
			}
		}

		//prepare the variables
		$sql_update = '';

		//add missing tables and fields
		foreach ($this->applications as $x => $app) {
			if (isset($app['db'])) {
				foreach ($app['db'] as $y => $row) {
					if (is_array($row['table']['name'])) {
						$table_name = $row['table']['name']['text'];
						if ($this->table_exists($row['table']['name']['deprecated'])) {
							$row['exists'] = "false"; //testing
							if ($this->db_type == "pgsql") {
								$sql_update .= "ALTER TABLE " . $row['table']['name']['deprecated'] . " RENAME TO " . $row['table']['name']['text'] . ";\n";
							}
							if ($this->db_type == "mysql") {
								$sql_update .= "RENAME TABLE " . $row['table']['name']['deprecated'] . " TO " . $row['table']['name']['text'] . ";\n";
							}
							if ($this->db_type == "sqlite") {
								$sql_update .= "ALTER TABLE " . $row['table']['name']['deprecated'] . " RENAME TO " . $row['table']['name']['text'] . ";\n";
							}
						} else {
							if ($this->table_exists($row['table']['name']['text'])) {
								$row['exists'] = "true";
							} else {
								$row['exists'] = "false";
								$sql_update .= $this->create_table($this->applications, $row['table']['name']['text']);
							}
						}
					} else {
						if ($this->table_exists($row['table']['name'])) {
							$row['exists'] = "true";
						} else {
							$row['exists'] = "false";
						}
						$table_name = $row['table']['name'];
					}

					//check if the table exists
					if ($row['exists'] == "true") {
						if (count($row['fields']) > 0) {
							foreach ($row['fields'] as $z => $field) {
								if (!empty($field['deprecated']) && $field['deprecated'] == "true") {
									//skip this field
								} else {
									//get the data type
									if (is_array($field['type'])) {
										$field_type = $field['type'][$this->db_type];
									} else {
										$field_type = $field['type'];
									}
									//get the field name
									if (is_array($field['name'])) {
										$field_name = $field['name']['text'];
									} else {
										$field_name = $field['name'];
									}

									//check if the field exists
									//	if ($this->column_exists($table_name, $field_name)) {
									//		$field['exists'] = "true";
									//	}
									//	else {
									//		$field['exists'] = "false";
									//	}
									//add or rename fields
									if (isset($field['name']['deprecated']) && $this->column_exists($table_name, $field['name']['deprecated'])) {
										if ($this->db_type == "pgsql") {
											$sql_update .= "ALTER TABLE " . $table_name . " RENAME COLUMN " . $field['name']['deprecated'] . " to " . $field['name']['text'] . ";\n";
										}
										if ($this->db_type == "mysql") {
											$field_type = str_replace("AUTO_INCREMENT PRIMARY KEY", "", $field_type);
											$sql_update .= "ALTER TABLE " . $table_name . " CHANGE " . $field['name']['deprecated'] . " " . $field['name']['text'] . " " . $field_type . ";\n";
										}
										if ($this->db_type == "sqlite") {
											//a change has been made to the field name
											$this->applications[$x]['db'][$y]['rebuild'] = 'true';
										}
									} else {
										//find missing fields and add them
										if ($field['exists'] == "false") {
											$sql_update .= "ALTER TABLE " . $table_name . " ADD " . $field_name . " " . $field_type . ";\n";
										}
									}

									//change the schema data types if needed
									//if the data type described in the app_config array is different than the type in the database then update the data type
									$db_field_type = $this->column_data_type($table_name, $field_name);
									$field_type_array = explode("(", $field_type);
									$field_type = $field_type_array[0];
									if (trim($db_field_type) != trim($field_type) && !empty($db_field_type)) {
										if ($this->db_type == "pgsql") {
											if (strtolower($field_type) == "uuid") {
												$sql_update .= "ALTER TABLE " . $table_name . " ALTER COLUMN " . $field_name . " TYPE uuid USING\n";
												$sql_update .= "CAST(regexp_replace(" . $field_name . ", '([A-Z0-9]{4})([A-Z0-9]{12})', E'\\1-\\2')\n";
												$sql_update .= "AS uuid);\n";
											} else {
												//field type has not changed
												if ($db_field_type == "integer" && strtolower($field_type) == "serial") {

												} elseif ($db_field_type == "timestamp without time zone" && strtolower($field_type) == "timestamp") {

												} elseif ($db_field_type == "timestamp without time zone" && strtolower($field_type) == "datetime") {

												} elseif ($db_field_type == "timestamp with time zone" && strtolower($field_type) == "timestamptz") {

												} elseif ($db_field_type == "integer" && strtolower($field_type) == "numeric") {

												} elseif ($db_field_type == "character" && strtolower($field_type) == "char") {

												} elseif ($db_field_type == "json" && strtolower($field_type) == "jsonb") {

												} //field type has changed
												else {
													switch ($field_type) {
														case 'numeric':
															$sql_update .= "ALTER TABLE " . $table_name . " ALTER COLUMN " . $field_name . " TYPE " . $field_type . " USING " . $field_name . "::numeric;\n";
															break;
														case 'timestamp':
															$sql_update .= "ALTER TABLE " . $table_name . " ALTER COLUMN " . $field_name . " TYPE " . $field_type . " USING " . $field_name . "::timestamp with time zone;\n";
															break;
														case 'datetime':
															$sql_update .= "ALTER TABLE " . $table_name . " ALTER COLUMN " . $field_name . " TYPE " . $field_type . " USING " . $field_name . "::timestamp without time zone;\n";
															break;
														case 'timestamptz':
															$sql_update .= "ALTER TABLE " . $table_name . " ALTER COLUMN " . $field_name . " TYPE " . $field_type . " USING " . $field_name . "::timestamp with time zone;\n";
															break;
														case 'boolean':
															if ($db_field_type == 'numeric') {
																$sql_update .= "ALTER TABLE " . $table_name . " ALTER COLUMN " . $field_name . " TYPE text USING " . $field_name . "::text;\n";
															}
															if ($db_field_type == 'text') {
																$sql_update .= "UPDATE " . $table_name . " set " . $field_name . " = 'false' where " . $field_name . " = '';\n";
															}
															$sql_update .= "ALTER TABLE " . $table_name . " ALTER COLUMN " . $field_name . " TYPE " . $field_type . " USING " . $field_name . "::boolean;\n";
															break;
														default:
															unset($using);
															$sql_update .= "ALTER TABLE " . $table_name . " ALTER COLUMN " . $field_name . " TYPE " . $field_type . "\n";
													}
												}
											}
										}
										if ($this->db_type == "mysql") {
											$type = explode("(", $db_field_type);
											if ($type[0] == $field_type) {
												//do nothing
											} elseif ($field_type == "numeric" && $type[0] == "decimal") {
												//do nothing
											} else {
												$sql_update .= "ALTER TABLE " . $table_name . " modify " . $field_name . " " . $field_type . ";\n";
											}
											unset($type);
										}
										if ($this->db_type == "sqlite") {
											//a change has been made to the field type
											$this->applications[$x]['db'][$y]['rebuild'] = 'true';
										}
									}
								}
							}
						}
					} elseif (!is_array($row['table']['name'])) {
						//create table
						$sql_update .= $this->create_table($this->applications, $row['table']['name']);
					}
				}
			}
		}
		//rebuild and populate the table
		foreach ($this->applications as $x => $app) {
			if (isset($app['db'])) {
				foreach ($app['db'] as $y => $row) {
					if (is_array($row['table']['name'])) {
						$table_name = $row['table']['name']['text'];
					} else {
						$table_name = $row['table']['name'];
					}
					if (!empty($field['rebuild']) && $row['rebuild'] == "true") {
						if ($this->db_type == "sqlite") {
							//start the transaction
							//$sql_update .= "BEGIN TRANSACTION;\n";
							//rename the table
							$sql_update .= "ALTER TABLE " . $table_name . " RENAME TO tmp_" . $table_name . ";\n";
							//create the table
							$sql_update .= $this->create_table($this->applications, $table_name);
							//insert the data into the new table
							$sql_update .= $this->insert_into($this->applications, $table_name);
							//drop the old table
							$sql_update .= "DROP TABLE tmp_" . $table_name . ";\n";
							//commit the transaction
							//$sql_update .= "COMMIT;\n";
						}
					}
				}
			}
		}

		// initialize response variable
		$response = '';

		//display results as html
		if ($format == "html") {
			//show the database type
			$response .= "<strong>" . $text['header-database_type'] . ": " . $this->db_type . "</strong><br /><br />";
			//start the table
			$response .= "<table width='100%' border='0' cellpadding='20' cellspacing='0'>\n";
			//show the changes
			if (!empty($sql_update)) {
				$response .= "<tr>\n";
				$response .= "<td class='row_style1' colspan='3'>\n";
				$response .= "<br />\n";
				$response .= "<strong>" . $text['label-sql_changes'] . ":</strong><br />\n";
				$response .= "<pre>\n";
				$response .= $sql_update;
				$response .= "</pre>\n";
				$response .= "<br />\n";
				$response .= "</td>\n";
				$response .= "</tr>\n";
			}
			//list all tables
			$response .= "<tr>\n";
			$response .= "<th>" . $text['label-table'] . "</th>\n";
			$response .= "<th>" . $text['label-exists'] . "</th>\n";
			$response .= "<th>" . $text['label-details'] . "</th>\n";
			$response .= "<tr>\n";
			//build the html while looping through the app db array
			$sql = '';
			foreach ($this->applications as $app) {
				if (isset($app['db'])) {
					foreach ($app['db'] as $row) {
						if (is_array($row['table']['name'])) {
							$table_name = $row['table']['name']['text'];
						} else {
							$table_name = $row['table']['name'];
						}
						$response .= "<tr>\n";

						//check if the table exists
						if ($row['exists'] == "true") {
							$response .= "<td valign='top' class='row_style1'>" . $table_name . "</td>\n";
							$response .= "<td valign='top' class='vncell' style='padding-top: 3px;'>" . $text['option-true'] . "</td>\n";

							if (count($row['fields']) > 0) {
								$response .= "<td class='row_style1'>\n";
								//show the list of columns
								$response .= "<table border='0' cellpadding='10' cellspacing='0'>\n";
								$response .= "<tr>\n";
								$response .= "<th>" . $text['label-name'] . "</th>\n";
								$response .= "<th>" . $text['label-type'] . "</th>\n";
								$response .= "<th>" . $text['label-exists'] . "</th>\n";
								$response .= "</tr>\n";
								foreach ($row['fields'] as $field) {
									if (!empty($field['deprecated']) && $field['deprecated'] == "true") {
										//skip this field
									} else {
										if (is_array($field['name'])) {
											$field_name = $field['name']['text'];
										} else {
											$field_name = $field['name'];
										}
										if (is_array($field['type'])) {
											$field_type = $field['type'][$this->db_type];
										} else {
											$field_type = $field['type'];
										}
										$response .= "<tr>\n";
										$response .= "<td class='row_style1' width='200'>" . $field_name . "</td>\n";
										$response .= "<td class='row_style1'>" . $field_type . "</td>\n";
										if ($field['exists'] == "true") {
											$response .= "<td class='row_style0' style=''>" . $text['option-true'] . "</td>\n";
											$response .= "<td>&nbsp;</td>\n";
										} else {
											$response .= "<td class='row_style1' style='background-color:#444444;color:#CCCCCC;'>" . $text['option-false'] . "</td>\n";
											$response .= "<td>&nbsp;</td>\n";
										}
										$response .= "</tr>\n";
									}
								}
								$response .= "	</table>\n";
								$response .= "</td>\n";
							}
						} else {
							$response .= "<td valign='top' class='row_style1'>$table_name</td>\n";
							$response .= "<td valign='top' class='row_style1' style='background-color:#444444;color:#CCCCCC;'><strong>" . $text['label-exists'] . "</strong><br />" . $text['option-false'] . "</td>\n";
							$response .= "<td valign='top' class='row_style1'>&nbsp;</td>\n";
						}
						$response .= "</tr>\n";
					}
				}
			}
			//end the list of tables
			$response .= "</table>\n";
			$response .= "<br />\n";
		}

		//loop line by line through all the lines of sql code
		$x = 0;
		if (empty($sql_update) && $format == "text") {
			$response .= "	" . $text['label-schema'] ?? '' . ":			" . $text['label-no_change'] . "\n";
		} else {
			if ($format == "text") {
				$response .= "	" . $text['label-schema'] . "\n";
			}
			//$this->db->beginTransaction();
			$update_array = explode(";", $sql_update);
			if (is_array($update_array) && count($update_array)) {
				//drop views so that alter table statements complete
				$result = $this->database->views('drop');

				foreach ($update_array as $sql) {
					if (strlen(trim($sql))) {
						try {
							$this->database->execute(trim($sql), null);
							if ($format == "text") {
								$response .= "	$sql;\n";
							}
						} catch (PDOException $error) {
							$response .= "	error: " . $error->getMessage() . "	sql: $sql\n";
						}
					}
				}
			}
			//$this->db->commit();
			$response .= "\n";
			unset($sql_update, $sql);
		}

		//refresh each postgresql subscription with its publication
		if ($this->db_type == "pgsql") {
			//get the list of postgresql subscriptions
			$sql = "select subname from pg_subscription; ";
			$pg_subscriptions = $this->database->select($sql, null, 'all');
			unset($sql, $parameters);

			//refresh each subscription publication
			foreach ($pg_subscriptions as $row) {
				$sql = "ALTER SUBSCRIPTION " . $row['subname'] . " REFRESH PUBLICATION;";
				$response .= $sql;
				$this->database->execute($sql);
			}
		}

		//create views so that alter table statements complete
		$this->database->views('create');

		//handle response
		return $response;

	}

	/**
	 * Checks if a table exists in the database.
	 *
	 * This method determines whether a specified table exists in the database,
	 * based on the current database type and available schema information.
	 *
	 * @param string $table_name The name of the table to check for existence.
	 *
	 * @return bool True if the table exists, false otherwise.
	 */
	private function table_exists($table_name) {
		if ($this->db_type == 'pgsql') {
			if (isset($this->schema_info[$table_name])) {
				return true;
			} else {
				return false;
			}
		}
		if ($this->db_type == 'sqlite' || $this->db_type == 'msyql') {
			$sql = "select count(*) from $table_name ";
			$result = $this->database->execute($sql, null);
			if ($result > 0) {
				return true; //table exists
			} else {
				return false; //table doesn't exist
			}
		}
	}

	/**
	 * Get the column default value from the database.
	 *
	 * This method gets the default column value from the database,
	 * based on the current database default and available schema information.
	 *
	 * @param string $table_name The name of the table.
	 * @param string $column_name The name of the column_name.
	 * @param string $type The name of the column_name.
	 *
	 * @return string Return the column default value, or an empty string for no default
	 */
	public function get_column_default($table_name, $column_name, $details = false) {
		if ($this->db_type == 'pgsql') {
			if (isset($this->schema_info[$table_name])) {
				$table_details = $this->schema_info[$table_name];
				foreach($table_details as $row) {
					if ($row['column_name'] == $column_name) {
						$column_default = $row['column_default'];
						if ($details) {
							return $column_default;
						}
						if (is_string($column_default)) {
							$column_default = explode('::', $column_default)[0];
							$column_default = trim($column_default, "'");
							return $column_default;
						}
					}
				}
			}
		}

		// if ($this->db_type == 'sqlite' || $this->db_type == 'msyql') {
		// 	$sql = "select count(*) from $table_name ";
		// 	$result = $this->database->execute($sql, null);
		// 	if ($result > 0) {
		// 		return true; //table exists
		// 	} else {
		// 		return ''; //table doesn't exist
		// 	}
		// }

		//return an empty string
		return '';
	}

	/**
	 * Check if a column exists in the specified table.
	 *
	 * This method performs database-specific checks to determine whether the given
	 * table_name and column_name exist. The method returns true if the column is found,
	 * and false otherwise.
	 *
	 * @param string $table_name  The name of the table to check for the column.
	 * @param string $column_name The name of the column to check for existence.
	 *
	 * @return bool True if the column exists, false otherwise.
	 */
	public function column_exists($table_name, $column_name) {
		if (empty($table_name)) {
			return false;
		}
		if (empty($column_name)) {
			return false;
		}
		if ($this->db_type == "sqlite") {
			$table_info = $this->table_info($table_name);
			if ($this->sqlite_column_exists($table_info, $column_name)) {
				return true;
			} else {
				return false;
			}
		}
		if ($this->db_type == "pgsql") {
			if (!isset($this->schema_info[$table_name])) {
				return false;
			}
			foreach ($this->schema_info[$table_name] as $row) {
				if ($row['column_name'] == $column_name) {
					return true;
				}
			}
			return false;
		}
		if ($this->db_type == "mysql") {
			//$sql .= "SELECT * FROM information_schema.COLUMNS where TABLE_SCHEMA = '".$this->db_name."' and TABLE_NAME = '$table_name' and COLUMN_NAME = '$column_name' ";
			$sql = "show columns from $table_name where field = '$column_name' ";
		}

		if ($sql) {
			$prep_statement = $this->database->db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			unset($prep_statement);
			if (!$result) {
				return false;
			}
			if (count($result) > 0) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Retrieves table information.
	 *
	 * This method fetches the specified table's schema from the database.
	 *
	 * @param string $table_name The name of the table for which to retrieve information.
	 *
	 * @return array|null Table schema information, or null if unable to retrieve.
	 */
	public function table_info($table_name) {
		if (empty($table_name)) {
			return false;
		}
		if ($this->db_type == "pgsql") {
			if (!isset($this->schema_info[$table_name])) {
				return false;
			}
			return $this->schema_info[$table_name];
		}
		if ($this->db_type == "sqlite") {
			$sql = "PRAGMA table_info(" . $table_name . ");";
		}
		if ($this->db_type == "mysql") {
			$sql = "describe " . $table_name . ";";
		}
		$prep_statement = $this->database->db->prepare($sql);
		$prep_statement->execute();
		return $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Checks if a column exists in the provided table information.
	 *
	 * This method iterates over the table_info array and checks for the existence of
	 * the specified column_name. If found, it returns true; otherwise, it returns false.
	 *
	 * @param array  $table_info  An array containing information about the columns in a table.
	 * @param string $column_name The name of the column to check for existence.
	 *
	 * @return bool True if the column exists, false otherwise.
	 */
	private function sqlite_column_exists($table_info, $column_name) {
		foreach ($table_info as $key => $row) {
			if ($row['name'] == $column_name) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Creates a table based on the provided app and table name.
	 *
	 * This method iterates through each app in the apps array, and then checks for
	 * a table with the specified name within that app. If a matching table is found,
	 * it generates an SQL statement to create the table.
	 *
	 * @param array  $apps  An array of applications, where each application has a 'db'
	 *                      sub-array containing information about tables in that database.
	 * @param string $table The name of the table to be created.
	 *
	 * @return string|null The SQL statement to create the table, or null if no match is found.
	 */
	public function create_table($apps, $table) {
		if (empty($apps)) {
			return false;
		}
		if (is_array($apps)) {
			foreach ($apps as $x => $app) {
				if (!empty($app['db']) && is_array($app['db'])) {
					foreach ($app['db'] as $y => $row) {
						if (!empty($row['table']['name']) && is_array($row['table']['name'])) {
							$table_name = $row['table']['name']['text'];
						} else {
							$table_name = $row['table']['name'];
						}
						if ($table_name == $table) {
							$sql = "CREATE TABLE " . $table_name . " (\n";
							(int)$field_count = 0;
							if (!empty($row['fields']) && is_array($row['fields'])) {
								foreach ($row['fields'] as $field) {
									if (!empty($field['deprecated']) && $field['deprecated'] == "true") {
										//skip this row
									} else {
										if ($field_count > 0) {
											$sql .= ",\n";
										}
										if (!empty($field['name']) && is_array($field['name'])) {
											$sql .= $field['name']['text'] . " ";
										} else {
											$sql .= $field['name'] . " ";
										}
										if (!empty($field['type']) && is_array($field['type'])) {
											$sql .= $field['type'][$this->db_type];
										} else {
											$sql .= $field['type'];
										}
										if (!empty($field['key']['type']) && $field['key']['type'] == "primary") {
											$sql .= " PRIMARY KEY";
										}
										$field_count++;
									}
								}
							}
							$sql .= ");\n";
							return $sql;
						}
					}
				}
			}
		}
	}

	/**
	 * Returns the data type of a column.
	 *
	 * This method retrieves the table info for the specified table name and then calls the
	 * data_type() method to get the data type of the specified column.
	 *
	 * @param string $table_name  The name of the table.
	 * @param string $column_name The name of the column.
	 *
	 * @return mixed The data type of the column.
	 */
	private function column_data_type($table_name, $column_name) {
		$table_info = $this->table_info($table_name);
		return $this->data_type($table_info, $column_name);
	}

	/**
	 * Retrieves the data type of a column in the database.
	 *
	 * This method checks the data type based on the current database type and
	 * returns the corresponding value from the table_info array.
	 *
	 * @param array  $table_info  An array containing table information.
	 * @param string $column_name The name of the column to retrieve the data type for.
	 *
	 * @return mixed The data type of the specified column, or null if not found.
	 */
	private function data_type($table_info, $column_name) {
		if ($this->db_type == "sqlite") {
			foreach ($table_info as $key => $row) {
				if ($row['name'] == $column_name) {
					return $row['type'];
				}
			}
		}
		if ($this->db_type == "pgsql") {
			foreach ($table_info as $key => $row) {
				if ($row['column_name'] == $column_name) {
					return $row['data_type'];
				}
			}
		}
		if ($this->db_type == "mysql") {
			foreach ($table_info as $key => $row) {
				if ($row['Field'] == $column_name) {
					return $row['Type'];
				}
			}
		}
	}

	/**
	 * Inserts data from temporary tables into a specified database table.
	 *
	 * This method iterates through the provided applications, database definitions,
	 * and temporary tables to construct SQL INSERT statements. The constructed SQL
	 * statements are returned for execution.
	 *
	 * @param array  $apps  An array of application configurations.
	 * @param string $table The name of the target database table.
	 *
	 * @return string The constructed SQL INSERT statement as a string.
	 */
	private function insert_into($apps, $table) {
		foreach ($apps as $x => $app) {
			foreach ($app['db'] as $y => $row) {
				if ($row['table']['name'] == $table) {
					$sql = "INSERT INTO " . $row['table']['name'] . " (";
					$field_count = 0;
					foreach ($row['fields'] as $field) {
						if (!empty($field['deprecated']) && $field['deprecated'] == "true") {
							//skip this field
						} else {
							if ($field_count > 0) {
								$sql .= ",";
							}
							if (is_array($field['name'])) {
								$sql .= $field['name']['text'];
							} else {
								$sql .= $field['name'];
							}
							$field_count++;
						}
					}
					$sql .= ")\n";
					$sql .= "SELECT ";
					$field_count = 0;
					foreach ($row['fields'] as $field) {
						if (!empty($field['deprecated']) && $field['deprecated'] == "true") {
							//skip this field
						} else {
							if ($field_count > 0) {
								$sql .= ",";
							}
							if (is_array($field['name'])) {
								if ($field['exists'] == "false") {
									if (is_array($field['name']['deprecated'])) {
										$found = false;
										foreach ($field['name']['deprecated'] as $row) {
											if ($this->column_exists('tmp_' . $table, $row)) {
												$sql .= $row;
												$found = true;
												break;
											}
										}
										if (!$found) {
											$sql .= "''";
										}
									} else {
										if ($this->column_exists('tmp_' . $table, $field['name']['deprecated'])) {
											$sql .= $field['name']['deprecated'];
										} else {
											$sql .= "''";
										}
									}
								} else {
									$sql .= $field['name']['text'];
								}
							} else {
								$sql .= $field['name'];
							}
							$field_count++;
						}
					}
					$sql .= " FROM tmp_" . $table . ";\n";
					return $sql;
				}
			}
		}
	} //end function
}

//example use
//$schema = new schema();
//$schema->db_type = $db_type;
//$schema->schema();
//$result_array = $schema->obj['sql'];
//print_r($result_array);
