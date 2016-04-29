<?php

/**
 * switch_settings class provides access methods related to FreeSWITCH
 *
 * @method settings will add missing switch directories to default settings
 */
if (!class_exists('switch_settings')) {
	class switch_settings {

		public $db;
		public $event_socket_ip_address;
		public $event_socket_port;
		public $event_socket_password;

		/**
		 * Called when the object is created
		 */
		public function __construct() {
			//connect to the database if not connected
			if (!$this->db) {
				require_once "resources/classes/database.php";
				$database = new database;
				$database->connect();
				$this->db = $database->db;
			}
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
					if (strlen($_SESSION['event_socket_ip_address']) > 0) {
						$this->event_socket_port = $_SESSION['event_socket_port'];
					}
					else {
						$this->event_socket_port = '8021';
					}
				}
				if (!isset($this->event_socket_password)) {
					if (strlen($_SESSION['event_socket_ip_address']) > 0) {
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

			//close event socket
				fclose($fp);

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
				$array[$x]['default_setting_subcategory'] = 'phrases';
				$array[$x]['default_setting_name'] = 'dir';
				$array[$x]['default_setting_value'] = $vars['conf_dir'].'/lang';
				$array[$x]['default_setting_enabled'] = 'false';
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
				$prep_statement = $this->db->prepare($sql);
				$prep_statement->execute();
				$default_settings = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				unset ($prep_statement, $sql);

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
				if (is_array($missing)) {
					$sql = "insert into v_default_settings (";
					$sql .= "default_setting_uuid, ";
					$sql .= "default_setting_category, ";
					$sql .= "default_setting_subcategory, ";
					$sql .= "default_setting_name, ";
					$sql .= "default_setting_value, ";
					$sql .= "default_setting_enabled, ";
					$sql .= "default_setting_description ";
					$sql .= ") values \n";
					$i = 1;
					foreach ($missing as $row) {
						$sql .= "(";
						$sql .= "'".uuid()."', ";
						$sql .= "'".check_str($row['default_setting_category'])."', ";
						$sql .= "'".check_str($row['default_setting_subcategory'])."', ";
						$sql .= "'".check_str($row['default_setting_name'])."', ";
						$sql .= "'".check_str($row['default_setting_value'])."', ";
						$sql .= "'".check_str($row['default_setting_enabled'])."', ";
						$sql .= "'".check_str($row['default_setting_description'])."' ";
						$sql .= ")";
						if (sizeof($missing) != $i) { 
							$sql .= ",\n";
						}
						$i++;
					}
					$this->db->exec(check_sql($sql));
					unset($missing);
				}

			//set the default settings
				foreach ($array as $row) {
					if (!isset($_SESSION['switch'][$row['default_setting_subcategory']])) {
						if ($row['default_setting_enabled'] != "false") {
							$_SESSION['switch'][$row['default_setting_subcategory']] = $row['default_setting_value'];
						}
					}
				}

			//unset the array variable
				unset($array);
		}
	}
}

?>
