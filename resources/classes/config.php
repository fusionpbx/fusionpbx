<?php

/**
 * config class loads configuration from the filesystem
 */
class config {

	// Full path and filename of config.conf
	private $file;

	// The internal array that holds the configuration in the config.conf file
	private $configuration;

	// Reports if a config.php file is found then this will be set to true
	private $deprecated;

	// Reports if the configuration is found in the same directory as the framework then this is set to true
	private $within_framework;

	/**
	 * Loads the framework configuration file
	 */
	public function __construct() {

		//initialize object variables to empty values
		$this->configuration = [];
		$this->deprecated = false;
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

		//check if the configuration is stored in the framework structure
		$this->within_framework = strpos(dirname(__DIR__, 2), $this->file) !== false;
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

	// loads the config.conf file or provides a backward compatibility layer if a config.php file was used
	private function load() {

		//check for deprecated version so we can use 'require' instead
		if ($this->deprecated) {
			//allow global variables to be set in the old config.php file
			global $db_type, $db_host, $db_port, $db_name, $db_username, $db_password;

			//load the config.php file
			require_once $this->file;

			//convert the old properties to the new standard
//			$this->configuration['#database system settings'] = '';
			$this->configuration['database.0.type'] = $db_type;
			$this->configuration['database.0.host'] = $db_host;
			$this->configuration['database.0.port'] = $db_port;
			$this->configuration['database.0.name'] = $db_name;
			$this->configuration['database.0.username'] = $db_username;
			$this->configuration['database.0.password'] = $db_password;
			$this->configuration['database.0.sslmode'] = 'prefer';

			//remove from the global namespace
			unset($db_type, $db_host, $db_port, $db_name, $db_username, $db_password);

			//set defaults for the new config.conf file
//			$this->configuration['#database switch settings'] = '';
			$this->configuration['database.1.type'] = 'sqlite';
			$this->configuration['database.1.path'] = '/var/lib/freeswitch/db';
			$this->configuration['database.1.name'] = 'core.db';
//			$this->configuration['#general settings'] = '';
			$this->configuration['document.root'] = '/var/www/fusionpbx';
			$this->configuration['project.path'] = '';
			$this->configuration['temp.dir'] = '/tmp';
			$this->configuration['php.dir'] = PHP_BINDIR;
			$this->configuration['php.bin'] = basename(PHP_BINARY);
//			$this->configuration['#cache settings'] = '';
			$this->configuration['cache.method'] = 'file';
			$this->configuration['cache.location'] = '/var/cache/fusionpbx';
			$this->configuration['cache.settings'] = 'true';
//			$this->configuration['#switch settings'] = '';
			$this->configuration['switch.conf.dir'] = '/etc/freeswitch';
			$this->configuration['switch.sounds.dir'] = '/usr/share/freeswitch/sounds';
			$this->configuration['switch.database.dir'] = '/var/lib/freeswitch/db';
			$this->configuration['switch.recordings.dir'] = '/var/lib/freeswitch/recordings';
			$this->configuration['switch.storage.dir'] = '/var/lib/freeswitch/storage';
			$this->configuration['switch.voicemail.dir'] = '/var/lib/freeswitch/storage/voicemail';
			$this->configuration['switch.scripts.dir'] = '/usr/share/freeswitch/scripts';
			$this->configuration['switch.event_socket.host'] = 'fs';
			$this->configuration['switch.event_socket.port'] = '8021';
			$this->configuration['switch.event_socket.password'] = 'ClueCon';
//			$this->configuration['#switch xml'] = '';
			$this->configuration['xml_handler.fs_path'] = 'false';
			$this->configuration['xml_handler.reg_as_number_alias'] = 'false';
			$this->configuration['xml_handler.number_as_presence_id']  =' true';
//			$this->configuration['#error reporting hide show all errors except notices and warnings'] = '';
			$this->configuration['error.reporting'] = 'none';
		} else {
			//use native php parsing function
			$conf = parse_ini_file($this->file);
			//save the loaded and parsed conf file to the object
			$this->configuration = $conf;
		}

	}

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
			$this->deprecated = true;
		}
		elseif (file_exists("/usr/local/etc/fusionpbx/config.php")) {
			$this->file = "/usr/local/etc/fusionpbx/config.php";
			$this->deprecated = true;
		}
		// use the current web directory to find it as a last resort
		elseif (file_exists(dirname(__DIR__, 2) . "/resources/config.php")) {
			$this->file = "/var/www/fusionpbx/resources/config.php";
			$this->deprecated = true;
		}
	}

	/**
	 * Returns true if the configuration was found to be using a .php extension instead of the
	 * config.conf configuration
	 * @return bool
	 */
	public function compatibility_enabled(): bool {
		return $this->deprecated;
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
	 * Returns the path fully resolved of any symlinks and the file name
	 * @return string
	 */
	public function path_and_filename(): string {
		return realpath($this->path()) . '/' . $this->filename();
	}

	/**
	 * Returns the array of configuration settings
	 * @return array
	 */
	public function configuration(): array {
		return $this->configuration;
	}

}


//Examples:
//~~~~~~~~
$config = new config;
echo "Config path: " . $config->path() . "\n";
echo "Config file: " . $config->filename() . "\n";
echo "Full path and filename: " . $config->path_and_filename() . "\n";
echo "Compatibility Mode: " . $config->compatibility_enabled() ? 'true' : 'false';
if ($config->compatibility_enabled()) {
	//do something to create a new one or notify user
}

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
