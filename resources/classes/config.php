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
		 * Set a value in the config object
		 * @param string $key Must not contain an '=' character
		 * @param string $value
		 */
		public function set_value(string $key, string $value) {
			global $conf;
			if (strpos($key, '=') > 0) {
				throw new \InvalidArgumentException('Key must not contain an equals character');
			}
			$conf[$key] = $value;
		}

		/**
		 * Saves to the path provided
		 * @param string $new_path
		 * @return bool
		 */
		public function save(?string $new_path = null): bool {
			//set to the current location if needed
			if ($new_path === null) {
				$new_path = $this->path();
			}

			//ensure we are writing to a location
			if (empty($new_path)) {
				throw new InvalidArgumentException('Path must not be empty');
			}

			//ensure the file name is removed from path
			$path_info = pathinfo($new_path);
			$path = $path_info['dirname'];

			//set file output stream
			$ostream = $path . '/config.conf';

			//open file
			$handle = fopen($ostream, 'w'); //w = Create and open for writing only replace contents
			if ($handle === false) {
				throw new \Exception('Unable to open file for writing');
			}

			//get the config data
			$data = $this->serialize();

			//write it to the file
			$bytes = fwrite($ostream, $data);

			//close file
			fclose($handle);

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
			$_SERVER["PROJECT_PATH"] = $conf['project.path'] ?? '';
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

		/**
		 * Returns the current path of the config
		 * @return string
		 */
		public function path(): string {
			return $this->file ?? '';
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
						$key = substr($key,strlen($section_name));
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
	}
