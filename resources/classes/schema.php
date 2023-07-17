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
	  Copyright (C) 2013 - 2019
	  All Rights Reserved.

	  Contributor(s):
	  Mark J Crane <markjcrane@fusionpbx.com>
	 */

//define the schema class
	if (!class_exists('schema')) {

		class schema {

			CONST TABLE_NEW = 0,
				TABLE_RENAME = 1,
				TABLE_COMMENT = 2;
			CONST FIELD_NEW = 0,
				FIELD_ADD = 1,
				FIELD_RENAME = 2,
				FIELD_TYPE = 3,
				FIELD_COMMENT = 4,
				FIELD_KEY = 5,
				FIELD_INDEX = 6;

			/**
			 * An array of error Exception objects from {@link \Exception} reported during processing of the included app_config files.
			 * <p>When empty, there are no errors detected. If errors are detected, the exception object is stored in
			 * the errors array. This is write only and does not effect the operation if set outside the
			 * class.</p>
			 * @var array Exceptions reported during processing app_config files.
			 * @see \Exception::class
			 */
			public $errors;
			//define variables
			private $database;
			private $response;
			private $output_format;
			private $sql;
			private $data_type;
			private $type;
			private $db_tables;

			/**
			 * Sets database tables and fields to match the app_config files.
			 * @global type $apps Used to parse the app_config files that use the apps[$x]['db']
			 * @param string $output_type Return the formatted text response of either 'text' or 'html'
			 * @param bool $check_data_types Forces check on each column type in the database
			 * @depends database::new()
			 */
			public function __construct(string $output_type = null, bool $check_data_types = false) {
				//open a database connection
				$this->database = database::new();

				//set the field type from the database
				$this->type = $this->database->type;

				//set the result to be an array
				$this->sql = [];

				//set the response to be empty
				$this->response = "";

				//set the default output to be html
				if ($output_type !== null) {
					$this->output_format($output_type);
				}

				//set the default value of check_data_type
				if ($check_data_types) {
					$this->check_data_types($check_data_types);
				}

				//assume no errors
				$this->errors = [];

				//initialize the internal database tracking array
				$this->db_tables = [];

			}

			public function __get($name) {
				switch($name) {
					case 'db_type':
						return $this->type;
				}
			}

			public function __set($name, $value) {
				switch($name) {
					case 'db_type':
						return $this;
				}
			}

			public function output_format(?string $format = null) {
				if ($format === null) {
					return $this->output_format;
				}
				switch ($format) {
					case 'html':
					case 'text':
						$this->output_format = $format;
						break;
					default:
						throw new InvalidArgumentException('Unknown output format');
				}
				return $this;
			}

			public function check_data_types(?bool $check_data_types = null) {
				if ($check_data_types === null) {
					return $this->data_type;
				}
				$this->data_type = $check_data_types;
				return $this;
			}

			public function schema() {
				//do nothing
				trigger_error('Deprecated function "schema" called');
			}

			//create the database schema
			private function exec() {
				if (!empty($this->sql)) {
					//create all tables
					if(isset($this->sql['tables'])) {
						$this->do_transaction($this->sql['tables']);
						unset($this->sql['tables']);
					}
					//write alter statements after tables are created
					foreach($this->sql as $command_set) {
						$this->do_transaction($command_set);
					}
				}
			}

			private function do_transaction(array $command_set) {
				try {
					$this->database->db->beginTransaction();
					foreach($command_set as $sql) {
						$this->database->db->query($sql);
					}
					$this->database->db->commit();
				} catch (PDOException $error) {
					$this->errors[] = $error->getMessage();
					//reverse changes due to error
					$this->database->db->rollBack();
				}
			}

			//datatase schema
			public function upgrade() {

				//enumerate database table and column info
				$all_fields = $this->database->get_pgsql_fields();
				//get the table names
				$tables = array_keys($all_fields);
				//get the primary keys and foreign keys
				$constraints = $this->database->get_pgsql_constraints();
				//get the comments
				$comments = $this->database->get_pgsql_comments();
				//get the indexes
				$indexes = $this->database->get_pgsql_indexes();
				//reorder the database tables
				foreach ($tables as $table) {
					$this->db_tables[$table]['fields'] = $all_fields[$table];
					foreach($constraints[$table] ?? [] as $constraint) {
						$this->db_field_f_key_add($table, $constraint['key'], $constraint['def']);
					}
					$this->db_tables[$table]['comments'] = $comments[$table] ?? '';
					$this->db_tables[$table]['indexes'] = $indexes[$table] ?? '';
				}

				//get the list of installed apps from the core and mod directories
				$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
				//$apps is used by all app_config files to load default settings, permissions, database etc.
				global $apps;
				//compare the database with the schema
				for ($x = 0; $x < count($config_list); $x++) {
					include($config_list[$x]);
					//process the file after loading
					if (isset($apps[$x]['db']))
						foreach ($apps[$x]['db'] as $table) {
							$table_name = self::app_table_name($table);
							//process a new table
							if(!$this->db_table_exists($table_name)) {
								$this->sql_generate_table_insert($table_name, $table['fields']);
							} else {
								// process the fields for existing tables
								$this->sql_generate_field_changes($table_name, $table['fields']);
							}
						}
				}
				$this->exec();
				return $this;
			}

			/**
			 * String representation of the changes made to the database.
			 * <p>The method will return the changes in the default HTML representation unless the
			 * object property <i>output_format</i> is set to text.</p>
			 * @return string Representation of the changes made
			 */
			public function __toString(): string {
				$response = "";
				return $response;
			}

			public function has_errors(): bool {
				return (count($this->errors) > 0);
			}

			/**
			 * Checks for an existing column.
			 * @param type $db_name
			 * @param type $table_name
			 * @param type $column_name
			 * @return bool
			 * @deprecated since version 5.1.1
			 */
			public function column_exists($db_name, $table_name, $column_name): bool {
				$database = $this->database;
				$columns = $database->get_pgsql_table_fields($table_name);
				return array_search($column_name, $columns, true) !== false;
			}

			private function sql_generate_table_insert($table_name, &$table_fields) {
				$this->sql['tables'][$table_name] = "CREATE TABLE $table_name ("
					. implode(', ', $this->sql_table_new($table_fields))
					. ");";
			}

			private function sql_table_new(&$table_fields) {
				$fields = [];
				foreach($table_fields as &$field) {
					if(!($this->app_field_name_is_deprecated($field) || $this->app_field_skip($field))) {
						$field_name = self::app_field_name($field);
						$type = self::app_field_type($this->type, $field);
						$is_primary = self::app_field_is_primary($field);
						$fields[] = "$field_name $type" . ($is_primary ? " PRIMARY KEY" : "");
					}
				}
				return $fields;
			}

			private function sql_field_rename($table_name, &$field) {
				$field_name = self::app_field_name($field);
				$deprecated_name = self::app_field_deprecated_name($field);
				if ($this->db_field_exists($table_name, $deprecated_name)) {
					//set the sql statement to update database
					$this->sql[self::FIELD_RENAME][] = "ALTER TABLE {$table_name} RENAME COLUMN {$deprecated_name} TO {$field_name}";
					//track the changes internally
					$this->db_field_rename($table_name, $deprecated_name, $field_name);
					return true;
				}
//				else {
//					//if the new field doesn't exist then create it
//					if(!$this->db_field_exists($table_name, $field_name)) {
//						return $this->sql_field_add($table_name, $field);
//					}
//				}
				return false;
			}

			private function sql_field_add($table_name, &$field) {
				$field_name = self::app_field_name($field);
				if (!$this->db_field_exists($table_name, $field_name)) {
					$type = self::app_field_type($this->type, $field);
					$this->sql[self::FIELD_ADD][] = "ALTER TABLE $table_name ADD $field_name $type";
					// update the internal database array
					$this->db_field_type_set($table_name, $field_name, $type);
					return true;
				}
				return false;
			}

			private function sql_field_add_foreign_key($table_name, &$field) {
				$field_name = self::app_field_name($field);
				if (self::app_field_ref_exists($field)) {
					$f_table_name = self::app_field_ref_table($field);
					$f_field_name = self::app_field_ref_field($field);
					$key = "fk_{$table_name}_{$field_name}_{$f_table_name}_{$f_field_name}";
					$sql = "ALTER TABLE {$table_name} ADD CONSTRAINT $key "
						. " FOREIGN KEY({$field_name})"
						. " REFERENCES $f_table_name({$f_field_name}); ";
					$this->sql[self::FIELD_KEY][] = $sql;
					//track changes internally
					$this->db_field_f_key_add($table_name, $key, $sql);
					return true;
				}
				return false;
			}

			private function sql_field_add_comment($table_name, &$field) {
				$comment = self::app_field_comment($field);
				$field_name = self::app_field_name($field);
				if (!empty($comment) && $this->db_field_comment($table_name, $field_name) !== $comment) {
					if(strpos($comment, "'") > 0) {
						//comment text is not allowed to have '
						trigger_error("SKIPPING TABLE $table_name FIELD $field_name COMMENT {$comment}", E_USER_WARNING);
					} else {
						$this->sql[self::FIELD_COMMENT][] = "COMMENT ON COLUMN $table_name.$field_name IS '$comment'; ";
					}
					return true;
				}
				return false;
			}

			private function sql_field_add_index($table_name, &$field) {
				$field_name = self::app_field_name($field);
				if (!$this->db_field_has_index($table_name, $field_name) && self::app_field_is_indexed($field)) {
					$this->sql[self::FIELD_INDEX][] = "CREATE INDEX {$table_name}_{$field_name}_idx ON {$table_name} ($field_name); ";
					return true;
				}
				return false;
			}

			private function sql_field_modify_type($table_name, &$field) {
				$field_name = self::app_field_name($field);
				$type = self::app_field_type($this->type, $field);
				$db_type = $this->db_field_type($table_name, $field_name);
				if ($type !== $db_type) {
					$type = is_array($field['type']) ? $field['type'][$this->type] : $field['type'];
					//set the sql statement to update database
					$this->sql[self::FIELD_TYPE][] = "ALTER TABLE $table_name ALTER COLUMN $field_name TYPE $type";
					//track the changes internally
					$this->db_field_type_set($table_name, $field_name, $type);
					return true;
				}
				return false;
			}

			private function sql_generate_field_changes($table_name, $table_fields) {
				foreach ($table_fields as $field) {
					if ($this->db_table_exists($table_name) && !self::app_field_skip($field)) {
						$this->sql_field_rename($table_name, $field);
						$this->sql_field_add($table_name, $field);
						$this->sql_field_modify_type($table_name, $field);
						$this->sql_field_add_index($table_name, $field);
						$this->sql_field_add_comment($table_name, $field);
						$this->sql_field_add_foreign_key($table_name, $field);
					}
				}
			}

			private function db_table_exists($table_name) {
				return isset($this->db_tables[$table_name]);
			}

			private function db_field_f_key_add($table_name, $key, $sql) {
				$this->db_tables[$table_name]['constraints'][$key] = $sql;
			}

			private function db_field_exists($table_name, $field_name) {
				if(!empty($field_name)) {
					return isset($this->db_tables[$table_name]['fields'][$field_name]);
				}
				return false;
			}

			private function db_field_rename($table_name, $old_name, $new_name) {
				$this->db_tables[$table_name]['fields'][$new_name] = $this->db_tables[$table_name]['fields'][$old_name];
				$this->db_tables[$table_name]['fields'][$new_name]['column_name'] = $new_name;
				unset($this->db_tables[$table_name]['fields'][$old_name]);
				return $this;
			}

			private function db_field_type($table_name, $field_name) {
				return $this->db_tables[$table_name]['fields'][$field_name]['udt_name'] ?? '';
			}

			private function db_field_type_set($table_name, $field_name, $type = '') {
				$this->db_tables[$table_name]['fields'][$field_name]['udt_name'] = $type;
				return $this;
			}

			private function db_field_comment($table_name, $field_name) {
				return $this->db_tables[$table_name]['comments'][$field_name] ?? '';
			}

			private function db_field_comment_add($table_name, $field_name, $comment = '') {
				$this->db_tables[$table_name]['comments'][$field_name] = $comment;
				return $this;
			}

			private function db_field_has_index($table_name, $field_name) {
				return isset($this->db_tables[$table_name]['indexes'][$field_name]);
			}

			private static function app_field_comment(&$field) {
				return is_array($field['description'] ?? '') ? reset($field['description']) : $field['description'] ?? '';
			}

			private static function app_field_name(&$field) {
				return is_array($field['name']) ? $field['name']['text'] : $field['name'] ?? '';
			}

			private static function app_field_is_primary(&$field) {
				return isset($field['key']['type']) && $field['key']['type'] === 'primary';
			}

			private static function app_field_is_indexed(&$field) {
				return isset($field['indexed']);
			}

			private static function app_field_type($db_type, &$field) {
				return is_array($field['type']) ? $field['type'][$db_type] : $field['type'];
			}

			private static function app_field_deprecated_name(&$field) {
				if(self::app_field_name_is_deprecated($field)) {
					return $field['name']['deprecated'];
				}
				return '';
			}

			private static function app_field_name_is_deprecated(&$field) {
				return isset($field['name']['deprecated']);
			}

			private static function app_field_skip(&$field) {
				return isset($field['deprecated']) ? $field['deprecated'] === 'true' : false;
			}

			private static function app_field_ref_exists(&$field) {
				return isset($field['key']['reference']['table']);
			}

			private static function app_field_ref_table(&$field) {
				return $field['key']['reference']['table'] ?? '';
			}

			private static function app_field_ref_field(&$field) {
				return $field['key']['reference']['field'] ?? '';
			}

			private static function app_table_name(&$table) {
				if (isset($table['table']['name'])) {
					if (is_array($table['table']['name'])) {
						$table_name = $table['table']['name']['text'];
					} else {
						$table_name = $table['table']['name'];
					}
				} else {
					//old array syntax
					if (is_array($table)) {
						$table_name = $table['table']['text'];
					} else {
						$table_name = $table['table'];
					}
				}
				return $table_name;
			}

			private static function app_table_parent($table) {
				return $table['parent'] ?? '';
			}

			private static function app_table_parent_exists($table) {
				return !empty($table['parent'] ?? '');
			}

			/*
			  //build the sql with anything changed or missing
			  //				foreach ($this->apps as $app) {
			  //					foreach ($app['db'] as $row) {
			  //						if (isset($row['table']['name'])) {
			  //							if (is_array($row['table']['name'])) {
			  //								$table_name = $row['table']['name']['text'];
			  //							} else {
			  //								$table_name = $row['table']['name'];
			  //							}
			  //						} else {
			  //							//old array syntax
			  //							if (is_array($row['table'])) {
			  //								$table_name = $row['table']['text'];
			  //							} else {
			  //								$table_name = $row['table'];
			  //							}
			  //						}
			  //						if (!empty($table_name)) {
			  //							//check if the table exists
			  //							if (!empty($db_tables[$table_name])) {
			  //								$apps[$x]['db'][$y]['exists'] = 'true';
			  //								//check if the column exists
			  //								foreach ($row['fields'] as $field) {
			  //									if (!empty($field['deprecated']) && $field['deprecated'] === "true") {
			  //										//skip this field
			  //									} else {
			  //										if (is_array($field['name'])) {
			  //											$field_name = $field['name']['text'];
			  //										} else {
			  //											$field_name = $field['name'];
			  //										}
			  //										if (!empty($field_name)) {
			  //											if (!empty($db_tables[$table_name][$field_name])) {
			  //												//found
			  //												$apps[$x]['db'][$y]['fields'][$z]['exists'] = 'true';
			  //											} else {
			  //												//not found
			  //												$apps[$x]['db'][$y]['fields'][$z]['exists'] = 'false';
			  //											}
			  //										} else {
			  //											//we are unable to parse field name
			  //											trigger_error("unable to parse field name '$field_name' in table '$table_name'\n");
			  //										}
			  //										unset($field_name);
			  //									}
			  //								}
			  //								unset($table_name);
			  //							} else {
			  //								$apps[$x]['db'][$y]['exists'] = 'false';
			  //							}
			  //						}
			  //					}
			  //				}

			  //prepare the variables
			  $sql_update = '';

			  //				//add missing tables and fields
			  //				foreach ($apps as $x => &$app) {
			  //					if (isset($app['db']))
			  //						foreach ($app['db'] as $y => &$row) {
			  //							if (is_array($row['table']['name'])) {
			  //								$table_name = $row['table']['name']['text'];
			  //								if (in_array($row['table']['name']['deprecated'], $db_tables)) {
			  //									//$row['exists'] = "false"; //testing
			  //									if ($db_type == "pgsql") {
			  //										$sql_update .= "ALTER TABLE " . $row['table']['name']['deprecated'] . " RENAME TO " . $row['table']['name']['text'] . ";\n";
			  //									}
			  //									if ($db_type == "mysql") {
			  //										$sql_update .= "RENAME TABLE " . $row['table']['name']['deprecated'] . " TO " . $row['table']['name']['text'] . ";\n";
			  //									}
			  //									if ($db_type == "sqlite") {
			  //										$sql_update .= "ALTER TABLE " . $row['table']['name']['deprecated'] . " RENAME TO " . $row['table']['name']['text'] . ";\n";
			  //									}
			  //								} else {
			  //									if (!empty($row['table']['name']['text'], $db_tables)) {
			  //										$row['exists'] = "true";
			  //									} else {
			  //										$row['exists'] = "false";
			  //										$sql_update .= $this->db_create_table($apps, $db_type, $row['table']['name']['text']);
			  //									}
			  //								}
			  //							} else {
			  //								$table_name = $row['table']['name'];
			  //							}
			  //							//check if the table exists
			  //							if ($row['exists'] == "true") {
			  //								if (count($row['fields']) > 0) {
			  //									$table_info = $this->db_table_info($db_name, $db_type, $table_name);
			  //									foreach ($row['fields'] as $z => $field) {
			  //										if (!empty($field['deprecated']) && $field['deprecated'] == "true") {
			  //											//skip this field
			  //										} else {
			  //											//get the data type
			  //											if (is_array($field['type'])) {
			  //												$field_type = $field['type'][$db_type];
			  //											} else {
			  //												$field_type = $field['type'];
			  //											}
			  //											//get the field name
			  //											if (is_array($field['name'])) {
			  //												$field_name = $field['name']['text'];
			  //												if (array_key_exists($field_name, $table_info)) {
			  //													$field['exists'] = "false";
			  //												}
			  //											} else {
			  //												$field_name = $field['name'];
			  //											}
			  //
			  //											//add or rename fields
			  //											if (isset($field['name']['deprecated']) && array_key_exists($field['name']['deprecated'], $table_info)) {
			  //												if ($db_type == "pgsql") {
			  //													$sql_update .= "ALTER TABLE " . $table_name . " RENAME COLUMN " . $field['name']['deprecated'] . " to " . $field['name']['text'] . ";\n";
			  //												}
			  //												if ($db_type == "mysql") {
			  //													$field_type = str_replace("AUTO_INCREMENT PRIMARY KEY", "", $field_type);
			  //													$sql_update .= "ALTER TABLE " . $table_name . " CHANGE " . $field['name']['deprecated'] . " " . $field['name']['text'] . " " . $field_type . ";\n";
			  //												}
			  //												if ($db_type == "sqlite") {
			  //													//a change has been made to the field name
			  //													$apps[$x]['db'][$y]['rebuild'] = 'true';
			  //												}
			  //											} else {
			  //												//find missing fields and add them
			  //												if ($field['exists'] == "false") {
			  //													$sql_update .= "ALTER TABLE " . $table_name . " ADD " . $field_name . " " . $field_type . ";\n";
			  //												}
			  //											}
			  //
			  //											//change the data type if it has been changed
			  //											//if the data type in the app db array is different than the type in the database then change the data type
			  //											if ($this->data_types) {
			  //												$db_field_type = $table_info[$field_name]['data_type'];
			  //												if (trim($db_field_type) != trim($field_type) && !empty($db_field_type)) {
			  //													if ($db_type == "pgsql") {
			  //														if (strtolower($field_type) == "uuid") {
			  //															$sql_update .= "ALTER TABLE " . $table_name . " ALTER COLUMN " . $field_name . " TYPE uuid USING\n";
			  //															$sql_update .= "CAST(regexp_replace(" . $field_name . ", '([A-Z0-9]{4})([A-Z0-9]{12})', E'\\1-\\2')\n";
			  //															$sql_update .= "AS uuid);\n";
			  //														} else {
			  //															//field type has not changed
			  //															if ($db_field_type == "integer" && strtolower($field_type) == "serial") {
			  //
			  //															} else if ($db_field_type == "timestamp without time zone" && strtolower($field_type) == "timestamp") {
			  //
			  //															} else if ($db_field_type == "timestamp without time zone" && strtolower($field_type) == "datetime") {
			  //
			  //															} else if ($db_field_type == "timestamp with time zone" && strtolower($field_type) == "timestamptz") {
			  //
			  //															} else if ($db_field_type == "integer" && strtolower($field_type) == "numeric") {
			  //
			  //															} else if ($db_field_type == "character" && strtolower($field_type) == "char") {
			  //
			  //															}
			  //															//field type has changed
			  //															else {
			  //																switch ($field_type) {
			  //																	case 'numeric': $using = $field_name . "::numeric";
			  //																		break;
			  //																	case 'timestamp':
			  //																	case 'datetime': $using = $field_name . "::timestamp without time zone";
			  //																		break;
			  //																	case 'timestamptz': $using = $field_name . "::timestamp with time zone";
			  //																		break;
			  //																	case 'boolean': $using = $field_name . "::boolean";
			  //																		break;
			  //																	default: unset($using);
			  //																}
			  //																$sql_update .= "ALTER TABLE " . $table_name . " ALTER COLUMN " . $field_name . " TYPE " . $field_type . " " . ($using ? "USING " . $using : null) . ";\n";
			  //															}
			  //														}
			  //													}
			  //													if ($db_type == "mysql") {
			  //														$type = explode("(", $db_field_type);
			  //														if ($type[0] == $field_type) {
			  //															//do nothing
			  //														} else if ($field_type == "numeric" && $type[0] == "decimal") {
			  //															//do nothing
			  //														} else {
			  //															$sql_update .= "ALTER TABLE " . $table_name . " modify " . $field_name . " " . $field_type . ";\n";
			  //														}
			  //														unset($type);
			  //													}
			  //													if ($db_type == "sqlite") {
			  //														//a change has been made to the field type
			  //														$apps[$x]['db'][$y]['rebuild'] = 'true';
			  //													}
			  //												}
			  //											}
			  //										}
			  //									}
			  //								}
			  //							} else {
			  //								//create table
			  //								if (!is_array($row['table']['name'])) {
			  //									$sql_update .= $this->db_create_table($apps, $db_type, $row['table']['name']);
			  //								}
			  //							}
			  //						}
			  //				}
			  //				//rebuild and populate the table
			  //				foreach ($apps as $x => &$app) {
			  //					if (isset($app['db']))
			  //						foreach ($app['db'] as $y => &$row) {
			  //							if (is_array($row['table']['name'])) {
			  //								$table_name = $row['table']['name']['text'];
			  //							} else {
			  //								$table_name = $row['table']['name'];
			  //							}
			  //							if (!empty($field['rebuild']) && $row['rebuild'] == "true") {
			  //								if ($db_type == "sqlite") {
			  //									//start the transaction
			  //									//$sql_update .= "BEGIN TRANSACTION;\n";
			  //									//rename the table
			  //									$sql_update .= "ALTER TABLE " . $table_name . " RENAME TO tmp_" . $table_name . ";\n";
			  //									//create the table
			  //									$sql_update .= $this->db_create_table($apps, $db_type, $table_name);
			  //									//insert the data into the new table
			  //									$sql_update .= $this->db_insert_into($apps, $db_type, $table_name);
			  //									//drop the old table
			  //									$sql_update .= "DROP TABLE tmp_" . $table_name . ";\n";
			  //									//commit the transaction
			  //									//$sql_update .= "COMMIT;\n";
			  //								}
			  //							}
			  //						}
			  //				}

			  // initialize response variable
			  $response = '';

			  //display results as html
			  if ($format == "html") {
			  //show the database type
			  $response .= "<strong>" . $text['header-database_type'] . ": " . $db_type . "</strong><br /><br />";
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
			  foreach ($apps as &$app) {
			  if (isset($app['db']))
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
			  $field_type = $field['type'][$db_type];
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
			  //end the list of tables
			  $response .= "</table>\n";
			  $response .= "<br />\n";
			  }

			  //loop line by line through all the lines of sql code
			  $x = 0;
			  if (empty($sql_update) && $format == "text") {
			  $response .= "	" . $text['label-schema'] . ":			" . $text['label-no_change'] . "\n";
			  } else {
			  if ($format == "text") {
			  $response .= "	" . $text['label-schema'] . "\n";
			  }
			  //$this->db->beginTransaction();
			  $update_array = explode(";", $sql_update);
			  foreach ($update_array as $sql) {
			  if (strlen(trim($sql))) {
			  try {
			  $this->db->query(trim($sql));
			  if ($format == "text") {
			  $response .= "	$sql;\n";
			  }
			  } catch (PDOException $error) {
			  $response .= "	error: " . $error->getMessage() . "	sql: $sql\n";
			  }
			  }
			  }
			  //$this->db->commit();
			  $response .= "\n";
			  unset($sql_update, $sql);
			  }

			  //handle response
			  //if ($output == "echo") {
			  //	echo $response;
			  //}
			  //else if ($output == "return") {
			  return $response;
			  //}
			  }
			 */


//end function
		}

	}

//example use
	//require_once "resources/classes/schema.php";
	//$obj = new schema;
	//$obj->schema();
	//$result_array = $schema->obj['sql'];
	//print_r($result_array);
?>
