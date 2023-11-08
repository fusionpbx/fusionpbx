<?php

	/**
	 * Logging class:
	 * - constructor requires the full file name and path for the log file. if it
	 * does not exist php will automatically try and create it. The log file will
	 * remain open for the life cycle of the object to improve performance.
	 * - message is written with the following format: [d/M/Y:H:i:s] (script name) message.
	 * - log file is closed when the object is destroyed.
	 */
	class Logging {

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
			// (don't forget to set the INI setting date.timezone)
			$time = @date('[d/M/Y:H:i:s]');
			// write current time, script name and message to the log file
			fwrite($this->fp, "$time ({$this->debug_line}: {$this->debug_file}) $msg");
			$this->clear_debug();
		}

		private function clear_debug() {
			$this->debug_line = null;
			$this->debug_file = null;
			$this->debug_func = null;
			$this->debug_class = null;
		}

		public function write($level, $message) {
			$this->get_backtrace_details();
			$this->_write("[" . strtoupper($level) . "] $message");
		}

		public function writeln($level, $message) {
			$this->get_backtrace_details();
			$this->write($level, $message . "\n");
		}

		public function debug($message) {
			$this->get_backtrace_details();
			$this->writeln("debug", $message);
		}

		public function info($message) {
			$this->get_backtrace_details();
			$this->writeln("info", $message);
		}

		public function warning($message) {
			$this->get_backtrace_details();
			$this->writeln("warning", $message);
		}

		public function error($message) {
			$this->get_backtrace_details();
			$this->writeln("error", $message);
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
	$log = new Logging(sys_get_temp_dir() . '/logging.log');
	$log->writeln("debug", "passed validation");
	$log->writeln("debug", "pass");
	$log->warning("variable should not used");
 */