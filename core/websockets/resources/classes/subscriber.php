<?php

declare(strict_types=1);

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
 * Portions created by the Initial Developer are Copyright (C) 2008-2025
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Mark J Crane <markjcrane@fusionpbx.com>
 * Tim Fry <tim@fusionpbx.com>
 */

/**
 * Description of subscriber
 * @author Tim Fry <tim@fusionpbx.com>
 */
class subscriber {

	/**
	 * The ID of the object given by PHP
	 * @var spl_object_id
	 */
	private $id;

	/**
	 *
	 * @var resource
	 */
	private $socket;

	/**
	 * Stores the original socket ID used when the subscriber object was created.
	 * The resource is cast to an integer and then saved in order to match the
	 * a resource to the original socket. This is primarily used in the equals
	 * method to test for equality.
	 * @var int
	 */
	private $socket_id;

	/**
	 * Remote IP of the socket resource connection
	 * @var string
	 */
	private $remote_ip;

	/**
	 * Remote port of the socket resource connection
	 * @var int
	 */
	private $remote_port;

	/**
	 * Services the subscriber has subscribed to
	 * @var array
	 */
	private $services;

	/**
	 * Permissions array of the subscriber
	 * @var array
	 */
	private $permissions;

	/**
	 * Domain name the subscriber belongs to
	 * @var string|null
	 */
	private $domain_name;

	/**
	 * Domain UUID the subscriber belongs to
	 * @var string|null
	 */
	private $domain_uuid;

	/**
	 * Token hash used to validate this subscriber
	 * @var string|null
	 */
	private $token_hash;

	/**
	 * Token name used to validate this subscriber
	 * @var string|null
	 */
	private $token_name;

	/**
	 * Epoch time the token was issued
	 * @var int
	 */
	private $token_time;

	/**
	 * Time limit in seconds
	 * @var int
	 */
	private $token_limit;

	/**
	 * Whether the subscriber has a time limit set for their token or not
	 * @var bool True when there is a time limit. False if no time limit set.
	 */
	private $enable_token_time_limit;

	/**
	 * Whether the subscriber is able to broadcast messages as a service
	 * @var bool
	 */
	private $service;

	/**
	 * The name of the service class object to handle callbacks
	 * @var string|null
	 */
	private $service_class;

	/**
	 * If the subscriber is a service the service name used
	 * @var string|null
	 */
	private $service_name;

	/**
	 * The filter used to send web socket messages
	 * @var filter
	 */
	private $filter;

	/**
	 * Function or method name to call when sending information through the socket
	 * @var callable
	 */
	private $callback;

	/**
	 * Subscriptions to services
	 * @var array
	 */
	private $subscriptions;

	/**
	 * Whether or not this subscriber has been authenticated
	 * @var bool
	 */
	private $authenticated;

	/**
	 * User information
	 * @var array
	 */
	private $user;

	/**
	 * Creates a subscriber object.
	 * @param resource|stream $socket Connected socket
	 * @param callable $frame_wrapper The callback used to wrap communication in a web socket frame. Sending NULL to the frame wrapper should send a disconnect.
	 * @throws \socket_exception Thrown when the passed socket is already closed
	 * @throws \InvalidArgumentException Thrown when the $callback is not a valid callback
	 */
	public function __construct($socket, callable $frame_wrapper) {
		if (!is_resource($socket)) {
			throw new \socket_exception('Socket must be a valid resource');
		}
		// check for valid callback so we can send websocket data when required
		if (!is_callable($frame_wrapper)) {
			throw new \InvalidArgumentException('Websocket callable method must be a valid callable function or method');
		}

		// set object identifiers
		$this->id = md5(spl_object_hash($this)); // PHP unique object hash is similar to 000000000000000f0000000000000000 so we use md5
		$this->socket = $socket;
		$this->socket_id = (int) $socket;

		$this->domain_name = '';
		$this->domain_uuid = '';

		// always use the same formula from the static functions
		[$this->remote_ip, $this->remote_port] = self::get_remote_information_from_socket($socket);

		// set defaults
		$this->authenticated = false;
		$this->permissions = [];
		$this->services = [];
		$this->enable_token_time_limit = false;
		$this->subscriptions = [];
		$this->service = false;
		$this->service_name = '';
		$this->user = [];

		// Save the websocket frame wrapper used to communicate to this subscriber
		$this->callback = $frame_wrapper;

		// No filter initially
		$this->filter = null;
	}

	/**
	 * Returns the user array information in this subscriber
	 * @return array
	 */
	public function get_user_array(): array {
		return $this->user;
	}

	/**
	 * Returns the user information from the provided key.
	 * @param string $key
	 * @return mixed
	 */
	public function get_user_setting($key, $default_value = null) {
		return $this->user[$key] ?? $default_value;
	}

	/**
	 * Gets or sets the subscribed to services
	 * @param array $services
	 * @return $this|array
	 */
	public function subscribed_to($services = []) {
		if (func_num_args() > 0) {
			$this->services = array_flip($services);
			return $this;
		}
		return array_keys($this->services);
	}

	/**
	 * Gets or sets the service class name for this subscriber
	 * @param string $service_class
	 * @return $this|string
	 */
	public function service_class($service_class = null) {
		if (func_num_args() > 0) {
			$this->service_class = $service_class;
			return $this;
		}
		return $this->service_class;
	}

	/**
	 * Sets the filter used for this subscriber
	 * @param filter $filter
	 * @return $this
	 */
	public function set_filter(filter $filter) {
		$this->filter = $filter;
		return $this;
	}

	/**
	 * Returns the filter used for this subscriber
	 * @return filter
	 */
	public function get_filter() {
		return $this->filter;
	}

	/**
	 * When there is no more references to the object we ensure that we disconnect from the subscriber
	 */
	public function __destruct() {
		// disconnect the socket
		$this->disconnect();
	}

	/**
	 * Disconnects the socket resource used for this subscriber
	 * @return bool true on success and false on failure
	 */
	public function disconnect(): bool {
		//return success if close was successful
		if (is_resource($this->socket)) {
			//self::$logger->info("Subscriber $this->id has been disconnected");
			// Send null to the frame wrapper to send a disconnect frame
			call_user_func($this->callback, $this->socket_id, null);
			return (@fclose($this->socket) !== false);
		}
		return false;
	}

	/**
	 * Compares the current object with another object to see if they are exactly the same object
	 * @param subscriber|resource $object_or_resource_or_id
	 * @return bool
	 */
	public function equals($object_or_resource_or_id): bool {
		// Compare by resource
		if (is_resource($object_or_resource_or_id)) {
			return $object_or_resource_or_id === $this->socket;
		}
		// Compare by spl_object_id or spl_object_hash
		if (gettype($object_or_resource_or_id) === 'integer' || gettype($object_or_resource_or_id) === 'string') {
			return $object_or_resource_or_id === $this->id;
		}
		// Ensure it is the same type of object
		if (!($object_or_resource_or_id instanceof subscriber)) {
			// Not a subscriber object
			return false;
		}
		// Compare by object using the spl_object_id to match
		return $object_or_resource_or_id->id() === $this->id;
	}

	/**
	 * Compares this object to another object or resource id.
	 * @param type $object_or_resource
	 * @return bool True if this object is not equal to the other object or resource. False otherwise.
	 * @see subscriber::equals()
	 */
	public function not_equals($object_or_resource): bool {
		return !$this->equals($object_or_resource);
	}

	/**
	 * Allow accessing <b>copies</b> of the private values to ensure the object values are immutable.
	 * @param string $name
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function __get(string $name) {
		switch ($name) {
			case 'id':
			case 'socket_id':
			case 'remote_ip':
			case 'remote_port':
			case 'token_name':
			case 'token_hash':
			case 'token_time':
			case 'domain_name':
			case 'permissions':
			case 'services':
				return $this->{$name};
			default:
				throw new \InvalidArgumentException("Property '$name' does not exist or direct access is prohibited. Try using '$name()' for access.");
		}
	}

	/**
	 * Returns the current ID of this subscriber.
	 * The ID is set in the constructor using the spl_object_id given by PHP
	 * @return string
	 */
	public function id(): string {
		return "$this->id";
	}

	/**
	 * Checks if this subscriber has the permission given in $permission
	 * @param string $permission
	 * @return bool True when this subscriber has the permission and false otherwise
	 */
	public function has_permission(string $permission): bool {
		// Do not allow empty names
		if (empty($this->permissions) || strlen($permission) === 0) {
			return false;
		}
		return isset($this->permissions[$permission]);
	}

	/**
	 * Returns the array of permissions this subscriber has been assigned.
	 * @return array
	 */
	public function get_permissions(): array {
		return $this->permissions;
	}

	/**
	 * Returns the domain name used.
	 * <p>Note:<br>
	 * This value is not validated in the object and must be validated.</p>
	 * @return string
	 */
	public function get_domain_name(): string {
		return $this->domain_name;
	}

	/**
	 * Returns the current socket resource used to communicate with this subscriber
	 * @return resource|stream Resource Id or stream used
	 */
	public function socket() {
		return $this->socket;
	}

	/**
	 * Returns the socket ID that was cast to an integer when the object was created.
	 * @return int The socket ID cast as an integer.
	 */
	public function socket_id(): int {
		return $this->socket_id;
	}

	/**
	 * Validates the given token against the loaded token in the this subscriber
	 * @param array $token Must be an associative array with name and hash as the keys.
	 * @return bool
	 */
	public function is_valid_token(array $token): bool {
		if (!is_array($token)) {
			throw new \InvalidArgumentException('Token must be an array');
		}

		// get the name and hash from array
		$token_name = $token['name'] ?? '';
		$token_hash = $token['hash'] ?? '';

		// empty values are not allowed
		if (empty($token_name) || empty($token_hash)) {
			return false;
		}

		// validate the name and hash
		$valid = ($token_name === $this->token_name && $token_hash === $this->token_hash);

		// Get the current epoch time
		$server_time = time();

		// check time validation required
		if ($this->enable_token_time_limit) {
			// compare against time limit in minutes
			$valid = $valid && ($server_time - $this->token_time < $this->token_limit * 60);
		}
		//self::$logger->debug("------------------ Token Compare ------------------");
		//self::$logger->debug("Subscriber token time: $this->token_time");
		//self::$logger->debug("          Server time: $server_time");
		//self::$logger->debug("Subscriber token name: $this->token_name");
		//self::$logger->debug("    Server token name: $token_name");
		//self::$logger->debug("Subscriber token hash: $this->token_hash");
		//self::$logger->debug("    Server token hash: $token_hash");
		//self::$logger->debug("Returning: " . ($valid ? 'true' : 'false'));
		//self::$logger->debug("---------------------------------------------------");
		return $valid;
	}

	/**
	 * Validates the given token array against the token previously saved in the file system. When the token is valid
	 * the token will be saved in this object and the file removed. This method should not be called a second time
	 * once a token has be authenticated.
	 * @param array $request_token
	 * @return bool
	 */
	public function authenticate_token(array $request_token): bool {
		// Check connection
		if (!$this->is_connected()) {
			throw new \socket_disconnected_exception($this->id);
		}

		// Check for required fields
		if (empty($request_token)) {
			//$date = date('Y/m/d H:i:s', time());
			//self::$logger->warn("Empty token given for $this->id");
			return false;
		}

		// Set local storage
		$token_file = self::get_token_file($request_token['name'] ?? '');

		// Set default return value of false
		$valid = false;

		//self::$logger->debug("Using file: $token_file");
		// Ensure the file is there
		if (file_exists($token_file)) {
			//self::$logger->debug("Using $token_file for token");
			// Get the token using PHP engine parsing (fastest method)
			$array = include($token_file);

			// Assign to local variables to reflect local storage
			$token_name = $array['token']['name'] ?? '';
			$token_hash = $array['token']['hash'] ?? '';
			$token_time = intval($array['token']['time'] ?? 0);
			$token_limit = intval($array['token']['limit'] ?? 0);

			// Compare the token given in the request with the one that was in local storage
			$valid = $token_name === $request_token['name'] && $token_hash === $request_token['hash'];

			// If the token is supposed to have a time limit then check the token time
			if ($token_limit > 0) {
				// check time has expired or not and put it in valid
				$valid = $valid && (time() - $token_time < $token_limit * 60);  // token_time_limit * 60 seconds = 15 minutes
			}

			// When token is valid
			if ($valid) {

				// Store the valid token information in this object
				$this->token_name = $token_name;
				$this->token_hash = $token_hash;
				$this->token_time = $token_time;
				$this->enable_token_time_limit = $token_limit > 0;
				$this->token_limit = $token_limit * 60; // convert to seconds for time() comparison

				// Add the domain
				$this->domain_name = $array['domain']['name'] ?? '';
				$this->domain_uuid = $array['domain']['uuid'] ?? '';

				// Store the permissions
				$this->permissions = $array['user']['permissions'] ?? [];

				// Remove the permissions from the user array because this class handles them seperately
				unset($array['user']['permissions']);

				// Add the user information when available
				$this->user = $array['user'] ?? [];

				// Add subscriptions for services
				$services = $array['services'] ?? [];
				foreach ($services as $service) {
					$this->subscribe($service);
				}

				// Check for service
				if (isset($array['service'])) {
					//
					// Set the service information in the object
					//
					$this->service_name = "" . ($array['service_name'] ?? '');
					$this->service_class = "" . ($array['service_class'] ?? '');

					//
					// Ensure we can call the method we need by checking for the interface.
					// Using the interface instead of calling method_exists means we only have to check once
					// for the interface instead of checking for each individual method required for it to be
					// considered a service. We can also adjust the interface with new methods and this code
					// remains the same. It is also possbile for us to use the 'instanceof' operator to check
					// that the object is what we require. However, using the instanceof operator requires an
					// object first. Here we only check that the class has implemented the interface allowing
					// us to call static methods without first creating an object.
					//
					$this->service = is_a($this->service_class, 'websocket_service_interface', true);
				}

				//self::$logger->debug("Permission count(".count($this->permissions) . ")");
			}

			// Remove the token from local storage
			@unlink($token_file);
		}
		// store the result
		$this->authenticated = $valid;

		// return success or failed
		return $valid;
	}

	/**
	 * Returns whether or not this subscriber has been authenticated.
	 * @return bool
	 */
	public function is_authenticated(): bool {
		return $this->authenticated;
	}

	/**
	 * Allows overriding the token authentication
	 * @param bool $authenticated
	 * @return self
	 */
	public function set_authenticated(bool $authenticated): self {
		$this->authenticated = $authenticated;
		return $this;
	}

	/**
	 * Sets the domain UUID and name
	 * @param string $uuid
	 * @param string $name
	 * @return self
	 * @throws invalid_uuid_exception
	 * @depends is_uuid()
	 * @see is_uuid()
	 */
	public function set_domain(string $uuid, string $name): self {
		if (is_uuid($uuid)) {
			$this->uuid = $uuid;
		} else {
			throw new invalid_uuid_exception("UUID is not valid");
		}
		$this->domain_name = $name;
		return $this;
	}

	/**
	 * Returns whether or not this subscriber is a service.
	 * @return bool True if this subscriber is a service and false if this subscriber is not a service.
	 */
	public function is_service(): bool {
		return $this->service;
	}

	/**
	 * Alias of service_name without the parameters
	 * @return string
	 */
	public function get_service_name(): string {
		return $this->service_name;
	}

	/**
	 * Get or set the service_name
	 * @param string|null $service_name
	 * @return string|$this
	 */
	public function service_name($service_name = null) { /* : string|self */
		if (func_num_args() > 0) {
			$this->service_name = $service_name;
			return $this;
		}
		return $this->service_name;
	}

	/**
	 * Returns whether or not the service name matches this subscriber
	 * @param string $service_name Name of the service
	 * @return bool True if this subscriber matches the provided service name. False if this subscriber does not
	 * match or this subscriber is not a service.
	 */
	public function service_equals(string $service_name): bool {
		return ($this->service && $this->service_name === $service_name);
	}

	/**
	 * Returns true if the socket/stream is still open (not at EOF).
	 * @return bool Returns true if connected and false if the connection has closed
	 */
	public function is_connected(): bool {
		return is_resource($this->socket) && !feof($this->socket);
	}

	/**
	 * Returns true if the subscriber is no longer connected
	 * @return bool Returns true if the subscriber is no longer connected
	 */
	public function is_not_connected(): bool {
		return !$this->is_connected();
	}

	/**
	 * Checks if this subscriber is subscribed to the given service name
	 * @param string $service_name The service name ie. active.calls
	 * @return bool
	 * @see subscriber::subscribe
	 */
	public function has_subscribed_to(string $service_name): bool {
		return isset($this->services[$service_name]);
	}

	/**
	 * Subscribe to a service by ensuring this subscriber has the appropriate permissions
	 * @param string $service_name
	 * @return self
	 */
	public function subscribe(string $service_name): self {
		$this->services[$service_name] = true;
		return $this;
	}

	/**
	 * Sends a response to the subscriber using the provided callback web socket wrapper in the constructor
	 * @param string $json Valid JSON response to send to the connected client
	 * @throws subscriber_token_expired_exception Thrown when the time limit set in the token has expired
	 */
	public function send(string $json) {
		//ensure the token is still valid
		if (!$this->token_time_exceeded()) {
			call_user_func($this->callback, $this->socket, $json);
		} else {
			throw new subscriber_token_expired_exception($this->id);
		}
	}

	/**
	 * Sends the given message through the websocket
	 * @param websocket_message $message
	 * @throws socket_disconnected_exception
	 */
	public function send_message(websocket_message $message) {

		// Filter the message
		if ($this->filter !== null) {
			$message->apply_filter($this->filter);
		}

		if (empty($message->service_name())) {
			return;
		}

		// Check that we are subscribed to the event
		if (!$this->has_subscribed_to($message->service_name())) {
			//self::$logger->warn("Subscriber not subscribed to " . $message->service_name());
			throw new subscriber_not_subscribed_exception($this->id);
		}

		// Ensure we are still connected
		if (!$this->is_connected()) {
			throw new \socket_disconnected_exception($this->id);
		}

		$this->send((string) $message);

		return;
	}

	/**
	 * The remote information is retrieved using the stream_socket_get_name function.
	 * @param resource $socket
	 * @return array Returns a zero-based indexed array of first the IP address and then the port of the remote machine.
	 * @see stream_socket_get_name();
	 * @link https://php.net/stream_socket_get_name PHP documentation for underlying function used to return information.
	 */
	public static function get_remote_information_from_socket($socket): array {
		return explode(':', stream_socket_get_name($socket, true), 2);
	}

	/**
	 * The remote information is retrieved using the stream_socket_get_name function.
	 * @param resource $socket
	 * @return string Returns the IP address of the remote machine or an empty string.
	 * @see stream_socket_get_name();
	 * @link https://php.net/stream_socket_get_name PHP documentation for underlying function used to return information.
	 */
	public static function get_remote_ip_from_socket($socket): string {
		$array = explode(':', stream_socket_get_name($socket, true), 2);
		return $array[0] ?? '';
	}

	/**
	 * The remote information is retrieved using the stream_socket_get_name function.
	 * @param resource $socket
	 * @return string Returns the port of the remote machine as a string or an empty string.
	 * @see stream_socket_get_name();
	 * @link https://php.net/stream_socket_get_name PHP documentation for underlying function used to return information.
	 */
	public static function get_remote_port_from_socket($socket): string {
		$array = explode(':', stream_socket_get_name($socket, true), 2);
		return $array[1] ?? '';
	}

	/**
	 * Returns the name and path for the token.
	 * Priority is given to the /dev/shm folder if it exists as this is much faster. If that is not available, then the
	 * sys_get_temp_dir() function is called to get a storage location.
	 * @param string $token_name
	 * @return string
	 * @see sys_get_temp_dir()
	 * @link https://php.net/sys_get_temp_dir PHP Documentation for the function used to get the temporary storage location.
	 */
	public static function get_token_file($token_name): string {
		// Try to store in RAM first
		if (is_dir('/dev/shm') && is_writable('/dev/shm')) {
			$token_file = '/dev/shm/' . $token_name . '.php';
		} else {
			// Use the filesystem
			$token_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $token_name . '.php';
		}
		return $token_file;
	}

	/**
	 * Saves the token array to local file system
	 *
	 * The web socket server runs in a separate process so it is unable to use
	 * sessions. Therefor, the token must be stored in a temp folder to be
	 * verified by the web socket server. It is possible to use a database
	 * but the database connection process is very slow compared to the file
	 * system. If the database resides on a remote system instead of local,
	 * the web socket service may not yet have access to the token before the
	 * web socket client requests authorization.
	 *
	 * @param array $token Standard token issued from the token object
	 * @param array $services A simple array list of service names to subscribe to
	 * @param int $time_limit_in_minutes Set a token time limit. Setting to zero will disable the time limit
	 * @see token::create()
	 */
	public static function save_token(array $token, array $services, int $time_limit_in_minutes = 0) {

		//
		// Store the currently logged in user when available
		//
		$array['user'] = $_SESSION['user'] ?? [];

		//
		// Store the token service and events
		//
		$array['services'] = $services;

		//
		// Store the name and hash of the token
		//
		$array['token']['name'] = $token['name'];
		$array['token']['hash'] = $token['hash'];

		//
		// Store the epoch time and time limit
		//
		$array['token']['time'] = "" . time();
		$array['token']['limit'] = $time_limit_in_minutes;

		//
		// Store the domain name in this session
		//
		$array['domain']['name'] = $_SESSION['domain_name'] ?? '';
		$array['domain']['uuid'] = $_SESSION['domain_uuid'] ?? '';

		//
		// Get the full path and file name for storing the token
		//
		$token_file = self::get_token_file($token['name']);

		$file_contents = "<?php\nreturn " . var_export($array, true) . ";\n";

		//
		// Put the contents in the file using the PHP method var_export. This is the fastest method to import
		// later because we can use the speed of the Zend Engine to import it with a simple include statement
		// The include can be used as a function: "$array = include($token_file);"
		//
		file_put_contents($token_file, $file_contents);
	}

	/**
	 * Checks the token time stored in this subscriber
	 * @return bool True if the token has expired. False if the token is still valid
	 */
	public function token_time_exceeded(): bool {
		if (!$this->enable_token_time_limit) {
			return false;
		}

		//test the time on the token to ensure it is valid
		return (time() - $this->token_time) > $this->token_limit;
	}
}
