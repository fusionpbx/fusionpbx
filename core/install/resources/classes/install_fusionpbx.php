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
	Copyright (C) 2010-2015
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Matthew Vale <github@mafoo.org>
*/
include "root.php";

//define the install class
	class install_fusionpbx {

		protected $_domain_uuid;
		protected $domain_name;
		protected $detect_switch;
		protected $config_php;
		protected $menu_uuid = 'b4750c3f-2a86-b00d-b7d0-345c14eca286';
		protected $dbh;
		
		public function domain_uuid() { return $this->_domain_uuid; }

		public $debug = false;
		
	 	public $install_msg;
	 	public $install_language = 'en-us';
	 	public $admin_username;
	 	public $admin_password;
	 	public $default_country = 'US';
		public $template_name = 'enhanced';

	 	public $db_type;
		public $db_path;
		public $db_host;
		public $db_port;
		public $db_name;
		public $db_username;
		public $db_password;
		
	 	function __construct($domain_name, $domain_uuid, $detect_switch) {
			if(!is_a($detect_switch, 'detect_switch')){
				throw new Exception('The parameter $detect_switch must be a detect_switch object (or a subclass of)');
			}
			if($domain_uuid == null){ $domain_uuid = uuid(); }
			$this->_domain_uuid = $domain_uuid;
			$this->domain_name = $domain_name;
			$this->detect_switch = $detect_switch;
			if (is_dir("/etc/fusionpbx")){
				$this->config_php = "/etc/fusionpbx/config.php";
			} elseif (is_dir("/usr/local/etc/fusionpbx")){
				$this->config_php = "/usr/local/etc/fusionpbx/config.php";
			}
			elseif (is_dir($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/resources")) {
				$this->config_php = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/config.php";
			}
			else {
				$this->config_php = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/config.php";
			}
		}

		function write_debug($message) {
			if($this->debug){
				echo "$message\n";
			}
		}
		
		function write_progress($message) {
			echo "$message\n";
		}

		function install() {
			ini_set('max_execution_time',3600);
			$this->create_config_php();
			$this->create_database();
			$this->create_domain();
			$this->create_superuser();
			require "resources/require.php";
			$this->create_menus();
		}
		
		protected function create_config_php() {
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
			$tmp_config .= "		\$db_type = '".$this->db_type."'; //sqlite, mysql, pgsql, others with a manually created PDO connection\n";
			$tmp_config .= "\n";
			if ($this->db_type == "sqlite") {
				$tmp_config .= "	//sqlite: the db_name and db_path are automatically assigned however the values can be overidden by setting the values here.\n";
				$tmp_config .= "		\$db_name = '".$this->db_name."'; //host name/ip address + '.db' is the default database filename\n";
				$tmp_config .= "		\$db_path = '".$this->db_path."'; //the path is determined by a php variable\n";
			}
			$tmp_config .= "\n";
			$tmp_config .= "	//mysql: database connection information\n";
			if ($this->db_type == "mysql") {
				if ($this->db_host == "localhost") {
					//if localhost is used it defaults to a Unix Socket which doesn't seem to work.
					//replace localhost with 127.0.0.1 so that it will connect using TCP
					$this->db_host = "127.0.0.1";
				}
				$tmp_config .= "		\$db_host = '".$this->db_host."';\n";
				$tmp_config .= "		\$db_port = '".$this->db_port."';\n";
				$tmp_config .= "		\$db_name = '".$this->db_name."';\n";
				$tmp_config .= "		\$db_username = '".$this->db_username."';\n";
				$tmp_config .= "		\$db_password = '".$this->db_password."';\n";
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
			if ($this->db_type == "pgsql") {
				$tmp_config .= "		\$db_host = '".$this->db_host."'; //set the host only if the database is not local\n";
				$tmp_config .= "		\$db_port = '".$this->db_port."';\n";
				$tmp_config .= "		\$db_name = '".$this->db_name."';\n";
				$tmp_config .= "		\$db_username = '".$this->db_username."';\n";
				$tmp_config .= "		\$db_password = '".$this->db_password."';\n";
			}
			else {
				$tmp_config .= "		//\$db_host = '".$this->db_host."'; //set the host only if the database is not local\n";
				$tmp_config .= "		//\$db_port = '".$this->db_port."';\n";
				$tmp_config .= "		//\$db_name = '".$this->db_name."';\n";
				$tmp_config .= "		//\$db_username = '".$this->db_username."';\n";
				$tmp_config .= "		//\$db_password = '".$this->db_password."';\n";
			}
			$tmp_config .= "\n";
			$tmp_config .= "	//show errors\n";
			$tmp_config .= "		ini_set('display_errors', '1');\n";
			$tmp_config .= "		//error_reporting (E_ALL); // Report everything\n";
			$tmp_config .= "		//error_reporting (E_ALL ^ E_NOTICE); // Report everything\n";
			$tmp_config .= "		error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ); //hide notices and warnings";
			$tmp_config .= "\n";
			$tmp_config .= "?>";
	
			if((file_exists($this->config_php) and !is_writable($this->config_php))
			   or !is_writable(dirname($this->config_php))
			   ){
				throw new Exception("cannot write to '" . $this->config_php . "'" );
			}
			$this->write_progress("Creating " . $this->config_php);
			$fout = fopen($this->config_php,"w");
			fwrite($fout, $tmp_config);
			unset($tmp_config);
			fclose($fout);
		}
		
		protected function create_database() {
			require $this->config_php;
			$this->write_progress("Creating database as " . $this->db_type);
			$function = "create_database_" . $this->db_type;
			$this->$function();
			global $db;
			$db = $this->dbh;
		}
		protected function create_database_sqlite() {
			//sqlite database will be created when the config.php is loaded and only if the database file does not exist
				try {
					$this->dbh = new PDO('sqlite:'.$this->db_path.'/'.$this->db_name); //sqlite 3
					//$this->dbh = new PDO('sqlite::memory:'); //sqlite 3
				}
				catch (PDOException $error) {
					throw Exception("Failed to create database: " . $error->getMessage());
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
				$schema->db_type = $this->db_type;
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
				chmod($this->db_path.'/'.$this->db_name, 0777);
		}

		protected function create_database_pgsql() {
	
				//if $this->db_create_username provided, attempt to create new PG role and database
					if (strlen($this->db_create_username) > 0) {
						try {
							if (strlen($this->db_port) == 0) { $this->db_port = "5432"; }
							if (strlen($this->db_host) > 0) {
								$this->dbh = new PDO("pgsql:host={$this->db_host} port={$this->db_port} user={".$this->db_create_username."} password={".$this->db_create_password."} dbname=template1");
							} else {
								$this->dbh = new PDO("pgsql:host=localhost port={$this->db_port} user={".$this->db_create_username."} password={".$this->db_create_password."} dbname=template1");
							}
						} catch (PDOException $error) {
							throw new Exception("error connecting to database: " . $error->getMessage());
						}
	
						//create the database, user, grant perms
						$this->dbh->exec("CREATE DATABASE {$this->db_name}");
						$this->dbh->exec("CREATE USER {$this->db_username} WITH PASSWORD '{$this->db_password}'");
						$this->dbh->exec("GRANT ALL ON {$this->db_name} TO {$this->db_username}");
	
						//close database connection_aborted
						$this->dbh = null;
					}
	
				//open database connection with $this->db_name
					try {
						if (strlen($this->db_port) == 0) { $this->db_port = "5432"; }
						if (strlen($this->db_host) > 0) {
							$this->dbh = new PDO("pgsql:host={$this->db_host} port={$this->db_port} dbname={$this->db_name} user={$this->db_username} password={$this->db_password}");
						} else {
							$this->dbh = new PDO("pgsql:host=localhost port={$this->db_port} user={$this->db_username} password={$this->db_password} dbname={$this->db_name}");
						}
					}
					catch (PDOException $error) {
						throw new Exception("error connecting to database: " . $error->getMessage());
					}
	
				//add the database structure
					require_once "resources/classes/schema.php";
					$schema = new schema;
					$schema->db = $this->dbh;
					$schema->db_type = $this->db_type;
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
					try {
						if (strlen($this->db_host) == 0 && strlen($this->db_port) == 0) {
							//if both host and port are empty use the unix socket
							if (strlen($this->db_create_username) == 0) {
								$this->dbh = new PDO("mysql:host=$this->db_host;unix_socket=/var/run/mysqld/mysqld.sock;", $this->db_username, $this->db_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
							}
							else {
								$this->dbh = new PDO("mysql:host=$this->db_host;unix_socket=/var/run/mysqld/mysqld.sock;", $this->db_create_username, $this->db_create_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
							}
						}
						else {
							if (strlen($this->db_port) == 0) {
								//leave out port if it is empty
								if (strlen($this->db_create_username) == 0) {
									$this->dbh = new PDO("mysql:host=$this->db_host;", $this->db_username, $this->db_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
								}
								else {
									$this->dbh = new PDO("mysql:host=$this->db_host;", $this->db_create_username, $this->db_create_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));							}
							}
							else {
								if (strlen($this->db_create_username) == 0) {
									$this->dbh = new PDO("mysql:host=$this->db_host;port=$this->db_port;", $this->db_username, $this->db_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
								}
								else {
									$this->dbh = new PDO("mysql:host=$this->db_host;port=$this->db_port;", $this->db_create_username, $this->db_create_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
								}
							}
						}
						$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
						$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
					}
					catch (PDOException $error) {
								throw new Exception("error creating database: " . $error->getMessage() . "\n" . $sql );
					}
	
				//create the table, user and set the permissions only if the db_create_username was provided
					if (strlen($this->db_create_username) > 0) {
						//select the mysql database
							try {
								$this->dbh->query("USE mysql;");
							}
							catch (PDOException $error) {
								if ($this->debug) {
									throw new Exception("error conencting to database: " . $error->getMessage());
								}
							}
	
						//create user and set the permissions
							try {
								$tmp_sql = "CREATE USER '".$this->db_username."'@'%' IDENTIFIED BY '".$this->db_password."'; ";
								$this->dbh->query($tmp_sql);
							}
							catch (PDOException $error) {
								if ($this->debug) {
									print "error: " . $error->getMessage() . "<br/>";
								}
							}
	
						//set account to unlimited use
							try {
								if ($this->db_host == "localhost" || $this->db_host == "127.0.0.1") {
									$tmp_sql = "GRANT USAGE ON * . * TO '".$this->db_username."'@'localhost' ";
									$tmp_sql .= "IDENTIFIED BY '".$this->db_password."' ";
									$tmp_sql .= "WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0; ";
									$this->dbh->query($tmp_sql);
	
									$tmp_sql = "GRANT USAGE ON * . * TO '".$this->db_username."'@'127.0.0.1' ";
									$tmp_sql .= "IDENTIFIED BY '".$this->db_password."' ";
									$tmp_sql .= "WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0; ";
									$this->dbh->query($tmp_sql);
								}
								else {
									$tmp_sql = "GRANT USAGE ON * . * TO '".$this->db_username."'@'".$this->db_host."' ";
									$tmp_sql .= "IDENTIFIED BY '".$this->db_password."' ";
									$tmp_sql .= "WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0; ";
									$this->dbh->query($tmp_sql);
								}
							}
							catch (PDOException $error) {
								if ($this->debug) {
									print "error: " . $error->getMessage() . "<br/>";
								}
							}
	
						//create the database and set the create user with permissions
							try {
								$tmp_sql = "CREATE DATABASE IF NOT EXISTS ".$this->db_name."; ";
								$this->dbh->query($tmp_sql);
							}
							catch (PDOException $error) {
								if ($this->debug) {
									print "error: " . $error->getMessage() . "<br/>";
								}
							}
	
						//set user permissions
							try {
								$this->dbh->query("GRANT ALL PRIVILEGES ON ".$this->db_name.".* TO '".$this->db_username."'@'%'; ");
							}
							catch (PDOException $error) {
								if ($this->debug) {
									print "error: " . $error->getMessage() . "<br/>";
								}
							}
	
						//make the changes active
							try {
								$tmp_sql = "FLUSH PRIVILEGES; ";
								$this->dbh->query($tmp_sql);
							}
							catch (PDOException $error) {
								if ($this->debug) {
									print "error: " . $error->getMessage() . "<br/>";
								}
							}
	
					} //if (strlen($this->db_create_username) > 0)
	
				//select the database
					try {
						$this->dbh->query("USE ".$this->db_name.";");
					}
					catch (PDOException $error) {
						if ($this->debug) {
							print "error: " . $error->getMessage() . "<br/>";
						}
					}
	
				//add the database structure
					require_once "resources/classes/schema.php";
					$schema = new schema;
					$schema->db = $this->dbh;
					$schema->db_type = $this->db_type;
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
			$this->write_progress("Checking if domain exists '" . $this->domain_name . "'");
			$sql = "select * from v_domains ";
			$sql .= "where domain_name = '".$this->domain_name."' ";
			$sql .= "limit 1";
			$this->write_debug($sql);
			$prep_statement = $this->dbh->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetch(PDO::FETCH_NAMED);
			unset($sql, $prep_statement);
			if ($result) {
				$this->_domain_uuid = $result['domain_uuid'];
				$this->write_progress("... domain exists as '" . $this->_domain_uuid . "'");
				if($result['domain_enabled'] != 'true'){
					throw new Exception("Domain already exists but is disabled, this is unexpected");
				}
			}else{
				$this->write_progress("... creating domain");
				$sql = "insert into v_domains ";
				$sql .= "(";
				$sql .= "domain_uuid, ";
				$sql .= "domain_name, ";
				$sql .= "domain_description ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$this->_domain_uuid."', ";
				$sql .= "'".$this->domain_name."', ";
				$sql .= "'' ";
				$sql .= ");";
				
				$this->write_debug($sql);
				$this->dbh->exec(check_sql($sql));
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
				
				//switch settings
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $switch_bin_dir;
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'bin';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->base_dir();
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'base';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->conf_dir();
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'conf';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->db_dir();
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'db';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->log_dir();
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'log';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->mod_dir();
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'mod';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->script_dir();
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'scripts';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->grammar_dir();
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'grammar';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->storage_dir();
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'storage';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = join( DIRECTORY_SEPARATOR, $this->detect_switch->storage_dir(), 'voicemail');
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'voicemail';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->recordings_dir();
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'recordings';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->sounds_dir();
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'sounds';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = '';
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'provision';
				$tmp[$x]['enabled'] = 'false';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = join( DIRECTORY_SEPARATOR, $this->detect_switch->conf_dir(), "/directory");
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'extensions';
				$tmp[$x]['enabled'] = 'false';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = join( DIRECTORY_SEPARATOR, $this->detect_switch->conf_dir(), "/sip_profiles");
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'sip_profiles';
				$tmp[$x]['enabled'] = 'false';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = join( DIRECTORY_SEPARATOR, $this->detect_switch->conf_dir(), "/dialplan");
				$tmp[$x]['category'] = 'switch';
				$tmp[$x]['subcategory'] = 'dialplan';
				$tmp[$x]['enabled'] = 'false';
				$x++;
		
				//server settings
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = $this->detect_switch->temp_dir();
				$tmp[$x]['category'] = 'server';
				$tmp[$x]['subcategory'] = 'temp';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				#throw new Exception("I don't know how to find /etc/init.d for server > startup_scripts");
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = '';
				$tmp[$x]['category'] = 'server';
				$tmp[$x]['subcategory'] = 'startup_script';
				$tmp[$x]['enabled'] = 'true';
				$x++;
				$tmp[$x]['name'] = 'dir';
				$tmp[$x]['value'] = sys_get_temp_dir();
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
		
			//add the groups
				$x = 0;
				$tmp[$x]['group_name'] = 'superadmin';
				$tmp[$x]['group_description'] = 'Super Administrator Group';
				$x++;
				$tmp[$x]['group_name'] = 'admin';
				$tmp[$x]['group_description'] = 'Administrator Group';
				$x++;
				$tmp[$x]['group_name'] = 'user';
				$tmp[$x]['group_description'] = 'User Group';
				$x++;
				$tmp[$x]['group_name'] = 'public';
				$tmp[$x]['group_description'] = 'Public Group';
				$x++;
				$tmp[$x]['group_name'] = 'agent';
				$tmp[$x]['group_description'] = 'Call Center Agent Group';
				$this->dbh->beginTransaction();
				foreach($tmp as $row) {
					$sql = "insert into v_groups ";
					$sql .= "(";
					$sql .= "group_uuid, ";
					$sql .= "group_name, ";
					$sql .= "group_description ";
					$sql .= ") ";
					$sql .= "values ";
					$sql .= "(";
					$sql .= "'".uuid()."', ";
					$sql .= "'".$row['group_name']."', ";
					$sql .= "'".$row['group_description']."' ";
					$sql .= ");";
					$this->write_debug($sql);
					$this->dbh->exec(check_sql($sql));
					unset($sql);
				}
				unset($tmp);
				$this->dbh->commit();
				//assign the default permissions to the groups
				$this->dbh->beginTransaction();
				foreach($apps as $app) {
					if ($app['permissions']) {
						foreach ($app['permissions'] as $row) {
							if ($this->debug) {
								$this->write_debug( "v_group_permissions\n");
								$this->write_debug( json_encode($row)."\n\n");
							}
							if ($row['groups']) {
								foreach ($row['groups'] as $group) {
									//add the record
									$sql = "insert into v_group_permissions ";
									$sql .= "(";
									$sql .= "group_permission_uuid, ";
									$sql .= "permission_name, ";
									$sql .= "group_name ";
									$sql .= ") ";
									$sql .= "values ";
									$sql .= "(";
									$sql .= "'".uuid()."', ";
									$sql .= "'".$row['name']."', ";
									$sql .= "'".$group."' ";
									$sql .= ");";
									if ($this->debug) {
										$this->write_debug( $sql."\n");
									}
									$this->dbh->exec(check_sql($sql));
									unset($sql);
								}
							}
						}
					}
				}
				$this->dbh->commit();
			}
		}
		
		protected function create_superuser() {
			$this->write_progress("Checking if superuser exists '" . $this->domain_name . "'");
			$sql = "select * from v_users ";
			$sql .= "where domain_uuid = '".$this->_domain_uuid."' ";
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
				$this->write_progress("... superuser exists as '" . $this->admin_uuid . "', updating password");
				$sql = "update v_users ";
				$sql .= "set password = '".md5($salt.$this->admin_password)."' ";
				$sql .= "set salt = '$salt' ";
				$sql .= "where USER_uuid = '".$this->admin_uuid."' ";
				$this->write_debug($sql);
				$this->dbh->exec(check_sql($sql));
			}else{
				$this->write_progress("... creating super user '" . $this->admin_username . "'");
			//add a user and then add the user to the superadmin group
			//prepare the values
				$this->admin_uuid = uuid();
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
				$sql .= "add_user ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$this->_domain_uuid."', ";
				$sql .= "'".$this->admin_uuid."', ";
				$sql .= "'$contact_uuid', ";
				$sql .= "'".$this->admin_username."', ";
				$sql .= "'".md5($salt.$this->admin_password)."', ";
				$sql .= "'$salt', ";
				$sql .= "now(), ";
				$sql .= "'".$this->admin_username."' ";
				$sql .= ");";
				$this->write_debug( $sql."\n");
				$this->dbh->exec(check_sql($sql));
				unset($sql);
			}
			$this->write_progress("Checking if superuser contact exists");
			$sql = "select count(*) from v_contacts ";
			$sql .= "where domain_uuid = '".$this->_domain_uuid."' ";
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
				$sql .= "'".$this->_domain_uuid."', ";
				$sql .= "'$contact_uuid', ";
				$sql .= "'user', ";
				$sql .= "'".$this->admin_username."', ";
				$sql .= "'".$this->admin_username."' ";
				$sql .= ")";
				$this->dbh->exec(check_sql($sql));
				unset($sql);
			}
			$this->write_progress("Checking if superuser is in the correct group");
			$sql = "select count(*) from v_group_users ";
			$sql .= "where domain_uuid = '".$this->_domain_uuid."' ";
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
				$sql .= "'".$this->_domain_uuid."', ";
				$sql .= "'".$this->admin_uuid."', ";
				$sql .= "'superadmin' ";
				$sql .= ");";
				$this->write_debug( $sql."\n");
				$this->dbh->exec(check_sql($sql));
				unset($sql);
			}
		}
	
		protected function create_menus() {
			$this->write_progress("Creating menus");
		//set the defaults
			$menu_name = 'default';
			$menu_language = 'en-us';
			$menu_description = 'Default Menu Set';
			
			$this->write_progress("Checking if menu exists");
			$sql = "select count(*) from v_menus ";
			$sql .= "where menu_uuid = '".$this->menu_uuid."' ";
			$sql .= "limit 1 ";
			$this->write_debug($sql);
			$prep_statement = $this->dbh->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetch(PDO::FETCH_NAMED);
			unset($sql, $prep_statement);
			if ($result['count'] == 0) {
				$this->write_progress("... creating menu '" . $menu_name. "'");
				$sql = "insert into v_menus ";
				$sql .= "(";
				$sql .= "menu_uuid, ";
				$sql .= "menu_name, ";
				$sql .= "menu_language, ";
				$sql .= "menu_description ";
				$sql .= ") ";
				$sql .= "values ";
				$sql .= "(";
				$sql .= "'".$this->menu_uuid."', ";
				$sql .= "'$menu_name', ";
				$sql .= "'$menu_language', ";
				$sql .= "'$menu_description' ";
				$sql .= ");";
				if ($this->debug) {
					$this->write_debug( $sql."\n");
				}
				$this->dbh->exec(check_sql($sql));
				unset($sql);
		
			//add the menu items
				require_once "resources/classes/menu.php";
				$menu = new menu;
				$menu->db = $this->dbh;
				$menu->menu_uuid = $this->menu_uuid;
				$menu->restore();
				unset($menu);
			}
		}
		
		public function app_defaults() {
			$this->write_progress("Running app_defaults");
			
		//set needed session settings
			$_SESSION["username"] = $this->admin_username;
			$_SESSION["domain_uuid"] = $this->_domain_uuid;
			require $this->config_php;
			require "resources/require.php";
			$_SESSION['event_socket_ip_address'] = $this->detect_switch->event_host;
			$_SESSION['event_socket_port'] = $this->detect_switch->event_port;
			$_SESSION['event_socket_password'] = $this->detect_switch->event_password;
			
		//get the groups assigned to the user and then set the groups in $_SESSION["groups"]
			$sql = "SELECT * FROM v_group_users ";
			$sql .= "where domain_uuid=:domain_uuid ";
			$sql .= "and user_uuid=:user_uuid ";
			$prep_statement = $this->dbh->prepare(check_sql($sql));
			$prep_statement->bindParam(':domain_uuid', $this->domain_uuid);
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
						$sql .= "where (domain_uuid = '".$this->domain_uuid."' and group_name = '".$field['group_name']."') ";
					}
					else {
						$sql .= "or (domain_uuid = '".$this->domain_uuid."' and group_name = '".$field['group_name']."') ";
					}
					$x++;
				}
			}
			$prep_statementsub = $this->dbh->prepare($sql);
			$prep_statementsub->execute();
			$_SESSION['permissions'] = $prep_statementsub->fetchAll(PDO::FETCH_NAMED);
			unset($sql, $prep_statementsub);

			require_once "resources/classes/schema.php";
			global $db, $db_type, $db_name, $db_username, $db_password, $db_host, $db_path, $db_port;
	
			$schema = new schema;
			echo $schema->schema();
	
		//run all app_defaults.php files
			$default_language = $this->install_language;
			$domain = new domains;
			$domain->upgrade();
	
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