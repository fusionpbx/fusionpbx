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
 * Simple WebSocket client class in pure PHP (PHP 8.1+).
 * Provides connect, send_message, and disconnect methods.
 */
class websocket_client {

	protected $url;
	protected $resource;
	protected $host;
	protected $port;
	protected $path;
	protected $origin;
	protected $key;
	private $stream_blocking;

	/**
	 * @param string $url WebSocket URL (e.g. ws://127.0.0.1:8080/)
	 */
	public function __construct(string $url) {
		$this->url = $url;
		//blocking should be enabled until we perform a handshake
		$this->stream_blocking = true;
	}

	public function socket() {
		return $this->resource;
	}

	/**
	 * Connects to the WebSocket server and performs handshake.
	 */
	public function connect(): void {
		$parts = parse_url($this->url);
		$this->host = $parts['host'] ?? '';
		$this->port = $parts['port'] ?? 80;
		$this->path = $parts['path'] ?? '/';
		$this->origin = ($parts['scheme'] ?? 'http') . '://' . $this->host;

		$this->resource = stream_socket_client("tcp://{$this->host}:{$this->port}", $errno, $errstr, 5);
		if (!$this->resource) {
			throw new \RuntimeException("Unable to connect: ({$errno}) {$errstr}");
		}

		// block the stream
		$is_blocking = $this->is_blocking();
		if (!$is_blocking) {
			$this->block();
		}

		// generate WebSocket key
		$this->key = base64_encode(random_bytes(16));

		// send handshake request
		$header = "GET {$this->path} HTTP/1.1\r\n";
		$header .= "Host: {$this->host}:{$this->port}\r\n";
		$header .= "Upgrade: websocket\r\n";
		$header .= "Connection: Upgrade\r\n";
		$header .= "Sec-WebSocket-Key: {$this->key}\r\n";
		$header .= "Sec-WebSocket-Version: 13\r\n";
		$header .= "Origin: {$this->origin}\r\n\r\n";
		fwrite($this->resource, $header);

		// read response headers
		$response = '';
		while (!feof($this->resource)) {
			$line = fgets($this->resource);
			if ($line === "\r\n") {
				break;
			}
			$response .= $line;
		}
		if (!preg_match('/Sec-WebSocket-Accept: (.*)\r\n/', $response, $m)) {
			throw new \RuntimeException("Handshake failed: no Accept header");
		}
		$accept = trim($m[1]);
		$expected = base64_encode(sha1($this->key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		if ($accept !== $expected) {
			throw new \RuntimeException("Handshake failed: invalid Accept key");
		}

		// Put the blocking back to the previous state
		if (!$is_blocking) {
			$this->disable_block();
		}
	}

	public function set_blocking(bool $block) {
		if ($this->is_connected())
			stream_set_blocking($this->resource, $block);
	}

	public function block() {
		$this->set_blocking(true);
	}

	public function unblock() {
		$this->set_blocking(false);
	}

	public function is_blocking(): bool {
		if ($this->is_connected()) {
			//
			// We allow the socket() function to return the socket as a reference
			// so we have to check the actual socket data to see if blocking was
			// modified outside of the object.
			// $meta_data['blocked'] = 0 // not blocking for event
			// $meta_data['blocked'] = 1 // blocking for event
			//
			$meta_data = stream_get_meta_data($this->resource);
			return !empty($meta_data['blocked']);
		}
		return false;
	}

	/**
	 * Returns true if socket is connected.
	 */
	public function is_connected(): bool {
		return isset($this->resource) && is_resource($this->resource) && !feof($this->resource);
	}

	/**
	 * Sends text to the web socket server.
	 * The web socket client wraps the payload in a web frame socket before sending on the socket.
	 * @param string|null $payload
	 */
	public static function send($resource, ?string $payload): bool {
		if (!is_resource($resource)) {
			throw new \RuntimeException("Not connected");
		}

		// Check for a null message and send a disconnect frame
		if ($payload === null) {
			@fwrite($resource, chr(0x88) . chr(0x00));
			return true;
		}

		$frame_header = "\x81"; // FIN=1, opcode=1 (text frame)
		$length = strlen($payload);

		// Set mask bit and payload length
		if ($length <= 125) {
			$frame_header .= chr(0x80 | $length); // mask bit set
		} elseif ($length <= 65535) {
			$frame_header .= chr(0x80 | 126) . pack('n', $length);
		} else {
			$frame_header .= chr(0x80 | 127) . pack('J', $length);
		}

		// must be masked when sending to the server
		$mask = random_bytes(4);
		$masked_payload = '';

		for ($i = 0; $i < $length; ++$i) {
			$masked_payload .= $payload[$i] ^ $mask[$i % 4];
		}

		$frame = $frame_header . $mask . $masked_payload;

		$written = @fwrite($resource, $frame);

		if ($written === false) {
			echo "[ERROR] Failed to write to socket\n";
			return false;
		}

		if ($written < strlen($frame)) {
			echo "[WARNING] Partial frame sent ({$written}/" . strlen($frame) . " bytes)\n";
			return false;
		}

		return true;
	}

	/**
	 * Disconnects from the server.
	 */
	public function disconnect(): void {
		if (isset($this->resource) && is_resource($this->resource)) {
			@fwrite($this->resource, "\x88\x00"); // 0x88 = close frame, no payload
			@fclose($this->resource);
		}
	}

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

	private function send_control_frame(int $opcode, string $payload = ''): void {
		$header = chr(0x80 | $opcode); // FIN=1, control frame
		$payload_len = strlen($payload);

		// Payload length
		if ($payload_len <= 125) {
			$header .= chr($payload_len);
		} elseif ($payload_len <= 65535) {
			$header .= chr(126) . pack('n', $payload_len);
		} else {
			// Control frames should never be this large; truncate to 125
			$payload = substr($payload, 0, 125);
			$header .= chr(125);
		}

		@fwrite($this->resource, $header . $payload);
	}

	/**
	 * Reads a web socket data frame and converts it to a regular string
	 * @param resource $this->resource
	 * @return string
	 */
	public function read(): ?string {
		if (!is_resource($this->resource)) {
			throw new \RuntimeException("Not connected");
		}

		$final_frame = false;
		$payload_data = '';

		while (!$final_frame) {
			$header = $this->read_bytes(2);
			if ($header === null)
				return null;

			$byte1 = ord($header[0]);
			$byte2 = ord($header[1]);

			$final_frame = ($byte1 >> 7) & 1;
			$opcode = $byte1 & 0x0F;
			$masked = ($byte2 >> 7) & 1;
			$payload_len = $byte2 & 0x7F;

			// Extended payload length
			if ($payload_len === 126) {
				$extended = $this->read_bytes(2);
				if ($extended === null)
					return null;
				$payload_len = unpack('n', $extended)[1];
			} elseif ($payload_len === 127) {
				$extended = $this->read_bytes(8);
				if ($extended === null)
					return null;
				$payload_len = 0;
				for ($i = 0; $i < 8; $i++) {
					$payload_len = ($payload_len << 8) | ord($extended[$i]);
				}
			}

			// Read mask
			$mask = '';
			if ($masked) {
				$mask = $this->read_bytes(4);
				if ($mask === null)
					return null;
			}

			// Read payload
			$payload = $this->read_bytes($payload_len);
			if ($payload === null) {
				echo "[ERROR] Incomplete payload received\n";
				return null;
			}

			// Unmask if needed
			if ($masked) {
				$unmasked = '';
				for ($i = 0; $i < $payload_len; $i++) {
					$unmasked .= $payload[$i] ^ $mask[$i % 4];
				}
				$payload = $unmasked;
			}

			// Handle control frames
			switch ($opcode) {
				case 0x9: // PING
					// Respond with PONG using same payload
					$this->send_control_frame(0xA, $payload);
					echo "[INFO] Received PING, sent PONG\n";
					break 2;
				case 0xA: // PONG
					echo "[INFO] Received PONG\n";
					break 2;
				case 0x1: // TEXT frame
				case 0x0: // Continuation frame
					$payload_data .= $payload;
					break;
				default:
					echo "[WARNING] Unsupported opcode: $opcode\n";
					return null;
			}
		}

		$meta = stream_get_meta_data($this->resource);
		if ($meta['unread_bytes'] > 0) {
			echo "[WARNING] {$meta['unread_bytes']} bytes left in socket after read\n";
		}

		return $payload_data;
	}

	// Helper function to fully read N bytes
	private function read_bytes(int $length): ?string {
		$data = '';
		$max_chunk_size = stream_get_chunk_size($this->resource);

		while (strlen($data) < $length) {
			$remaining = $length - strlen($data);
			$read_size = min($max_chunk_size, $remaining);

			// Read maximum chunk size or what is remaining
			$chunk = fread($this->resource, $read_size);

			if ($chunk === false) {
				echo "[ERROR] fread() failed to read stream\n";
				return null;
			}

			if ($chunk === '') {
				$meta = stream_get_meta_data($this->resource);
				if (!empty($meta['timed_out'])) {
					echo "[ERROR] Socket timed out after reading " . strlen($data) . " of $length bytes\n";
					return null;
				}
				// Jitter or other read issues on the socket so wait 10 ms
				usleep(10000);

				// Try again
				continue;
			}
			$data .= $chunk;
		}
		return $data;
	}

	public function authenticate($token_name, $token_hash) {
		return self::send($this->resource, json_encode(['service' => 'authentication', 'token' => ['name' => $token_name, 'hash' => $token_hash]]));
	}

	/**
	 * Create a token for a service that can broadcast a message
	 * @param string $service_name
	 * @param string $service_class
	 * @param array $permissions
	 * @param int $time_limit_in_minutes
	 * @return array
	 */
	public static function create_service_token(string $service_name, string $service_class, array $permissions = [], int $time_limit_in_minutes = 0) {

		//
		// Create a service token
		//
		$token = (new token())->create($service_name);

		//
		// Put the permissions, and token in local storage so we can use all the information
		// to authenticate an incoming connection from the websocket service.
		//
		$array = $permissions;

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
		// Store the service name used by web browser to subscribe
		// and store the class name of this service
		//
		$array['service'] = true;
		$array['service_name'] = $service_name;
		$array['service_class'] = $service_class;

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

		return [$array['token']['name'], $array['token']['hash']];
	}
}

// PHP <=7.4 compatibility - Replaced in PHP 8.0+
if (!function_exists('stream_get_chunk_size')) {
	function stream_get_chunk_size($stream): int {
		// For PHP versions lower then 8 we send the maximum size defined from https://php.net/stream_get_chunk_size
		return 8192;
	}
}

/**
 * Example usage:
 */
// require_once 'websocket_client.php';
//$client = new websocket_client('ws://127.0.0.1:8080/');
//try {
//    $client->connect();
//    $client->send_message('Hello from PHP client!');
//    // ... do more send_message() calls as needed
//    $client->disconnect();
//} catch (\Throwable $e) {
//    echo "Error: " . $e->getMessage() . "\n";
//}
