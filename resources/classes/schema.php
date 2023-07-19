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

			//used to determine how the database transactions are performed
			/**
			 * ATOMIC commit will write the sql to the PDO driver and then commit at once. If there is an error in any of
			 * the sql statements all changes are reversed.
			 * BATCH commit groups each each change by type of statement. The types are determined by the constants
			 * of FIELD_NEW, FIELD_ADD, FIELD_RENAME, FIELD_TYPE, FIELD_COMMENT, FIELD_KEY, FIELD_INDEX. If there are any
			 * errors within that group, the changes are reversed for that group and the next group is tried.
			 * SINGLE (default) commit executes each sql individually. This is the safest type of commit. If there are any errors
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
			private $data_type;
			private $type;
			private $db_tables;
			private $commit_mode;
			private $text;
			private $html;

			/**
			 * Sets database tables and fields to match the app_config files.
			 * @param string $output_type Return the formatted text response of either 'text' or 'html'
			 * @param bool $check_data_types Forces check on each column type in the database
			 * @param int $commit_mode Set writing to the database per single transaction, batches, or atomically
			 * @depends database::new()
			 */
			public function __construct(string $output_type = null, bool $check_data_types = false, int $commit_mode = 2) {
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

				$this->html = [];

				$this->text = [];
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
					try {
						//check database commit type
						switch($this->commit_mode) {
							//fastest write method but can reverse all changes if there is an error
							case self::SCHEMA_COMMIT_ATOMIC:
								$this->database->db->beginTransaction();
								foreach($this->sql as $command_set) {
									$this->database->db->query($command_set);
								}
								$this->database->db->commit();
								break;
							//write each section of the statements
							case self::SCHEMA_COMMIT_BATCH:
								//create all tables
								if(isset($this->sql['tables'])) {
									$this->save_transaction_batch($this->sql['tables']);
									unset($this->sql['tables']);
								}
								//write alter statements after tables are created
								foreach($this->sql as $command_set) {
									$this->save_transaction_batch($command_set);
								}
								break;
							//safest option to ensure all changes are written
							case self::SCHEMA_COMMIT_SINGLE:
								foreach($this->sql as $sql) {
									$this->database->db->exec($sql);
								}
								break;
						}
					} catch (Throwable $e) {
						$this->errors[] = $e->getMessage();
						if($this->database->db->inTransaction()) {
							$this->database->db->rollBack();
						}
					}
				}
			}

			private function save_transaction_batch(array $batch) {
				try {
					$this->database->db->beginTransaction();
					foreach($batch as $sql) {
						$this->database->db->query($sql);
					}
					$this->database->db->commit();
				} catch (PDOException $error) {
					$this->errors[] = $error->getMessage();
					//reverse changes due to error
					$this->database->db->rollBack();
				}
			}

			private function sql_generate_field_changes($table_name, $table_fields) {
				foreach ($table_fields as $field) {
					if (!self::app_field_skip($field)) {
						$this->sql_field_rename($table_name, $field);
						$this->sql_field_add($table_name, $field);
						$this->sql_field_modify_type($table_name, $field);
						$this->sql_field_add_index($table_name, $field);
						$this->sql_field_add_comment($table_name, $field);
						$this->sql_field_add_foreign_key($table_name, $field);
					}
				}
			}

			//datatase schema
			public function upgrade() {

				//set object properties to have database information
				$this->enumerate_database();
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
					if(!$this->db_table_exists($f_table_name)) {
						trigger_error("Foreign key defined in table $table_name references table $f_table_name but table does not exist", E_USER_WARNING);
						return false;
					}
					if(!$this->db_field_exists($f_table_name, $f_field_name)) {
						trigger_error("Foreign key defined in table $table_name references column $f_table_name but table does not exist", E_USER_WARNING);
						return false;
					}
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
