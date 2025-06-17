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
 * Tracks switch events in an object instead of array
 *
 * @author Tim Fry <tim@fusionpbx.com>
 */
class event_message implements filterable_payload {

	const BODY_ARRAY_KEY = '_body';

	const EVENT_SWAP_API = 0x01;
	const EVENT_USE_SUBCLASS = 0x02;

	// Default keys in the event to capture
	public static $keys = [];

	/**
	 * Associative array to store the event with the key name always lowercase and the hyphen replaced with an underscore
	 * @var array
	 */
	private $event;
	private $body;

	/**
	 * Only permitted keys on this list are allowed to be inserted in to the event_message object
	 * @var filter
	 */
	private $event_filter;

	/**
	 * Creates an event message
	 * @param array $event_array
	 * @param filter $filter
	 */
	public function __construct(array $event_array, ?filter $filter = null) {

		// Set the event to an empty array
		$this->event = [];

		// Clear the memory area for body and key_filter
		$this->body = null;

		$this->event_filter = $filter;

		// Set the event array to match
		foreach ($event_array as $name => $value) {
			$this->__set($name, $value);
		}

	}

	/**
	 * Sanitizes the key name and then stores the value in the event property as an associative array
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function __set(string $name, $value) {
		self::sanitize_event_key($name);

		// Use the filter chain to ensure the key is allowed
		if ($this->event_filter === null || ($this->event_filter)($name, $value)) {
			$this->event[$name] = $value;
		}
	}

	/**
	 * Sanitizes the key name and then returns the value stored in the event property
	 * @param string $name Name of the event key
	 * @return string Returns the stored value or an empty string
	 */
	public function __get(string $name) {
		self::sanitize_event_key($name);
		if ($name === 'name') $name = 'event_name';
		return $this->event[$name] ?? '';
	}

	public function __toArray(): array {
		$array = [];
		foreach ($this->event as $key => $value) {
			$array[$key] = $value;
		}
		return $array;
	}

	public function to_array(): array {
		return $this->__toArray();
	}

	public function apply_filter(filter $filter) {
		foreach ($this->event as $key => $value) {
			$result = ($filter)($key, $value);
			if ($result === null) {
				$this->event = [];
			} elseif (!$result) {
				unset($this->event[$key]);
			}
		}
		return $this;
	}

	public static function parse_active_calls($json_string): array {
		$calls = [];
		$json_array = json_decode($json_string, true);
		if (empty($json_array["rows"])) {
			return $calls;
		}
		foreach ($json_array["rows"] as $call) {
			$message = new event_message($call);
			// adjust basic info to match an event setting the callstate to ringing
			// so that a row can be created for it
			$message['event_name'] = 'CHANNEL_CALLSTATE';
			$message['answer_state'] = 'ringing';
			$message['channel_call_state'] = 'ACTIVE';
			$message['unique_id'] = $call['uuid'];
			$message['call_direction'] = $call['direction'];

			//set the codecs
			$message['caller_channel_created_time'] = intval($call['created_epoch']) * 1000000;
			$message['channel_read_codec_name'] = $call['read_codec'];
			$message['channel_read_codec_rate'] = $call['read_rate'];
			$message['channel_write_codec_name'] = $call['write_codec'];
			$message['channel_write_codec_rate'] = $call['write_rate'];

			//get the profile name
			$message['caller_channel_name'] = $call['name'];

			//domain or context
			$message['caller_context'] = $call['context'];
			$message['caller_caller_id_name'] = $call['initial_cid_name'];
			$message['caller_caller_id_number'] = $call['initial_cid_num'];
			$message['caller_destination_number'] = $call['initial_dest'];
			$message['application'] = $call['application'] ?? '';
			$message['secure'] = $call['secure'] ?? '';
			$calls[] = $message;
		}
		return $calls;
	}

	/**
	 * Creates a websocket_message_event object from a json string
	 * @param type $json_string
	 * @return self|null
	 */
	public static function create_from_json($json_string) {
		if (is_array($json_string)) {
			print_r(debug_backtrace());
			die();
		}
		$array = json_decode($json_string, true);
		if ($array !== false) {
			return new static($array);
		}
		return null;
	}

	public static function create_from_switch_event($raw_event, filter $filter = null, $flags = 3): self {

		// Set the options from the flags passed
		$swap_api_name_with_event_name = ($flags & self::EVENT_SWAP_API) !== 0;
		$swap_subclass_event_name_with_event_name = ($flags & self::EVENT_USE_SUBCLASS) !== 0;

		// Get the payload and ignore the headers
		if (is_array($raw_event) && isset($raw_event['$'])) {
			$raw_event = $raw_event['$'];
		}

		//check if it is still an array
		if (is_array($raw_event)) {
			$raw_event = array_pop($raw_event);
		}

		$event_array = [];
		foreach (explode("\n", $raw_event) as $line) {
			$parts = explode(':', $line, 2);
			$key = '';
			$value = '';
			if (count($parts) > 0) {
				$key = $parts[0];
			}
			if (count($parts) > 1) {
				$value = urldecode(trim($parts[1]));
			}
			if (!empty($key)) {
				$event_array[$key] = $value;
			}
		}

		//check for body
		if (!empty($event_array['Content-Length'])) {
			$event_array['_body'] = substr($raw_event, -1*$event_array['Content-Length']);
		}

		// Instead of using 'CUSTOM' for the Event-Name we use the actual API-Command when it is available instead
		if ($swap_api_name_with_event_name && !empty($event_array['API-Command'])) {
			// swap the values
			[$event_array['Event-Name'], $event_array['API-Command']] = [$event_array['API-Command'], $event_array['Event-Name']];
		}

		// Promote the Event-Subclass name to the Event-Name
		if ($swap_subclass_event_name_with_event_name && !empty($event_array['Event-Subclass'])) {
			// swap the values
			[$event_array['Event-Name'], $event_array['Event-Subclass']] = [$event_array['Event-Subclass'], $event_array['Event-Name']];
		}

		// Return the new object
		return new static($event_array, $filter);
	}

	/**
	 * Return a Json representation for this object when the object is echoed or printed
	 * @return string
	 * @override websocket_message
	 */
	public function __toString(): string {
		return json_encode($this->to_array());
	}

	/**
	 * Set or Get the body
	 * @param null|string $body
	 * @return self|string
	 */
	public function body(?string $body = null) {

		// Check if we are setting the value for body
		if (func_num_args() > 0) {

			// Set the value
			$this->body = $body;

			// Return the object for chaining
			return $this;
		}

		// A request was made to get the value from body
		return $this->body;
	}

	public function event_to_array(): array {
		$array = [];
		foreach ($this->event as $key => $value) {
			$array[$key] = $value;
		}
		if ($this->body !== null) {
			$array[self::BODY_ARRAY_KEY] = $this->body;
		}
		return $array;
	}

	public function getIterator(): \Traversable {
		yield from $this->event_to_array();
	}

	public function offsetExists(mixed $offset): bool {
		self::sanitize_event_key($offset);
		return isset($this->event[$offset]);
	}

	public function offsetGet(mixed $offset): mixed {
		self::sanitize_event_key($offset);
		if ($offset === self::BODY_ARRAY_KEY) {
			return $this->body;
		}
		return $this->event[$offset];
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		self::sanitize_event_key($offset);
		if ($offset === self::BODY_ARRAY_KEY) {
			$this->body = $value;
		} else {
			$this->event[$offset] = $value;
		}
	}

	public function offsetUnset(mixed $offset): void {
		self::sanitize_event_key($offset);
		if ($offset === self::BODY_ARRAY_KEY) {
			$this->body = null;
		} else {
			unset($this->event[$offset]);
		}
	}

	/**
	 * Sanitizes key by replacing '-' with '_', converts to lowercase, and only allows digits 0-9 and letters a-z
	 * @param string $key
	 * @return string
	 */
	public static function sanitize_event_key(string &$key) /* : never */ {
		$key = preg_replace('/[^a-z0-9_]/', '', str_replace('-', '_', strtolower($key)));
		//rewrite 'name' to 'event_name'
		if ($key === 'name') $key = 'event_name';
	}
}
