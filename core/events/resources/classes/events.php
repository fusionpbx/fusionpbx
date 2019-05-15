<?php

/**
 * events class provides an event system
 *
 * @method void load_plugins
 * @method dynamic __call
 */
class events {

	/**
	 * @var obj $db 		Database connnection object
	 * @var array $plugins		Store available plugin classes
	 * @var array $methods		store methods found on each plugin
	 * @var array $headers		headers provide information about the events
	 * @var array $required		array of items that are required
	 * @var string $content		optional additional data about the event
	 */
	public $db;
	private $plugins = array();
	private $methods = array();
	public  $headers = array();
	public  $required = array();
	private $content;

	/**
	 * Called when the object is created
	 * Creates the database connection object
	 */
	public function __construct() {
		//create the database connection
			include "root.php";
			require_once "resources/classes/database.php";
			$database = new database;
			$database->connect();
			$this->db = $database->db;
			return $this->db = $database->db;

		//load the plugins
			$this->load_plugins();

		//add values to the required array
			$this->required['headers'][] = "content-type";
			$this->required['headers'][] = "date";
			$this->required['headers'][] = "host";
			$this->required['headers'][] = "status";
			$this->required['headers'][] = "app_name";
			$this->required['headers'][] = "app_uuid";
			$this->required['headers'][] = "domain_uuid";
			$this->required['headers'][] = "user_uuid";
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
	 * This function will load all available plugins into the memory
	 * Rules:
	 * 		plugins are stored in ./plugins
	 * 		plugin class is named plugin_<name>
	 * 		php file is named <name>.php
	 */
	private function load_plugins() {
		$base = realpath(dirname(__FILE__)) . "/plugins";
		$this->plugins = glob($base . "/*.php");
		foreach($this->plugins as $plugin) {
			//include the plugin php file and define the class name
				include_once $plugin;
				$plugin_name = basename($plugin, ".php");
				$class_name = "plugin_".$plugin_name;

			//create the plugin object so that it can be stored and called later
				$obj = new $class_name();
				$this->plugins[$plugin_name] = $obj;

			//store all methods found in the plugin
				foreach (get_class_methods($obj) as $method ) {
					$this->methods[$method] = $plugin_name;
				}

		}
	}

	/**
	 * Run the plugin method
	 * @param strint $method
	 * @param string $args
	 *
	 */
	public function __call($method, $args) {
		if (! key_exists($method, $this->methods)) {
			throw new Exception ("Call to undefined method: " . $method);
		}
		array_unshift($args, $this);
		try {
			$obj = call_user_func_array(array($this->plugins[$this->methods[$method]], $method), $args);
		}
		catch (Exception $e) {
			echo 'Exception: ',  $e->getMessage(), "\n";
		}
		return $obj;
	}

	/**
	 * Set a new event header
	 * @param string $category
	 * @param string $name
	 * @param string $value
	 */
	public function set_header($category, $name, $value) {
		$this->headers[$category][$name] = $value;
	}

	/**
	 * check for required headers
	 * @param string $category
	 * @return boolean $value
	 */
	public function check_required($category) {
		foreach ($this->required['headers'] as &$header) {
			if ($category == $header) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Send the event
	 */
	public function send() {
		//check for required headers are present return false if any are missing
			foreach ($this->headers as &$header) {
				if (!$this->check_required($header)) {
					return false;
				}
			}

		//$this->content;
	}

	/**
	 * Serialize the event headers
	 * @param string $type  values: array, json
	 */
	public function serialize($type) {
		$array = $this->headers;
		if ($type == "array") {
			return $array;
		} elseif ($type == "json") {
			return json_encode($array);
		}
	}

}

?>
