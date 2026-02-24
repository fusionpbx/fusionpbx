<?php

/**
 * Provides an abstracted cache
 */
class cache {
	private $settings;
	private $syslog;
	private $location;
	private $method;

	/**
	 * Initializes the cache object with default settings if none are provided.
	 *
	 * @param settings|null $settings The settings to use for initialization. Defaults to null.
	 *
	 * @return void
	 */
	public function __construct(settings $settings = null) {
		//set defaults
		if ($settings === null) {
			$settings = new settings();
		}

		//get the settings
		$this->settings = $settings;
		$this->method = $this->setting('method');
		$this->syslog = $this->setting('syslog');
		$this->location = $this->setting('location');
		$this->method = 'file';
		$this->syslog = 'false';
		$this->location = '/var/cache/fusionpbx';
	}

	/**
	 * Get a specific cache setting from the settings array.
	 *
	 * @param string $subcategory The subcategory of the cache setting to retrieve.
	 *
	 * @return mixed The value of the specified cache setting, or null if it does not exist.
	 */
	private function setting($subcategory) {
		return $this->settings->get('cache', $subcategory);
	}

	/**
	 * Retrieve the value associated with a given cache key.
	 *
	 * @param string $key The cache key to retrieve. Delimiter is automatically changed from ':' to '.'.
	 *
	 * @return mixed The cached value, or null if it does not exist.
	 */
	public function get($key) {

		//change the delimiter
		$key = str_replace(":", ".", $key);

		//cache method memcache
		if ($this->method === "memcache") {
			// connect to event socket
			$esl = event_socket::create();
			if (!$esl->is_connected()) {
				return false;
			}

			//send a custom event

			//run the memcache
			$command = "memcache get " . $key;
			$result = event_socket::api($command);

		}

		//get the file cache
		if ($this->method === "file") {
			if (file_exists($this->location . "/" . $key)) {
				$result = file_get_contents($this->location . "/" . $key);
			}
		}

		//return result
		return $result ?? null;
	}

	/**
	 * Set a value in the cache based on the cache type in global default settings.
	 *
	 * Cache location is based on the global default setting for either "memcache" or "file".
	 *
	 * @param string $key   The key of the value to set.
	 * @param mixed  $value The value to store.
	 *
	 * @return mixed When location is "file" the return value is in bytes written or null. When location is "memcache"
	 *               return value is the return value from the switch socket response or false.
	 */
	public function set($key, $value) {

		//change the delimiter
		$key = str_replace(":", ".", $key);

		//save to memcache
		if ($this->method === "memcache") {
			//connect to event socket
			$esl = event_socket::create();
			if ($esl === false) {
				return false;
			}

			//run the memcache
			$command = "memcache set " . $key . " " . $value;
			$result = event_socket::api($command);

		}

		//save to the file cache
		if ($this->method === "file") {
			$result = file_put_contents($this->location . "/" . $key, $value);
		}

		//return result
		return $result;
	}

	/**
	 * Delete a single cache key.
	 *
	 * @param string $key The cache key to delete
	 *
	 * @return bool When cache type is "memcache" false is returned on failure otherwise no value is returned
	 */
	public function delete($key) {

		//debug information
		if ($this->syslog === "true") {
			openlog("fusionpbx", LOG_PID | LOG_PERROR, LOG_USER);
			syslog(LOG_WARNING, "debug: cache: [key: " . $key . ", script: " . $_SERVER['SCRIPT_NAME'] . ", line: " . __line__ . "]");
			closelog();
		}

		//cache method memcache
		if ($this->method === "memcache") {
			//connect to event socket
			$esl = event_socket::create();
			if ($esl === false) {
				return false;
			}

			//send a custom event
			$event = "sendevent CUSTOM\n";
			$event .= "Event-Name: CUSTOM\n";
			$event .= "Event-Subclass: fusion::memcache\n";
			$event .= "API-Command: memcache\n";
			$event .= "API-Command-Argument: delete " . $key . "\n";
			event_socket::command($event);

			//run the memcache
			$command = "memcache delete " . $key;
			$result = event_socket::api($command);

		}

		//cache method file
		if ($this->method === "file") {
			//change the delimiter
			$key = str_replace(":", ".", $key);

			//connect to event socket
			$esl = event_socket::create();
			if ($esl === false) {
				return false;
			}

			//send a custom event
			$event = "sendevent CUSTOM\n";
			$event .= "Event-Name: CUSTOM\n";
			$event .= "Event-Subclass: fusion::file\n";
			$event .= "API-Command: cache\n";
			$event .= "API-Command-Argument: delete " . $key . "\n";
			event_socket::command($event);

			//remove the local files
			foreach (glob($this->location . "/" . $key) as $file) {
				if (file_exists($file)) {
					unlink($file);
				}
				if (file_exists($file)) {
					unlink($file . ".tmp");
				}
			}
		}

	}

	/**
	 * Flushes the cache based on the current method setting.
	 *
	 * @return string|false The result of the flush operation, or false if an error occurred.
	 */
	public function flush() {

		//debug information
		if ($this->syslog === "true") {
			openlog("fusionpbx", LOG_PID | LOG_PERROR, LOG_USER);
			syslog(LOG_WARNING, "debug: cache: [flush: all, script: " . $_SERVER['SCRIPT_NAME'] . ", line: " . __line__ . "]");
			closelog();
		}

		//check for apcu extension
		if (function_exists('apcu_enabled') && apcu_enabled()) {
			//flush everything
			apcu_clear_cache();
		}

		//cache method memcache
		if ($this->method === "memcache") {
			// connect to event socket
			$esl = event_socket::create();
			if ($esl === false) {
				return false;
			}

			//send a custom event
			$event = "sendevent CUSTOM\n";
			$event .= "Event-Name: CUSTOM\n";
			$event .= "Event-Subclass: fusion::memcache\n";
			$event .= "API-Command: memcache\n";
			$event .= "API-Command-Argument: flush\n";
			event_socket::command($event);

			//run the memcache
			$command = "memcache flush";
			$result = event_socket::api($command);

		}

		//cache method file
		if ($this->method === "file") {
			// connect to event socket
			$esl = event_socket::create();
			if ($esl === false) {
				return false;
			}

			//send a custom event
			$event = "sendevent CUSTOM\n";
			$event .= "Event-Name: CUSTOM\n";
			$event .= "Event-Subclass: fusion::file\n";
			$event .= "API-Command: cache\n";
			$event .= "API-Command-Argument: flush\n";
			event_socket::command($event);

			//remove the cache
			recursive_delete($this->location);

			//set message
			$result = '+OK cache flushed';
		}

		//return result
		return $result;
	}
}
