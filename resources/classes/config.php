<?php

	/**
	 * config class to manage the configuration file
	 * @author Mark J Crane <markjcrane@fusionpbx.com>
	 */
	class config {

		/**
		 * @var string full path and file name of the config.conf
		 */
		private $file;

		/**
		 * @var array list of filesystem paths to check for the config.conf and the older config.php
		 */
		private $paths_to_check;

		/**
		 * Allows syncing the conf global database setting array to this object
		 * @global string $conf
		 * @param type $name
		 * @return string
		 */
		public function __get($name) {
			global $conf;
			if (isset($conf[$name])) {
				return $conf[$name];
			}
			if (substr($name, 0, 3) === 'db_') {
				return $conf['database.0.' . substr($name, 4)] ?? '';
			}
			if ($name === 'config_path') {
				return $this->file;
			}
			return "";
		}

		/**
		 * Called when the object is created
		 */
		public function __construct(array $paths_to_check = []) {
			if (empty($paths_to_check)) {
				$this->paths_to_check = [
					'/etc/fusionpbx/config.conf',
					'/usr/local/etc/fusionpbx/config.conf',
					($_SERVER['PROJECT_ROOT'] ?? '/var/www/fusionpbx') . '/resources/config.php',
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
		 * Returns the value of the setting cached in memory
		 * @global array $conf
		 * @param string $setting
		 * @return string
		 */
		public function value(string $setting, string $default = ''): string {
			global $conf;
			if (array_key_exists($setting, $conf)) {
				return $conf[$setting];
			}
			return $default;
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
			if (!$this->exists()) {
				return;
			}

			//set the scope of $conf
			global $conf;

			//use the global variable to store the copy of the config
			$conf = parse_ini_file($this->file);

			//set project paths from global $conf
			$this->set_project_paths();

			//add the document root to the include path
			set_include_path(PROJECT_ROOT);

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
			$this->file = '';

			foreach ($this->paths_to_check as $path) {
				if (file_exists($path)) {
					$this->file = $path;
					break;
				}
			}

			//return the path
			return $this->file;
		}

		/**
		 * Returns whether the config file exists
		 * @see file_exists()
		 */
		public function exists() {
			return file_exists($this->file);
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
