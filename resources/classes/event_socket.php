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

class event_socket {
	private $buffer;
	private $fp;

	public function __construct($fp = false) {
		$this->buffer = new buffer;
		$this->fp = $fp;
	}

	public function __destructor() {
		$this->close();
	}

	public function read_event() {
		if (!$this->fp) {
			return false;
		}

		$b = $this->buffer;
		$content_length = 0;
		$content = Array();

		while (true) {
			while(($line = $b->read_line()) !== false ) {
				if ($line == '') {
					break 2;
				}
				$kv = explode(':', $line, 2);
				$content[trim($kv[0])] = trim($kv[1]);
			}
			usleep(100);

			if (feof($this->fp)) {
				break;
			}

			$buffer = fgets($this->fp, 1024);
			$b->append($buffer);
		}

		if (array_key_exists('Content-Length', $content)) {
			$str = $b->read_n($content['Content-Length']);
			if ($str === false) {
				while (!feof($this->fp)) {
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

	public function connect($host, $port, $password) {
		//set defaults
		if ($host == '') { $host = '127.0.0.1'; }
		if ($port == '') { $port = '8021'; }
		if ($password == '') { $password = 'ClueCon'; }

		$fp = @fsockopen($host, $port, $errno, $errdesc, 3);

		if (!$fp) {
			return false;
		}

		socket_set_blocking($fp, false);
		$this->fp = $fp;

		// Wait auth request and send response
			while (!feof($fp)) {
				$event = $this->read_event();
				if(@$event['Content-Type'] == 'auth/request'){
					fputs($fp, "auth $password\n\n");
					break;
				}
			}

		// Wait auth response
			while (!feof($fp)) {
				$event = $this->read_event();
				if (@$event['Content-Type'] == 'command/reply') {
					if (@$event['Reply-Text'] == '+OK accepted') {
						return $fp;
					}
					$this->fp = false;
					fclose($fp);
					return false;
				}
			}

		return false;
	}

	public function request($cmd) {
		if (!$this->fp) {
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

	public function reset_fp($fp = false){
		$tmp = $this->fp;
		$this->fp = $fp;
		return $tmp;
	}

	public function close() {
		if ($this->fp) {
			fclose($this->fp);
			$this->fp = false;
		}
	}
}

/*
function event_socket_create($host, $port, $password) {
	$esl = new event_socket;
	if ($esl->connect($host, $port, $password)) {
		return $esl->reset_fp();
	}
	return false;
}

function event_socket_request($fp, $cmd) {
	$esl = new event_socket($fp);
	$result = $esl->request($cmd);
	$esl->reset_fp();
	return $result;
}
*/

// $esl = new event_socket;
// $esl->connect('127.0.0.1', 8021, 'ClueCon');
// print($esl->request('api sofia status'));

// $fp = event_socket_create('127.0.0.1', 8021, 'ClueCon');
// print(event_socket_request($fp, 'api sofia status'));

?>
