<?php

/**
 * settings class
 *
 * @method array default_settings
 * @method array domain_settings
 * @method array user_settings
 * @method array all
 */
class settings {

	/**
	 * Called when the object is created
	 */
	public function __construct() {
		//place holder
	}

	/**
	 * get the default settings
	 * @var string $setting_category		the category
	 */
	public function default_settings($setting_category = null) {

		//set default parameters		
		$parameters = null;

		//get the default settings
		$sql = "select * from v_default_settings ";
		$sql .= "where default_setting_enabled = 'true' ";
		if (!empty($setting_category)) {
			$sql .= "and default_setting_category = :default_setting_category ";
			$parameters['default_setting_category'] = $setting_category;
		}
		$sql .= "order by default_setting_order asc ";
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $row) {
				$name = $row['default_setting_name'];
				$category = $row['default_setting_category'];
				$subcategory = $row['default_setting_subcategory'];
				if (empty($subcategory)) {
					if ($name == "array") {
						$settings[$category][] = $row['default_setting_value'];
					}
					else {
						$settings[$category][$name] = $row['default_setting_value'];
					}
				}
				else {
					if ($name == "array") {
						$settings[$category][$subcategory][] = $row['default_setting_value'];
					}
					else {
						$settings[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
						$settings[$category][$subcategory][$name] = $row['default_setting_value'];
					}
				}
			}
		}
		unset($sql, $result, $row);
		
		//return the settings array
		return $settings;

	}


	/**
	 * get the domain settings
	 * @var uuid domain_uuid
	 */
	public function domain_settings($domain_uuid) {

		$sql = "select * from v_domain_settings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and domain_setting_enabled = 'true' ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		unset($sql, $parameters);

		if (is_array($result) && sizeof($result) != 0) {
			foreach ($result as $row) {
				$name = $row['domain_setting_name'];
				$category = $row['domain_setting_category'];
				$subcategory = $row['domain_setting_subcategory'];
				if ($subcategory != '') {
					if ($name == "array") {
						$settings[$category][] = $row['default_setting_value'];
					}
					else {
						$settings[$category][$name] = $row['default_setting_value'];
					}
				}
				else {
					if ($name == "array") {
						$settings[$category][$subcategory][] = $row['default_setting_value'];
					}
					else {
						$settings[$category][$subcategory]['uuid'] = $row['default_setting_uuid'];
						$settings[$category][$subcategory][$name] = $row['default_setting_value'];
					}
				}
			}
		}
		unset($result, $row);

		//return the settings array
		return $settings;

	}


	/**
	 * get the user settings
	 * @var uuid domain_uuid
	 * @var uuid user_uuid
	 */
	public function user_settings($domain_uuid, $user_uuid) {

		//if (array_key_exists("domain_uuid",$_SESSION) && array_key_exists("user_uuid",$_SESSION) && is_uuid($_SESSION["domain_uuid"])) {
		$sql = "select * from v_user_settings ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and user_uuid = :user_uuid ";
		$sql .= " order by user_setting_order asc ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['user_uuid'] = $user_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result)) {
			foreach ($result as $row) {
				if ($row['user_setting_enabled'] == 'true') {
					$name = $row['user_setting_name'];
					$category = $row['user_setting_category'];
					$subcategory = $row['user_setting_subcategory'];
					if (!empty($row['user_setting_value'])) {
						if (empty($subcategory)) {
							//$$category[$name] = $row['domain_setting_value'];
							if ($name == "array") {
								$settings[$category][] = $row['user_setting_value'];
							}
							else {
								$settings[$category][$name] = $row['user_setting_value'];
							}
						}
						else {
							//$$category[$subcategory][$name] = $row['domain_setting_value'];
							if ($name == "array") {
								$settings[$category][$subcategory][] = $row['user_setting_value'];
							}
							else {
								$settings[$category][$subcategory][$name] = $row['user_setting_value'];
							}
						}
					}
				}
			}
		}
		//}

		//return the settings array
		return $settings;
	}


	/**
	 * Build the all settings into an array
	 * @var uuid domain_uuid
	 * @var uuid user_uuid
	 */
	public function all($domain_uuid, $user_uuid = null) {

		//get the default settings
		$settings = $this->default_settings();

		//get the domains settings
		if (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/app_config.php")) {
			include "app/domains/resources/settings.php";
		}

		//get the domain settings
		if (!empty($domain_uuid)) {
			$result = $this->domain_settings($domain_uuid);
			foreach($result as $key => $row) {
				$settings[$key] = $row;
			}
		}

		//get the user settings
		if (!empty($user_uuid)) {
			$result = $this->user_settings($domain_uuid, $user_uuid);
			foreach($result as $key => $row) {
				$settings[$key] = $row;
			}
		}

		//add settings to the session array
		if (!defined('STDIN') && !empty($settings)) {
			foreach($settings as $key => $row) {
				$_SESSION[$key] = $row;
			}
		}

		//return the settings array
		return $settings;
	}

}

?>
