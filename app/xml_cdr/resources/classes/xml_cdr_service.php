<?php

/**
 * Description goes here for xml_cdr service
 */
class xml_cdr_service extends service {

	/**
	 * Message to show when reloading the settings
	 *
	 * @var string
	 */
	private $message;

	/**
	 * database object
	 * @var database
	 */
	private $database;

	/**
	 * settings object
	 * @var settings
	 */
	private $settings;

	/**
	 * cdr object
	 * @var settings
	 */
	private $cdr;

	/**
	 * hostname variable
	 * @var string
	 */
	private $hostname;

	/**
	 * limit variable
	 * @var string
	 */
	private $xml_cdr_dir;

	/**
	 * Reloads settings from database, config file and websocket server.
	 *
	 * @return void
	 */
	protected function reload_settings(): void {
		// Read the config file to get any possible changes
		parent::$config->read();

		// Connect to the database
		$this->database = new database(['config' => parent::$config]);

		// Get the settings using global defaults
		$this->settings = new settings(['database' => $this->database]);

		// Get the hostname
		$this->hostname = gethostname();

		// Initialize the xml cdr object
		$this->cdr = new xml_cdr;

		// Get the xml_cdr directory
		$this->xml_cdr_dir = $this->settings->get('switch', 'log', '/var/log/freeswitch').'/xml_cdr';

		// Show the message in the log so we can track when the settings are reloaded
		$this->notice($this->message);

		// Set the message for the next reload
		$this->message = "Settings reloaded";
	}

	public function run(): int {

		// Set the initial message
		$this->message = "Settings loaded";

		// Reload the settings
		$this->reload_settings();

		// Rename the directory
		if (file_exists($this->xml_cdr_dir.'/failed/invalid_xml')) {
			rename($this->xml_cdr_dir.'/failed/invalid_xml', $this->xml_cdr_dir.'/failed/xml');
		}

		// Create the invalid xml directory
		if (!file_exists($this->xml_cdr_dir.'/failed/xml')) {
			mkdir($this->xml_cdr_dir.'/failed/xml', 0770, true);
		}

		// Create the invalid size directory
		if (!file_exists($this->xml_cdr_dir.'/failed/size')) {
			mkdir($this->xml_cdr_dir.'/failed/size', 0770, true);
		}

		// Create the invalid SQL directory
		if (!file_exists($this->xml_cdr_dir.'/failed/sql')) {
			mkdir($this->xml_cdr_dir.'/failed/sql', 0770, true);
		}

		// Check if inotify extension is available
		$use_inotify = extension_loaded('inotify');

		// Initialize inotify if available
		$inotify = null;
		if ($use_inotify) {
			$inotify = inotify_init();
			if ($inotify === false) {
				$this->notice("Failed to initialize inotify");
				$use_inotify = false;
			} else {
				$inotify_add = inotify_add_watch($inotify, $this->xml_cdr_dir, IN_CLOSE_WRITE | IN_MOVED_TO);
				if ($inotify_add === false) {
					$this->notice("Failed to add inotify watch");
					inotify_free_resources($inotify);
					$use_inotify = false;
				}
			}
		}

		// Send message to the log
		if ($use_inotify) {
			$this->notice("Using inotify for file monitoring");
		} else {
			$this->notice("Using opendir for file monitoring");
		}

		// Set the initial time
		$last_poll_time = time();

		// Service work is handled here
		while ($this->running) {

			// Make sure the database connection is available
			while (!$this->database->is_connected()) {
				// Connect to the database
				$this->database->connect();

				// Reload settings after connection to the database
				$this->settings = new settings(['database' => $this->database]);

				// Sleep for a moment
				sleep(3);
			}

			// Use opendir - inotify not available 
			if (!$use_inotify) {
				// Read the directory to find files to process
				$this->process_files();

				// Sleep for 100 ms
				usleep(100000);
			}

			// Use inotify
			if ($use_inotify) {
				// Set up stream select for inotify
				$read = [$inotify];
				$write = $except = null;
				$timeout = 300; // 5 minutes timeout

				if (stream_select($read, $write, $except, $timeout) > 0) {
					// Process inotify events
					$events = inotify_read($inotify) ?: [];
					if ($events === false) {
						$this->notice("inotify_read failed");
						$use_inotify = false;
						continue;
					}

					foreach ($events as $event) {
						// Set as a variable
						$mask = $event['mask'];

						// Check the event type (mask) inotify detected
						if ($mask & IN_Q_OVERFLOW) {
							// More than 20,000 files will cause an overflow, so process all files in the directory
							$this->warning('Too many files created. Processing in bulk.');
							$this->process_files();

							// Clear the queue by removing and re-adding the watch
							inotify_rm_watch($inotify, IN_CLOSE_WRITE | IN_MOVED_TO);
							inotify_add_watch($inotify, $this->xml_cdr_dir, IN_CLOSE_WRITE | IN_MOVED_TO);

							// Send a message that opendir processing has completed
							$this->warning('Bulk Processing completed.');

							// Stop processing the foreach loop
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

				// Periodic poll for missed files (every 5 minutes)
				if (time() - $last_poll_time >= 300) {
					$last_poll_time = time();
					$this->process_files();
				}
			}

		}

		// Cleanup
		if ($use_inotify && $inotify !== null) {
			inotify_rm_watch($inotify, $inotify_add);
			unset($inotify);
		}

		// Return a successful exit code
		return 0;
	}


	/**
	 * Read the XML CDR directory, process all files
	 *
	 * @return void
	 */
	protected function process_files(): void {

		// Prepare the directory handle
		$handle = opendir($this->xml_cdr_dir);

		// No handle, send a return
		if (!$handle) return;

		// Set the default value
		$processing = false;

		// Loop through files in the directory
		while (($file = readdir($handle)) !== false) {
			// Skip processing directories
			if (is_dir($this->xml_cdr_dir . DIRECTORY_SEPARATOR . $file)) {
				continue;
			}

			// File found set to process
			$processing = true;

			// Process the file
			$this->process_file($file);
		}
		closedir($handle);

		// Only run this again if files were processed
		if ($processing) {
			$this->process_files();
		}

	}

	/**
	 * Import the call detail records from the file system
	 *
	 * @return void
	 */
	protected function process_file($xml_cdr_file): void {

		// Only process XML files
		if (!str_ends_with($xml_cdr_file, '.cdr.xml')) {
			$this->notice("Skipped '$xml_cdr_file'");
			return;
		}

		// Send the name of the file to the log
		$this->debug("Processing ".$xml_cdr_file);

		// Prepend the XML CDR directory when not present
		if (!str_starts_with($xml_cdr_file, $this->xml_cdr_dir)) {
			$xml_cdr_file = $this->xml_cdr_dir . '/' . $xml_cdr_file;
		}

		// Move the files that are too large or have a zero file size to the failed size directory
		if (filesize($xml_cdr_file) >= (3 * 1024 * 1024) || filesize($xml_cdr_file) == 0) {
			if (!empty($this->xml_cdr_dir)) {
				$this->notice("Move the file ".$xml_cdr_file." to ".$this->xml_cdr_dir."/failed/size");
				rename($xml_cdr_file, $this->xml_cdr_dir.'/failed/size/'.basename($xml_cdr_file));
			}
			return;
		}

		// Get the content from the file
		$call_details = file_get_contents($xml_cdr_file);

		// Process the call detail record
		if (isset($xml_cdr_file) && isset($call_details)) {
			// Set the file
			$this->cdr->file = basename($xml_cdr_file);

			// Get the leg of the call and the file prefix
			if (substr(basename($xml_cdr_file), 0, 2) == "a_") {
				$leg = "a";
			}
			else {
				$leg = "b";
			}

			// Decode the xml string
			if (substr($call_details, 0, 1) == '%') {
				$call_details = urldecode($call_details);
			}

			// Parse the XML and insert the data into the database
			$this->cdr->xml_array(0, $leg, $call_details);
		}
	}

	protected static function display_version(): void {
		echo "XML CDR Service version 2.0\n";
	}

	protected static function set_command_options() {

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
