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

		// write message to the log file
		private function _write($msg) {
			// define current time and suppress E_WARNING if using the system TZ settings
		}

		private function clear_debug() {
			$this->debug_line = null;
			$this->debug_file = null;
			$this->debug_func = null;
			$this->debug_class = null;
		}

		/**
		 * Write raw data to the
		 * @param string $level
		 * @param string $message
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

		public function debug_class(?string $debug_class = null) {
			if (func_num_args() > 0) {
				$this->debug_class = $debug_class;
				return $this;
			}
			return $this->debug_class;
		}

		public function debug_line(?string $debug_line = null) {
			if (func_num_args() > 0) {
				$this->debug_line = $debug_line;
				return $this;
			}
			return $this->debug_line;
		}

		public function debug_func(?string $debug_func = null) {
			if (func_num_args() > 0) {
				$this->debug_func = $debug_func;
				return $this;
			}
			return $this->debug_func;
		}

		public function debug_file(?string $debug_file = null) {
			if (func_num_args() > 0) {
				$this->debug_file = $debug_file;
				return $this;
			}
			return $this->debug_file;
		}

		public function writeln($level, $message) {
			$this->get_backtrace_details();
			$this->write($level, $message . "\n");
		}

		public function debug($message) {
			$this->get_backtrace_details();
			$this->writeln("DEBUG", $message);
		}

		public function info($message) {
			$this->get_backtrace_details();
			$this->writeln("INFO", $message);
		}

		public function warning($message) {
			$this->get_backtrace_details();
			$this->writeln("WARNING", $message);
		}

		public function error($message) {
			$this->get_backtrace_details();
			$this->writeln("ERROR", $message);
		}

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
	}

	/*
 * Example:
	$log = new logging(sys_get_temp_dir() . '/logging.log');
	$log->writeln("debug", "passed validation");
	$log->debug("pass");
	$log->warning("variable should not used");
	$log->debug_file(__FILE__)->debug_line(__LINE__)->write("DEBUG", "Raw message\n");
 */