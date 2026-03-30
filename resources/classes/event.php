<?php

class event {

	/**
	 * The data payload associated with the event
	 *
	 * @var array|null
	 */
	public $payload;

	/**
	 * Timestamp when the event was created
	 *
	 * @var int
	 */
	private $timestamp;

	/**
	 * The domain UUID used to create the event or the domain uuid that was passed as the domain
	 *
	 * @var string|null
	 */
	private $domain_uuid;

	/**
	 * The user UUID used to create the event or the user uuid that was passed as the user
	 *
	 * @var string|null
	 */
	private $user_uuid;

	/**
	 * The settings object used for the event
	 *
	 * @var settings|null
	 */
	private $settings;

	/**
	 * The name of the event
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Constructor for the event class.
	 *
	 * @param string $name   The name of the event. (Required)
	 * @param string $prefix Optional prefix for the event name. Defaults to 'on_'.
	 */
	public function __construct(string $name, string $prefix = 'on_') {
		// Ensure event name starts with the prefix for the event
		if (!str_starts_with($name, $prefix)) {
			$name = $prefix . $name;
		}
		$this->name = $name;

		// Set the timestamp when the event is created
		$this->timestamp = time();

		// Set default values to null
		$this->domain_uuid = null;
		$this->user_uuid   = null;
		$this->settings    = null;
		$this->payload     = null;
	}

	/**
	 * This method allows the event object to be called as a function, which will dispatch the event to any listeners.
	 *
	 * @param array|null $payload An optional associative array of data to pass to the event listeners.
	 *                            Defaults to null.
	 *
	 * @return self Returns the event object after dispatching the event.
	 */
	public function __invoke($payload = null): self {

		if (is_array($payload) && is_array($this->payload)) {
			// Update event data with any new data provided when the event is called
			$this->payload = array_merge($this->payload, $payload);
		} else {
			if ($payload !== null) {
				// If the existing payload is not an array, overwrite it with the new payload
				$this->payload = $payload;
			}
		}

		// Update event data with any new data provided when the event is called
		self::dispatch($this);
		return $this;
	}

	/**
	 * Retrieves a value from the event's data payload.
	 *
	 * This method checks if the key exists in the event's payload array and returns that value if found.
	 * If the key is not found, it returns the provided default value.
	 *
	 * @param string $key     The key to look for in the event's properties and payload.
	 * @param mixed  $default The default value to return if the key is not found. Defaults to null.
	 *
	 * @return mixed The value associated with the specified key from the event's payload,
	 *               or the default value if the key is not found.
	 */
	public function get($key, $default = null) {
		// Data is public so make sure it's an array before checking for the key in the data array
		if ($this->payload !== null && is_array($this->payload) && isset($this->payload[$key])) {
			return $this->payload[$key];
		}

		// return default if the payload is null
		return $this->payload ?? $default;
	}

	/**
	 * Sets a value in the event's data payload.
	 *
	 * This method allows you to set a key-value pair in the event's payload. If the payload is not
	 * already an array, it will be initialized as an array before setting the key-value pair. If
	 * the payload has data that is not an array, it will be overwritten with the new key-value pair.
	 *
	 * @param string $key   The key to set in the event's payload.
	 * @param mixed  $value The value to associate with the specified key in the event's payload.
	 *
	 * @return void
	 */
	public function set($key, $value): void {
		// Data is public so make sure it's an array before setting the key in the data array
		if (is_array($this->payload)) {
			$this->payload[$key] = $value;
		} else {
			//old value
			$old_value = $this->payload;
			if ($old_value !== null) {
				$this->payload = [
					0 => $old_value,
					$key => $value
				];
			} else {
				$this->payload = [$key => $value];
			}
		}

		return;
	}

	/**
	 * Timestamp when the event was created
	 *
	 * @return int
	 */
	public function get_timestamp(): int {
		return $this->timestamp;
	}

	/**
	 * The domain UUID used to create the event or the domain uuid that was passed as the domain
	 *
	 * @return null|string
	 */
	public function get_domain_uuid(): ?string {
		return $this->domain_uuid;
	}

	/**
	 * The user UUID used to create the event or the user uuid that was passed as the user
	 *
	 * @return null|string
	 */
	public function get_user_uuid(): ?string {
		return $this->user_uuid;
	}

	/**
	 * The name of the event
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * The data payload associated with the event
	 *
	 * @return array|null
	 */
	public function get_data(): ?array {
		return $this->payload;
	}

	/**
	 * The data payload associated with the event
	 *
	 * @return array|null
	 */
	public function get_payload(): ?array {
		return $this->payload;
	}

	/**
	 * The settings object used for the event
	 *
	 * @return settings|null
	 */
	public function get_settings(): ?settings {
		return $this->settings;
	}

	/**
	 * Set the name of the event
	 *
	 * @param string|null $name The name to set for the event
	 *
	 * @return void
	 */
	public function set_name(?string $name): void {
		$this->name = $name;
	}

	/**
	 * Set the domain UUID for the event
	 *
	 * @param string|null $domain_uuid The domain UUID to set for the event
	 *
	 * @return void
	 */
	public function set_domain_uuid(?string $domain_uuid): void {
		// Allow setting the domain_uuid to null to clear it from the event
		if ($domain_uuid === null) {
			$this->domain_uuid = null;
			return;
		}

		// Test for a valid UUID string before setting the domain_uuid property
		if (!is_uuid($domain_uuid)) {
			throw new InvalidArgumentException('Invalid domain UUID provided. Must be a valid UUID string or null.');
		}

		// Valid UUID string provided, set the domain_uuid property
		$this->domain_uuid = $domain_uuid;
	}

	/**
	 * Set the user UUID for the event
	 *
	 * @param string|null $user_uuid The user UUID to set for the event
	 *
	 * @return void
	 */
	public function set_user_uuid(?string $user_uuid): void {
		// Allow setting the user_uuid to null to clear it from the event
		if ($user_uuid === null) {
			$this->user_uuid = null;
			return;
		}

		// Test for a valid UUID string before setting the user_uuid property
		if (!is_uuid($user_uuid)) {
			throw new InvalidArgumentException('Invalid user UUID provided. Must be a valid UUID string or null.');
		}

		// Valid UUID string provided, set the user_uuid property
		$this->user_uuid = $user_uuid;
	}

	/**
	 * Set the settings object for the event
	 *
	 * @param settings $settings The settings object to set for the event
	 *
	 * @return void
	 */
	public function set_settings(?settings $settings): void {
		// Allow setting the settings to null to clear it from the event
		if ($settings === null) {
			$this->settings = null;
			return;
		}

		// Ensure the provided settings is an instance of the settings class before setting it to the event
		if (!($settings instanceof settings)) {
			throw new InvalidArgumentException('Invalid settings object provided. Must be an instance of the settings class.');
		}
		$this->settings = $settings;
	}

	/**
	 * Set the data payload for the event
	 *
	 * @param mixed $payload The data payload to set for the event
	 *
	 * @return void
	 */
	public function set_payload($payload): void {
		$this->payload = $payload;
	}

	/**
	 * This method allows for static calls to the event class, which will create a new event object
	 * and dispatch it to any listeners.
	 *
	 * @param string $event_name The name of the event to call.
	 * @param mixed  $payload    An optional associative array of data to pass to the event listeners.
	 *                           Defaults to null.
	 *
	 * @return self Returns the event object after dispatching the event.
	 */
	public static function __callStatic(string $event_name, $payload = null): self {
		// Ensure event name starts with 'on_' for all events
		if (!str_starts_with($event_name, 'on_')) {
			$event_name = 'on_' . $event_name;
		}

		// Create a new event object with the event name and set the payload from the static call arguments
		$event = new self($event_name);
		$event->set_payload($payload[0] ?? null);

		// Automatically set the domain and user UUID from the global SESSION variables
		$event->set_domain_uuid($_SESSION['domain_uuid'] ?? null);
		$event->set_user_uuid($_SESSION['user_uuid'] ?? null);

		// Automatically set the settings object
		global $settings;
		if ($settings instanceof settings) {
			$event->set_settings($settings);
		} else {
			$event->set_settings(new settings([
				'domain_uuid' => $event->get_domain_uuid(),
				'user_uuid'   => $event->get_user_uuid(),
			]));
		}

		// Call the event and pass the event object with the payload
		self::dispatch($event);

		// Return the event object after dispatching the event so that it can be
		// used for chaining or accessing the event payload data after dispatch.
		return $event;
	}

	/**
	 * Notifies any listener waiting for a specific event by calling the name from $event_name.
	 *
	 * @param string $event_name The name of the event to call.
	 * @param array  $event_data An associative array of data to pass to the event listeners.
	 *
	 * @return void
	 */
	public static function dispatch(event $event): void {

		// Event name must not be empty
		$event_name = $event->get_name();
		if (empty($event_name)) {
			return;
		}

		// Attempt to use the already loaded classes from the auto_loader to find any listeners for this event
		global $autoload;
		if (!($autoload instanceof auto_loader)) {
			$autoload = new auto_loader(true);
		}

		// Get all classes that implement the event_listener interface and call the method matching
		// the event name if it exists
		$listeners = $autoload->get_interface_list('event_listener');
		foreach ($listeners as $listener) {
			// Check for the event in the class
			if (method_exists($listener, $event_name)) {
				// Call the event in that class and pass the event object with the payload
				$listener::$event_name($event);
			}
			// Check for short-circuiting the event dispatch
			if (empty($event->get_name())) {
				break;
			}
		}
	}
}
