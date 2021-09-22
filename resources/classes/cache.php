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
		//save to memcache
			if ($_SESSION['cache']['method']['text'] == "memcache") {
				//connect to event socket
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp === false) {
						return false;
					}

				//run the memcache
					$command = "memcache set ".$key." ".$value;
					$result = event_socket_request($fp, 'api '.$command);

				//close event socket
					fclose($fp);
			}

		//save to the file cache
			if ($_SESSION['cache']['method']['text'] == "file") {
				if (file_exists($_SESSION['cache']['location']['text'] . "/" . $key)) {
					$result = file_put_contents($_SESSION['cache']['location']['text'] . "/" . $key, $value);
				}
			}

		//return result
			return $result;
	}

	/**
	 * Get a specific item from the cache
	 * @var string $key		cache id
	 */
	public function get($key) {

		//cache method memcache 
			if ($_SESSION['cache']['method']['text'] == "memcache") {
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
			}

		//get the file cache
			if ($_SESSION['cache']['method']['text'] == "file") {
				if (file_exists($_SESSION['cache']['location']['text'] . "/" . $key)) {
					$result = file_get_contents($_SESSION['cache']['location']['text'] . "/" . $key);
				}
			}

		//return result
			return $result;
	}

	/**
	 * Delete a specific item from the cache
	 * @var string $key		cache id
	 */
	public function delete($key) {

		//cache method memcache 
			if ($_SESSION['cache']['method']['text'] == "memcache") {
				//connect to event socket
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp === false) {
						return false;
					}

				//send a custom event
					$event = "sendevent CUSTOM\n";
					$event .= "Event-Name: CUSTOM\n";
					$event .= "Event-Subclass: fusion::memcache\n";
					$event .= "API-Command: memcache\n";
					$event .= "API-Command-Argument: delete ".$key."\n";
					event_socket_request($fp, $event);

				//run the memcache
					$command = "memcache delete ".$key;
					$result = event_socket_request($fp, 'api '.$command);

				//close event socket
					fclose($fp);
			}

		//cache method file
			if ($_SESSION['cache']['method']['text'] == "file") {
				//change the delimiter
					$key = str_replace(":", ".", $key);

				//connect to event socket
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp === false) {
						return false;
					}

				//send a custom event
					$event = "sendevent CUSTOM\n";
					$event .= "Event-Name: CUSTOM\n";
					$event .= "Event-Subclass: fusion::file\n";
					$event .= "API-Command: cache\n";
					$event .= "API-Command-Argument: delete ".$key."\n";
					event_socket_request($fp, $event);

				//remove the local files
					foreach (glob($_SESSION['cache']['location']['text'] . "/" . $key) as $file) {
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
		//cache method memcache 
			if ($_SESSION['cache']['method']['text'] == "memcache") {
				// connect to event socket
					$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
					if ($fp === false) {
						return false;
					}

				//send a custom event
					$event = "sendevent CUSTOM\n";
					$event .= "Event-Name: CUSTOM\n";
					$event .= "Event-Subclass: fusion::memcache\n";
					$event .= "API-Command: memcache\n";
					$event .= "API-Command-Argument: flush\n";
					event_socket_request($fp, $event);

				//run the memcache
					$command = "memcache flush";
					$result = event_socket_request($fp, 'api '.$command);

				//close event socket
					fclose($fp);
			}

		//cache method file 
			if ($_SESSION['cache']['method']['text'] == "file") {
				//send a custom event
					$event = "sendevent CUSTOM\n";
					$event .= "Event-Name: CUSTOM\n";
					$event .= "Event-Subclass: fusion::file\n";
					$event .= "API-Command: cache\n";
					$event .= "API-Command-Argument: flush\n";
					event_socket_request($fp, $event);

				//remove the cache
					recursive_delete($_SESSION['cache']['location']['text']);

				//set message
					$result = '+OK cache flushed';
			}

		//return result
			return $result;
	}
}

?>
