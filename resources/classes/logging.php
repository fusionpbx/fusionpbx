<?php

/**
 * Logging class:
 * - constructor requires the full file name and path for the log file. if it
 * does not exist php will automatically try and create it. The log file will
 * remain open for the life cycle of the object to improve performance.
 * - message is written with the following format: [d/M/Y:H:i:s] (script name) message.
 * - log file is closed when the object is destroyed.
 */
class logging {

	// declare log file and file pointer as private properties
	private $fp;
	private $debug_func;
	private $debug_line;
	private $debug_file;
	private $debug_class;

	/**
	 * Initializes a new instance of this class.
	 *
	 * Opens a file in append mode and sets it as the output stream for subsequent operations.
	 *
	 * @param string $filename_and_path The path to the file to be opened.
	 *
	 * @throws Exception If there is an error opening the file.
	 */
	public function __construct(string $filename_and_path) {
		//init values
		$this->clear_debug();

		try {
			//open file in append mode
			$this->fp = fopen($filename_and_path, 'a');
		} catch (Exception $ex) {
			//send the error to the caller
			throw $ex;
		}
	}

	/**
	 * Clear debug settings
	 *
	 * @return void
	 */
	private function clear_debug() {
		$this->debug_line = null;
		$this->debug_file = null;
		$this->debug_func = null;
		$this->debug_class = null;
	}

	/**
	 * Clean up any resources held by this object on destruction.
	 *
	 * @throws Exception if an error occurs during flushing or closing of the file pointer.
	 */
	public function __destruct() {
		try {
			$this->flush();
		} catch (Exception $ex) {
			//do nothing
		} finally {
			//close the file
			if (is_resource($this->fp)) {
				fclose($this->fp);
			}
		}
	}

	// write message to the log file

	/**
	 * Ensure all data arrives on disk
	 *
	 * @return void
	 * @throws Exception
	 */
	public function flush() {
		try {
			//ensure everything arrives on disk
			if (is_resource($this->fp)) {
				fflush($this->fp);
			}
		} catch (Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * Set or retrieve the class name for debugging purposes.
	 *
	 * @param string|null $debug_class The class name to set for debugging. If null, returns the current debug class.
	 *
	 * @return object This object instance for chaining.
	 */
	public function debug_class(?string $debug_class = null) {
		if (func_num_args() > 0) {
			$this->debug_class = $debug_class;
			return $this;
		}
		return $this->debug_class;
	}

	/**
	 * Set or retrieve the current debug line
	 *
	 * @param string|null $debug_line The new debug line to set, or null to clear it
	 *
	 * @return object|string The instance itself if a new value was provided, otherwise the current debug line as a
	 *                       string
	 */
	public function debug_line(?string $debug_line = null) {
		if (func_num_args() > 0) {
			$this->debug_line = $debug_line;
			return $this;
		}
		return $this->debug_line;
	}

	/**
	 * Sets or retrieves the current debug function.
	 *
	 * If a string argument is provided, it sets the current debug function. If no argument is provided,
	 * it returns the current debug function.
	 *
	 * @param string|null $debug_func The debug function to set (optional)
	 *
	 * @return $this Self-reference for chaining
	 */
	public function debug_func(?string $debug_func = null) {
		if (func_num_args() > 0) {
			$this->debug_func = $debug_func;
			return $this;
		}
		return $this->debug_func;
	}

	/**
	 * Set or retrieve the path to a debug file.
	 *
	 * @param string|null $debug_file Path to the debug file (optional)
	 *
	 * @return object|string The current object if setting the debug file, otherwise the current debug file path
	 */
	public function debug_file(?string $debug_file = null) {
		if (func_num_args() > 0) {
			$this->debug_file = $debug_file;
			return $this;
		}
		return $this->debug_file;
	}

	/**
	 * Write a debug message to the log along with its backtrace details
	 *
	 * @param string $message The debug message to be written
	 *
	 * @return void
	 */
	public function debug($message) {
		$this->get_backtrace_details();
		$this->writeln("DEBUG", $message);
	}

	/**
	 * Get detailed backtrace information for the current call stack.
	 *
	 * If the debug file, line and function have not been cached, this method will
	 * cache them in object properties to prevent repeated calls to debug_backtrace().
	 *
	 * @return void
	 */
	private function get_backtrace_details() {
		if ($this->debug_file === null) {
			$debug = debug_backtrace();
			$ndx = count($debug) - 1;
			$this->debug_file = $debug[$ndx]['file'];
			$this->debug_line = $debug[$ndx]['line'];
			$this->debug_func = $debug[$ndx]['function'];
			$this->debug_class = $debug[$ndx]['class'] ?? '';
		}
	}

	/**
	 * Write a message to the output with an optional level and trailing newline character.
	 *
	 * @param string $level   The logging level (optional).
	 * @param string $message The message to be written.
	 */
	public function writeln($level, $message) {
		$this->get_backtrace_details();
		$this->write($level, $message . "\n");
	}

	/**
	 * Write a log message to the file
	 *
	 * @param string $level   The level of the log message (e.g. 'error', 'warning', etc.)
	 * @param string $message The actual log message
	 *
	 * @return void
	 */
	public function write(string $level, string $message) {
		$this->get_backtrace_details();
		// write current time, script name and message to the log file
		// (don't forget to set the INI setting date.timezone)
		$time = @date('Y-m-d H:i:s');
		$file = $this->debug_file ?? 'file not set';
		$line = $this->debug_line ?? '0000';
		fwrite($this->fp, "[$time] [$level] [{$file}:{$line}] $message");
		$this->clear_debug();
	}

	/**
	 * Log an informational message.
	 *
	 * This method logs a message with level "INFO" and stores backtrace details for debugging purposes.
	 *
	 * @param string $message The message to be logged
	 */
	public function info($message) {
		$this->get_backtrace_details();
		$this->writeln("INFO", $message);
	}

	/**
	 * Display a warning message to the user
	 *
	 * @param string $message The warning message to display
	 *
	 * @throws Exception If an error occurs while displaying the message
	 */
	public function warning($message) {
		$this->get_backtrace_details();
		$this->writeln("WARNING", $message);
	}

	/**
	 * Log an error message with a backtrace.
	 *
	 * @param string $message The error message to log
	 */
	public function error($message) {
		$this->get_backtrace_details();
		$this->writeln("ERROR", $message);
	}

	/**
	 * Writes a message to the underlying output stream.
	 *
	 * @param string $msg The message to be written
	 *
	 * @throws Exception If an error occurs while writing to the stream
	 */
	private function _write($msg) {
		// define current time and suppress E_WARNING if using the system TZ settings
	}
}

/*
* Example:
$log = new logging(sys_get_temp_dir() . '/logging.log');
$log->writeln("debug", "passed validation");
$log->debug("pass");
$log->warning("variable should not used");
$log->debug_file(__FILE__)->debug_line(__LINE__)->write("DEBUG", "Raw message\n");
*/