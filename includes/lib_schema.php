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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
//require_once "includes/require.php";
//require_once "includes/classes/database.php";
//$db = new database;
//$db->db = $db;
//$db->db_type = $db_type;
//$db->add();

function db_table_exists_alternate ($db, $db_type, $table_name) {
	$sql = "select count(*) from $table_name ";
	$result = $db->query($sql);
	if ($result > 0) {
		return true; //table exists
	}
	else {
		return false; //table doesn't exist
	}
}

function db_table_exists ($db, $db_type, $db_name, $table_name) {
	$sql = "";
	if ($db_type == "sqlite") {
		$sql .= "SELECT * FROM sqlite_master WHERE type='table' and name='$table_name' ";
	}
	if ($db_type == "pgsql") {
		$sql .= "select * from pg_tables where schemaname='public' and tablename = '$table_name' ";
	}
	if ($db_type == "mysql") {
		$sql .= "SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = '$db_name' and TABLE_NAME = '$table_name' ";
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	if (count($result) > 0) {
		return true; //table exists
	}
	else {
		return false; //table doesn't exist
	}
}

function db_table_info($db, $db_name, $db_type, $table_name) {
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
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	return $prep_statement->fetchAll(PDO::FETCH_ASSOC);
}

function db_data_type($db_type, $table_info, $column_name) {
	if ($db_type == "sqlite") {
		foreach ($table_info as $key => &$row) {
			if ($row['name'] == $column_name) {
				return $row['type'];
			}
		}
	}
	if ($db_type == "pgsql") {
		foreach ($table_info as $key => &$row) {
			if ($row['column_name'] == $column_name) {
				return $row['data_type'];
			}
		}
	}
	if ($db_type == "mysql") {
		foreach ($table_info as $key => &$row) {
			if ($row['Field'] == $column_name) {
				return $row['Type'];
			}
		}
	}
}

function db_sqlite_column_exists($table_info, $column_name) {
	foreach ($table_info as $key => &$row) {
		if ($row['name'] == $column_name) {
			return true;
		}
	}
	return $false;
}

function db_column_exists ($db, $db_type, $db_name, $table_name, $column_name) {
	global $display_type;

	if ($db_type == "sqlite") {
		$table_info = db_table_info($db, $db_name, $db_type, $table_name);
		if (db_sqlite_column_exists($table_info, $column_name)) {
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
		$prep_statement = $db->prepare(check_sql($sql));
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

function db_column_data_type ($db, $db_type, $db_name, $table_name, $column_name) {
	$table_info = db_table_info($db, $db_name, $db_type, $table_name);
	return db_data_type($db_type, $table_info, $column_name);
}

function db_create_table ($apps, $db_type, $table) {
	foreach ($apps as $x => &$app) {
		foreach ($app['db'] as $y => $row) {
			if ($row['table'] == $table) {
				$sql = "CREATE TABLE " . $row['table'] . " (\n";
				$field_count = 0;
				foreach ($row['fields'] as $field) {
					if ($field['deprecated'] == "true") {
						//skip this row
					}
					else {
						if ($field_count > 0 ) { $sql .= ",\n"; }
						if (is_array($field['name'])) {
							$sql .= $field['name']['text'] . " ";
						}
						else {
							$sql .= $field['name'] . " ";
						}
						if (is_array($field['type'])) {
							$sql .= $field['type'][$db_type];
						}
						else {
							$sql .= $field['type'];
						}
						$field_count++;
					}
				}
				$sql .= ");\n\n";
				return $sql;
			}
		}
	}
}

function db_insert_into ($apps, $db_type, $table) {
	global $db, $db_name;
	foreach ($apps as $x => &$app) {
		foreach ($app['db'] as $y => $row) {
			if ($row['table'] == $table) {
				$sql = "INSERT INTO " . $row['table'] . " (";
				$field_count = 0;
				foreach ($row['fields'] as $field) {
					if ($field['deprecated'] == "true") {
						//skip this field
					}
					else {
						if ($field_count > 0 ) { $sql .= ","; }
						if (is_array($field['name'])) {
							$sql .= $field['name']['text'];
						}
						else {
							$sql .= $field['name'];
						}
						$field_count++;
					}
				}
				$sql .= ")\n";
				$sql .= "SELECT ";
				$field_count = 0;
				foreach ($row['fields'] as $field) {
					if ($field['deprecated'] == "true") {
						//skip this field
					}
					else {
						if ($field_count > 0 ) { $sql .= ","; }
						if (is_array($field['name'])) {
							if ($field['exists'] == "false") {
								if (is_array($field['name']['deprecated'])) {
									$found = false;
									foreach ($field['name']['deprecated'] as $row) {
										if (db_column_exists ($db, $db_type, $db_name, 'tmp_'.$table, $row)) {
											$sql .= $row;
											$found = true;
											break;
										}
									}
									if (!$found) { $sql .= "''"; }
								}
								else {
									if (db_column_exists ($db, $db_type, $db_name, 'tmp_'.$table, $field['name']['deprecated'])) {
										$sql .= $field['name']['deprecated'];
									}
									else {
										$sql .= "''";
									}
								}
							}
							else {
								$sql .= $field['name']['text'];
							}
						}
						else {
							$sql .= $field['name'];
						}
						$field_count++;
					}
				}
				$sql .= " FROM tmp_".$table.";\n\n";
				return $sql;
			}
		}
	}
}

function db_upgrade_schema ($db, $db_type, $db_name, $display_results) {
	global $display_type;

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

	//get the $apps array from the installed apps from the core and mod directories
		$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
		$x=0;
		foreach ($config_list as &$config_path) {
			include($config_path);
			$x++;
		}

	//update the app db array add exists true or false
		$sql = '';
		foreach ($apps as $x => &$app) {
			foreach ($app['db'] as $y => &$row) {
				if (is_array($row['table'])) {
					$table_name = $row['table']['text'];
				}
				else {
					$table_name = $row['table'];
				}
				if (strlen($table_name) > 0) {
					//check if the table exists
						if (db_table_exists($db, $db_type, $db_name, $table_name)) {
							$apps[$x]['db'][$y]['exists'] = 'true';
						}
						else {
							$apps[$x]['db'][$y]['exists'] = 'false';
						}
					//check if the column exists
						foreach ($row['fields'] as $z => $field) {
							if ($field['deprecated'] == "true") {
								//skip this field
							}
							else {
								if (is_array($field['name'])) {
									$field_name = $field['name']['text'];
								}
								else {
									$field_name = $field['name'];
								}
								if (strlen(field_name) > 0) {
									if (db_column_exists ($db, $db_type, $db_name, $table_name, $field_name)) {
										//found
										$apps[$x]['db'][$y]['fields'][$z]['exists'] = 'true';
									}
									else {
										//not found
										$apps[$x]['db'][$y]['fields'][$z]['exists'] = 'false';
									}
								}
								unset($field_name);
							}
						}
					unset($table_name);
				}
			}
		}

	//prepare the variables
		$sql_update = '';
		$var_uuid = $_GET["id"];

	//add missing tables and fields
		foreach ($apps as $x => &$app) {
			foreach ($app['db'] as $y => &$row) {
				if (is_array($row['table'])) {
					$table_name = $row['table']['text'];
					if (!db_table_exists($db, $db_type, $db_name, $row['table']['text'])) {
						$row['exists'] = "true"; //testing
						//if (db_table_exists($db, $db_type, $db_name, $row['table']['deprecated'])) {
							if ($db_type == "pgsql") {
								$sql_update .= "ALTER TABLE ".$row['table']['deprecated']." RENAME TO ".$row['table']['text'].";\n";
							}
							if ($db_type == "mysql") {
								$sql_update .= "RENAME TABLE ".$row['table']['deprecated']." TO ".$row['table']['text'].";\n";
							}
							if ($db_type == "sqlite") {
								$sql_update .= "ALTER TABLE ".$row['table']['deprecated']." RENAME TO ".$row['table']['text'].";\n";
							}
						//}
					}
				}
				else {
					$table_name = $row['table'];
				}
				//check if the table exists
					if ($row['exists'] == "true") {
						if (count($row['fields']) > 0) {
							foreach ($row['fields'] as $z => $field) {
								if ($field['deprecated'] == "true") {
									//skip this field
								}
								else {
									//get the data type
										if (is_array($field['type'])) {
											$field_type = $field['type'][$db_type];
										}
										else {
											$field_type = $field['type'];
										}
									//get the field name
										if (is_array($field['name'])) {
											$field_name = $field['name']['text'];
										}
										else {
											$field_name = $field['name'];
										}
									//find missing fields and add them
										if ($field['deprecated'] == "true") {
											//skip this row
										}
										else {
											if (!is_array($field['name'])) {
												if ($field['exists'] == "false") {
													$sql_update .= "ALTER TABLE ".$table_name." ADD ".$field['name']." ".$field_type.";\n";
												}
											}
										}
									//rename fields where the name has changed
										if (is_array($field['name'])) {
											if (db_column_exists ($db, $db_type, $db_name, $table_name, $field['name']['deprecated'])) {
												if ($db_type == "pgsql") {
													$sql_update .= "ALTER TABLE ".$table_name." RENAME COLUMN ".$field['name']['deprecated']." to ".$field['name']['text'].";\n";
												}
												if ($db_type == "mysql") {
													$field_type = str_replace("AUTO_INCREMENT PRIMARY KEY", "", $field_type);
													$sql_update .= "ALTER TABLE ".$table_name." CHANGE ".$field['name']['deprecated']." ".$field['name']['text']." ".$field_type.";\n";
												}
												if ($db_type == "sqlite") {
													//a change has been made to the field name
													$apps[$x]['db'][$y]['rebuild'] = 'true';
												}
											}
										}
									//change the data type if it has been changed
										//if the data type in the app db array is different than the type in the database then change the data type
										$db_field_type = db_column_data_type ($db, $db_type, $db_name, $table_name, $field_name);
										if ($db_field_type != $field_type) {
											if ($db_type == "pgsql") {
												if (strtolower($field_type) == "uuid") {
													$sql_update .= "ALTER TABLE ".$table_name." ALTER COLUMN ".$field_name." TYPE uuid USING\n";
													$sql_update .= "CAST(regexp_replace(".$field_name.", '([A-Z0-9]{4})([A-Z0-9]{12})', E'\\1-\\2')\n";
													$sql_update .= "AS uuid);\n";
												}
												else {
													if ($db_field_type = "integer" && strtolower($field_type) == "serial") {
														//field type has not changed
													} elseif ($db_field_type = "timestamp without time zone" && strtolower($field_type) == "timestamp") {
														//field type has not changed
													} elseif ($db_field_type = "character" && strtolower($field_type) == "char(1)") {
														//field type has not changed
													}
													else {
														//$sql_update .= "-- $db_type, $db_name, $table_name, $field_name ".db_column_data_type ($db, $db_type, $db_name, $table_name, $field_name)."<br>";
														$sql_update .= "ALTER TABLE ".$table_name." ALTER COLUMN ".$field_name." TYPE ".$field_type.";\n";
													}
												}
											}
											if ($db_type == "mysql") {
												$type = explode("(", $db_field_type);
												if ($type[0] == $field_type) {
													//do nothing
												}
												elseif ($field_type == "numeric" && $type[0] == "decimal") {
													//do nothing
												}
												else {
													$sql_update .= "ALTER TABLE ".$table_name." modify ".$field_name." ".$field_type.";\n";
												}
												unset($type);
											}
											if ($db_type == "sqlite") {
												//a change has been made to the field type
												$apps[$x]['db'][$y]['rebuild'] = 'true';
											}
										}
								}
							}
							unset($column_array);
						}
					}
					else {
						//create table
							if (!is_array($row['table'])) {
								$sql_update .= db_create_table($apps, $db_type, $row['table']);
							}
					}
			}
		}
	//rebuild and populate the table
		foreach ($apps as $x => &$app) {
			foreach ($app['db'] as $y => &$row) {
				if (is_array($row['table'])) {
					$table_name = $row['table']['text'];
				}
				else {
					$table_name = $row['table'];
				}
				if ($row['rebuild'] == "true") {
					if ($db_type == "sqlite") {
						//start the transaction
							//$sql_update .= "BEGIN TRANSACTION;\n";
						//rename the table
							$sql_update .= "ALTER TABLE ".$table_name." RENAME TO tmp_".$table_name.";\n";
						//create the table
							$sql_update .= db_create_table($apps, $db_type, $table_name);
						//insert the data into the new table
							$sql_update .= db_insert_into($apps, $db_type, $table_name);
						//drop the old table
							$sql_update .= "DROP TABLE tmp_".$table_name.";\n";
						//commit the transaction
							//$sql_update .= "COMMIT;\n";
					}
				}
			}
		}
	//display results as html
		if ($display_results && $display_type == "html") {
			//show the database type
				echo "<strong>Database Type: ".$db_type. "</strong><br /><br />";
			//start the table
				echo "<table width='100%' border='0' cellpadding='20' cellspacing='0'>\n";
			//show the changes
				if (strlen($sql_update) > 0) {
					echo "<tr>\n";
					echo "<td class='row_style1' colspan='3'>\n";
					echo "<br />\n";
					echo "<strong>SQL Changes:</strong><br />\n";
					echo "<pre>\n";
					echo $sql_update;
					echo "</pre>\n";
					echo "<br />\n";
					echo "</td>\n";
					echo "</tr>\n";
				}
			//list all tables
				echo "<tr>\n";
				echo "<th>Table</th>\n";
				echo "<th>Exists</th>\n";
				echo "<th>Details</th>\n";
				echo "<tr>\n";
			//build the html while looping through the app db array
				$sql = '';
				foreach ($apps as &$app) {
					foreach ($app['db'] as $row) {
						if (is_array($row['table'])) {
							$table_name = $row['table']['text'];
						}
						else {
							$table_name = $row['table'];
						}
						echo "<tr>\n";

						//check if the table exists
							if ($row['exists'] == "true") {
								echo "<td valign='top' class='row_style1'><strong>table</strong><br />$table_name</td>\n";
								echo "<td valign='top' class='vncell' style=''>true</td>\n";

								if (count($row['fields']) > 0) {
									echo "<td class='row_style1'>\n";
									//show the list of columns
										echo "<table border='0' cellpadding='10' cellspacing='0'>\n";
										echo "<tr>\n";
										echo "<th>name</th>\n";
										echo "<th>type</th>\n";
										echo "<th>exists</th>\n";
										echo "</tr>\n";
										foreach ($row['fields'] as $field) {
											if ($field['deprecated'] == "true") {
												//skip this field
											}
											else {
												if (is_array($field['name'])) {
													$field_name = $field['name']['text'];
												}
												else {
													$field_name = $field['name'];
												}
												if (is_array($field['type'])) {
													$field_type = $field['type'][$db_type];
												}
												else {
													$field_type = $field['type'];
												}
												echo "<tr>\n";
												echo "<td class='row_style1' width='200'>".$field_name."</td>\n";
												echo "<td class='row_style1'>".$field_type."</td>\n";
												if ($field['exists'] == "true") {
													echo "<td class='row_style0' style=''>true</td>\n";
													echo "<td>&nbsp;</td>\n";
												}
												else {
													echo "<td class='row_style1' style='background-color:#444444;color:#CCCCCC;'>false</td>\n";
													echo "<td>&nbsp;</td>\n";
												}
												echo "</tr>\n";
											}
										}
										unset($column_array);
										echo "	</table>\n";
										echo "</td>\n";
								}
							}
							else {
								echo "<td valign='top' class='row_style1'><strong>table</strong><br />$table_name</td>\n";
								echo "<td valign='top' class='row_style1' style='background-color:#444444;color:#CCCCCC;'><strong>exists</strong><br />false</td>\n";
								echo "<td valign='top' class='row_style1'>&nbsp;</td>\n";
							}
							echo "</tr>\n";
					}
				}
				unset ($prep_statement);
			//end the list of tables
				echo "</table>\n";
				echo "<br />\n";
		}

		//loop line by line through all the lines of sql code
			$x = 0;
			if (strlen($sql_update) == 0 && $display_type == "text") {
				echo "	Schema:			no change\n";
			}
			else {
				if ($display_type == "text") {
					echo "	Schema:\n";
				}
				//$db->beginTransaction();
				$update_array = explode(";", $sql_update);
				foreach($update_array as $sql) {
					if (strlen(trim($sql))) {
						try {
							$db->query(trim($sql));
							if ($display_type == "text") {
								echo "	$sql\n";
							}
						}
						catch (PDOException $error) {
							if ($display_results) {
								echo "	error: " . $error->getMessage() . "	sql: $sql<br/>";
							}
						}
					}
				}
				//$db->commit();
				echo "\n";
				unset ($file_contents, $sql_update, $sql);
			}

} //end function

?>