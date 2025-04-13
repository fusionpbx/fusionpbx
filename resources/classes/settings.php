<?php

/**
 * settings class is used to load settings using hierarchical overriding
 *
 * The settings are loaded from the database tables default_settings, domain_settings, and user_settings in that order with
 * each setting overriding the setting from the previous table.
 *
 * @access public
 * @author Mark Crane <mark@fusionpbx.com>
 */
class settings implements clear_cache {

	/**
	 * Set in the constructor. String used to load a specific domain. Must be a value domain UUID before sending to the constructor.
	 * @var string
	 */
	private $domain_uuid;

	/**
	 * Set in the constructor. String used to load a specific user. Must be a valid user UUID before sending to the constructor.
	 * @var string
	 */
	private $user_uuid;

	/**
	 * Set in the constructor. String used for loading a specific device UUID
	 * @var string
	 */
	private $device_uuid;

	/**
	 * Set in the constructor. String used for loading device profile
	 * @var string
	 */
	private $device_profile_uuid;

	/**
	 * Set in the constructor. Current category set to load
	 * @var string
	 */
	private $category;

	/**
	 * Internal array structure that is populated from the database
	 * @var array Array of settings loaded from Default Settings
	 */
	private $settings;

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 * @var database Database Object
	 */
	private $database;

	/**
	 * Tracks if the APCu extension is loaded for database queries
	 * @var bool
	 */
	private $apcu_enabled;

	/**
	 * Create a settings object using key/value pairs in the $setting_array.
	 *
	 * Valid values are: database, domain_uuid, user_uuid, device_uuid, device_profile_uuid, and category.
	 * @param array setting_array
	 * @depends database::new()
	 * @access public
	 */
	public function __construct($setting_array = []) {

		//try to use RAM cache by default
		if (!isset($setting_array['allow_caching'])) {
			$setting_array['allow_caching'] = true;
		}

		//track ram caching
		if ($setting_array['allow_caching']) {
			$this->apcu_enabled = function_exists('apcu_enabled') && apcu_enabled();
		}

		//open a database connection
		if (empty($setting_array['database'])) {
			$this->database = database::new();
		} else {
			$this->database = $setting_array['database'];
		}

		//trap passing a PDO object instead of the required database object
		if (!($this->database instanceof database)) {
			//should never happen but will trap it here just-in-case
			throw new \InvalidArgumentException("Database object passed in settings class constructor is not a valid database object");
		}

		//set the values from the array
		$this->domain_uuid = $setting_array['domain_uuid'] ?? null;
		$this->user_uuid = $setting_array['user_uuid'] ?? null;
		$this->device_profile_uuid = $setting_array['device_profile_uuid'] ?? null;
		$this->device_uuid = $setting_array['device_uuid'] ?? null;
		$this->category = $setting_array['category'] ?? null;

		$this->reload();
	}

	/**
	 * Returns the database object used in the settings
	 * @return database Object
	 */
	public function database(): database {
		return $this->database;
	}

	/**
	 * Reloads the settings from the database
	 */
	public function reload() {
		$this->settings = [];

		//set the default settings
		$this->default_settings();

		//set the domain settings
		if (!empty($this->domain_uuid)) {
			$this->domain_settings();

			//set the user settings only when the domain_uuid was set
			if (!empty($this->user_uuid)) {
				$this->user_settings();
			}

			//set the device profile settings
			if (!empty($this->device_profile_uuid)) {
				$this->device_profile_settings();
			}

			//set the device settings
			if (!empty($this->device_uuid)) {
				$this->device_settings();
			}
		}
	}

	/**
	 * Get the value utilizing the hierarchical overriding technique
	 * @param string $category Returns all settings when empty or the default value if the settings array is null
	 * @param string $subcategory Returns the array of category items when empty or the default value if the category array is null
	 * @param mixed $default_value allows default value returned if category and subcategory not found
	 */
	public function get(string $category = null, string $subcategory = null, $default_value = null) {

		//incremental refinement from all settings to a single setting
		if (empty($category)) {
			return $this->settings ?? $default_value;
		}
		elseif (empty($subcategory)) {
			return $this->settings[$category] ?? $default_value;
		}
		else {
			return $this->settings[$category][$subcategory] ?? $default_value;
		}
	}

	/**
	 * Returns the domain_uuid in this object used to load the settings
	 * @return string UUID of the domain used to load the object or an empty string
	 */
	public function get_domain_uuid(): string {
		if (!empty($this->domain_uuid)) {
			return $this->domain_uuid;
		}
		return "";
	}

	/**
	 * Returns the user_uuid in this object used to load the settings
	 * @return string UUID of the user used to load the object or an empty string
	 */
	public function get_user_uuid(): string {
		if (!empty($this->user_uuid)) {
			return $this->user_uuid;
		}
		return "";
	}

	/**
	 * set the default, domain, user, device or device profile settings
	 * @param string $table_prefix prefix for the table.
	 * @param string $uuid uuid of the setting if available. If set to an empty string then a new uuid will be created.
	 * @param string $category Category of the setting.
	 * @param string $subcategory Subcategory of the setting.
	 * @param string $value (optional) Value to set. Default is empty string.
	 * @param string $type Type of the setting (array, numeric, text, etc)
	 * @param bool $enabled (optional) True or False. Default is True.
	 * @param string $description (optional) Description. Default is empty string.
	 */
	public function set(string $table_prefix, string $uuid, string $category, string $subcategory, string $value = "", string $type = 'text', bool $enabled = true, string $description = "") {

		//set the table name
		$table_name = $table_prefix.'_settings';

		//init record as an array
		$record = [];
		if(!empty($this->domain_uuid)) {
			$record[$table_name][0]['domain_uuid'] = $this->domain_uuid;
		}
		if(!empty($this->user_uuid)) {
			$record[$table_name][0]['user_uuid'] = $this->user_uuid;
		}
		if(!empty($this->device_uuid)) {
			$record[$table_name][0]['device_uuid'] = $this->device_uuid;
		}
		if(!empty($this->device_profile_uuid)) {
			$record[$table_name][0]['device_profile_uuid'] = $this->device_profile_uuid;
		}
		if(!is_uuid($uuid)) {
			$uuid = uuid();
		}

		//build the array
		$record[$table_name][0][$table_prefix.'_setting_uuid'       ] = $uuid;
		$record[$table_name][0][$table_prefix.'_setting_category'   ] = $category;
		$record[$table_name][0][$table_prefix.'_setting_subcategory'] = $subcategory;
		$record[$table_name][0][$table_prefix.'_setting_name'       ] = $type;
		$record[$table_name][0][$table_prefix.'_setting_value'      ] = $value;
		$record[$table_name][0][$table_prefix.'_setting_enabled'    ] = $enabled;
		$record[$table_name][0][$table_prefix.'_setting_description'] = $description;

		//grant temporary permissions
		$p = permissions::new();
		$p->add($table_prefix.'_setting_add', 'temp');
		$p->add($table_prefix.'_setting_edit', 'temp');

		//execute insert
		$this->database->app_name = $table_name;
		$this->database->save($record);

		//revoke temporary permissions
		$p->delete($table_prefix.'_setting_add', 'temp');
		$p->delete($table_prefix.'_setting_edit', 'temp');
	}

	/**
	 * Update the internal settings array with the default settings from the database
	 * @access private
	 */
	private function default_settings() {

		//set the key for global defaults
		$key = 'settings_global_' . $this->category;

		//if the apcu extension is loaded get the already parsed array
		if ($this->apcu_enabled && apcu_exists($key)) {
			$this->settings = apcu_fetch($key);
			return;
		}

		//get the default settings
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_enabled = 'true' ";
		if (!empty($this->category)) {
			$sql .= "and default_setting_category = :default_setting_category ";
			$parameters['default_setting_category'] = $this->category;
		}
		$sql .= "order by default_setting_order asc ";
		$result = $this->database->select($sql, $parameters ?? null, 'all');
		if (!empty($result)) {
			foreach ($result as $row) {
				$name = $row['default_setting_name'];
				$category = $row['default_setting_category'];
				$subcategory = $row['default_setting_subcategory'];
				if (isset($row['default_setting_value']) && $row['default_setting_value'] !== '') {
					if ($name == "boolean") {
						$this->settings[$category][$subcategory] = filter_var($row['default_setting_value'], FILTER_VALIDATE_BOOLEAN);
					}
					elseif ($name == "array") {
						if (!isset($this->settings[$category][$subcategory]) || !is_array($this->settings[$category][$subcategory])) {
							$this->settings[$category][$subcategory] = array();
						}
						$this->settings[$category][$subcategory][] = $row['default_setting_value'];
					}
					else {
						$this->settings[$category][$subcategory] = $row['default_setting_value'];
					}
				}
			}
		}

		//if the apcu extension is loaded store the result
		if ($this->apcu_enabled) {
			apcu_store($key, $this->settings);
		}
	}

	/**
	 * Update the internal settings array with the domain settings from the database
	 * @access private
	 */
	private function domain_settings() {
		$key = 'settings_domain_'.$this->domain_uuid;
		$result = '';
		//if the apcu extension is loaded get the cached database result
		if ($this->apcu_enabled && apcu_exists($key)) {
			$result = apcu_fetch($key);
		} else {
			$sql = "select * from v_domain_settings ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and domain_setting_enabled = 'true' ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$result = $this->database->select($sql, $parameters, 'all');
			//if the apcu extension is loaded store the result
			if ($this->apcu_enabled) {
				apcu_store($key, $result);
			}
		}
		if (!empty($result)) {
			//domain setting array types override the default settings set as type array
			foreach ($result as $row) {
				$name = $row['domain_setting_name'];
				$category = $row['domain_setting_category'];
				$subcategory = $row['domain_setting_subcategory'];
				if ($name == "array") {
					$this->settings[$category][$subcategory] = array();
				}
			}

			//add the domain settings to the $this->settings array
			foreach ($result as $row) {
				$name = $row['domain_setting_name'];
				$category = $row['domain_setting_category'];
				$subcategory = $row['domain_setting_subcategory'];
				if (isset($row['domain_setting_value']) && $row['domain_setting_value'] !== '') {
					if ($name == "boolean") {
						$this->settings[$category][$subcategory] = filter_var($row['domain_setting_value'], FILTER_VALIDATE_BOOLEAN);
					}
					if ($name == "array") {
						if (!isset($this->settings[$category][$subcategory]) || !is_array($this->settings[$category][$subcategory])) {
							$this->settings[$category][$subcategory] = array();
						}
						$this->settings[$category][$subcategory][] = $row['domain_setting_value'];
					}
					else {
						$this->settings[$category][$subcategory] = $row['domain_setting_value'];
					}
				}

			}
		}
	}

	/**
	 * Update the internal settings array with the user settings from the database
	 * @access private
	 * @depends $this->domain_uuid
	 */
	private function user_settings() {
		$key = 'settings_user_'.$this->user_uuid;
		$result = '';
		//if the apcu extension is loaded get the cached database result
		if ($this->apcu_enabled && apcu_exists($key)) {
			$result = apcu_fetch($key);
		} else {
			$sql = "select * from v_user_settings ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and user_uuid = :user_uuid ";
			$sql .= " order by user_setting_order asc ";
			$parameters['domain_uuid'] = $this->domain_uuid;
			$parameters['user_uuid'] = $this->user_uuid;
			$result = $this->database->select($sql, $parameters, 'all');
			//if the apcu extension is loaded store the result
			if ($this->apcu_enabled) {
				apcu_store($key, $result);
			}
		}
		if (!empty($result)) {
			foreach ($result as $row) {
				if ($row['user_setting_enabled'] == 'true') {
					$name = $row['user_setting_name'];
					$category = $row['user_setting_category'];
					$subcategory = $row['user_setting_subcategory'];
					if (isset($row['user_setting_value']) && $row['user_setting_value'] !== '') {
						if ($name == "boolean") {
							$this->settings[$category][$subcategory] = filter_var($row['user_setting_value'], FILTER_VALIDATE_BOOLEAN);
						}
						elseif ($name == "array") {
							$this->settings[$category][$subcategory][] = $row['user_setting_value'];
						}
						else {
							$this->settings[$category][$subcategory] = $row['user_setting_value'];
						}

					}
				}
			}
		}
	}

	/**
	 * Update the internal settings array with the device profile settings from the database
	 * @access private
	 * @depends $this->domain_uuid
	 */
	private function device_profile_settings() {

		//get the device profile settings
		$sql = "select profile_setting_name, profile_setting_value from v_device_profile_settings"
			. " where device_profile_uuid = :device_profile_uuid"
			. " and domain_uuid = :domain_uuid"
			. " and profile_setting_enabled = 'true'"
		;
		$params = [];
		$params['device_profile_uuid'] = $this->device_profile_uuid;
		$params['domain_uuid'] = $this->domain_uuid;
		$result = $this->database->select($sql, $params, 'all');
		if (!empty($result)) {
			foreach ($result as $row) {
				$name = $row['profile_setting_name'];
				$value = $row['profile_setting_value'];
				$this->settings[$name] = $value;
			}
		}
	}

	/**
	 * Update the internal settings array with the device settings from the database
	 * @access private
	 * @depends $this->domain_uuid
	 */
	private function device_settings() {

		//get the device settings
		$sql = "select device_setting_subcategory, device_setting_value from v_device_settings"
			. " where device_setting_uuid = :device_uuid"
			. " and domain_uuid = :domain_uuid"
			. " and device_setting_enabled = 'true'"
		;
		$params = [];
		$params['device_uuid'] = $this->device_uuid;
		$params['domain_uuid'] = $this->domain_uuid;
		$result = $this->database->select($sql, $params, 'all');
		if (!empty($result)) {
			foreach ($result as $row) {
				$name = $row['device_setting_subcategory'];
				$value = $row['device_setting_value'] ?? null;
				$this->settings[$name] = $value;
			}
		}
	}

	public static function clear_cache() {
		if (function_exists('apcu_enabled') && apcu_enabled()) {
			$cache = apcu_cache_info(false);
			if (!empty($cache['cache_list'])) {
				//clear apcu cache
				foreach ($cache['cache_list'] as $entry) {
					$key = $entry['info'];
					if (str_starts_with($key, 'settings_')) {
						apcu_delete($key);
					}
				}
				global $settings;
				//check there is a settings object
				if (!empty($settings) && ($settings instanceof settings)) {
					$database = $settings->database();
					$domain_uuid = $settings->get_domain_uuid();
					$user_uuid = $settings->get_user_uuid();
					//recreate the settings object to reload all settings from database
					$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $user_uuid]);
				}
			}
		}
	}
}
