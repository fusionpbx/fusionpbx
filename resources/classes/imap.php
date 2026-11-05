<?php

/*
 * imap class
 *
 * Pure PHP IMAP/POP3 client used to replace the PHP imap extension for the
 * methods required by the fax application. Connection strings use the same
 * format as the PHP imap extension, e.g.:
 *     {mail.example.com:993/imap/ssl/novalidate-cert}INBOX
 *
 * Usage (replaces imap_open/imap_close and friends):
 *     $connection = new imap();
 *     if (!$connection->open($connection_string, $username, $password)) {
 *         print_r($connection->errors());
 *     }
 */

//define the imap extension constants when the extension is not loaded
if (!defined('OP_READONLY')) {
	define('OP_READONLY', 0);
	define('OP_AN', 1);
	define('OP_NOHEADER', 2);
	define('OP_VERBOSE', 4);
}
if (!defined('FT_UID')) {
	define('FT_UID', 16);
	define('FT_PREFETCHBODY', 2);
	define('FT_INTERNAL', 256);
	define('FT_INTERNALDATE', 512);
	define('FT_PEEK', 1024);
	define('FT_NOT', 2048);
}
if (!defined('SE_FREE')) {
	define('SE_FREE', 1);
	define('SE_FROM', 2);
	define('SE_SUBJECT', 4);
	define('SE_CC', 8);
	define('SE_TO', 16);
	define('SE_BODY', 32);
	define('SE_TEXT', 64);
	define('SE_ONBEFORE', 128);
	define('SE_ON', 256);
	define('SE_ONAFTER', 512);
	define('SE_BEFORE', 1024);
	define('SE_AFTER', 2048);
	define('SE_HEADER', 4096);
	define('SE_SMALLER', 8192);
	define('SE_LARGER', 16384);
	define('SE_UID', 32768);
	define('SE_MODSINCE', 65536);
	define('SE_UNMODSINCE', 131072);
	define('SE_RETURNALL', 262144);
}
if (!defined('ENC7BIT')) {
	define('ENC7BIT', 0);
	define('ENC8BIT', 1);
	define('ENCBINARY', 2);
	define('ENCBASE64', 3);
	define('ENCQUOTEDPRINTABLE', 4);
	define('ENCMIXED', 5);
	define('ENCOTHER', 6);
}
if (!defined('TYPEMESSAGE')) {
	define('TYPEMESSAGE', 0);
	define('TYPEMULTIPART', 1);
	define('TYPEAPPLICATION', 2);
	define('TYPEAUDIO', 3);
	define('TYPEIMAGE', 4);
	define('TYPETEXT', 5);
	define('TYPEVIDEO', 6);
	define('TYPEMODEL', 7);
	define('TYPEOTHER', 8);
}
class imap {

	//connection settings
	private $host = null;
	private $port = null;
	private $type = 'imap';
	private $security = 'notls';
	private $validate_cert = true;
	private $mailbox = 'INBOX';

	//socket and connection state
	private $socket = null;
	private $opened = false;
	private $tag = 0;
	private $timeout = 30;
	private $errors = array();
	private $last_error = '';

	//selected mailbox state
	private $message_count = 0;
	private $recent_count = 0;
	private $uid_validity = 0;
	private $mailbox_flags = array();

	//caches
	private $structure_cache = array();

	/**
	 * Constructor. Pass the connection string, username, and password to open
	 * the connection immediately, or leave the arguments empty and call open() later.
	 *
	 * @param string|null $connection_string IMAP/POP3 connection string, e.g. {host:port/imap/ssl/novalidate-cert}INBOX
	 * @param string|null $username          Account username
	 * @param string|null $password          Account password
	 */
	public function __construct($connection_string = null, $username = null, $password = null) {
		if ($connection_string !== null && $username !== null && $password !== null) {
			$this->open($connection_string, $username, $password);
		}
	}

	/**
	 * Open a connection to the mail server and select the mailbox.
	 * Replaces imap_open().
	 *
	 * @param string $connection_string IMAP/POP3 connection string, e.g. {host:port/imap/ssl/novalidate-cert}INBOX
	 * @param string $username          Account username
	 * @param string $password          Account password
	 *
	 * @return bool True when the connection was opened successfully, otherwise false
	 */
	public function open($connection_string, $username, $password) {
		//reset the connection state
		if ($this->socket) {
			$this->disconnect();
		}
		$this->errors = array();
		$this->last_error = '';
		$this->tag = 0;
		$this->message_count = 0;
		$this->recent_count = 0;
		$this->uid_validity = 0;
		$this->mailbox_flags = array();
		$this->structure_cache = array();

		//parse the connection string
		if (!$this->parse_connection_string($connection_string)) {
			return false;
		}

		//connect to the server
		if (!$this->connect()) {
			return false;
		}

		//read the server greeting
		$greeting = $this->read_line();
		if ($greeting === false) {
			$this->raise_error('Failed to read the server greeting');
			$this->disconnect();
			return false;
		}
		if ($this->type === 'pop3') {
			if (!preg_match('/^\+OK/', $greeting)) {
				$this->raise_error('Unexpected POP3 server greeting: ' . $greeting);
				$this->disconnect();
				return false;
			}
		}
		else {
			if (!preg_match('/^\* (OK|PREAUTH)/', $greeting)) {
				$this->raise_error('Unexpected server greeting: ' . $greeting);
				$this->disconnect();
				return false;
			}
		}

		//authenticate and select the mailbox
		if ($this->type === 'pop3') {
			if (!$this->pop3_authenticate($username, $password)) {
				$this->disconnect();
				return false;
			}
			$this->opened = true;
			return true;
		}

		//upgrade to tls when requested
		if ($this->security === 'tls') {
			$response = $this->send('STARTTLS');
			if ($response['status'] !== 'OK') {
				$this->raise_error('STARTTLS failed: ' . $response['response']);
				$this->disconnect();
				return false;
			}
			$method = defined('STREAM_CRYPTO_METHOD_TLS_CLIENT') ? STREAM_CRYPTO_METHOD_TLS_CLIENT : STREAM_CRYPTO_METHOD_ANY_CLIENT;
			if (!@stream_socket_enable_crypto($this->socket, true, $method)) {
				$this->raise_error('TLS negotiation failed');
				$this->disconnect();
				return false;
			}
		}

		//log in
		$response = $this->send('LOGIN ' . $this->quote($username) . ' ' . $this->quote($password));
		if ($response['status'] !== 'OK') {
			$this->raise_error('LOGIN failed: ' . $response['response']);
			$this->disconnect();
			return false;
		}

		//select the mailbox
		$response = $this->send('SELECT ' . $this->quote($this->mailbox));
		if ($response['status'] !== 'OK') {
			$this->raise_error('SELECT ' . $this->mailbox . ' failed: ' . $response['response']);
			$this->disconnect();
			return false;
		}
		$this->parse_select_responses($response['untagged']);

		$this->opened = true;
		return true;
	}

	/**
	 * Parses a connection string in the format {host:port/type[...options]}mailbox.
	 *
	 * @param string $connection_string Connection string to parse
	 *
	 * @return bool True on success, otherwise false
	 */
	private function parse_connection_string($connection_string) {
		if (!is_string($connection_string) || !preg_match('/^\{([^}]+)\}(.*)$/s', $connection_string, $matches)) {
			$this->raise_error('Invalid connection string: ' . (is_string($connection_string) ? $connection_string : ''));
			return false;
		}

		//parse the host:port/type/options section
		$parts = explode('/', $matches[1]);
		$host_port = strtolower(array_shift($parts));

		//determine the protocol type
		$this->type = !empty($parts) ? strtolower(array_shift($parts)) : 'imap';
		if ($this->type === 'imaps') {
			$this->type = 'imap';
			$this->security = 'ssl';
		}
		if (!in_array($this->type, array('imap', 'pop3'), true)) {
			$this->raise_error('Unsupported connection type: ' . $this->type);
			return false;
		}

		//determine the host and port
		if (strpos($host_port, ':') !== false) {
			list($this->host, $port) = explode(':', $host_port, 2);
			if (!is_numeric($port) || (int)$port < 1 || (int)$port > 65535) {
				$this->raise_error('Invalid port in connection string: ' . $port);
				return false;
			}
			$this->port = (int)$port;
		}
		else {
			$this->host = $host_port;
			$this->port = $this->type === 'pop3' ? 110 : 143;
		}
		if ($this->host === '') {
			$this->raise_error('Missing host in connection string');
			return false;
		}

		//parse the remaining options (security, certificate validation, etc.)
		foreach ($parts as $option) {
			$option = strtolower($option);
			switch ($option) {
				case 'ssl':
					$this->security = 'ssl';
					break;
				case 'tls':
				case 'starttls':
					$this->security = 'tls';
					break;
				case 'notls':
					$this->security = 'notls';
					break;
				case 'novalidate-cert':
					$this->validate_cert = false;
					break;
				case 'validate-cert':
					$this->validate_cert = true;
					break;
				//ignore unsupported options (e.g. compress)
			}
		}

		//determine the mailbox
		$this->mailbox = $matches[2] !== '' ? $matches[2] : 'INBOX';
		return true;
	}

	/**
	 * Creates the socket connection to the mail server.
	 *
	 * @return bool True on success, otherwise false
	 */
	private function connect() {
		$remote = $this->host . ':' . $this->port;
		$errno = 0;
		$errstr = '';
		if ($this->security === 'ssl') {
			$context = stream_context_create(array(
				'ssl' => array(
					'verify_peer' => $this->validate_cert,
					'verify_peer_name' => $this->validate_cert,
					'allow_self_signed' => false,
					'SNI_enabled' => true,
				),
			));
			$this->socket = @stream_socket_client('ssl://' . $remote, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
		}
		else {
			$this->socket = @stream_socket_client('tcp://' . $remote, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT);
		}
		if (!$this->socket) {
			$this->raise_error('Unable to connect to ' . $remote . ': ' . $errstr);
			$this->socket = null;
			return false;
		}
		stream_set_timeout($this->socket, $this->timeout);
		return true;
	}

	/**
	 * Closes the socket and resets the connection state.
	 */
	private function disconnect() {
		if ($this->socket) {
			@fclose($this->socket);
			$this->socket = null;
		}
		$this->opened = false;
	}

	/**
	 * Escapes a value for use as a quoted IMAP string literal.
	 *
	 * @param string $value Value to quote
	 *
	 * @return string Quoted value
	 */
	private function quote($value) {
		return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), (string)$value) . '"';
	}

	/**
	 * Records an error message.
	 *
	 * @param string $message Error message
	 */
	private function raise_error($message) {
		$this->last_error = $message;
		$this->errors[] = $message;
	}

	/**
	 * Sends a tagged IMAP command and reads the response.
	 *
	 * @param string $command IMAP command (without the tag)
	 *
	 * @return array An array with 'status' (OK/BAD/NO), 'response', and 'untagged' (array of untagged response lines)
	 */
	private function send($command) {
		$this->tag++;
		$tag = 'A' . str_pad((string)$this->tag, 4, '0', STR_PAD_LEFT);
		if (@fwrite($this->socket, $tag . ' ' . $command . "\r\n") === false) {
			$this->raise_error('Unable to send command to the server');
			$this->disconnect();
			return array('status' => 'BAD', 'response' => '', 'untagged' => array());
		}

		//read responses until the tagged response for this command is received
		$untagged = array();
		while (true) {
			$line = $this->read_line();
			if ($line === false) {
				return array('status' => 'BAD', 'response' => '', 'untagged' => $untagged);
			}
			if ($line !== '') {
				if (preg_match('/^([A-Za-z][A-Za-z0-9\-]{0,9})\s+(\S+)\s*(.*)$/s', $line, $matches) && $matches[1] === $tag) {
					return array('status' => strtoupper($matches[2]), 'response' => $matches[3], 'untagged' => $untagged);
				}
				if (strpos($line, '+ ') === 0) {
					//continuation response, none of the commands used require a reply
					continue;
				}
				$untagged[] = $line;
			}
		}
	}

	/**
	 * Reads a single line from the server, transparently handling IMAP literals
	 * ({n} followed by n bytes of data).
	 *
	 * @return string|false The complete logical line, or false on error
	 */
	private function read_line() {
		$line = '';
		while (true) {
			$buffer = @stream_get_line($this->socket, 1048576, "\n");
			if ($buffer === false || $buffer === '') {
				$info = stream_get_meta_data($this->socket);
				if (!empty($info['timed_out'])) {
					$this->raise_error('Connection to the server timed out');
				}
				else {
					$this->raise_error('Connection to the server was closed');
				}
				$this->disconnect();
				return false;
			}
			$line .= $buffer;

			//handle a literal at the end of the line
			if (preg_match('/\{(\d+)\}\r?$/', $line, $matches)) {
				$length = (int)$matches[1];
				$line = substr($line, 0, strlen($line) - strlen($matches[0]));
				$literal = $this->read_bytes($length);
				if ($literal === false) {
					return false;
				}
				$line .= $literal;
				continue; //read the remainder of the line that follows the literal
			}
			break;
		}
		return rtrim($line, "\r\n");
	}

	/**
	 * Reads an exact number of bytes from the socket.
	 *
	 * @param int $length Number of bytes to read
	 *
	 * @return string|false The bytes read, or false on error
	 */
	private function read_bytes($length) {
		$buffer = '';
		while (strlen($buffer) < $length) {
			$chunk = @fread($this->socket, min(8192, $length - strlen($buffer)));
			if ($chunk === false || $chunk === '') {
				$info = stream_get_meta_data($this->socket);
				if (!empty($info['timed_out'])) {
					$this->raise_error('Connection to the server timed out while reading message data');
				}
				else {
					$this->raise_error('Connection to the server was closed while reading message data');
				}
				$this->disconnect();
				return false;
			}
			$buffer .= $chunk;
		}
		return $buffer;
	}

	/**
	 * Authenticates to a POP3 server and gets the number of messages.
	 *
	 * @param string $username Account username
	 * @param string $password Account password
	 *
	 * @return bool True on success, otherwise false
	 */
	private function pop3_authenticate($username, $password) {
		$response = $this->pop3_command('USER ' . $username);
		if ($response === false || strpos($response, '+') !== 0) {
			$this->raise_error('POP3 USER failed: ' . (string)$response);
			return false;
		}
		$response = $this->pop3_command($password);
		if ($response === false || strpos($response, '+') !== 0) {
			$this->raise_error('POP3 authentication failed: ' . (string)$response);
			return false;
		}

		//get the number of messages
		$response = $this->pop3_command('STATUS');
		if ($response !== false && strpos($response, '+') === 0 && preg_match('/^\+OK\s+(\d+)\s+messages/i', $response, $matches)) {
			$this->message_count = (int)$matches[1];
		}
		elseif ($response !== false && strpos($response, '+') === 0) {
			//count the LIST entries instead
			$response = $this->pop3_command('LIST');
			if ($response === false || strpos($response, '+') !== 0) {
				$this->raise_error('POP3 STATUS failed: ' . (string)$response);
				return false;
			}
			$count = 0;
			foreach (explode("\n", $response) as $line) {
				if (preg_match('/^\s*\d+\s+\d+\s*$/', $line)) {
					$count++;
				}
			}
			$this->message_count = $count;
		}
		else {
			$this->raise_error('POP3 STATUS failed: ' . (string)$response);
			return false;
		}
		return true;
	}

	/**
	 * Sends a POP3 command and reads the response.
	 *
	 * @param string      $command   POP3 command
	 * @param bool        $read_data True to read a message data block (used by RETR)
	 *
	 * @return string|false The response line (or the message data), or false on error
	 */
	private function pop3_command($command, $read_data = false) {
		if (@fwrite($this->socket, $command . "\r\n") === false) {
			$this->raise_error('Unable to send command to the server');
			$this->disconnect();
			return false;
		}

		//read the response line
		$response = $this->read_line();
		if ($response === false) {
			return false;
		}

		//read a message data block
		if ($read_data && strpos($response, '+') === 0) {
			$data = '';
			while (true) {
				$line = $this->read_line();
				if ($line === false) {
					return false;
				}
				if ($line === '.') {
					break;
				}
				if (strpos($line, '..') === 0) {
					$line = substr($line, 1);
				}
				$data .= $line . "\r\n";
			}
			//read the final response line
			$final = $this->read_line();
			if ($final === false) {
				return false;
			}
			if (strpos($final, '-') === 0) {
				$this->raise_error('POP3 RETR failed: ' . $final);
				return false;
			}
			return $data;
		}
		return $response;
	}

	/**
	 * Returns true when the connection is open.
	 *
	 * @return bool
	 */
	public function is_opened() {
		return $this->opened;
	}

	/**
	 * Closes the connection, expunging deleted messages first.
	 * Replaces imap_close().
	 *
	 * @return bool True on success, otherwise false
	 */
	public function close() {
		if (!$this->opened) {
			return false;
		}
		if ($this->type === 'pop3') {
			$response = $this->pop3_command('QUIT');
			$success = ($response !== false && strpos($response, '+') === 0);
		}
		else {
			$response = $this->send('CLOSE');
			$success = ($response['status'] === 'OK');
			if (!$success) {
				$this->raise_error('CLOSE failed: ' . $response['response']);
			}
			$this->send('LOGOUT');
		}
		$this->disconnect();
		return $success;
	}

	/**
	 * Returns the list of errors that occurred.
	 * Replaces imap_errors().
	 *
	 * @return array Array of error messages
	 */
	public function errors() {
		return $this->errors;
	}

	/**
	 * Returns the last error message.
	 * Replaces imap_last_error().
	 *
	 * @return string Last error message
	 */
	public function last_error() {
		return $this->last_error;
	}

	/**
	 * Returns the number of messages in the selected mailbox.
	 * Replaces imap_num_msg().
	 *
	 * @return int|false The number of messages, or false when not connected
	 */
	public function num_msg() {
		if (!$this->opened) {
			return false;
		}
		return $this->message_count;
	}

	/**
	 * Parses the untagged responses returned by the SELECT command.
	 *
	 * @param array $untagged Array of untagged response lines
	 */
	private function parse_select_responses($untagged) {
		foreach ($untagged as $line) {
			if (preg_match('/^\* (\d+) EXISTS\b/', $line, $matches)) {
				$this->message_count = (int)$matches[1];
			}
			elseif (preg_match('/^\* (\d+) RECENT\b/', $line, $matches)) {
				$this->recent_count = (int)$matches[1];
			}
			elseif (preg_match('/^\* OK \[UIDVALIDITY (\d+)\]/', $line, $matches)) {
				$this->uid_validity = (int)$matches[1];
			}
			elseif (preg_match('/^\* FLAGS \((.*)\)\s*$/s', $line, $matches)) {
				$this->mailbox_flags = $this->parse_paren_list($matches[1]);
			}
		}
	}

	/**
	 * Searches the mailbox for messages matching the criteria.
	 * Replaces imap_search().
	 *
	 * @param string $criteria IMAP search criteria, e.g. SUBJECT "[FAX]"
	 * @param int    $options  Search options, e.g. SE_UID to return UIDs
	 *
	 * @return array|false Array of message numbers (or UIDs) when matches are found,
	 *                     otherwise false (matches the PHP imap extension behavior)
	 */
	public function search($criteria, $options = 0) {
		if (!$this->opened) {
			return false;
		}
		if ($this->type === 'pop3') {
			$this->raise_error('Search is not supported for POP3 connections');
			return false;
		}

		$criteria = trim((string)$criteria);
		if ($criteria === '') {
			$this->raise_error('No search criteria provided');
			return false;
		}

		//use UIDs when requested (the PHP imap extension uses SE_UID for this)
		$command = (($options & SE_UID) ? 'UID SEARCH' : 'SEARCH') . ' ' . $criteria;
		$response = $this->send($command);
		if ($response['status'] !== 'OK') {
			$this->raise_error('SEARCH failed: ' . $response['response']);
			return false;
		}

		//parse the "* SEARCH <number> ..." responses
		$result = array();
		foreach ($response['untagged'] as $line) {
			if (preg_match('/^\* SEARCH(?:\s+(.*))?$/s', $line, $matches)) {
				if (!empty($matches[1])) {
					$result = array_merge($result, preg_split('/\s+/', trim($matches[1])));
				}
			}
		}

		//return false when no messages match (matches the PHP imap extension behavior)
		if (empty($result)) {
			return false;
		}
		return array_map('intval', $result);
	}

	/**
	 * Fetches a minimal set of information from each message.
	 * Replaces imap_fetch_overview().
	 *
	 * @param int|null $message_number Message number or UID to fetch (null for all messages)
	 * @param int      $options        Fetch options, e.g. FT_UID when $message_number is a UID
	 *
	 * @return array|false Array of message overview objects (with from, to, cc, bcc, date,
	 *                     message, uid, status, seen, subject, in_reply_to, references),
	 *                     or false on error
	 */
	public function fetch_overview($message_number = null, $options = 0) {
		if (!$this->opened) {
			return false;
		}
		if ($this->type === 'pop3') {
			return $this->pop3_fetch_overview($message_number);
		}

		//determine the message range to fetch
		if ($message_number === null) {
			if ($this->message_count < 1) {
				return false;
			}
			$range = '1:' . $this->message_count;
		}
		else {
			$range = (string)$message_number;
		}

		$command = (($options & FT_UID) ? 'UID FETCH ' : 'FETCH ') . $range . ' (FLAGS INTERNALDATE UID BODY.PEEK[HEADER])';
		$response = $this->send($command);
		if ($response['status'] !== 'OK') {
			$this->raise_error('FETCH headers failed: ' . $response['response']);
			return false;
		}

		$overview = array();
		foreach ($response['untagged'] as $line) {
			if (!preg_match('/^\* (\d+) FETCH \((.*)\)\s*$/s', $line, $matches)) {
				continue;
			}
			$inner = $matches[2];
			//handle inline header content (server returned the body without a literal)
			$header = $this->extract_inline_body($inner);
			if ($header !== false) {
				$data = array('BODY[HEADER]' => $header);
				if (preg_match('/\sUID (\d+)\)/', $line, $uid_match)) {
					$data['UID'] = $uid_match[1];
				}
				if (preg_match('/\sINTERNALDATE "([^"]*)"/', $line, $date_match)) {
					$data['INTERNALDATE'] = $date_match[1];
				}
				if (preg_match('/\sFLAGS \(([^)]*)\)/', $line, $flags_match)) {
					$data['FLAGS'] = $flags_match[1] !== '' ? explode(' ', $flags_match[1]) : array();
				}
				$overview[] = $this->build_overview((int)$matches[1], $data);
				continue;
			}
			$data = $this->parse_fetch_data($inner);
			if (!isset($data['BODY[HEADER]'])) {
				continue;
			}
			$overview[] = $this->build_overview((int)$matches[1], $data);
		}
		if ($overview === array() && $message_number !== null) {
			$this->raise_error('FETCH headers returned no data');
			return false;
		}
		return $overview;
	}

	/**
	 * Builds a message overview object from a FETCH response.
	 *
	 * @param int   $sequence Message sequence number
	 * @param array $data     Parsed FETCH response data
	 *
	 * @return object Message overview object
	 */
	private function build_overview($sequence, $data) {
		$overview = new stdClass();
		$overview->from = null;
		$overview->to = null;
		$overview->cc = null;
		$overview->bcc = null;
		$overview->date = null;
		$overview->message = $sequence;
		$overview->uid = isset($data['UID']) ? (int)$data['UID'] : null;
		$overview->status = 0;
		$overview->seen = 0;
		$overview->subject = null;
		$overview->in_reply_to = null;
		$overview->references = null;

		//message flags
		if (isset($data['FLAGS']) && is_array($data['FLAGS'])) {
			foreach ($data['FLAGS'] as $flag) {
				if (strtolower((string)$flag) === '\\seen') {
					$overview->seen = 1;
				}
			}
		}

		//internal date
		if (isset($data['INTERNALDATE']) && $data['INTERNALDATE'] !== null) {
			$overview->date = trim((string)$data['INTERNALDATE']);
		}

		//headers
		$headers = $this->parse_headers($data['BODY[HEADER]']);
		$overview->from = $this->decode_header(isset($headers['from']) ? $headers['from'] : null);
		$overview->to = $this->decode_header(isset($headers['to']) ? $headers['to'] : null);
		$overview->cc = $this->decode_header(isset($headers['cc']) ? $headers['cc'] : null);
		$overview->bcc = $this->decode_header(isset($headers['bcc']) ? $headers['bcc'] : null);
		$overview->subject = $this->decode_header(isset($headers['subject']) ? $headers['subject'] : null);
		$overview->in_reply_to = isset($headers['in-reply-to']) ? $headers['in-reply-to'] : null;
		$overview->references = isset($headers['references']) ? $headers['references'] : null;
		return $overview;
	}

	/**
	 * Parses a raw header block into an associative array (lowercased header names
	 * mapped to their unfolded values, the last occurrence wins).
	 *
	 * @param string $block Raw header block
	 *
	 * @return array Associative array of headers
	 */
	private function parse_headers($block) {
		$block = str_replace("\r\n", "\n", (string)$block);
		$block = preg_replace('/\n[ \t]/', ' ', $block);
		$headers = array();
		foreach (explode("\n", $block) as $line) {
			if ($line === '') {
				continue;
			}
			$position = strpos($line, ':');
			if ($position === false) {
				continue;
			}
			$name = strtolower(trim(substr($line, 0, $position)));
			if ($name !== '') {
				$headers[$name] = trim(substr($line, $position + 1));
			}
		}
		return $headers;
	}

	/**
	 * Decodes an RFC 2047 encoded header value (e.g. =?UTF-8?B?...?=).
	 *
	 * @param string|null $value Header value
	 *
	 * @return string|null Decoded header value
	 */
	private function decode_header($value) {
		if ($value === null || $value === '') {
			return $value;
		}
		if (function_exists('mb_decode_mimeheader')) {
			$decoded = mb_decode_mimeheader($value);
			if ($decoded !== false && $decoded !== null) {
				return $decoded;
			}
		}
		return $value;
	}

	/**
	 * Extracts inline (non-literal) body content from a FETCH response line.
	 * Some servers (e.g. SmarterMail) return the body content inline instead of
	 * as a {n} literal, e.g.: * 1 FETCH (BODY[] <content> UID 1)
	 *
	 * @param string $inner The content within the outer FETCH parentheses
	 *
	 * @return string|false The body content, or false when not found
	 */
	private function extract_inline_body($inner) {
		$inner = (string)$inner;
		//find the BODY[...] key at the start of the data section
		if (!preg_match('/^\s*BODY\[([^\]]*)\](\s|$)/', $inner, $matches)) {
			return false;
		}
		$content = substr($inner, strlen($matches[0]));
		//strip a trailing "UID <n>" data item when present
		$content = preg_replace('/\s+UID \d+$/', '', $content);
		return $content;
	}

	/**
	 * Parses a FETCH response data section (the content within the outer parentheses).
	 * Values can be atoms, quoted strings, nested parenthesis groups, or literals.
	 *
	 * @param string $data FETCH data section
	 *
	 * @return array Associative array of the fetched values
	 */
	private function parse_fetch_data($data) {
		$data = trim((string)$data);
		$result = array();
		$length = strlen($data);
		$position = 0;
		while ($position < $length) {
			while ($position < $length && $data[$position] === ' ') {
				$position++;
			}
			if ($position >= $length) {
				break;
			}

			//read the attribute name (stops at space, parenthesis, or literal)
			if ($data[$position] === '"') {
				$key = $this->read_quoted($data, $position);
			}
			else {
				$start = $position;
				while ($position < $length && $data[$position] !== ' ' && $data[$position] !== '(' && $data[$position] !== '{') {
					$position++;
				}
				$key = substr($data, $start, $position - $start);
				if ($key === '') {
					break;
				}
			}

			while ($position < $length && $data[$position] === ' ') {
				$position++;
			}
			if ($position >= $length) {
				$result[$key] = null;
				break;
			}

			//read the attribute value
			$char = $data[$position];
			if ($char === '(') {
				$end = $this->match_paren($data, $position);
				$result[$key] = $this->parse_paren_list(substr($data, $position + 1, $end - $position - 1));
				$position = $end + 1;
			}
			elseif ($char === '"') {
				$result[$key] = $this->read_quoted($data, $position);
			}
			elseif ($char === '{') {
				//parse the literal size declaration {n}
				$end = $position + 1;
				while ($end < $length && $data[$end] !== '}') {
					$end++;
				}
				if ($end >= $length) {
					break;
				}
				$size = (int)substr($data, $position + 1, $end - $position - 1);
				$result[$key] = substr($data, $end + 1, $size);
				$position = $end + 1 + $size;
			}
			elseif (strpos($key, 'BODY[') === 0) {
				//inline body content: the server returned the body without a literal
				//the body is everything remaining in the data section
				$body = substr($data, $position);
				//strip a trailing "UID <n>" data item when present
				$body = preg_replace('/\s+UID \d+$/', '', $body);
				$result[$key] = $body;
				break;
			}
			else {
				$start = $position;
				while ($position < $length && $data[$position] !== ' ' && $data[$position] !== '{') {
					$position++;
				}
				$result[$key] = substr($data, $start, $position - $start);
			}
		}
		return $result;
	}

	/**
	 * Reads a quoted string from data, advancing the position past the closing quote.
	 *
	 * @param string $data     Data to read from
	 * @param int    &$position Position, must point at the opening quote
	 *
	 * @return string The unquoted value
	 */
	private function read_quoted($data, &$position) {
		$position++;
		$result = '';
		$length = strlen($data);
		while ($position < $length) {
			$char = $data[$position];
			if ($char === '\\' && $position + 1 < $length) {
				$result .= $data[$position + 1];
				$position += 2;
			}
			elseif ($char === '"') {
				$position++;
				return $result;
			}
			else {
				$result .= $char;
				$position++;
			}
		}
		return $result;
	}

	/**
	 * Finds the position of the parenthesis that matches the opening one at $position.
	 *
	 * @param string $data     Data to search
	 * @param int    $position Position of the opening parenthesis
	 *
	 * @return int Position of the matching closing parenthesis
	 */
	private function match_paren($data, $position) {
		$depth = 0;
		$length = strlen($data);
		while ($position < $length) {
			$char = $data[$position];
			if ($char === '(') {
				$depth++;
			}
			elseif ($char === ')') {
				$depth--;
				if ($depth === 0) {
					return $position;
				}
			}
			$position++;
		}
		return $position;
	}

	/**
	 * Parses the contents of a parenthesized list (e.g. message flags).
	 *
	 * @param string $data Contents of the parenthesis group
	 *
	 * @return array Array of values
	 */
	private function parse_paren_list($data) {
		$result = array();
		$data = trim((string)$data);
		if ($data === '') {
			return $result;
		}
		$length = strlen($data);
		$position = 0;
		while ($position < $length) {
			while ($position < $length && $data[$position] === ' ') {
				$position++;
			}
			if ($position >= $length) {
				break;
			}
			if ($data[$position] === '"') {
				$result[] = $this->read_quoted($data, $position);
			}
			else {
				$start = $position;
				while ($position < $length && $data[$position] !== ' ') {
					$position++;
				}
				$result[] = substr($data, $start, $position - $start);
			}
		}
		return $result;
	}

	/**
	 * Fetches the full MIME structure of a message.
	 * Replaces imap_fetchstructure().
	 *
	 * @param int  $message_number Message number or UID
	 * @param int  $options        Fetch options, e.g. FT_UID when $message_number is a UID
	 *
	 * @return object|false A structure object (matching the PHP imap extension format) or false on error
	 */
	public function fetchstructure($message_number, $options = 0) {
		if (!$this->opened) {
			return false;
		}
		if ($this->type === 'pop3') {
			$raw = $this->pop3_retrieve((int)$message_number);
			if ($raw === false) {
				return false;
			}
			return $this->parse_mime_structure($raw);
		}

		$command = (($options & FT_UID) ? 'UID FETCH ' : 'FETCH ') . $message_number . ' (BODY.PEEK[])';
		$response = $this->send($command);
		if ($response['status'] !== 'OK') {
			$this->raise_error('FETCH message failed: ' . $response['response']);
			return false;
		}
		foreach ($response['untagged'] as $line) {
			if (!preg_match('/^\* \d+ FETCH \((.*)\)\s*$/s', $line, $matches)) {
				continue;
			}
			$inner = $matches[1];
			//handle inline content (server returned the body without a literal)
			$content = $this->extract_inline_body($inner);
			if ($content !== false) {
				return $this->parse_mime_structure($content);
			}
			//handle literal content
			$data = $this->parse_fetch_data($inner);
			if (isset($data['BODY[]'])) {
				return $this->parse_mime_structure($data['BODY[]']);
			}
		}
		$this->raise_error('FETCH message returned no data');
		return false;
	}

	/**
	 * Returns the message structure, using the cache when available.
	 *
	 * @param int $message_number Message number or UID
	 * @param int $options        Fetch options, e.g. FT_UID
	 *
	 * @return object|false Structure object or false on error
	 */
	private function get_structure($message_number, $options = 0) {
		$key = (($options & FT_UID) ? 'u' : 's') . (string)$message_number;
		if (!isset($this->structure_cache[$key])) {
			$this->structure_cache[$key] = $this->fetchstructure($message_number, $options);
		}
		return $this->structure_cache[$key];
	}

	/**
	 * Parses a raw RFC 822 message into a MIME structure object that matches the
	 * format returned by the PHP imap extension (imap_fetchstructure).
	 *
	 * @param string $raw Raw message content
	 *
	 * @return object MIME structure object
	 */
	private function parse_mime_structure($raw) {
		$raw = str_replace("\r\n", "\n", (string)$raw);
		$position = strpos($raw, "\n\n");
		if ($position === false) {
			$header_block = $raw;
			$body = '';
		}
		else {
			$header_block = substr($raw, 0, $position);
			$body = substr($raw, $position + 2);
		}

		$headers = $this->parse_headers($header_block);
		list($major, $minor, $parameters) = $this->parse_media_type(isset($headers['content-type']) ? $headers['content-type'] : null);
		$encoding = $this->parse_encoding(isset($headers['content-transfer-encoding']) ? $headers['content-transfer-encoding'] : '7bit');
		list($disposition, $dparameters) = $this->parse_content_disposition(isset($headers['content-disposition']) ? $headers['content-disposition'] : null);

		$structure = new stdClass();
		$structure->type = $this->map_type($major);
		$structure->subtype = $minor;
		$structure->encoding = $encoding;
		$structure->description = null;
		$structure->id = null;
		$structure->md5 = null;
		$structure->parameters = $parameters;
		$structure->ifparameters = (count($parameters) > 0) ? 1 : 0;
		$structure->disposition = $disposition;
		$structure->dparameters = $dparameters;
		$structure->ifdparameters = (count($dparameters) > 0) ? 1 : 0;
		$structure->ifbody = 1;

		if ($major === 'multipart') {
			$structure->type = TYPEMULTIPART;
			$boundary = null;
			foreach ($parameters as $parameter) {
				if (strtolower($parameter->attribute) === 'boundary') {
					$boundary = $parameter->value;
				}
			}
			$subparts = ($boundary !== null) ? $this->split_multipart($body, $boundary) : array();
			$parts = array();
			foreach ($subparts as $subpart) {
				$parts[] = $this->parse_mime_structure($subpart);
			}
			$structure->body = count($parts);
			$structure->parts = $parts;
		}
		else {
			$structure->body = strlen($body);
		}
		return $structure;
	}

	/**
	 * Maps a MIME major type to the PHP imap extension type constant.
	 *
	 * @param string $major MIME major type
	 *
	 * @return int Type constant
	 */
	private function map_type($major) {
		$map = array(
			'message' => TYPEMESSAGE,
			'multipart' => TYPEMULTIPART,
			'application' => TYPEAPPLICATION,
			'audio' => TYPEAUDIO,
			'image' => TYPEIMAGE,
			'text' => TYPETEXT,
			'video' => TYPEVIDEO,
			'model' => TYPEMODEL,
		);
		return isset($map[$major]) ? $map[$major] : TYPEOTHER;
	}

	/**
	 * Parses a media type value (e.g. "multipart/mixed; boundary=abc").
	 *
	 * @param string|null $value Media type header value
	 *
	 * @return array Array with [major type, minor type, parameter objects]
	 */
	private function parse_media_type($value) {
		$major = 'text';
		$minor = 'plain';
		$parameters = array();
		if ($value !== null && $value !== '') {
			$value = trim($value);
			list($media, $rest) = $this->split_params($value);
			if (strpos($media, '/') !== false) {
				$bits = explode('/', $media, 2);
				$major = strtolower(trim($bits[0]));
				$minor = strtolower(trim($bits[1]));
			}
			else {
				$major = strtolower(trim($media));
				$minor = '';
			}
			$parameters = $this->parse_parameters($rest);
		}
		return array($major, $minor, $parameters);
	}

	/**
	 * Parses a content-disposition value (e.g. 'attachment; filename="a.pdf"').
	 * The first element is the disposition type itself with a null value,
	 * matching the PHP imap extension format.
	 *
	 * @param string|null $value Content-disposition header value
	 *
	 * @return array Array with [disposition objects, disposition parameter objects]
	 */
	private function parse_content_disposition($value) {
		if ($value === null || trim($value) === '') {
			return array(null, array());
		}
		list($disposition_type, $rest) = $this->split_params(trim($value));
		$disposition = strtoupper(trim($disposition_type));
		$dparameters = $this->parse_parameters($rest);
		return array($disposition, $dparameters);
	}

	/**
	 * Splits a value at the first unquoted semicolon.
	 *
	 * @param string $value Value to split
	 *
	 * @return array Array with [value before the semicolon, value after the semicolon]
	 */
	private function split_params($value) {
		$in_quotes = false;
		$length = strlen($value);
		for ($i = 0; $i < $length; $i++) {
			$char = $value[$i];
			if ($char === '"') {
				if ($i > 0 && $value[$i - 1] === '\\') {
					continue;
				}
				$in_quotes = !$in_quotes;
			}
			elseif ($char === ';' && !$in_quotes) {
				return array(trim(substr($value, 0, $i)), substr($value, $i + 1));
			}
		}
		return array(trim($value), '');
	}

	/**
	 * Parses parameter pairs (name=value; ...) into parameter objects with
	 * uppercased attribute names, matching the PHP imap extension format.
	 *
	 * @param string $rest Parameter string (without the leading media type)
	 *
	 * @return array Array of parameter objects (attribute, value)
	 */
	private function parse_parameters($rest) {
		$parameters = array();
		$rest = trim((string)$rest);
		while ($rest !== '') {
			list($segment, $rest) = $this->split_params($rest);
			$segment = trim($segment);
			if ($segment === '') {
				continue;
			}
			$position = strpos($segment, '=');
			if ($position === false) {
				$parameters[] = (object)array('attribute' => strtoupper($segment), 'value' => null);
			}
			else {
				$attribute = strtoupper(trim(substr($segment, 0, $position)));
				$value = trim(substr($segment, $position + 1));
				if ($value !== '' && $value[0] === '"' && substr($value, -1) === '"' && strlen($value) >= 2) {
					$value = substr($value, 1, -1);
					$value = str_replace(array('\\"', '\\\\'), array('"', '\\'), $value);
				}
				$parameters[] = (object)array('attribute' => $attribute, 'value' => $value);
			}
		}
		return $parameters;
	}

	/**
	 * Maps a content-transfer-encoding value to the PHP imap extension encoding constant.
	 *
	 * @param string $value Content-transfer-encoding value
	 *
	 * @return int Encoding constant
	 */
	private function parse_encoding($value) {
		$map = array(
			'7bit' => ENC7BIT,
			'8bit' => ENC8BIT,
			'binary' => ENCBINARY,
			'base64' => ENCBASE64,
			'quoted-printable' => ENCQUOTEDPRINTABLE,
			'mixed' => ENCMIXED,
		);
		$value = strtolower(trim((string)$value));
		return isset($map[$value]) ? $map[$value] : ENCOTHER;
	}

	/**
	 * Splits a multipart message body into its individual parts using the boundary.
	 *
	 * @param string $body     Multipart message body
	 * @param string $boundary MIME boundary value
	 *
	 * @return array Array of raw parts (each a complete sub-part with headers)
	 */
	private function split_multipart($body, $boundary) {
		$parts = array();
		$current = null;
		$start = '--' . $boundary;
		foreach (explode("\n", $body) as $line) {
			$trimmed = rtrim($line, " \t");
			if ($trimmed === $start) {
				if ($current !== null) {
					$parts[] = implode("\n", $current);
				}
				$current = array();
			}
			elseif ($trimmed === $start . '--') {
				if ($current !== null) {
					$parts[] = implode("\n", $current);
				}
				$current = null; //ignore the epilogue after the closing boundary
			}
			elseif ($current !== null) {
				$current[] = $line;
			}
		}
		if ($current !== null) {
			$parts[] = implode("\n", $current);
		}
		return $parts;
	}

	/**
	 * Finds a part within a structure using a dot separated part number (e.g. "1.2").
	 *
	 * @param object $structure  Structure object
	 * @param string $part_number Dot separated part number
	 *
	 * @return object|null The part or null when not found
	 */
	private function find_part($structure, $part_number) {
		if (!is_object($structure)) {
			return null;
		}
		$part = $structure;
		foreach (explode('.', (string)$part_number) as $index) {
			$index = (int)$index;
			if (!isset($part->parts) || !is_array($part->parts) || $index < 1 || $index > count($part->parts)) {
				return null;
			}
			$part = $part->parts[$index - 1];
		}
		return $part;
	}

	/**
	 * Fetches part of a message (only the body, leaving the message unmodified on the server).
	 * Replaces imap_fetchbody(). The returned content is still transfer-encoded,
	 * matching the PHP imap extension (the caller decodes it using the structure encoding).
	 *
	 * @param int         $message_number Message number or UID
	 * @param string      $part_number    Part number (e.g. "3" or "1.2"), empty string for the whole message
	 * @param int         $options        Fetch options, e.g. FT_UID when $message_number is a UID
	 *
	 * @return string|false The (still encoded) part content or false on error
	 */
	public function fetchbody($message_number, $part_number = '', $options = 0) {
		if (!$this->opened) {
			return false;
		}
		if ($this->type === 'pop3') {
			return $this->pop3_fetchbody((int)$message_number, $part_number);
		}

		$part_number = (string)$part_number;

		//nested part numbers (e.g. 1.1.1) are not supported by all servers,
		//so fetch the full message and extract the part locally
		if ($part_number !== '' && strpos($part_number, '.') !== false) {
			$raw = $this->fetch_full_message($message_number, $options);
			if ($raw === false) {
				return false;
			}
			return $this->extract_part_body($raw, $part_number);
		}

		//determine the fetch item
		if ($part_number === '') {
			$item = 'BODY.PEEK[]';
		}
		else {
			$item = 'BODY.PEEK[' . $part_number . ']';
			//text parts are fetched including their MIME headers (matches the PHP imap extension)
			$structure = $this->get_structure($message_number, $options);
			$part = $this->find_part($structure, $part_number);
			if ($part !== null && isset($part->type) && $part->type === TYPETEXT) {
				$item = 'BODY.PEEK[text/' . $part->subtype . ']';
			}
		}

		$command = (($options & FT_UID) ? 'UID FETCH ' : 'FETCH ') . $message_number . ' (' . $item . ')';
		$response = $this->send($command);
		if ($response['status'] !== 'OK') {
			$this->raise_error('FETCH part failed: ' . $response['response']);
			return false;
		}
		foreach ($response['untagged'] as $line) {
			if (!preg_match('/^\* \d+ FETCH \((.*)\)\s*$/s', $line, $matches)) {
				continue;
			}
			$inner = $matches[1];
			//handle inline content (server returned the body without a literal)
			$content = $this->extract_inline_body($inner);
			if ($content !== false) {
				return $content;
			}
			//handle literal content
			$data = $this->parse_fetch_data($inner);
			foreach ($data as $key => $value) {
				if (strpos($key, 'BODY[') === 0 && $value !== null) {
					return $value;
				}
			}
		}
		$this->raise_error('FETCH part returned no data');
		return false;
	}

	/**
	 * Fetches the full raw message (headers and body).
	 *
	 * @param int $message_number Message number or UID
	 * @param int $options        Fetch options, e.g. FT_UID
	 *
	 * @return string|false Raw message content or false on error
	 */
	private function fetch_full_message($message_number, $options = 0) {
		$command = (($options & FT_UID) ? 'UID FETCH ' : 'FETCH ') . $message_number . ' (BODY.PEEK[])';
		$response = $this->send($command);
		if ($response['status'] !== 'OK') {
			$this->raise_error('FETCH message failed: ' . $response['response']);
			return false;
		}
		foreach ($response['untagged'] as $line) {
			if (!preg_match('/^\* \d+ FETCH \((.*)\)\s*$/s', $line, $matches)) {
				continue;
			}
			$content = $this->extract_inline_body($matches[1]);
			if ($content !== false) {
				return $content;
			}
			$data = $this->parse_fetch_data($matches[1]);
			if (isset($data['BODY[]'])) {
				return $data['BODY[]'];
			}
		}
		$this->raise_error('FETCH message returned no data');
		return false;
	}

	/**
	 * Marks a message for deletion (sets the \Deleted flag).
	 * Replaces imap_delete().
	 *
	 * @param int $message_number Message number or UID
	 * @param int $options        Fetch options, e.g. FT_UID when $message_number is a UID
	 *
	 * @return bool True on success, otherwise false
	 */
	public function delete($message_number, $options = 0) {
		if (!$this->opened) {
			return false;
		}
		if ($this->type === 'pop3') {
			$response = $this->pop3_command('DELE ' . (int)$message_number);
			if ($response === false || strpos($response, '+') !== 0) {
				$this->raise_error('POP3 DELE failed: ' . (string)$response);
				return false;
			}
			return true;
		}
		$command = (($options & FT_UID) ? 'UID STORE ' : 'STORE ') . $message_number . ' +FLAGS (\\Deleted)';
		$response = $this->send($command);
		if ($response['status'] !== 'OK') {
			$this->raise_error('STORE \\Deleted failed: ' . $response['response']);
			return false;
		}
		return true;
	}

	/**
	 * Permanently removes the messages marked for deletion.
	 * Replaces imap_expunge().
	 *
	 * @return bool True on success, otherwise false
	 */
	public function expunge() {
		if (!$this->opened) {
			return false;
		}
		if ($this->type === 'pop3') {
			//deleted messages are permanently removed when the session ends
			$response = $this->pop3_command('QUIT');
			$success = ($response !== false && strpos($response, '+') === 0);
			$this->disconnect();
			return $success;
		}
		$response = $this->send('EXPUNGE');
		if ($response['status'] !== 'OK') {
			$this->raise_error('EXPUNGE failed: ' . $response['response']);
			return false;
		}

		//update the message count based on the expunged messages
		$expunged = 0;
		foreach ($response['untagged'] as $line) {
			if (preg_match('/^\* (\d+) EXPUNGE\b/', $line, $matches)) {
				$expunged++;
			}
		}
		$this->message_count = max(0, $this->message_count - $expunged);
		$this->structure_cache = array();
		return true;
	}

	/**
	 * Retrieves a full message from a POP3 server.
	 *
	 * @param int $message_number POP3 message number
	 *
	 * @return string|false Raw message content or false on error
	 */
	private function pop3_retrieve($message_number) {
		if ($message_number < 1 || $message_number > max(1, $this->message_count)) {
			$this->raise_error('Invalid POP3 message number: ' . $message_number);
			return false;
		}
		$data = $this->pop3_command('RETR ' . $message_number, true);
		if ($data === false) {
			$this->raise_error('POP3 RETR failed');
			return false;
		}
		return $data;
	}

	/**
	 * Builds message overviews for a POP3 connection using RETR.
	 *
	 * @param int|null $message_number POP3 message number (null for all messages)
	 *
	 * @return array|false Array of message overview objects or false on error
	 */
	private function pop3_fetch_overview($message_number) {
		if ($message_number === null) {
			if ($this->message_count < 1) {
				return false;
			}
			$range = range(1, $this->message_count);
		}
		else {
			$range = array((int)$message_number);
		}

		$overview = array();
		foreach ($range as $number) {
			$raw = $this->pop3_retrieve($number);
			if ($raw === false) {
				return false;
			}
			$raw = str_replace("\r\n", "\n", $raw);
			$position = strpos($raw, "\n\n");
			$header_block = ($position === false) ? $raw : substr($raw, 0, $position);
			$headers = $this->parse_headers($header_block);

			$object = new stdClass();
			$object->from = $this->decode_header(isset($headers['from']) ? $headers['from'] : null);
			$object->to = $this->decode_header(isset($headers['to']) ? $headers['to'] : null);
			$object->cc = $this->decode_header(isset($headers['cc']) ? $headers['cc'] : null);
			$object->bcc = null;
			$object->date = isset($headers['date']) ? $headers['date'] : null;
			$object->message = $number;
			$object->uid = null;
			$object->status = 0;
			$object->seen = 0;
			$object->subject = $this->decode_header(isset($headers['subject']) ? $headers['subject'] : null);
			$object->in_reply_to = isset($headers['in-reply-to']) ? $headers['in-reply-to'] : null;
			$object->references = isset($headers['references']) ? $headers['references'] : null;
			$overview[] = $object;
		}
		return $overview;
	}

	/**
	 * Fetches part of a message from a POP3 connection.
	 *
	 * @param int    $message_number POP3 message number
	 * @param string $part_number    Part number (e.g. "3" or "1.2"), empty string for the whole message
	 *
	 * @return string|false The (still encoded) part content or false on error
	 */
	private function pop3_fetchbody($message_number, $part_number) {
		$raw = $this->pop3_retrieve($message_number);
		if ($raw === false) {
			return false;
		}
		if ($part_number === '' || $part_number === null) {
			return $raw;
		}
		return $this->extract_part_body($raw, (string)$part_number);
	}

	/**
	 * Extracts the raw (still encoded) content of a part from a raw message.
	 *
	 * @param string $raw         Raw message content
	 * @param string $part_number Dot separated part number
	 *
	 * @return string|false The part content or false when the part is not found
	 */
	private function extract_part_body($raw, $part_number) {
		$raw = str_replace("\r\n", "\n", (string)$raw);
		$position = strpos($raw, "\n\n");
		if ($position === false) {
			$header_block = $raw;
			$body = '';
		}
		else {
			$header_block = substr($raw, 0, $position);
			$body = substr($raw, $position + 2);
		}

		$parts_path = explode('.', $part_number);
		$segment = (int)array_shift($parts_path);

		$headers = $this->parse_headers($header_block);
		list($major, $minor, $parameters) = $this->parse_media_type(isset($headers['content-type']) ? $headers['content-type'] : null);

		if ($major === 'multipart') {
			$boundary = null;
			foreach ($parameters as $parameter) {
				if (strtolower($parameter->attribute) === 'boundary') {
					$boundary = $parameter->value;
				}
			}
			if ($boundary === null) {
				return false;
			}
			$subparts = $this->split_multipart($body, $boundary);
			if ($segment < 1 || $segment > count($subparts)) {
				return false;
			}
			if (empty($parts_path)) {
				//return the sub-part (headers and body) so text parts include their MIME headers
				return $subparts[$segment - 1];
			}
			return $this->extract_part_body($subparts[$segment - 1], implode('.', $parts_path));
		}
		if ($major === 'message' && $minor === 'rfc822') {
			if ($segment !== 1) {
				return false;
			}
			if (empty($parts_path)) {
				return $body;
			}
			return $this->extract_part_body($body, implode('.', $parts_path));
		}
		if (!empty($parts_path)) {
			//leaf part, no sub-parts available
			return false;
		}
		return $body;
	}

	/**
	 * Returns a string representation of the connection.
	 *
	 * @return string Connection description
	 */
	public function __toString() {
		return 'imap connection to ' . ($this->host ?? 'unknown') . ':' . ($this->port ?? 'unknown') . ' mailbox ' . ($this->mailbox ?? 'unknown');
	}

}

//define the global imap_* functions when the PHP imap extension is not loaded
//these functions provide the same interface as the PHP imap extension
if (!function_exists('imap_open')) {
	/**
	 * Opens a connection to an IMAP or POP3 server.
	 * Replaces the PHP imap extension's imap_open().
	 *
	 * @param string $connection_string Connection string, e.g. {host:port/imap/ssl}INBOX
	 * @param string $username          Account username
	 * @param string $password          Account password
	 *
	 * @return imap|false An imap connection object, or false on failure
	 */
	function imap_open($connection_string, $username, $password) {
		$connection = new imap();
		if (!$connection->open($connection_string, $username, $password)) {
			return false;
		}
		return $connection;
	}
}

if (!function_exists('imap_close')) {
	/**
	 * Closes an IMAP or POP3 connection.
	 * Replaces the PHP imap extension's imap_close().
	 *
	 * @param imap $connection IMAP connection
	 *
	 * @return bool True on success
	 */
	function imap_close($connection) {
		if ($connection instanceof imap) {
			return $connection->close();
		}
		return false;
	}
}

if (!function_exists('imap_errors')) {
	/**
	 * Returns any errors from the last IMAP operation.
	 * Replaces the PHP imap extension's imap_errors().
	 *
	 * @param imap|null $connection Optional IMAP connection
	 *
	 * @return array Array of error messages
	 */
	function imap_errors($connection = null) {
		if ($connection instanceof imap) {
			return $connection->errors();
		}
		return array();
	}
}

if (!function_exists('imap_last_error')) {
	/**
	 * Returns the last error message from the IMAP connection.
	 * Replaces the PHP imap extension's imap_last_error().
	 *
	 * @param imap|null $connection Optional IMAP connection
	 *
	 * @return string Last error message
	 */
	function imap_last_error($connection = null) {
		if ($connection instanceof imap) {
			return $connection->last_error();
		}
		return '';
	}
}

if (!function_exists('imap_num_msg')) {
	/**
	 * Returns the number of messages in the selected mailbox.
	 * Replaces the PHP imap extension's imap_num_msg().
	 *
	 * @param imap $connection IMAP connection
	 *
	 * @return int Number of messages
	 */
	function imap_num_msg($connection) {
		if ($connection instanceof imap) {
			return $connection->num_msg();
		}
		return 0;
	}
}

if (!function_exists('imap_search')) {
	/**
	 * Searches for messages matching the given criteria.
	 * Replaces the PHP imap extension's imap_search().
	 *
	 * @param imap  $connection IMAP connection
	 * @param string $criteria  Search criteria, e.g. SUBJECT "test"
	 * @param int   $options    Search options, e.g. SE_UID
	 *
	 * @return array|false Array of message numbers/UIDs, or false on failure
	 */
	function imap_search($connection, $criteria, $options = 0) {
		if ($connection instanceof imap) {
			return $connection->search($criteria, $options);
		}
		return false;
	}
}

if (!function_exists('imap_fetch_overview')) {
	/**
	 * Fetches message overview information.
	 * Replaces the PHP imap extension's imap_fetch_overview().
	 *
	 * @param imap      $connection     IMAP connection
	 * @param int|null  $message_number Message number or UID (null for all)
	 * @param int       $options        Fetch options, e.g. FT_UID
	 *
	 * @return array|false Array of message overview objects, or false on failure
	 */
	function imap_fetch_overview($connection, $message_number = null, $options = 0) {
		if ($connection instanceof imap) {
			return $connection->fetch_overview($message_number, $options);
		}
		return false;
	}
}

if (!function_exists('imap_fetchstructure')) {
	/**
	 * Fetches the MIME structure of a message.
	 * Replaces the PHP imap extension's imap_fetchstructure().
	 *
	 * @param imap $connection     IMAP connection
	 * @param int  $message_number Message number or UID
	 * @param int  $options        Fetch options, e.g. FT_UID
	 *
	 * @return object|false MIME structure object, or false on failure
	 */
	function imap_fetchstructure($connection, $message_number, $options = 0) {
		if ($connection instanceof imap) {
			return $connection->fetchstructure($message_number, $options);
		}
		return false;
	}
}

if (!function_exists('imap_fetchbody')) {
	/**
	 * Fetches a part of a message.
	 * Replaces the PHP imap extension's imap_fetchbody().
	 *
	 * @param imap      $connection     IMAP connection
	 * @param int       $message_number Message number or UID
	 * @param string    $part_number    Part number (e.g. "1", "1.2"), empty for whole message
	 * @param int       $options        Fetch options, e.g. FT_UID
	 *
	 * @return string|false The part content, or false on failure
	 */
	function imap_fetchbody($connection, $message_number, $part_number = '', $options = 0) {
		if ($connection instanceof imap) {
			return $connection->fetchbody($message_number, $part_number, $options);
		}
		return false;
	}
}

if (!function_exists('imap_delete')) {
	/**
	 * Marks a message for deletion.
	 * Replaces the PHP imap extension's imap_delete().
	 *
	 * @param imap $connection     IMAP connection
	 * @param int  $message_number Message number or UID
	 * @param int  $options        Fetch options, e.g. FT_UID
	 *
	 * @return bool True on success, false on failure
	 */
	function imap_delete($connection, $message_number, $options = 0) {
		if ($connection instanceof imap) {
			return $connection->delete($message_number, $options);
		}
		return false;
	}
}

if (!function_exists('imap_expunge')) {
	/**
	 * Permanently removes messages marked for deletion.
	 * Replaces the PHP imap extension's imap_expunge().
	 *
	 * @param imap $connection IMAP connection
	 *
	 * @return bool True on success, false on failure
	 */
	function imap_expunge($connection) {
		if ($connection instanceof imap) {
			return $connection->expunge();
		}
		return false;
	}
}
