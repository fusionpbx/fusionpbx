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
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/schema.php";

if (if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//show errors
	ini_set('display_errors', '1');
	//error_reporting (E_ALL); // Report everything
	error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ); //hide notices and warnings

//define the db file exists function
	function db_field_exists ($tmp_array, $column) {
		$result = false;
		foreach ($tmp_array as &$row) {
			if ($row[0] == $column) {
				$result = true;
			}
			return $result;
		}
	}
	//db_field_exists ($result_dest, $column)

//destination info
	//set the domain_uuid
		$dest_domain_uuid = '1';

	//set the database type
		$db_dest_type = 'mysql'; //sqlite, mysql, pgsql, others with a manually created PDO connection

	//sqlite: the dbfilename and db_file_path are automatically assigned however the values can be overidden by setting the values here.
		//$dbfilename = 'fusionpbx.db'; //host name/ip address + '.db' is the default database filename
		//$db_file_path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/secure'; //the path is determined by a php variable

	//mysql: database connection information
		$db_host = '127.0.0.1'; //set the host only if the database is not local
		$db_port = '3306';
		$db_name = 'fusionpbx';
		$db_username = 'fusionpbx';
		$db_password = '';
		$db_create_username = 'root';
		$db_create_password = '';

	//pgsql: database connection information
		//$db_host = ''; //set the host only if the database is not local
		//$db_port = '';
		//$db_name = '';
		//$db_username = '';
		//$db_password = '';
		//$db_create_username = '';
		//$db_create_password = '';

	//load data into the database

		//create the sqlite database
			if ($db_dest_type == "sqlite") {
				//sqlite database will be created when the config.php is loaded and only if the database file does not exist
				$filename = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/sql/sqlite.sql';
				$file_contents = file_get_contents($filename);
				unset($filename);
				try {
					$db_dest = new PDO('sqlite:'.$db_filepath.'/'.$db_filename); //sqlite 3
					//$db_dest = new PDO('sqlite::memory:'); //sqlite 3
					$db_dest->beginTransaction();
				}
				catch (PDOException $error) {
					print $text['label-error'].": " . $error->getMessage() . "<br/>";
					die();
				}

				//replace \r\n with \n then explode on \n
					$file_contents = str_replace("\r\n", "\n", $file_contents);

				//loop line by line through all the lines of sql code
					$stringarray = explode("\n", $file_contents);
					$x = 0;
					foreach($stringarray as $sql) {
						try {
							if(stristr($sql, 'CREATE TABLE') === FALSE) {
								//not found do not execute
							}
							else {
								//execute create table sql strings
								$db_dest->query($sql);
							}
						}
						catch (PDOException $error) {
							echo $text['label-error'].": " . $error->getMessage() . " sql: $sql<br/>";
						}
						$x++;
					}
					unset ($file_contents, $sql);
					$db_dest->commit();
			}

		//create the postgres database
			if ($db_dest_type == "pgsql") {
				$filename = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/sql/pgsql.sql';
				$file_contents = file_get_contents($filename);

				//if $db_create_username provided, attempt to create new PG role and database
					if (strlen($db_create_username) > 0) {
						//create the database connection
							try {
								if (strlen($db_port) == 0) { $db_port = "5432"; }
								if (strlen($db_host) > 0) {
									$db_dest = new PDO("pgsql:host={$db_host} port={$db_port} user={$db_create_username} password={$db_create_password} dbname=template1");
								} else {
									$db_dest = new PDO("pgsql:host=localhost port={$db_port} user={$db_create_username} password={$db_create_password} dbname=template1");
								}
							} catch (PDOException $error) {
								print $text['label-error'].": " . $error->getMessage() . "<br/>";
								die();
							}
						//create the database, user, grant perms
							$db_dest->exec("CREATE DATABASE {$db_name}");
							$db_dest->exec("CREATE USER {$db_username} WITH PASSWORD '{$db_password}'");
							$db_dest->exec("GRANT ALL ON {$db_name} TO {$db_username}");
						//close database connection_aborted
							$db_dest = null;
					}

				//open database connection with $db_name
					try {
						if (strlen($db_port) == 0) { $db_port = "5432"; }
						if (strlen($db_host) > 0) {
							$db_dest = new PDO("pgsql:host={$db_host} port={$db_port} dbname={$db_name} user={$db_username} password={$db_password}");
						} else {
							$db_dest = new PDO("pgsql:host=localhost port={$db_port} user={$db_username} password={$db_password} dbname={$db_name}");
						}
					}
					catch (PDOException $error) {
						print $text['label-error'].": " . $error->getMessage() . "<br/>";
						die();
					}

				//replace \r\n with \n then explode on \n
					$file_contents = str_replace("\r\n", "\n", $file_contents);

				//loop line by line through all the lines of sql code
					$stringarray = explode("\n", $file_contents);
					$x = 0;
					foreach($stringarray as $sql) {
						if (strlen($sql) > 3) {
							try {
								if(stristr($sql, 'CREATE TABLE') === FALSE) {
									//not found do not execute
								}
								else {
									//execute create table sql strings
									$db_dest->query($sql);
								}
							}
							catch (PDOException $error) {
								echo $text['label-error'].": " . $error->getMessage() . " sql: $sql<br/>";
								die();
							}
						}
						$x++;
					}
					unset ($file_contents, $sql);
			}

		//create the mysql database
		if ($db_dest_type == "mysql") {
			$filename = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/sql/mysql.sql';
			$file_contents = file_get_contents($filename);

			//database connection
				try {
					if (strlen($db_host) == 0 && strlen($db_port) == 0) {
						//if both host and port are empty use the unix socket
						if (strlen($db_create_username) == 0) {
							$db_dest = new PDO("mysql:host=$db_host;unix_socket=/var/run/mysqld/mysqld.sock;charset=utf8;", $db_username, $db_password);
						}
						else {
							$db_dest = new PDO("mysql:host=$db_host;unix_socket=/var/run/mysqld/mysqld.sock;charset=utf8;", $db_create_username, $db_create_password);						}
					}
					else {
						if (strlen($db_port) == 0) {
							//leave out port if it is empty
							if (strlen($db_create_username) == 0) {
								$db_dest = new PDO("mysql:host=$db_host;charset=utf8;", $db_username, $db_password);
							}
							else {
								$db_dest = new PDO("mysql:host=$db_host;charset=utf8;", $db_create_username, $db_create_password);
							}
						}
						else {
							if (strlen($db_create_username) == 0) {
								$db_dest = new PDO("mysql:host=$db_host;port=$db_port;charset=utf8;", $db_username, $db_password);
							}
							else {
								$db_dest = new PDO("mysql:host=$db_host;port=$db_port;charset=utf8;", $db_create_username, $db_create_password);
							}
						}
					}
					$db_dest->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db_dest->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
				}
				catch (PDOException $error) {
					if ($v_debug) {
						print $text['label-error'].": " . $error->getMessage() . "<br/>";
					}
				}

			//create the table, user and set the permissions only if the db_create_username was provided
				if (strlen($db_create_username) > 0) {
					//select the mysql database
						try {
							$db_dest->query("USE mysql;");
						}
						catch (PDOException $error) {
							if ($v_debug) {
								print $text['label-error'].": " . $error->getMessage() . "<br/>";
							}
						}
					//create user and set the permissions
						try {
							$tmp_sql = "CREATE USER '".$db_username."'@'%' IDENTIFIED BY '".$db_password."'; ";
							$db_dest->query($tmp_sql);
						}
						catch (PDOException $error) {
							if ($v_debug) {
								print $text['label-error'].": " . $error->getMessage() . "<br/>";
							}
						}
					//set account to unlimitted use
						try {
							$tmp_sql = "GRANT USAGE ON * . * TO '".$db_username."'@'localhost' ";
							$tmp_sql .= "IDENTIFIED BY '".$db_password."' ";
							$tmp_sql .= "WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0; ";
							$db_dest->query($tmp_sql);
						}
						catch (PDOException $error) {
							if ($v_debug) {
								print $text['label-error'].": " . $error->getMessage() . "<br/>";
							}
						}
					//create the database and set the create user with permissions
						try {
							$tmp_sql = "CREATE DATABASE IF NOT EXISTS ".$db_name."; ";
							$db_dest->query($tmp_sql);
						}
						catch (PDOException $error) {
							if ($v_debug) {
								print $text['label-error'].": " . $error->getMessage() . "<br/>";
							}
						}
					//set user permissions
						try {
							$db_dest->query("GRANT ALL PRIVILEGES ON ".$db_name.".* TO '".$db_username."'@'%'; ");
						}
						catch (PDOException $error) {
							if ($v_debug) {
								print $text['label-error'].": " . $error->getMessage() . "<br/>";
							}
						}
					//make the changes active
						try {
							$tmp_sql = "FLUSH PRIVILEGES; ";
							$db_dest->query($tmp_sql);
						}
						catch (PDOException $error) {
							if ($v_debug) {
								print $text['label-error'].": " . $error->getMessage() . "<br/>";
							}
						}
				} //if (strlen($db_create_username) > 0)
			//select the database
				try {
					$db_dest->query("USE ".$db_name.";");
				}
				catch (PDOException $error) {
					if ($v_debug) {
						print $text['label-error'].": " . $error->getMessage() . "<br/>";
					}
				}

			//add the defaults data into the database
				//replace \r\n with \n then explode on \n
					$file_contents = str_replace("\r\n", "\n", $file_contents);

				//loop line by line through all the lines of sql code
					$stringarray = explode("\n", $file_contents);
					$x = 0;
					foreach($stringarray as $sql) {
						if (strlen($sql) > 3) {
							try {
								if(stristr($sql, 'CREATE TABLE') === FALSE) {
									//not found do not execute
								}
								else {
									//execute create table sql strings
									$db_dest->query($sql);
								}
							}
							catch (PDOException $error) {
								//echo "error on line $x: " . $error->getMessage() . " sql: $sql<br/>";
								//die();
							}
						}
						$x++;
					}
					unset ($file_contents, $sql);
		}

//get the list of tables
	if ($db_dest_type == "sqlite") {
		$sql = "SELECT name FROM sqlite_master ";
		$sql .= "WHERE type='table' ";
		$sql .= "order by name;";
	}
	if ($db_dest_type == "pgsql") {
		$sql = "select table_name as name ";
		$sql .= "from information_schema.tables ";
		$sql .= "where table_schema='public' ";
		$sql .= "and table_type='BASE TABLE' ";
		$sql .= "order by table_name ";
	}
	if ($db_dest_type == "mysql") {
		$sql = "show tables";
	}
	//get the default schema structure
		$prep_statement = $db_dest->prepare(check_sql($sql));
		$prep_statement->execute();
		$result_dest = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	//clean the content from the table
		foreach ($result_dest as &$row) {
			$table_name = $row[0];
			$sql = 'delete from '.$table_name;
			//$db_dest->query($sql);
		}

	//add data into each table
		foreach ($result_dest as &$row) {
			//get the table name
				$table_name = $row[0];

			//$table_name = 'v_extensions';
			//$db_dest_type = "sqlite";

			//get the table source data
				$destination_column_array='';
				unset($destination_column_array);
				if ($db_dest_type == "sqlite") {
					$tmp_sql = "PRAGMA table_info($table_name);";
				}
				if ($db_dest_type == "pgsql") {

				}
				if ($db_dest_type == "mysql") {
					$tmp_sql = "show columns from $table_name;";
				}
				if (strlen($tmp_sql) > 0) {
					$prep_statement_2 = $db_dest->prepare(check_sql($tmp_sql));
					//$prep_statement_2 = $db->prepare(check_sql($tmp_sql));
					if ($prep_statement_2) {
						$prep_statement_2->execute();
						$result2 = $prep_statement_2->fetchAll(PDO::FETCH_ASSOC);
					}
					else {
						echo "<b>".$text['label-error'].":</b>\n";
						echo "<pre>\n";
						print_r($db_dest->errorInfo());
						echo "</pre>\n";
					}
					$x = 0;
					foreach ($result2 as $row2) {
						if ($db_dest_type == "sqlite") {
							$destination_column_array[$x] = $row2['name'];
						}
						if ($db_dest_type == "mysql") {
							$destination_column_array[$x] = $row2['Field'];
						}
						if ($db_dest_type == "pgsql") {

						}
						$x++;
					}
					/*
						$x = 0;
						foreach ($result2[0] as $key => $value) {
							if ($db_dest_type == "sqlite" && $key == "name") {
								$destination_column_array[$x] = $key;
							}
							$x++;
						}
					*/
					$destination_column_array_count = count($destination_column_array);
				}
				unset($prep_statement_2, $result2);
				//echo "<pre>\n";
				//print_r($destination_column_array);
				//echo "</pre>\n";

			//get the table source data
				$tmp_sql = "select * from $table_name";
				if (strlen($tmp_sql) > 0) {
					$prep_statement_2 = $db->prepare(check_sql($tmp_sql));
					if ($prep_statement_2) {
						$prep_statement_2->execute();
						$result2 = $prep_statement_2->fetchAll(PDO::FETCH_ASSOC);
					}
					else {
						echo "<b>".$text['label-error'].":</b>\n";
						echo "<pre>\n";
						print_r($db->errorInfo());
						echo "</pre>\n";
					}

					$x = 0;
					foreach ($result2[0] as $key => $value) {
						$column_array[$x] = $key;
						$x++;
					}

					foreach ($result2 as &$row) {
						//build the sql query string
							if (substr($table_name, 0, 2) == 'v_') {
								$sql = "INSERT INTO $table_name (";
								$x = 1;
								foreach ($destination_column_array as $column) {
									if ($x < $destination_column_array_count) {
										$sql .= "".$column.", ";
									}
									else {
										$sql .= "".$column."";
									}
									$x++;
								}
								$sql .= ") ";
								$sql .= "VALUES( ";
								$x = 1;
								foreach ($destination_column_array as $column) {
									if ($x < $destination_column_array_count) {
										//if ($column == "domain_uuid") {
										//	$sql .= "'".$dest_domain_uuid."',";
										//}
										//else {
											$sql .= "'".check_str($row[$column])."', ";
										//}
									}
									else {
										//if ($column == "domain_uuid") {
										//	$sql .= "'".$dest_domain_uuid."'";
										//}
										//else {
											$sql .= "'".check_str($row[$column])."'";
										//}
									}
									$x++;
								}
								$sql .= ");\n";
							}
						//add the sql into the destination database
							echo $sql."<br />\n";
							$db_dest->query($sql);
					}
				}
		}

?>