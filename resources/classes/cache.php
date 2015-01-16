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
		//send a custom event
			
		//run the memcache
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$command = "memcache set ".$key." ".$value;
				return event_socket_request($fp, 'api '.$command);
			}
			else {
				return false;
			}
	}

	/**
	 * Get a specific item from the cache
	 * @var string $key		cache id
	 */
	public function get($key) {
		//send a custom event
			
		//run the memcache
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$command = "memcache get ".$key;
				return event_socket_request($fp, 'api '.$command);
			}
			else {
				return false;
			}
	}

	/**
	 * Delete a specific item from the cache
	 * @var string $key		cache id
	 */
	public function delete($key) {
		//send a custom event
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$event = "sendevent CUSTOM\n";
				$event .= "Event-Name: MEMCACHE\n";
				$event .= "Event-Subclass: delete\n";
				$event .= "API-Command: memcache\n";
				$event .= "API-Command-Argument: delete ".$key."\n";
				echo event_socket_request($fp, $event);
			}

		//run the memcache
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$command = "memcache delete ".$key;
				return event_socket_request($fp, 'api '.$command);
			}
			else {
				return false;
			}
	}

	/**
	 * Delete the entire cache
	 */
	public function flush() {
		//send a custom event
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$event = "sendevent CUSTOM\n";
				$event .= "Event-Name: MEMCACHE\n";
				$event .= "Event-Subclass: flush\n";
				$event .= "API-Command: memcache\n";
				$event .= "API-Command-Argument: flush\n";
				echo event_socket_request($fp, $event);
			}

		//run the memcache
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if ($fp) {
				$command = "memcache flush";
				return event_socket_request($fp, 'api '.$command);
			}
			else {
				return false;
			}
	}
}

?>