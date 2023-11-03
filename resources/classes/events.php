<?php

	/**
	 * Events class:
	 * - manages events in the FusionPBX
	 * use:
	 * $e = new Events(__DIR__ . '/listeners'); // no trailing slash
	 */
//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";

	if (!class_exists('Events')) {

		class Events {

			/** @var array objects of events */
			private $event_listeners;

			/** @var array objects of errors */
			private $errors;

			public function __construct(string $directory) {
				$this->errors = [];
				$this->event_listeners = [];
				if (empty($directory)) {
					$directory = __DIR__ . "/listeners";
				}
				if (is_dir($directory)) {
					$this->load_events($directory);
				}
			}

			private function load_events(string $directory) {
				$events = glob($directory . '/*.php');
				foreach ($events as $event_listener) {
					include $event_listener;
					$class = basename($event_listener, ".php");
					$listener = new $class;
					// make sure the event_listener has been implemented
					if ($listener instanceof event_listener) {
						$this->add_listener($listener);
					}
				}
			}

			private function get_name(event $event) {
				$name = $event->name();
				// when the name is API override it with API name
				if (!empty($event->api_command())) {
					$name = $event->api_command();
				}
				return $name;
			}

			public function execute_event(event $event) {
				$name = $this->get_name($event);

				//ensure we have listeners for that event
				if (empty($this->event_listeners[$name]))
					return;

				//go through all registered listeners for the event
				foreach ($this->event_listeners[$event->name()] as $listener) {
					try {
						$listener->exec($event);
					} catch (\Throwable $e) {
						$this->errors[] = $e;
					}
				}
			}

			public function add_listener(event_listener $listener) {
				$event = $listener->event_name();
				$class = get_class($listener);
				$this->event_listeners[$event][$class] = $listener;
			}

			public function remove_listener(event_listener $event_listener) {
				$event_name = $event_listener->event_name();
				$classname = get_class($event_listener);
				unset ($this->event_listeners[$event_name][$classname]);
			}
		}

	}

/*
 Example:


//Events will automatically load and register all listeners in the directory provided
$events = new Events();
$events->execute_event(new \event(
		uuid()
		, "Event-Name: RELOADXML\n"
		. "Core-UUID: e0943a8d-9d09-4446-bce8-da000a403b47\n"
		. "FreeSWITCH-Hostname: fs\n"
		. "FreeSWITCH-Switchname: fs\n"
		. "FreeSWITCH-IPv4: 172.20.0.5\n"
		. "FreeSWITCH-IPv6: ::1\n"
		. "Event-Date-Local: 2023-11-02 12:39:56\n"
		. "Event-Date-GMT: Thu, 02 Nov 2023 12:39:56 GMT\n"
		. "Event-Date-Timestamp: 1698928796942090\n"
		. "Event-Calling-File: switch_xml.c\n"
		. "Event-Calling-Function: switch_xml_open_root\n"
		. "Event-Calling-Line-Number: 2388\n"
		. "Event-Sequence: 21604\n"
		, ""
	)
);
*/