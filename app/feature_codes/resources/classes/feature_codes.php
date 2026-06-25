<?php

class feature_codes {
	const app_name = 'feature_codes';
	const app_uuid = '7a39c83a-f3f2-4774-8732-8d839758aa47';
	const app_category = 'reports';
	const app_subcategory = '';
	const app_version = '1.0.0';
	const app_description = 'This application provides a report of all features and their status.';

	/**
	 * Set in the constructor. Must be a database object and cannot be null.
	 * @var database Database Object
	 */
	private $database;

	/**
	 * Settings object set in the constructor. Must be a settings object and cannot be null.
	 * @var settings Settings Object
	 */
	private $settings;

	/**
	 * User UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
	 * @var string
	 */
	private $user_uuid;

	/**
	 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
	 * @var string
	 */
	private $domain_uuid;

	/**
	 * Initializes the object with settings and default values.
	 *
	 * @param array $setting_array Associative array of setting keys to their respective values (optional)
	 */
	public function __construct(array $setting_array = []) {
		//set domain and user UUIDs
		$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
		$this->user_uuid = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';

		//set objects
		$config = $setting_array['config'] ?? config::load();
		$this->database = $setting_array['database'] ?? database::new(['config' => $config]);
		$this->settings = $setting_array['settings'] ?? new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);
	}

	public function get_name() {
		return 'Features Report';
	}

	/**
	 * Fetch feature code rows from the dialplan and call flows tables.
	 *
	 * Dialplan entries whose number begins with '*' are included. Call flows
	 * that have a feature code set are joined in and that code is used in
	 * place of the dialplan number.
	 *
	 * @param database $database    Database instance to use for the query.
	 * @param bool     $include_raw Include the raw dialplan_xml column.
	 *
	 * @return array                Array of associative rows, or an empty array.
	 */
	public static function fetch_features(database $database, bool $include_raw = false): array {
		$domain_uuid = $_SESSION['domain_uuid'] ?? '';
		$order_by = $_GET['order_by'] ?? '';
		$order = $_GET['order'] ?? '';
		$sql = "SELECT d.dialplan_uuid, d.dialplan_name, ";
		$sql .= "COALESCE(NULLIF(cf.call_flow_feature_code, ''), d.dialplan_number) AS feature_code, ";
		$sql .= "COALESCE(cf.call_flow_description, d.dialplan_description) AS dialplan_description ";
		if ($include_raw) {
			$sql .= ", d.dialplan_xml ";
		}
		$sql .= "FROM v_dialplans d ";
		$sql .= "LEFT JOIN v_call_flows cf ON cf.dialplan_uuid = d.dialplan_uuid ";
		$sql .= "WHERE d.dialplan_enabled = 'true' ";
		$sql .= "AND (d.dialplan_number LIKE '*%' OR NULLIF(cf.call_flow_feature_code, '') IS NOT NULL) ";
		$sql .= "AND (d.domain_uuid = :domain_uuid OR d.domain_uuid IS NULL) ";
		$sql .= order_by($order_by, $order, 'feature_code', 'asc');
		$parameters['domain_uuid'] = $domain_uuid;
		$result = $database->select($sql, $parameters, 'all');
		return is_array($result) ? $result : [];
	}

	public function display(): void {
		echo $this->render();
		return;
	}

	public function render(): string {
		return "Features Report";
	}
}
