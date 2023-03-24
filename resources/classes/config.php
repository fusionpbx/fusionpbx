<?php

/**
 * config
 *
 * @method get config.php
 * @method find find the path to the config.php file
 * @method exists determin if the the config.php file exists
 */
class config {

	/**
	 * database variables and config path
	 */
	public $db_type;
	public $db_name;
	public $db_username;
	public $db_password;
	public $db_host;
	public $db_path;
	public $db_port;
	public $db_secure;
	public $db_cert_authority;
	public $config_path;

	/**
	 * Called when the object is created
	 */
	public function __construct() {
		//place holder
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
	 * Determine whether the config.php exists
	 * @var string $db_type - type of database
	 * @var string $db_name - name of the database
	 * @var string $db_username - username to access the database
	 * @var string $db_password - password to access the database
	 * @var string $db_host - hostname of the database server
	 * @var string $db_path - path of the database file
	 * @var string $db_port - network port to connect to the database
	 * @var bool $db_secure - whether or not to connect with SSL
	 * @var string $db_cert_authority - location of certificate authority
	 */
	public function get() {
		$this->find();
		if ($this->exists()) {
			require $this->config_path;
			$this->db_type = $db_type;
			$this->db_name = $db_name;
			$this->db_username = $db_username;
			$this->db_password = $db_password;
			$this->db_secure = $db_secure;
			$this->db_cert_authority = $db_cert_authority;
			$this->db_host = $db_host;
			$this->db_path = $db_path;
			$this->db_port = $db_port;
		}
	}

	/**
	 * Find the path to the config.php
	 * @var string $config_path - full path to the config.php file
	 */
	public function find() {
		//set the include path
			$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
			set_include_path(parse_ini_file($conf[0])['document.root']);

		//includes files
			require_once "resources/require.php";
		// find the file
			if (file_exists($_SERVER["PROJECT_ROOT"]."/resources/config.php")) {
				$this->config_path = $_SERVER["PROJECT_ROOT"]."/resources/config.php";
			} elseif (file_exists("/etc/fusionpbx/config.php")) {
				$this->config_path = "/etc/fusionpbx/config.php";
			} elseif (file_exists("/usr/local/etc/fusionpbx/config.php")) {
				$this->config_path = "/usr/local/etc/fusionpbx/config.php";
			}
			else {
				$this->config_path = '';
			}
		//return the path
			return $this->config_path;
	}

	/**
	 * Determine whether the config.php exists
	 */
	public function exists() {
		$this->find();
		if (strlen($this->config_path) > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
       public function dsn($index = 0, $odbc = false){
                $this->get();
                $conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
                $data = parse_ini_file($conf[0]);
                $string = '';
                if ($odbc == false){
                        switch ($data['database.'.$index.'.type']){
                                case 'pgsql':
                                        $string = 'pgsql://';
                                        if (array_key_exists('database.'.$index.'.host', $data)){
                                                $string .= 'host='.$data['database.'.$index.'.host'].' ';
                                        }
                                        if (array_key_exists('database.'.$index.'.hostaddr', $data)){
                                                $string .= 'hostaddr='.$data['database.'.$index.'.hostaddr'].' ';
                                        }
                                        $string .= 'port='.$data['database.'.$index.'.port'].' ';
                                        if (array_key_exists('database.'.$index.'.sslmode', $data)){
                                                $string .= 'sslmode='.$data['database.'.$index.'.sslmode'].' ';
                                        }
                                        $string .= 'dbname='.$data['database.'.$index.'.name'].' ';
                                        $string .= 'user='.$data['database.'.$index.'.username'].' ';
                                        $string .= 'password='.$data['database.'.$index.'.password'].' ';
                                        break;
                                case 'mysql':
                                        $string = 'mariadb://';
                                        if (array_key_exists('database.'.$index.'.host', $data)){
                                                $string .= 'Server='.$data['database.'.$index.'.host'].';';
                                        }
                                        if (array_key_exists('database.'.$index.'.hostaddr', $data)){
                                                $string .= 'Server='.$data['database.'.$index.'.hostaddr'].';';
                                        }
                                        $string .= 'Port='.$data['database.'.$index.'.port'].';';
                                        $string .= 'Database='.$data['database.'.$index.'.name'].';';
                                        $string .= 'Uid='.$data['database.'.$index.'.username'].';';
                                        $string .= 'Pwd='.$data['database.'.$index.'.password'];
                                        break;
                                case 'sqlite':
                                        $string = 'sqlite://'.$data['database.'.$index.'.path'].'/'.$data['database.'.$index.'.name'].' ';
                                        brea;
                        }
                }
                else{
                        $string = $data['database.'.$index.'.name'].':'.$data['database.'.$index.'.username'].':'.$data['database.'.$index.'.password'];
                }
                return $string;
        }
}
/*
$config = new config;
$config_exists = $config->exists();
$config_path = $config->find();
$config->get();
$db_type = $config->db_type;
$db_name = $config->db_name;
$db_username = $config->db_username;
$db_password = $config->db_password;
$db_host = $config->db_host;
$db_path = $config->db_path;
$db_port = $config->db_port;
echo "config_path: ".$config_path."\n";
if ($config_exists) {
	echo "config_exists: true\n";
} else {
	echo "config_exists: false\n";
}
echo "db_type: ".$db_type."\n";
echo "db_name: ".$db_name."\n";
echo "db_username: ".$db_username."\n";
echo "db_password: ".$db_password."\n";
echo "db_host: ".$db_host."\n";
echo "db_path: ".$db_path."\n";
echo "db_port: ".$db_port."\n";
*/

?>
