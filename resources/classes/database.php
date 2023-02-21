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
	Copyright (C) 2010 - 2022
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//define the database class
	if (!class_exists('database')) {
		class database {

			const TABLE_PREFIX = "v_";

			/**
			 * Database connection
			 * @access private
			 * @var PDO object
			 */
			private $db;

			/**
			 * Driver to use.
			 * @access private
			 * @var string Can be pgsql, mysql, sqlite, odbc
			 */
			private $driver;

			/**
			 * Alias of driver.
			 * @access private
			 * @var string Can be pgsql, mysql, sqlite, odbc
			 * @see $driver
			 */
			private $type;

			/**
			 * Host for database connection
			 * @access private
			 * @var string host name or IP address.
			 */
			private $host;

			/**
			 * Port number
			 * @access private
			 * @var int 1025 - 65534
			 */
			private $port;

			/**
			 * Database name
			 * @access private
			 * @var string
			 */
			private $db_name;

			/**
			 * Database security
			 * @access private
			 * @var boolean
			 */
			private $db_secure;

			/**
			 * Specifies the file name of the client SSL certificate
			 * @access private
			 * @var string full path
			 */
			private $db_cert_authority;

			/**
			 * Username used to connect
			 * @access private
			 * @var string
			 */
			private $username;

			/**
			 * Password used to connect
			 * @access private
			 * @var string
			 */
			private $password;

			/**
			 * Full path to file name.
			 * @access private
			 * @var string full path to file name
			 */
			private $path;

			/**
			 * Table name.
			 * @access private
			 * @var string sanitized
			 */
			private $table;

			/**
			 * Where clause(s) of an SQL statement.
			 * <p>Array of arrays must be passed with each having the
			 * following keys:
			 * <ol><li>'name' - Any valid column name.</li>
			 * <li>'operator' - Must be <b>one</b> of the following values: =, &gt;, &lt;, &gt;=, &lt;=, &lt;&gt;, !=</li>
			 * <li>'value' - Value being matched</li></ol></p>
			 * <p>Example Usage:</p>
			 * <p><code>$db->where['SearchTerm'] = ['name'=>'MyColumn','operator'=>'=','value'=>'MySearchTerm'</code></p>
			 * <p><code>$db->where['NextSearchTerm'] = ['name'=>'MyColumn','operator'=>'=','value'=>'MyOtherSearchTerm'</code></p>
			 * <p>Below is equivalent to the above.</p>
			 * <p><code>$db->where[0] = ['name'=>'MyColumn','operator'=>'=','value'=>'MyValue'</code></p>
			 * <p><code>$db->where[1] = ['name'=>'MyColumn','operator'=>'=&gt;','value'=>'MyValue'</code></p>
			 * @access private
			 * @var array Two dimensional array of key value pairs
			 * @see $order_by
			 */
			public $where; //array

			/**
			 * Order By clause(s) of an SQL statement.
			 * <p>Array of arrays must be passed with each having the
			 * following keys:
			 * <ol><li>'name' - Any valid column name.</li>
			 * <li>'operator' - Must be <b>one</b> of the following values: =, &gt;, &lt;, &gt;=, &lt;=, &lt;&gt;, !=</li>
			 * <li>'value' - Value being matched</li></ol></p>
			 * <p>Example Usage:</p>
			 * <p><code>$db->where['SearchTerm'] = ['name'=>'MyColumn','operator'=>'=','value'=>'MySearchTerm'</code></p>
			 * <p><code>$db->where['NextSearchTerm'] = ['name'=>'MyColumn','operator'=>'=','value'=>'MyOtherSearchTerm'</code></p>
			 * <p>Below is equivalent to the above.</p>
			 * <p><code>$db->where[0] = ['name'=>'MyColumn','operator'=>'=','value'=>'MyValue'</code></p>
			 * <p><code>$db->where[1] = ['name'=>'MyColumn','operator'=>'=&gt;','value'=>'MyValue'</code></p>
			 * @access private
			 * @var array Two dimensional array of key value pairs
			 * @see $where
			 */
			public $order_by; //array

			/**
			 * Ascending or Descending order.
			 * @var string
			 * @access private
			 */
			private $order_type;

			/**
			 * Numerical value to limit returned results.
			 * @var int Used for 'LIMIT' in SQL statement.
			 * @access private
			 */
			private $limit;

			/**
			 * Numerical value to offset returned results.
			 * @var int Used for 'OFFSET' in SQL statement.
			 * @access private
			 */
			private $offset;

			/**
			 * <p>Array of fields.</p>
			 * <p>Fields are specified in 'name'=>'value' format.
			 * <p>Used by {@link database::add() } and {@link database::update() }</p>
			 * @access private
			 * @var array Array of columns
			 * @see database::add()
			 * @see database::update()
			 */
			private $fields;

			/**
			 * Unknown property
			 * @var unknown
			 * @access private
			 */
			private $count;

			/**
			 * Unknown property
			 * @var unknown
			 * @access private
			 */
			private $sql;

			/**
			 * <p>Stores the result from the most recent query. The type will be based on what was requested.</p>
			 * <p><b>NOTE:</b> If an error occurred on the last query the result is set to an empty string.</p>
			 * @var mixed
			 */
			private $result;

			/**
			 * Stores the application name making the request.
			 * @var string App name making database request.
			 * @see $app_uuid
			 * @access public
			 */
			public $app_name;

			/**
			 * Stores the application UUID making the request.
			 * @var string
			 * @see $app_name
			 * @access public
			 */
			public $app_uuid;

			/**
			 * <p>Stores the domain UUID making the request.</p>
			 * <p>This is defaulted to the Session domain UUID.</p>
			 * @access private
			 * @uses $_SESSION['domain_uuid'] <br>Default value upon object creation
			 * @var string Domain UUID making request.
			 */
			private $domain_uuid;

			/**
			 * <p>Message for the query results.</p>
			 * @var array Contains the message array after a query
			 * @access private
			 */
			private $message;

			/**
			 * Called when the object is created
			 */
			public function __construct() {
				if (!isset($this->domain_uuid) && isset($_SESSION['domain_uuid'])) {
					$this->domain_uuid = $_SESSION['domain_uuid'];
				}
			}

			/**
			 * <p>Magic function called whenever a property is attempted to be set.</p>
			 * <p>This is used to protect the values stored in the object properties.</p>
			 * @param mixed $name Name of object property
			 * @param mixed $value Value of property
			 */
			public function __set($name,$value) {
				switch($name) {
					case 'name':
					case 'app_name':
						$this->app_name = self::sanitize($value);
						break;
					case 'message':
						if (is_array($value)) {
							$this->message = $value;
						} else {
							trigger_error('Message property must be set to array type', E_USER_ERROR);
						}
						break;
					case 'table':
						$this->table = self::sanitize($value);
						break;
					case 'db_name':
						$this->db_name = self::sanitize($value);
						break;
					case 'db':
						if ($name instanceof PDO) {
							$this->db = $value;
						} else {
							trigger_error('db property must be a PDO object!', E_USER_ERROR);
						}
						break;
					case 'count':
						break;
					case 'path':
						$value = realpath($value);
						if (file_exists($value)) {
							$this->path = $value;
						} else {
							trigger_error('Unable to find database path file!', E_USER_ERROR);
						}
						break;
					case 'db_cert_authority':
						if (!file_exists($value)) {
							trigger_error('db cert authority not found!', E_USER_WARNING);
						}
						$this->db_cert_authority = $value;
						break;
					case 'port':
						$value = (int)$value; // force cast to int
						if ($value > 1023 && $value < 65536) { $this->port = $value;	} //valid values are 1024...65535
						else { trigger_error('Port not a valid range', E_USER_ERROR);	}
						break;
					case 'app_uuid':
					case 'domain_uuid':
						if (is_uuid($value)) { $this->domain_uuid = $value; }
						break;
					case 'type':
					case 'driver':
						switch($value) {
							case 'pgsql':
							case 'mysql':
							case 'sqlite':
							case 'odbc':
								$this->type = $value;
								$this->driver = $value;
								break;
							default:
								trigger_error("Type/Driver must be set to pgsql,mysql,sqlite,odbc", E_USER_ERROR);
								break;
						}
					case 'offset':
					case 'limit':
						if (is_int($value)) {
							$this->$name = $value;
						} else {
							trigger_error('Offset or Limit not set to valid integer. Resetting to zero!', E_USER_WARNING);
						}
						break;
					case '':
						trigger_error('Database property must not be empty', E_USER_ERROR);
						break;
					case 'null':
					case null:
						trigger_error('Database property must not be null', E_USER_ERROR);
						break;
					case 'debug':
						$this->debug = $value;
				}
			}

			/**
			 * Magic function called whenever a property is requested.
			 * <p>If any case statement is removed then access to the variable will be removed.</p>
			 * @param mixed $name object property
			 * @return mixed
			 */
			public function __get($name) {
				//remove any case statement below to remove access to the variable
				switch($name) {
					case 'name':
						return $this->app_name;
					case 'app_name':
					case 'app_uuid':
					case 'db':
					case 'db_cert_authority':
					case 'db_name':
					case 'db_secure':
					case 'domain_uuid':
					case 'driver':
					case 'fields':
					case 'host':
					case 'limit':
					case 'message':
					case 'offset':
					case 'order_by':
					case 'order_type':
					case 'password':
					case 'path':
					case 'port':
					case 'result':
					case 'sql':
					case 'table':
					case 'type':
					case 'username':
					case 'where':
					case 'debug':
						return $this->$name;
					case 'count':
						return $this->count();
					default:
						trigger_error('Object property not available', E_USER_ERROR);
				}
			}

			/**
			 * Called when there are no references to a particular object
			 * unset the variables used in the class
			 */
			public function __destruct() {
				foreach ($this as $key => $value) {
					unset($this->$key);
				}
			}

			/**
			 * <p>Connect to the database.</p>
			 * <p>Database driver must be set before calling connect.</p>
			 * <p>For types other than sqlite. Execution will stop on failure.</p>
			 * @depends database::driver Alias of database::type.
			 *
			 */
			public function connect() {

				//set the include path
					$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
					set_include_path(parse_ini_file($conf[0])['document.root']);

				//parset the config.conf file
					$conf = parse_ini_file($conf[0]);

				//get the database connection settings
					$db_type = $conf['database.0.type'];
					$db_host = $conf['database.0.host'];
					$db_port = $conf['database.0.port'];
					$db_name = $conf['database.0.name'];
					$db_username = $conf['database.0.username'];
					$db_password = $conf['database.0.password'];

				//debug info
					//echo "db type:".$db_type."\n";
					//echo "db host:".$db_host."\n";
					//echo "db port:".$db_port."\n";
					//echo "db name:".$db_name."\n";
					//echo "db username:".$db_username."\n";
					//echo "db password:".$db_password."\n";
					//echo "db path:".$db_path."\n";
					//echo "</pre>\n";

				//set defaults
					if (!isset($this->driver) && isset($db_type)) { $this->driver = $db_type; }
					if (!isset($this->type) && isset($db_type)) { $this->type = $db_type; }
					if (!isset($this->host) && isset($db_host)) { $this->host = $db_host; }
					if (!isset($this->port) && isset($db_port)) { $this->port = $db_port; }
					if (!isset($this->db_name) && isset($db_name)) { $this->db_name = $db_name; }
					if (!isset($this->db_secure) && isset($db_secure)) {
						$this->db_secure = $db_secure;
					}
					else {
						$this->db_secure = false;
					}
					if (!isset($this->username) && isset($db_username)) { $this->username = $db_username; }
					if (!isset($this->password) && isset($db_password)) { $this->password = $db_password; }
					if (!isset($this->path) && isset($db_path)) { $this->path = $db_path; }

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
						//connect to the database
							$this->db = new PDO('sqlite:'.$this->path.'/'.$this->db_name); //sqlite 3
						//PRAGMA commands
							$this->db->query('PRAGMA foreign_keys = ON;');
							$this->db->query('PRAGMA journal_mode = wal;');
						//add additional functions to SQLite so that they are accessible inside SQL
							//bool PDO::sqliteCreateFunction ( string function_name, callback callback [, int num_args] )
							$this->db->sqliteCreateFunction('md5', 'php_md5', 1);
							$this->db->sqliteCreateFunction('unix_timestamp', 'php_unix_timestamp', 1);
							$this->db->sqliteCreateFunction('now', 'php_now', 0);
							$this->db->sqliteCreateFunction('sqlitedatatype', 'php_sqlite_data_type', 2);
							$this->db->sqliteCreateFunction('strleft', 'php_left', 2);
							$this->db->sqliteCreateFunction('strright', 'php_right', 2);
					}
					else {
						echo "not found";
					}
				}

				if ($this->driver == "mysql") {
					try {
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
							if ($this->db_secure === true) {
								$this->db = new PDO("pgsql:host=$this->host port=$this->port dbname=$this->db_name user=$this->username password=$this->password sslmode=verify-ca sslrootcert=$this->db_cert_authority");
							}
							else {
								$this->db = new PDO("pgsql:host=$this->host port=$this->port dbname=$this->db_name user=$this->username password=$this->password");
							}
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

			/**
			 * Returns the table names from the database.
			 * @return array tables
			 * @depends connect()
			 */
			public function tables() {
					$result = [];
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
						if (is_array($tmp)) {
							foreach ($tmp as &$row) {
								$result[]['name'] = $row['name'];
							}
						}
					}
					if ($this->type == "mysql") {
						if (is_array($tmp)) {
							foreach ($tmp as &$row) {
								$table_array = array_values($row);
								$result[]['name'] = $table_array[0];
							}
						}
					}
					return $result;
			}

			/**
			 * Returns table information from the database.
			 * @return array table info
			 * @depends connect()
			 */
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

			/**
			 * Checks if the table exists in the database.
			 * <p><b>Note:</b><br>
			 * Table name must be sanitized. Otherwise, a warning will be
			 * emitted and false will be returned.</p>
			 * @param type $table_name Sanitized name of the table to search for.
			 * @return boolean Returns <i>true</i> if the table exists and <i>false</i> if it does not.
			 * @depends connect()
			 */
			public function table_exists ($table_name) {
				if (self::sanitize($table_name) != $table_name) {
					trigger_error('Table Name must be sanitized', E_USER_WARNING);
					return false;
				}

				//connect to the database if needed
				if (!$this->db) {
					$this->connect();
				}

				//query table store to see if the table exists
				$sql = "";
				if ($this->type == "sqlite") {
					$sql .= "SELECT * FROM sqlite_master WHERE type='table' and name='$table_name' ";
				}
				if ($this->type == "pgsql") {
					$sql .= "select * from pg_tables where schemaname='public' and tablename = '$table_name' ";
				}
				if ($this->type == "mysql") {
					$sql .= "SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = '$db_name' and TABLE_NAME = '$table_name' ";
				}
				$prep_statement = $this->db->prepare($sql);
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result) > 0) {
					return true; //table exists
				}
				else {
					return false; //table doesn't exist
				}
			}

			/**
			 * Queries {@link database::table_info()} to return the fields.
			 * @access public
			 * @return array Two dimensional array
			 * @depends table_info()
			 */
			public function fields() {
				//public $db;
				//public $type;
				//public $table;
				//public $name;
				
				//initialize the array
					$result = [];

				//get the table info
					$table_info = $this->table_info();

				//set the list of fields
					if ($this->type == "sqlite") {
						if (is_array($table_info)) {
							foreach($table_info as $row) {
								$result[]['name'] = $row['name'];
							}
						}
					}
					if ($this->type == "pgsql") {
						if (is_array($table_info)) {
							foreach($table_info as $row) {
								$result[]['name'] = $row['column_name'];
							}
						}
					}
					if ($this->type == "mysql") {
						if (is_array($table_info)) {
							foreach($table_info as $row) {
								$result[]['name'] = $row['Field'];
							}
						}
					}
					if ($this->type == "mssql") {
						if (is_array($table_info)) {
							foreach($table_info as $row) {
								$result[]['name'] = $row['COLUMN_NAME'];
							}
						}
					}

				//return the result array
					return $result;
			}

			/**
			 * Searches database using the following object properties:
			 * <ol>
			 *  <li>table - sanitized name of the table {@see database::table}</li>
			 *  <li>where - where clause {@see database::where}</li>
			 *  <li>order_by - order_by clause {@see database::order_by}</li>
			 *  <li>limit - limit clause {@see database::limit}</li>
			 *  <li>offset - offset clause {@see database::offset}</li>
			 * </ol>
			 * @return boolean
			 * @depends connect()
			 */
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
						if (is_array($this->where)) {
							foreach($this->where as $row) {
								//sanitize the name
								$array['name'] = self::sanitize($array['name']);

								//validate the operator
								switch ($row['operator']) {
									case "<": break;
									case ">": break;
									case "<=": break;
									case ">=": break;
									case "=": break;
									case "<>": break;
									case "!=": break;
									default:
										//invalid operator
										return false;
								}

								//build the sql
								if ($i == 0) {
									//$sql .= 'where '.$row['name']." ".$row['operator']." '".$row['value']."' ";
									$sql .= 'where '.$row['name']." ".$row['operator']." :".$row['name']." ";
								}
								else {
									//$sql .= "and ".$row['name']." ".$row['operator']." '".$row['value']."' ";
									$sql .= "and ".$row['name']." ".$row['operator']." :".$row['name']." ";
								}

								//add the name and value to the params array
								$params[$row['name']] = $row['value'];

								//increment $i
								$i++;
							}
						}
					}
					if (is_array($this->order_by)) {
						$sql .= "order by ";
						$i = 1;
						if (is_array($this->order_by)) {
							foreach($this->order_by as $row) {
								//sanitize the name
								$row['name'] = self::sanitize($row['name']);

								//sanitize the order
								switch ($row['order']) {
									case "asc":
										break;
									case "desc":
										break;
									default:
										$row['order'] = '';
								}

								//build the sql
								if (count($this->order_by) == $i) {
									$sql .= $row['name']." ".$row['order']." ";
								}
								else {
									$sql .= $row['name']." ".$row['order'].", ";
								}

								//increment $i
								$i++;
							}
						}
					}

					//limit
					if (isset($this->limit) && is_numeric($this->limit)) {
						$sql .= "limit ".$this->limit." ";
					}
					//offset
					if (isset($this->offset) && is_numeric($this->offset)) {
						$sql .= "offset ".$this->offset." ";
					}

					$prep_statement = $this->db->prepare($sql);
					if ($prep_statement) {
						$prep_statement->execute($params);
						$array = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
						unset($prep_statement);
						return $array;
					}
					else {
						return false;
					}
			}

			// Use this function to execute complex queries
			public function execute($sql, $parameters = null, $return_type = 'all') {

				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}

				//set the error mode
					$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				//execute the query, and return the results
					try {
						$prep_statement = $this->db->prepare($sql);
						if (is_array($parameters)) {
							$prep_statement->execute($parameters);
						}
						else {
							$prep_statement->execute();
						}
						$message["message"] = "OK";
						$message["code"] = "200";
						$message["sql"] = $sql;
						if (is_array($parameters)) {
							$message["parameters"] = $parameters;
						}
						$this->message = $message;

						//return the results
						switch($return_type) {
						case 'all':
							return $prep_statement->fetchAll(PDO::FETCH_ASSOC);
						case 'row':
							return $prep_statement->fetch(PDO::FETCH_ASSOC);
						case 'column';
							return $prep_statement->fetchColumn();
						default:
							return $prep_statement->fetchAll(PDO::FETCH_ASSOC);
						}
					}
					catch(PDOException $e) {
						$message["message"] = "Bad Request";
						$message["code"] = "400";
						$message["error"]["message"] = $e->getMessage();
						if ($this->debug["sql"]) {
							$message["sql"] = $sql;
						}
						if (is_array($parameters)) {
							$message["parameters"] = $parameters;
						}
						$this->message = $message;
						return false;
					}
			}

			public function add() {
				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}

				//sanitize the table name
					//$this->table = self::sanitize($this->table); // no longer needed

				//count the fields
					$field_count = count($this->fields);

				//add data to the database
					$sql = "insert into ".$this->table;
					$sql .= " (";
					$i = 1;
					if (is_array($this->fields)) {
						foreach($this->fields as $name => $value) {
							$name = self::sanitize($name);
							if (count($this->fields) == $i) {
								$sql .= $name." \n";
							}
							else {
								$sql .= $name.", \n";
							}
							$i++;
						}
					}
					$sql .= ") \n";
					$sql .= "values \n";
					$sql .= "(\n";
					$i = 1;
					if (is_array($this->fields)) {
						foreach($this->fields as $name => $value) {
							$name = self::sanitize($name);
							if ($field_count == $i) {
								if (strlen($value) > 0) {
									//$sql .= "'".$value."' ";
									$sql .= ":".$name." \n";
									$params[$name] = trim($value);
								}
								else {
									$sql .= "null \n";
								}
							}
							else {
								if (strlen($value) > 0) {
									//$sql .= "'".$value."', ";
									$sql .= ":".$name.", \n";
									$params[$name] = trim($value);
								}
								else {
									$sql .= "null, \n";
								}
							}
							$i++;
						}
					}
					$sql .= ")\n";

				//execute the query, show exceptions
					$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				//reduce prepared statement latency
					if (defined('PDO::PGSQL_ATTR_DISABLE_PREPARES')) {
						$this->db->setAttribute(PDO::PGSQL_ATTR_DISABLE_PREPARES, true);
					}

				//prepare the sql and parameters and then execute the query
					try {
						//$this->sql = $sql;
						//$this->db->exec($sql);
						$prep_statement = $this->db->prepare($sql);
						$prep_statement->execute($params);
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
					unset($sql, $prep_statement, $this->fields);
			}

			public function update() {
				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}

				//sanitize the table name
					//$this->table = self::sanitize($this->table); // no longer needed

				//udate the database
					$sql = "update ".$this->table." set ";
					$i = 1;
					if (is_array($this->fields)) {
						foreach($this->fields as $name => $value) {
							$name = self::sanitize($name);
							if (count($this->fields) == $i) {
								if (strlen($name) > 0 && $value == null) {
									$sql .= $name." = null ";
								}
								else {
									//$sql .= $name." = '".$value."' ";
									$sql .= $name." = :".$name." ";
									$params[$name] = trim($value);
								}
							}
							else {
								if (strlen($name) > 0 && $value == null) {
									$sql .= $name." = null, ";
								}
								else {
									//$sql .= $name." = '".$value."', ";
									$sql .= $name." = :".$name.", ";
									$params[$name] = trim($value);
								}
							}
							$i++;
						}
					}
					$i = 0;
					if (is_array($this->where)) {
						foreach($this->where as $row) {

							//sanitize the name
							$row['name'] = self::sanitize($row['name']);

							//validate the operator
							switch ($row['operator']) {
								case "<": break;
								case ">": break;
								case "<=": break;
								case ">=": break;
								case "=": break;
								case "<>": break;
								case "!=": break;
								default:
									//invalid operator
									return false;
							}

							//build the sql
							if ($i == 0) {
								//$sql .= $row['name']." ".$row['operator']." '".$row['value']."' ";
								$sql .= "where ".$row['name']." ".$row['operator']." :".$row['name']." ";
							}
							else {
								//$sql .= $row['name']." ".$row['operator']." '".$row['value']."' ";
								$sql .= "and ".$row['name']." ".$row['operator']." :".$row['name']." ";
							}

							//add the name and value to the params array
							$params[$row['name']] = $row['value'];

							//increment $i
							$i++;
						}
					}
					//$this->db->exec(check_sql($sql));
					$prep_statement = $this->db->prepare($sql);
					$prep_statement->execute($params);
					unset($prep_statement);
					unset($this->fields);
					unset($this->where);
					unset($sql);
			}

			public function delete(array $array) {
				//set the default value
					$retval = true;

				//return the array
					if (!is_array($array)) { return false; }

				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}

				//set the message id
					$m = 0;

				//debug sql
					//$this->debug["sql"] = true;

				//set the message id
					$m = 0;

				//loop through the array
					$checked = false;
					$x = 0;
					foreach ($array as $parent_name => $tables) {
						if (is_array($tables)) {
							foreach ($tables as $id => $row) {

								//prepare the variables
									$parent_name = self::sanitize($parent_name);
									$parent_key_name = self::singular($parent_name)."_uuid";

								//build the delete array
									if ($row['checked'] == 'true') {
										//set checked to true
										$checked = true;

										//delete the child data
										if (isset($row[$parent_key_name])) {
											$new_array[$parent_name][$x][$parent_key_name] = $row[$parent_key_name];
										}

										//remove the row from the main array
										unset($array[$parent_name][$x]);
									}

								//loop through the fields
									foreach($row as $field_name => $field_value) {

										//find the child tables
										$y = 0;
										if (is_array($field_value)) {
											//prepare the variables
											$child_name = self::sanitize($field_name);
											$child_key_name = self::singular($child_name)."_uuid";

											//loop through the child rows
											foreach ($field_value as $sub_row) {

												//build the delete array
												if ($row['checked'] == 'true') {
													//set checked to true
													$checked = true;

													//delete the child data
													$new_array[$child_name][][$child_key_name] = $sub_row[$child_key_name];

													//remove the row from the main array
													unset($array[$parent_name][$x][$child_name][$y]);
												}

												//increment the value
												$y++;
											}
										}
									}

								//increment the value
									$x++;

							}
						}
					}

				//if not checked then copy the array to delete array
					if (!$checked) {
						$new_array = $array;
					}

				//get the current data
					if (count($new_array) > 0) {
						//build an array of tables, fields, and values
						foreach($new_array as $table_name => $rows) {
							foreach($rows as $row) {
								foreach($row as $field_name => $field_value) {
									$keys[$table_name][$field_name][] = $field_value;
								}
							}
						}

						//use the array to get a copy of the parent data before deleting it
						foreach($new_array as $table_name => $rows) {
							foreach($rows as $row) {
								$table_name = self::sanitize($table_name);
								$sql = "select * from ".self::TABLE_PREFIX.$table_name." ";
								$i = 0;
								foreach($row as $field_name => $field_value) {
									if ($i == 0) { $sql .= "where "; } else { $sql .= "and "; }
									$sql .= $field_name." in ( ";
									$i = 0;
									foreach($keys[$table_name][$field_name] as $field_value) {
										$field_name = self::sanitize($field_name);
										if ($i > 0) { $sql .= " ,"; }
										$sql .= " :".$field_name."_".$i." ";
										$i++;
									}
									$sql .= ") ";
									$i = 0;
									foreach($keys[$table_name][$field_name] as $field_value) {
										$parameters[$field_name.'_'.$i] = $field_value;
										$i++;
									}
								}
							}
							if (strlen($field_value) > 0) {
								$results = $this->execute($sql, $parameters, 'all');
								unset($parameters);
								if (is_array($results)) {
									$old_array[$table_name] = $results;
								}
							}
						}

						//get relations array
						$relations = self::get_relations($parent_name);

						//add child data to the old array
						foreach($old_array as $parent_name => $rows) {
							//get relations array
							$relations = self::get_relations($parent_name);

							//loop through the rows
							$x = 0;
							foreach($rows as $row) {
								if (is_array($relations)) {
									foreach ($relations as $relation) {
										if ($relation['key']['action']['delete'] == 'cascade') {
											//set the child table
											$child_table = $relation['table'];

											//remove the v_ prefix
											if (substr($child_table, 0, strlen(self::TABLE_PREFIX)) == self::TABLE_PREFIX) {
												$child_table = substr($child_table, strlen(self::TABLE_PREFIX));
											}

											//get the child data
											$sql = "select * from ".self::TABLE_PREFIX.$child_table." ";
											$sql .= "where ".$relation['field']." = :".$relation['field'];
											$parameters[$relation['field']] = $row[$relation['field']];
											$results = $this->execute($sql, $parameters, 'all');
											unset($parameters);
											if (is_array($results) && $parent_name !== $child_table) {
												$old_array[$parent_name][$x][$child_table] = $results;
											}

											//delete the child data
											if (isset($row[$relation['field']]) && strlen($row[$relation['field']]) > 0) {
												$sql = "delete from ".self::TABLE_PREFIX.$child_table." ";
												$sql .= "where ".$relation['field']." = :".$relation['field'];
												$parameters[$relation['field']] = $row[$relation['field']];
//												$this->execute($sql, $parameters);
											}
											unset($parameters);
										}
									}
								}
								$x++;
							}
						}
					}

				//start the atomic transaction
					$this->db->beginTransaction();

				//delete the current data
					foreach($new_array as $table_name => $rows) {
						//echo "table: ".$table_name."\n";
						foreach($rows as $row) {
							if (permission_exists(self::singular($table_name).'_delete')) {
								$sql = "delete from ".self::TABLE_PREFIX.$table_name." ";
								$i = 0;
								foreach($row as $field_name => $field_value) {
									//echo "field: ".$field_name." = ".$field_value."\n";
									if ($i == 0) { $sql .= "where "; } else { $sql .= "and "; }
									$sql .= $field_name." = :".$field_name." ";
									$parameters[$field_name] = $field_value;
									$i++;
								}
								try {
									$this->execute($sql, $parameters);
									$message["message"] = "OK";
									$message["code"] = "200";
									$message["uuid"] = $id;
									$message["details"][$m]["name"] = $this->name;
									$message["details"][$m]["message"] = "OK";
									$message["details"][$m]["code"] = "200";
									//$message["details"][$m]["uuid"] = $parent_key_value;
									if ($this->debug["sql"]) {
										$message["details"][$m]["sql"] = $sql;
									}
									$this->message = $message;
									$m++;
									unset($sql);
									unset($statement);
								}
								catch(PDOException $e) {
									$retval = false;
									$message["message"] = "Bad Request";
									$message["code"] = "400";
									$message["details"][$m]["name"] = $this->name;
									$message["details"][$m]["message"] = $e->getMessage();
									$message["details"][$m]["code"] = "400";
									if ($this->debug["sql"]) {
										$message["details"][$m]["sql"] = $sql;
									}
									$this->message = $message;
									$m++;
								}
								unset($parameters);
							} //if permission
						} //foreach rows
					} //foreach $array

				//commit the atomic transaction
					$this->db->commit();

				//set the action if not set
					$transaction_type = 'delete';

				//get the UUIDs
					$user_uuid = $_SESSION['user_uuid'];

				//log the transaction results
					if (file_exists($_SERVER["PROJECT_ROOT"]."/app/database_transactions/app_config.php")) {
						$sql = "insert into ".self::TABLE_PREFIX."database_transactions ";
						$sql .= "(";
						$sql .= "database_transaction_uuid, ";
						if (isset($this->domain_uuid) && is_uuid($this->domain_uuid)) {
							$sql .= "domain_uuid, ";
						}
						if (isset($user_uuid) && is_uuid($user_uuid)) {
							$sql .= "user_uuid, ";
						}
						if (isset($this->app_uuid) && is_uuid($this->app_uuid)) {
							$sql .= "app_uuid, ";
						}
						if (isset($this->app_name) && strlen($this->app_name) > 0) {
							$sql .= "app_name, ";
						}
						$sql .= "transaction_code, ";
						$sql .= "transaction_address, ";
						$sql .= "transaction_type, ";
						$sql .= "transaction_date, ";
						$sql .= "transaction_old, ";
						$sql .= "transaction_new, ";
						$sql .= "transaction_result ";
						$sql .= ")";
						$sql .= "values ";
						$sql .= "(";
						$sql .= "'".uuid()."', ";
						if (isset($this->domain_uuid) && is_uuid($this->domain_uuid)) {
							$sql .= "'".$this->domain_uuid."', ";
						}
						if (isset($user_uuid) && is_uuid($user_uuid)) {
							$sql .= ":user_uuid, ";
						}
						if (isset($this->app_uuid) && is_uuid($this->app_uuid)) {
							$sql .= ":app_uuid, ";
						}
						if (isset($this->app_name) && strlen($this->app_name) > 0) {
							$sql .= ":app_name, ";
						}
						$sql .= "'".$message["code"]."', ";
						$sql .= ":remote_address, ";
						$sql .= "'".$transaction_type."', ";
						$sql .= "now(), ";
						if (is_array($old_array)) {
							$sql .= ":transaction_old, ";
						}
						else {
							$sql .= "null, ";
						}
						if (is_array($new_array)) {
							$sql .= ":transaction_new, ";
						}
						else {
							$sql .= "null, ";
						}
						$sql .= ":transaction_result ";
						$sql .= ")";
						$statement = $this->db->prepare($sql);
						if (isset($user_uuid) && is_uuid($user_uuid)) {
							$statement->bindParam(':user_uuid', $user_uuid);
						}
						if (isset($this->app_uuid) && is_uuid($this->app_uuid)) {
							$statement->bindParam(':app_uuid', $this->app_uuid);
						}
						if (isset($this->app_name) && strlen($this->app_name) > 0) {
							$statement->bindParam(':app_name', $this->app_name);
						}
						$statement->bindParam(':remote_address', $_SERVER['REMOTE_ADDR']);
						if (is_array($old_array)) {
							$old_json = json_encode($old_array, JSON_PRETTY_PRINT);
							$statement->bindParam(':transaction_old', $old_json);
						}
						if (is_array($new_array)) {
							$new_json = json_encode($new_array, JSON_PRETTY_PRINT);
							$statement->bindParam(':transaction_new', $new_json);
						}
						$result = json_encode($this->message, JSON_PRETTY_PRINT);
						$statement->bindParam(':transaction_result', $result);
						$statement->execute();
						unset($sql);
					}
					return $retval;
			} //delete

			/**
			 * Counts the number of rows.
			 * @return int Represents the number of counted rows or -1 if failed.
			 */
			public function count() {

				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}

				//sanitize the table name
					//$this->table = self::sanitize($this->table); // no longer needed

				//get the number of rows
					$sql = "select count(*) as num_rows from ".$this->table." ";
					if ($this->where) {
						$i = 0;
						if (is_array($this->where)) {
							foreach($this->where as $row) {
								//sanitize the name
								$row['name'] = self::sanitize($row['name']);

								//validate the operator
								switch ($row['operator']) {
									case "<": break;
									case ">": break;
									case "<=": break;
									case ">=": break;
									case "=": break;
									case "<>": break;
									case "!=": break;
									default:
										//invalid operator
										return -1;
								}

								//build the sql
								if ($i == 0) {
									$sql .= "where ".$row['name']." ".$row['operator']." :".$row['name']." ";
								}
								else {
									$sql .= "and ".$row['name']." ".$row['operator']." :".$row['name']." ";
								}

								//add the name and value to the params array
								$params[$row['name']] = $row['value'];

								//increment $i
								$i++;
							}
						}
					}
					//unset($this->where); //should not be objects resposibility
					$prep_statement = $this->db->prepare($sql);
					if ($prep_statement) {
						$prep_statement->execute($params);
						$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
						if ($row['num_rows'] > 0) {
							return $row['num_rows'];
						}
						else {
							return 0;
						}
					}
					unset($prep_statement);

			} //count

			/**
			 * Performs a select query on database using the <b>$sql</b> statement supplied.
			 * @param type $sql Valid SQL statement.
			 * @param type $parameters Value can be <i>array</i>, empty string, or <i>null</i>.
			 * @param type $return_type Values can be set to <i>all</i>, <i>row</i>, or <i>column</i>.
			 * @return mixed Returned values can be array, string, boolean, int, or false. This is dependent on <i>$return_type</i>.
			 */
			public function select($sql, $parameters = '', $return_type = 'all') {

				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}

				//set the error mode
					$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				//reduce prepared statement latency
					if (defined('PDO::PGSQL_ATTR_DISABLE_PREPARES')) {
						$this->db->setAttribute(PDO::PGSQL_ATTR_DISABLE_PREPARES, true);
					}

				//execute the query and return the results
					try {
						$prep_statement = $this->db->prepare($sql);
						if (is_array($parameters)) {
							$prep_statement->execute($parameters);
						}
						else {
							$prep_statement->execute();
						}
						$message["message"] = "OK";
						$message["code"] = "200";
						$message["sql"] = $sql;
						if (is_array($parameters)) {
							$message["parameters"] = $parameters;
						}
						$this->message = $message;

						//return the results
						switch($return_type) {
						case 'all':
							return $prep_statement->fetchAll(PDO::FETCH_ASSOC);
						case 'row':
							return $prep_statement->fetch(PDO::FETCH_ASSOC);
						case 'column':
							return $prep_statement->fetchColumn();
						default:
							return $prep_statement->fetchAll(PDO::FETCH_ASSOC);
						}
					}
					catch(PDOException $e) {
						$message["message"] = "Bad Request";
						$message["code"] = "400";
						$message["error"]["message"] = $e->getMessage();
						if ($this->debug["sql"]) {
							$message["sql"] = $sql;
						}
						if (is_array($parameters)) {
							$message["parameters"] = $parameters;
						}
						$this->message = $message;
						return false;
					}
			} //select

			 /**
			 * Sets the object <i>$result</i> to sql array
			 * @param array $array Array containing the table name, uuid, SQL and where clause.
			 * @return database Returns the database object or null.
			 */
			public function find_new(array $array) {

				//connect to the database if needed
				if (!$this->db) {
					$this->connect();
				}

				//set the name
				if (isset($array['name'])) {
					$this->name = $array['name'];
				}

				//set the uuid
				if (isset($array['uuid'])) {
					$this->uuid = $array['uuid'];
				}

				//build the query
				$sql = "SELECT * FROM ".self::TABLE_PREFIX . $this->name . " ";
				if (isset($this->uuid)) {
					//get the specific uuid
					$sql .= "WHERE " . self::singular($this->name) . "_uuid = '" . $this->uuid . "' ";
				} else {
					//where
					$i = 0;
					if (isset($array['where'])) {
						foreach ($array['where'] as $row) {
							if (isset($row['operator'])) {
								//validate the operator
								switch ($row['operator']) {
									case "<": break;
									case ">": break;
									case "<=": break;
									case ">=": break;
									case "=": break;
									case "<>": break;
									case "!=": break;
									default:
										//invalid operator
										return null;
								}

								//build the sql
								if ($i == 0) {
									$sql .= "WHERE " . $row['name'] . " " . $row['operator'] . " :" . $row['value'] . " ";
								} else {
									$sql .= "AND " . $row['name'] . " " . $row['operator'] . " :" . $row['value'] . " ";
								}
							}
							//add the name and value to the params array
							$params[$row['name']] = $row['value'];

							//increment $i
							$i++;
						}
					}
					//order by
					if (isset($array['order_by'])) {
						$array['order_by'] = self::sanitize($array['order_by']);
						$sql .= "ORDER BY " . $array['order_by'] . " ";
					}
					//limit
					if (isset($array['limit']) && is_numeric($array['limit'])) {
						$sql .= "LIMIT " . $array['limit'] . " ";
					}
					//offset
					if (isset($array['offset']) && is_numeric($array['offset'])) {
						$sql .= "OFFSET " . $array['offset'] . " ";
					}
				}
				//execute the query, and return the results
				try {
					$prep_statement = $this->db->prepare($sql);
					$prep_statement->execute($params);
					$message["message"] = "OK";
					$message["code"] = "200";
					$message["details"][$m]["name"] = $this->name;
					$message["details"][$m]["message"] = "OK";
					$message["details"][$m]["code"] = "200";
					if ($this->debug["sql"]) {
						$message["details"][$m]["sql"] = $sql;
					}
					$this->message = $message;
					$this->result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					unset($prep_statement);
					$m++;
				} catch (PDOException $e) {
					$message["message"] = "Bad Request";
					$message["code"] = "400";
					$message["details"][$m]["name"] = $this->name;
					$message["details"][$m]["message"] = $e->getMessage();
					$message["details"][$m]["code"] = "400";
					if ($this->debug["sql"]) {
						$message["details"][$m]["sql"] = $sql;
					}
					$this->message = $message;
					$this->result = '';
					$m++;
				}
				return $this;
			}

			/**
			 * Stores the passed UUID in the object
			 * @param string $uuid A valid UUID must be passed
			 * @return database Returns this object
			 */
			public function uuid(string $uuid) {
				$this->uuid = $uuid;
				return $this;
			}

			/**
			 * Copies records and appends <i>suffix</i> to the column <i>description</i> data
			 * @param array $array Three dimensional Array. The first dimension is the table name without the prefix 'v_'. Second dimension in the row value as int. Third dimension is the column name.
			 * @return bool Returns <b>true</b> on success and <b>false</b> on failure.
			 */
			public function copy(array $array, $suffix = '(Copy)') {
				//set default return value
					$retval = false;

				//return the array
					if (!is_array($array)) { return $retval; }

				//initialize array
					$copy_array = [];

				//set the message id
					$m = 0;

				//loop through the array
					$x = 0;
					foreach ($array as $parent_name => $tables) {
						if (is_array($tables)) {
							foreach ($tables as $id => $row) {

								//prepare the variables
									$parent_name = self::sanitize($parent_name);
									$parent_key_name = self::singular($parent_name)."_uuid";

								//build the copy array
									if ($row['checked'] == 'true') {
										//set checked to true
										$checked = true;

										//copy the child data
										if (is_uuid($row[$parent_key_name])) {
											$copy_array[$parent_name][$x][$parent_key_name] = $row[$parent_key_name];
										}

										//remove the row from the main array
										unset($array[$parent_name][$x]);

										//loop through the fields
										foreach($row as $field_name => $field_value) {
											//find the child tables
											if (is_array($field_value)) {

												//prepare the variables
												$child_name = self::sanitize($field_name);
												$child_key_name = self::singular($child_name)."_uuid";

												//loop through the child rows
												$y = 0;
												foreach ($field_value as $sub_row) {

													//delete the child data
													$copy_array[$child_name][][$child_key_name] = $sub_row[$child_key_name];

													//remove the row from the main array
													unset($array[$parent_name][$x][$child_name][$y]);

													//increment the value
													$y++;
												}
											}
										}
									}

								//increment the value
									$x++;

							}
						}
					}

				//get the current data
					if (count($copy_array) > 0) {

						//build an array of tables, fields, and values
						foreach($copy_array as $table_name => $rows) {
							foreach($rows as $row) {
								foreach($row as $field_name => $field_value) {
									$keys[$table_name][$field_name][] = $field_value;
								}
							}
						}

						//unset the array
						unset($array);

						//use the array to get a copy of the paent data before deleting it
						foreach($copy_array as $table_name => $rows) {
							foreach($rows as $row) {
								$table_name = self::sanitize($table_name);
								$sql = "select * from ".self::TABLE_PREFIX.$table_name." ";
								$i = 0;
								foreach($row as $field_name => $field_value) {
									if ($i == 0) { $sql .= "where "; } else { $sql .= "and "; }
									$sql .= $field_name." in ( ";
									$i = 0;
									foreach($keys[$table_name][$field_name] as $field_value) {
										$field_name = self::sanitize($field_name);
										if ($i > 0) { $sql .= " ,"; }
										$sql .= " :".$field_name."_".$i." ";
										$i++;
									}
									$sql .= ") ";
									$i = 0;
									foreach($keys[$table_name][$field_name] as $field_value) {
										$parameters[$field_name.'_'.$i] = $field_value;
										$i++;
									}
								}
							}

							$results = $this->execute($sql, $parameters, 'all');
							unset($parameters);
							if (is_array($results)) {
								$array[$table_name] = $results;
							}
						}

						//add child data to the old array
						foreach($copy_array as $parent_name => $rows) {
							//get relations array
							$relations = self::get_relations($parent_name);

							//loop through the rows
							$x = 0;
							foreach($rows as $row) {
								if (is_array($relations)) {
									foreach ($relations as $relation) {
										//set the child table
										$child_table = $relation['table'];

										//remove the v_ prefix
										if (substr($child_table, 0, strlen(self::TABLE_PREFIX)) == self::TABLE_PREFIX) {
											$child_table = substr($child_table, strlen(self::TABLE_PREFIX));
										}

										//get the child data
										$sql = "select * from ".self::TABLE_PREFIX.$child_table." ";
										$sql .= "where ".$relation['field']." = :".$relation['field'];
										$parameters[$relation['field']] = $row[$relation['field']];
										$results = $this->execute($sql, $parameters, 'all');
										unset($parameters);
										if (is_array($results)) {
											$array[$parent_name][$x][$child_table] = $results;
										}
									}
								}
								$x++;
							}
						}
					}

				//update the parent and child keys
					$checked = false;
					$x = 0;
					foreach ($array as $parent_name => $tables) {
						if (is_array($tables)) {
							foreach ($tables as $id => $row) {

								//prepare the variables
									$parent_name = self::sanitize($parent_name);
									$parent_key_name = self::singular($parent_name)."_uuid";
									$parent_key_value = uuid();

								//update the parent key id
									$array[$parent_name][$x][$parent_key_name] = $parent_key_value;

								//add copy to the description
									if (isset($array[$parent_name][$x][self::singular($parent_name).'_description'])) {
										$array[$parent_name][$x][self::singular($parent_name).'_description'] = $suffix.$array[$parent_name][$x][self::singular($parent_name).'_description'];
									}

								//loop through the fields
									foreach($row as $field_name => $field_value) {

										//find the child tables
										$y = 0;
										if (is_array($field_value)) {
											//prepare the variables
											$child_name = self::sanitize($field_name);
											$child_key_name = self::singular($child_name)."_uuid";

											//loop through the child rows
											foreach ($field_value as $sub_row) {
												//update the parent key id
												$array[$parent_name][$x][$child_name][$y][$parent_key_name] = $parent_key_value;

												//udpate the child key id
												$array[$parent_name][$x][$child_name][$y][$child_key_name] = uuid();

												//increment the value
												$y++;
											}
										}
									}

								//increment the value
									$x++;

							}
						}
					}

				//save the copy of the data
					if (is_array($array) && count($array) > 0) {
						$retval = $this->save($array);
						unset($array);
					}
					return $retval;
			} //end function copy

			/**
			 * Toggles fields on a table using the <i>toggle_field</i> array values within the app object.
			 * @param array $array Three dimensional Array. The first dimension is the table name without the prefix 'v_'. Second dimension in the row value as int. Third dimension is the column name.
			 * @return bool Returns <b>true</b> on success and <b>false</b> on failure.
			 * @depends database::save()
			 * @depends database::get_apps()
			 */
			public function toggle(array $array) {

				//return the array
					if (!is_array($array)) { return false; }

				//set the message id
					$m = 0;

				//loop through the array
					if (is_array($array)) {
						$x = 0;
						foreach ($array as $parent_name => $tables) {
							if (is_array($tables)) {
								foreach ($tables as $id => $row) {

									//prepare the variables
										$parent_name = self::sanitize($parent_name);
										$parent_key_name = self::singular($parent_name)."_uuid";

									//build the toggle array
										if ($row['checked'] == 'true') {
											//toggle the field value
											//$toggle_array[$parent_name][$x][$parent_key_name] = $row[$parent_key_name];
											$toggle_array[$parent_name][$x] = $row;

											//remove the row from the main array
											unset($array[$parent_name][$x]);
										}

									//loop through the fields
										foreach($row as $field_name => $field_value) {

											//find the child tables
											$y = 0;
											if (is_array($field_value)) {
												//prepare the variables
												$child_name = self::sanitize($field_name);
												$child_key_name = self::singular($child_name)."_uuid";

												//loop through the child rows
												foreach ($field_value as $sub_row) {

													//build the delete array
													if ($action == 'delete' && $sub_row['checked'] == 'true') {
														//delete the child data
														$delete_array[$child_name][$y][$child_key_name] = $sub_row[$child_key_name];

														//remove the row from the main array
														unset($array[$parent_name][$x][$child_name][$y]);
													}

													//increment the value
													$y++;
												}
											}
										}

									//increment the value
										$x++;

								}
							}
						}
					}

					//unset the original array
					unset($array);

					//get the $apps array from the installed apps from the core and mod directories
					if (!is_array($_SESSION['apps'])) {
						self::get_apps();
					}

					//search through all fields to see if toggle field exists
					if (is_array($_SESSION['apps'])) {
						foreach ($_SESSION['apps'] as $x => $app) {
							if (is_array($app['db'])) {
								foreach ($app['db'] as $y => $row) {
									if (is_array($row['table']['name'])) {
										$table_name = $row['table']['name']['text'];
									}
									else {
										$table_name = $row['table']['name'];
									}
									if ($table_name === self::TABLE_PREFIX.$parent_name) {
										if (is_array($row['fields'])) {
											foreach ($row['fields'] as $field) {
												if (isset($field['toggle'])) {
													$toggle_field = $field['name'];
													$toggle_values = $field['toggle'];
												}
											}
										}
									}
								}
							}
						}
					}

					//get the current values from the database
					foreach ($toggle_array as $table_name => $table) {
						$x = 0;
						foreach($table as $row) {
							$child_name = self::sanitize($table_name);
							$child_key_name = self::singular($child_name)."_uuid";

							$array[$table_name][$x][$child_key_name] = $row[$child_key_name];
							$array[$table_name][$x][$toggle_field] = ($row[$toggle_field] === $toggle_values[0]) ? $toggle_values[1] : $toggle_values[0];
							$x++;
						}
					}
					unset($toggle_array);

					//save the array
					return $this->save($array);

			} //end function toggle

			/**
			 * <p>Save an array to the database.</p>
			 * <p>Usage Example:<br><code>$database = new database();<br>$database->app_name = "MyApp";<br>$database->app_uuid = "12345678-1234-1234-1234-123456789abc";<br>$row = 0;<br>$array['mytable'][$row]['mycolumn'] = "myvalue";<br>if ($database->save($array)) { <br>&nbsp;&nbsp;echo "Saved Successfully.";<br> } else {<br>&nbsp;&nbsp;echo "Save Failed.";<br>}</code></p>
			 * @param array $array Three dimensional Array. The first dimension is the table name without the prefix 'v_'. Second dimension in the row value as int. Third dimension is the column name.
			 * @param bool $transaction_save
			 * @return boolean Returns <b>true</b> on success and <b>false</b> on failure of one or more failed write attempts.
			 */
			public function save(array &$array, bool $transaction_save = true) {
				//set default return value
					$retval = true;

				//return the array
					if (!is_array($array)) { return false; }

				//set the message id
					$m = 0;

				//build the json string from the array
					$new_json = json_encode($array, JSON_PRETTY_PRINT);

				//debug sql
					//$this->debug["sql"] = true;

				//connect to the database if needed
					if (!$this->db) {
						$this->connect();
					}

				//start the atomic transaction
					$this->db->beginTransaction();

				//loop through the array
					if (is_array($array)) foreach ($array as $schema_name => $schema_array) {

						$this->name = $schema_name;
						if (is_array($schema_array)) foreach ($schema_array as $schema_id => $array) {

							//set the variables
								$table_name = self::TABLE_PREFIX.$this->name;
								$parent_key_name = self::singular($this->name)."_uuid";
								$parent_key_name = self::sanitize($parent_key_name);

							//if the uuid is set then set parent key exists and value 
								//determine if the parent_key_exists
								$parent_key_exists = false;
								if (isset($array[$parent_key_name])) {
									$parent_key_value = $array[$parent_key_name];
									$parent_key_exists = true;
								}
								else {
									if (isset($this->uuid)) {
										$parent_key_exists = true;
										$parent_key_value = $this->uuid;
									}
									else {
										$parent_key_value = uuid();
									}
								}

							//allow characters found in the uuid only.
								$parent_key_value = self::sanitize($parent_key_value);

							//get the parent field names
								$parent_field_names = array();
								if (is_array($array)) {
									foreach ($array as $key => $value) {
										if (!is_array($value)) {
											$parent_field_names[] = self::sanitize($key);
										}
									}
								}

							//determine action update or delete and get the original data
								if ($parent_key_exists) {
									$sql = "SELECT ".implode(", ", $parent_field_names)." FROM ".$table_name." ";
									$sql .= "WHERE ".$parent_key_name." = '".$parent_key_value."'; ";
									$prep_statement = $this->db->prepare($sql);
									if ($prep_statement) {
										//get the data
											try {
												$prep_statement->execute();
												$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
											}
											catch(PDOException $e) {
												echo $sql."<br />\n";
												echo 'Caught exception: '.  $e->getMessage()."<br /><br />\n";
												echo $sql. "<br /><br />\n";
												exit;
											}

										//set the action
											if (count($result) > 0) {
												$action = "update";
												$old_array[$schema_name] = $result;
											}
											else {
												$action = "add";
											}
									}
									unset($prep_statement, $result);
								}
								else {
									$action = "add";
								}

							//add a record
								if ($action == "add") {

									if (permission_exists(self::singular($this->name).'_add')) {

											$params = array();
											$sql = "INSERT INTO ".self::TABLE_PREFIX.$this->name." ";
											$sql .= "(";
											if (!$parent_key_exists) {
												$sql .= $parent_key_name.", ";
											}
											if (is_array($array)) {
												foreach ($array as $array_key => $array_value) {
													if (!is_array($array_value)) {
														$array_key = self::sanitize($array_key);
														if ($array_key != 'insert_user' &&
															$array_key != 'insert_date' &&
															$array_key != 'update_user' && 
															$array_key != 'update_date') {
															$sql .= $array_key.", ";
														}
													}
												}
											}
											$sql .= "insert_date, ";
											$sql .= "insert_user ";
											$sql .= ") ";
											$sql .= "VALUES ";
											$sql .= "(";
											if (!$parent_key_exists) {
												$sql .= "'".$parent_key_value."', ";
											}
											if (is_array($array)) {
												foreach ($array as $array_key => $array_value) {
													if (!is_array($array_value)) {
														if ($array_key != 'insert_user' &&
															$array_key != 'insert_date' &&
															$array_key != 'update_user' && 
															$array_key != 'update_date') {
															if (strlen($array_value) == 0) {
																$sql .= "null, ";
															}
															elseif ($array_value === "now()") {
																$sql .= "now(), ";
															}
															elseif ($array_value === "user_uuid()") {
																$sql .= ':'.$array_key.", ";
																$params[$array_key] = $_SESSION['user_uuid'];
															}
															elseif ($array_value === "remote_address()") {
																$sql .= ':'.$array_key.", ";
																$params[$array_key] = $_SERVER['REMOTE_ADDR'];
															}
															else {
																$sql .= ':'.$array_key.", ";
																$params[$array_key] = trim($array_value);
															}
														}
													}
												}
											}
											$sql .= "now(), ";
											$sql .= ":insert_user ";
											$sql .= ");";

											//add insert user parameter
											$params['insert_user'] = $_SESSION['user_uuid'];

											//set the error mode
											$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

											//reduce prepared statement latency
											if (defined('PDO::PGSQL_ATTR_DISABLE_PREPARES')) {
												$this->db->setAttribute(PDO::PGSQL_ATTR_DISABLE_PREPARES, true);
											}

											//execute the query and return the results
											try {
												//$this->db->query(check_sql($sql));
												$prep_statement = $this->db->prepare($sql);
												$prep_statement->execute($params);
												unset($prep_statement);
												$message["message"] = "OK";
												$message["code"] = "200";
												$message["uuid"] = $parent_key_value;
												$message["details"][$m]["name"] = $this->name;
												$message["details"][$m]["message"] = "OK";
												$message["details"][$m]["code"] = "200";
												$message["details"][$m]["uuid"] = $parent_key_value;
												if ($this->debug["sql"]) {
													$message["details"][$m]["sql"] = $sql;
													if (is_array($params)) {
														$message["details"][$m]["params"] = $params;
													}
												}
												unset($params);
												$this->message = $message;
												$m++;
											}
											catch(PDOException $e) {
												$retval = false;
												$message["message"] = "Bad Request";
												$message["code"] = "400";
												$message["details"][$m]["name"] = $this->name;
												$message["details"][$m]["message"] = $e->getMessage();
												$message["details"][$m]["code"] = "400";
												$message["details"][$m]["array"] = $array;
												if ($this->debug["sql"]) {
													$message["details"][$m]["sql"] = $sql;
													if (is_array($params)) {
														$message["details"][$m]["params"] = $params;
													}
												}
												unset($params);
												$this->message = $message;
												$m++;
											}
											unset($sql);
									}
									else {
										$retval = false;
										$message["name"] = $this->name;
										$message["message"] = "Forbidden, does not have '".self::singular($this->name)."_add'";
										$message["code"] = "403";
										$message["line"] = __line__;
										$this->message[] = $message;
										$m++;
									}
								}

							//edit a specific uuid
								if ($action == "update") {
									if (permission_exists(self::singular($this->name).'_edit')) {

										//parent data
											$params = array();
											$sql = "UPDATE ".self::TABLE_PREFIX.$this->name." SET ";
											if (is_array($array)) {
												foreach ($array as $array_key => $array_value) {
													if (!is_array($array_value) && $array_key != $parent_key_name) {
														$array_key = self::sanitize($array_key);
														if (strlen($array_value) == 0) {
															$sql .= $array_key." = null, ";
														}
														elseif ($array_value === "now()") {
															$sql .= $array_key." = now(), ";
														}
														elseif ($array_value === "user_uuid()") {
															$sql .= $array_key." = :".$array_key.", ";
															$params[$array_key] = $_SESSION['user_uuid'];
														}
														elseif ($array_value === "remote_address()") {
															$sql .= $array_key." = :".$array_key.", ";
															$params[$array_key] = $_SERVER['REMOTE_ADDR'];
														}
														else {
															$sql .= $array_key." = :".$array_key.", ";
															$params[$array_key] = trim($array_value);
														}
													}
												}
											}

											//add the modified date and user
											$sql .= "update_date = now(), ";
											$sql .= "update_user = :update_user ";
											$params['update_user'] = $_SESSION['user_uuid'];

											//add the where with the parent name and value
											$sql .= "WHERE ".$parent_key_name." = '".$parent_key_value."'; ";
											$sql = str_replace(", WHERE", " WHERE", $sql);

											//add update user parameter
											$params['update_user'] = $_SESSION['user_uuid'];

											//set the error mode
											$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

											//reduce prepared statement latency
											if (defined('PDO::PGSQL_ATTR_DISABLE_PREPARES')) {
												$this->db->setAttribute(PDO::PGSQL_ATTR_DISABLE_PREPARES, true);
											}

											//execute the query and return the results
											try {
												$prep_statement = $this->db->prepare($sql);
												$prep_statement->execute($params);
												//$this->db->query(check_sql($sql));
												$message["message"] = "OK";
												$message["code"] = "200";
												$message["uuid"] = $parent_key_value;
												$message["details"][$m]["name"] = $this->name;
												$message["details"][$m]["message"] = "OK";
												$message["details"][$m]["code"] = "200";
												$message["details"][$m]["uuid"] = $parent_key_value;
												if ($this->debug["sql"]) {
													$message["details"][$m]["sql"] = $sql;
													if (is_array($params)) {
														$message["details"][$m]["params"] = $params;
													}
												}
												unset($params);
												$this->message = $message;
												$m++;
												unset($sql);
											}
											catch(PDOException $e) {
												$retval = false;
												$message["message"] = "Bad Request";
												$message["code"] = "400";
												$message["details"][$m]["name"] = $this->name;
												$message["details"][$m]["message"] = $e->getMessage();
												$message["details"][$m]["code"] = "400";
												if ($this->debug["sql"]) {
													$message["details"][$m]["sql"] = $sql;
													if (is_array($params)) {
														$message["details"][$m]["params"] = $params;
													}
												}
												unset($params);
												$this->message = $message;
												$m++;
											}
									}
									else {
										$retval = false;
										$message["message"] = "Forbidden, does not have '".self::singular($this->name)."_edit'";
										$message["code"] = "403";
										$message["line"] = __line__;
										$this->message = $message;
										$m++;
									}
								}

							//unset the variables
								unset($sql, $action);

							//child data
								if (is_array($array)) {
									foreach ($array as $key => $value) {
										if (is_array($value)) {
												$child_table_name = self::TABLE_PREFIX.$key;
												$child_table_name = self::sanitize($child_table_name);
												foreach ($value as $id => $row) {
													//prepare the variables
														$child_name = self::singular($key);
														$child_name = self::sanitize($child_name);
														$child_key_name = $child_name."_uuid";

													//determine if the parent key exists in the child array
														$parent_key_exists = false;
														if (!isset($array[$parent_key_name])) {
															$parent_key_exists = true;
														}

													//determine if the uuid exists
														$uuid_exists = false;
														if (is_array($row)) foreach ($row as $k => $v) {
															if ($child_key_name == $k) {
																if (strlen($v) > 0) {
																	$child_key_value = trim($v);
																	$uuid_exists = true;
																	break;
																}
															}
															else {
																$uuid_exists = false;
															}
														}

													//allow characters found in the uuid only
														if (isset($child_key_value)) {
															$child_key_value = self::sanitize($child_key_value);
														}

													//get the child field names
														$child_field_names = array();
														if (is_array($row)) {
															foreach ($row as $k => $v) {
																if (!is_array($v) && $k !== 'checked') {
																	$child_field_names[] = self::sanitize($k);
																}
															}
														}

													//determine sql update or delete and get the original data
														if ($uuid_exists) {
															$sql = "SELECT ". implode(", ", $child_field_names)." FROM ".$child_table_name." ";
															$sql .= "WHERE ".$child_key_name." = '".$child_key_value."'; ";
															try {
																$prep_statement = $this->db->prepare($sql);
																if ($prep_statement) {
																	//get the data
																		$prep_statement->execute();
																		$child_array = $prep_statement->fetch(PDO::FETCH_ASSOC);

																	//set the action
																		if (is_array($child_array)) {
																			$action = "update";
																		}
																		else {
																			$action = "add";
																		}

																	//add to the parent array
																		if (is_array($child_array)) {
																			$old_array[$schema_name][$schema_id][$key][] = $child_array;
																		}
																}
																unset($prep_statement);
															}
															catch(PDOException $e) {
																echo $sql."<br />\n";
																echo 'Caught exception: '.  $e->getMessage()."<br /><br />\n";
																echo $sql. "<br /><br />\n";
																exit;
															}

														}
														else {
															$action = "add";
														}

													//update the child data
														if ($action == "update") {
															if (permission_exists($child_name.'_edit')) {
																$sql = "UPDATE ".$child_table_name." SET ";
																if (is_array($row)) {
																	foreach ($row as $k => $v) {
																		if (!is_array($v) && ($k != $parent_key_name || $k != $child_key_name)) {
																			$k = self::sanitize($k);
																			if (strlen($v) == 0) {
																				$sql .= $k." = null, ";
																			}
																			elseif ($v === "now()") {
																				$sql .= $k." = now(), ";
																			}
																			elseif ($v === "user_uuid()") {
																				$sql .= $k." = :".$k.", ";
																				$params[$k] = $_SESSION['user_uuid'];
																			}
																			elseif ($v === "remote_address()") {
																				$sql .= $k." = :".$k.", ";
																				$params[$k] = $_SERVER['REMOTE_ADDR'];
																			}
																			else {
																				$sql .= $k." = :".$k.", ";
																				$params[$k] = trim($v);
																			}
																		}
																	}
																}

																//add the modified date and user
																$sql .= "update_date = now(), ";
																$sql .= "update_user = :update_user ";
																$params['update_user'] = $_SESSION['user_uuid'];

																//add the where with the parent name and value
																$sql .= "WHERE ".$parent_key_name." = '".$parent_key_value."' ";
																$sql .= "AND ".$child_key_name." = '".$child_key_value."'; ";
																$sql = str_replace(", WHERE", " WHERE", $sql);

																//set the error mode
																$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

																//reduce prepared statement latency
																if (defined('PDO::PGSQL_ATTR_DISABLE_PREPARES')) {
																	$this->db->setAttribute(PDO::PGSQL_ATTR_DISABLE_PREPARES, true);
																}

																//$prep_statement->bindParam(':domain_uuid', $this->domain_uuid );
																try {
																	//$this->db->query(check_sql($sql));
																	$prep_statement = $this->db->prepare($sql);
																	$prep_statement->execute($params);
																	unset($prep_statement);
																	$message["details"][$m]["name"] = $key;
																	$message["details"][$m]["message"] = "OK";
																	$message["details"][$m]["code"] = "200";
																	$message["details"][$m]["uuid"] = $child_key_value;
																	if ($this->debug["sql"]) {
																		$message["details"][$m]["sql"] = $sql;
																		if (is_array($params)) {
																			$message["details"][$m]["params"] = $params;
																		}
																	}
																	unset($params);
																	$this->message = $message;
																	$m++;
																}
																catch(PDOException $e) {
																	$retval = false;
																	if ($message["code"] = "200") {
																		$message["message"] = "Bad Request";
																		$message["code"] = "400";
																	}
																	$message["details"][$m]["name"] = $key;
																	$message["details"][$m]["message"] = $e->getMessage();
																	$message["details"][$m]["code"] = "400";
																	if ($this->debug["sql"]) {
																		$message["details"][$m]["sql"] = $sql;
																		if (is_array($params)) {
																			$message["details"][$m]["params"] = $params;
																		}
																	}
																	unset($params);
																	$this->message = $message;
																	$m++;
																}
															}
															else {
																$retval = false;
																$message["name"] = $child_name;
																$message["message"] = "Forbidden, does not have '${child_name}_edit'";
																$message["code"] = "403";
																$message["line"] = __line__;
																$this->message = $message;
																$m++;
															}
														} //action update

												//add the child data
													if ($action == "add") {
														if (permission_exists($child_name.'_add')) {
															//determine if child or parent key exists
															$child_key_name = $child_name.'_uuid';
															$parent_key_exists = false;
															$child_key_exists = false;
															if (is_array($row)) {
																foreach ($row as $k => $v) {
																	if ($k == $parent_key_name) {
																		$parent_key_exists = true; 
																	}
																	if ($k == $child_key_name) {
																		$child_key_exists = true;
																		$child_key_value = trim($v);
																	}
																}
															}
															if (!$child_key_value) {
																$child_key_value = uuid();
															}
															//build the insert
															$sql = "INSERT INTO ".$child_table_name." ";
															$sql .= "(";
															if (!$parent_key_exists) {
																$sql .= self::singular($parent_key_name).", ";
															}
															if (!$child_key_exists) {
																$sql .= self::singular($child_key_name).", ";
															}
															if (is_array($row)) {
																foreach ($row as $k => $v) {
																	if (!is_array($v)) {
																		$k = self::sanitize($k);
																		if ($k != 'insert_user' &&
																		$k != 'insert_date' &&
																		$k != 'update_user' && 
																		$k != 'update_date') {
																			$sql .= $k.", ";
																		}
																	}
																}
															}
															$sql .= "insert_date, ";
															$sql .= "insert_user ";
															$sql .= ") ";
															$sql .= "VALUES ";
															$sql .= "(";
															if (!$parent_key_exists) {
																$sql .= "'".$parent_key_value."', ";
															}
															if (!$child_key_exists) {
																$sql .= "'".$child_key_value."', ";
															}
															if (is_array($row)) {
																foreach ($row as $k => $v) {
																	if (!is_array($v)) {
																		if ($k != 'insert_user' &&
																			$k != 'insert_date' &&
																			$k != 'update_user' && 
																			$k != 'update_date') {
																			if (strlen($v) == 0) {
																				$sql .= "null, ";
																			}
																			elseif ($v === "now()") {
																				$sql .= "now(), ";
																			}
																			elseif ($v === "user_uuid()") {
																				$sql .= ':'.$k.", ";
																				$params[$k] = $_SESSION['user_uuid'];
																			}
																			elseif ($v === "remote_address()") {
																				$sql .= ':'.$k.", ";
																				$params[$k] = $_SERVER['REMOTE_ADDR'];
																			}
																			else {
																				$k = self::sanitize($k);
																				if ($k != 'insert_user' &&
																				$k != 'insert_date' &&
																				$k != 'update_user' && 
																				$k != 'update_date') {
																					$sql .= ':'.$k.", ";
																					$params[$k] = trim($v);
																				}
																			}
																		}
																	}
																}
															}
															$sql .= "now(), ";
															$sql .= ":insert_user ";
															$sql .= ");";

															//add insert user parameter
															$params['insert_user'] = $_SESSION['user_uuid'];

															//set the error mode
															$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

															//reduce prepared statement latency
															if (defined('PDO::PGSQL_ATTR_DISABLE_PREPARES')) {
																$this->db->setAttribute(PDO::PGSQL_ATTR_DISABLE_PREPARES, true);
															}

															//execute the query and return the results
															try {
																$prep_statement = $this->db->prepare($sql);
																$prep_statement->execute($params);
																unset($prep_statement);
																$message["details"][$m]["name"] = $key;
																$message["details"][$m]["message"] = "OK";
																$message["details"][$m]["code"] = "200";
																$message["details"][$m]["uuid"] = $child_key_value;
																if ($this->debug["sql"]) {
																	$message["details"][$m]["sql"] = $sql;
																	if (is_array($params)) {
																		$message["details"][$m]["params"] = $params;
																	}
																}
																unset($params);
																$this->message = $message;
																$m++;
															}
															catch(PDOException $e) {
																$retval = false;
																if ($message["code"] = "200") {
																	$message["message"] = "Bad Request";
																	$message["code"] = "400";
																}
																$message["details"][$m]["name"] = $key;
																$message["details"][$m]["message"] = $e->getMessage();
																$message["details"][$m]["code"] = "400";
																if ($this->debug["sql"]) {
																	$message["details"][$m]["sql"] = $sql;
																	if (is_array($params)) {
																		$message["details"][$m]["params"] = $params;
																	}
																}
																unset($params);
																$this->message = $message;
																$m++;
															}
														}
														else {
															$retval = false;
															$message["name"] = $child_name;
															$message["message"] = "Forbidden, does not have '${child_name}_add'";
															$message["code"] = "403";
															$message["line"] = __line__;
															$this->message = $message;
															$m++;
														}
													} //action add

												//unset the variables
													unset($sql, $action, $child_key_name, $child_key_value);
											} // foreach value

										} //is array
									} //foreach array
								}

						} // foreach schema_array
					} // foreach main array

					$this->message = $message;

				//commit the atomic transaction
					$this->db->commit();

				//set the action if not set
					if (strlen($action) == 0) {
						if (is_array($old_array)) {
							$transaction_type = 'update';
						}
						else {
							$transaction_type = 'add';
						}
					}
					else {
						$transaction_type = $action;
					}

				//get the UUIDs
					$user_uuid = $_SESSION['user_uuid'];

				//log the transaction results
					if ($transaction_save && file_exists($_SERVER["PROJECT_ROOT"]."/app/database_transactions/app_config.php")) {
						try {
							$sql = "insert into ".self::TABLE_PREFIX."database_transactions ";
							$sql .= "(";
							$sql .= "database_transaction_uuid, ";
							$sql .= "domain_uuid, ";
							if (isset($user_uuid) && is_uuid($user_uuid)) {
								$sql .= "user_uuid, ";
							}
							if (isset($this->app_uuid) && is_uuid($this->app_uuid)) {
								$sql .= "app_uuid, ";
							}
							if (isset($this->app_name) && strlen($this->app_name) > 0) {
								$sql .= "app_name, ";
							}
							$sql .= "transaction_code, ";
							$sql .= "transaction_address, ";
							$sql .= "transaction_type, ";
							$sql .= "transaction_date, ";
							$sql .= "transaction_old, ";
							$sql .= "transaction_new, ";
							$sql .= "transaction_result ";
							$sql .= ")";
							$sql .= "values ";
							$sql .= "(";
							$sql .= "'".uuid()."', ";
							if (is_null($this->domain_uuid)) {
								$sql .= "null, ";
							}
							else {
								$sql .= "'".$this->domain_uuid."', ";
							}
							if (isset($user_uuid) && is_uuid($user_uuid)) {
								$sql .= ":user_uuid, ";
							}
							if (isset($this->app_uuid) && is_uuid($this->app_uuid)) {
								$sql .= ":app_uuid, ";
							}
							if (isset($this->app_name) && strlen($this->app_name) > 0) {
								$sql .= ":app_name, ";
							}
							$sql .= "'".$message["code"]."', ";
							$sql .= ":remote_address, ";
							$sql .= "'".$transaction_type."', ";
							$sql .= "now(), ";
							if (is_array($old_array)) {
								$sql .= ":transaction_old, ";
							}
							else {
								$sql .= "null, ";
							}
							if (is_array($array)) {
								$sql .= ":transaction_new, ";
							}
							else {
								$sql .= "null, ";
							}
							$sql .= ":transaction_result ";
							$sql .= ")";
							$statement = $this->db->prepare($sql);
							if (isset($user_uuid) && is_uuid($user_uuid)) {
								$statement->bindParam(':user_uuid', $user_uuid);
							}
							if (isset($this->app_uuid) && is_uuid($this->app_uuid)) {
								$statement->bindParam(':app_uuid', $this->app_uuid);
							}
							if (isset($this->app_name) && strlen($this->app_name) > 0) {
								$statement->bindParam(':app_name', $this->app_name);
							}
							$statement->bindParam(':remote_address', $_SERVER['REMOTE_ADDR']);
							if (is_array($old_array)) {
								$old_json = json_encode($old_array, JSON_PRETTY_PRINT);
								$statement->bindParam(':transaction_old', $old_json);
							}
							if (isset($new_json)) {
								$statement->bindParam(':transaction_new', $new_json);
							}
							$message = json_encode($this->message, JSON_PRETTY_PRINT);
							$statement->bindParam(':transaction_result', $message);
							$statement->execute();
							unset($sql);
						}
						catch(PDOException $e) {
							echo $e->getMessage();
							exit;
						}
					}
					return $retval;
			} //save method

			/**
			 * Converts a plural English word to singular.
			 * @param string $word English word
			 * @return string Singular version of English word
			 * @internal Moved to class to conserve resources.
			 */
			public static function singular(string $word) {
				//"-es" is used for words that end in "-x", "-s", "-z", "-sh", "-ch" in which case you add
				if (substr($word, -2) == "es") {
					if (substr($word, -4) == "sses") { // eg. 'addresses' to 'address'
						return substr($word,0,-2);
					}
					elseif (substr($word, -3) == "ses") { // eg. 'databases' to 'database' (necessary!)
						return substr($word,0,-1);
					}
					elseif (substr($word, -3) == "ies") { // eg. 'countries' to 'country'
						return substr($word,0,-3)."y";
					}
					elseif (substr($word, -3, 1) == "x") {
						return substr($word,0,-2);
					}
					elseif (substr($word, -3, 1) == "s") {
						return substr($word,0,-2);
					}
					elseif (substr($word, -3, 1) == "z") {
						return substr($word,0,-2);
					}
					elseif (substr($word, -4, 2) == "sh") {
						return substr($word,0,-2);
					}
					elseif (substr($word, -4, 2) == "ch") {
						return substr($word,0,-2);
					}
					else {
						return rtrim($word, "s");
					}
				}
				else {
					return rtrim($word, "s");
				}
			}

			/**
			 * Gets the $apps array from the installed apps from the core and mod directories and writes it to $_SESSION['apps'] overwriting previous values.
			 * @uses $_SERVER['DOCUMENT_ROOT'] Global variable
			 * @uses PROJECT_PATH Global variable
			 * @return null Does not return any values
			 * @internal Moved to class to conserve resources.
			 */
			public static function get_apps() {
				//get the $apps array from the installed apps from the core and mod directories
					$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/*/*/app_config.php");
					$x = 0;
					if (is_array($config_list)) {
						foreach ($config_list as &$config_path) {
							include($config_path);
							$x++;
						}
					}
					$_SESSION['apps'] = $apps;
			}

			/**
			 * Returns the depth of an array
			 * @param array $array Reference to array
			 * @return int Depth of array
			 * @internal Moved to class to conserve resources.
			 */
			public static function array_depth(array &$array) {
				$depth = 0;
				if (is_array($array)) {
					$depth++;
					foreach ($array as $value) {
						if (is_array($value)) {
							$depth = self::array_depth($value) + 1;
						}
					}
				}
				return $depth;
			}

			/**
			 * Searches through all fields to see if domain_uuid exists
			 * @param string $name
			 * @uses $_SESSION['apps'] directly
			 * @return boolean <b>true</b> on success and <b>false</b> on failure
			 * @see database::get_apps()
			 */
			public static function domain_uuid_exists($name) {
				//get the $apps array from the installed apps from the core and mod directories
					if (!is_array($_SESSION['apps'])) {
						self::get_apps();
					}

				//search through all fields to see if domain_uuid exists
					$apps = $_SESSION['apps'];
					if (is_array($apps)) {
						foreach ($apps as $x => &$app) {
							if (is_array($app['db'])) {
								foreach ($app['db'] as $y => &$row) {
									if (is_array($row['table']['name'])) {
										$table_name = $row['table']['name']['text'];
									}
									else {
										$table_name = $row['table']['name'];
									}
									if ($table_name === self::TABLE_PREFIX.$name) {
										if (is_array($row['fields'])) {
											foreach ($row['fields'] as $field) {
												if ($field['name'] == "domain_uuid") {
													return true;
												}
											} //foreach
										} //is array
									}
								} //foreach
							} //is array
						} //foreach
					} //is array

				//not found
					return false;
			}

			/**
			 * Get Relations searches through all fields to find relations
			 * @param string $schema Table name
			 * @return array Returns array or false
			 * @internal Moved to class to conserve resources.
			 */
			public static function get_relations($schema) {

				//remove the v_ prefix
					if (substr($schema, 0, strlen(self::TABLE_PREFIX)) == self::TABLE_PREFIX) {
						$schema = substr($schema, strlen(self::TABLE_PREFIX));
					}

				//sanitize the values
					$schema = self::sanitize($schema);

				//get the apps array
					$config_list = glob($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH . "/{core,app}/{".$schema.",".self::singular($schema)."}/app_config.php", GLOB_BRACE);
					foreach ($config_list as &$config_path) {
						include($config_path);
					}

				//search through all fields to find relations
					if (is_array($apps)) {
						foreach ($apps as $x => &$app) {
							foreach ($app['db'] as $y => &$row) {
								foreach ($row['fields'] as $z => $field) {
									if ($field['deprecated'] != "true") {
										if ($field['key']['type'] == "foreign") {
											if ($row['table']['name'] == self::TABLE_PREFIX.$schema || $field['key']['reference']['table'] == self::TABLE_PREFIX.$schema) {
												//get the field name
													if (is_array($field['name'])) {
														$field_name = trim($field['name']['text']);
													}
													else {
														$field_name = trim($field['name']);
													}
												//build the array
													$relations[$i]['table'] = $row['table']['name'];
													$relations[$i]['field'] = $field_name;
													$relations[$i]['key']['type'] = $field['key']['type'];
													$relations[$i]['key']['table'] = $field['key']['reference']['table'];
													$relations[$i]['key']['field'] = $field['key']['reference']['field'];
													if (isset($field['key']['reference']['action'])) {
														$relations[$i]['key']['action'] = $field['key']['reference']['action'];
													}
												//increment the value
													$i++;
											}
										}
									}
									unset($field_name);
								}
							}
						}
					}

				//return the array
					if (is_array($relations)) {
						return $relations;
					} else {
						return false;
					}
			}

		/**
		 * Returns a sanitized string value safe for database or table name.
		 * @param string $value To be sanitized
		 * @return string Sanitized using preg_replace('#[^a-zA-Z0-9_\-]#', '')
		 * @see preg_replace()
		 */
		public static function sanitize(string $value) {
			return preg_replace('#[^a-zA-Z0-9_\-]#', '', $value);
		}

		/**
		 * Returns a new connected database object.<br>
		 * <p>This allows a shortcut for a common syntax. For more information
		 * on how the connection happens see {@link database::__construct()} and
		 * {@link database::connect()}</p>
		 * <p><b>Usage:</b><br>
		 * <code>&nbsp; $database_object = database::new();</code></p>
		 * @return database new instance of database object already connected
		 * @see database::__construct()
		 * @see database::connect()
		 */
		public static function new() {
			$db = new database();
			$db->connect();
			return $db;
		}

		} //class database
	} //!class_exists

//addtitional functions for sqlite
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

/*
//example usage
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
		$database->table = "v_ivr_menus";
		$fields[0]['name'] = 'domain_uuid';
		$fields[0]['value'] = $_SESSION["domain_uuid"];
		echo $database->count();
*/

?>
