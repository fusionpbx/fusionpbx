<?php

/**
 * config class loads configuration from the filesystem
 */
class config {

	// Full path and filename of config.conf
	private $file;

	// The internal array that holds the configuration in the config.conf file
	private $configuration;

	/**
	 * Loads the framework configuration file
	 */
	public function __construct() {

		//initialize object variables to empty values
		$this->configuration = [];
		$this->file = '';

		//locate the conf file
		$this->find();

		//check if the config file exists
		if (!$this->exists()) {
			//unable to load config.conf so throw an exception
			throw new Exception("Unable to find config path");
		}

		//load the conf file
		$this->load();

		//set the server variables
		$this->define_project_paths();
	}

	/**
	 * Implement backwards compatibility for db_* configuration values
	 * @param string $property
	 * @return mixed
	 */
	public function __get(string $property) {
		switch($property) {
			case 'db_type':
				return $this->get('database.0.type');
			case 'db_host':
				return $this->get('database.0.host');
			case 'db_port':
				return $this->get('database.0.port');
			case 'db_name':
				return $this->get('database.0.name');
			case 'db_username':
				return $this->get('database.0.username');
			case 'db_password':
				return $this->get('database.0.password');
			case 'config_path':
				return $this->path();
		}
	}

	/**
	 * Returns the string representation of the configuration file
	 * @return string configuration
	 */
	public function __toString(): string {
		$sb = "";
		foreach ($this->configuration as $key => $value) {
			$sb .= "$key = '$value'\n";
		}
		return $sb;
	}

	// loads the config.conf file
	private function load() {

		//use native php parsing function
		$conf = parse_ini_file($this->file);
		//save the loaded and parsed conf file to the object
		$this->configuration = $conf;

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
	 * Determine whether the config file exists
	 */
	public function exists() {
		if (!empty($this->file)) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Returns true if the configuration was found within the same directory structure as the framework or false
	 * if the configuration file was found outside of the framework directory structure
	 * @return bool true if the configuration was found in the framework false otherwise
	 */
	public function exists_in_framework(): bool {
		return $this->within_framework;
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

// use current style configuration options
echo "database.0.type: " . $config->get('database.0.type') . "\n";

// use a default value
echo "admin.name: " . $config->get('admin.name', 'admin') . "\n";

// get all configuration options by printing the object
echo "config settings: " . $config . "\n";

// get all configuration options as an array
var_dump($config->configuration());

//*/

?>
