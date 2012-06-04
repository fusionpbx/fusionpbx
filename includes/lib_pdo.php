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
 Raymond Chandler <intralanman@gmail.com>
 */
include "root.php";
require_once "includes/lib_functions.php";

//set defaults
	if (isset($dbtype) > 0) { 
		$db_type = $dbtype; 
	}
	if (isset($dbhost) > 0) { 
		$db_host = $dbhost; 
	}
	if (isset($dbport) > 0) { 
		$db_port = $dbport; 
	}
	if (isset($dbname) > 0) { 
		$db_name = $dbname; 
	}
	if (isset($dbusername) > 0) { 
		$db_username = $dbusername; 
	}
	if (isset($dbpassword) > 0) { 
		$db_password = $dbpassword; 
	}
	if (isset($db_file_path) > 0) { 
		$db_path = $db_file_path; 
	}
	if (isset($dbfilename) > 0) { 
		$db_name = $dbfilename; 
	}

if (!function_exists('get_db_field_names')) {
	function get_db_field_names($db, $table, $db_name='fusionpbx') {
		$query = sprintf('SELECT * FROM %s LIMIT 1', $table);
		foreach ($db->query($query, PDO::FETCH_ASSOC) as $row) {
			return array_keys($row);
		}

		// if we're still here, we need to try something else
		$fields 	= array();
		$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
		if ($driver == 'sqlite') {
			$query 		= sprintf("Pragma table_info(%s);", $table);
			$stmt 		= $db->prepare($query);
			$result 	= $stmt->execute();
			$rows 		= $stmt->fetchAll(PDO::FETCH_NAMED);
			//printf('<pre>%s</pre>', print_r($rows, true));
			$row_count 	= count($rows);
			//printf('<pre>%s</pre>', print_r($rows, true));
			for ($i = 0; $i < $row_count; $i++) {
				array_push($fields, $rows[$i]['name']);
			}
			return $fields;
		} else {
			$query 		= sprintf("SELECT * FROM information_schema.columns
			WHERE table_schema='%s' AND table_name='%s';"
			, $db_name, $table
			);
			$stmt 		= $db->prepare($query);
			$result 	= $stmt->execute();
			$rows 		= $stmt->fetchAll(PDO::FETCH_NAMED);
			$row_count 	= count($rows);
			//printf('<pre>%s</pre>', print_r($rows, true));
			for ($i = 0; $i < $row_count; $i++) {
				array_push($fields, $rows[$i]['COLUMN_NAME']);
			}
			return $fields;
		}
	}
}

if ($db_type == "sqlite") {
	//prepare the database connection
		if (strlen($db_name) == 0) {
			//if (strlen($_SERVER["SERVER_NAME"]) == 0) { $_SERVER["SERVER_NAME"] = "http://localhost"; }
			$server_name = $_SERVER["SERVER_NAME"];
			$server_name = str_replace ("www.", "", $server_name);
			//$server_name = str_replace (".", "_", $server_name);
			$db_name_short = $server_name;
			$db_name = $server_name.'.db';
		}
		else {
			$db_name_short = $db_name;
		}

		$filepath = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/secure';
		$db_path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/secure';
		$db_path = realpath($db_path);
		if (file_exists($db_path.'/'.$db_name)) {
			//echo "database file exists<br>";
		}
		else {
			if (is_writable($db_path.'/'.$db_name)) {
				//use database in current location
			}
			else { //not writable
				echo "The database ".$db_path."/".$db_name." does not exist or is not writable.";
				exit;
			}
		}

		if (!function_exists('php_md5')) {
			function php_md5($string) {
				return md5($string);
			}
		}
		if (!function_exists('php_unix_timestamp')) {
			function php_unix_timestamp($string) {
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

	//database connection
		try {
			//create the database connection object
				//$db = new PDO('sqlite2:example.db'); //sqlite 2
				//$db = new PDO('sqlite::memory:'); //sqlite 3
				$db = new PDO('sqlite:'.$db_path.'/'.$db_name); //sqlite 3
			//enable foreign key constraints
				$db->query('PRAGMA foreign_keys = ON;');
			//add additional functions to SQLite so that they are accessible inside SQL
				//bool PDO::sqliteCreateFunction ( string function_name, callback callback [, int num_args] )
				$db->sqliteCreateFunction('md5', 'php_md5', 1);
				$db->sqliteCreateFunction('unix_timestamp', 'php_unix_timestamp', 1);
				$db->sqliteCreateFunction('now', 'php_now', 0);
				$db->sqliteCreateFunction('sqlitedatatype', 'phpsqlitedatatype', 2);
				$db->sqliteCreateFunction('strleft', 'php_left', 2);
				$db->sqliteCreateFunction('strright', 'php_right', 2);
		}
		catch (PDOException $error) {
			print "error: " . $error->getMessage() . "<br/>";
			die();
		}
} //end if db_type sqlite


if ($db_type == "mysql") {
	//database connection
	try {
		//required for mysql_real_escape_string
			if (function_exists(mysql_connect)) {
				$mysql_connection = mysql_connect($db_host, $db_username, $db_password);
			}
		//mysql pdo connection
			if (strlen($db_host) == 0 && strlen($db_port) == 0) {
				//if both host and port are empty use the unix socket
				$db = new PDO("mysql:host=$db_host;unix_socket=/var/run/mysqld/mysqld.sock;dbname=$db_name", $db_username, $db_password);
			}
			else {
				if (strlen($db_port) == 0) {
					//leave out port if it is empty
					$db = new PDO("mysql:host=$db_host;dbname=$db_name;", $db_username, $db_password, array(
					PDO::ATTR_ERRMODE,
					PDO::ERRMODE_EXCEPTION
					));
				}
				else {
					$db = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;", $db_username, $db_password, array(
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
} //end if db_type mysql


if ($db_type == "pgsql") {
	//database connection
	try {
		if (strlen($db_host) > 0) {
			if (strlen($db_port) == 0) { $db_port = "5432"; }
			$db = new PDO("pgsql:host=$db_host port=$db_port dbname=$db_name user=$db_username password=$db_password");
		}
		else {
			$db = new PDO("pgsql:dbname=$db_name user=$db_username password=$db_password");
		}
	}
	catch (PDOException $error) {
		print "error: " . $error->getMessage() . "<br/>";
		die();
	}
} //end if db_type pgsql

//domain list
	if (strlen($_SESSION["domain_uuid"]) == 0) {
		//get the domain
			$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
		//get the domain_uuid
			$sql = "select * from v_domains ";
			$sql .= "order by domain_name asc ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $row) {
				if (count($result) == 1) {
					$_SESSION["domain_uuid"] = $row["domain_uuid"];
					$_SESSION["domain_name"] = $row['domain_name'];
				}
				else {
					if ($row['domain_name'] == $domain_array[0] || $row['domain_name'] == 'www.'.$domain_array[0]) {
						$_SESSION["domain_uuid"] = $row["domain_uuid"];
						$_SESSION["domain_name"] = $row["domain_name"];
					}
				}
				$_SESSION['domains'][$row['domain_uuid']]['domain_uuid'] = $row['domain_uuid'];
				$_SESSION['domains'][$row['domain_uuid']]['domain_name'] = $row['domain_name'];
			}
			unset($result, $prep_statement);
	}

//get the session settings
	if (!isset($_SESSION['domain']['menu'])) {
		//get the default settings
			$sql = "select * from v_default_settings ";
			$sql .= "where default_setting_enabled = 'true' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $row) {
				$name = $row['default_setting_name'];
				$category = $row['default_setting_category'];
				$subcategory = $row['default_setting_subcategory'];	
				if (strlen($subcategory) == 0) {
					$_SESSION[$category][$name] = $row['default_setting_value'];
				}
				else {
					$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
				}
			}

		//get the domains settings
			$sql = "select * from v_domain_settings ";
			$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
			$sql .= "and domain_setting_enabled = 'true' ";
			$prep_statement = $db->prepare($sql);
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $row) {
				$name = $row['domain_setting_name'];
				$category = $row['domain_setting_category'];
				$subcategory = $row['domain_setting_subcategory'];
				if (strlen($subcategory) == 0) {
					//$$category[$name] = $row['domain_setting_value'];
					$_SESSION[$category][$name] = $row['domain_setting_value'];
				}
				else {
					//$$category[$subcategory][$name] = $row['domain_setting_value'];
					$_SESSION[$category][$subcategory][$name] = $row['domain_setting_value'];
				}
			}

		//get the user settings
			$sql = "select * from v_user_settings ";
			$sql .= "where domain_uuid = '".$_SESSION["domain_uuid"]."' ";
			$sql .= "and user_uuid = '".$_SESSION["user_uuid"]."' ";
			$sql .= "and user_setting_enabled = 'true' ";
			$prep_statement = $db->prepare($sql);
			if ($prep_statement) {
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach($result as $row) {
					$name = $row['user_setting_name'];
					$category = $row['user_setting_category'];
					$subcategory = $row['user_setting_subcategory'];
					if (strlen($subcategory) == 0) {
						//$$category[$name] = $row['domain_setting_value'];
						$_SESSION[$category][$name] = $row['user_setting_value'];
					}
					else {
						//$$category[$subcategory][$name] = $row['domain_setting_value'];
						$_SESSION[$category][$subcategory][$name] = $row['user_setting_value'];
					}
				}
			}

		//set the values from the session variables
			if (strlen($_SESSION['domain']['time_zone']['name']) > 0) {
				//server time zone
					$_SESSION['time_zone']['system'] = date_default_timezone_get();
				//domain time zone set in system settings
					$_SESSION['time_zone']['domain'] = $_SESSION['domain']['time_zone']['name'];
				//set the domain time zone as the default time zone
					date_default_timezone_set($_SESSION['domain']['time_zone']['name']);
			}

		//set the context
			if (strlen($_SESSION["context"]) == 0) {
				if (count($_SESSION["domains"]) > 1) {
					$_SESSION["context"] = $_SESSION["domain_name"];
				}
				else {
					$_SESSION["context"] = 'default';
				}
			}
	}

//recordings add the domain to the path if there is more than one domains
	if (count($_SESSION["domains"]) > 1) {
		if (strlen($_SESSION['switch']['recordings']['dir']) > 0) {
			if (substr($_SESSION['switch']['recordings']['dir'], -strlen($_SESSION["domain_name"])) != $_SESSION["domain_name"]) {
				//get the default recordings directory
					$sql = "select * from v_default_settings ";
					$sql .= "where default_setting_enabled = 'true' ";
					$sql .= "and default_setting_category = 'switch' ";
					$sql .= "and default_setting_subcategory = 'recordings' ";
					$sql .= "and default_setting_name = 'dir' ";
					$prep_statement = $db->prepare($sql);
					$prep_statement->execute();
					$result_default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					foreach($result_default_settings as $row) {
						$name = $row['default_setting_name'];
						$category = $row['default_setting_category'];
						$subcategory = $row['default_setting_subcategory'];
						$switch_recordings_dir = $row['default_setting_value'];
					}
				//add the domain
					$_SESSION['switch']['recordings']['dir'] = $switch_recordings_dir.'/'.$_SESSION["domain_name"];
			}
		}
	}

//set the domain_uuid variable from the session
	if (strlen($_SESSION["domain_uuid"]) > 0) { 
		$domain_uuid = $_SESSION["domain_uuid"];
	}
	else {
		$domain_uuid = uuid();
	}

?>