<?php

/**
 * destinations
 *
 * @method get config.php
 * @method exists check to see if the config.php exists
 */
class config {

	/**
	 * destinations array
	 */
	public $db_type;
	public $db_name;
	public $db_username;
	public $db_password;
	public $db_host;
	public $db_path;
	public $db_port;

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
	 */
	public function get() {
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/resources/config.php")) {
			include($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/resources/config.php");
		} elseif (file_exists("/etc/fusionpbx/config.php")) {
			include("/etc/fusionpbx/config.php");
		} elseif (file_exists("/usr/local/etc/fusionpbx/config.php")) {
			include("/usr/local/etc/fusionpbx/config.php");
		}
		$this->db_type = $db_type;
		$this->db_name = $db_name;
		$this->db_username = $db_username;
		$this->db_password = $db_password;
		$this->db_host = $db_host;
		$this->db_path = $db_path;
		$this->db_port = $db_port;
	}

	/**
	 * Determine whether the config.php exists
	 */
	public function exists() {
		if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/resources/config.php")) {
			return true;
		} elseif (file_exists("/etc/fusionpbx/config.php")) {
			return true;
		} elseif (file_exists("/usr/local/etc/fusionpbx/config.php")) {
			return true;
		}
		else {
			return false;
		}
	}
}
/*
$config = new config;
$config = $config->get;
$config_exists = $config->exists();
*/

?>