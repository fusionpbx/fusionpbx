<?php

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
 * A structured web socket message easily converted to and from a json string
 *
 * @author Tim Fry <tim@fusionpbx.com>
 * @param string $service_name;
 * @param string $token_name;
 * @param string $token_hash;
 * @param string $status_string;
 * @param string $status_code;
 * @param string $request_id;
 * @param string $resource_id;
 * @param string $domain_uuid;
 * @param string $permissions;
 * @param string $topic;
 */
class websocket_message extends base_message {

	// By setting these to protected we ensure the __set and __get methods are used in the parent class
	protected $service_name;
	protected $token_name;
	protected $token_hash;
	protected $status_string;
	protected $status_code;
	protected $request_id;
	protected $resource_id;
	protected $domain_uuid;
	protected $domain_name;
	protected $permissions;
	protected $topic;

	public function __construct($associative_properties_array = []) {
		// Initialize empty default values
		$this->service_name = '';
		$this->token_name = '';
		$this->token_hash = '';
		$this->status_string = '';
		$this->status_code = '';
		$this->request_id = '';
		$this->resource_id = '';
		$this->domain_uuid = '';
		$this->domain_name = '';
		$this->permissions = [];
		$this->topic = '';
		//
		// Send to parent (base_message) constructor
		//
		parent::__construct($associative_properties_array);
	}

	public function has_permission($permission_name) {
		return isset($this->permissions[$permission_name]);
	}

	/**
	 * Alias of service_name.
	 * @param string $service_name
	 * @return $this
	 * @see service_name
	 */
	public function service($service_name = null) {
		if (func_num_args() > 0) {
			$this->service_name = $service_name;
			return $this;
		}
		return $this->service_name;
	}

	/**
	 * Gets or Sets the service name
	 * If no parameters are provided then the service_name is returned. If the service name is provided, then the
	 * service_name is set to the value provided.
	 * @param string $service_name
	 * @return $this
	 */
	public function service_name($service_name = null) {
		if (func_num_args() > 0) {
			$this->service_name = $service_name;
			return $this;
		}
		return $this->service_name;
	}

	/**
	 * Gets or sets the permissions array
	 * @param array $permissions
	 * @return $this
	 */
	public function permissions($permissions = []) {
		if (func_num_args() > 0) {
			$this->permissions = $permissions;
			return $this;
		}
		return $this->permissions;
	}

	/**
	 * Applies a filter to the payload of this message.
	 * When a filter returns null then the payload is set to null
	 * @param filter $filter
	 */
	public function apply_filter(?filter $filter) {
		if ($filter !== null && is_array($this->payload)) {
			foreach ($this->payload as $key => $value) {
				$result = ($filter)($key, $value);
				// Check if a filter requires dropping the payload
				if ($result === null) {
					$this->payload = null;
					return;
				}
				// Remove a key if filter does not pass
				elseif(!$result) {
					unset($this->payload[$key]);
				}
			}
		}
	}

	/**
	 * Gets or sets the domain UUID
	 * @param string $domain_uuid
	 * @return $this or $domain_uuid
	 */
	public function domain_uuid($domain_uuid = '') {
		if (func_num_args() > 0) {
			$this->domain_uuid = $domain_uuid;
			return $this;
		}
		return $this->domain_uuid;
	}

	/**
	 * Gets or sets the domain name
	 * @param string $domain_name
	 * @return $this or $domain_name
	 */
	public function domain_name($domain_name = '') {
		if (func_num_args() > 0) {
			$this->domain_name = $domain_name;
			return $this;
		}
		return $this->domain_name;
	}

	/**
	 * Gets or Sets the service name
	 * If no parameters are provided then the service_name is returned. If the service name is provided, then the
	 * topic is set to the value provided.
	 * @param string $topic
	 * @return $this
	 */
	public function topic($topic = null) {
		if (func_num_args() > 0) {
			$this->topic = $topic;
			return $this;
		}
		return $this->topic;
	}

	/**
	 * Gets or sets the token array using the key values of 'name' and 'hash'
	 * @param array $token_array
	 * @return array|$this
	 * @see token_name
	 * @see token_hash
	 */
	public function token($token_array = []) {
		if (func_num_args() > 0) {
			$this->token_name($token_array['name'] ?? '')->token_hash($token_array['hash'] ?? '');
			return $this;
		}
		return ['name' => $this->token_name, 'hash' => $this->token_hash];
	}

	/**
	 * Sets the token name
	 * @param string $token_name
	 * @return $this
	 * @see token_hash
	 */
	public function token_name($token_name  = '') {
		if (func_num_args() > 0) {
			$this->token_name = $token_name;
			return $this;
		}
		return $this->token_name;
	}

	/**
	 * Gets or sets the status code of this message
	 * @param int $status_code
	 * @return $this
	 */
	public function status_code($status_code = '') {
		if (func_num_args() > 0) {
			$this->status_code = $status_code;
			return $this;
		}
		return $this->status_code;
	}

	/**
	 * Gets or sets the resource id
	 * @param type $resource_id
	 * @return $this
	 */
	public function resource_id($resource_id = null) {
		if (func_num_args() > 0) {
			$this->resource_id = $resource_id;
			return $this;
		}
		return $this->resource_id;
	}

	/**
	 * Gets or sets the request ID
	 * @param type $request_id
	 * @return $this
	 */
	public function request_id($request_id = null) {
		if (func_num_args() > 0) {
			$this->request_id = $request_id;
			return $this;
		}
		return $this->request_id;
	}

	/**
	 * Gets or sets the status string
	 * @param type $status_string
	 * @return $this
	 */
	public function status_string( $status_string = null) {
		if (func_num_args() > 0) {
			$this->status_string = $status_string;
			return $this;
		}
		return $this->status_string;
	}

	/**
	 * Gets or sets the token hash
	 * @param type $token_hash
	 * @return $this
	 * @see token_name
	 */
	public function token_hash($token_hash = null) {
		if (func_num_args() > 0) {
			$this->token_hash = $token_hash;
			return $this;
		}
		return $this->token_hash;
	}

	/**
	 * Convert the 'statusString' key that comes from javascript
	 * @param type $status_string
	 * @return type
	 */
	public function statusString($status_string = '') {
		return $this->status_string($status_string);
	}

	/**
	 * Convert the 'statusCode' key that comes from javascript
	 * @param type $status_code
	 * @return $this
	 */
	public function statusCode($status_code = 200) {
		return $this->status_code($status_code);
	}

	/**
	 * Unwrap a JSON message to an associative array
	 * @param string $json_string
	 * @return array
	 */
	public static function unwrap($json_string = '') {
		return json_decode($json_string, true);
	}

	/**
	 * Helper function to respond with a connected message
	 * @param type $request_id
	 * @return type
	 */
	public static function connected($request_id = '') {
		return static::request_authentication($request_id);
	}

	/**
	 * Helper function to respond with a authentication message
	 * @param type $request_id
	 * @return type
	 */
	public static function request_authentication($request_id = '') {
		$class = static::class;
		return (new $class())
			->request_id($request_id)
			->service_name('authentication')
			->status_code(407)
			->status_string('Authentication Required')
			->topic('authenticate')
			->__toString()
		;
	}

	/**
	 * Helper function to respond with a bad request message
	 * @param type $request_id
	 * @param type $service
	 * @param type $topic
	 * @return type
	 */
	public static function request_is_bad($request_id = '', $service = '', $topic = '') {
		$class = static::class;
		return (new $class())
			->request_id($request_id)
			->service_name($service)
			->topic($topic)
			->status_code(400)
			->__toString()
		;
	}

	/**
	 * Helper function to respond with an authenticated message
	 * @param type $request_id
	 * @param type $service
	 * @param type $topic
	 * @return type
	 */
	public static function request_authenticated($request_id = '', $service = '', $topic = 'authenticated') {
		$class = static::class;
		return (new $class())
				->request_id($request_id)
				->service_name($service)
				->topic($topic)
				->status_code(200)
				->status_string('OK')
				->__toString()
		;
	}

	/**
	 * Helper function to respond with an unauthorized request message
	 * @param type $request_id
	 * @param type $service
	 * @param type $topic
	 * @return type
	 */
	public static function request_unauthorized($request_id = '', $service = '', $topic = 'unauthorized') {
		$class = static::class;
		return (new $class())
			->request_id($request_id)
			->service_name($service)
			->topic($topic)
			->status_code(401)
			->__toString()
		;
	}

	/**
	 * Helper function to respond with a forbidden message
	 * @param type $request_id
	 * @param type $service
	 * @param type $topic
	 * @return type
	 */
	public static function request_forbidden($request_id = '', $service = '', $topic = 'forbidden') {
		$class = static::class;
		return (new $class())
			->request_id($request_id)
			->service_name($service)
			->topic($topic)
			->status_code(403)
			->__toString()
		;
	}

	/**
	 * Returns a websocket_message object (or child object) using the provided JSON string or JSON array
	 * @param string|array $websocket_message_json JSON array or JSON string
	 * @return static|null Returns a new websocket_message object (or child object)
	 * @throws \InvalidArgumentException
	 */
	public static function create_from_json_message($websocket_message_json) {
		if (empty($websocket_message_json)) {
			// Nothing to do
			return null;
		} elseif (is_string($websocket_message_json)) {
			$json_array = json_decode($websocket_message_json, true);
		} elseif (is_array($websocket_message_json)) {
			$json_array = $websocket_message_json;
		} else {
			throw new \InvalidArgumentException("create_from_websocket_message_json expected string or array but got " . gettype($websocket_message_json));
		}

		return new static($json_array);
	}

}
