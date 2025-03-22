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
	 * Called when the object is created
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
		if (empty($this->method)) {
			$this->method = 'file';
		}
		if (empty($this->syslog)) {
			$this->syslog = 'false';
		}
		if (empty($this->location)) {
			$this->location = '/var/cache/fusionpbx';
		}
	}

	private function setting($subcategory) {
		return $this->settings->get('cache', $subcategory);
	}

	/**
	 * Add a specific item in the cache
	 * @var string $key		the cache id
	 * @var string $value	string to be cached
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
					$command = "memcache set ".$key." ".$value;
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
	 * Get a specific item from the cache
	 * @var string $key		cache id
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
					$command = "memcache get ".$key;
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
	 * Delete a specific item from the cache
	 * @var string $key		cache id
	 */
	public function delete($key) {

		//debug information
			if ($this->syslog === "true") {
				openlog("fusionpbx", LOG_PID | LOG_PERROR, LOG_USER);
				syslog(LOG_WARNING, "debug: cache: [key: ".$key.", script: ".$_SERVER['SCRIPT_NAME'].", line: ".__line__."]");
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
					$event .= "API-Command-Argument: delete ".$key."\n";
					event_socket::command($event);

				//run the memcache
					$command = "memcache delete ".$key;
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
					$event .= "API-Command-Argument: delete ".$key."\n";
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
	 * Delete the entire cache
	 */
	public function flush() {

		//debug information
			if ($this->syslog === "true") {
				openlog("fusionpbx", LOG_PID | LOG_PERROR, LOG_USER);
				syslog(LOG_WARNING, "debug: cache: [flush: all, script: ".$_SERVER['SCRIPT_NAME'].", line: ".__line__."]");
				closelog();
			}

		//check for apcu extension
			if (function_exists('apcu_enabled') && apcu_enabled()) {
				//flush everything
				apcu_clear_cache();
			}

		//remove the autoloader file cache
			if (file_exists(sys_get_temp_dir() . '/' . auto_loader::CLASSES_FILE)) {
				@unlink(sys_get_temp_dir() . '/' . auto_loader::CLASSES_FILE);
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

?>
