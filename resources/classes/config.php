<?php

	/**
	 * config class to manage the configuration file
	 * <p>Basic usage:<br>
	 * <code>
	 * $config = new config;
	 * $config->load();
	 * $db_name = $config->value('database.0.name', 'fusionpbx');
	 * $db_path = $config->path();
	 * $config->save();
	 * </code>
	 * </p>
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
				return $conf['database.0.' . substr($name, 3)] ?? '';
			}
			if ($name === 'config_path') {
				return $this->file;
			}
			if ($name === 'count') {
				return $this->lines();
			}
			return "";
		}

		/**
		 * Returns a new <i>config</i> object with PROJECT_ROOT defined.
		 * <p>The array locations are searched for in order provided by the array upon
		 * instantiation. If the first match in the array is found matches a config.php
		 * file, the constructor will try and migrate the config.php to the new config.conf
		 * file. It is required that the migration process occurs while executing from
		 * the CLI. For backwards compatibility, the $conf variable is also declared
		 * global and can be used to access the $conf array that matches the config
		 * file. However, this is highly discouraged and the value() method should be
		 * used instead as the $conf global array has been deprecated.<br>
		 * Example:<br>
		 * <code>
		 * $config = config::new()->load();
		 * </code><br>In this example, the configuration is located and then parsed.</p>
		 * <p>Notes:<br>
		 * The PROJECT_ROOT that is defined ensures that the <i><b>PROJECT_PATH</b></i> is included
		 * in the definition. This means that <b><i>PROJECT_ROOT</i></b> and
		 * <b><i>$_SERVER['DOCUMENT_ROOT'] . '/' . PROJECT_PATH</i></b> are equivalent and thus,
		 * <i><b>PROJECT_ROOT</b></i> can now be relied upon for the fully qualified
		 * path of the project.
		 * </p>
		 * @param array $paths_to_check A single dimension array of system paths to check for the config.conf or config.php file
		 * @param array $names A single dimension array of names that should be searched for the config file. Default is 'config'.
		 * @param array $extensions A single dimension array of extension types that should be used to search. Default is 'conf' and 'php'.
		 * @return config Object instance
		 * @throws Exception Thrown when a migration is attempted and the CLI is not being used for execution
		 */
		public function __construct(array $paths_to_check = [], array $names = ['config'], array $extensions = ['conf', 'php']) {

			if (empty($paths_to_check)) {
				$paths_to_check = [
					'/etc/fusionpbx',
					'/usr/local/etc/fusionpbx',
					($_SERVER['PROJECT_ROOT'] ?? '/var/www/fusionpbx') . '/resources'
				];
			}

			//initialize object property to an array
			$this->paths_to_check = [];

			//create an array by combining all possibilities
			foreach ($paths_to_check as $path) {
				foreach ($names as $name) {
					foreach ($extensions as $extension) {
						$this->paths_to_check[] = "$path/$name.$extension";
					}
				}
			}

			//find the config file
			$this->find();

			if (!$this->is_conf()) {
				//allow running from cli only
				if (self::is_cli()) {
					$this->migrate();
				} else {
					throw new \Exception('Config must be migrated manually. Try running upgrade from the terminal shell.');
				}
			}
		}

		/**
		 * Alias of load
		 */
		public function get(): ?config {
			return $this->load();
		}

		/**
		 * Set a value in the config object
		 * @param string $key Must not contain an '=' character
		 * @param string $value
		 */
		public function set_value(string $key, string $value): config {
			global $conf;
			if (strpos($key, '=') > 0) {
				throw new \InvalidArgumentException('Key must not contain an equals character');
			}
			$conf[$key] = $value;
			return $this;
		}

		/**
		 * Saves to the path provided
		 * Save method can only be called from the CLI as the config.conf file should be protected
		 * from writing
		 * @param string $new_path
		 * @return bool
		 */
		public function save(?string $new_path = null): bool {
			//prevent running from the web
			if (!self::is_cli()) {
				return false;
			}

			//set to the current location if needed
			if ($new_path === null) {
				$new_path = $this->path();
			}

			//ensure we are writing to a location
			if (empty($new_path)) {
				throw new InvalidArgumentException('Path must not be empty');
			}

			//set file output stream
			$filename = $this->dirname() . '/config.conf';

			//open file
			$ostream = fopen($filename, 'w'); //w = Create and open for writing only replace contents
			if ($ostream === false) {
				throw new \Exception('Unable to open file for writing');
			}

			//get the config data
			$data = $this->serialize();

			//write it to the file
			$bytes = fwrite($ostream, $data);

			//close file
			fclose($ostream);

			//return success if we have managed to write all bytes to the file
			return $bytes === strlen($data);
		}

		/**
		 * Returns the value of the setting cached in memory
		 * @global array $conf
		 * @param string $setting Setting key from the Configuration file
		 * @param mixed $default Default value if no setting key exists
		 * @param bool $remove_trailing_slash removes a trailing '/' from a string if it exists before returning the value
		 * @return mixed returns the setting as a string or a default value from the configuration file
		 */
		public function value(string $setting, mixed $default = null, bool $remove_trailing_slash = false) {
			global $conf;
			$retval = $default;
			// make sure it exists
			if (array_key_exists($setting, $conf)) {
				//override return value with true value
				$retval = $conf[$setting];
			}
			// return the retval without trialing slash
			if ($remove_trailing_slash && gettype($retval) === 'string') {
				return rtrim($retval, '/');
			}
			// return retval as is
			return $retval;
		}

		/**
		 * Load the configuration in to global scoped variables
		 * <p>Loads the configuration file if it exists in the paths set in the constructor
		 * and then defines the <b><code>PROJECT_ROOT</code></b> and <b><code>PROJECT_PATH</code></b>
		 * taken from the <i>document.root</i> and <i>project.path</i> set in the configuration file.
		 * If no <i>document.root</i> is found then the <i>/var/www/fusionpbx</i> value is used
		 * as a default location for the project.</p>
		 * @return config Returns this object or null if the config file does not exist
		 * @global string[] $conf
		 */
		public function load(): ?config {
			if (!$this->exists()) {
				return null;
			}

			//set the scope of $conf
			global $conf;

			//use the global variable to store the copy of the config
			$conf = parse_ini_file($this->file);

			//set project path
			if (!defined('PROJECT_PATH')) {
				define('PROJECT_PATH', $this->value('project.path', '', true));
			}

			//set project root
			$document_root = $this->value('document.root', '/var/www/fusionpbx', true);
			if (!defined('PROJECT_ROOT')) {
				define('PROJECT_ROOT', (empty(PROJECT_PATH) ? $document_root : $document_root . '/' . PROJECT_PATH));
			}

			//ensure php knows the search path
			set_include_path(PROJECT_ROOT);

			return $this->set_db_global_vars();
		}

		public function set_db_global_vars(): config {
			global $db_type, $db_host, $db_port, $db_name, $db_username, $db_password;
			global $db_secure, $db_sslmode;
			$db_type = $this->value('database.0.type', 'pgsql');
			$db_host = $this->value('database.0.host', 'localhost');
			$db_name = $this->value('database.0.name', 'fusionpbx');
			$db_port = $this->value('database.0.port', '5432');
			$db_username = $this->value('database.0.username', 'fusionpbx');
			$db_password = $this->value('database.0.password', '');
			$db_secure = $this->value('database.0.secure', false);
			$db_sslmode = $this->value('database.0.sslmode', 'prefer');

			return $this;
		}

		/**
		 * Find the path to the config.php
		 * @var string $config_path - full path to the config.php file
		 * @return string Path of the config file
		 */
		public function find(): string {
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
		 * @return bool True if the file exists and false if it does not
		 * @see file_exists()
		 */
		public function exists(): bool {
			return file_exists($this->file);
		}

		/**
		 * Returns the full path and file name
		 * @return string
		 */
		public function path(): string {
			return $this->file ?? '';
		}

		/**
		 * Returns the path
		 * @return string Path of the file
		 */
		public function dirname(): string {
			$file_info = pathinfo($this->file);
			return $file_info['dirname'] ?? '';
		}

		/**
		 * Returns the number of lines in the config file
		 * @global string $conf
		 * @return int number of entries in the config file
		 */
		public function lines(): int {
			global $conf;
			return count($conf ?? []);
		}

		/**
		 * Alias of lines
		 * @return int
		 * @see lines
		 */
		public function count(): int {
			return $this->lines();
		}

		/**
		 * Returns a section of the config file
		 * Each line is parsed and returned when the section_name matches the config line before the first '.' (dot).
		 * @param string $section_name
		 * @param bool $strip_section_name_from_key
		 * @return array
		 */
		public function section(string $section_name, bool $strip_section_name_from_key = false): array {
			global $conf;
			$ret_arr = [];
			foreach ($conf as $key => $value) {
				$section = substr(trim($key), 0, strlen($section_name));
				if ($section === $section_name) {
					if ($strip_section_name_from_key) {
						$key = substr($key, strlen($section_name));
					}
					$ret_arr[$key] = $value;
				}
			}
			return $ret_arr;
		}

		public function serialize(): string {
			global $conf;
			$sb = "";
			foreach ($conf as $key => $value) {
				$sb = "$key = $value\n";
			}
			return $sb;
		}

		/**
		 * Returns a new <i>config</i> object with PROJECT_ROOT defined.
		 * <p>The array locations are searched for in order provided by the array upon
		 * instantiation. If the first match in the array is found matches a config.php
		 * file, the constructor will try and migrate the config.php to the new config.conf
		 * file. It is required that the migration process occurs while executing from
		 * the CLI. For backwards compatibility, the $conf variable is also declared
		 * global and can be used to access the $conf array that matches the config
		 * file. However, this is highly discouraged and the value() method should be
		 * used instead as the $conf global array has been deprecated.<br>
		 * Example:<br>
		 * <code>
		 * $config = config::new()->load();
		 * </code><br>In this example, the configuration is located and then parsed.
		 * PROJECT_ROOT and PROJECT_PATH are defined and the now <u>deprecated</u> global variables
		 * <i>$db_type, $db_host, $db_name, $db_port, $db_username, $db_secure, $db_sslmode</i> are
		 * set to their corresponding values in the configuration file.</p>
		 * <p>Notes:<br>
		 * The PROJECT_ROOT that is defined ensures that the <i><b>PROJECT_PATH</b></i> is included
		 * in the definition. This means that <b><i>PROJECT_ROOT</i></b> and
		 * <b><i>$_SERVER['DOCUMENT_ROOT'] . '/' . PROJECT_PATH</i></b> are equivalent and thus,
		 * <i><b>PROJECT_ROOT</b></i> can now be relied upon for the fully qualified
		 * path of the project.
		 * </p>
		 * @param array $paths_to_check A single dimension array of system paths to check for the config.conf or config.php file
		 * @param array $names A single dimension array of names that should be searched for the config file. Default is 'config'.
		 * @param array $extensions A single dimension array of extension types that should be used to search. Default is 'conf' and 'php'.
		 * @return config Object instance
		 * @throws Exception Thrown when a migration is attempted and the CLI is not being used for execution.
		 */
		public static function new(array $paths_to_check = [], array $names = ['config'], array $extensions = ['conf', 'php']): config {
			try {
				return new config($paths_to_check, $names, $extensions);
			} catch (\Exception $e) {
				//let the caller handle the error
				throw $e;
			}
		}

		/**
		 * Tests if the found configuration file is a config.conf file
		 * @return bool True if config.conf is used otherwise it is false
		 */
		public function is_conf(): bool {
			return (basename($this->file) === 'config.conf');
		}

		/**
		 * Migrates the config.php file to a config.conf file
		 */
		private function migrate() {
			$export_config_array = [];

			//get the currently declared variables
			$vars_orig = get_defined_vars();

			//include the config.php
			include $this->file;

			// Get all defined variables from the external file
			$vars_new = get_defined_vars();

			// Get the difference
			$var_list = @array_diff_assoc($vars_new, $vars_orig);

			// Loop through the variables from the external file and add them to the array if not already defined
			foreach ($var_list as $variable_name => $variable_value) {
				//ignore arrays
				if (is_array($variable_value))
					continue;

				//rewrite the variable name to use '.' instead of '_'
				$name = str_replace('_', '.', $variable_name);

				//check for database settings
				if (substr($name, 0, 3) === 'db.') {
					$name = 'database.0.' . substr($variable_name, 3);
				}
				$export_config_array[$name] = $variable_value;

				//remove variable from declared variables
				unset($$variable_name);
			}

			//ensure there is a configuration to save
			if (count($export_config_array) > 0) {
				//get the current location of the file
				$path = $this->dirname();

				//set the new name
				$new_file = $path . '/config.conf';

				//map variables in to string of key/value pairs
				$contents = implode("\n", array_map(function ($value, $key) {
						return "$key = $value";
					}, $export_config_array, array_keys($export_config_array)));

				//put the contents of the old config.php in to the newly created config.conf
				file_put_contents($new_file, $contents);
				//rename old file
				rename($this->file, $this->file . '.old');
				//point to the new config file
				$this->file = $new_file;
			}
		}

		//checks if this is running in the cli or from web
		private static function is_cli(): bool {
			if (defined('STDIN')) {
				return true;
			}
			if (php_sapi_name() == 'cli' && !isset($_SERVER['HTTP_USER_AGENT']) && is_numeric($_SERVER['argc'])) {
				return true;
			}
			return false;
		}
	}
