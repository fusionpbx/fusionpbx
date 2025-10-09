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

	private static $cli_hostname = null;
	private static $cli_full_scan_seconds = null;
	private static $cli_idle_time = null;
	private static $cli_max_file_size = null;

	private $xml_cdr_dir;
	private $database;
	private $cdr;
	private $initialized = false;
	private $idle_time;
	private $full_scan_expire_time;
	private $full_scan_seconds;
	private $max_file_size;

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
		if (self::$cli_hostname !== null) {
			// command line overrides global settings
			$hostname = self::$cli_hostname;
		} else {
			$hostname = $this->settings->get('cdr', 'hostname', null);
		}
		$this->set_hostname($hostname);
		$this->info("Set hostname: $hostname");

		// Set the max file size
		if (self::$cli_max_file_size !== null) {
			$max_file_size = self::$cli_max_file_size;
		} else {
			$max_file_size = $this->settings->get('cdr', 'max_file_size', 3145727);
		}
		$this->info("Set max file size to $max_file_size");
		$this->set_max_file_size($max_file_size);

		// Check for inotify:
		//   When the inotify PHP extension is available we use the full_scan_expire_time
		//   Otherwise, we will use the idle_time_seconds
		if (function_exists('inotify_init')) {
			// Get the full scan seconds from command line first
			if (self::$cli_full_scan_seconds !== null) {
				$this->full_scan_seconds = intval(self::$cli_full_scan_seconds);
			} else {
				$this->full_scan_seconds = intval($this->settings->get('cdr', 'full_scan_seconds', 600));
			}
			$this->info("Set full scan to every $this->full_scan_seconds seconds");
			$this->set_full_scan_expire_time($this->full_scan_seconds);
		} else {
			// Get the idle time when inotify is not available
			if (self::$cli_idle_time !== null) {
				// Command line overrides default settings
				$idle_time = self::$cli_idle_time;
			} else {
				// Not set from command line so get from default settings
				$idle_time = $this->settings->get('cdr', 'idle_time_seconds', 3);
			}
			$this->info("Set idle time $idle_time seconds");

			// set the idle time using a setter
			$this->set_idle_time($idle_time);
		}

		//recreate the call detail records object
		$this->cdr = new xml_cdr();

		// Notify user we are ready
		if ($this->initialized) {
			$this->notice('Settings Reloaded');
		} else {
			$this->notice('Settings Loaded');
		}

		// Reload any existing files after reloading settings
		$this->info('Scanning for existing files');
		$this->process_all_files();

	}

	protected function create_xml_cdr_paths($xml_cdr_dir) {
		//unable to continue if we can't create directories
		if (!is_writable($xml_cdr_dir . '/failed')) {
			throw new \RuntimeException("Unable to write to $xml_cdr_dir/failed", 4008);
		}

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

		//save the xml_cdr directory in the object
		$this->xml_cdr_dir = $xml_cdr_dir;

	}

	protected static function display_version(): void {
		echo "Version 2.00\n";
	}

	protected static function set_command_options() {
		self::append_command_option(command_option::new()
				->short_option('n:')
				->long_option('hostname:')
				->function_append('set_cli_hostname')
				->description('Set the hostname. Defaults to use the php function gethostname()')
		);
		self::append_command_option(command_option::new()
				->short_option('m:')
				->long_option('max_file_size:')
				->function_append('set_cli_max_file_size')
				->description('Set the maximum filesize to process. Defaults to 3145727 bytes or 3 MB')
		);
		self::append_command_option(command_option::new()
				->short_option('s:')
				->long_option('full_scan_interval:')
				->function_append('set_cli_full_scan_expire_seconds')
				->description('Set the number of seconds to rescan the entire directory for XML CDR files. Default 600 (ten minutes)')
		);
		self::append_command_option(command_option::new()
				->short_option('i:')
				->long_option('idle_time:')
				->function_append('set_cli_idle_time')
				->description('Set the number of seconds to wait before scanning the entire directory if the inotify extension is not available. Default 3 seconds.')
		);
	}

	protected static function set_cli_hostname($hostname) {
		self::$cli_hostname = $hostname;
	}

	protected function set_hostname($hostname) {
		if (empty($hostname)) {
			// Quietly set the hostname to the one detected by php
			$hostname = gethostname();
		}
		$this->hostname = $hostname;
	}

	protected static function set_cli_full_scan_expire_seconds($seconds) {
		self::$cli_full_scan_seconds = intval($seconds);

		// Don't allow the program to start if the number given on the command line is invalid
		if (!is_numeric(self::$cli_full_scan_seconds) || empty(self::$cli_full_scan_seconds)) {
			throw new \RuntimeException("Idle time must be a number greater than zero", 4092);
		}

	}

	protected function set_full_scan_expire_time($seconds) {
		// convert to a true number
		$seconds = intval($seconds);

		// during runtime we will correct the number without exiting
		if (!is_numeric($seconds) || empty($seconds)) {
			$seconds = 600;
			// Warn the user the settings was not correct
			$this->warning("The number of seconds to rescan the XML CDR directory is invalid. Setting to $seconds.");
		}

		// set the time to a now valid number
		$this->full_scan_expire_time = time() + $seconds;
	}

	protected static function set_cli_idle_time($seconds) {
		// convert to a real number
		self::$cli_idle_time = intval($seconds);

		// Don't allow the program to start if the number given on the command line is invalid
		if (!is_numeric(self::$cli_idle_time) || empty(self::$cli_idle_time)) {
			throw new \RuntimeException("Idle time must be a number greater than zero", 4092);
		}
	}

	protected function set_idle_time($seconds) {
		// Make sure it is always a number
		$seconds = intval($seconds);

		// During runtime we will correct the number with a warning instead of exiting
		if (!is_numeric($seconds) || empty($seconds)) {
			$seconds = 3;
			$this->warning("The idle time must be set to a number greater than zero. Setting idle time to $seconds");
		}
		$this->idle_time = $seconds;
	}

	protected function set_cli_max_file_size($max_file_size) {
		// convert to a real number
		self::$cli_max_file_size = intval($max_file_size);

		// Don't allow the program to start if the number given on the command line is invalid
		if (!is_numeric(self::$cli_max_file_size) || empty(self::$cli_max_file_size)) {
			throw new \RuntimeException("Idle time must be a number greater than zero", 4092);
		}
	}

	protected function set_max_file_size($max_file_size) {
		$max_file_size = intval($max_file_size);
		if (!is_numeric($max_file_size) || empty($max_file_size)) {
			$max_file_size = 3145727;
			$this->warning("Max file size must be a number greater than zero. Setting to $max_file_size");
		}
		$this->max_file_size = $max_file_size;
	}

	public function run(): int {
		// Load the settings
		$this->reload_settings();

		// Set a flag for notifications
		$this->initialized = true;

		// Check for inotify php extension
		if (!function_exists('inotify_init')) {
			$this->warning('Missing inotify extension. Please install php' . substr(PHP_VERSION, 0, 3). '-inotify package for better performance');
			while ($this->running) {
				$this->process_all_files();
				sleep($this->idle_time);
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

			// Block for event
			if (stream_select($read, $write, $except, null) > 0) {
				$events = inotify_read($inotify_instance) ?: [];
				foreach ($events as $event) {
					$mask = $event['mask'];

					// Check the event type (mask) inotify detected
					if ($mask & IN_Q_OVERFLOW) {
						// More than 20,000 files will cause an overflow so process all files in the directory
						$this->warning('Too many files created. Processing in bulk.');
						$this->process_all_files();

						// Clear the queue by removing and re-adding the watch
						inotify_rm_watch($inotify_instance, IN_CLOSE_WRITE | IN_MOVED_TO);
						inotify_add_watch($inotify_instance, $this->xml_cdr_dir, IN_CLOSE_WRITE | IN_MOVED_TO);

						// Stop processing foreach loop
						break;
					} elseif (($mask & IN_CLOSE_WRITE) || $mask & IN_MOVED_TO) {
						// Process individual file detected
						$this->process_file($event['name']);
					} else {
						// Debug any extra events detected
						$this->debug('Detected event: ' . self::inotify_to_string($mask));
					}
				}
			}

			// Check if we need to do a full scan
			if ($this->full_scan_expire_time >= time()) {
				$this->info('Timer expired running full scan');
				$this->process_all_files();
				//set a new timer
				$this->set_full_scan_expire_time($this->full_scan_seconds);
			}
		}

		//exit process without error
		return 0;
	}

	/**
	 * Reads files from the xml cdr directory.
	 * The files are read until the readdir command returns false. This means any failed files MUST be moved to another
	 * location so that the directory can be empty so this function can return.
	 * @return void
	 * @link https://php.net/readdir Readdir documentation
	 */
	private function process_all_files(): void {
		do {
			// Read the existing files with the fastest method
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

		if (!str_starts_with($xml_cdr_file, $this->xml_cdr_dir)) {
			$xml_cdr_file = $this->xml_cdr_dir . '/' . $xml_cdr_file;
		}

		$size = filesize($xml_cdr_file);

		if ($size < 1) {
			$this->debug("Moving zero byte file '$xml_cdr_file' to 'failed/size'");
			$this->move($xml_cdr_file, 'size');
			// Next file
			return;
		}

		//move files zero bytes or over 3 MB to the failed size directory
		if ($size > $this->max_file_size) {
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
			$result = $this->cdr->xml_array(0, $leg, $call_details);
			if ($result === false) {
				//processing failed in the xml_cdr class
				$this->warning("Result when processing file returned false. Unable to process $xml_cdr_file.");
			}
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
				$this->error("Failed to create directory $destination");
			}
		}

		$destination .= '/' . basename($file);

		// Move file to failed directory
		if (!rename($source, $destination)) {
			$this->warning("Failed to move $source to $destination. Moving file to system temp folder.");
			// Try moving to the temp folder so the process doesn't hang
			if (!rename($source, sys_get_temp_dir())) {
				$this->error("Failed to relocate file $source to system temp folder");
				// We are unable to remove the file from the xml_cdr directory so we have to stop
				throw new \RuntimeException("Failed to remove file $source", 4871);
			}
			return;
		}

		return;
	}

	public static function inotify_to_string(int $mask): string {
		$flags = [];
		$map = [
			IN_ACCESS => 'ACCESS',
			IN_MODIFY => 'MODIFY',
			IN_ATTRIB => 'ATTRIB',
			IN_CLOSE_WRITE => 'CLOSE_WRITE',
			IN_CLOSE_NOWRITE => 'CLOSE_NOWRITE',
			IN_OPEN => 'OPEN',
			IN_MOVED_TO => 'MOVED_TO',
			IN_MOVED_FROM => 'MOVED_FROM',
			IN_CREATE => 'CREATE',
			IN_DELETE => 'DELETE',
			IN_DELETE_SELF => 'DELETE_SELF',
			IN_MOVE_SELF => 'MOVE_SELF',
			IN_UNMOUNT => 'UNMOUNT',
			IN_Q_OVERFLOW => 'Q_OVERFLOW',
			IN_ISDIR => 'SUBJECT IS DIRECTORY',
			IN_ONLYDIR => 'PATHNAME ONLY',
			IN_DONT_FOLLOW => 'DONT_FOLLOW',
			IN_MASK_ADD => 'MASK_ADD',
			IN_ONESHOT => 'ONESHOT',
		];
		foreach ($map as $bit => $name) {
			if ($mask & $bit)
				$flags[] = $name;
		}
		return implode('|', $flags);
	}
}
