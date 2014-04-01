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
	Copyright (C) 2013
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the schema class
	class schema {
		public $db;
		public $apps;
		public $db_type;
		public $result;

		//get the list of installed apps from the core and mod directories
			public function __construct() {
				$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
				$x=0;
				foreach ($config_list as &$config_path) {
					include($config_path);
					$x++;
				}
				$this->apps = $apps;
			}

		//create the database schema
			public function sql() {
				$sql = '';
				$sql_schema = '';
				foreach ($this->apps as $app) {
					if (count($app['db'])) {
						foreach ($app['db'] as $row) {
							//create the sql string
								$table_name = $row['table'];
								$sql = "CREATE TABLE " . $row['table'] . " (\n";
								$field_count = 0;
								foreach ($row['fields'] as $field) {
									if ($field['deprecated'] == "true") {
										//skip this field
									}
									else {
										if ($field_count > 0 ) { $sql .= ",\n"; }
										if (is_array($field['name'])) {
											$sql .= $field['name']['text']." ";
										}
										else {
											$sql .= $field['name']." ";
										}
										if (is_array($field['type'])) {
											$sql .= $field['type'][$this->db_type];
										}
										else {
											$sql .= $field['type'];
										}
										if ($field['key']['type'] == "primary") {
											$sql .= " PRIMARY KEY";
										}
										if ($field['key']['type'] == "foreign") {
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
								}
								else {
									$sql .= ");";
								}
								$this->result['sql'][] = $sql;
								unset($sql);
						}
					}
				}
			}

		//create the database schema
			public function exec() {
				foreach ($this->result['sql'] as $sql) {
					//start the sql transaction
						$this->db->beginTransaction();
					//execute the sql query
						try {
							$this->db->query($sql);
						}
						catch (PDOException $error) {
							echo "error: " . $error->getMessage() . " sql: $sql<br/>";
						}
					//complete the transaction
						$this->db->commit();
				}
			}

		//check if a column exists in sqlite
			private function sqlite_column_exists($table_info, $column_name) {
				foreach ($table_info as $key => &$row) {
					if ($row['name'] == $column_name) {
						return true;
					}
				}
				return $false;
			}

		//check if a column exists
			public function column_exists ($db_type, $db_name, $table_name, $column_name) {
				global $display_type;

				if ($db_type == "sqlite") {
					$table_info = $this->table_info($db_name, $db_type, $table_name);
					if ($this->sqlite_column_exists($table_info, $column_name)) {
						return true;
					}
					else {
						return false;
					}
				}
				if ($db_type == "pgsql") {
					$sql = "SELECT attname FROM pg_attribute WHERE attrelid = (SELECT oid FROM pg_class WHERE relname = '$table_name') AND attname = '$column_name'; ";
				}
				if ($db_type == "mysql") {
					//$sql .= "SELECT * FROM information_schema.COLUMNS where TABLE_SCHEMA = '$db_name' and TABLE_NAME = '$table_name' and COLUMN_NAME = '$column_name' ";
					$sql = "show columns from $table_name where field = '$column_name' ";
				}
				if ($sql) {
					$prep_statement = $this->db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					if (!$result) {
						return false;
					}
					if (count($result) > 0) {
						return true;
					}
					else {
						return false;
					}
					unset ($prep_statement);
				}
			}

		//get the table information
			public function table_info($db_name, $db_type, $table_name) {
				if (strlen($table_name) == 0) { return false; }
				if ($db_type == "sqlite") {
					$sql = "PRAGMA table_info(".$table_name.");";
				}
				if ($db_type == "pgsql") {
					$sql = "SELECT ordinal_position, ";
					$sql .= "column_name, ";
					$sql .= "data_type, ";
					$sql .= "column_default, ";
					$sql .= "is_nullable, ";
					$sql .= "character_maximum_length, ";
					$sql .= "numeric_precision ";
					$sql .= "FROM information_schema.columns ";
					$sql .= "WHERE table_name = '".$table_name."' ";
					$sql .= "and table_catalog = '".$db_name."' ";
					$sql .= "ORDER BY ordinal_position; ";
				}
				if ($db_type == "mysql") {
					$sql = "describe ".$table_name.";";
				}
				$prep_statement = $this->db->prepare($sql);
				$prep_statement->execute();
				return $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			}
	}

//example use
	//require_once "resources/classes/schema.php";
	//$schema = new schema;
	//$schema->db_type = $db_type;
	//$schema->sql();
	//$result_array = $schema->result['sql'];
	//print_r($result_array);
