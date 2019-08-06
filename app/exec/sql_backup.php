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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permisions
	if (permission_exists('exec_sql_backup')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//pdo database connection
	if (strlen($_REQUEST['id']) > 0) {
		require_once "sql_query_pdo.php";
	}

//get the $apps array from the installed apps from the core and mod directories
	$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
	$x = 0;
	foreach ($config_list as &$config_path) {
		include($config_path);
		$x++;
	}

//define a function that checks if the field exists
	function field_exists($apps, $table_name, $field_name) {
		$result = false;
		foreach ($apps as &$row) {
			$tables = $row["db"];
			foreach ($tables as &$table) {
				if ($table['table'] == $table_name) {
					foreach ($table["fields"] as &$field) {
						if ($field['deprecated'] != "true") {
							if (is_array($field["name"])) {
								if ($field["name"]["text"] == $field_name) {
									$result = true;
									break;
								}
							}
							else {
								if ($field["name"] == $field_name) {
									$result = true;
									break;
								}
							}
						}
					}
				}
			}
		}
		return $result;
	}

//set the headers
	header('Content-type: application/octet-binary');
	header('Content-Disposition: attachment; filename=database_backup.sql');

//get the list of tables
	if ($db_type == "sqlite") {
		$sql = "select name from sqlite_master ";
		$sql .= "where type='table' ";
		$sql .= "order by name;";
	}
	if ($db_type == "pgsql") {
		$sql = "select table_name as name ";
		$sql .= "from information_schema.tables ";
		$sql .= "where table_schema='public' ";
		$sql .= "and table_type='BASE TABLE' ";
		$sql .= "order by table_name ";
	}
	if ($db_type == "mysql") {
		$sql = "show tables";
	}
	$database = new database;
	$result_1 = $database->select($sql, null, 'all');
	unset($sql);

	if (is_array($result_1) && @sizeof($result_1) != 0) {
		foreach ($result_1 as &$row_1) {
			$row_1 = array_values($row_1);
			$table_name = $row_1[0];

			//get the table data
				$sql = "select * from ".$table_name;
				$database = new database;
				$result_2 = $database->select($sql, null, 'all');
				unset($sql);

				foreach ($result_2[0] as $key => $value) {
					if ($row_1[$column] != "db") {
						if (field_exists($apps, $table_name, $key)) {
							$column_array[] = $key;
						}
					}
				}

				$column_array_count = count($column_array);

				foreach ($result_2 as &$row_2) {
					foreach ($column_array as $column) {
						$columns[] = $column;
						$values[] = $row_2[$column] != '' ? "'".check_str($row_2[$column])."'" : 'null';
					}
					$sql = "insert into ".$table_name." (";
					$sql .= implode(', ', $columns);
					$sql .= ") values ( ";
					$sql .= implode(', ', $values);
					$sql .= ");";
					echo $sql."\n";

					unset($columns, $values);
				}
				unset($result_2, $row_2);

			unset($column_array);
		}
	}
	unset($result_1, $row_1);

?>