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
 * A base message for communication
 *
 * @author Tim Fry <tim@fusionpbx.com>
 * @param string $payload;
 */
class base_message {

	/**
	 * The id is contained to the base_message class. Subclasses or child classes should not adjust this value
	 * @var int
	 */
	private $id;

	/**
	 * Payload can be any value
	 * @var mixed
	 */
	protected $payload;

	/**
	 * Constructs a base_message object.
	 * When the array is provided as an associative array, the object properties
	 * are filled using the array key as the property name and the value of the array
	 * for the value of the property in the object.
	 * @param array $associative_properties_array
	 */
	public function __construct($associative_properties_array = []) {

		// Assign the unique object id given by PHP to identify the object
		$this->id = md5(spl_object_hash($this));

		// Assign the object properties using the associative array provided in constructor
		foreach ($associative_properties_array as $property_or_method => $value) {
			$this->__set($property_or_method, $value);
		}
	}

	/**
	 * Returns the property from the object.
	 * If the method exists then the method will be called to get the value in the object property.
	 * If the method is not in the object then the property name is checked to see if it is valid. If the
	 * name is not available then an exception is thrown.
	 * @param string $name Name of the property
	 * @return mixed
	 * @throws InvalidProperty
	 */
	public function __get(string $name) {
		if ($name === 'class') {
			return static::class;
		} elseif (method_exists($this, "get_$name")) {
			// call function with 'get_' prefixed
			return $this->{"get_$name"}();
		} elseif (method_exists($this, $name)) {
			// call function with name only
			return $this->{$name}();
		} elseif (property_exists($this, $name)) {
			// return the property from the object
			return $this->{$name};
		}
	}

	/**
	 * Sets the object property in the given name to be the given value
	 * @param string $name Name of the object property
	 * @param mixed $value Value of the object property
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function __set(string $name, $value): void {
		if (method_exists($this, "set_$name")){
			//
			// By calling the method with the setter name of the property first, we give
			// the child object the opportunity to modify the value before it is
			// stored in the object. In the case of the key names for an event this
			// allows that child class to adjust the event name from a key value of
			// 'Unique-Id' to be standardized to 'unique_id'.
			//
			$this->{"set_$name"}($value);
		} elseif (method_exists($this, $name)) {
			//
			// We next check for a function with the same name as the property. If the
			// method exists then we call the method with the same name instead of
			// setting the property directly. This allows the value to be adjusted
			// before it is set in the object. Similar to the previous check.
			//
			$this->{$name}($value);
		} elseif (property_exists($this, $name)) {
			//
			// Lastly, we check for the property to exist and set it directly. This
			// is so the property of the child message or base message can be set.
			//
			$this->{$name} = $value;
		}
	}

	/**
	 * Provides a method that PHP will call if the object is echoed or printed.
	 * @return string JSON string representing the object
	 * @depends to_json
	 */
	public function __toString(): string {
		return $this->to_json();
	}

	/**
	 * Returns this object ID given by PHP
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Sets the message payload to be delivered
	 * @param mixed $payload Payload for the message to carry
	 * @return $this Returns this object for chaining
	 */
	public function set_payload($payload) {
		$this->payload = $payload;
		return $this;
	}

	/**
	 * Returns the payload contained in this message
	 * @return mixed Payload in the message
	 */
	public function get_payload() {
		return $this->payload;
	}

	/**
	 * Alias of get_payload and set_payload. When the parameter
	 * is used to call the method, the payload property of the object
	 * is set to the payload provided and this object is returned. When
	 * the method is called with no parameters given, the payload is
	 * returned to the caller.
	 * Payload the message object is delivering
	 * @param mixed $payload If set, payload is set to the value. Otherwise, the payload is returned.
	 * @return mixed If payload was given to call the method, this object is returned. If no value was provided the payload is returned.
	 * @see set_payload
	 * @see get_payload
	 */
	public function payload($payload = null) {
		if (func_num_args() > 0) {
			return $this->set_payload($payload);
		}
		return $this->get_payload();
	}

	/**
	 * Recursively convert this object or child object to an array.
	 * @param mixed $iterate Private value to be set while iterating over the object properties
	 * @return array Array representing the properties of this object
	 */
	public function to_array($iterate = null): array {
		$array = [];
		if ($iterate === null) {
			$iterate = $this;
		}
		foreach ($iterate as $property => $value) {
			if (is_array($value)) {
				$value = $this->to_array($value);
			} elseif (is_object($value) && method_exists($value, 'to_array')) {
				$value = $value->to_array();
			} elseif (is_object($value) && method_exists($value, '__toArray')) {	// PHP array casting
				$value = $value->__toArray();
			}
			$array[$property] = $value;
		}
		return $array;
	}

	/**
	 * Returns a json string
	 * @return string
	 * @depends to_array
	 */
	public function to_json(): string {
		return json_encode($this->to_array());
	}

	/**
	 * Returns an array representing this object or child object.
	 * @return array Array of object properties
	 */
	public function __toArray(): array {
		return $this->to_array();
	}
}
