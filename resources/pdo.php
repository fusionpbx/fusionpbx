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
	  Portions created by the Initial Developer are Copyright (C) 2008-2016
	  the Initial Developer. All Rights Reserved.

	  Contributor(s):
	  Mark J Crane <markjcrane@fusionpbx.com>
	  Raymond Chandler <intralanman@gmail.com>
	 */

//includes files
	require_once __DIR__ . "/require.php";
	require_once "resources/functions.php";

//set defaults
	if (isset($dbtype)) {
		$db_type = $dbtype;
	}
	if (isset($dbhost)) {
		$db_host = $dbhost;
	}
	if (isset($dbport)) {
		$db_port = $dbport;
	}
	if (isset($dbname)) {
		$db_name = $dbname;
	}
	if (isset($dbusername)) {
		$db_username = $dbusername;
	}
	if (isset($dbpassword)) {
		$db_password = $dbpassword;
	}
	if (isset($db_file_path)) {
		$db_path = $db_file_path;
	}
	if (isset($dbfilename)) {
		$db_name = $dbfilename;
	}
	if (isset($dbsecure)) {
		$db_secure = $dbsecure;
	}
	if (isset($dbcertauthority)) {
		$db_cert_authority = $dbcertauthority;
	}

	if (!function_exists('get_db_field_names')) {

		function get_db_field_names($db, $table, $db_name = 'fusionpbx') {
			$query = sprintf('SELECT * FROM %s LIMIT 1', $table);
			foreach ($db->query($query, PDO::FETCH_ASSOC) as $row) {
				return array_keys($row);
			}

			// if we're still here, we need to try something else
			$fields = array();
			$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
			if ($driver == 'sqlite') {
				$query = sprintf("Pragma table_info(%s);", $table);
				$stmt = $db->prepare($query);
				$result = $stmt->execute();
				$rows = $stmt->fetchAll(PDO::FETCH_NAMED);
				//printf('<pre>%s</pre>', print_r($rows, true));
				$row_count = count($rows);
				//printf('<pre>%s</pre>', print_r($rows, true));
				for ($i = 0; $i < $row_count; $i++) {
					array_push($fields, $rows[$i]['name']);
				}
				return $fields;
			} else {
				$query = sprintf("SELECT * FROM information_schema.columns
			WHERE table_schema='%s' AND table_name='%s';"
					, $db_name, $table
				);
				$stmt = $db->prepare($query);
				$result = $stmt->execute();
				$rows = $stmt->fetchAll(PDO::FETCH_NAMED);
				$row_count = count($rows);
				//printf('<pre>%s</pre>', print_r($rows, true));
				for ($i = 0; $i < $row_count; $i++) {
					array_push($fields, $rows[$i]['COLUMN_NAME']);
				}
				return $fields;
			}
		}

	}

// build the dsn string
//	$dsn_options = [
//		'type'     => $config->value('database.0.type', 'pgsql'),
//		'host'     => $config->value('database.0.host', ''),
//		'port'     => $config->value('database.0.port', ''),
//		'name'     => $config->value('database.0.name', 'fusionpbx'),
//		'username' => $config->value('database.0.username', 'fusionpbx'),
//		'password' => $config->value('database.0.password', ''),
//	];
	$dsn_options = $config->section('database.0.', true);

	switch ($dsn_options['type']) {
		case 'sqlite':
			update_sqlite_db_name($dsn_options);
			inject_sqlite_functions($dsn_options);
			break;
		case 'mysql':
			load_mysql_functions();
			//required for mysql_real_escape_string
			if (function_exists('mysql_connect')) {
				$mysql_connection = @mysql_connect($db_host, $db_username, $db_password);
			}
			$dsn_options['charset'] = "utf8";
			//if both host and port are empty use the unix socket
			if (empty($dsn_options['port']) && empty($dsn_options['host'])) {
				$dsn_options['unix_socket'] = "/var/run/mysqld/mysqld.sock;";
				//ensure they are removed from array
				unset($dsn_options['port'], $dsn_options['host']);
			}
			break;
		case 'pgsql':
			$db_secure = $config->value('database.0.secure');
			if (empty($dsn_options['host'])) {
				$dsn_options['host'] = 'localhost';
			}
			if (empty($dsn_options['port'])) {
				$dsn_options['port'] = '5432';
			}
			if ($db_secure === 'true') {
				$db_cert_authority = $config->value('database.0.cert_authority');
				$dsn .= " sslmode=verify-ca sslrootcert=$db_cert_authority";
			}
			break;
		case 'odbc':
	}

	function dsn_options_to_string(array $options): string {
		//set the type first and remove from the options array
		$retval = ($options['type'] ?? 'pgsql').':';
		unset ($options['type']);
		//set each key / value pair using an equals and a semi-colon at the end
		foreach($options as $key => $value) {
			//adjust to PDO key name requirements
			switch ($key) {
				case 'name':
					$key = 'dbname';
					break;
				case 'username':
					$key = 'user';
					break;
			}
			$retval .= "$key=$value;";
		}
		//return the string back
		return $retval;
	}

	function update_sqlite_db_name(array &$dsn_options) {
		$db_name = $dsn_options['name'] ?? '';
		//prepare the database connection
		if (empty($db_name)) {
			$server_name = $_SERVER["SERVER_NAME"];
			$server_name = str_replace("www.", "", $server_name);
			$db_name = $server_name . '.db';
		}

		$db_path = realpath($db_path);
		if (!is_writable($db_path . '/' . $db_name)) {
			//not writable
			echo "The database " . $db_path . "/" . $db_name . " does not exist or is not writable.";
			exit;
		}
		$dsn_options['name'] = $db_path . '/' . $db_name;
	}

	function inject_sqlite_functions(array $dsn_options) {
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
				return substr($string, (strlen($string) - $num), strlen($string));
			}

		}
		if (!function_exists('php_sqlite_data_type')) {

			function php_sqlite_data_type($string, $field) {

				//get the string between the start and end characters
				$start = '(';
				$end = ')';
				$ini = stripos($string, $start);
				if ($ini == 0)
					return "";
				$ini += strlen($start);
				$len = stripos($string, $end, $ini) - $ini;
				$string = substr($string, $ini, $len);

				$str_data_type = '';
				$string_array = explode(',', $string);
				foreach ($string_array as $lnvalue) {
					$fieldlistarray = explode(" ", $value);
					unset($fieldarray, $string, $field);
				}

				return $str_data_type;
			}

		}
		$dsn = dsn_options_to_string($dsn_options);
		//database connection
		try {
			//create the database connection object
			//test sqlite2
			//$dsn_options['type'] = 'sqlite2';
			//$dsn_options['name'] = 'example.db';
			//test sqlite3 in-memory type
			//$dsn_options['type'] = 'sqlite::memory';

			$db = new PDO($dsn);
			//enable foreign key constraints
			$db->query('PRAGMA foreign_keys = ON;');
			//add additional functions to SQLite so that they are accessible inside SQL
			//bool PDO::sqliteCreateFunction ( string function_name, callback callback [, int num_args] )
			$db->sqliteCreateFunction('md5', 'php_md5', 1);
			$db->sqliteCreateFunction('unix_timestamp', 'php_unix_timestamp', 1);
			$db->sqliteCreateFunction('now', 'php_now', 0);
			$db->sqliteCreateFunction('sqlitedatatype', 'php_sqlite_data_type', 2);
			$db->sqliteCreateFunction('strleft', 'php_left', 2);
			$db->sqliteCreateFunction('strright', 'php_right', 2);
		} catch (PDOException $error) {
			print "error: " . $error->getMessage() . "<br/>";
			die();
		}
	}

	//set the final $dsn string to a global variable
	global $dsn;

	//get the string
	$dsn = dsn_options_to_string($dsn_options);
?>
