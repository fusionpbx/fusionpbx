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
 * @depends buffer::class
 */
class event_socket {
	private $buffer;
	public $fp;

	private static $socket = null;

	/**
	 * Create a new connection to the socket
	 * @param resource|false $fp
	 */
	public function __construct($fp = false) {
		$this->buffer = new buffer;
		$this->fp = $fp;
	}

	/**
	 * Ensures a closed connection on destruction of object
	 */
	public function __destructor() {
		$this->close();
	}

	/**
	 * Read the event body from the socket
	 * @return string|false Content body or false if not connected or empty message
	 * @depends buffer::class
	 */
	public function read_event() {
		if (!$this->connected()) {
			return false;
		}

		$b = $this->buffer;
		$content = array();

		while (true) {
			$line = $b->read_line();
			if ($line !== false) {
				if ($line === '') {
					break;
				}
				list($key, $value) = explode(':', $line, 2);
				$content[trim($key)] = trim($value);
			}

			if (feof($this->fp)) {
				break;
			}

			$buffer = fgets($this->fp, 1024);
			$b->append($buffer);
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
	 * Connect to the FreeSWITCH (c) event socket server
	 * <p>If the configuration is not loaded then the defaults of
	 * host 127.0.0.1, port of 8021, and default password of ClueCon will be used</p>
	 * @global array $conf Global configuration used in fusionpbx/config.conf
	 * @param string $host Host or IP address of FreeSWITCH event socket server. Defaults to 127.0.0.1
	 * @param string $port Port number of FreeSWITCH event socket server. Defaults to 8021
	 * @param string $password Password of FreeSWITCH event socket server. Defaults to ClueCon
	 * @param int $timeout_microseconds Number of microseconds before timeout is triggered on socket
	 * @return bool Returns true on success or false if not connected
	 */
	public function connect($host = null, $port = null, $password = null, $timeout_microseconds = 30000) {

		global $conf;

		//set the event socket variables in the order of
		//param passed to func, conf setting, old conf setting, default
		$host = $host ?? $conf['switch.event_socket.host'] ?? $conf['event_socket.ip_address'] ?? '127.0.0.1';
		$port = $port ?? $conf['switch.event_socket.port'] ?? $conf['event_socket.port'] ?? '8021';
		$password = $password ?? $conf['switch.event_socket.password'] ?? $conf['event_socket.password'] ?? 'ClueCon';

		//open the socket connection
		$this->fp = @fsockopen($host, $port, $errno, $errdesc, 3);

		if (!$this->connected()) {
			return false;
		}

		socket_set_timeout($this->fp, 0, $timeout_microseconds);
		socket_set_blocking($this->fp, true);

		//wait auth request and send response
		while ($this->connected()) {
			$event = $this->read_event();
			if(($event['Content-Type'] ?? '') === 'auth/request'){
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
	 * Tests if connected to the FreeSWITCH Event Socket Server
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
	 * alias of connected
	 * @return bool
	 */
	public function is_connected(): bool {
		return $this->connected();
	}

	/**
	 * Send a command to the FreeSWITCH Event Socket Server
	 * <p>Multi-line commands can be sent when separated by '\n'</p>
	 * @param string $cmd Command to send through the socket
	 * @return mixed Returns the response from FreeSWITCH or false if not connected
	 * @depends read_event()
	 */
	public function request($cmd) {
		if (!$this->connected()) {
			return false;
		}

		$cmd_array = explode("\n", $cmd);
		foreach ($cmd_array as &$value) {
			fputs($this->fp, $value."\n");
		}
		fputs($this->fp, "\n"); //second line feed to end the headers

		$event = $this->read_event();

		if (array_key_exists('$', $event)) {
			return $event['$'];
		}
		return $event;
	}

	/**
	 * Sets the current socket resource returning the old
	 * @param resource|bool $fp Sets the current FreeSWITCH resource
	 * @return mixed Returns the original resource
	 * @deprecated since version 5.1
	 */
	public function reset_fp($fp = false){
		$tmp = $this->fp;
		$this->fp = $fp;
		return $tmp;
	}

	/**
	 * Closes the socket
	 */
	public function close() {
		//fp is public access so ensure it is a resource before closing it
		if (is_resource($this->fp)) {
			try {
				fclose($this->fp);
			} catch (\Exception $t) {
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
	 * Create uses a singleton design to return a connected socket to the FreeSWITCH Event Socket Layer
	 * @global array $conf Global configuration used in config.conf
	 * @param string $host Host or IP address of FreeSWITCH event socket server. Defaults to 127.0.0.1
	 * @param string $port Port number of FreeSWITCH event socket server. Defaults to 8021
	 * @param string $password Password of FreeSWITCH event socket server. Defaults to ClueCon
	 * @param int $timeout_microseconds Number of microseconds before timeout is triggered on socket
	 * @return self
	 */
	public static function create($host = null, $port = null, $password = null, $timeout_microseconds = 30000): self {
		//create the event socket object
		if (self::$socket === null) {
			self::$socket = new event_socket();
		}
		//attempt to connect it
		if(!self::$socket->connected()) {
			self::$socket->connect($host, $port, $password, $timeout_microseconds);
		}
		return self::$socket;
	}

	/**
	 * Sends a command on the socket blocking for a response
	 * @param string $cmd
	 * @return string|false Response from server or false if failed
	 */
	public static function command(string $cmd) {
		return self::create()->request($cmd);
	}

	/**
	 * Sends an API command on the socket
	 * @param string $api_cmd
	 * @return string|false Response from server or false if failed
	 */
	public static function api(string $api_cmd) {
		return self::command('api '.$api_cmd);
	}

	/**
	 * Sends an API command to FreeSWITCH using asynchronous (non-blocking) mode
	 * @param string $cmd API command to send
	 * @returns string $job_id the Job ID for tracking completion status
	 */
	public static function async(string $cmd) {
		return self::command('bgapi '.$cmd);
	}
}

// $esl = event_socket::create('127.0.0.1', 8021, 'ClueCon');
// print($esl->request('api sofia status'));

?>
