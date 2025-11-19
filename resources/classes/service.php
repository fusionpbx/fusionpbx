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
 * Portions created by the Initial Developer are Copyright (C) 2008-2024
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

/**
 * Service class
 *
 * @version 1.00
 * @author  Tim Fry <tim@fusionpbx.com>
 */
abstract class service {

	const VERSION = "1.00";
	/**
	 * current debugging level for output to syslog
	 *
	 * @var int Syslog level
	 */
	protected static $log_level = LOG_NOTICE;
	/**
	 * config object
	 *
	 * @var config config object
	 */
	protected static $config;
	/**
	 * Holds the parsed options from the command line
	 *
	 * @var array
	 */
	protected static $parsed_command_options;
	/**
	 * Cli Options Array
	 *
	 * @var array
	 */
	protected static $available_command_options = [];
	/**
	 * Holds the configuration file location
	 *
	 * @var string
	 */
	protected static $config_file = "";
	/**
	 * Fork the service to it's own process ID
	 *
	 * @var bool
	 */
	protected static $daemon_mode = false;
	/**
	 * Suppress the timestamp
	 * Used to suppress the timestamp in syslog
	 *
	 * @var bool
	 */
	protected static $show_timestamp_log = false;
	/**
	 * Operating System process identification file
	 *
	 * @var string
	 */
	private static $pid_file = "";
	/**
	 * Track the internal loop. It is recommended to use this variable to control the loop inside the run function. See
	 * the example below the class for a more complete explanation
	 *
	 * @var bool
	 */
	protected $running;

	/**
	 * Open a log when created.
	 * <p>NOTE:<br>
	 * This is a protected function so it can not be called using the keyword 'new' outside of this class or a child
	 * class. This is due to the requirement to set signal handlers for the POSIX system outside of the constructor.
	 * PHP seems to have an issue on some versions where setting a signal handler while in the constructor (even
	 * calling another method from the constructor) will fail to register the signal handlers.</p>
	 */
	protected function __construct() {
		openlog('[php][' . self::class . ']', LOG_CONS | LOG_NDELAY | LOG_PID, LOG_DAEMON);
	}

	/**
	 * Shutdown the currently running process gracefully.
	 */
	public static function shutdown() {
		exit();
	}

	/**
	 * Sends a shutdown signal to the running process.
	 */
	public static function send_shutdown() {
		if (self::is_any_running()) {
			self::send_signal(SIGTERM);
		} else {
			die("Service Not Started\n");
		}
	}

	/**
	 * Checks if any service process is running.
	 *
	 * @return bool True if a service process is found, false otherwise
	 */
	public static function is_any_running(): bool {
		return self::get_service_pid() !== false;
	}

	/**
	 * Sends the shutdown signal to the service using a posix signal.
	 * <p>NOTE:<br>
	 * The signal will not be received from the service if the
	 * command is sent from a user that has less privileges then
	 * the running service. For example, if the service is started
	 * by user root and then the command line option '-r' is given
	 * as user www-data, the service will not receive this signal
	 * because the OS will not allow the signal to be passed to a
	 * more privileged user due to security concerns. This would
	 * be the main reason why you must run a 'systemctl' or a
	 * 'service' command as root user. It is possible to start the
	 * service with user www-data and then the web UI would in fact
	 * be able to send the reload signal to the running service.</p>
	 */
	public static function send_signal($posix_signal) {
		$signal_name = "";
		switch ($posix_signal) {
			case 1:   //SIGHUP
			case 10:  //SIGUSR1
				$signal_name = "Reload";
				break;
			case 12:  //SIGUSR2
			case 15:  //SIGTERM
				$signal_name = "Shutdown";
				break;
		}
		$pid = self::get_service_pid();
		if ($pid === false) {
			self::log("service not running", LOG_EMERG);
		} else {
			if (posix_kill((int)$pid, $posix_signal)) {
				echo "Sent $signal_name\n";
			} else {
				$err = posix_strerror(posix_get_last_error());
				echo "Failed to send $signal_name: $err\n";
			}
		}
	}

	/**
	 * Write a standard copyright notice to the console
	 *
	 * @return void
	 */
	public static function display_copyright(): void {
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
		echo "Tim Fry <tim@fusionpbx.com>\n";
		echo "\n";
	}

	/**
	 * Sends a reload signal to running services, or exits if no service is running.
	 *
	 * @return void
	 */
	public static function send_reload() {
		if (self::is_any_running()) {
			self::send_signal(10);
		} else {
			die("Service Not Started\n");
		}
		exit();
	}

	/**
	 * Set to foreground when started
	 */
	public static function enable_daemon_mode() {
		self::$daemon_mode = true;
		self::$show_timestamp_log = false;
	}

	// register signal handlers

	/**
	 * Set the configuration file to be used by FusionPBX.
	 *
	 * @param string $file The path to the configuration file (default: '/etc/fusionpbx/config.conf')
	 *
	 * @return void
	 */
	public static function set_config_file(string $file = '/etc/fusionpbx/config.conf') {
		if (empty(self::$config_file)) {
			self::$config_file = $file;
		}
		self::$config = new config(self::$config_file);
	}

	/**
	 * Appends a command option to the list of available options.
	 *
	 * @param command_option $option The command option to append.
	 *
	 * @return int The index where the option was appended.
	 */
	public static function append_command_option(command_option $option): int {
		$index = count(self::$available_command_options);
		self::$available_command_options[$index] = $option->to_array();
		return $index;
	}

	/**
	 * Add a new command option to the available options list.
	 *
	 * @param string  $short_option      Short option (e.g., 'h' for '-h')
	 * @param string  $long_option       Long option (e.g., 'help' for '--help')
	 * @param string  $description       Brief description of the option
	 * @param string  $short_description Short description of the short option (optional)
	 * @param string  $long_description  Long description of the long option (optional)
	 * @param mixed[] ...$callback       Callback function(s) associated with this option
	 *
	 * @return int Index of the newly added command option in self::$available_command_options array
	 */
	public static function add_command_option(string $short_option, string $long_option, string $description, string $short_description = '', string $long_description = '', ...$callback): int {
		//use the option as the description if not filled in
		if (empty($short_description)) {
			$short_description = '-' . $short_option;
			if (str_ends_with($short_option, ':')) {
				$short_description .= " <setting>";
			}
		}
		if (empty($long_description)) {
			$long_description = '-' . $long_option;
			if (str_ends_with($long_option, ':')) {
				$long_description .= " <setting>";
			}
		}
		$index = count(self::$available_command_options);
		self::$available_command_options[$index]['short_option'] = $short_option;
		self::$available_command_options[$index]['long_option'] = $long_option;
		self::$available_command_options[$index]['description'] = $description;
		self::$available_command_options[$index]['short_description'] = $short_description;
		self::$available_command_options[$index]['long_description'] = $long_description;
		self::$available_command_options[$index]['functions'] = $callback;
		return $index;
	}

	/**
	 * Creates a new instance of the service class, initializing it and returning the object.
	 *
	 * @return self A new instance of the service class.
	 */
	public static function create(): self {
		//can only start from command line
		defined('STDIN') or die('Unauthorized');

		//parse the cli options and store them statically
		self::parse_service_command_options();

		//fork process
		if (self::$daemon_mode) {
			//force launching in a separate process
			if ($pid = pcntl_fork()) {
				exit;
			}

			if ($cid = pcntl_fork()) {
				exit;
			}
		}

		//create the config object if not already created
		if (self::$config === null) {
			self::$config = new config(self::$config_file);
		}

		//get the name of child object
		$class = self::base_class_name();

		//create the child object
		$service = new $class();

		//initialize the service
		$service->init();

		//return the initialized object
		return $service;
	}

	/**
	 * Parse service command options.
	 *
	 * This method ensures that we have a PID file and parses the short and long options from the command line.
	 * It then logs the detected options at the DEBUG level and calls the corresponding functions to handle them.
	 *
	 * @return void
	 */
	protected static function parse_service_command_options(): void {

		//ensure we have a PID so that reload and exit send commands work
		if (empty(self::$pid_file)) {
			self::$pid_file = self::get_pid_filename();
		}

		//base class short options
		self::$available_command_options = self::base_command_options();

		//get the options from the child class
		static::set_command_options();

		//collapse short options to a string
		$short_options = self::get_short_options();

		//isolate long options
		$long_options = self::get_long_options();

		//parse the short and long options
		$options = getopt($short_options, $long_options);

		//make the options available to the child object
		if ($options !== false) {
			self::$parsed_command_options = $options;
		} else {
			//make sure the command_options are reset
			self::$parsed_command_options = [];
			//if the options are empty there is nothing left to do
			return;
		}

		//notify user
		self::log("CLI Options detected: " . implode(",", self::$parsed_command_options), LOG_DEBUG);

		//loop through the parsed options given on the command line
		foreach ($options as $option_key => $option_value) {

			//get the function responsible for handling the cli option
			$funcs = self::get_user_callbacks_from_available_options($option_key);

			//ensure it was found before we take action
			if (!empty($funcs)) {
				//check for more than one function to be called is permitted
				if (is_array($funcs)) {
					//call each one
					foreach ($funcs as $func) {
						//use the best method to call the function
						self::call_function($func, $option_value);
					}
				} else {
					//single function call
					self::call_function($func, $option_value);
				}
			}
		}
	}

	//
	//
	//

	/**
	 * Returns an array of base command options.
	 *
	 * This method constructs an array of help options for the command line interface,
	 * including short and long option descriptions, functions that these options
	 * trigger, and other metadata. The resulting array can be used to provide help
	 * messages or determine which functions are available based on the input.
	 *
	 * @return array An associative array where each key represents a help option and
	 *               its corresponding value is an associative array containing
	 *               short_option, long_option, description, short_description,
	 *               long_description, and functions keys.
	 */
	private static function base_command_options(): array {
		//put the display for help in an array so we can calculate width
		$help_options = [];
		$index = 0;
		$help_options[$index]['short_option'] = 'v';
		$help_options[$index]['long_option'] = 'version';
		$help_options[$index]['description'] = 'Show the version information';
		$help_options[$index]['short_description'] = '-v';
		$help_options[$index]['long_description'] = '--version';
		$help_options[$index]['functions'][] = 'display_version';
		$help_options[$index]['functions'][] = 'shutdown';
		$index++;
		$help_options[$index]['short_option'] = 'h';
		$help_options[$index]['long_option'] = 'help';
		$help_options[$index]['description'] = 'Show the version and help message';
		$help_options[$index]['short_description'] = '-h';
		$help_options[$index]['long_description'] = '--help';
		$help_options[$index]['functions'][] = 'display_version';
		$help_options[$index]['functions'][] = 'display_help_message';
		$help_options[$index]['functions'][] = 'shutdown';
		$index++;
		$help_options[$index]['short_option'] = 'a';
		$help_options[$index]['long_option'] = 'about';
		$help_options[$index]['description'] = 'Show the version and copyright information';
		$help_options[$index]['short_description'] = '-a';
		$help_options[$index]['long_description'] = '--about';
		$help_options[$index]['functions'][] = 'display_version';
		$help_options[$index]['functions'][] = 'display_copyright';
		$help_options[$index]['functions'][] = 'shutdown';
		$index++;
		$help_options[$index]['short_option'] = 'r';
		$help_options[$index]['long_option'] = 'reload';
		$help_options[$index]['description'] = 'Reload settings for an already running service';
		$help_options[$index]['short_description'] = '-r';
		$help_options[$index]['long_description'] = '--reload';
		$help_options[$index]['functions'][] = 'send_reload';
		$index++;
		$help_options[$index]['short_option'] = 'd:';
		$help_options[$index]['long_option'] = 'debug:';
		$help_options[$index]['description'] = 'Set the syslog level between 0 (EMERG) and 7 (DEBUG). 5 (INFO) is default';
		$help_options[$index]['short_description'] = '-d <level>';
		$help_options[$index]['long_description'] = '--debug <level>';
		$help_options[$index]['functions'][] = 'set_debug_level';
		$index++;
		$help_options[$index]['short_option'] = 'c:';
		$help_options[$index]['long_option'] = 'config:';
		$help_options[$index]['description'] = 'Full path and file name of the configuration file to use. /etc/fusionpbx/config.conf or /usr/local/etc/fusionpbx/config.conf on FreeBSD is default';
		$help_options[$index]['short_description'] = '-c <path>';
		$help_options[$index]['long_description'] = '--config <path>';
		$help_options[$index]['functions'][] = 'set_config_file';
		$index++;
		$help_options[$index]['short_option'] = 'f';
		$help_options[$index]['long_option'] = 'daemon';
		$help_options[$index]['description'] = 'Start the process as a daemon. (Also known as forking)';
		$help_options[$index]['short_description'] = '-f';
		$help_options[$index]['long_description'] = '--daemon';
		$help_options[$index]['functions'][] = 'enable_daemon_mode';
		$index++;
		$help_options[$index]['short_option'] = '';
		$help_options[$index]['long_option'] = 'show-timestamp';
		$help_options[$index]['description'] = 'Enable the timestamp when logging';
		$help_options[$index]['short_description'] = '';
		$help_options[$index]['long_description'] = '--show-timestamp';
		$help_options[$index]['functions'][] = 'show_timestamp';
		$index++;
		$help_options[$index]['short_option'] = 'x';
		$help_options[$index]['long_option'] = 'exit';
		$help_options[$index]['description'] = 'Exit the service gracefully';
		$help_options[$index]['short_description'] = '-x';
		$help_options[$index]['long_description'] = '--exit';
		$help_options[$index]['functions'][] = 'send_shutdown';
		$help_options[$index]['functions'][] = 'shutdown';
		return $help_options;
	}

	/**
	 * Called when the display_help_message is run in the base class for extra command line parameter explanation
	 */
	abstract protected static function set_command_options();

	/**
	 * Extracts the short options from the cli options array and returns a string. The resulting string must
	 * return a single string with all options in the string such as 'rxc:'.
	 * This can be overridden by the child class.
	 *
	 * @return string
	 */
	protected static function get_short_options(): string {
		return implode('', array_map(function ($option) {
			return $option['short_option'];
		}, self::$available_command_options));
	}

	/**
	 * Extracts the long options from the cli options array and returns an array. The resulting array must
	 * return a single dimension array with an integer indexed key but does not have to be sequential order.
	 * This can be overridden by the child class.
	 *
	 * @return array
	 */
	protected static function get_long_options(): array {
		return array_map(function ($option) {
			return $option['long_option'];
		}, self::$available_command_options);
	}

	/**
	 * Retrieves the callback functions associated with the cli options array.
	 *
	 * @param string $set_option The set option to match against available options
	 *
	 * @return array An array of callback functions that need to be called for the matched option
	 */
	protected static function get_user_callbacks_from_available_options(string $set_option): array {
		//match the available option to the set option and return the callback function that needs to be called
		foreach (self::$available_command_options as $option) {
			$short_option = $option['short_option'] ?? '';
			if (str_ends_with($short_option, ':')) {
				$short_option = rtrim($short_option, ':');
			}
			$long_option = $option['long_option'] ?? '';
			if (str_ends_with($long_option, ':')) {
				$long_option = rtrim($long_option, ':');
			}
			if ($short_option === $set_option ||
				$long_option === $set_option) {
				return $option['functions'] ?? [$option['function']] ?? [];
			}
		}
		return [];
	}

	/**
	 * Calls a function using the best suited PHP method.
	 *
	 * @param string $function The name of the function to call, either as a string or an instance of Closure.
	 * @param mixed  $args     An array of arguments to pass to the called function.
	 *
	 * @return void
	 */
	private static function call_function($function, $args) {
		if ($function === 'exit') {
			//check for exit
			exit($args);
		} elseif ($function instanceof Closure || function_exists($function)) {
			//globally available function or closure
			$function($args);
		} else {
			static::$function($args);
		}
	}

	/**
	 * Sets the following:
	 *   - execution time to unlimited
	 *   - location for PID file
	 *   - parses CLI options
	 *   - ensures folder structure exists
	 *   - registers signal handlers
	 */
	private function init() {

		// Increase limits
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '512M');

		//set the PID file
		self::$pid_file = self::get_pid_filename();

		//register the shutdown function
		register_shutdown_function([$this, 'shutdown']);

		// Ensure we have only one instance
		if (self::is_any_running()) {
			self::log("Service already running", LOG_ERR);
			exit();
		}

		// Ensure directory creation for pid location
		$this->create_service_directory();

		// Create a process identifier file
		$this->create_service_pid();

		// Set the signal handlers for reloading
		$this->register_signal_handlers();

		// We are now considered running
		$this->running = true;
	}

	/**
	 * Creates the service directory to store the PID
	 *
	 * @return void
	 * @throws Exception thrown when the service directory is unable to be created
	 */
	private function create_service_directory() {
		//make sure the /var/run/fusionpbx directory exists
		if (!file_exists('/var/run/fusionpbx')) {
			$result = mkdir('/var/run/fusionpbx', 0777, true);
			if (!$result) {
				throw new Exception('Failed to create /var/run/fusionpbx');
			}
		}
	}

	/**
	 * Create an operating system PID file removing any existing PID file
	 */
	private function create_service_pid() {
		// Set the pid filename
		$basename = basename(self::$pid_file, '.pid');
		$pid = getmypid();

		// Remove the old pid file
		if (file_exists(self::$pid_file)) {
			if (is_writable(self::$pid_file)) {
				unlink(self::$pid_file);
			} else {
				throw new RuntimeException("Unable to write to PID file " . self::$pid_file, 73); //Unix error code 73 - unable to write/create file
			}
		}

		// Show the details to the user
		self::log("Starting up...");
		self::log("Mode      : " . (self::$daemon_mode ? "Daemon" : "Foreground"), LOG_INFO);
		self::log("Service   : $basename", LOG_INFO);
		self::log("Process ID: $pid", LOG_INFO);
		self::log("PID File  : " . self::$pid_file, LOG_INFO);
		self::log("Log level : " . self::log_level_to_string(self::$log_level), LOG_INFO);
		self::log("Timestamps: " . (self::$show_timestamp_log ? "Yes" : "No"), LOG_INFO);

		// Save the pid file
		$success = file_put_contents(self::$pid_file, $pid);
		if ($success === false) {
			throw new RuntimeException("Failed writing to PID file " . self::$pid_file, 74); //Unix error code 74 - I/O error
		}
	}

	/**
	 * Registers signal handlers for handling various system signals.
	 *
	 * This method sets up event listeners for the following signals:
	 * - SIGUSR1 and SIGHUP: reload the service settings from the database
	 * - SIGUSR2 and SIGTERM: shut down the service
	 *
	 * @return void
	 */
	private function register_signal_handlers() {
		// Allow the calls to be made while the main loop is running
		pcntl_async_signals(true);

		// A signal listener to reload the service for any config changes in the database
		pcntl_signal(SIGUSR1, [$this, 'reload_settings']);
		pcntl_signal(SIGHUP, [$this, 'reload_settings']);

		// A signal listener to stop the service
		pcntl_signal(SIGUSR2, [self::class, 'shutdown']);
		pcntl_signal(SIGTERM, [self::class, 'shutdown']);
	}

	/**
	 * Display version notice
	 */
	abstract protected static function display_version(): void;

	/**
	 * Parses the debug level to an integer and stores it in the class for syslog use
	 *
	 * @param string $debug_level Debug level with any of the Linux system log levels
	 *
	 * @return void
	 */
	protected static function set_debug_level(string $debug_level) {
		// Map user input log level to syslog constant
		switch ($debug_level) {
			case '0':
			case 'emergency':
				self::$log_level = LOG_EMERG; // Hardware failures
				break;
			case '1':
			case 'alert':
				self::$log_level = LOG_ALERT; // Loss of network connection or a condition that should be corrected immediately
				break;
			case '2':
			case 'critical':
				self::$log_level = LOG_CRIT; // Condition like low disk space
				break;
			case '3':
			case 'error':
				self::$log_level = LOG_ERR;  // Database query failure, file not found
				break;
			case '4':
			case 'warning':
				self::$log_level = LOG_WARNING; // Deprecated function usage, approaching resource limits
				break;
			case '5':
			case 'notice':
				self::$log_level = LOG_NOTICE; // Normal conditions
				break;
			case '6':
			case 'info':
				self::$log_level = LOG_INFO; // Informational
				break;
			case '7':
			case 'debug':
				self::$log_level = LOG_DEBUG; // Debugging
				break;
			default:
				self::$log_level = LOG_NOTICE; // Default to NOTICE if invalid level
		}

		// When we are using LOG_DEBUG there is a high chance we are logging to the console
		// directly without systemctl so enable the timestamps by default
		if (self::$log_level === LOG_DEBUG && !self::$daemon_mode) {
			self::show_timestamp();
		}
	}

	/**
	 * Enables timestamp logging.
	 *
	 * @return void
	 */
	public static function show_timestamp() {
		self::$show_timestamp_log = true;
	}

	/**
	 * Logs the current and peak memory usage of the application at the INFO level.
	 *
	 * @return void
	 */
	protected static function show_mem_usage() {
		//current memory
		$memory_usage = memory_get_usage();
		//peak memory
		$memory_peak = memory_get_peak_usage();
		self::log('Current memory: ' . round($memory_usage / 1024) . " KB", LOG_INFO);
		self::log('Peak memory: ' . round($memory_peak / 1024) . " KB", LOG_INFO);
	}

	/**
	 * Displays a help message for the available command options.
	 */
	protected static function display_help_message(): void {
		//get the classname of the child class
		$class_name = self::base_class_name();

		//get the widest options for proper alignment
		$width_short = max(array_map(function ($arr) {
			return strlen($arr['short_description'] ?? '');
		}, self::$available_command_options));
		$width_long = max(array_map(function ($arr) {
			return strlen($arr['long_description'] ?? '');
		}, self::$available_command_options));

		//display usage help using the class name of child
		echo "Usage: php $class_name [options]\n";

		//display the options aligned to the widest short and long options
		echo "Options:\n";
		foreach (self::$available_command_options as $option) {
			printf("%-{$width_short}s %-{$width_long}s %s\n",
				$option['short_description'],
				$option['long_description'],
				$option['description']
			);
		}
	}

	/**
	 * Returns only the name of the class without namespace
	 *
	 * @return string base class name
	 */
	protected static function base_class_name(): string {
		$class_and_namespace = explode('\\', static::class);
		return array_pop($class_and_namespace);
	}

	//
	// Options built-in to the base service class. These can be overridden with the child class
	// or they can be extended using the array
	//

	/**
	 * Method to start the child class internal loop
	 */
	abstract public function run(): int;

	/**
	 * Ensures the correct PID file is unlinked when this process terminates.
	 *
	 * This method is called automatically by PHP when an object's reference count reaches zero,
	 * or when it's explicitly destroyed using unset(). It checks if the process is still running
	 * and unlinks the corresponding PID file. Finally, it ensures that any open log connections
	 * are properly closed before the script exits.
	 *
	 * @return void
	 */
	public function __destruct() {
		//ensure we unlink the correct PID file if needed
		if (self::is_running()) {
			unlink(self::$pid_file);
			self::log("Initiating Shutdown...", LOG_NOTICE);
			$this->running = false;
		}
		//this should remain the last statement to execute before exit
		closelog();
	}

	/**
	 * Checks the file system for a pid file that matches the process ID from this running instance
	 *
	 * @return bool true if pid exists and false if not
	 */
	public static function is_running(): bool {
		return posix_getpid() === self::get_service_pid();
	}

	/**
	 * Returns the process ID of the service if it exists and is running.
	 *
	 * @return int|false The process ID of the service, or false if it does not exist or is not running.
	 */
	protected static function get_service_pid() {
		if (file_exists(static::get_pid_filename())) {
			$pid = file_get_contents(static::get_pid_filename());
			if (function_exists('posix_getsid')) {
				if (posix_getsid($pid) !== false) {
					//return the pid for reloading configuration
					return intval($pid);
				}
			} else {
				if (file_exists('/proc/' . $pid)) {
					//return the pid for reloading configuration
					return intval($pid);
				}
			}
		}
		return false;
	}

	/**
	 * Returns the filename of the PID file.
	 *
	 * The PID file is located in /var/run/fusionpbx/ and its name is based on the base file name.
	 *
	 * @return string The path to the PID file
	 */
	public static function get_pid_filename(): string {
		return '/var/run/fusionpbx/' . self::base_file_name() . '.pid';
	}

	/**
	 * Returns a file safe class name with \ from namespaces converted to _
	 *
	 * @return string file safe name
	 */
	protected static function base_file_name(): string {
		return str_replace('\\', "_", static::class);
	}

	/**
	 * Logs to the system log or console when running in foreground
	 *
	 * @param string $message Message to display in the system log or console when running in foreground
	 * @param int    $level   (Optional) Level to use for logging to the console or daemon. Default value is LOG_NOTICE
	 *
	 * @return void
	 */
	protected static function log(string $message, int $level = LOG_NOTICE) {
		// Check if we need to show the message
		if ($level <= self::$log_level) {
			// When not in daemon mode we log to console directly
			if (!self::$daemon_mode) {
				$level_as_string = self::log_level_to_string($level);
				if (!self::$show_timestamp_log) {
					echo "[$level_as_string] $message\n";
				} else {
					$time = date('Y-m-d H:i:s');
					echo "[$time][$level_as_string] $message\n";
				}
			} else {
				// Log the message to syslog
				syslog($level, 'fusionpbx[' . posix_getpid() . ']: [' . static::class . '] ' . $message);
			}
		}
	}

	/**
	 * Converts a log level integer to its corresponding string representation.
	 *
	 * @param int $level The log level as an integer, defaults to LOG_NOTICE (5).
	 *
	 * @return string The log level as a string.
	 */
	private static function log_level_to_string(int $level = LOG_NOTICE): string {
		switch ($level) {
			case 0:
				return 'EMERGENCY';
			case 1:
				return 'ALERT';
			case 2:
				return 'CRITICAL';
			case 3:
				return 'ERROR';
			case 4:
				return 'WARNING';
			case 5:
				return 'NOTICE';
			case 6:
				return 'INFO';
			case 7:
				return 'DEBUG';
			default:
				return 'INFO';
		}
	}

	/**
	 * Child classes must provide a mechanism to reload settings
	 */
	abstract protected function reload_settings(): void;

	/**
	 * Logs a message at the DEBUG level.
	 *
	 * @param string $message The debug message to be printed. Defaults to an empty string.
	 *
	 * @return void
	 */
	protected function debug(string $message = ''): void {
		self::log($message, LOG_DEBUG);
	}

	/**
	 * Logs a message at the INFO level.
	 *
	 * @param string $message The message to be logged. Defaults to an empty string.
	 *
	 * @return void
	 */
	protected function info(string $message = ''): void {
		self::log($message, LOG_INFO);
	}

	/**
	 * Logs a message at the NOTICE level.
	 *
	 * @param string $message The message to be logged. Defaults to an empty string.
	 *
	 * @return void
	 */
	protected function notice(string $message = ''): void {
		self::log($message, LOG_NOTICE);
	}

	/**
	 * Logs a message at the WARNING level.
	 *
	 * @param string $message The message to be logged. Defaults to an empty string.
	 *
	 * @return void
	 */
	protected function warning(string $message = ''): void {
		self::log($message, LOG_WARNING);
	}

	/**
	 * Logs a message at the ERROR level.
	 *
	 * @param string $message The message to be logged. Defaults to an empty string.
	 *
	 * @return void
	 */
	protected function error(string $message = ''): void {
		self::log($message, LOG_ERR);
	}

	/**
	 * Logs a message at the CRIT (critical) level.
	 *
	 * @param string $message The message to be logged. Defaults to an empty string.
	 *
	 * @return void
	 */
	protected function critical(string $message = ''): void {
		self::log($message, LOG_CRIT);
	}

	/**
	 * Logs a message at the ALERT level.
	 *
	 * @param string $message The message to be logged. Defaults to an empty string.
	 *
	 * @return void
	 */
	protected function alert(string $message = ''): void {
		self::log($message, LOG_ALERT);
	}

	/**
	 * Logs a message at the EMERG (emergency) level.
	 *
	 * @param string $message The message to be logged. Defaults to an empty string.
	 *
	 * @return void
	 */
	protected function emergency(string $message = ''): void {
		self::log($message, LOG_EMERG);
	}
}

/*
 * Example
 *
 * The child_service class must be used to demonstrate the base_service because base_service is abstract. This means that you
 * cannot use the syntax of:
 *   $service = new service();		//throws fatal error
 *   $service->run();				//never reaches this statement
 *
 * Instead, you must use a class that will extend the service class like this:
 *   $service = child_service::create();
 *   $service->run();
 * (make the code below more readable by putting)
 * ( in the '/' line below to complete the comment section )
 *

//
// A class that extends base_service must implement 4 functions:
//   - run()               This is the entry point called from an external source after the create method is called
//   - reload_settings     This is called when the CLI option -r or --reload is used
//   - display_version
//   - command_options
//
// Using the class below use the commands
//   $simple_example = simple_example::create();
//   $simple_example->run();
//
// This will create the class and then run it once and exit with a success code.
//
//
class simple_example extends service {

	protected function reload_settings(): void {

	}

	protected static function display_version(): void {
		echo "Version 1.00\n";
	}

	protected static function set_command_options() {

	}

	public function run(): int {
		echo "Successfully ran child service\n";
		echo "Try command line options -h or -v\n";
		return 0;
	}
}

//*/
/*
//
// This class is more complex in that it will continue to run with a connection to a database
//
// The service class is divided between static and non-static methods. The static methods are
// used and called before the service is run allowing the CLI options to be read and parsed
// before the object is initialized. This allows for configuration options to be available
// when the child class is first started up. Keep in mind that these are called statically
// so that all callback functions declared in the cli options must be static.
//
class child_service extends service {

	//
	// Using a version constant is ideal for tracking and reporting
	//
	const CHILD_SERVICE_VERSION = '1.00';

	//
	// The parent service does not create a database connection as the child service may not need it. This example
	// demonstrates how the config object is passed from the parent and then used in the child service to connect
	// to other resources or use other settings the base class loaded so the child class automatically inherits.
	//
	private $database;

	// This example uses a settings object to demonstrate how the config is passed through to the child class
	// and is then used again in the reload_settings to demonstrate how the settings could be reloaded
	// with changes in the configuration, database connection, and default settings without the need to create
	// new instances of the config object.
	private $settings;

	//
	// This function is required from the base service class because it is used when the reload command line option is used
	//
	protected function reload_settings(): void {
		//informing the user in this example is simple but can use the parent class log functions
		echo "Reloading settings\n";

		//
		// Reload the configuration file
		//
		self::$config->read();

		//
		// If services have their own configuration file that was passed in using the -c or --config option, the options
		// would be available here as well to the child class
		// By allowing the config file to be specified, it is possible for services to have a configuration specific to them
		// while it could still be possible to allow access to the original making it very flexible with a wide degree of
		// choices.
		//
		// For example, specifying a configuration file that could be used for an archive or backup server would allow
		// the backup service to connect to another system remotely.
		//
		// It could also be used to separate the web configuration from system services to keep them organized and allow for
		// configuration settings to be available should the database fail. One possible scenario where this could be useful
		// is to send an email if the database stops responding. Currently, this is not possible as the database class uses
		// the 'die' command to immediately exit. I think it would be good to remove that and instead set the error message
		// to be something that would reflect the error allowing a system service to detect and even possibly correct that.
		//
		$alert_email = self::$config->get('alert_email', '');
		$smtp_host = self::$config->get('smtp_host', '');
		$smtp_port = self::$config->get('smtp_port', '');

		//
		// Ensure the database is connected with the new configuration parameters
		//
		$this->database->connect();

		//
		// The reload settings here completes the chain
		//
		$this->settings->reload();

	}

	//
	// This run function is required as it is called to launch child_service. This
	// is the entry point for the child class.
	//
	public function run(): int {

		//
		// Create the database object once passing a reference to the config object
		//
		$this->database = new database(['config' => self::$config]);

		//
		// Create the settings object using the database connection
		//
		$this->settings = new settings(['database' => $this->database]);

		//
		// In this example I have used the reload_settings because it is required by the parent class
		// whenever the '-r' or '--reload' option is given on the CLI. The base class is responsible for
		// parsing the information given on the CLI. Whenever the base class detects a '-r' option, the
		// reload_settings method in the child class is called. This gives the responsibility to the the
		// child class to reload any settings that might be needed during long execution of the service
		// without stopping and starting the service. The method is called here to initialize any and all
		// objects within the child service.
		//
		$this->reload_settings();

		//
		// The $running property is declared in the base service class as a boolean and it is responsible
		// to enable this so that the child class can run. The base service class will set this to false
		// if it receives a shutdown command from either the OS, PHP, or a posix signal allowing the child
		// class to respond or clean up after the while loop.
		//
		while($this->running) {
			//
			// This is where the actual heart of the code for the new service will be created
			//
			echo "Doing something..." . date("Y-m-d H:i:s") . "\n";
			sleep(1);
		}


		//
		// Returning a non-zero value would indicate there was an issue. Here we return zero to indicate graceful shutdown.
		//
		return 0;
	}

	//
	// This is the version that will be displayed when the option '-v' or '--version' is used on the command line.
	// This run function is required
	//
	protected static function display_version(): void {
		echo "Child service example version " . self::CHILD_SERVICE_VERSION . "\n";
	}

	//
	// set_command_options can either add to or replace options. Replacing the base options would allow an override for default behaviour.
	// This run function is required
	//
	protected static function set_command_options() {

		//
		// The options below are added to the CLI options and displayed whenever the -h or --help option is used.
		// There are multiple methods are used to suite the style of the creator
		//

		//
		// The callbacks set here are used to demonstrate multiple calls can be used
		//

		//using the parameter in the function
		self::add_command_option(
			't:'
			, 'template:'
			, 'Full path and file name of the template file to use'
			, '-t <path>'
			, '--template <path>'
			, ['set_template_path']
		);
		//using a container object
		self::append_command_option(command_option::new()
			->short_option('n')
			->long_option('null')
			->description('This option is to demonstrate using a cli object to create cli options')
			->functions(['null_function_method'])
		);
		//using an array of key/value pairs
		self::append_command_option(command_option::new([
			'short_option' => 'z:'
			,'long_option' => 'zero:'
			,'description' => 'This has zero effect on behavior'
			,'function' => 'call_single_function'
		]));

		//
		// These options are here but are commented out to allow the functionality to still exist in the parent
		//
//
//		//replace cli options in the parent class using array
//		$index = 0;
//		$arr_options = [];
//		$arr_options[$index]['short_option'] = 'z';
//		$arr_options[$index]['long_option'] = 'zero';
//		$arr_options[$index]['description'] = 'This has zero effect on behavior';
//		$arr_options[$index]['short_description'] = '-z';
//		$arr_options[$index]['long_description'] = '--zero';
//		$arr_options[$index]['function'][] = 'call_single_function';
//		self::$available_command_options = $arr_options;
//
//		//replace all cli options using container object
//		$arr_options = [];
//		self::$available_command_options = [];
//		$arr_options[0] = command_option::new()
//			->short_option('z')
//			->short_description('-z')
//			->function('call_a_function')
//			->function('call_another_function_after_first')
//			->description('This option does nothing')
//			->to_array();
//
//		$arr_options[1] = command_option::new([
//			'short_option' => 'z'
//			,'long_option' => '--zero'
//			,'description' => 'This option does nothing'
//			,'functions' => ['call_a_function', 'call_another_function']
//		])->to_array();
		//self::$available_command_options = $arr_options;
	}
} // class child_service

//*/

/*
//
// Standard includes do not apply for the base class because the require.php has included many other php files. These other files
// or objects may not be required for some services. Thus, only the config is required for base_service. Child services may then
// create a database class and use it by passing the config object to the database constructor. This is why the 'require.php' is
// left out of the initial setup class.
//

// Use the auto_loader to find any classes needed so we don't have a lot of include statements
// In this example, the auto_loader should not be using the PROJECT_ROOT or any other defined constants
// because they are not needed in the initial stage of loading
require_once __DIR__ . '/auto_loader.php';

// We don't need to ever reference the object so don't assign a variable. It
// would be a good idea to remove the auto_loader as a class declaration so
// that there would only need to be one line. It seems illogical to have an
// object that never needs to be referenced.
new auto_loader();

// The base_service class has a 'protected' constructor, meaning you are not able to use "new" to create the object. Instead, you
// must use the 'create' static method to create an object. This technique is employed because some PHP versions have an issue with
// registering signal listeners in the constructor. See the link https://www.php.net/manual/en/function.pcntl-signal.php in the user
// comments section.
// The child_service class does not override the parent constructor so parent constructor is used. If the child_service class does
// have a constructor then the child class must call:
//   parent::__construct($config);
// as the first line of the child constructor. This is because the parent constructor uses the config class. This also means
// that the child class must receive the config object in the constructor as a minimum.
$service = child_service::create();

// The run class is declared as abstract in the parent. So the child class must have one.
$service->run();
//*/
