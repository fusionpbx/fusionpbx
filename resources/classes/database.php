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
	Copyright (C) 2010
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
include "root.php";

//define the database class
	if (!class_exists('database')) {
		class database {
			public $db;
			public $driver;
			public $type;
			public $host;
			public $port;
			public $db_name;
			public $username;
			public $password;
			public $path;
			public $table;
			public $where; //array
			public $order_by; //array
			public $order_type;
			public $limit;
			public $offset;
			public $fields;
			public $count;
			public $sql;
			public $result;

			public function connect() {

				if (strlen($this->type) == 0 && strlen($this->db_name) == 0) {
					//include config.php
						include "root.php";
						if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/resources/config.php")) {
							include $_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/resources/config.php";
						} elseif (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/resources/config.php")) {
							include $_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/resources/config.php";
						} elseif (file_exists("/etc/fusionpbx/config.php")){
							//linux
							include "/etc/fusionpbx/config.php";
						} elseif (file_exists("/usr/local/etc/fusionpbx/config.php")){
							//bsd
							include "/usr/local/etc/fusionpbx/config.php";
						}

					//backwards compatibility
						if (isset($dbtype)) { $db_type = $dbtype; }
						if (isset($dbhost)) { $db_host = $dbhost; }
						if (isset($dbport)) { $db_port = $dbport; }
						if (isset($dbname)) { $db_name = $dbname; }
						if (isset($dbusername)) { $db_username = $dbusername; }
						if (isset($dbpassword)) { $db_password = $dbpassword; }
						if (isset($dbfilepath)) { $db_path = $db_file_path; }
						if (isset($dbfilename)) { $db_name = $dbfilename; }

					//set defaults
						if (!isset($this->driver) && isset($db_type)) { $this->driver = $db_type; }
						if (!isset($this->type) && isset($db_type)) { $this->type = $db_type; }
						if (!isset($this->host) && isset($db_host)) { $this->host = $db_host; }
						if (!isset($this->port) && isset($db_port)) { $this->port = $db_port; }
						if (!isset($this->db_name) && isset($db_name)) { $this->db_name = $db_name; }
						if (!isset($this->username) && isset($db_username)) { $this->username = $db_username; }
						if (!isset($this->password) && isset($db_password)) { $this->password = $db_password; }
						if (!isset($this->path) && isset($db_path)) { $this->path = $db_path; }
				}
				if (strlen($this->driver) == 0) {
					$this->driver = $this->type;
				}

				if ($this->driver == "sqlite") {
					if (strlen($this->db_name) == 0) {
						$server_name = $_SERVER["SERVER_NAME"];
						$server_name = str_replace ("www.", "", $server_name);
						$db_name_short = $server_name;
						$this->db_name = $server_name.'.db';
					}
					else {
						$db_name_short = $this->db_name;
					}
					$this->path = realpath($this->path);
					if (file_exists($this->path.'/'.$this->db_name)) {
						$this->db = new PDO('sqlite:'.$this->path.'/'.$this->db_name); //sqlite 3
					}
					else {
						echo "not found";
					}
				}

				if ($this->driver == "mysql") {
					try {
						//required for mysql_real_escape_string
							if (function_exists(mysql_connect)) {
								$mysql_connection = mysql_connect($this->host, $this->username, $this->password);
							}
						//mysql pdo connection
							if (strlen($this->host) == 0 && strlen($this->port) == 0) {
								//if both host and port are empty use the unix socket
								$this->db = new PDO("mysql:host=$this->host;unix_socket=/var/run/mysqld/mysqld.sock;dbname=$this->db_name", $this->username, $this->password);
							}
							else {
								if (strlen($this->port) == 0) {
									//leave out port if it is empty
									$this->db = new PDO("mysql:host=$this->host;dbname=$this->db_name;", $this->username, $this->password, array(
									PDO::ATTR_ERRMODE,
									PDO::ERRMODE_EXCEPTION
									));
								}
								else {
									$this->db = new PDO("mysql:host=$this->host;port=$this->port;dbname=$this->db_name;", $this->username, $this->password, array(
									PDO::ATTR_ERRMODE,
									PDO::ERRMODE_EXCEPTION
									));
								}
							}
					}
					catch (PDOException $error) {
						print "error: " . $error->getMessage() . "<br/>";
						die();
					}
				}

				if ($this->driver == "pgsql") {
					//database connection
					try {
						if (strlen($this->host) > 0) {
							if (strlen($this->port) == 0) { $this->port = "5432"; }
							$this->db = new PDO("pgsql:host=$this->host port=$this->port dbname=$this->db_name user=$this->username password=$this->password");
						}
						else {
							$this->db = new PDO("pgsql:dbname=$this->db_name user=$this->username password=$this->password");
						}
					}
					catch (PDOException $error) {
						print "error: " . $error->getMessage() . "<br/>";
						die();
					}
				}

				if ($this->driver == "odbc") {
					//database connection
						try {
							$this->db = new PDO("odbc:".$this->db_name, $this->username, $this->password);
						}
						catch (PDOException $e) {
							echo 'Connection failed: ' . $e->getMessage();
						}
				}
			}

			public function tables() {
				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}
					if ($this->type == "sqlite") {
						$sql = "SELECT name FROM sqlite_master ";
						$sql .= "WHERE type='table' ";
						$sql .= "order by name;";
					}
					if ($this->type == "pgsql") {
						$sql = "select table_name as name ";
						$sql .= "from information_schema.tables ";
						$sql .= "where table_schema='public' ";
						$sql .= "and table_type='BASE TABLE' ";
						$sql .= "order by table_name ";
					}
					if ($this->type == "mysql") {
						$sql = "show tables";
					}
					if ($this->type == "mssql") {
						$sql = "SELECT * FROM sys.Tables order by name asc";
					}
					$prep_statement = $this->db->prepare(check_sql($sql));
					$prep_statement->execute();
					$tmp = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					if ($this->type == "pgsql" || $this->type == "sqlite" || $this->type == "mssql") {
						foreach ($tmp as &$row) {
							$result[]['name'] = $row['name'];
						}
					}
					if ($this->type == "mysql") {
						foreach ($tmp as &$row) {
							$table_array = array_values($row);
							$result[]['name'] = $table_array[0];
						}
					}
					return $result;
			}

			public function table_info() {
				//public $db;
				//public $type;
				//public $table;
				//public $name;

				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}
				//get the table info
					if (strlen($this->table) == 0) { return false; }
					if ($this->type == "sqlite") {
						$sql = "PRAGMA table_info(".$this->table.");";
					}
					if ($this->type == "pgsql") {
						$sql = "SELECT ordinal_position, ";
						$sql .= "column_name, ";
						$sql .= "data_type, ";
						$sql .= "column_default, ";
						$sql .= "is_nullable, ";
						$sql .= "character_maximum_length, ";
						$sql .= "numeric_precision ";
						$sql .= "FROM information_schema.columns ";
						$sql .= "WHERE table_name = '".$this->table."' ";
						$sql .= "and table_catalog = '".$this->db_name."' ";
						$sql .= "ORDER BY ordinal_position; ";
					}
					if ($this->type == "mysql") {
						$sql = "DESCRIBE ".$this->table.";";
					}
					if ($this->type == "mssql") {
						$sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '".$this->table."'";
					}
					$prep_statement = $this->db->prepare($sql);
					$prep_statement->execute();
				//set the result array
					return $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			}

			public function fields() {
				//public $db;
				//public $type;
				//public $table;
				//public $name;

				//get the table info
					$table_info = $this->table_info();

				//set the list of fields
					if ($this->type == "sqlite") {
						foreach($table_info as $row) {
							$result[]['name'] = $row['name'];
						}
					}
					if ($this->type == "pgsql") {
						foreach($table_info as $row) {
							$result[]['name'] = $row['column_name'];
						}
					}
					if ($this->type == "mysql") {
						foreach($table_info as $row) {
							$result[]['name'] = $row['Field'];
						}
					}
					if ($this->type == "mssql") {
						foreach($table_info as $row) {
							$result[]['name'] = $row['COLUMN_NAME'];
						}
					}

				//return the result array
					return $result;
			}

			//public function disconnect() {
			//	return null;
			//}

			public function find() {
				//connect;
				//table;
				//where;
				//order_by;
				//limit;
				//offset;

				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}
				//get data from the database
					$sql = "select * from ".$this->table." ";
					if ($this->where) {
						$i = 0;
						foreach($this->where as $row) {
							if ($i == 0) {
								$sql .= 'where '.$row['name']." ".$row['operator']." '".$row['value']."' ";
							}
							else {
								$sql .= "and ".$row['name']." ".$row['operator']." '".$row['value']."' ";
							}
							$i++;
						}
					}
					if (count($this->order_by) > 0) {
						$sql .= "order by ";
						$i = 1;
						foreach($this->order_by as $row) {
							if (count($this->order_by) == $i) {
								$sql .= $row['name']." ".$row['order']." ";
							}
							else {
								$sql .= $row['name']." ".$row['order'].", ";
							}
							$i++;
						}
					}
					if ($this->limit) {
						$sql .= " limit ".$this->limit." offset ".$this->offset." ";
					}
					//echo $sql;
					$prep_statement = $this->db->prepare($sql);
					if ($prep_statement) {
						$prep_statement->execute();
						return $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					}
					else {
						return false;
					}
			}

			// Use this function to execute complex queries
			public function execute(){
					$sql = $this->sql;
					//echo $sql;
				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}
				//get data from the database
					$prep_statement = $this->db->prepare($sql);
					if ($prep_statement) {
						$prep_statement->execute();
						return $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					}
					else {
						return false;
					}
			}

			public function add(){
				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}
				//add data to the database
					$sql = "insert into ".$this->table;
					$sql .= " (";
					$i = 1;
					foreach($this->fields as $name => $value) {
						if (count($this->fields) == $i) {
							$sql .= $name." ";
						}
						else {
							$sql .= $name.", ";
						}
						$i++;
					}
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$i = 1;
					foreach($this->fields as $name => $value) {
						if (count($this->fields) == $i) {
							if (strlen($value) > 0) {
								$sql .= "'".$value."' ";
							}
							else {
								$sql .= "'".$value."' ";
							}
						}
						else {
							if (strlen($value) > 0) {
								$sql .= "'".$value."', ";
							}
							else {
								$sql .= "null, ";
							}
						}
						$i++;
					}
					$sql .= ")";
				//execute the query, show exceptions
					$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					try {
						$this->sql = $sql;
						$this->db->exec($sql);
					}
					catch(PDOException $e) {
						echo "<b>Error:</b><br />\n";
						echo "<table>\n";
						echo "<tr>\n";
						echo "<td>\n";
						echo $e->getMessage();
						echo "</td>\n";
						echo "</tr>\n";
						echo "</table>\n";
					}
					unset($this->fields);
					unset($sql);
			}

			public function update() {
				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}
				//udate the database
					$sql = "update ".$this->table." set ";
					$i = 1;
					foreach($this->fields as $name => $value) {
						if (count($this->fields) == $i) {
							if (strlen($name) > 0 && $value == null) {
								$sql .= $name." = null ";
							}
							else {
								$sql .= $name." = '".$value."' ";
							}
						}
						else {
							if (strlen($name) > 0 && $value == null) {
								$sql .= $name." = null, ";
							}
							else {
								$sql .= $name." = '".$value."', ";
							}
						}
						$i++;
					}
					$i = 0;
					foreach($this->where as $row) {
						if ($i == 0) {
							$sql .= 'where '.$row['name']." ".$row['operator']." '".$row['value']."' ";
						}
						else {
							$sql .= "and ".$row['name']." ".$row['operator']." '".$row['value']."' ";
						}
						$i++;
					}
					$this->db->exec(check_sql($sql));
					unset($this->fields);
					unset($this->where);
					unset($sql);
			}

			public function delete(){
				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}
				//delete from the database
					$sql = "delete from ".$this->table." ";
					if ($this->where) {
						$i = 0;
						foreach($this->where as $row) {
							if ($i == 0) {
								$sql .= "where ".$row['name']." ".$row['operator']." '".$row['value']."' ";
							}
							else {
								$sql .= "and ".$row['name']." ".$row['operator']." '".$row['value']."' ";
							}
							$i++;
						}
					}
					//echo $sql."<br>\n";
					$prep_statement = $this->db->prepare($sql);
					$prep_statement->execute();
					unset($sql);
					unset($this->where);
			}

			public function count() {
				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}
				//get the number of rows
					$sql = "select count(*) as num_rows from ".$this->table." ";
					if ($this->where) {
						$i = 0;
						foreach($this->where as $row) {
							if ($i == 0) {
								$sql .= "where ".$row['name']." ".$row['operator']." '".$row['value']."' ";
							}
							else {
								$sql .= "and ".$row['name']." ".$row['operator']." '".$row['value']."' ";
							}
							$i++;
						}
					}
					unset($this->where);
					$prep_statement = $this->db->prepare(check_sql($sql));
					if ($prep_statement) {
						$prep_statement->execute();
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						if ($row['num_rows'] > 0) {
							$this->result = $row['num_rows'];
						}
						else {
							$this->result = 0;
						}
					}
					unset($prep_statement);
			}
		}
	}

if (!function_exists('php_md5')) {
	function php_md5($string) {
		return md5($string);
	}
}

if (!function_exists('php_unix_time_stamp')) {
	function php_unix_time_stamp($string) {
		return strtotime($string);
	}
}

if (!function_exists('php_now')) {
	function php_now() {
		return date("Y-m-d H:i:s");
	}
}

if (!function_exists('php_left')) {
	function php_left($string, $num) {
		return substr($string, 0, $num);
	}
}

if (!function_exists('php_right')) {
	function php_right($string, $num) {
		return substr($string, (strlen($string)-$num), strlen($string));
	}
}

//example usage
/*
//find
	require_once "resources/classes/database.php";
	$database = new database;
	$database->domain_uuid = $_SESSION["domain_uuid"];
	$database->type = $db_type;
	$database->table = "v_extensions";
	$where[0]['name'] = 'domain_uuid';
	$where[0]['value'] = $_SESSION["domain_uuid"];
	$where[0]['operator'] = '=';
	$database->where = $where;
	$order_by[0]['name'] = 'extension';
	$database->order_by = $order_by;
	$database->order_type = 'desc';
	$database->limit = '2';
	$database->offset = '0';
	$database->find();
	print_r($database->result);
//insert
	require_once "resources/classes/database.php";
	$database = new database;
	$database->domain_uuid = $_SESSION["domain_uuid"];
	$database->type = $db_type;
	$database->table = "v_ivr_menus";
	$fields[0]['name'] = 'domain_uuid';
	$fields[0]['value'] = $_SESSION["domain_uuid"];
	$database->add();
	print_r($database->result);
*/
?>
