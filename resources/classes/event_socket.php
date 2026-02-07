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
	public function __destructor() {
		$this->close();
	}

	/**
	 * Close the socket connection with the FreeSWITCH Event Socket Server.
	 *
	 * @return void
	 */
	public function close() {
		//fp is public access so ensure it is a resource before closing it
		if (is_resource($this->fp)) {
			try {
				fclose($this->fp);
			} catch (Exception $t) {
				//report it
				trigger_error("event_socket failed to close socket", E_USER_WARNING);
			}
		} else {
			//log an error if fp was set to something other than a resource
			if ($this->fp !== false) {
				trigger_error("event_socket not a resource", E_USER_ERROR);
			}
		}
		//force fp to be false
		$this->fp = false;
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
