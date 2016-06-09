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
	Copyright (C) 2010-2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Matthew Vale <github@mafoo.org>
*/
include "root.php";

//define the install class
	class install_fusionpbx {

		protected $global_settings;
		protected $config_php;
		protected $menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';
		protected $dbh;

		public $debug = false;
		public $echo_progress = false;

		public $install_language = 'en-us';
		public $admin_username;
		public $admin_password;
		public $default_country = 'US';
		public $template_name = 'enhanced';

		function __construct($global_settings) {
			if(is_null($global_settings)){
				require_once "resources/classes/global_settings.php";
				$global_settings = new global_settings();
			}elseif(!is_a($global_settings, 'global_settings')){
				throw new Exception('The parameter $global_settings must be a global_settings object (or a subclass of)');
			}
			$this->global_settings = $global_settings;
			if (is_dir("/etc/fusionpbx")){
				$this->config_php = "/etc/fusionpbx/config.php";
			} elseif (is_dir("/usr/local/etc/fusionpbx")){
				$this->config_php = "/usr/local/etc/fusionpbx/config.php";
			}
			elseif (is_dir($_SERVER["PROJECT_ROOT"]."/resources")) {
				$this->config_php = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/config.php";
			}
			else {
				$this->config_php = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/config.php";
			}
			$this->config_php = normalize_path_to_os($this->config_php);
		}

		function write_debug($message) {
			if($this->debug){
				echo "$message\n";
			}
		}

		function write_progress($message) {
			if($this->echo_progress){
				echo "$message\n";
			}
		}

		function install_phase_1() {
			ini_set('max_execution_time',3600);
			$this->write_progress("Install phase 1 started for FusionPBX");
			$this->create_config_php();
			$this->write_progress("\tExecuting config.php");
			require $this->config_php;
			global $db;
			$this->create_database();
			$db = $this->dbh;
			$this->create_domain();
			$this->create_superuser();
			$this->app_defaults();
			$this->write_progress("\tRunning requires");
			require "resources/require.php";
			$this->write_progress("Install phase 1 complete for FusionPBX");
		}

		function install_phase_2() {
			ini_set('max_execution_time',3600);
			$this->write_progress("Install phase 2 started for FusionPBX");
			//$this->app_defaults();
			$this->write_progress("Install phase 2 complete for FusionPBX");
		}

		protected function create_config_php() {
			$this->write_progress("\tCreating " . $this->config_php);
			$tmp_config = "<?php\n";
			$tmp_config .= "/* \$Id\$ */\n";
			$tmp_config .= "/*\n";
			$tmp_config .= "	config.php\n";
			$tmp_config .= "	Copyright (C) 2008, 2013 Mark J Crane\n";
			$tmp_config .= "	All rights reserved.\n";
			$tmp_config .= "\n";
			$tmp_config .= "	Redistribution and use in source and binary forms, with or without\n";
			$tmp_config .= "	modification, are permitted provided that the following conditions are met:\n";
			$tmp_config .= "\n";
			$tmp_config .= "	1. Redistributions of source code must retain the above copyright notice,\n";
			$tmp_config .= "	   this list of conditions and the following disclaimer.\n";
			$tmp_config .= "\n";
			$tmp_config .= "	2. Redistributions in binary form must reproduce the above copyright\n";
			$tmp_config .= "	   notice, this list of conditions and the following disclaimer in the\n";
			$tmp_config .= "	   documentation and/or other materials provided with the distribution.\n";
			$tmp_config .= "\n";
			$tmp_config .= "	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,\n";
			$tmp_config .= "	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY\n";
			$tmp_config .= "	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE\n";
			$tmp_config .= "	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,\n";
			$tmp_config .= "	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF\n";
			$tmp_config .= "	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS\n";
			$tmp_config .= "	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN\n";
			$tmp_config .= "	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)\n";
			$tmp_config .= "	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE\n";
			$tmp_config .= "	POSSIBILITY OF SUCH DAMAGE.\n";
			$tmp_config .= "*/\n";
			$tmp_config .= "\n";
			$tmp_config .= "//-----------------------------------------------------\n";
			$tmp_config .= "// settings:\n";
			$tmp_config .= "//-----------------------------------------------------\n";
			$tmp_config .= "\n";
			$tmp_config .= "	//set the database type\n";
			$tmp_config .= "		\$db_type = '".$this->global_settings->db_type()."'; //sqlite, mysql, pgsql, others with a manually created PDO connection\n";
			$tmp_config .= "\n";
			if ($this->global_settings->db_type() == "sqlite") {
				$tmp_config .= "	//sqlite: the db_name and db_path are automatically assigned however the values can be overidden by setting the values here.\n";
				$tmp_config .= "		\$db_name = '".$this->global_settings->db_name()."'; //host name/ip address + '.db' is the default database filename\n";
				$tmp_config .= "		\$db_path = '".$this->global_settings->db_path()."'; //the path is determined by a php variable\n";
			}
			$tmp_config .= "\n";
			$tmp_config .= "	//mysql: database connection information\n";
			if ($this->global_settings->db_type() == "mysql") {
				$db_host = $this->global_settings->db_host();
				if ( $db_host == "localhost") {
					//if localhost is used it defaults to a Unix Socket which doesn't seem to work.
					//replace localhost with 127.0.0.1 so that it will connect using TCP
					$db_host = "127.0.0.1";
				}
				$tmp_config .= "		\$db_host = '".$db_host."';\n";
				$tmp_config .= "		\$db_port = '".$this->global_settings->db_port()."';\n";
				$tmp_config .= "		\$db_name = '".$this->global_settings->db_name()."';\n";
				$tmp_config .= "		\$db_username = '".$this->global_settings->db_username()."';\n";
				$tmp_config .= "		\$db_password = '".$this->global_settings->db_password()."';\n";
			}
			else {
				$tmp_config .= "		//\$db_host = '';\n";
				$tmp_config .= "		//\$db_port = '';\n";
				$tmp_config .= "		//\$db_name = '';\n";
				$tmp_config .= "		//\$db_username = '';\n";
				$tmp_config .= "		//\$db_password = '';\n";
			}
			$tmp_config .= "\n";
			$tmp_config .= "	//pgsql: database connection information\n";
			if ($this->global_settings->db_type() == "pgsql") {
				$db_host = $this->global_settings->db_host();
				//Unix Socket - if localhost or 127.0.0.1 we want it to default to a Unix Socket.
				//$comment_out = '';
				//if ( $db_host == "localhost" or $db_host == "127.0.0.1") {
				//	$comment_out = "//";
				//}
				//$tmp_config .= "		$comment_out\$db_host = '".$this->global_settings->db_host()."'; //set the host only if the database is not local\n";
				$tmp_config .= "		\$db_host = '".$this->global_settings->db_host()."'; //set the host only if the database is not local\n";
				$tmp_config .= "		\$db_port = '".$this->global_settings->db_port()."';\n";
				$tmp_config .= "		\$db_name = '".$this->global_settings->db_name()."';\n";
				$tmp_config .= "		\$db_username = '".$this->global_settings->db_username()."';\n";
				$tmp_config .= "		\$db_password = '".$this->global_settings->db_password()."';\n";
			}
			else {
				$tmp_config .= "		//\$db_host = '".$this->global_settings->db_host()."'; //set the host only if the database is not local\n";
				$tmp_config .= "		//\$db_port = '".$this->global_settings->db_port()."';\n";
				$tmp_config .= "		//\$db_name = '".$this->global_settings->db_name()."';\n";
				$tmp_config .= "		//\$db_username = '".$this->global_settings->db_username()."';\n";
				$tmp_config .= "		//\$db_password = '".$this->global_settings->db_password()."';\n";
			}
			$tmp_config .= "\n";
			$tmp_config .= "	//show errors\n";
			$tmp_config .= "		ini_set('display_errors', '1');\n";
			$tmp_config .= "		//error_reporting (E_ALL); // Report everything\n";
			$tmp_config .= "		//error_reporting (E_ALL ^ E_NOTICE); // Report everything\n";
			$tmp_config .= "		error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ); //hide notices and warnings";
			$tmp_config .= "\n";
			$tmp_config .= "?>";

			if((file_exists($this->config_php)
				and !is_writable($this->config_php))
				or !is_writable(dirname($this->config_php))
				) {
				throw new Exception("cannot write to '" . $this->config_php . "'" );
			}
			$fout = fopen($this->config_php,"w");
			fwrite($fout, $tmp_config);
			unset($tmp_config);
			fclose($fout);
		}

		protected function create_database() {
			$this->write_progress("\tUsing database as type " . $this->global_settings->db_type());
			$function = "create_database_" . $this->global_settings->db_type();
			$this->$function();

			//sqlite is natively supported under all known OS'es
			if($this->global_settings->db_type() != 'sqlite'){
				if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
				//non sqlite database support only uses ODBC under windows
					$this->create_odbc_database_connection();
				}elseif($this->global_settings->db_type() != 'pgsql'){
				//switch supports postgresql natively
					$this->create_odbc_database_connection();
				}
			}
		}

		protected function create_odbc_database_connection() {
			//needed for non native database support
				$database_uuid = uuid();
				$sql = "insert into v_databases ";
				$sql .= "(";
				$sql .= "database_uuid, ";
				$sql .= "database_driver, ";
				$sql .= "database_type, ";
				$sql .= "database_host, ";
				$sql .= "database_port, ";
				$sql .= "database_name, ";
				$sql .= "database_username, ";
				$sql .= "database_password, ";
				$sql .= "database_path, ";
				$sql .= "database_description ";
				$sql .= ")";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'$database_uuid', ";
				$sql .= "'odbc', ";
				$sql .= "'".$this->global_settings->db_type()."', ";
				$sql .= "'".$this->global_settings->db_host()."', ";
				$sql .= "'".$this->global_settings->db_port()."', ";
				$sql .= "'".$this->global_settings->db_name()."', ";
				$sql .= "'".$this->global_settings->db_username()."', ";
				$sql .= "'".$this->global_settings->db_password()."', ";
				$sql .= "'".$this->global_settings->db_path()."', ";
				$sql .= "'Created by installer' ";
				$sql .= ")";
				if($this->dbh->exec(check_sql($sql)) === false){
					throw new Exception("Failed to create odbc_database entery: " . join(":", $this->dbh->errorInfo()));
				}
				unset($sql);
		}

		protected function create_database_sqlite() {
			//sqlite database will be created when the config.php is loaded and only if the database file does not exist
				try {
					$this->dbh = new PDO('sqlite:'.$this->global_settings->db_path().'/'.$this->global_settings->db_name()); //sqlite 3
					//$this->dbh = new PDO('sqlite::memory:'); //sqlite 3
				}
				catch (PDOException $error) {
					throw new Exception("Failed to create database: " . $error->getMessage());
				}

			//add additional functions to SQLite - bool PDO::sqliteCreateFunction ( string function_name, callback callback [, int num_args] )
				if (!function_exists('php_now')) {
					function php_now() {
						if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get")) {
							@date_default_timezone_set(@date_default_timezone_get());
						}
						return date("Y-m-d H:i:s");
					}
				}
				$this->dbh->sqliteCreateFunction('now', 'php_now', 0);

			//add the database structure
				require_once "resources/classes/schema.php";
				$schema = new schema;
				$schema->db = $this->dbh;
				$schema->db_type = $this->global_settings->db_type();
				$schema->sql();
				$schema->exec();

			//get the contents of the sql file
				if (file_exists('/usr/share/examples/fusionpbx/resources/install/sql/sqlite.sql')){
					$filename = "/usr/share/examples/fusionpbx/resources/install/sql/sqlite.sql";
				}
				else {
					$filename = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/sql/sqlite.sql';
				}
				$file_contents = file_get_contents($filename);
				unset($filename);

			//replace \r\n with \n then explode on \n
				$file_contents = str_replace("\r\n", "\n", $file_contents);

			//loop line by line through all the lines of sql code
				$this->dbh->beginTransaction();
				$string_array = explode("\n", $file_contents);
				$x = 0;
				foreach($string_array as $sql) {
					try {
						$this->dbh->query($sql);
					}
					catch (PDOException $error) {
						throw new Exception("error creating database: " . $error->getMessage() . "\n" . $sql );
					}
					$x++;
				}
				unset ($file_contents, $sql);
				$this->dbh->commit();

			//set the file permissions
				chmod($this->global_settings->db_path().'/'.$this->global_settings->db_name(), 0777);
		}

		protected function create_database_pgsql() {
			//create the database
				if ($this->global_settings->db_create()) {
					//attempt to create new Postgres role and database
						$this->write_progress("\tCreating database");
						$db_create_username = $this->global_settings->db_create_username();
						$db_create_password = $this->global_settings->db_create_password();
						$db_host            = $this->global_settings->db_host();
						$db_port            = $this->global_settings->db_port();
						if(strlen($db_create_username) == 0){
							$db_create_username = $this->global_settings->db_username();
							$db_create_password = $this->global_settings->db_password();
						}
						if (strlen($db_host) == 0) {
							$db_host = 'localhost';
						}

						try {
							$this->dbh = new PDO("pgsql:host=$db_host port=$db_port user=$db_create_username password=$db_create_password dbname=template1");
						} catch (PDOException $error) {
							throw new Exception("error connecting to database in order to create: " . $error->getMessage());
						}

					//create the database, user, grant perms
						if($this->dbh->exec("CREATE DATABASE {$this->global_settings->db_name()}") === false) {
							throw new Exception("Failed to create database {$this->global_settings->db_name()}: " . join(":", $this->dbh->errorInfo()));
						}
						if($this->global_settings->db_username() != $db_create_username){
							if($this->dbh->exec("CREATE USER {$this->global_settings->db_username()} WITH PASSWORD '{$this->global_settings->db_password()}'") === false){
								// user may be already exists
								// throw new Exception("Failed to create user {$this->global_settings->db_name()}: " . join(":", $this->dbh->errorInfo()));
							}
							if($this->dbh->exec("GRANT ALL ON DATABASE {$this->global_settings->db_name()} TO {$this->global_settings->db_username()}") === false){
								throw new Exception("Failed to create user {$this->global_settings->db_name()}: " . join(":", $this->dbh->errorInfo()));
							}
						}

					//close database connection_aborted
						$this->dbh = null;
				}
				$this->write_progress("\tInstalling data to database");

			//open database connection with $this->global_settings->db_name()
				try {
					if (strlen($this->global_settings->db_host()) > 0) {
						$this->dbh = new PDO("pgsql:host={$this->global_settings->db_host()} port={$this->global_settings->db_port()} dbname={$this->global_settings->db_name()} user={$this->global_settings->db_username()} password={$this->global_settings->db_password()}");
					} else {
						$this->dbh = new PDO("pgsql:host=localhost port={$this->global_settings->db_port()} user={$this->global_settings->db_username()} password={$this->global_settings->db_password()} dbname={$this->global_settings->db_name()}");
					}
				}
				catch (PDOException $error) {
					throw new Exception("error connecting to database: " . $error->getMessage());
				}

			//add the database structure
				require_once "resources/classes/schema.php";
				$schema = new schema;
				$schema->db = $this->dbh;
				$schema->db_type = $this->global_settings->db_type();
				$schema->sql();
				$schema->exec();

			//get the contents of the sql file
				if (file_exists('/usr/share/examples/fusionpbx/resources/install/sql/pgsql.sql')){
					$filename = "/usr/share/examples/fusionpbx/resources/install/sql/pgsql.sql";
				}
				else {
				$filename = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/sql/pgsql.sql';
				}
				$file_contents = file_get_contents($filename);

			//replace \r\n with \n then explode on \n
				$file_contents = str_replace("\r\n", "\n", $file_contents);

			//loop line by line through all the lines of sql code
				$string_array = explode("\n", $file_contents);
				$x = 0;
				foreach($string_array as $sql) {
					if (strlen($sql) > 3) {
						try {
							$this->dbh->query($sql);
						}
						catch (PDOException $error) {
							throw new Exception("error creating database: " . $error->getMessage() . "\n" . $sql );
						}
					}
					$x++;
				}
				unset ($file_contents, $sql);
		}

		protected function create_database_mysql() {
			//database connection
				$connect_string;
				if (strlen($this->global_settings->db_host()) == 0 && strlen($this->global_settings->db_port()) == 0) {
					//if both host and port are empty use the unix socket
					$connect_string = "mysql:host={$this->global_settings->db_host()};unix_socket=/var/run/mysqld/mysqld.sock;";
				}
				elseif (strlen($this->global_settings->db_port()) == 0) {
					//leave out port if it is empty
					$connect_string = "mysql:host={$this->global_settings->db_host()};";
				}
				else {
					$connect_string = "mysql:host={$this->global_settings->db_host()};port={$this->global_settings->db_port()};";
				}

			//if we need create new database
				if ($this->global_settings->db_create()) {
					//attempt to create new user and database
						$this->write_progress("\tCreating database");
						$db_create_username = $this->global_settings->db_create_username();
						$db_create_password = $this->global_settings->db_create_password();

						if(strlen($db_create_username) == 0){
							$db_create_username = $this->global_settings->db_username();
							$db_create_password = $this->global_settings->db_password();
						}

					//connect to MySQL
						try {
							$this->dbh = new PDO($connect_string, $db_create_username, $db_create_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
							$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
							$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
						}
						catch (PDOException $error) {
							throw new Exception("error connecting to database for create: " . $error->getMessage() . "\n" . $sql );
						}

					//select the mysql database
						try {
							$this->dbh->query("USE mysql;");
						}
						catch (PDOException $error) {
							throw new Exception("error in database: " . $error->getMessage() . "\n" . $sql );
						}

					//create user if we use separeate user to access and create
						if($this->global_settings->db_username() != $db_create_username) {
							//create user and set the permissions
								try {
									$tmp_sql = "CREATE USER '".$this->global_settings->db_username()."'@'%' IDENTIFIED BY '".$this->global_settings->db_password()."'; ";
									$this->dbh->query($tmp_sql);
								}
								catch (PDOException $error) {
									// ignore error here because user may already exists
									// (e.g. reinstall can be done via remove db)
									// throw new Exception("error in database: " . $error->getMessage() . "\n" . $sql );
								}

							//set account to unlimited use
								try {
									if ($this->global_settings->db_host() == "localhost" || $this->global_settings->db_host() == "127.0.0.1") {
										$tmp_sql = "GRANT USAGE ON * . * TO '".$this->global_settings->db_username()."'@'localhost' ";
										$tmp_sql .= "IDENTIFIED BY '".$this->global_settings->db_password()."' ";
										$tmp_sql .= "WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0; ";
										$this->dbh->query($tmp_sql);

										$tmp_sql = "GRANT USAGE ON * . * TO '".$this->global_settings->db_username()."'@'127.0.0.1' ";
										$tmp_sql .= "IDENTIFIED BY '".$this->global_settings->db_password()."' ";
										$tmp_sql .= "WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0; ";
										$this->dbh->query($tmp_sql);
									}
									else {
										$tmp_sql = "GRANT USAGE ON * . * TO '".$this->global_settings->db_username()."'@'".$this->global_settings->db_host()."' ";
										$tmp_sql .= "IDENTIFIED BY '".$this->global_settings->db_password()."' ";
										$tmp_sql .= "WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0; ";
										$this->dbh->query($tmp_sql);
									}
								}
								catch (PDOException $error) {
									throw new Exception("error in database: " . $error->getMessage() . "\n" . $sql );
								}
						}

					//create the database and set the create user with permissions
						try {
							$tmp_sql = "CREATE DATABASE IF NOT EXISTS ".$this->global_settings->db_name()."; ";
							$this->dbh->query($tmp_sql);
						}
						catch (PDOException $error) {
							throw new Exception("error in database: " . $error->getMessage() . "\n" . $sql );
						}

					//set user permissions
						if($this->global_settings->db_username() != $db_create_username) {
							try {
								$this->dbh->query("GRANT ALL PRIVILEGES ON ".$this->global_settings->db_name().".* TO '".$this->global_settings->db_username()."'@'%'; ");
							}
							catch (PDOException $error) {
								throw new Exception("error in database: " . $error->getMessage() . "\n" . $sql );
							}
						}

					//make the changes active
						try {
							$tmp_sql = "FLUSH PRIVILEGES; ";
							$this->dbh->query($tmp_sql);
						}
						catch (PDOException $error) {
							throw new Exception("error in database: " . $error->getMessage() . "\n" . $sql );
						}
						$this->dbh = null;
				}

				$this->write_progress("\tInstalling data to database");

			//connect to the database
				try {
					$this->dbh = new PDO($connect_string, $this->global_settings->db_username(), $this->global_settings->db_password(), array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
					$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
				}
				catch (PDOException $error) {
					throw new Exception("error connecting to database: " . $error->getMessage() . "\n" . $sql );
				}

			//select the database
				try {
					$this->dbh->query("USE ".$this->global_settings->db_name().";");
				}
				catch (PDOException $error) {
					throw new Exception("error in database: " . $error->getMessage() . "\n" . $sql );
				}

			//add the database structure
				require_once "resources/classes/schema.php";
				$schema = new schema;
				$schema->db = $this->dbh;
				$schema->db_type = $this->global_settings->db_type();
				$schema->sql();
				$schema->exec();

			//add the defaults data into the database
				//get the contents of the sql file
					if (file_exists('/usr/share/examples/fusionpbx/resources/install/sql/mysql.sql')){
						$filename = "/usr/share/examples/fusionpbx/resources/install/sql/mysql.sql";
					}
					else {
						$filename = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/install/sql/mysql.sql';
					}
					$file_contents = file_get_contents($filename);

				//replace \r\n with \n then explode on \n
					$file_contents = str_replace("\r\n", "\n", $file_contents);

				//loop line by line through all the lines of sql code
					$string_array = explode("\n", $file_contents);
					$x = 0;
					foreach($string_array as $sql) {
						if (strlen($sql) > 3) {
							try {
								if ($this->debug) {
									$this->write_debug( $sql."\n");
								}
								$this->dbh->query($sql);
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

		protected function create_domain() {
			$this->write_progress("\tChecking if domain exists '" . $this->global_settings->domain_name() . "'");
			$sql = "select * from v_domains ";
			$sql .= "where domain_name = '".$this->global_settings->domain_name()."' ";
			$sql .= "limit 1";
			$this->write_debug($sql);
			$prep_statement = $this->dbh->prepare(check_sql($sql));
			if($prep_statement->execute() === false){
				throw new Exception("Failed to search for domain: " . join(":", $this->dbh->errorInfo()));
			}
			$result = $prep_statement->fetch(PDO::FETCH_NAMED);
			unset($sql, $prep_statement);
			if ($result) {
				$this->global_settings->set_domain_uuid($result['domain_uuid']);
				$this->write_progress("... domain exists as '" . $this->global_settings->domain_uuid() . "'");
				if($result['domain_enabled'] != 'true'){
					throw new Exception("Domain already exists but is disabled, this is unexpected");
				}
			} else {
				$this->write_progress("\t... creating domain");
				$sql = "insert into v_domains ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "domain_name, ";
				$sql .= "domain_description ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$this->global_settings->domain_uuid()."', ";
				$sql .= "'".$this->global_settings->domain_name()."', ";
				$sql .= "'Default Domain' ";
				$sql .= ");";

				$this->write_debug($sql);
				if($this->dbh->exec(check_sql($sql)) === false){
					throw new Exception("Failed to execute sql statement: " . join(":", $this->dbh->errorInfo()));
				}
				unset($sql);

				//domain settings
				$x = 0;
				$tmp[$x]['name'] = 'uuid';
				$tmp[$x]['value'] = $this->menu_uuid;
				$tmp[$x]['category'] = 'domain';
				$tmp[$x]['subcategory'] = 'menu';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'name';
				$tmp[$x]['category'] = 'domain';
				$tmp[$x]['subcategory'] = 'time_zone';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'code';
				$tmp[$x]['value'] = 'en-us';
				$tmp[$x]['category'] = 'domain';
				$tmp[$x]['subcategory'] = 'language';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'iso_code';
				$tmp[$x]['value'] = $this->default_country;
				$tmp[$x]['category'] = 'domain';
				$tmp[$x]['subcategory'] = 'country';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'name';
				$tmp[$x]['value'] = $this->template_name;
				$tmp[$x]['category'] = 'domain';
				$tmp[$x]['subcategory'] = 'template';
				$tmp[$x]['enabled'] = 'true';
				$x++;

				//server settings
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->global_settings->switch_temp_dir();
				$tmp[$x]['category'] = 'server';
				$tmp[$x]['subcategory'] = 'temp';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->global_settings->switch_backup_vdir();
				$tmp[$x]['category'] = 'server';
				$tmp[$x]['subcategory'] = 'backup';
				$tmp[$x]['enabled'] = 'true';
				$x++;

				$this->dbh->beginTransaction();
				foreach($tmp as $row) {
					$sql = "insert into v_default_settings ";
					$sql .= "(";
					$sql .= "default_setting_uuid, ";
					$sql .= "default_setting_name, ";
					$sql .= "default_setting_value, ";
					$sql .= "default_setting_category, ";
					$sql .= "default_setting_subcategory, ";
					$sql .= "default_setting_enabled ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'".$row['name']."', ";
					$sql .= "'".$row['value']."', ";
					$sql .= "'".$row['category']."', ";
					$sql .= "'".$row['subcategory']."', ";
					$sql .= "'".$row['enabled']."' ";
					$sql .= ");";
					$this->write_debug($sql);
					$this->dbh->exec(check_sql($sql));
					unset($sql);
				}
				$this->dbh->commit();
				unset($tmp);

			//get the list of installed apps from the core and mod directories
				$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
				$x=0;
				foreach ($config_list as $config_path) {
					include($config_path);
					$x++;
				}
			}
		}

		protected function create_superuser() {
			$this->write_progress("\tChecking if superuser exists '" . $this->admin_username . "'");
			$sql = "select * from v_users ";
			$sql .= "where domain_uuid = '".$this->global_settings->domain_uuid()."' ";
			$sql .= "and username = '".$this->admin_username."' ";
			$sql .= "limit 1 ";
			$this->write_debug($sql);
			$prep_statement = $this->dbh->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetch(PDO::FETCH_NAMED);
			unset($sql, $prep_statement);
			$salt = generate_password('20', '4');
			if ($result) {
				$this->admin_uuid = $result['user_uuid'];
                                $user_uuid = $result['user_uuid'];
                                $_SESSION["user_uuid"] = $this->admin_uuid;
				$this->write_progress("... superuser exists as '" . $this->admin_uuid . "', updating password");
				$sql = "update v_users ";
				$sql .= "set password = '".md5($salt.$this->admin_password)."' ";
				$sql .= "set salt = '$salt' ";
				$sql .= "where USER_uuid = '".$this->admin_uuid."' ";
				$this->write_debug($sql);
				$this->dbh->exec(check_sql($sql));
			} else {
				//message
					$this->write_progress("\t... creating super user");
				//add a user and then add the user to the superadmin group
				//prepare the values
					$user_uuid = $this->admin_uuid = uuid();
					$contact_uuid = uuid();
				//set a sessiong variable
					$_SESSION["user_uuid"] = $user_uuid;
				//salt used with the password to create a one way hash
				//add the user account
					$sql = "insert into v_users ";
					$sql .= "(";
					$sql .= "domain_uuid, ";
					$sql .= "user_uuid, ";
					$sql .= "contact_uuid, ";
					$sql .= "username, ";
					$sql .= "password, ";
					$sql .= "salt, ";
					$sql .= "add_date, ";
					$sql .= "add_user, ";
					$sql .= "user_enabled ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".$this->global_settings->domain_uuid()."', ";
					$sql .= "'".$this->admin_uuid."', ";
					$sql .= "'$contact_uuid', ";
					$sql .= "'".$this->admin_username."', ";
					$sql .= "'".md5($salt.$this->admin_password)."', ";
					$sql .= "'$salt', ";
					$sql .= "now(), ";
					$sql .= "'".$this->admin_username."', ";
					$sql .= "'true' ";
					$sql .= ");";
					$this->write_debug( $sql."\n");
					$this->dbh->exec(check_sql($sql));
					unset($sql);
			}
			$this->write_progress("\tChecking if superuser contact exists");
			$sql = "select count(*) from v_contacts ";
			$sql .= "where domain_uuid = '".$this->global_settings->domain_uuid()."' ";
			$sql .= "and contact_name_given = '".$this->admin_username."' ";
			$sql .= "and contact_nickname = '".$this->admin_username."' ";
			$sql .= "limit 1 ";
			$this->write_debug($sql);
			$prep_statement = $this->dbh->prepare(check_sql($sql));
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['count'] == 0) {
				$sql = "insert into v_contacts ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "contact_uuid, ";
				$sql .= "contact_type, ";
				$sql .= "contact_name_given, ";
				$sql .= "contact_nickname ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$this->global_settings->domain_uuid()."', ";
				$sql .= "'$contact_uuid', ";
				$sql .= "'user', ";
				$sql .= "'".$this->admin_username."', ";
				$sql .= "'".$this->admin_username."' ";
				$sql .= ")";
				$this->dbh->exec(check_sql($sql));
				unset($sql);
			}
			$this->write_progress("\tChecking if superuser is in the correct group");
			$sql = "select count(*) from v_group_users ";
			$sql .= "where domain_uuid = '".$this->global_settings->domain_uuid()."' ";
			$sql .= "and user_uuid = '".$this->admin_uuid."' ";
			$sql .= "and group_name = 'superadmin' ";
			$sql .= "limit 1 ";
			$this->write_debug($sql);
			$prep_statement = $this->dbh->prepare(check_sql($sql));
			$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['count'] == 0) {
				//add the user to the superadmin group
				$sql = "insert into v_group_users ";
				$sql .= "(";
				$sql .= "group_user_uuid, ";
				$sql .= "domain_uuid, ";
				$sql .= "user_uuid, ";
				$sql .= "group_name ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".uuid()."', ";
				$sql .= "'".$this->global_settings->domain_uuid()."', ";
				$sql .= "'".$this->admin_uuid."', ";
				$sql .= "'superadmin' ";
				$sql .= ");";
				$this->write_debug( $sql."\n");
				$this->dbh->exec(check_sql($sql));
				unset($sql);
			}
		}

		protected function app_defaults() {

			//write a progress message
				$this->write_progress("\tRunning app_defaults");

			//set needed session settings
				$_SESSION["username"] = $this->admin_username;
				$_SESSION["domain_uuid"] = $this->global_settings->domain_uuid();
				require $this->config_php;
				require "resources/require.php";
				$_SESSION['event_socket_ip_address'] = $this->global_settings->event_host;
				$_SESSION['event_socket_port'] = $this->global_settings->event_port;
				$_SESSION['event_socket_password'] = $this->global_settings->event_password;

			//get the groups assigned to the user and then set the groups in $_SESSION["groups"]
				$sql = "SELECT * FROM v_group_users ";
				$sql .= "where domain_uuid=:domain_uuid ";
				$sql .= "and user_uuid=:user_uuid ";
				$prep_statement = $this->dbh->prepare(check_sql($sql));
				$prep_statement->bindParam(':domain_uuid', $this->global_settings->domain_uuid);
				$prep_statement->bindParam(':user_uuid', $this->admin_uuid);
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$_SESSION["groups"] = $result;
				unset($sql, $row_count, $prep_statement);

			//get the permissions assigned to the groups that the user is a member of set the permissions in $_SESSION['permissions']
				$x = 0;
				$sql = "select distinct(permission_name) from v_group_permissions ";
				foreach($_SESSION["groups"] as $field) {
					if (strlen($field['group_name']) > 0) {
						if ($x == 0) {
							$sql .= "where (domain_uuid = '".$this->global_settings->domain_uuid."' and group_name = '".$field['group_name']."') ";
						}
						else {
							$sql .= "or (domain_uuid = '".$this->global_settings->domain_uuid."' and group_name = '".$field['group_name']."') ";
						}
						$x++;
					}
				}
				$prep_statement_sub = $this->dbh->prepare($sql);
				$prep_statement_sub->execute();
				$_SESSION['permissions'] = $prep_statement_sub->fetchAll(PDO::FETCH_NAMED);
				unset($sql, $prep_statement_sub);

			//include the config.php
				$db_type = $this->global_settings->db_type();
				$db_path = $this->global_settings->db_path();
				$db_host = $this->global_settings->db_host();
				$db_port = $this->global_settings->db_port();
				$db_name = $this->global_settings->db_name();
				$db_username = $this->global_settings->db_username();
				$db_password = $this->global_settings->db_password();

			//add the database structure
				require_once "resources/classes/schema.php";
				$schema = new schema;
				echo $schema->schema();

			//run all app_defaults.php files
				$default_language = $this->install_language;
				$domain = new domains;
				$domain->upgrade();

			//get the switch default settings
				$sql = "select * from v_default_settings ";
				$sql .= "where default_setting_category = 'switch' ";
				$sql .= "and default_setting_enabled = 'true' ";
				$prep_statement = $this->dbh->prepare($sql);
				$prep_statement->execute();
				$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach($default_settings as $row) {
					$name = $row['default_setting_name'];
					$category = $row['default_setting_category'];
					$subcategory = $row['default_setting_subcategory'];
					if ($category == "switch") {
						$_SESSION[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
						$_SESSION[$category][$subcategory][$name] = $row['default_setting_value'];
					}
				}
				unset ($prep_statement, $sql);

			//update config.lua
				$obj = new scripts;
				$obj->copy_files();
				$obj->write_config();

			//synchronize the config with the saved settings
				save_switch_xml();

			//do not show the apply settings reminder on the login page
				$_SESSION["reload_xml"] = false;

			//clear the menu
				$_SESSION["menu"] = "";

		}

		public function remove_config() {
			if (file_exists('/bin/rm')) {
				$this->write_debug('rm -f ' . $this->config_php);
				exec ('rm -f ' . $this->config_php);
			}
			elseif(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
				$this->write_debug("del /S /F /Q '$dir'");
				exec("del /F /Q '" . $this->config_php . "'");
			}
			else {
				$this->write_debug("delete file: ".$file);
				unlink($this->config_php);
			}
			clearstatcache();
		}
	}

?>