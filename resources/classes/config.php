<?php

	/**
	 * config class to manage the configuration file
	 * @author Mark J Crane <markjcrane@fusionpbx.com>
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
		private $paths_to_check;

		/**
		 * Called when the object is created
		 */
		public function __construct(array $paths_to_check = []) {
			if (empty($paths_to_check)) {
				$this->paths_to_check = [
					'/etc/fusionpbx/config.conf',
					'/usr/local/etc/fusionpbx/config.conf',
					$_SERVER['PROJECT_ROOT'] . '/resources/config.php',
					'/etc/fusionpbx/config.php',
					'/usr/local/etc/fusionpbx/config.php',
				];
			}
			$this->find();
		}

		/**
		 * Alias of load
		 */
		public function get() {
			return $this->load();
		}

		/**
		 * Load the configuration in to global scoped variables
		 * @var string $db_type - type of database
		 * @var string $db_name - name of the database
		 * @var string $db_username - username to access the database
		 * @var string $db_password - password to access the database
		 * @var string $db_host - hostname of the database server
		 * @var string $db_path - path of the database file
		 * @var string $db_port - network port to connect to the database
		 * @var bool $db_secure - whether or not to connect with SSL
		 * @var string $db_cert_authority - location of certificate authority
		 * @global string[] $conf
		 */
		public function load() {
			if (!$this->exists) {
				return;
			}
			//find the config_path
			$config_path = $this->config_path;

			//set the scope of $conf
			global $conf;

			//add the document root to the include path
			$conf = parse_ini_file($config_path);
			set_include_path($conf['document.root']);

			//add the database settings
			$this->db_type = $conf['database.0.type'] ?? '';
			$this->db_name = $conf['database.0.name'] ?? '';
			$this->db_username = $conf['database.0.username'] ?? '';
			$this->db_password = $conf['database.0.password'] ?? '';
			$this->db_sslmode = $conf['database.0.sslmode'] ?? '';
			$this->db_secure = $conf['database.0.secure'] ?? '';
			$this->db_cert_authority = $conf['database.0.db_cert_authority'] ?? '';
			$this->db_host = $conf['database.0.host'] ?? '';
			$this->db_path = $conf['database.0.path'] ?? '';
			$this->db_port = $conf['database.0.port'] ?? '';

			//set project paths from global $conf
			$this->set_project_paths();
		}

		/**
		 * Set the project paths from global $conf
		 * @global string $conf
		 */
		public function set_project_paths() {
			global $conf;
			//set the server variables and define project path constant
			$_SERVER["DOCUMENT_ROOT"] = $conf['document.root'] ?? '/var/www/fusionpbx';
			$_SERVER["PROJECT_ROOT"] = $conf['document.root'] ?? '/var/www/fusionpbx';
			$_SERVER["PROJECT_PATH"] = $conf['project.path'];
			if (isset($conf['project.path'])) {
				$_SERVER["PROJECT_ROOT"] = $conf['document.root'] . '/' . $conf['project.path'];
				if (!defined('PROJECT_ROOT')) {
					define("PROJECT_ROOT", $conf['document.root'] . '/' . $conf['project.path']);
				}
				if (!defined('PROJECT_PATH')) {
					define("PROJECT_PATH", $conf['project.path']);
				}
			} else {
				if (!defined('PROJECT_ROOT')) {
					define("PROJECT_ROOT", $conf['document.root']);
				}
				if (!defined('PROJECT_PATH')) {
					define("PROJECT_PATH", '');
				}
			}
		}

		/**
		 * Find the path to the config.php
		 * @var string $config_path - full path to the config.php file
		 * @return string Path of the config file
		 */
		public function find() {
			$this->config_path = '';

			foreach ($this->paths_to_check as $path) {
				if (file_exists($path)) {
					$this->config_path = $path;
					break;
				}
			}

			//return the path
			return $this->config_path;
		}

		/**
		 * Returns whether the config file exists
		 * @see file_exists()
		 */
		public function exists() {
			return file_exists($this->config_path);
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
