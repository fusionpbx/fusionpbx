<?php

/**
 * switch_settings class provides access methods related to FreeSWITCH
 *
 * @method settings will add missing switch directories to default settings
 */
	class switch_settings {

		public $event_socket_ip_address;
		public $event_socket_port;
		public $event_socket_password;

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
		 * Username set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
		 * @var string
		 */
		private $username;

		/**
		 * Domain UUID set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
		 * @var string
		 */
		private $domain_uuid;

		/**
		 * Domain name set in the constructor. This can be passed in through the $settings_array associative array or set in the session global array
		 * @var string
		 */
		private $domain_name;

		/**
		 * called when the object is created
		 */
		public function __construct(array $setting_array = []) {
			//set domain and user UUIDs
			$this->domain_uuid = $setting_array['domain_uuid'] ?? $_SESSION['domain_uuid'] ?? '';
			$this->domain_name = $setting_array['domain_name'] ?? $_SESSION['domain_name'] ?? '';
			$this->user_uuid = $setting_array['user_uuid'] ?? $_SESSION['user_uuid'] ?? '';
			$this->username = $setting_array['username'] ?? $_SESSION['username'] ?? '';

			//set objects
			$this->database = $setting_array['database'] ?? database::new();
			$this->settings = $setting_array['settings'] ?? new settings(['database' => $this->database, 'domain_uuid' => $this->domain_uuid, 'user_uuid' => $this->user_uuid]);

		}

		/**
		 * settings Set switch directories in default settings
		 */
		public function settings() {

			//connect to event socket
				$esl = event_socket::create($this->event_socket_ip_address, $this->event_socket_port, $this->event_socket_password);

			//run the api command
				$result = $esl->request('api global_getvar');

			//set the result as a named array
				$vars = array();
				foreach (explode("\n", $result) as $row) {
					$a = explode("=", $row);
					if (substr($a[0], -4) == "_dir") {
						$vars[$a[0]] = $a[1];
					}
				}

			//set defaults
				$vars['base_dir'] = $vars['base_dir'] ?? '';
				$vars['conf_dir'] = $vars['conf_dir'] ?? '';
				$vars['db_dir'] = $vars['db_dir'] ?? '';
				$vars['recordings_dir'] = $vars['recordings_dir'] ?? '';
				$vars['script_dir'] = $vars['script_dir'] ?? '';
				$vars['sounds_dir'] = $vars['sounds_dir'] ?? '';
				$vars['storage_dir'] = $vars['storage_dir'] ?? '';
				$vars['grammar_dir'] = $vars['grammar_dir'] ?? '';
				$vars['log_dir'] = $vars['log_dir'] ?? '';
				$vars['mod_dir'] = $vars['mod_dir'] ?? '';

			//set the bin directory
				if ($vars['base_dir'] == "/usr/local/freeswitch") {
					$bin = '/usr/local/freeswitch/bin';
				}
				else {
					$bin = '';
				}

			//create the default settings array
				$x=0;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'bin';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $bin;
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'base';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['base_dir'];
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'call_center';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['conf_dir'].'/autoload_configs';
				$array[$x]['default_setting_enabled'] = 'false';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'conf';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['conf_dir'];
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'db';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['db_dir'];
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'dialplan';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['conf_dir'].'/dialplan';
				$array[$x]['default_setting_enabled'] = 'false';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'extensions';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['conf_dir'].'/directory';
				$array[$x]['default_setting_enabled'] = 'false';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'grammar';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['grammar_dir'];
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'log';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['log_dir'];
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'mod';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['mod_dir'];
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'languages';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['conf_dir'].'/languages';
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'recordings';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['recordings_dir'];
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'scripts';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['script_dir'];
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'sip_profiles';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['conf_dir'].'/sip_profiles';
				$array[$x]['default_setting_enabled'] = 'false';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'sounds';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['sounds_dir'];
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'storage';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['storage_dir'];
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;
				$array[$x]['default_setting_category'] = 'switch';
				$array[$x]['default_setting_subcategory'] = 'voicemail';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['storage_dir'].'/voicemail';
				$array[$x]['default_setting_enabled'] = 'true';
				$array[$x]['default_setting_description'] = '';
				$x++;

			//get an array of the default settings
				$sql = "select * from v_default_settings ";
				$sql .= "where default_setting_category = 'switch' ";
				$default_settings = $this->database->select($sql, null, 'all');
				unset($sql);

			//find the missing default settings
				$x = 0;
				foreach ($array as $setting) {
					$found = false;
					$missing[$x] = $setting;
					foreach ($default_settings as $row) {
						if (trim($row['default_setting_subcategory']) == trim($setting['default_setting_subcategory'])) {
							$found = true;
							//remove items from the array that were found
							unset($missing[$x]);
						}
					}
					$x++;
				}
				unset($array);

			//add the missing default settings
				if (count($missing) > 0) {
					$i = 1;
					foreach ($missing as $row) {
						//build insert array
							$array['default_settings'][$i]['default_setting_uuid'] = uuid();
							$array['default_settings'][$i]['default_setting_category'] = $row['default_setting_category'];
							$array['default_settings'][$i]['default_setting_subcategory'] = $row['default_setting_subcategory'];
							$array['default_settings'][$i]['default_setting_name'] = $row['default_setting_name'];
							$array['default_settings'][$i]['default_setting_value'] = $row['default_setting_value'];
							$array['default_settings'][$i]['default_setting_enabled'] = $row['default_setting_enabled'];
							$array['default_settings'][$i]['default_setting_description'] = $row['default_setting_description'];

						//increment the row id
							$i++;
					}
					if (is_array($array) && @sizeof($array) != 0) {
						//grant temporary permissions
							$p = permissions::new();
							$p->add('default_setting_add', 'temp');

						//execute insert
							$this->database->save($array);

						//clear the apcu cache
							settings::clear_cache();

						//revoke temporary permissions
							$p->delete('default_setting_add', 'temp');
					}
					unset($missing);
				}

			//set the default settings
				if (!empty($array) && is_array($array)) {
					foreach ($array as $row) {
						if (isset($row['default_setting_enabled']) && $row['default_setting_enabled'] == "true" && isset($row['default_setting_subcategory'])) {
							$_SESSION['switch'][$row['default_setting_subcategory']][$row['default_setting_name']] = $row['default_setting_value'] ?? '';
						}
					}
				}

			//unset the array variable
				unset($array);
		}
	}
