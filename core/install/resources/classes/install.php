<?php

if (!class_exists('install')) {
	class install {

		/**
		* declare the variables
		*/
		private $app_name;
		private $app_uuid;
		public $message;
		public $database_host;
		public $database_port;
		public $database_name;
		public $database_username;
		public $database_password;

		/**
		 * called when the object is created
		 */
		public function __construct() {
			//assign the variables
			$this->app_name = 'install';
			$this->app_uuid = '75507e6e-891e-11e5-af63-feff819cdc9f';
		}

		/**
		 * <p>Used to create the config.conf file.</p>
		 * <p>BSD /usr/local/etc/fusionpbx</p>
		 * <p>Linux /etc/fusionpbx</p>
		 * @return boolean
		 */
		public function config() {

			//set the default config file location
			$os = strtoupper(substr(PHP_OS, 0, 3));
			switch ($os) {
			case "BSD":
				$config_path = '/usr/local/etc/fusionpbx';
				$config_file = $config_path.'/config.conf';
				$document_root = '/usr/local/www/fusionpbx';

				$conf_dir = '/usr/local/etc/freeswitch';
				$sounds_dir = '/usr/share/freeswitch/sounds';
				$database_dir = '/var/lib/freeswitch/db';
				$recordings_dir = '/var/lib/freeswitch/recordings';
				$storage_dir = '/var/lib/freeswitch/storage';
				$voicemail_dir = '/var/lib/freeswitch/storage/voicemail';
				$scripts_dir = '/usr/share/freeswitch/scripts';
				$php_dir = PHP_BINDIR;
				$cache_location = '/var/cache/fusionpbx';
				break;
			case "LIN":
				$config_path = '/etc/fusionpbx/';
				$config_file = $config_path.'/config.conf';
				$document_root = '/var/www/fusionpbx';

				$conf_dir = '/etc/freeswitch';
				$sounds_dir = '/usr/share/freeswitch/sounds';
				$database_dir = '/var/lib/freeswitch/db';
				$recordings_dir = '/var/lib/freeswitch/recordings';
				$storage_dir = '/var/lib/freeswitch/storage';
				$voicemail_dir = '/var/lib/freeswitch/storage/voicemail';
				$scripts_dir = '/usr/share/freeswitch/scripts';
				$php_dir = PHP_BINDIR;
				$cache_location = '/var/cache/fusionpbx';
				break;
			case "WIN":
				$config_path = $_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR.'resources';
				$config_file = $config_path.DIRECTORY_SEPARATOR.'config.conf';
				$config_file = $config_path.DIRECTORY_SEPARATOR.'config.conf';
				$document_root = $_SERVER["DOCUMENT_ROOT"];

				$conf_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'conf';
				$sounds_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'sounds';
				$database_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'db';
				$recordings_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'recordings';
				$storage_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'storage';
				$voicemail_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'voicemail';
				$scripts_dir = $_SERVER['ProgramFiles'].DIRECTORY_SEPARATOR.'freeswitch'.DIRECTORY_SEPARATOR.'scripts';
				$php_dir = dirname(PHP_BINARY);
				$cache_location = dirname($_SERVER['DOCUMENT_ROOT']).DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'fusionpbx';
				break;
			}

			//end the script if the config path is not set
			if (!isset($config_path)) {
				$this->message = "Config file path not found\n";
				return false;
			}

			//config directory is not writable
			if (!is_writable($config_path)) {
				$this->message = "Check permissions ".$config_path." must be writable.\n";
				return false;
			}

			//make the config directory
			if (isset($config_path)) {
				system('mkdir -p '.$config_path);
			}

			//build the config file
			$conf = "\n";
			$conf .= "#database system settings\n";
			$conf .= "database.0.type = pgsql\n";
			$conf .= "database.0.host = ".$this->database_host."\n";
			$conf .= "database.0.port = ".$this->database_port."\n";
			$conf .= "database.0.sslmode = prefer\n";
			$conf .= "database.0.name = ".$this->database_name."\n";
			$conf .= "database.0.username = ".$this->database_username."\n";
			$conf .= "database.0.password = ".$this->database_password."\n";
			$conf .= "\n";
			$conf .= "#database switch settings\n";
			$conf .= "database.1.type = sqlite\n";
			$conf .= "database.1.path = ".$database_dir."\n";
			$conf .= "database.1.name = core.db\n";
			//$conf .= "database.1.backend.base64 = \n";
			$conf .= "\n";
			$conf .= "#general settings\n";
			$conf .= "document.root = ".$document_root."\n";
			$conf .= "project.path =\n";
			$conf .= "temp.dir = /tmp\n";
			$conf .= "php.dir = ".$php_dir."\n";
			$conf .= "php.bin = php\n";
			$conf .= "\n";
			$conf .= "#cache settings\n";
			$conf .= "cache.method = file\n";
			$conf .= "cache.location = ".$cache_location."\n";
			$conf .= "cache.settings = true\n";
			$conf .= "\n";
			$conf .= "#switch settings\n";
			$conf .= "switch.conf.dir = ".$conf_dir."\n";
			$conf .= "switch.sounds.dir = ".$sounds_dir."\n";
			$conf .= "switch.database.dir = ".$database_dir."\n";
			$conf .= "switch.recordings.dir = ".$recordings_dir."\n";
			$conf .= "switch.storage.dir = ".$storage_dir."\n";
			$conf .= "switch.voicemail.dir = ".$voicemail_dir."\n";
			$conf .= "switch.scripts.dir = ".$scripts_dir."\n";
			$conf .= "\n";
			$conf .= "#switch xml handler\n";
			$conf .= "xml_handler.fs_path = false\n";
			$conf .= "xml_handler.reg_as_number_alias = false\n";
			$conf .= "xml_handler.number_as_presence_id = true\n";
			$conf .= "\n";
			$conf .= "#error reporting options: user,dev,all\n";
			$conf .= "error.reporting = user\n";

			//write the config file
			$file_handle = fopen($config_file,"w");
			if(!$file_handle) { return; }
			fwrite($file_handle, $conf);
			fclose($file_handle);
			
			//if the config.conf file was saved return true
			if (file_exists($config_file)) {
				return true;
			}
			else {
				return false;
			}

		}

	}
}

?>
