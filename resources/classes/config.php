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
	public $db_sslmode;
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
	 * Determine whether the config file exists
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
		//find the config_path
		$config_path = $this->find();

		//add the document root to the include path
		$conf = parse_ini_file($config_path);
		set_include_path($conf['document.root']);

		//check if the config file exists
		$config_exists = file_exists($config_path) ? true : false;
		
		//set the server variables and define project path constant
		$_SERVER["DOCUMENT_ROOT"] = $conf['document.root'];
		$_SERVER["PROJECT_ROOT"] = $conf['document.root'];
		$_SERVER["PROJECT_PATH"]  = $conf['project.path'];
		if (isset($conf['project.path'])) {
			$_SERVER["PROJECT_ROOT"] = $conf['document.root'].'/'.$conf['project.path'];
			if (!defined('PROJECT_ROOT')) { define("PROJECT_ROOT", $conf['document.root'].'/'.$conf['project.path']); }
			if (!defined('PROJECT_PATH')) { define("PROJECT_PATH", $conf['project.path']); }
		}
		else {
			if (!defined('PROJECT_ROOT')) { define("PROJECT_ROOT", $conf['document.root']); }
			if (!defined('PROJECT_PATH')) { define("PROJECT_PATH", ''); }
		}

		//add the database settings
		$this->db_type = $conf['database.0.type'];
		$this->db_name = $conf['database.0.name'];
		$this->db_username = $conf['database.0.username'];
		$this->db_password = $conf['database.0.password'];
		$this->db_sslmode = $conf['database.0.sslmode'] ?? '';
		$this->db_secure = $conf['database.0.secure'] ?? '';
		$this->db_cert_authority = $conf['database.0.db_cert_authority'] ?? '';
		$this->db_host = $conf['database.0.host'];
		$this->db_path = $conf['database.0.path'] ?? '';
		$this->db_port = $conf['database.0.port'];

	}

	/**
	 * Find the path to the config.php
	 * @var string $config_path - full path to the config.php file
	 */
	public function find() {

		//find the file
			if (file_exists("/etc/fusionpbx/config.conf")) {
				$this->config_path = "/etc/fusionpbx/config.conf";
			}
			elseif (file_exists("/usr/local/etc/fusionpbx/config.conf")) {
				$this->config_path = "/usr/local/etc/fusionpbx/config.conf";
			}
			elseif (file_exists($_SERVER["PROJECT_ROOT"]."/resources/config.php")) {
				$this->config_path = $_SERVER["PROJECT_ROOT"]."/resources/config.php";
			}
			elseif (file_exists("/etc/fusionpbx/config.php")) {
				$this->config_path = "/etc/fusionpbx/config.php";
			}
			elseif (file_exists("/usr/local/etc/fusionpbx/config.php")) {
				$this->config_path = "/usr/local/etc/fusionpbx/config.php";
			}
			else {
				$this->config_path = '';
			}

		//return the path
			return $this->config_path;
	}

	/**
	 * Determine whether the config file exists
	 */
	public function exists() {
		$this->find();
		if (!empty($this->config_path)) {
			return true;
		}
		else {
			return false;
		}
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
