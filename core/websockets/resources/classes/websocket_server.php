<?php

declare(strict_types=1);

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
 * Simple WebSocket server class. Supporting chunking, PING, PONG.
 *
 * The on_connect, on_disconnect, on_message events require a function to be passed
 * so the websocket_server can call that function when the specific events occur. Each
 * of the functions must accept one parameter for the resource that the event occurred on.
 * Supports multiple clients and broadcasts messages from one to all others.
 */
class websocket_server {

	/**
	 * Address to bind to. (Default 8080)
	 * @var string
	 */
	protected $address;

	/**
	 * Port to bind to. (Default 0.0.0.0 - all PHP detected IP addresses of the system)
	 * @var int
	 */
	protected $port;

	/**
	 * Tracks if the server is running
	 * @var bool
	 */
	protected $running;

	/**
	 * Resource or stream of the server socket binding
	 * @var resource|stream
	 */
	protected $server_socket;

	/**
	 * List of connected client sockets
	 * @var array
	 */
	protected $clients;

	/**
	 * Used to track on_message events
	 * @var array
	 */
	private $message_callbacks;

	/**
	 * Used to track on_connect events
	 * @var array
	 */
	private $connect_callbacks;

	/**
	 * Used to track on_disconnect events
	 * @var array
	 */
	private $disconnect_callbacks;

	/**
	 * Used to track switch listeners or other socket connection types
	 * @var array
	 */
	private $listeners;

	/**
	 * Creates a websocket_server instance
	 * @param string $address IP to bind (default 0.0.0.0)
	 * @param int    $port    TCP port (default 8080)
	 */
	public function __construct(string $address = '127.0.0.1', int $port = 8080) {
		$this->running = false;
		$this->address = $address;
		$this->port = $port;

		// Initialize arrays
		$this->listeners = [];
		$this->clients = [];
		$this->message_callbacks = [];
		$this->connect_callbacks = [];
		$this->disconnect_callbacks = [];
	}

	private function debug(string $message) {
		self::log($message, LOG_DEBUG);
	}

	private function warn(string $message) {
		self::log($message, LOG_WARNING);
	}

	private function error(string $message) {
		self::log($message, LOG_ERR);
	}

	private function info(string $message) {
		self::log($message, LOG_INFO);
	}

	/**
	 * Starts server: accepts new clients, reads frames, and broadcasts messages.
	 * @returns int A non-zero indicates an abnormal termination
	 */
	public function run(): int {

		$this->server_socket = stream_socket_server("tcp://{$this->address}:{$this->port}", $errno, $errstr);
		if (!$this->server_socket) {
			throw new \RuntimeException("Cannot bind socket ({$errno}): {$errstr}");
		}
		stream_set_blocking($this->server_socket, false);

		// We are now running
		$this->running = true;

		while ($this->running) {
			$listeners = array_column($this->listeners, 0);
			$read = array_merge([$this->server_socket], $listeners, $this->clients);
			$write = $except = [];
			// Server connection issue
			if (false === stream_select($read, $write, $except, null)) {
				$this->running = false;
				break;
			}
			// new connection
			if (in_array($this->server_socket, $read, true)) {
				$conn = @stream_socket_accept($this->server_socket, 0);
				if ($conn) {
					// complete handshake on blocking socket
					stream_set_blocking($conn, true);
					$this->handshake($conn);
					// switch to non-blocking for further reads
					stream_set_blocking($conn, false);
					// add them to the websocket list
					$this->clients[] = $conn;
					// notify websocket on_connect listeners
					$this->trigger_connect($conn);
				}
				continue;
			}
			// handle other sockets
			foreach ($read as $client_socket) {

				// check switch listeners
				if (in_array($client_socket, $listeners, true)) {
					// Process external listeners
					$index = array_search($client_socket, $listeners, true);
					try {
						//send the switch event to the registered callback function
						call_user_func($this->listeners[$index][1], $client_socket);
					} catch (\socket_disconnected_exception $s) {
						$this->info("[INFO] Removed client $s->id from list");
						$success = $this->disconnect_client($client_socket);
						// By attaching the socket_disconnect error message to \socket_exception we can see where something went wrong
						if (!$success)
							throw new socket_exception('Socket does not exist in tracking array', 256, $s);
					}
					continue;
				}

				// Process web socket client communication
				$message = $this->receive_frame($client_socket);
				if ($message === '') {
					continue;
				}
				$this->trigger_message($client_socket, $message);
			}
		}
	}

	/**
	 * Add a non-blocking socket to listen for traffic on
	 * @param resource $socket
	 * @param callable $on_data_ready_callback Callable function to call when data arrives on the socket
	 * @throws \InvalidArgumentException
	 */
	public function add_listener($socket, callable $on_data_ready_callback) {
		if (!is_callable($on_data_ready_callback)) {
			throw new \InvalidArgumentException('The callable on_data_ready_callback must be a valid callable function');
		}
		$this->listeners[] = [$socket, $on_data_ready_callback];
	}

	/**
	 * Returns true if there are connected web socket clients.
	 * @return bool
	 */
	public function has_clients(): bool {
		return !empty($this->clients);
	}

	/**
	 * When a web socket message is received the $on_message_callback function is called.
	 * Multiple on_message functions can be specified.
	 * @param callable $on_message_callback Callable function to call when data arrives on the socket
	 * @throws InvalidArgumentException
	 */
	public function on_message(callable $on_message_callback) {
		if (!is_callable($on_message_callback)) {
			throw new \InvalidArgumentException('The callable on_message_callback must be a valid callable function');
		}
		$this->message_callbacks[] = $on_message_callback;
	}

	/**
	 * Calls all the on_message functions
	 * @param resource $socket
	 * @param string $message
	 * @return void
	 */
	private function trigger_message($socket, string $message) {
		foreach ($this->message_callbacks as $callback) {
			$response = call_user_func($callback, $socket, $message);
			if ($response !== null) {
				$this->send($socket, $response);
			}
			return;
		}
	}

	/**
	 * When a web socket handshake has completed, the $on_connect_callback function is called.
	 * Multiple on_connect functions can be specified.
	 * @param callable $on_connect_callback Callable function to call when a new connection occurs.
	 * @throws InvalidArgumentException
	 */
	public function on_connect(callable $on_connect_callback) {
		if (!is_callable($on_connect_callback)) {
			throw new \InvalidArgumentException('The callable on_connect_callback must be a valid callable function');
		}
		$this->connect_callbacks[] = $on_connect_callback;
	}

	/**
	 * Calls all the on_connect functions
	 * @param resource $socket
	 */
	private function trigger_connect($socket) {
		foreach ($this->connect_callbacks as $callback) {
			$response = call_user_func($callback, $socket);
			if ($response !== null) {
				self::send($socket, $response);
			}
		}
	}

	/**
	 * When a web socket has disconnected, the $on_disconnect_callback function is called.
	 * Multiple functions can be specified with subsequent calls
	 * @param string|callable $on_disconnect_callback Callable function to call when a socket disconnects. The function must accept a single parameter for the socket that was disconnected.
	 * @throws InvalidArgumentException
	 */
	public function on_disconnect($on_disconnect_callback) {
		if (!is_callable($on_disconnect_callback)) {
			throw new \InvalidArgumentException('The callable on_disconnect_callback must be a valid callable function');
		}
		$this->disconnect_callbacks[] = $on_disconnect_callback;
	}

	/**
	 * Calls all the on_disconnect_callback functions
	 * @param type $socket
	 */
	private function trigger_disconnect($socket) {
		foreach ($this->disconnect_callbacks as $callback) {
			call_user_func($callback, $socket);
		}
	}

	/**
	 * Returns the socket used in the server connection
	 * @return resource
	 */
	public function get_socket() {
		return $this->server_socket;
	}

	/**
	 * Remove a client socket on disconnect.
	 * @return bool Returns true on client disconnect and false when the client is not found in the tracking array
	 */
	protected function disconnect_client($socket, $error = null): bool {
		$index = array_search($resource, $this->clients, true);
		if ($index !== false) {
			self::disconnect($resource);
			unset($this->clients[$index]);
			$this->trigger_disconnect($socket);
			return true;
		}
		return false;
	}

	/**
	 * Sends a disconnect frame with no payload
	 * @param type $resource
	 */
	public static function disconnect($resource) {
		if (is_resource($resource)) {
			//send OPCODE
			@fwrite($resource, "\x88\x00"); // 0x88 = close frame, no payload
			@fclose($resource);
		}
	}

	/**
	 * Performs web socket handshake on new connection.
	 * @param type $socket Socket to perform the handshake on.
	 */
	protected function handshake($socket) {
		// ensure blocking to read full header
		stream_set_blocking($socket, true);
		$request_header = '';
		while (($line = fgets($socket)) !== false) {
			$request_header .= $line;
			if (rtrim($line) === '') {
				break;
			}
		}
		if (!preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $request_header, $matches)) {
			throw new \invalid_handshake_exception($socket, "Invalid WebSocket handshake");
		}
		$key = trim($matches[1]);
		$accept_key = base64_encode(
				sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true)
		);
		$response_header = "HTTP/1.1 101 Switching Protocols\r\n"
				. "Upgrade: websocket\r\n"
				. "Connection: Upgrade\r\n"
				. "Sec-WebSocket-Accept: {$accept_key}\r\n\r\n";
		fwrite($socket, $response_header);
	}

	/**
	 * Read specific number of bytes from a web socket
	 * @param resource $socket
	 * @param int $length
	 * @return string
	 */
	private function read_bytes($socket, int $length): string {
		$data = '';
		while (strlen($data) < $length && is_resource($socket)) {
			$chunk = fread($socket, $length - strlen($data));
			if ($chunk === false || $chunk === '' || !is_resource($socket)) {
				$this->disconnect_client($socket);
				return '';
			}
			$data .= $chunk;
		}
		return $data;
	}

	/**
	 * Reads a web socket data frame and converts it to a regular string
	 * @param resource $socket
	 * @return string
	 */
	private function receive_frame($socket): string {
		if (!is_resource($socket)) {
			throw new \RuntimeException("Not connected");
		}

		$final_frame = false;
		$payload_data = '';

		while (!$final_frame) {
			$header = $this->read_bytes($socket, 2);
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
				$extended = $this->read_bytes($socket, 2);
				if ($extended === null)
					return null;
				$payload_len = unpack('n', $extended)[1];
			} elseif ($payload_len === 127) {
				$extended = $this->read_bytes($socket, 8);
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
				$mask = $this->read_bytes($socket, 4);
				if ($mask === null)
					return null;
			}

			// Read payload
			$payload = $this->read_bytes($socket, $payload_len);
			if ($payload === null) {
				$this->error("[ERROR] Incomplete payload received");
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
					$this->info("Received PING, sent PONG");
					continue; // Skip returning PING
				case 0x8: // CLOSE frame
					$this->info("Received CLOSE frame, connection will be closed.");
					$this->disconnect_client($socket);
					return null;
				case 0xA: // PONG
					$this->info("Received PONG");
					$reason = $this->read_bytes($socket, 2);
					$this->info("Reason: $reason");
					continue; // Skip returning PONG
				case 0x1: // TEXT frame
				case 0x0: // Continuation frame
					$payload_data .= $payload;
					break;
				default:
					$this->warn("Unsupported opcode: $opcode");
					return null;
			}
		}

		$meta = stream_get_meta_data($socket);
		if ($meta['unread_bytes'] > 0) {
			$this->warn("{$meta['unread_bytes']} bytes left in socket after read");
		}

		return $payload_data;
	}

	/**
	 * Send text frame to client. If the socket connection is not a valid resource, the send
	 * method will fail silently and return false.
	 * @param resource $resource The socket or resource id to communicate on.
	 * @param string|null $payload The message to send to the clients. Sending null as the message sends a close frame packet.
	 * @return bool True if message was sent on the provided resource or false if there was an error.
	 */
	public static function send($resource, ?string $payload): bool {
		if (!is_resource($resource)) {
			throw new \socket_disconnected_exception($resource);
		}

		// Check for a null message and send a disconnect frame
		if ($payload === null) {
			// 88 = CLOSE, 00 = NO REASON
			@fwrite($resource, chr(0x88) . chr(0x00));
			return true;
		}

		$chunk_size = 4096; // 4 KB
		$payload_len = strlen($payload);
		$offset = 0;
		$first = true;

		while ($offset < $payload_len) {
			$remaining = $payload_len - $offset;
			$chunk = substr($payload, $offset, min($chunk_size, $remaining));
			$chunk_len = strlen($chunk);

			// Determine FIN bit and opcode
			$fin = ($offset + $chunk_size >= $payload_len) ? 0x80 : 0x00; // 0x80 if final
			$opcode = $first ? 0x1 : 0x0; // text for first frame, continuation for rest
			$first = false;

			// Build header
			$header = chr($fin | $opcode);

			// Payload length
			if ($chunk_len <= 125) {
				$header .= chr($chunk_len);
			} elseif ($chunk_len <= 65535) {
				$header .= chr(126) . pack('n', $chunk_len);
			} else {
				// 64-bit big-endian
				$length_bytes = '';
				for ($i = 7; $i >= 0; $i--) {
					$length_bytes .= chr(($chunk_len >> ($i * 8)) & 0xFF);
				}
				$header .= chr(127) . $length_bytes;
			}

			// Send frame (header + chunk)
			$bytes_written = @fwrite($resource, $header . $chunk);
			if ($bytes_written === false) {
				return false;
			}

			$offset += $chunk_len;
		}

		return true;
	}

	/**
	 * Get the IP and port of the connected remote system.
	 * @param socket $socket The socket stream of the connection
	 * @return array An associative array of remote_ip and remote_port
	 */
	public static function get_remote_info($socket): array {
		[$remote_ip, $remote_port] = explode(':', stream_socket_get_name($socket, true), 2);
		return ['remote_ip' => $remote_ip, 'remote_port' => $remote_port];
	}

	/**
	 * Print socket information
	 * @param resource $resource
	 * @param bool $return If you would like to capture the output of print_r(), use the return parameter. When this
	 * parameter is set to true, print_r() will return the information rather than print it.
	 */
	public static function print_stream_info($resource, $return = false) {
		if (is_resource($resource)) {
			$meta_data = stream_get_meta_data($resource);
			[$remote_ip, $remote_port] = explode(':', stream_socket_get_name($resource, true), 2);
			$meta_data['remote_addr'] = $remote_ip;
			$meta_data['remote_port'] = $remote_port;

			if ($return)
				return $meta_data;
			print_r($meta_data);
		}
	}
}
