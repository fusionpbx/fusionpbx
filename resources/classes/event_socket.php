<?php

class buffer {
	private $content;
	private $eol;

	public function __construct() {
		$this->content = '';
		$this->eol = "\n";
	}

	public function append($str) {
		$this->content .= $str;
	}

	public function read_line() {
		$ar = explode($this->eol, $this->content, 2);
		if (count($ar) != 2) {
			return false;
		}
		$this->content = $ar[1];
		return $ar[0];
	}

	public function read_n($n) {
		if (strlen($this->content) < $n) {
			return false;
		}
		$s = substr($this->content, 0, $n);
		$this->content = substr($this->content, $n);
		return $s;
	}

	public function read_all($n) {
		$tmp = $this->content;
		$this->content = '';
		return $tmp;
	}
}

//$b = new buffer;
//$b->append("hello\nworld\n");
//print($b->read_line());
//print($b->read_line());

/**
 * Subscribes to the event socket of the FreeSWITCH (c) Event Socket Server
 *
 * @depends buffer::class
 */
class event_socket {
	private static $socket = null;
	public $fp;
	/**
	 * Used as a flag to determine if the socket should be created automatically
	 *
	 * @var bool
	 */
	protected $auto_create;
	private $buffer;
	private $config;

	/**
	 * Create a new connection to the socket
	 *
	 * @param resource|false $fp
	 */
	public function __construct($fp = false, ?config $config = null) {
		$this->buffer = new buffer;
		$this->auto_create = $fp === false;
		$this->fp = $fp;
		$this->config = $config ?? config::load();
	}

	/**
	 * Sends an API command on the socket
	 *
	 * @param string $api_cmd
	 *
	 * @return string|false Response from server or false if failed
	 * @static
	 */
	public static function api(string $api_cmd) {
		return self::command('api ' . $api_cmd);
	}

	/**
	 * Sends a command on the socket blocking for a response
	 *
	 * @param string $cmd
	 *
	 * @return string|false Response from server or false if failed
	 * @static
	 */
	public static function command(string $cmd) {
		return self::create()->request($cmd);
	}

	/**
	 * Send a command to the FreeSWITCH Event Socket Server
	 * <p>Multi-line commands can be sent when separated by '\n'</p>
	 *
	 * @param string $cmd Command to send through the socket
	 *
	 * @return mixed Returns the response from FreeSWITCH or false if not connected
	 * @depends read_event()
	 */
	public function request($cmd) {
		if (!$this->connected()) {
			return false;
		}

		// Add filter when the request includes 'filter EVENT_NAME', or 'filter CUSTOM subclass'
		// matched by Reply-Text (skipping interleaved async events) and the wait is
		// bounded instead of blocking until an event of any kind arrives
		if (preg_match('/^filter ([A-Z][A-Z_]*) ?(\S*)\s*$/', $cmd, $m)) {
			if ($m[1] === 'CUSTOM' && $m[2] !== '') {
				[$field, $value] = ['Event-Subclass', $m[2]];
			} elseif ($m[1] !== 'CUSTOM' && $m[2] === '') {
				[$field, $value] = ['Event-Name', $m[1]];
			} else {
				$field = null;
			}
			if ($field !== null) {
				if ($this->add_filter($field, $value)) {
					return ['Content-Type' => 'command/reply', 'Reply-Text' => "+OK filter added. [{$field}]=[$value]"];
				}
				// No confirmation from event socket within the timeout
				return false;
			}
		}

		$cmd_array = explode("\n", $cmd);
		foreach ($cmd_array as $value) {
			fputs($this->fp, $value . "\n");
		}
		fputs($this->fp, "\n"); //second line feed to end the headers

		$event = $this->read_event();

		if (array_key_exists('$', $event)) {
			return $event['$'];
		}
		return $event;
	}

	/**
	 * Read the event body from the socket
	 * 
	 * @param int|null $timeout the timeout in microseconds for stream_select, Default 10000
	 * 
	 * @return mixed Content body or false if not connected or empty message
	 * @depends buffer::class
	 */
	public function read_event($timeout = 10000) {
		if (!$this->connected()) {
			return false;
		}

		$b = $this->buffer;
		$content = [];

		while (true) {
			$line = $b->read_line();
			if ($line !== false) {
				if ($line === '') {
					break;
				}
				[$key, $value] = explode(':', $line, 2);
				$content[trim($key)] = trim($value);
			}

			if (feof($this->fp)) {
				break;
			}

			// Check if we have data available
			$read = [$this->fp];
			$write = [];
			$except = [];
			if (stream_select($read, $write, $except, 0, $timeout) > 0) {
				$buffer = fgets($this->fp, 1024);
				if ($buffer !== false) {
					$b->append($buffer);
				}
			}
		}

		if (array_key_exists('Content-Length', $content)) {
			$str = $b->read_n($content['Content-Length']);
			if ($str === false) {
				while (true) {
					if (!$this->connected()) {
						break;
					}

					$buffer = fgets($this->fp, 1024);
					$b->append($buffer);
					$str = $b->read_n($content['Content-Length']);
					if ($str !== false) {
						break;
					}
				}
			}
			if ($str !== false) {
				$content['$'] = $str;
			}
		}

		return $content;
	}

	/**
	 * Read an event from the socket giving up after a hard timeout
	 * <p>Unlike read_event(), which polls indefinitely until a complete event
	 * has been received, this method returns false when the total timeout
	 * expires or the connection is lost. Bytes already received are kept in
	 * the internal buffer, so a later read_event() or read_event_bounded()
	 * call continues where this one left off. The stream is set to
	 * non-blocking for the duration of the read (like drain()) so the wait
	 * can never run past the deadline, and its original blocking state is
	 * restored afterwards.</p>
	 *
	 * @param int $timeout Total timeout in microseconds, Default 10000
	 *
	 * @return array|false Event headers (plus the body under the '$' key when
	 *                     present) or false on timeout or when not connected
	 * @depends buffer::class
	 */
	public function read_event_bounded($timeout = 10000) {
		if (!$this->connected()) {
			return false;
		}

		// Work non-blocking so the wait can never run past the deadline,
		// then restore the original blocking state
		$meta = stream_get_meta_data($this->fp);
		$blocking = $meta['blocked'] ?? true;
		if ($blocking && !stream_set_blocking($this->fp, false)) {
			return false;
		}

		try {
			$b = $this->buffer;
			$content = [];
			$deadline = microtime(true) + ($timeout / 1000000);

			while (true) {
				$line = $b->read_line();
				if ($line !== false) {
					if ($line === '') {
						break;
					}
					[$key, $value] = explode(':', $line, 2);
					$content[trim($key)] = trim($value);
					// keep pulling lines already available in the buffer
					continue;
				}

				if (feof($this->fp)) {
					break;
				}

				// give up when the total timeout expires
				if (microtime(true) >= $deadline) {
					return false;
				}

				$read = [$this->fp];
				$write = [];
				$except = [];
				$remaining = (int) max(0, ($deadline - microtime(true)) * 1000000);
				if (stream_select($read, $write, $except, 0, $remaining) > 0) {
					$chunk = fread($this->fp, 8192);
					if ($chunk !== false && $chunk !== '') {
						$b->append($chunk);
					}
				} else {
					// stream_select timed out before any data arrived
					return false;
				}
			}

			if (array_key_exists('Content-Length', $content)) {
				$str = $b->read_n($content['Content-Length']);
				while ($str === false) {
					if (microtime(true) >= $deadline || feof($this->fp)) {
						break;
					}

					$read = [$this->fp];
					$write = [];
					$except = [];
					$remaining = (int) max(0, ($deadline - microtime(true)) * 1000000);
					if (stream_select($read, $write, $except, 0, $remaining) > 0) {
						$chunk = fread($this->fp, 8192);
						if ($chunk !== false && $chunk !== '') {
							$b->append($chunk);
						}
					}
					$str = $b->read_n($content['Content-Length']);
				}
				if ($str !== false) {
					$content['$'] = $str;
				}
			}

			return $content;
		} finally {
			if ($blocking) {
				stream_set_blocking($this->fp, true);
			}
		}
	}

	/**
	 * Send a single-line command and wait for a specific reply text
	 * <p>FreeSWITCH answers client commands with a 'command/reply' event
	 * that carries the result in its Reply-Text header (e.g.
	 * '+OK filter added. [Event-Name]=[CHANNEL_CREATE]'). Asynchronous
	 * events may be interleaved on the same socket, so events are read and
	 * skipped until the expected reply arrives or the total timeout
	 * expires. Unlike a plain read_event() loop, a missing reply or a
	 * '-ERR' reply never causes an infinite wait.</p>
	 *
	 * @param string $cmd Command to send without line endings
	 * @param string $expected Exact Reply-Text value that confirms success
	 * @param int $timeout Timeout in microseconds, Default 3000000
	 *
	 * @return bool True when the expected reply was received, false on timeout or disconnect
	 * @depends read_event_bounded()
	 */
	private function send_and_wait(string $cmd, string $expected, int $timeout = 3000000): bool {
		if (!$this->connected()) {
			return false;
		}

		fputs($this->fp, $cmd . "\n");
		fputs($this->fp, "\n"); //second line feed to end the headers

		$deadline = microtime(true) + ($timeout / 1000000);

		while (microtime(true) < $deadline) {
			$remaining = (int) max(1000, ($deadline - microtime(true)) * 1000000);
			$event = $this->read_event_bounded($remaining);
			if ($event === false) {
				//timeout or connection lost
				return false;
			}
			if (($event['Reply-Text'] ?? '') === $expected) {
				return true;
			}
			//asynchronous events and unrelated replies are skipped
		}

		return false;
	}

	/**
	 * Add a filter to the socket connection
	 * <p>Sends the ESL 'filter' command so FreeSWITCH only delivers events
	 * that match the given header field and value, for example:</p>
	 * <code>
	 * $socket->add_filter('Event-Name', 'CHANNEL_CREATE');
	 * $socket->add_filter('Event-Subclass', 'valet_parking::info');
	 * </code>
	 * <p>Multiple filters are OR conditions on the FreeSWITCH side: an event
	 * is delivered when it matches any registered filter.</p>
	 *
	 * @param string $field Event header to filter on (e.g. 'Event-Name', 'Event-Subclass', 'API-Command')
	 * @param string $value Value the header must match; a leading '/' enables regex matching
	 * @param int $timeout Timeout in microseconds to wait for the reply, Default 3000000
	 *
	 * @return bool True when FreeSWITCH confirmed the filter was added, false otherwise
	 * @depends send_and_wait()
	 */
	public function add_filter(string $field, string $value, int $timeout = 3000000): bool {
		if ($field === '' || $value === '') {
			return false;
		}
		return $this->send_and_wait("filter {$field} {$value}", "+OK filter added. [{$field}]=[".$value."]", $timeout);
	}

	/**
	 * Register multiple event filters on the socket connection
	 * <p>Accepts the same structure used by the service event lists
	 * (e.g. active_calls_service::SWITCH_EVENTS): a list of Field => Value
	 * pairs, for example:</p>
	 * <code>
	 * [
	 *     ['Event-Name' => 'CHANNEL_CREATE'],
	 *     ['Event-Subclass' => 'valet_parking::info'],
	 * ]
	 * </code>
	 *
	 * @param array $filters List of event filter pairs to register
	 * @param int $timeout Timeout in microseconds to wait for each reply, Default 3000000
	 *
	 * @return array List of "Field=Value" pairs that failed to register, empty when all succeeded
	 * @depends add_filter()
	 */
	public function register_filters(array $filters, int $timeout = 3000000): array {
		$failed = [];

		foreach ($filters as $pair) {
			if (!is_array($pair)) {
				continue;
			}
			foreach ($pair as $field => $value) {
				if (!$this->add_filter((string) $field, (string) $value, $timeout)) {
					$failed[] = "{$field}={$value}";
				}
			}
		}

		return $failed;
	}

	/**
	 * Clear event filters from the socket connection
	 * <p>FreeSWITCH supports 'filter delete all' to remove every filter,
	 * 'filter delete Field' to remove all filters of a given header and
	 * 'filter delete Field Value' to remove a specific filter.</p>
	 *
	 * @param null|string $field Header name to clear; null clears all filters
	 * @param null|string $value Specific filter value to clear, only used when $field is provided
	 * @param int $timeout Timeout in microseconds to wait for the reply, Default 3000000
	 *
	 * @return bool True when FreeSWITCH confirmed the deletion, false on timeout or disconnect
	 * @depends send_and_wait()
	 */
	public function clear_filters(?string $field = null, ?string $value = null, int $timeout = 3000000): bool {
		if ($field !== null && $value === null) {
			$cmd = "filter delete {$field}";
			$expected = "+OK filter deleted. [{$field}][]";
		} elseif ($field !== null) {
			$cmd = "filter delete {$field} {$value}";
			$expected = "+OK filter deleted. [{$field}][{$value}]";
		} else {
			$cmd = 'filter delete all';
			$expected = '+OK filter deleted. [all][]';
		}
		return $this->send_and_wait($cmd, $expected, $timeout);
	}

	/**
	 * Create uses a singleton design to return a connected socket to the FreeSWITCH Event Socket Layer
	 *
	 * @param string $host                 Host or IP address of FreeSWITCH event socket server. Defaults to 127.0.0.1
	 * @param string $port                 Port number of FreeSWITCH event socket server. Defaults to 8021
	 * @param string $password             Password of FreeSWITCH event socket server. Defaults to ClueCon
	 * @param int    $timeout_microseconds Number of microseconds before timeout is triggered on socket
	 *
	 * @return self
	 * @global array $conf                 Global configuration used in config.conf
	 * @static
	 */
	public static function create($host = null, $port = null, $password = null, $timeout_microseconds = 30000): self {
		//create the event socket object
		if (self::$socket === null) {
			self::$socket = new event_socket();
		}
		//attempt to connect it
		if (!self::$socket->connected()) {
			self::$socket->connect($host, $port, $password, $timeout_microseconds);
		}
		return self::$socket;
	}

	/**
	 * Connect to the FreeSWITCH (c) event socket server
	 * <p>If the configuration is not loaded then the defaults of
	 * host 127.0.0.1, port of 8021, and default password of ClueCon will be used</p>
	 *
	 * @param null|string     $host                 Host or IP address of FreeSWITCH event socket server. Defaults to
	 *                                              127.0.0.1
	 * @param null|string|int $port                 Port number of FreeSWITCH event socket server. Defaults to 8021
	 * @param null|string     $password             Password of FreeSWITCH event socket server. Defaults to ClueCon
	 * @param int             $timeout_microseconds Number of microseconds before timeout is triggered on socket.
	 *                                              Defaults to 30,000
	 *
	 * @return bool Returns true on success or false if not connected
	 */
	public function connect($host = null, $port = null, $password = null, $timeout_microseconds = 30000) {
		//set the event socket variables in the order of
		//param passed to func, conf setting, old conf setting, default
		$host = $host ?? $this->config->get('switch.event_socket.host', null) ?? $this->config->get('event_socket.ip_address', null) ?? '127.0.0.1';
		$port = intval($port ?? $this->config->get('switch.event_socket.port', null) ?? $this->config->get('event_socket.port', null) ?? '8021');
		$password = $password ?? $this->config->get('switch.event_socket.password', null) ?? $this->config->get('event_socket.password', null) ?? 'ClueCon';

		//if a socket was provided in the constructor, then don't create a new one
		if ($this->fp === false || $this->auto_create) {
			//open the socket connection
			$this->fp = @fsockopen($host, $port, $errno, $errdesc, 3);
		}

		if (!$this->connected()) {
			return false;
		}

		socket_set_timeout($this->fp, 0, $timeout_microseconds);
		socket_set_blocking($this->fp, true);

		//wait auth request and send a response
		while ($this->connected()) {
			$event = $this->read_event();
			if (($event['Content-Type'] ?? '') === 'auth/request') {
				fputs($this->fp, "auth $password\n\n");
				break;
			}
		}

		//wait auth response
		while ($this->connected()) {
			$event = $this->read_event();
			if (($event['Content-Type'] ?? '') === 'command/reply') {
				if (($event['Reply-Text'] ?? '') === '+OK accepted') {
					break;
				} else {
					$this->close();
				}
			}
		}

		return $this->connected();
	}

	/**
	 * Sends an API command to FreeSWITCH using asynchronous (non-blocking) mode
	 *
	 * @param string $cmd API command to send
	 *
	 * @returns string $job_id the Job ID for tracking completion status
	 * @static
	 */
	public static function async(string $cmd) {
		return self::command('bgapi ' . $cmd);
	}

	/**
	 * Ensures a closed connection on destruction of object
	 */
	public function __destruct() {
		// Only close the resources that we created in this class
		if ($this->auto_create) {
			$this->close();
		}
	}

	/**
	 * Close the socket connection with the FreeSWITCH Event Socket Server.
	 *
	 * @return void
	 */
	public function close() {
		// fp is public access so ensure it is a resource before closing it
		if (is_resource($this->fp)) {
			try {
				// Failing to empty the incoming buffer prevents the socket from closing
				$this->drain();
				// Close the stream with the '@' to suppress warnings (new PHP versions may emit warnings on fclose)
				@fclose($this->fp);
			} catch (Throwable $t) {
				// Silently fail
			}
		}

		// Force fp to be false
		$this->fp = false;
	}

	/**
	 * Drain the inbound data buffer and discard any pending data
	 * 
	 * The drain method ensures that any pending data in the socket buffer is read and discarded, preventing
	 * blocking issues when closing the socket. Some streams may be non-blocking already, in which case this
	 * method will not alter their state. This method is typically used internally before closing the socket
	 * to ensure no data is left unread. Draining the buffer should not be used for normal data processing,
	 * as it discards all incoming data. Drain should not be used with unix domain sockets as it may
	 * interfere with normal socket operations.
	 *
	 * @return void
	 * @throws Throwable if an error occurs while restoring the stream's blocking state
	 */
	public function drain(): void {
		// If the stream is not a valid resource, there is nothing to drain
		if (!is_resource($this->fp)) {
			return;
		}
		
		// The $fp can be passed in the constructor and may be a non-blocking stream, so capture the original blocking state so it is preserved on restore
		$meta = stream_get_meta_data($this->fp);
		$blocking = $meta['blocked'] ?? true;

		// Set the stream to non-blocking when necessary to drain the buffer without blocking
		if ($blocking && !stream_set_blocking($this->fp, false)) {
			// failed to set the stream to non-blocking mode, cannot drain
			return;
		}

		// Limit the maximum number of bytes to read from the stream while draining so we don't get stuck in an infinite loop
		$max_read_bytes = 1 * 1024 * 1024; // 1 MB

		try {
			// Read the data until the eof is reached or no more data is available
			while (!feof($this->fp)) {
				$bytes = fread($this->fp, 8196);
				if ($bytes === false || $bytes === '') {
					break;
				}

				// Decrement the remaining allowed read bytes and check if the limit has been reached
				$max_read_bytes -= strlen($bytes);

				// Stop draining if the maximum allowed read bytes have been reached
				if ($max_read_bytes <= 0) {
					break;
				}
			}
		} finally {
			// Restore the original blocking state of the stream
			stream_set_blocking($this->fp, $blocking);
		}

	}

	/**
	 * alias of connected
	 *
	 * @return bool
	 */
	public function is_connected(): bool {
		return $this->connected();
	}

	/**
	 * Tests if connected to the FreeSWITCH Event Socket Server
	 *
	 * @return bool Returns true when connected or false when not connected
	 */
	public function connected(): bool {
		if (!is_resource($this->fp)) {
			//not connected to the socket
			return false;
		}
		if (feof($this->fp) === true) {
			//not connected to the socket
			return false;
		}
		//connected to the socket
		return true;
	}

	/**
	 * Sets the current socket resource returning the old
	 *
	 * @param resource|bool $fp Sets the current FreeSWITCH resource
	 *
	 * @return mixed Returns the original resource
	 * @deprecated since version 5.1
	 */
	public function reset_fp($fp = false) {
		$tmp = $this->fp;
		$this->fp = $fp;
		return $tmp;
	}
}

// $esl = event_socket::create('127.0.0.1', 8021, 'ClueCon');
// print($esl->request('api sofia status'));
