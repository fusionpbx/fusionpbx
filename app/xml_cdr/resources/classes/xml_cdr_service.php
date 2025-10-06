<?php

/*
 * FusionPBX
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is FusionPBX
 *
 * The Initial Developer of the Original Code is
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Portions created by the Initial Developer are Copyright (C) 2008-2025
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

/**
 * Description of xml_cdr_service
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class xml_cdr_service extends service {

	static $hostname = null;
	static $max_records = null;
	private $xml_cdr_dir;
	private $database;
	private $cdr;
	private $initialized = false;

	protected function reload_settings(): void {
		if (!$this->initialized)
			$this->info('Loading settings');

		// Create database object or recreate if config settings changed
		$this->database = new database(['config' => self::$config]);
		while (!$this->database->is_connected()) {
			// Connect to database
			$this->database->connect();
			sleep(5);
		}

		// Get the global default settings with any new settings
		$this->settings = new settings(['database' => $this->database]);

		//get the xml_cdr directory
		$xml_cdr_dir = $this->settings->get('switch', 'log', '/var/log/freeswitch/xml_cdr') . '/xml_cdr';
		$this->create_xml_cdr_paths($xml_cdr_dir);

		// Set the hostname
		$hostname = $this->settings->get('cdr', 'hostname', null);
		if ($hostname !== null && $hostname !== self::$hostname) {
			self::set_hostname($hostname);
			$this->info("Set hostname: $hostname");
		}

		// Set a max records to process
		$max_records = $this->settings->get('cdr', 'process_max', null);
		if ($max_records !== null && $max_records !== self::$max_records) {
			self::set_max_records($max_records);
			$this->info("Set max processing records: $max_records");
		}

		if ($this->initialized)
			$this->notice('Settings Reloaded');
		else
			$this->notice('Settings Loaded');

		//recreate the call detail records object
		$this->cdr = new xml_cdr;

		// Reload any existing files after reloading settings
		$this->process_in_bulk();

	}

	protected function create_xml_cdr_paths($xml_cdr_dir) {
		//rename the directory
		if (file_exists($xml_cdr_dir . '/failed/invalid_xml')) {
			$this->debug("Found old 'invalid_xml' directory. Moving to new name 'xml'");
			rename($xml_cdr_dir . '/failed/invalid_xml', $xml_cdr_dir . '/failed/xml');
		}

		//create the invalid xml directory
		if (!file_exists($xml_cdr_dir . '/failed/xml')) {
			$this->debug("Creating missing 'xml' failed folder");
			mkdir($xml_cdr_dir . '/failed/xml', 0770, true);
		}

		//create the invalid size directory
		if (!file_exists($xml_cdr_dir . '/failed/size')) {
			$this->debug("Creating missing 'size' failed folder");
			mkdir($xml_cdr_dir . '/failed/size', 0770, true);
		}

		//create the invalid sql directory
		if (!file_exists($xml_cdr_dir . '/failed/sql')) {
			$this->debug("Creating missing 'sql' failed folder");
			mkdir($xml_cdr_dir . '/failed/sql', 0770, true);
		}

//		// XML CDR runs as www-data now so settings permissions don't work
//		if (file_exists($xml_cdr_dir . '/failed')) {
//			exec('chmod 770 -R ' . $xml_cdr_dir . '/failed');
//		}

		//save the xml_cdr directory in the object
		$this->xml_cdr_dir = $xml_cdr_dir;

	}

	protected static function display_version(): void {
		echo "Version 2.00\n";
	}

	protected static function set_command_options() {
		self::append_command_option(command_option::new()
				->short_option('H:')
				->long_option('hostname:')
				->function_append('set_hostname')
				->description('Set the hostname. Defaults to use the php function gethostname()')
		);
		self::append_command_option(command_option::new()
				->short_option('m:')
				->long_option('max_records:')
				->function_append('set_max_records')
				->description('Set the maximum records to process on each iteration. Default 100')
		);
	}

	protected static function set_hostname($hostname) {
		self::$hostname = $hostname;
	}

	public static function set_max_records($max_records) {
		self::$max_records = intval($max_records);
	}

	public function run(): int {
		// Set a default
		if (self::$hostname === null) {
			self::$hostname = gethostname();
		}

		// Set a default
		if (self::$max_records === null) {
			self::$max_records = 100;
		}

		$this->reload_settings();

		$this->initialized = true;

		// Check for inotify php extension
		if (!function_exists('inotify_init')) {
			$this->warning('Missing inotify extension. Please install php' . substr(PHP_VERSION, 0, 3). '-inotify package');
			while ($this->running) {
				$this->process_in_bulk();
				sleep(5);
			}
			return 0;
		}

		$inotify_instance = inotify_init();
		stream_set_blocking($inotify_instance, false);
		inotify_add_watch($inotify_instance, $this->xml_cdr_dir, IN_CLOSE_WRITE | IN_MOVED_TO);

		$this->notice("Watching $this->xml_cdr_dir for files");

		// Main loop
		while ($this->running) {

			$read = [$inotify_instance];
			$write = null;
			$except = null;
			if (stream_select($read, $write, $except, null) > 0) {
				$events = inotify_read($inotify_instance) ?: [];
				foreach ($events as $event) {
					$mask = $event['mask'];
					if ($mask & IN_Q_OVERFLOW) {
						//more than 20,000 files will cause an overflow so process all files in the directory
						$this->warning('Too many files created. Processing in bulk.');
						$this->process_in_bulk();
						// clear the queue by removing and re-adding the watch
						inotify_rm_watch($inotify_instance, IN_CLOSE_WRITE | IN_MOVED_TO);
						inotify_add_watch($inotify_instance, $this->xml_cdr_dir, IN_CLOSE_WRITE | IN_MOVED_TO);
						break;
					} elseif (($mask & IN_CLOSE_WRITE) || $mask & IN_MOVED_TO) {
						//process individual file
						$this->process_file($event['name']);
					} else {
						$this->debug('Detected event: ' . $this->mask_debug($mask));
						var_dump($event);
						exit();
					}
				}
			}
		}

		return 0;
	}

	private function process_in_bulk(): void {
		do {
			// Clean the existing files with the fastest method
			$handle = opendir($this->xml_cdr_dir);
			if (!$handle) return;
			$processing = false;
			while (false !== ($file = readdir($handle))) {
				$xml_cdr_file = $this->xml_cdr_dir . DIRECTORY_SEPARATOR . $file;
				if (is_dir($xml_cdr_file)) continue;
				$processing = true;
				$this->process_file($xml_cdr_file);
			}
			closedir($handle);
		} while ($processing);
	}

	private function process_file($xml_cdr_file) {
		if (!str_ends_with($xml_cdr_file, '.cdr.xml')) {
			$this->debug("Skipped '$xml_cdr_file'");
			return;
		}
		$this->debug("Processing '$xml_cdr_file'");

		$size = filesize($xml_cdr_file);

		if ($size < 1) {
			$this->debug("Moving zero byte file '$xml_cdr_file' to 'failed/size'");
			$this->move($xml_cdr_file, 'size');
			// Next file
			return;
		}

		//move files zero bytes or over 3 MB to the failed size directory
		if ($size > 3145727) {
			$this->debug("Moving oversize file '$xml_cdr_file' to 'failed/size'");
			$this->move($xml_cdr_file, 'size');
			// Next file
			return;
		}

		//get the content from the file
		$call_details = file_get_contents($xml_cdr_file);

		//process the call detail record
		if ($call_details !== false) {
			//set the file
			$this->cdr->file = basename($xml_cdr_file);

			//get the leg of the call and the file prefix
			if (substr(basename($xml_cdr_file), 0, 2) == "a_") {
				$leg = "a";
			} else {
				$leg = "b";
			}

			//decode the xml string
			if (substr($call_details, 0, 1) == '%') {
				$call_details = urldecode($call_details);
				if (empty($call_details)) {
					$this->move($xml_cdr_file, 'decoding');
				}
			}

			//parse the xml and insert the data into the db
			$this->cdr->xml_array(0, $leg, $call_details);
		} else {
			$this->warning("Failed to read contents of $xml_cdr_file");
			$this->move($xml_cdr_file, 'failed');
		}
	}

	private function move($file, $failed_sub_directory = '') {

		// Set the source full path and filename
		if (!str_starts_with($file, $this->xml_cdr_dir)) {
			$source = $this->xml_cdr_dir . DIRECTORY_SEPARATOR . $file;
		} else {
			$source = $file;
		}

		// Set the failed folder destination
		$destination = $this->xml_cdr_dir . '/failed';
		if (!empty($failed_sub_directory) && $failed_sub_directory !== 'failed') {
			$destination .= '/' . $failed_sub_directory;
		}

		if (!is_dir($destination)) {
			if (!mkdir($destination, 0751, true)) {
				throw new \RuntimeException("Failed to create directory $destination", 4871);
			}
		}

		$destination .= '/' . basename($file);

		// Move file to failed directory
		if (!rename($source, $destination)) {
			$this->error("Failed to move $source to $destination");
			return;
		}

		return;
	}

	protected function debug(string $message = '') {
		self::log($message, LOG_DEBUG);
	}

	protected function info(string $message = '') {
		self::log($message, LOG_INFO);
	}

	protected function notice(string $message = '') {
		self::log($message, LOG_NOTICE);
	}

	protected function warning(string $message = '') {
		self::log($message, LOG_WARNING);
	}

	protected function error(string $message = '') {
		self::log($message, LOG_ERR);
	}

	function mask_debug(int $mask): string {
		$flags = [];
		$map = [
			IN_ACCESS => 'ACCESS',
			IN_ATTRIB => 'ATTRIB',
			IN_CLOSE_NOWRITE => 'CLOSE_NOWRITE',
			IN_CLOSE_WRITE => 'CLOSE_WRITE',
			IN_CREATE => 'CREATE',
			IN_DELETE => 'DELETE',
			IN_DELETE_SELF => 'DELETE_SELF',
			IN_MODIFY => 'MODIFY',
			IN_MOVE_SELF => 'MOVE_SELF',
			IN_MOVED_FROM => 'MOVED_FROM',
			IN_MOVED_TO => 'MOVED_TO',
			IN_OPEN => 'OPEN',
		];
		foreach ($map as $bit => $name) {
			if ($mask & $bit)
				$flags[] = $name;
		}
		return implode('|', $flags);
	}
}
