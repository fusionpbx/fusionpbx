<?php

/*
  FusionPBX
  Version: MPL 1.1

  The contents of this file are subject to the Mozilla Public License Version
  1.1 (the "License"); you may not use this file except in compliance with
  the License. You may obtain a copy of the License at
  http://www.mozilla.org/MPL/

  Software distributed under the License is distributed on an "AS IS" basis,
  WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
  for the specific language governing rights and limitations under the
  License.

  The Original Code is FusionPBX

  The Initial Developer of the Original Code is
  Mark J Crane <markjcrane@fusionpbx.com>
  Portions created by the Initial Developer are Copyright (C) 2008-2018
  the Initial Developer. All Rights Reserved.

  Contributor(s):
  Mark J Crane <markjcrane@fusionpbx.com>
  Tim Fry <tim.fry@hotmail.com>
 */

/*
 * Install instructions
 *
 *
 * Pre-requisit:
 *   In order to use the new event based features, this class requires some extra configuration of a PHP extension
 *   called inotify. The example below uses PHP version 8.1. Change 8.1 to your version. You can generally check
 *   what version is running by issuing the command: php -v
 *   Check if you already have the PHP module loaded by using: php -m
 *   If you find 'inotify' in the list you can skip to the 'How to Install as a System Service' section.
 *
 * Debian:
 *   sudo apt update; sudo apt install -y php8.1-inotify; sudo systemctl restart php8.1-fpm
 *
 * Raspberry pi:
 *   ** COMING SOON **
 *
 * CentOS:
 *   ** COMING SOON **
 *
 * After installation is completed check that the module is loaded using: php -m
 * You should see inotify in the list of modules PHP has loaded.
 * Once this is completed restart the PHP FPM service: sudo systemctl restart php8.1-fpm
 *
 *
 * How to Install as a System Service
 *
 * Debian:
 *   Copy the service file:
 *     sudo cp /var/www/fusionpbx/app/xml_cdr/resources/service/debian.service /etc/systemd/system/xml_cdr.service
 *   Reload the daemon:
 *     sudo systemctl daemon-reload
 *   Enable the service to start when the system boots up:
 *     sudo systemctl enable xml_cdr
 *   Start the service:
 *     sudo systemctl start xml_cdr
 *
 * Usage:
 *   First move in to the folder where the service currently resides
 *     cd /var/www/fusionpbx/app/xml_cdr/resources/service/
 *   Display help message:
 *     sudo php xml_cdr.php --help
 *
 */


/**
 * Creates an XML CDR Service that watches file events and imports new cdr records
 * @version 1.01
 */
final class xml_cdr_service {

	/**
	 * Current Version
	 */
	const VERSION = '1.01';

	/**
	 * Keys or values with the ':' denote a required value after it
	 * @var array Key is the short option and full name is the long option for the CLI
	 */
	const OPTIONS = [
		'd:' => 'debug:',
		'w:' => 'watch:',
		'H:' => 'hostname:',
		'h' => 'help',
		'v' => 'version',
		'r' => 'reload',
		's' => 'sleep',
		'b' => 'batch',
		'q' => 'quit',
	];

	/**
	 * @var array Matches the inotify mask with a description
	 */
	const FILE_ACCESS_EVENTS = [
		1 => ['IN_ACCESS', 'File was accessed (read)'],
		2 => ['IN_MODIFY', 'File was modified'],
		4 => ['IN_ATTRIB', 'Metadata changed (e.g. permissions, mtime, etc.)'],
		8 => ['IN_CLOSE_WRITE', 'File opened for writing was closed'],
		16 => ['IN_CLOSE_NOWRITE', 'File not opened for writing was closed'],
		32 => ['IN_OPEN', 'File was opened'],
		128 => ['IN_MOVED_TO', 'File moved into watched directory'],
		64 => ['IN_MOVED_FROM', 'File moved out of watched directory'],
		256 => ['IN_CREATE', 'File or directory created in watched directory'],
		512 => ['IN_DELETE', 'File or directory deleted in watched directory'],
		1024 => ['IN_DELETE_SELF', 'Watched file or directory was deleted'],
		2048 => ['IN_MOVE_SELF', 'Watch file or directory was moved'],
		24 => ['IN_CLOSE', 'Equals to IN_CLOSE_WRITE | IN_CLOSE_NOWRITE'],
		192 => ['IN_MOVE', 'Equals to IN_MOVED_FROM | IN_MOVED_TO'],
		4095 => ['IN_ALL_EVENTS', 'Bitmask of all the above constants'],
		8192 => ['IN_UNMOUNT', 'File system containing watched object was unmounted'],
		16384 => ['IN_Q_OVERFLOW', 'Event queue overflowed (wd is -1 for this event)'],
		32768 => ['IN_IGNORED', 'Watch was removed (explicitly by inotify_rm_watch() or because file was removed or filesystem unmounted'],
		1073741824 => ['IN_ISDIR', 'Subject of this event is a directory'],
		1073741840 => ['IN_CLOSE_NOWRITE', 'High-bit: File not opened for writing was closed'],
		1073741856 => ['IN_OPEN', 'High-bit: File was opened'],
		1073742080 => ['IN_CREATE', 'High-bit: File or directory created in watched directory'],
		1073742336 => ['IN_DELETE', 'High-bit: File or directory deleted in watched directory'],
		16777216 => ['IN_ONLYDIR', 'Only watch pathname if it is a directory (Since Linux 2.6.15)'],
		33554432 => ['IN_DONT_FOLLOW', 'Do not dereference pathname if it is a symlink (Since Linux 2.6.15)'],
		536870912 => ['IN_MASK_ADD', 'Add events to watch mask for this pathname if it already exists (instead of replacing mask).'],
		2147483648 => ['IN_ONESHOT', 'Monitor pathname for one event, then remove from watch list.']
	];

	/**
	 * Track the internal loop
	 * @var bool
	 */
	public static $running;

	/**
	 * Time to sleep when filesystem is being used
	 * @var int
	 */
	public $sleep;

	/**
	 * Number of CDRs to process before sleeps
	 * @var int
	 */
	public $batch_size;

	/**
	 * Directory to watch for changes in
	 * @var string
	 */
	private $watch_dir;

	/**
	 * File extension
	 * @var string
	 */
	private $file_extension;

	/**
	 * database object
	 * @var database
	 */
	private $database;

	/**
	 *  object used to parse file
	 *  @var xml_cdr
	 */
	private $xml_cdr;

	/**
	 * where to write the failed cdr imports
	 * @var string
	 */
	private $failed_dir;

	/**
	 * current debugging level for output to syslog
	 * @var int static
	 */
	public static $log_level = LOG_INFO;

	/**
	 * watch event type
	 * @var int
	 */
	public static $event_mask = 8;

	/**
	 * Operating System process identification file
	 * @var string
	 */
	public static $pid_file = "";

	/**
	 * system hostname
	 * @var string
	 */
	public static $hostname = "";

	/**
	 * Uses the inotify PECL extension to move files with the given extension from source directory to the
	 * destination directory
	 * @param database $db database connection
	 * @param xml_cdr $xml_cdr object to parse call information
	 * @param string $file_extension File extension to watch for including the '.' (period)
	 * @throws \Exception
	 * @see main()
	 * @depends inotify_init()
	 */
	private function __construct(database $db, xml_cdr $xml_cdr, string $file_extension = '.xml') {
		// Set up object properties to constructor params
		$this->database = $db;
		$this->xml_cdr = $xml_cdr;
		$this->file_extension = $file_extension;

		// Set up object properties to default values
		$this->watch_dir = $this->get_switch_log_dir();

		// Load the all settings from the database
		$this->reload_settings();

		// Load any existing files present
		$this->load_existing();
	}

	// register signal handlers
	private function register_signal_handlers() {
		// Allow the calls to be made while the main loop is running
		pcntl_async_signals(true);

		// A signal listener to reload the service for any config changes in the database
		pcntl_signal(SIGUSR1, [$this, 'reload_settings']);
		pcntl_signal(SIGHUP, [$this, 'reload_settings']);

		// A signal listener to stop the service
		pcntl_signal(SIGTERM, [$this, 'shutdown']);
	}

	/**
	 * Watches the source folder continuously until a file event occurs and then moves the file
	 * from the source to the database as defined in the settings
	 * @see __construct()
	 */
	public function run_event_listener() {
		// Register to receive SIGHUP and SIGUSR1 when not in a blocking read event
		$this->register_signal_handlers();

		// Notify the rest of the internal app we are running
		self::$running = true;

		// Set up the inotify instance
		$inotify = inotify_init();

		// Add a watch to monitor the directory for file creation events
		self::log('Adding watch dir ' . $this->watch_dir, LOG_INFO);
		inotify_add_watch($inotify, $this->watch_dir, self::$event_mask);

		// Loop indefinitely to monitor events
		while (self::$running) {
			// ensure we have memory information
			self::show_mem_usage();

			// blocks process to read events (including registered signal handlers)
			self::log('waiting for event', LOG_DEBUG);
			$events = inotify_read($inotify);

			// If events are detected
			if ($events !== false) {
				$this->loop_events($events);
			}
		}
		self::log('Stopping service', LOG_NOTICE);
		self::$running = false;
	}

	// import the xml file handler
	private function import_file(string $fileName) {
		// Get the file name
		$basename = basename($fileName);
		// Ensure the file extension matches by comparing the end of the filename with file_extension
		if (substr($basename, -1 * strlen($this->file_extension)) === $this->file_extension) {
			// Move the file to a new location
			$source_file = $this->watch_dir . '/' . $fileName;
			if ($this->import($source_file)) {
				self::log("Imported successfully: $fileName", LOG_INFO);
			} else {
				// Notify of failure
				self::log("Failed to import: $fileName", LOG_WARNING);
			}
		}
	}

	/**
	 * Shutdown process gracefully
	 */
	public function shutdown() {
		self::log("Initiating Shutdown...", LOG_NOTICE);
		self::$running = false;
	}

	/**
	 * Reloads settings from the database and notifies user
	 * Reloading the settings will occur only after
	 * the main thread has been unblocked from
	 * inotify_read
	 */
	public function reload_settings() {
		//reload config settings
		self::log("Reloading Configuration...", LOG_DEBUG);

		//ensure code changes take effect
		$this->database = new database;
		$this->xml_cdr = new xml_cdr();

		$this->watch_dir = $this->get_switch_log_dir();
		//check for valid source directory
		if (!file_exists($this->watch_dir)) {
			// Do not allow to continue with a missing source folder for importing
			throw new \InvalidArgumentException("XML_CDR source folder must be a valid location");
		}
		$this->failed_dir = $this->get_cdr_failed_dir();
		if (!file_exists($this->failed_dir)) {
			self::create_failed_directory($this->failed_dir);
		}
		self::log("OK", LOG_DEBUG);
		self::log("Configuration Reloaded.", LOG_WARNING);
	}

	/**
	 * Get the switch log folder from the default settings in the database
	 */
	private function get_switch_log_dir(): string {
		$dir = "";
		$sql = "select default_setting_value from v_default_settings ";
		$sql .= "where default_setting_category = 'switch' ";
		$sql .= "and default_setting_subcategory = 'log' ";
		$sql .= "and default_setting_enabled = 'true' ";
		$result = $this->database->select($sql, null, 'column');
		if (!empty($result)) {
			$dir = $result . '/xml_cdr';
		} else {
			$dir = '/var/log/freeswitch/xml_cdr';
		}
		return $dir;
	}

	private function get_cdr_failed_dir(): string {
		$dir = "";
		$sql = "select default_setting_value from v_default_settings ";
		$sql .= "where default_setting_category = 'cdr' ";
		$sql .= "and default_setting_subcategory = 'failed_directory' ";
		$sql .= "and default_setting_enabled = 'true' ";
		$result = $this->database->select($sql, null, 'column');
		if (!empty($result)) {
			$dir = $result;
		} else {
			$dir = '/var/log/freeswitch/xml_cdr/failed';
		}
		return $dir;
	}

	// load existing files in the xml log dir
	private function load_existing() {
		// Check for files in directory ie. /var/log/freeswitch/xml_cdr/*.xml
		$files = glob($this->watch_dir . '/*' . $this->file_extension);

		// Loop through all files and import one at a time
		$batch_counter = 0;
		foreach ($files as $file) {
			$this->import($file);
			// check for processing files in batches
			if ($this->batch_size > ++$batch_counter) {
				// sleep the required time
				sleep($this->sleep);
				// reset batch counter
				$batch_counter = 0;
			}
		}
	}

	/**
	 * Imports file in to database using the xml_cdr object set in the class
	 * @param string $file
	 */
	private function import(string $file): bool {
		// Ensure file exists
		if (!file_exists($file)) {
			return false;
		}

		// Isolate the filename from the path
		$basename = basename($file);

		// Get the content from the file
		$contents = file_get_contents($file);

		// Point the cdr to the file name
		$this->xml_cdr->file = $basename;

		// Determine if it is 'A' leg or 'B' leg
		$leg = self::leg($basename);

		// Decode the xml string
		self::decode($contents);

		// Parse the xml and insert the data into the db
		if ($this->xml_cdr->xml_array(0, $leg, $contents) === false) {
			// Parsing the xml returned an error of some kind
			self::log("Importing $basename in to the database did not complete successfully", LOG_CRIT);
			$this->move_failed_cdr_record($file);
			return false;
		} else {
			// No need to keep file
			if (file_exists($file)) {
				unlink($file);
			}
		}

		// Success
		return true;
	}

	// Get the leg of the call based on the file name
	private static function leg($basename): string {
		if (substr($basename, 0, 2) == "a_") {
			return "a";
		}
		return "b";
	}

	// Decode xml contents
	private static function decode(&$contents) {
		if (substr($contents, 0, 1) == '%') {
			$contents = urldecode($contents);
		}
	}

	private function loop_events(array $events) {
		self::log("Processing event array", LOG_DEBUG);
		// Iterate through each event
		foreach ($events as $event) {
			// notify we are processing an event
			self::log("Processing Event Mask: {$event['mask']}", LOG_INFO);

			// Check if the event is for a file being written to
			if ($event['mask'] & self::$event_mask) {
				$this->import_file($event['name']);
			}
		}
		self::log("Processing event array complete", LOG_DEBUG);
	}

	// Parse CLI options using getopt()
	private static function parse_cli_options() {

		//short options need to be a string
		$short_options = implode("", array_keys(self::OPTIONS));

		//long options need to be an array
		$long_options = array_unique(array_values(self::OPTIONS));

		//parse the short and long options
		$options = getopt($short_options, $long_options);

		//notify user
		self::log("CLI Options detected: " . implode(",", $options), LOG_INFO);

		//loop through the parsed options
		foreach ($options as $option_key => $option_value) {
			switch ($option_key) {
				case 'v':
				case 'version':
					self::display_version();
					exit();
				case 'h':
				case 'help':
					self::display_help_message();
					exit();
				case 'd':
				case 'debug':
					self::set_debug_level($option_value);
					break;
				case 'w':
				case 'watch':
					self::set_watch_event($option_value);
					break;
				case 'r':
				case 'reload':
					self::send_reload_signal();
					break;
				case 'H':
				case 'hostname':
					self::set_hostname($option_value);
					break;
			}
		}
	}

	/**
	 * Checks the file system for a pid file
	 * @return bool true if pid exists and false if not
	 */
	public static function is_running(): bool {
		return self::get_service_pid() !== false;
	}

	/**
	 * Returns the operating system service PID or false if it is not yet running
	 * @return bool|int PID or false if not running
	 */
	public static function get_service_pid() {
		$file = self::$pid_file;
		if (file_exists($file)) {
			$pid = file_get_contents($file);
			if (function_exists('posix_getsid')) {
				if (posix_getsid($pid) !== false) {
					//return the pid for reloading configuration
					return $pid;
				}
			} else {
				if (file_exists('/proc/' . $pid)) {
					//return the pid for reloading configuration
					return $pid;
				}
			}
		}
		return false;
	}

	/**
	 * Create an operating system PID file removing any existing PID file
	 */
	public static function create_service_pid() {
		// Set the pid filename
		$pid_file = '/var/run/fusionpbx/' . basename(__FILE__, '.php') . '.pid';
		$pid = getmypid();

		// Remove the old pid file
		if (file_exists($pid_file)) {
			unlink($pid_file);
		}

		// Show the details to the user
		self::log("Service   : " . basename(__FILE__, ".php"), LOG_INFO);
		self::log("Process ID: $pid", LOG_INFO);
		self::log("PID File  : $pid_file", LOG_INFO);

		// Save the pid file
		file_put_contents($pid_file, $pid);
	}

	/**
	 * Creates the service directory to store the PID
	 * @throws Exception thrown when the service directory is unable to be created
	 */
	private static function create_service_directory() {
		//make sure the /var/run/fusionpbx directory exists
		if (!file_exists('/var/run/fusionpbx')) {
			$result = mkdir('/var/run/fusionpbx', 0777, true);
			if (!$result) {
				throw new Exception('Failed to create /var/run/fusionpbx');
			}
		}
	}

	private static function create_failed_directory($directory) {
		if (!file_exists($directory)) {
			$result = mkdir($directory, 0777, true);
			if (!$result) {
				throw new \Exception("Unable to create FAILED folder: $directory");
			}
		}
	}

	/**
	 * Show memory usage to the user
	 */
	public static function show_mem_usage() {
		//current memory
		$memory_usage = memory_get_usage();
		//peak memory
		$memory_peak = memory_get_peak_usage();
		self::log('Current memory: ' . round($memory_usage / 1024) . " KB", LOG_INFO);
		self::log('Peak memory: ' . round($memory_peak / 1024) . " KB", LOG_INFO);
	}

	/**
	 * Parses the debug level to an integer and stores it in the class for syslog use
	 * @param string $debug_level Debug level with any of the Linux system log levels
	 */
	private static function set_debug_level(string $debug_level) {
		// Map user input log level to syslog constant
        switch ($debug_level) {
			case '0':
            case 'emergency':
                self::$log_level = LOG_EMERG;	// Hardware failures
                break;
			case '1':
            case 'alert':
                self::$log_level = LOG_ALERT;	// Loss of network connection or a condition that should be corrected immediately
                break;
			case '2':
            case 'critical':
                self::$log_level = LOG_CRIT;	// Condition like low disk space
                break;
			case '3':
            case 'error':
                self::$log_level = LOG_ERR;		// Database query failure, file not found
                break;
			case '4':
            case 'warning':
                self::$log_level = LOG_WARNING;	// Deprecated function usage, approaching resource limits
                break;
			case '5':
            case 'notice':
                self::$log_level = LOG_NOTICE;	// Normal conditions
                break;
			case '6':
            case 'info':
                self::$log_level = LOG_INFO;	// Informational
                break;
			case '7':
            case 'debug':
                self::$log_level = LOG_DEBUG;	// Debugging
                break;
            default:
                self::$log_level = LOG_NOTICE;	// Default to NOTICE if invalid level
        }
	}

	/**
	 * Sets the file watch type mask for inotify events
	 * @param string $watch_event_type The watch event type used by inotify
	 */
	private static function set_watch_event(string $watch_event_type) {
		self::log("Setting watch type", LOG_DEBUG);

		if (array_key_exists((int) $watch_event_type, self::FILE_ACCESS_EVENTS)) {
			self::log("Overriding default watch type to $watch_event_type", LOG_DEBUG);
			self::$event_mask = (int) $watch_event_type;
		} else {
			self::log("Watch type not an integer", LOG_DEBUG);
			foreach (self::FILE_ACCESS_EVENTS as $key => $value) {
				if (in_array($watch_event_type, $value)) {
					self::$event_mask = (int) $key;
					self::log("Overriding default watch type to $key", LOG_DEBUG);
				}
			}
		}
		$text = self::FILE_ACCESS_EVENTS[self::$event_mask][1];
		$code = self::FILE_ACCESS_EVENTS[self::$event_mask][0];
		self::log("Watching folders for: $text ($code)", LOG_INFO);
	}

	private static function run_filesystem_polling() {

		//get the settings
		$settings = new settings();

		//get cdr settings
		//$interval = $setting->get('xml_cdr', '$interval');
		//import the call detail records from HTTP POST or file system
		$cdr = new xml_cdr;

		//get the cdr record
		$xml_cdr_dir = $settings->get('switch', 'log') . '/xml_cdr';

		//loop through
		while (self::$running) {

			//find and process cdr records
			$xml_cdr_array = glob($xml_cdr_dir . '/*.cdr.xml');
			if (!empty($xml_cdr_array)) {
				$i = 0;
				foreach ($xml_cdr_array as $xml_cdr_file) {
					//add debug information
					self::log($xml_cdr_file, LOG_DEBUG);

					//get the content from the file
					$call_details = file_get_contents($xml_cdr_file);

					//process the call detail record
					if (isset($xml_cdr_file) && isset($call_details)) {
						//set the file
						$cdr->file = basename($xml_cdr_file);

						//get the leg of the call and the file prefix
						if (substr(basename($xml_cdr_file), 0, 2) == "a_") {
							self::log('leg a prefix detected', LOG_DEBUG);
							$leg = "a";
						} else {
							self::log('leg b prefix detected', LOG_DEBUG);
							$leg = "b";
						}

						//decode the xml string
						if (substr($call_details, 0, 1) == '%') {
							self::log('url decode need detected', LOG_DEBUG);
							$call_details = urldecode($call_details);
						}

						//parse the xml and insert the data into the db
						$cdr->xml_array($i, $leg, $call_details);
					}

					//limit the number of records process at one time
					if ($i >= 100) {
						self::log('processed max records resetting count', LOG_DEBUG);
						break;
					}

					//increment the value
					$i++;
				}
			}

			self::log('sleeping for ' . 100000, LOG_INFO);
			//sleep for a moment
			usleep(100000);

			self::show_mem_usage();
		}
	}

	/**
	 * Logs to the system log
	 * @param string $message
	 * @param int $level
	 */
	public static function log(string $message, int $level = null) {
        // Use default log level if not provided
		if ($level === null) {
			$level = self::$log_level;
		}

		// Log the message to syslog
        syslog($level, $message);
    }

	/**
	 * Launch the service
	 * @param array $args Command line arguments or other arguments needed when starting the service
	 */
	public static function main() {
		//set the PID file
		self::$pid_file = '/var/run/fusionpbx/' . basename(__FILE__, '.php') . '.pid';

		// Parse the command line arguments
		self::parse_cli_options();

		// Ensure we have only one instance
		if (self::is_running()) {
			self::log("Service already running", LOG_ERR);
			exit();
		}

		// Mark the internal running process tracking to allow graceful shutdown
		self::$running = true;

		// Ensure a hostname is set
		self::$hostname = self::set_hostname();

		// Ensure directory creation for pid location
		self::create_service_directory();

		// Create a process identifier file
		self::create_service_pid();

		// Make sure the inotify php extension is loaded for new methods
		if (function_exists('inotify_init')) {
			// Notify user
			self::log("Starting inotify event listener", LOG_NOTICE);

			// Create the service object
			$service = new xml_cdr_service(new database(), new xml_cdr());

			// Run the program using event listener
			$service->run_event_listener();
		} else {
			// Notify user
			self::log("Starting compatibility filesystem polling", LOG_NOTICE);
			// Run the old method of polling the filesystem
			self::run_filesystem_polling();
		}
	}

	/**
	 * Display the help message to the user for using this service
	 */
	public static function display_help_message() {
		echo "Usage: xml_cdr_service [options]\n";
		echo "\n";
		echo "Options:\n";
		echo "  -h --help                 Show this help message.\n";
		echo "  -v --version              Show the version information and copyright.\n";
		echo "  -r --reload               Reload the settings from database after next event detection. (Only \n";
		echo "                            available when using inotify extension)                             \n";
		echo "  -H --hostname             Set the hostname explicitly.\n";
		echo "  -d<level> --debug <level> Set the debug level between 0 (default) and 3.\n";
		echo "  -w<event> --watch <event> Set the type of integer watch event to detect.\n";
		echo "                            The full documentation of event types can be found at:\n";
		echo "                            https://www.php.net/manual/en/inotify.constants.php\n";
		echo "\n";
	}

	/**
	 * Display version notice
	 */
	public static function display_version() {
		echo "FusionPBX XML CDR Service version " . self::VERSION . "\n";
	}

	public static function display_copyright() {
		echo "FusionPBX\n";
		echo "Version: MPL 1.1\n";
		echo "\n";
		echo "The contents of this file are subject to the Mozilla Public License Version\n";
		echo "1.1 (the \"License\"); you may not use this file except in compliance with\n";
		echo "the License. You may obtain a copy of the License at\n";
		echo "http://www.mozilla.org/MPL/\n";
		echo "\n";
		echo "Software distributed under the License is distributed on an \"AS IS\" basis,\n";
		echo "WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License\n";
		echo "for the specific language governing rights and limitations under the\n";
		echo "License.\n";
		echo "\n";
		echo "The Original Code is FusionPBX\n";
		echo "\n";
		echo "The Initial Developer of the Original Code is\n";
		echo "Mark J Crane <markjcrane@fusionpbx.com>\n";
		echo "Portions created by the Initial Developer are Copyright (C) 2008-2023\n";
		echo "the Initial Developer. All Rights Reserved.\n";
		echo "\n";
		echo "Contributor(s):\n";
		echo "Mark J Crane <markjcrane@fusionpbx.com>\n";
		echo "Tim Fry <tim.fry@hotmail.com>\n";
		echo "\n";
	}

	public static function send_reload_signal() {
		$pid = self::get_service_pid();
		if ($pid === false) {
			self::log("service not running", LOG_EMERG);
		} else {
			posix_kill((int) $pid, SIGHUP);
		}
	}

	public static function set_hostname(?string $hostname = null) {
		if (!empty($hostname)) {
			self::$hostname = $hostname;
		}
		if (empty($hostname)) {
			self::$hostname = urldecode(strtolower(gethostname()));
		}
	}

	private function move_failed_cdr_record(string $file) {
		self::log("Moving failed file: $file to " . $this->failed_dir, LOG_INFO);
		$basename = basename($file);
		if (!rename($file, $this->failed_dir . '/' . $basename)) {
			self::log("Unable to move the failed import to the destination", LOG_ERR);
		}
	}
}

// Ensure we are using the command line
defined('STDIN') or die("Unauthorized\n");

// Increase limits
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

// Load the required framework files
$path = "";
if (file_exists(dirname(__DIR__, 4) . '/resources/require.php')) {
	$path = dirname(__DIR__, 4);
}
elseif (file_exists(dirname(__DIR__, 2) . '/resources/require.php')) {
	$path = dirname(__DIR__, 2);
}
elseif (file_exists('/var/www/fusionpbx/resources/require.php')) {
	$path = '/var/www/fusionpbx';
}
else {
	die("unable to find require.php");
}
// set the current working directory
chdir($path);
// load the framework
require_once 'resources/require.php';

xml_cdr_service::main($argv);
exit();
