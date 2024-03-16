<?php

	/**
	 * Logging class:
	 * - constructor requires the full file name and path for the log file. if it
	 * does not exist php will automatically try and create it. The log file will
	 * remain open for the life cycle of the object to improve performance.
	 * - message is written with the following format: [d/M/Y:H:i:s] (script name) message.
	 * - log file is closed when the object is destroyed.
	 * - when the mirror_to_syslog is enabled, the message will also be written to the syslog
	 */
	class logging {

		// declare log file and file pointer as private properties
		private $fp;
		private $debug_func;
		private $debug_line;
		private $debug_file;
		private $debug_class;
		private $mirror_to_syslog;

		/**
		 * Creates a logging object
		 * @param string $filename_and_path Can be an empty string when write_to_syslog is enabled to only write to system log
		 * @param bool $write_to_syslog When true, logs to the syslog as well as the log file
		 * @throws Exception
		 * @see https://www.php.net/manual/en/function.openlog.php
		 */
		public function __construct(string $filename_and_path, bool $write_to_syslog = false) {
			//init values
			$this->clear_debug();

			//check if we are writing to syslog
			if ($write_to_syslog) {
				$this->mirror_to_syslog = openlog("FusionPBX", LOG_PID | LOG_PERROR, LOG_SYSLOG);
			}

			//ensure the filename and path is not empty
			if (!empty($filename_and_path)) {
				try {
					//open file in append mode
					$this->fp = fopen($filename_and_path, 'a');
				} catch (Exception $ex) {
					//send the error to the caller
					throw $ex;
				}
			} else {
				$this->fp = false;
			}

			//check that we have something to do
			if (!is_resource($this->fp) && !$this->mirror_to_syslog) {
				//determine caller
				$this->set_backtrace_details();
				//notify with caller details
				throw new \ErrorException("Failed to open log", E_USER_ERROR, E_USER_ERROR, $this->debug_file, $this->debug_line);
			}
		}

		/**
		 * Flushes and closes the log file
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
				//close syslog
				if ($this->mirror_to_syslog) {
					closelog();
				}
			}
		}

		/**
		 * Ensure all data arrives on disk
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
		 * Write directly to log.
		 * <p>Use is_open before writing as this function does not check for a valid resource.</p>
		 * @param string $message String to send to the log
		 * @return void
		 */
		public function raw_write(string $message): void {
			fwrite($this->fp, $message);
		}

		/**
		 * Returns if the log has a valid resource and is currently open
		 * @return bool true if the log is currently open
		 */
		public function is_open(): bool {
			return is_resource($this->fp);
		}

		private function clear_debug(): void {
			$this->debug_line = null;
			$this->debug_file = null;
			$this->debug_func = null;
			$this->debug_class = null;
		}

		/**
		 * Write formed data to the log
		 * @param string $level
		 * @param string $message
		 * @return logging
		 */
		public function write(string $level, string $message, string $suffix = ""): logging {
			$this->set_backtrace_details();
			// write current time, script name and message to the log file
			// (don't forget to set the INI setting date.timezone)
			$time = @date('Y-m-d H:i:s');
			$file = $this->debug_file ?? 'file not set';
			$line = $this->debug_line ?? '0000';
			if (is_resource($this->fp)) {
				$this->raw_write("[$time] [$level] [{$file}:{$line}] {$message}{$suffix}");
			}
			if ($this->mirror_to_syslog) {
				$this->write_to_syslog(self::level_to_int($level), $message, $this->file, $this->line);
			}
			$this->clear_debug();
			return $this;
		}

		/**
		 * Writes to the system log.
		 * <p>If the instance was created with the mirror_to_syslog enabled then this is called with each write to the log file and will have a prefix with each line written.</p>
		 * @param int $level
		 * @param string $message
		 * @param string $file
		 * @param string $line
		 * @return void
		 */
		public static function write_to_syslog(int $level, string $message, string $file = '', string $line = ''): void {
			syslog($level, "[{$file}:{$line}] {$message}");
		}

		private static function level_to_int($level): int {
			switch ($level) {
				case 'INFO':
					return LOG_INFO;
				case 'WARNING':
					return LOG_WARNING;
				case 'ERROR':
					return LOG_ERR;
				default:
					return LOG_DEBUG;
			}
		}

		/**
		 * Sets or returns the debug_class variable used when writing to log.
		 * <p>When the method is called with no parameters given, the method will return $this.</p>
		 * @param string|null $debug_class
		 * @return $this
		 */
		public function debug_class(?string $debug_class = null) {
			if (func_num_args() > 0) {
				$this->debug_class = $debug_class;
				return $this;
			}
			return $this->debug_class;
		}

		/**
		 * Sets or returns the debug_line variable used when writing to log.
		 * @param string|null $debug_line
		 * @return $this
		 */
		public function debug_line(?string $debug_line = null) {
			if (func_num_args() > 0) {
				$this->debug_line = $debug_line;
				return $this;
			}
			return $this->debug_line;
		}

		/**
		 * Sets or returns the debug_func variable used when writing to log.
		 * @param string|null $debug_func
		 * @return $this
		 */
		public function debug_func(?string $debug_func = null) {
			if (func_num_args() > 0) {
				$this->debug_func = $debug_func;
				return $this;
			}
			return $this->debug_func;
		}

		/**
		 * Sets or returns the debug_file variable used when writing to log.
		 * @param string|null $debug_file
		 * @return $this
		 */
		public function debug_file(?string $debug_file = null) {
			if (func_num_args() > 0) {
				$this->debug_file = $debug_file;
				return $this;
			}
			return $this->debug_file;
		}

		/**
		 * Writes to the log appending a new line character after each message
		 * @param string $level One of the strings "DEBUG", "INFO", "WARNING", or "ERROR" values should be used
		 * @param string $message Message to send to the log without a newline character suffix
		 * @return logging
		 */
		public function writeln(string $level, string $message): logging {
			return $this->write($level, $message, "\n");
		}

		/**
		 * Writes a debug level message to the log with a newline character automatically appended.
		 * @param string $message Message to write
		 * @return logging
		 */
		public function debug(string $message): logging {
			return $this->writeln("DEBUG", $message);
		}

		/**
		 * Writes a info level message to the log with a newline character automatically appended.
		 * @param string $message Message to write
		 * @return logging
		 */
		public function info(string $message): logging {
			return $this->writeln("INFO", $message);
		}

		/**
		 * Writes a warning level message to the log with a newline character automatically appended.
		 * @param string $message Message to write
		 */
		public function warning(string $message) {
			$this->writeln("WARNING", $message);
		}

		/**
		 * Writes a error level message to the log with a newline character automatically appended.
		 * @param string $message Message to write
		 */
		public function error(string $message) {
			$this->writeln("ERROR", $message);
		}

		private function set_backtrace_details() {
			if ($this->debug_file === null) {
				$debug = debug_backtrace();
				$ndx = count($debug) - 1;
				//set values direct in object
				$this->debug_file = $debug[$ndx]['file'];
				$this->debug_line = $debug[$ndx]['line'];
				$this->debug_func = $debug[$ndx]['function'];
				$this->debug_class = $debug[$ndx]['class'] ?? '';
			}
		}
	}

	/*
 * Example:
	$log = new logging(sys_get_temp_dir() . '/logging.log');
	$log->writeln("debug", "passed validation");
	$log->debug("pass");
	$log->warning("variable should not used");
	$log->debug_file(__FILE__)->debug_line(__LINE__)->raw_write("Raw message\n");
 */