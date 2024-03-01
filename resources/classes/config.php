<?php

/**
 * config class loads configuration from the filesystem
 */
final class config {

	// Full path and filename of config.conf
	private $file;

	// The internal array that holds the configuration in the config.conf file
	private $configuration;
	public static $config = null;

	/**
	 * Loads the framework configuration file
	 */
	private function __construct() {

		//initialize object variables to empty values
		$this->configuration = [];
		$this->file = '';

		//locate the conf file
		$this->find();

		//check if the config file was found
		if (empty($this->file)) {
			//unable to load config.conf so throw an exception
			throw new config_file_not_found();
		}

		//load the conf file
		$this->_load();

		//set the server variables
		$this->define_project_paths();
	}

	/**
	 * Implement backwards compatibility for previously public properties
	 * @param string $property
	 * @return mixed
	 */
	public function __get(string $property) {
		switch($property) {
			case 'db_type':
				return $this->get('database.0.type');
			case 'db_path':
				return $this->get('database.0.path', '');
			case 'db_host':
				return $this->get('database.0.host');
			case 'db_port':
				return $this->get('database.0.port');
			case 'db_name':
				return $this->get('database.0.name');
			case 'db_sslmode':
				return $this->get('database.0.sslmode', 'prefer');
			case 'db_cert_authority':
				return $this->get('database.0.cert_authority', '');
			case 'db_secure':
				return $this->get('database.0.secure', 'false');
			case 'db_username':
				return $this->get('database.0.username');
			case 'db_password':
				return $this->get('database.0.password');
			case 'config_path':
				return $this->path();
			default:
				if (property_exists($this, $property)) {
					return $this->{$property};
				} else {
					throw new InvalidArgumentException("Property does not exist");
				}
		}
	}

	/**
	 * Returns the string representation of the configuration file
	 * @return string configuration
	 */
	public function __toString(): string {
		$string_builder = "";
		foreach ($this->configuration as $key => $value) {
			$string_builder .= "$key = '$value'\n";
		}
		return $string_builder;
	}

	// loads the config.conf file
	private function _load() {

		//check if include is needed
		if (substr($this->file, 0, -4) === '.php') {
			//allow global variables to be set in the old config.php file
			global $db_type, $db_host, $db_port, $db_name, $db_username, $db_password, $db_path;
			global $db_sslmode, $db_secure, $db_cert_authority;

			//load the config.php file
			require_once $this->file;

			//convert the old properties to the new standard
			if (isset($db_type)) {
				$this->configuration['database.0.type'] = $db_type;
			} else {
				$this->configuration['database.0.type'] = 'pgsql';
			}
			if (isset($db_path)) {
				$this->configuration['database.0.path'] = $db_path;
			} else {
				$this->configuration['database.0.path'] = '';
			}
			if (isset($db_host)) {
				$this->configuration['database.0.host'] = $db_host;
			}
			if (isset($db_port)) {
				$this->configuration['database.0.port'] = $db_port;
			}
			if (isset($db_name)) {
				$this->configuration['database.0.name'] = $db_name;
			}
			if (isset($db_username)) {
				$this->configuration['database.0.username'] = $db_username;
			}
			if (isset($db_password)) {
				$this->configuration['database.0.password'] = $db_password;
			}
			if (isset($db_sslmode)) {
				$this->configuration['database.0.sslmode'] = $db_sslmode;
			} else {
				$this->configuration['database.0.sslmode'] = 'prefer';
			}
			if (isset($db_secure)) {
				$this->configuration['database.0.secure'] = $db_secure;
			}
			if (isset($db_cert_authority)) {
				$this->configuration['database.0.cert_authority'] = $db_cert_authority;
			}

			//remove from the global namespace
			unset($db_type, $db_host, $db_port, $db_name, $db_username, $db_password, $db_sslmode, $db_secure, $db_cert_authority);

		} else {
			//use native php parsing function
			$conf_arr = parse_ini_file($this->file);

			//save the loaded and parsed conf file to the object
			$this->configuration = $conf_arr;
		}

	}

	// set project paths if not already defined
	private function define_project_paths() {
		// Load the document root
		$doc_root = $this->get('document.root', '/var/www/fusionpbx');
		$doc_path = $this->get('document.path', '');
		//set the server variables and define project path constant
		if (!empty($doc_path)) {
			if (!defined('PROJECT_PATH')) { define("PROJECT_PATH", $doc_path); }
			if (!defined('PROJECT_ROOT')) { define("PROJECT_ROOT", $doc_root.'/'.$doc_path); }
		}
		else {
			if (!defined('PROJECT_PATH')) { define("PROJECT_PATH", ''); }
			if (!defined('PROJECT_ROOT')) { define("PROJECT_ROOT", $doc_root); }
		}

		// internal definitions to the framework
		$_SERVER["PROJECT_PATH"] = PROJECT_PATH;
		$_SERVER["PROJECT_ROOT"] = PROJECT_ROOT;

		// tell php where the framework is
		$_SERVER["DOCUMENT_ROOT"] = PROJECT_ROOT;

		// have php search for any libraries in the now defined root
		set_include_path(PROJECT_ROOT);
	}

	/**
	 * Find the path to the config.conf file
	 * @var string $config_path - full path to the config.php file
	 */
	private function find() {
		//find the file
		if (file_exists("/etc/fusionpbx/config.conf")) {
			$this->file = "/etc/fusionpbx/config.conf";
		}
		elseif (file_exists("/usr/local/etc/fusionpbx/config.conf")) {
			$this->file = "/usr/local/etc/fusionpbx/config.conf";
		}
		elseif (file_exists("/etc/fusionpbx/config.php")) {
			$this->file = "/etc/fusionpbx/config.php";
		}
		elseif (file_exists("/usr/local/etc/fusionpbx/config.php")) {
			$this->file = "/usr/local/etc/fusionpbx/config.php";
		}
		// use the current web directory to find it as a last resort
		elseif (file_exists(dirname(__DIR__, 2) . "/resources/config.php")) {
			$this->file = "/var/www/fusionpbx/resources/config.php";
		}
	}

	/**
	 * Get a configuration value using a key in the configuration file
	 * @param string $key key to match
	 * @param string $default_value if no matching key is found, then this value will be returned
	 * @return string returns a value in the conf file or an empty string
	 */
	public function get(string $key, string $default_value = ''): string {
		if (array_key_exists($key, $this->configuration)) {
			return $this->configuration[$key];
		}
		return $default_value;
	}

	/**
	 * Returns the config path or an empty string
	 * @return string
	 */
	public function path(): string {
		return dirname($this->file);
	}

	/**
	 * Returns the file name only of the configuration file
	 * @return string
	 */
	public function filename(): string {
		return basename($this->file);
	}

	/**
	 * Returns the path and the file name
	 * @return string
	 */
	public function path_and_filename(): string {
		return $this->path() . '/' . $this->filename();
	}

	/**
	 * Returns the array of configuration settings
	 * @return array
	 */
	public function configuration(): array {
		return $this->configuration;
	}

	/**
	 * Ensures the configuration file is loaded only once
	 * @return config
	 */
	public static function load(): config {
		if (self::$config === null) {
			self::$config = new config();
		}
		return self::$config;
	}
}

/*
//Examples:
//~~~~~~~~
$config = new config;
echo "Config path: " . $config->path() . "\n";
echo "Config file: " . $config->filename() . "\n";
echo "Full path and filename: " . $config->path_and_filename() . "\n";

// show old style configuration options
echo "db_type: ".$config->db_type."\n";
echo "db_name: ".$config->db_name."\n";
echo "db_username: ".$config->db_username."\n";
echo "db_password: ".$config->db_password."\n";
echo "db_host: ".$config->db_host."\n";
echo "db_path: ".$config->db_path."\n";
echo "db_port: ".$config->db_port."\n";

// use current style configuration options even on old config.php
echo "database.0.type: " . $config->get('database.0.type') . "\n";

// use a default value
echo "admin.name: " . $config->get('admin.name', 'admin') . "\n";

// get all configuration options by printing the object
echo "config settings: " . $config . "\n";

// get all configuration options as an array
var_dump($config->configuration());

//*/

?>
