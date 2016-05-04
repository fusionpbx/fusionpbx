<?php

/**
 * cache class provides an abstracted cache
 *
 * @method string set
 * @method string get
 * @method string delete
 * @method string flush
 */
class cache {

	/**
	 * Called when the object is created
	 */
	public function __construct() {
		//place holder
	}

	/**
	 * Called when there are no references to a particular object
	 * unset the variables used in the class
	 */
	public function __destruct() {
		foreach ($this as $key => $value) {
			unset($this->$key);
		}
	}

	/**
	 * Add a specific item in the cache
	 * @var string $key		the cache id
	 * @var string $value	string to be cached
	 */
	public function set($key, $value) {
		// connect to event socket
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp === false) {
				return false;
			}

		//send a custom event

		//run the memcache
			$command = "memcache set ".$key." ".$value;
			$result = event_socket_request($fp, 'api '.$command);

		//close event socket
			fclose($fp);

		// return result
			return $result;
	}

	/**
	 * Get a specific item from the cache
	 * @var string $key		cache id
	 */
	public function get($key) {
		// connect to event socket
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp === false) {
				return false;
			}

		//send a custom event

		//run the memcache
			$command = "memcache get ".$key;
			$result = event_socket_request($fp, 'api '.$command);

		//close event socket
			fclose($fp);

		// return result
			return $result;
	}

	/**
	 * Delete a specific item from the cache
	 * @var string $key		cache id
	 */
	public function delete($key) {
		// connect to event socket
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp === false) {
				return false;
			}

		//send a custom event
			$event = "sendevent CUSTOM\n";
			$event .= "Event-Name: MEMCACHE\n";
			$event .= "Event-Subclass: delete\n";
			$event .= "API-Command: memcache\n";
			$event .= "API-Command-Argument: delete ".$key."\n";
			event_socket_request($fp, $event);

		//run the memcache
			$command = "memcache delete ".$key;
			$result = event_socket_request($fp, 'api '.$command);

		//close event socket
			fclose($fp);

		// return result
			return $result;
	}

	/**
	 * Delete the entire cache
	 */
	public function flush() {
		// connect to event socket
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp === false) {
				return false;
			}

		//send a custom event
			$event = "sendevent CUSTOM\n";
			$event .= "Event-Name: MEMCACHE\n";
			$event .= "Event-Subclass: flush\n";
			$event .= "API-Command: memcache\n";
			$event .= "API-Command-Argument: flush\n";
			event_socket_request($fp, $event);

		//run the memcache
			$command = "memcache flush";
			$result = event_socket_request($fp, 'api '.$command);

		//close event socket
			fclose($fp);

		// return result
			return $result;
	}
}

?>