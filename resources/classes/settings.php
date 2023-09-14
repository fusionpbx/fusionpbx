<?php

/**
 * settings class
 * 
 */
class settings {

	private $domain_uuid;
	private $user_uuid;
	private $device_uuid;
	private $device_profile_uuid;
	private $category;
	private $settings;
	private $database;

	/**
	 * Called when the object is created
	 * @param array setting_array
	 * @depends database::new()
	 */
	public function __construct($setting_array = []) {

		//open a database connection
		$this->database = database::new();

		//set the values from the array
		$this->domain_uuid = $setting_array['domain_uuid'] ?? null;
		$this->user_uuid = $setting_array['user_uuid'] ?? null;
		$this->device_uuid = $setting_array['device_uuid'] ?? null;
		$this->device_profile_uuid = $setting_array['device_profile_uuid'] ?? null;
		$this->category = $setting_array['category'] ?? null;

		//set the default settings
		$this->default_settings();

		//set the domains settings
		//if (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/app_config.php")) {
		//	include "app/domains/resources/settings.php";
		//}

		//set the domain settings
		if (!empty($this->domain_uuid)) {
			$this->domain_settings();
		}

		//set the user settings
		if (!empty($this->user_uuid)) {
			$this->user_settings();
		}

		//debug show the settings
		//print_r($this->settings);

		//add settings to the session array
		//if (!defined('STDIN') && !empty($this->settings)) {
		//	foreach($this->settings as $key => $row) {
		//		$_SESSION[$key] = $row;
		//	}
		//}

	}

	/**
	 * get the value
	 * @param text category
	 * @param text subcategory
	 */
	public function get($category = null, $subcategory = null) {

		if (empty($category)) {
			return $this->settings;
		}
		elseif (empty($subcategory)) {
			return $this->settings[$category];
		}
		else {
			return $this->settings[$category][$subcategory];
		}

	}

	/**
	 * set the default settings
	 * 
	 */
	private function default_settings() {

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
				if (empty($subcategory)) {
					if ($name == "array") {
						$this->settings[$category][] = $row['default_setting_value'];
					}
					else {
						$this->settings[$category] = $row['default_setting_value'];
					}
				}
				else {
					if ($name == "array") {
						$this->settings[$category][$subcategory][] = $row['default_setting_value'];
					}
					else {
						$this->settings[$category][$subcategory] = $row['default_setting_value'];
					}
				}
			}
		}
		unset($sql, $result, $row);
	}


	/**
	 * set the domain settings
	 */
	private function domain_settings() {

		$sql = "select * from v_domain_settings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and domain_setting_enabled = 'true' ";
		$parameters['domain_uuid'] = $this->domain_uuid;
		$result = $this->database->select($sql, $parameters, 'all');
		unset($sql, $parameters);
		if (!empty($result)) {
			foreach ($result as $row) {
				$name = $row['domain_setting_name'];
				$category = $row['domain_setting_category'];
				$subcategory = $row['domain_setting_subcategory'];
				if (empty($subcategory)) {
					if ($name == "array") {
						$this->settings[$category][] = $row['domain_setting_value'];
					}
					else {
						$this->settings[$category] = $row['domain_setting_value'];
					}
				}
				else {
					if ($name == "array") {
						$this->settings[$category][$subcategory][] = $row['domain_setting_value'];
					}
					else {
						$this->settings[$category][$subcategory] = $row['domain_setting_value'];
					}
				}
			}
		}
		unset($result, $row);

	}


	/**
	 * set the user settings
	 */
	private function user_settings() {

		$sql = "select * from v_user_settings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and user_uuid = :user_uuid ";
		$sql .= " order by user_setting_order asc ";
		$parameters['domain_uuid'] = $this->domain_uuid;
		$parameters['user_uuid'] = $this->user_uuid;
		$result = $this->database->select($sql, $parameters, 'all');
		if (is_array($result)) {
			foreach ($result as $row) {
				if ($row['user_setting_enabled'] == 'true') {
					$name = $row['user_setting_name'];
					$category = $row['user_setting_category'];
					$subcategory = $row['user_setting_subcategory'];
					if (!empty($row['user_setting_value'])) {
						if (empty($subcategory)) {
							if ($name == "array") {
								$this->settings[$category][] = $row['user_setting_value'];
							}
							else {
								$this->settings[$category] = $row['user_setting_value'];
							}
						}
						else {
							if ($name == "array") {
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

	}

}

?>
