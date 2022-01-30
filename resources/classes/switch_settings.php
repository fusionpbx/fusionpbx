<?php

/**
 * switch_settings class provides access methods related to FreeSWITCH
 *
 * @method settings will add missing switch directories to default settings
 */
if (!class_exists('switch_settings')) {
	class switch_settings {

		public $event_socket_ip_address;
		public $event_socket_port;
		public $event_socket_password;

		/**
		 * Called when the object is created
		 */
		public function __construct() {

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
		 * settings Set switch directories in default settings
		 */
		public function settings() {

			//define the variables
				if (!isset($this->event_socket_ip_address)) {
					if (strlen($_SESSION['event_socket_ip_address']) > 0) {
						$this->event_socket_ip_address = $_SESSION['event_socket_ip_address'];
					}
					else {
						$this->event_socket_ip_address = '127.0.0.1';
					}
				}
				if (!isset($this->event_socket_port)) {
					if (strlen($_SESSION['event_socket_port']) > 0) {
						$this->event_socket_port = $_SESSION['event_socket_port'];
					}
					else {
						$this->event_socket_port = '8021';
					}
				}
				if (!isset($this->event_socket_password)) {
					if (strlen($_SESSION['event_socket_password']) > 0) {
						$this->event_socket_password = $_SESSION['event_socket_password'];
					}
					else {
						$this->event_socket_password = 'ClueCon';
					}
				}

			//connect to event socket
				$esl = new event_socket;
				$esl->connect($this->event_socket_ip_address, $this->event_socket_port, $this->event_socket_password);

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

			//set the bin directory
				if ($vars['base_dir'] == "/usr/local/freeswitch") {
					$bin = "/usr/local/freeswitch/bin"; 
				} else {
					$bin = "";
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
				$database = new database;
				$default_settings = $database->select($sql, null, 'all');
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
							$p = new permissions;
							$p->add('default_setting_add', 'temp');
						//execute insert
							$database = new database;
							$database->app_name = 'switch_settings';
							$database->app_uuid = '84e91084-a227-43cd-ae99-a0f8ed61eb8b';
							$database->save($array);
						//revoke temporary permissions
							$p->delete('default_setting_add', 'temp');
					}
					unset($missing);
				}

			//set the default settings
				if (is_array($array)) {
					foreach ($array as $row) {
						if (!isset($_SESSION['switch'][$row['default_setting_subcategory']])) {
							if ($row['default_setting_enabled'] != "false") {
								$_SESSION['switch'][$row['default_setting_subcategory']] = $row['default_setting_value'];
							}
						}
					}
				}

			//unset the array variable
				unset($array);
		}
	}
}

?>
