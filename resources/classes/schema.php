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
	  Tim Fry <tim@dnsnetworks.com>
	 */

//define the schema class
	if (!class_exists('schema')) {

		class schema {

			//used to determine how the database transactions are performed
			const TABLE_NEW = 0,
				TABLE_RENAME = 1,
				TABLE_COMMENT = 2;
			const FIELD_NEW = 0,
				FIELD_ADD = 1,
				FIELD_RENAME = 2,
				FIELD_TYPE = 3,
				FIELD_COMMENT = 4,
				FIELD_KEY = 5,
				FIELD_INDEX = 6;

			/**
			 * ATOMIC commit will write all the SQL statements to the PDO driver and then commit at once. If there is an
			 * error in any of the SQL statements all changes are reversed.
			 * BATCH commit groups each each change by type of statement. The types are determined by the constants
			 * of FIELD_NEW, FIELD_ADD, FIELD_RENAME, FIELD_TYPE, FIELD_COMMENT, FIELD_KEY, FIELD_INDEX. If there are any
			 * errors within that group, the changes are reversed for that group and the next group is tried.
			 * SINGLE (default) commit executes each SQL individually. This is the safest type of commit. If there are any errors
			 * then only that single commit is not written to the database.
			 */
			const SCHEMA_COMMIT_ATOMIC = 0,
				SCHEMA_COMMIT_BATCH = 1,
				SCHEMA_COMMIT_SINGLE = 2;

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
			private $sql_success;
			private $data_type;
			private $type;
			private $db_tables;
			private $commit_mode;
			private $text;
			private $file;
			private $change_details;

			/**
			 * Sets database tables and fields to match the app_config files.
			 * @param string $output_type Return the formatted text response of either 'text' or 'html'
			 * @param bool $check_data_types Forces check on each column type in the database
			 * @param int $commit_mode Set writing to the database per single transaction, batches, or atomically
			 * @depends database::new()
			 */
			public function __construct(string $output_type = null, bool $check_data_types = false, int $commit_mode = 0) {
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

				//set the default write mode to the database
				$this->commit_mode = $commit_mode;

				$this->text = [];

				//track the tables in the app_config files for display output
				$this->change_details = [];

				//current app_config file being processed
				$this->file = "";

				//successful statements executed in the database
				$this->sql_success = [];
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
						$this->type = $value;
						return $this;
					case 'db':
						$this->database->db = $value;
						return $this;
				}
			}

			/**
			 * Alias of output_format
			 * @param string|null $display_type
			 * @return type
			 */
			public function display_type(?string $display_type = null) {
				return $this->output_format($display_type);
			}

			/**
			 * Sets the output type returned in the __toString method.
			 * @param string|null $format Can be null or 'html' or 'text'
			 * @return $this returns the object if the format parameter is omitted.
			 * @throws InvalidArgumentException if the format given is not supported an InvalidArgumentException is thrown
			 */
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
				//report an error
				//print_r(debug_backtrace());
				//trigger_error('Deprecated function "schema" called');
			}

			public static function new(...$params): self {
				$classname = static::class;
				$obj = new $classname();
				if (!empty($params) && is_array($params)) {
					//if the array has another row then the attr have been passed
					if (count($params) > 1) {
						$attrs = array_pop($params);
					}
					//get the array of key=value pairs
					$values = array_pop($params);
					if (!empty($values) && is_array($values)) {
						//set properties of the object
						self::set_object_properties($values, $obj);
					} else {
						//not an array of key=value pairs so put the value back
						array_push($params, $values);
						//let the reflection class handle creating the object
						$obj = self::create_reflection_object($params);
					}
					//store the attributes
					if (!empty($attrs)) {
						$obj->attributes = $attrs;
					}
				}
				return $obj;
			}

			//preferred over the standard reflection as this will
			//call the individual setter and getter methods utilizing
			//their checks to make sure validation is done on properties
			private static function set_object_properties(array $array, self $object) { //: never {
				foreach ($array as $key => $value) {
					if ($value !== null) {
						//check for a setter method first for any validation being done
						if (method_exists(static::class, "$key")) {
							$object->{$key}($value);
						}
						//check for property
						elseif (property_exists(static::class, "$key")) {
							$object->{$key} = $value;
						}
					}
				}
			}

			//generic method used to create a new object
			//unable to call the constructor with different number
			//of arguments so a reflection object is used
			private static function create_reflection_object(array $params): self {
				//get the registered class name
				$classname = static::class;
				//create a reflection class to instantiate the object
				$reflection = new \ReflectionClass($classname);
				//send the reflection class the params needed to create the object
				$obj = $reflection->newInstanceArgs($params);
				return $obj;
			}

			//create the database schema
			private function exec() {
				if (!empty($this->sql)) {
					//check database commit type
					switch($this->commit_mode) {
						//fastest write method but will reverse all changes if there is an error
						case self::SCHEMA_COMMIT_ATOMIC:
							$this->save_transactions_atomic();
							break;
						//write each section of the statements
						case self::SCHEMA_COMMIT_BATCH:
							$this->save_transactions_batch();
							break;
						//safest option to ensure all changes are written
						case self::SCHEMA_COMMIT_SINGLE:
							$this->save_transactions_single();
							break;
					}
				}
			}

			//assume there are no mistakes so fastest method will work
			private function save_transactions_atomic() {
				try {
					$this->database->db->beginTransaction();
					if(!empty($this->sql['tables'])) {
						foreach($this->sql['tables'] as $table_insert) {
							$this->database->db->query($table_insert);
						}
						$this->sql_success = $this->sql['tables'];
						unset($this->sql['tables']);
					}
					for($i=0; $i < 7; $i++) {
						if(!empty($this->sql[$i])) {
							foreach($this->sql[$i] as $sql) {
								$this->database->db->query($sql);
							}
						}
					}
					$this->database->db->commit();
					//save them as a successful commit
					$this->sql_success += $this->sql;
					//everything has been committed so reset the sql commands tracking array
					$this->sql = [];
				} catch (PDOException $e) {
					if($this->database->db->inTransaction()) {
						$this->database->db->rollBack();
					}
					//try in batch
					$this->commit_mode = 1;
					$this->exec();
				}
			}

			//secondary commit mode tries each category of sql statements
			private function save_transactions_batch() {
				try {
					if(!empty($this->sql['tables'])) {
						$this->database->db->beginTransaction();
						foreach($this->sql['tables'] as $table_insert) {
							$this->database->db->query($table_insert);
						}
						$this->database->db->commit();
						//save the sql commands that were successful
						$this->sql_success += $this->sql['tables'];
						unset($this->sql['tables']);
					}
					for($i=0; $i < 7; $i++) {
						if(!empty($this->sql[$i])) {
							$this->database->db->beginTransaction();
							foreach($this->sql[$i] as $sql) {
								$this->database->db->query($sql);
							}
							$this->database->db->commit();
							//save the sql commands for this set that were successful
							$this->sql_success += $this->sql[$i];
							//this set of sql commands was successful so remove them from the tracking array
							unset($this->sql[$i]);
						}
					}
				} catch (PDOException $error) {
					//reverse changes due to error
					if($this->database->db->inTransaction()) {
						$this->database->db->rollBack();
					}
					//try single commit type
					$this->commit_mode = 2;
					$this->exec();
				}
			}

			private function save_transactions_single() {
				foreach($this->sql as $command_set) {
					foreach($command_set as $statement) {
						try {
							$this->database->db->exec($statement);
							$this->sql_success[] = $statement;
						} catch (PDOException $e) {
							$this->errors[] = $e->getMessage();
							if($this->database->db->inTransaction()) {
								$this->database->db->rollBack();
							}
						}
					}
				}
			}

			private function sql_generate_field_changes($table_name, $table_fields) {
				foreach ($table_fields as $field) {
					$field_name = $this->app_field_name($field);
					$type = $this->app_field_type($this->type, $field);
					//ensure this field is not set to be skipped or it has already been processed
					if (!self::app_field_skip($field)) {
						//make sure we process the field only once
						if(empty($this->change_details[$table_name][$field_name])) {
							$status = "";
							//report back more details
							if($this->sql_field_rename($table_name, $field_name, $field))
								$status .= "R";
							if($this->sql_field_add($table_name, $field_name, $field))
								$status .= "A";
							if($this->sql_field_modify_type($table_name, $field_name, $field))
								$status .= "T";
							if($this->sql_field_add_index($table_name, $field_name, $field))
								$status .= "I";
							if($this->sql_field_add_comment($table_name, $field_name, $field))
								$status .= "C";
							if($this->sql_field_add_foreign_key($table_name, $field_name, $field))
								$status .= "K";
							//if nothing has been changed with the field then report "True"
							if(empty($status))
								$status = '-';
							$this->change_details[$table_name][$field_name] = ['name' => $field_name,'status' => $status, 'type' => $type];
						} else {
							//a field from the same table should not be processed twice
							$this->errors[] = "Field $field_name in table $table_name from file {$this->file} already processed";
//							trigger_error("Field $field_name in table $table_name from file {$this->file} already processed", E_USER_WARNING);
						}
					} else {
//						//report the field as skipped because it is no longer used
//						$this->display_changes[$table_name][$field_name] = ['name' => $field_name, 'status' => 'D', 'type' => $type];
					}
				}
			}

			/**
			 * Upgrade the database schema to match the app_config.php files.
			 * <p>The function reads all app_config.php files within the project and then parses the $apps[$x]['db']
			 * section within the file. These are translated to sql commands required for the database to be
			 * adjusted to match. However, the upgrade schema will not remove or drop any configuration from the
			 * database. This is so that an application or plug-in can be removed and re-installed while retaining
			 * the information.</p>
			 * <p>This process can be broken down in to the following steps:
			 * <ul>
			 *		<li>
			 *			Database is enumerated using four separate reads. This information is parsed in to an array of
			 *			tables, fields, comments, constraints (primary keys), and indexes.
			 *		</li>
			 *		<li>
			 *			The app_config.php files are then loaded using a glob command. The array of files are then
			 *			<i>included</i> in the project.
			 *		</li>
			 *		<li>
			 *			As each file is loaded the <i>$apps[$x]['db']</i> is then compared to the existing database
			 *			structure using field functions.
			 *		</li>
			 * </ul>
			 * </p>
			 * @global array $apps Required array from the app_config.php file
			 * @return $this Returns this object to allow object chaining
			 */
			public function upgrade() {

				//set object properties to have database information
				$this->enumerate_database();
				//get the list of installed apps from the core and mod directories
				$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
				//$apps is used by all app_config files to load default settings, permissions, database etc.
				global $apps;
				//compare the database with the schema
				for ($x = 0; $x < count($config_list); $x++) {
					$this->file = $config_list[$x];
					include($this->file);
					//process the file after loading
					if (isset($apps[$x]['db'])) {
						foreach ($apps[$x]['db'] as $table) {
							$table_name = self::app_table_name($table);
							if($this->db_table_exists($table_name)) {
								// the tableoid is not allowed to be a column name
								// so we will use that to track the 'status' of the table
								$this->change_details[$table_name]['tableoid'] = 'option-true';
								// process the fields for existing table
								$this->sql_generate_field_changes($table_name, $table['fields']);
							} else {
								//process a new table
								$this->change_details[$table_name]['tableoid'] = 'option-false';
								$this->sql_generate_table_insert($table_name, $table['fields']);
							}
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
				$func = $this->output_format . "_response";
				if(method_exists($this, $func)) {
						$response = $this->{$func}();
				} else {
					trigger_error('Unknown display type');
				}
				return $response;
			}

			public function has_errors(): bool {
				return (!empty($this->errors));
			}

			private function text_response(): string {
				global $text;
				$response = "";
				if(!empty($this->change_details)) {
					$response .= implode("\n", array_map(function ($statements) { return implode("\n", $statements); },$this->sql));
				} else {
					$response .= "	".$text['label-schema'].":			".$text['label-no_change']."\n";
				}
				return $response;
			}

			private function html_response(): string {
				global $text;
				//show the database type
				$html = "<strong>{$text['header-database_type']}: {$this->type}</strong><br />".(empty($this->errors) ? "" : $text['header-errors_detected'])."<br /><br />";
				//start the table
				$html .= "<table width='100%' border='0' cellpadding='20' cellspacing='0'>\n";
				if(!empty($this->sql_success)) {
					$html .= "<tr>\n";
					$html .= "<td class='row_style1' colspan='3'>\n";
					$html .= "<br />\n";
					$html .= "<strong>".$text['label-sql_changes'].":</strong><br />\n";
					$html .= "<pre>\n";
					$html .= implode("\n", $this->sql_success);
					$html .= "</pre>\n";
					$html .= "<br />\n";
					$html .= "</td>\n";
					$html .= "</tr>\n";
				}
				$html .= "<tr>\n";
				$html .= "<th>".$text['label-table']."</th>\n";
				$html .= "<th>".$text['label-exists']."</th>\n";
				$html .= "<th>".$text['label-details']."</th>\n";
				$html .= "<tr>\n";
				$html .= $this->html_table();
				return $html;
			}

			private function html_table(): string {
				$html = implode("", array_map(function($fields, $table_name) {
					global $text;
					return "<tr>"
							. "<td class='row_style1' valign='top'>$table_name</td>"
							. "<td class='vncell' style='padding-top: 3px;' valign='top'>{$text[$this->change_details[$table_name]['tableoid']]}</td>"
							. "<td class='row_style1'>\n"
								. "<table cellspacing='0' cellpadding='10' border='0'>\n"
									. "<tbody>\n"
										. "<tr><th>{$text['label-table']}</th><th>{$text['label-type']}</th><th>{$text['label-details']}</th></tr>"
											. implode("", array_map(function($field) {
												if(is_array($field)) {
													$field_name = $field['name'];
													$status = $field['status'];
													$type = $field['type'];
													if($field_name === 'tableoid') {
														return "";
													}
													return "<tr>\n"
														. "<td class='row_style1'>$field_name</td>"
														. "<td class='row_style1'>$type</td>"
														. "<td class='row_style0' style='text-align:center'>$status</td>"
														. "</tr>\n";
													}
												return "";
											}, $fields))
									. "</tbody>\n"
								. "</table>\n";
				}, $this->change_details, array_keys($this->change_details))) . "\n";
				return $html;
			}

			private function sql_generate_table_insert($table_name, &$table_fields) {
				$this->sql['tables'][$table_name] = "CREATE TABLE $table_name ("
					. implode(', ', $this->sql_table_new_fields($table_name, $table_fields))
					. ");";
			}

			private function sql_table_new_fields($table_name, &$table_fields) {
				$fields = [];
				foreach($table_fields as &$field) {
					if(!($this->app_field_name_is_deprecated($field) || $this->app_field_skip($field))) {
						//get the field info
						$field_name = self::app_field_name($field);
						$type = self::app_field_type($this->type, $field);
						$is_primary = self::app_field_is_primary($field);
						//create the SQL statement
						$fields[] = "$field_name $type" . ($is_primary ? " PRIMARY KEY" : "");
						//track the response
						$this->change_details[$table_name][$field_name]['status'] = 'new';
						$this->change_details[$table_name][$field_name]['details'] = $type . ($is_primary ? " PRIMARY KEY" : "");
					}
				}
				return $fields;
			}

			private function enumerate_database() {
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
			}

			private function sql_field_rename($table_name, $field_name, &$field) {
				$deprecated_name = self::app_field_deprecated_name($field);
				if ($this->db_field_exists($table_name, $deprecated_name)) {
					//set the sql statement to update database
					$this->sql[self::FIELD_RENAME][] = "ALTER TABLE {$table_name} RENAME COLUMN {$deprecated_name} TO {$field_name}";
					//track the changes internally
					$this->db_field_rename($table_name, $deprecated_name, $field_name);
					return true;
				}
				return false;
			}

			private function sql_field_add($table_name, $field_name, &$field) {
				if (!$this->db_field_exists($table_name, $field_name)) {
					$type = self::app_field_type($this->type, $field);
					$this->sql[self::FIELD_ADD][] = "ALTER TABLE $table_name ADD $field_name $type";
					// update the internal database array
					$this->db_field_type_set($table_name, $field_name, $type);
					return true;
				}
				return false;
			}

			private function sql_field_add_foreign_key($table_name, $field_name, &$field) {
				if (self::app_field_ref_exists($field)) {
					$f_table_name = self::app_field_ref_table($field);
					$f_field_name = self::app_field_ref_field($field);
					if(!$this->db_table_exists($f_table_name)) {
						trigger_error("Foreign key defined in table $table_name references table $f_table_name but table does not exist", E_USER_WARNING);
						return false;
					}
					if(!$this->db_field_exists($f_table_name, $f_field_name)) {
						trigger_error("Foreign key defined in table $table_name references column $f_table_name but table does not exist", E_USER_WARNING);
						return false;
					}
					$key = "fk_{$table_name}_{$field_name}_{$f_table_name}_{$f_field_name}";
					$reference = "FOREIGN KEY ({$field_name}) REFERENCES $f_table_name({$f_field_name})";
					if(!$this->db_field_f_key_exists($table_name, $reference)) {
						$sql = "ALTER TABLE {$table_name} ADD CONSTRAINT $key $reference";
						$this->sql[self::FIELD_KEY][] = $sql;
						//track changes internally
						$this->db_field_f_key_add($table_name, $key, $sql);
						return true;
					}
				}
				return false;
			}

			private function sql_field_add_comment($table_name, $field_name, &$field) {
				$comment = self::app_field_comment($field);
				if (!empty($comment) && $this->db_field_comment($table_name, $field_name) !== $comment) {
					if(strpos($comment, "'") > 0) {
						//comment text is not allowed to have '
//						trigger_error("SKIPPING TABLE $table_name FIELD $field_name COMMENT {$comment}", E_USER_WARNING);
					} else {
						$this->sql[self::FIELD_COMMENT][] = "COMMENT ON COLUMN $table_name.$field_name IS '$comment'; ";
					}
					return true;
				}
				return false;
			}

			private function sql_field_add_index($table_name, $field_name, &$field) {
				if (!$this->db_field_has_index($table_name, $field_name) && self::app_field_is_indexed($field)) {
					$this->sql[self::FIELD_INDEX][] = "CREATE INDEX {$table_name}_{$field_name}_idx ON {$table_name} ($field_name); ";
					return true;
				}
				return false;
			}

			private function sql_field_modify_type($table_name, $field_name, &$field) {
				$type = self::app_field_type($this->type, $field);
				$db_type = $this->db_field_type($table_name, $field_name);
				if ($type !== $db_type) {
					$type = is_array($field['type']) ? $field['type'][$this->type] : $field['type'];
					//set the sql statement to update database
					$this->sql[self::FIELD_TYPE][] = "ALTER TABLE $table_name ALTER COLUMN $field_name TYPE $type USING $field_name::$type";
					//track the changes internally
					$this->db_field_type_set($table_name, $field_name, $type);
					return true;
				}
				return false;
			}

			private function db_table_exists($table_name) {
				return isset($this->db_tables[$table_name]);
			}

			private function db_field_f_key_add($table_name, $key, $sql) {
				$this->db_tables[$table_name]['constraints'][$key] = $sql;
			}

			private function db_field_f_key_exists($table_name, $value) {
				$exists = array_search($value, $this->db_tables[$table_name]['constraints'] ?? []) !== false;
				return $exists;
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
				$type = $this->db_tables[$table_name]['fields'][$field_name]['udt_name'] ?? '';
				if($type === 'bpchar') {
					$type = 'char(1)';
				}
				return $type;
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
				$type = is_array($field['type']) ? $field['type'][$db_type] : $field['type'];
				if($type === 'boolean') {
					$type = 'bool';
				}
				return $type;
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

//end function
		}

	}

//example use
	//require_once "resources/classes/schema.php";
	//$schema = new schema();
	//print_r($schema->output_type('text')->upgrade());
?>
