<?php

/*
 * PHP TFTP Server
 *
 * Copyright (c) 2011 <mattias.wadman@gmail.com>
 *
 * MIT License:
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 *
 * Extend TFTPServer class and then call loop method with UDP URL.
 * Possible methods to override:
 * exists($peer, $filename)
 *   Check if file exist, default always true.
 * readable($peer, $filename)
 *   Check if file is readable, default always true.
 * get($peer, $filename, $mode)
 *   Return content of file, default always false.
 *   Only called if both exists and readable returns true.
 * writable($peer, $filename)
 *   Check if file is writable, default always false.
 * put($peer, $filename, $mode, $content)
 *   Write content to file.
 *   Only falled if both exists and writable returns true.
 *
 * $peer is $ip:$port, source ip and port of client
 * $filename is filename specified by client
 * $mode is probably "octet" or "netascii"
 * $content is file content
 *
 * The server support multiple concurrent read and writes, but the method calls
 * are serialized, so make sure to return quickly.
 *
 * TODO:
 * select must handle EINTR, how?
 * multiple recv per select?
 *
 */

/* Note about the Logger class:
 * The "priority" and "minimum should be one of the constants used for syslog.
 * See: http://php.net/manual/en/function.syslog.php
 * They are: LOG_EMERG, LOG_ALERT, LOG_CRIT, LOG_ERR, LOG_WARNING, LOG_NOTICE,
 *           LOG_INFO, LOG_DEBUG
 * Note that LOG_EMERG, LOG_ALERT, and LOG_CRIT are not really relevant to a
 * tftp server - these represent instability in the entire operating system.
 * Note that the number they are represented by are in reverse order -
 * LOG_EMERG is the lowest, LOG_DEBUG the highest.
 */

abstract class Logger
{
  function __construct($minimum)
  {
    $this->minimum = $minimum;
  }

  function shouldlog($priority)
  {
    // Note: this looks reversed, but is correct
    // the priority must be AT LEAST the minimum,
    // because higher priorities represent lower numbers.
    return $priority <= $this->minimum;
  }

  abstract function log($priority, $message);
}

class Logger_Null extends Logger
{
  function log($priority, $message)
  {
  }
}

class Logger_Syslog extends Logger
{
  function log($priority, $message)
  {
    if($this->shouldlog($priority))
      syslog($priority,$message);
  }
}

class Logger_Filehandle extends Logger
{
  private $priority_map = array(
    LOG_DEBUG => "D",
    LOG_INFO => "I",
    LOG_NOTICE => "N",
    LOG_WARNING => "W",
    LOG_ERR => "E",
    LOG_CRIT => "C",
    LOG_ALERT => "A",
    LOG_EMERG => "!"
  );
  function __construct($minimum, $filehandle, $dateformat = "r")
  {
    $this->filehandle = $filehandle;
    $this->dateformat = $dateformat;
    return parent::__construct($minimum);
  }

  function log($priority, $message)
  {
    if($this->shouldlog($priority))
      fwrite($this->filehandle, date($this->dateformat) . ": " . $this->priority_map[$priority] . " $message\n");
  }
}

class Logger_Filename extends Logger_Filehandle
{
  function __construct($minimum, $filename, $dateformat = "r")
  {
    return parent::__construct($minimum, fopen($filename, "a"), $dateformat);
  }
}

class Logger_Stderr extends Logger_Filehandle
{
  function __construct($minimum, $dateformat = "r")
  {
    return parent::__construct($minimum, STDERR, $dateformat);
  }
}
class Logger_Stdout extends Logger_Filehandle
{
  function __construct($minimum, $dateformat = "r")
  {
    return parent::__construct($minimum, STDOUT, $dateformat);
  }
}

class TFTPOpcode
{
  public static function name($v)
  {
    static $names = array(TFTPOpcode::RRQ => "RRQ",
			  TFTPOpcode::WRQ => "WRQ",
			  TFTPOpcode::DATA => "DATA",
			  TFTPOpcode::ACK => "ACK",
			  TFTPOpcode::ERROR => "ERROR",
			  TFTPOpcode::OACK => "OACK");
    if(isset($names[$v]))
      return $names[$v];
    else
      return "UNKNOWN";
  }

  const RRQ = 1; // read request
  const WRQ = 2; // write request
  const DATA = 3; // send data
  const ACK = 4; // ack data
  const ERROR = 5;
  const OACK = 6; // option ack, instead of first ACK/DATA
}

class TFTPError
{
  const NOT_DEFINED = 0; // see error message instead of error code
  const FILE_NOT_FOUND = 1;
  const ACCESS_VIOLATION = 2;
  const DISK_FULL = 3;
  const ILLEGAL_OPERATION = 4;
  const UNKNOWN_TID = 5; // unknown transfer (id is ip:port pair)
  const FILE_ALREADY_EXISTS = 6;
  const NO_SUCH_USER = 7;
  const OACK_FAILURE = 8;
}

class TFTPTransferState
{
  const READY = 1;
  const SENDING_WAIT_OACK = 2;
  const SENDING = 3;
  const RECEIVING = 4;
  const TERMINATING = 5;
}

abstract class TFTPTransfer {
  public $state;
  public $peer;
  public $retransmit_timeout;
  public $block_size;
  public $tsize;
  protected $_server; // TFTPServer reference

  function __construct($server, $peer, $extensions)
  {
    $this->state = TFTPTransferState::READY;
    $this->peer = $peer;
    $this->retransmit_timeout = $server->retransmit_timeout;
    $this->block_size = $server->block_size;
    $this->tsize = 0;
    $this->_server = $server;

    if(isset($extensions["timeout"])) {
      $timeout = (int)$extensions["timeout"];
      if($timeout > 0 && $timeout < 256)
	$this->retransmit_timeout = $timeout;
    }

    if(isset($extensions["blksize"])) {
      $blksize = (int)$extensions["blksize"];
      if($blksize > 0 && $blksize <= $server->max_block_size)
	$this->block_size = $blksize;
    }

    // tsize is only checked for in write transfers
  }

  protected function log_debug($message)
  {
    $this->_server->log_debug($this->peer, $message);
  }

  protected function log_info($message)
  {
    $this->_server->log_info($this->peer, $message);
  }

  protected function log_warning($message)
  {
    $this->_server->log_warning($this->peer, $message);
  }

  protected function log_error($message)
  {
    $this->_server->log_error($this->peer, $message);
  }

  protected function terminal_info($error, $message)
  {
    $this->log_info($message);
    $this->state = TFTPTransferState::TERMINATING;
    return TFTPServer::packet_error($error, $message);
  }

  protected function terminal_error($op, $error, $message)
  {
    $this->log_debug("$op: $message");
    $this->state = TFTPTransferState::TERMINATING;
    return TFTPServer::packet_error($error, $message);
  }

  protected function illegal_operation($op, $message = "Illegal operation")
  {
    return $this->terminal_error($op, TFTPError::ILLEGAL_OPERATION, $message);
  }

  public function rrq($filename, $mode)
  {
    return $this->illegal_operation("RRQ");
  }

  public function wrq($filename, $mode)
  {
    return $this->illegal_operation("WRQ");
  }

  public function data($block, $data)
  {
    return $this->illegal_operation("DATA");
  }

  public function ack($block)
  {
    return $this->illegal_operation("ACK");
  }

  public function error($error, $message)
  {
    $this->log_debug("ERROR: $error: $message");
    $this->state = TFTPTransferState::TERMINATING;
  }

  protected function use_extensions() {
    return
      $this->retransmit_timeout != $this->_server->retransmit_timeout ||
      $this->block_size != $this->_server->block_size ||
      $this->tsize != 0;
  }

  protected function packet_oack() {
    $options = array();

    if($this->retransmit_timeout != $this->_server->retransmit_timeout)
      $options["timeout"] = (string)$this->retransmit_timeout;

    if($this->block_size != $this->_server->block_size)
      $options["blksize"] = (string)$this->block_size;

    if($this->tsize != 0)
      $options["tsize"] = (string)$this->tsize;

    return TFTPServer::packet_oack($options);
  }
}

class TFTPReadTransfer extends TFTPTransfer {
  private $_last_recv_ack;
  private $_last_sent_data;
  private $_buffer;
  private $_block;
  private $_last_block;

  function __construct($server, $peer, $extensions)
  {
    parent::__construct($server, $peer, $extensions);
    $this->_last_recv_ack = time();
    $this->_last_sent_data = $this->_last_recv_ack;
    $this->_buffer = false;
    $this->_block = 1;
    $this->_last_block = 1;

    $this->log_debug("new read transfer");
  }

  private function current_block()
  {
    return substr($this->_buffer,
		  ($this->_block - 1) * $this->block_size,
		  $this->block_size);
  }

  private function packet_data_current()
  {
    $this->_last_sent_data = time();

    if($this->state == TFTPTransferState::SENDING_WAIT_OACK)
      return $this->packet_oack();
    else
      return TFTPServer::packet_data($this->_block, $this->current_block());
  }

  public function rrq($filename, $mode)
  {
    $this->log_debug("RRQ: filename $filename in $mode mode");

    if($this->state != TFTPTransferState::READY)
      return $this->illegal_operation("RRQ", "Not in ready state");

    if(!$this->_server->exists($this->peer, $filename))
      return $this->terminal_info(TFTPError::FILE_NOT_FOUND,
				  "File $filename does not exist");

    if(!$this->_server->readable($this->peer, $filename))
      return $this->terminal_info(TFTPError::ACCESS_VIOLATION,
				  "File $filename is not readable");

    $this->_buffer = $this->_server->get($this->peer, $filename, $mode);
    if($this->_buffer === false)
      return $this->terminal_info(TFTPError::FILE_NOT_FOUND,
				  "Failed to read $filename");

    $this->log_info("Reading $filename (" .
		    strlen($this->_buffer) . " bytes)");

    if($this->use_extensions())
      $this->state = TFTPTransferState::SENDING_WAIT_OACK;
    else
      $this->state = TFTPTransferState::SENDING;
    $this->_last_block = floor(strlen($this->_buffer) /
			       $this->block_size) + 1;

    $this->log_debug("RRQ: send first block or OACK");
    return $this->packet_data_current();
  }

  public function ack($block)
  {
    if($this->state == TFTPTransferState::SENDING_WAIT_OACK) {
      if($block != 0) {
	$this->log_debug("ACK: waiting OACK ACK got block $block");
	return false;
      }

      $this->state = TFTPTransferState::SENDING;
      $this->log_debug("ACK: got OACK ACK, send first block");
      return $this->packet_data_current();
    }

    if($this->state != TFTPTransferState::SENDING)
      return $this->illegal_operation("ACK", "Not in sending state");

    $this->log_debug("ACK: block $block");
    $this->_last_recv_ack = time();

    if($block < $this->_block) {
      $this->log_debug("ACK: duplicate block $block");
      // just ignore it
      return false;
    }

    if($block > $this->_last_block)
      return $this->illegal_operation("ACK",
				      "Block $block outside " .
				      "range 1-{$this->_last_block}");

    if($block == $this->_last_block) {
      $this->log_debug("ACK: last block, done");
      $this->state = TFTPTransferState::TERMINATING;
      return false;
    }

    // move to next block
    $this->_block = $block + 1;

    $this->log_debug("ACK: sending block {$this->_block}");
    return $this->packet_data_current();
  }

  public function retransmit($now)
  {
    if($now - $this->_last_recv_ack > $this->_server->timeout) {
      $this->log_debug("retransmit: timeout");
      $this->state = TFTPTransferState::TERMINATING;
      return false;
    }

    if($now - $this->_last_sent_data > $this->retransmit_timeout) {
      $this->log_debug("retransmit: resending block {$this->_block} or OACK");
      return $this->packet_data_current();
    }

    return false;
  }
}

class TFTPWriteTransfer extends TFTPTransfer {
  private $_last_sent_ack;
  private $_last_recv_data;
  private $_buffer;
  private $_buffer_size;
  private $_next_block;
  private $_filename;
  private $_mode;

  function __construct($server, $peer, $extensions)
  {
    parent::__construct($server, $peer, $extensions);
    $this->_last_sent_ack = time();
    $this->_last_recv_data = $this->_last_sent_ack;
    $this->_buffer = array();
    $this->_buffer_size = 0;
    $this->_last_recv_block = 0;
    $this->_filename = false;
    $this->_mode = false;

    if(isset($extensions["tsize"]))
      $this->tsize = (int)$extensions["tsize"];

    $this->log_debug("new write transfer");
  }

  private function packet_ack_current()
  {
    $this->_last_sent_ack = time();

    if($this->_last_recv_block == 0 && $this->use_extensions())
      return $this->packet_oack();
    else
      return TFTPServer::packet_ack($this->_last_recv_block);
  }

  public function wrq($filename, $mode)
  {
    $this->log_debug("WRQ: filename $filename in $mode mode");

    if($this->state != TFTPTransferState::READY)
      return $this->illegal_operation("WRQ", "Not in ready state");

    if(!$this->_server->writable($this->peer, $filename))
      return $this->terminal_info(TFTPError::ACCESS_VIOLATION,
				  "File $filename is not writable");

    if($this->tsize != 0 && $this->tsize > $this->_server->max_put_size)
      return $this->terminal_info(TFTPError::DISK_FULL,
				  "File too big, " .
				  $this->tsize . "(tsize) > " .
				  $this->_server->max_put_size);

    $this->state = TFTPTransferState::RECEIVING;
    $this->_filename = $filename;
    $this->_mode = $mode;
    $this->_last_sent_ack = time();

    $this->log_debug("WRQ: ack request");
    if($this->use_extensions())
      return $this->packet_oack();
    else
      return TFTPServer::packet_ack(0);
  }

  public function data($block, $data)
  {
    if($this->state != TFTPTransferState::RECEIVING)
      return $this->illegal_operation("DATA", "Not in receiving state");

    $this->log_debug("DATA: block $block");
    $this->last_recv_data = time();

    if($block <= $this->_last_recv_block) {
      $this->log_debug("DATA: duplicate block $block");
      // just ignore it
      return false;
    }

    if($block != $this->_last_recv_block + 1)
      return $this->illegal_operation("DATA",
				      "Expected block " . 
				      ($this->_last_recv_block + 1) .
				      " got $block");

    $this->_last_recv_block = $block;
    $this->_last_recv_data = time();
    array_push($this->_buffer, $data);
    $this->_buffer_size += strlen($data);

    if($this->_buffer_size > $this->_server->max_put_size)
      return $this->terminal_info(TFTPError::DISK_FULL,
				  "File too big, " .
				  $this->_buffer_size . " > " .
				  $this->_server->max_put_size);

    if(strlen($data) < $this->block_size) {
      $this->log_debug("DATA: last, done");
      $this->state = TFTPTransferState::TERMINATING;
      $this->log_info("Writing {$this->_filename} " .
		      "({$this->_buffer_size} bytes)");
      $this->_server->put($this->peer, $this->_filename, $this->_mode,
			  implode("", $this->_buffer));
      return $this->packet_ack_current();
    }

    $this->log_debug("DATA: ack block $block");
    return $this->packet_ack_current();
  }

  public function retransmit($now)
  {
    if($now - $this->_last_recv_data > $this->_server->timeout) {
      $this->log_debug("retransmit: timeout");
      $this->state = TFTPTransferState::TERMINATING;
      return false;
    }

    if($now - $this->_last_sent_ack > $this->retransmit_timeout) {
      $this->log_debug("retransmit: reack block {$this->_last_recv_block}");
      return $this->packet_ack_current();
    }

    return false;
  }
}

class TFTPServer {
  public $block_size = 512;
  public $max_block_size = 65464; // max block size from rfc2348
  public $timeout = 10;
  public $retransmit_timeout = 1;
  public $max_put_size = 10485760; // 10 Mibi
  private $_socket_url;
  private $_socket;
  private $_transfers = array();
  private $_logger = NULL;

  function __construct($socket_url, $logger = NULL)
  {
    $this->_socket_url = $socket_url;
    $this->_logger = $logger;
  }

  public function exists($peer, $filename)
  {
    return true;
  }

  public function readable($peer, $filename)
  {
    return true;
  }

  public function get($peer, $filename, $mode)
  {
    return false;
  }

  public function writable($peer, $filename)
  {
    return false;
  }

  public function put($peer, $filename, $mode, $content)
  {
  }

  public function logger_log($priority, $message) {
    if($this->_logger === NULL)
      return;

    $this->_logger->log($priority, $message);
  }

  public function log_debug($peer, $message)
  {
    $this->logger_log(LOG_DEBUG, "$peer $message");
  }

  public function log_info($peer, $message)
  {
    $this->logger_log(LOG_INFO, "$peer $message");
  }

  public function log_warning($peer, $message)
  {
    $this->logger_log(LOG_WARNING, "$peer $message");
  }

  public function log_error($peer, $message)
  {
    $this->logger_log(LOG_ERR, "$peer $message");
  }

  public static function packet_ack($block)
  {
    return pack("nn", TFTPOpcode::ACK, $block);
  }

  public static function packet_data($block, $data)
  {
    return pack("nn", TFTPOpcode::DATA, $block) . $data;
  }

  public static function packet_error($code, $message = "")
  {
    return pack("nn", TFTPOpcode::ERROR, $code) . $message . "\0";
  }

  public static function packet_oack($options)
  {
    $data = "";
    foreach($options as $key => $value)
      $data .= "$key\0$value\0";
    return pack("n", TFTPOpcode::OACK) . $data;
  }

  public static function escape_string($str)
  {
    $b = "";
    $l = strlen($str);
    for($i = 0; $i < $l; $i++) {
      $c = $str[$i];
      if(ctype_print($c))
	$b .= $c;
      else
	$b .= sprintf("\\x%'02x", ord($c));
    }

    return $b;
  }

  public function loop(&$error = false, $user = null)
  {
    $this->_socket =
      stream_socket_server($this->_socket_url, $errno, $errstr,
			   STREAM_SERVER_BIND);
    if(!$this->_socket) {
      if($error !== false)
	$error = "$errno: $errstr";
      return false;
    }

    if($user != null) {
      posix_seteuid($user["uid"]);
      posix_setegid($user["gid"]);
    }

    stream_set_blocking($this->_socket, false);

    return $this->loop_ex();
  }

  private function loop_ex()
  {
    $now = $last = time();

    while(true) {
      $read = array($this->_socket);
      $write = null;
      $excpt = null;
      $r = stream_select($read, $write, $excpt, 1);

      if($r === false) {
        $this->log_error("server", "select returned false");
        continue;
      }

      if(count($read) > 0) {
        $packet = stream_socket_recvfrom($this->_socket,
                65535, // max udp packet size
                0, // no flags
                $peer);
        // ipv6 hack, convert to [host]:port format
        if(strpos($peer, ".") === false) {
          $portpos = strrpos($peer, ":");
          $host = substr($peer, 0, $portpos);
          $port = substr($peer, $portpos + 1);
          $peer = "[$host]:$port";
        }
        $this->log_debug($peer, "request: ".strlen($packet)." bytes");
        $this->log_debug($peer, "request: ".TFTPServer::escape_string($packet));
        $reply = $this->request($peer, $packet);
        if($reply !== false) {
          $this->log_debug($peer, "reply: " .
              TFTPServer::escape_string($reply));
          stream_socket_sendto($this->_socket, $reply, 0, $peer);
        }
      }

      $now = time();
      if($now != $last) {
	$last = $now;
	$this->retransmit($now);
      }
    }
  }

  private function retransmit($now)
  {
    foreach($this->_transfers as $peer => $transfer) {
      $reply = $transfer->retransmit($now);
      if($reply !== false) {
	$this->log_debug($peer, "resend: " .
			 TFTPServer::escape_string($reply));
	stream_socket_sendto($this->_socket, $reply, 0, $peer);
      }

      if($transfer->state == TFTPTransferState::TERMINATING)
	unset($this->_transfers[$peer]);
    }
  }

  private function request($peer, $packet)
  {
    if(strlen($packet) < 4) {
      $this->log_debug($peer, "request: short packet");
      return false;
    }

    $reply = false;
    $transfer = false;
    if(isset($this->_transfers[$peer])) {
      $this->log_debug($peer, "request: existing transfer");
      $transfer = $this->_transfers[$peer];
    }

    $fields = unpack("n", $packet);
    $op = $fields[1];
    $this->log_debug($peer, "request: opcode " .
		     TFTPOpcode::name($op) . " ($op)");
    switch($op) {
      case TFTPOpcode::WRQ:
      case TFTPOpcode::RRQ:
	$a = explode("\0", substr($packet, 2));
	if(count($a) < 3 || $a[count($a) - 1] != "") {
	  $this->log_warning($peer, "request: malformed " .
			     TFTPOpcode::name($op));
	  return false;
	}

	$rawexts = array_slice($a, 2, -1);

	// Cisco IP Phone 7941 (and possibly others) return an extra null
	// at the end; a breach of RFC rfc2347. This is a workaround.
	// If odd count strip last and continue if empty, else warn and ignore
	if(count($rawexts) % 2 != 0) {
	  if(array_pop($rawexts)!="") {
	    $this->log_warning($peer, "request: malformed extension " .
			       "key/value pairs " . TFTPOpcode::name($op));
	    return false;
	  }
	}

	$extensions = array();
	foreach(array_chunk($rawexts, 2) as $pair)
	  $extensions[strtolower($pair[0])] = $pair[1];

	if($transfer === false) {
	  if($op == TFTPOpcode::RRQ)
	    $transfer = new TFTPReadTransfer($this, $peer, $extensions);
	  else
	    $transfer = new TFTPWriteTransfer($this, $peer, $extensions);

	  $this->_transfers[$peer] = $transfer;
	}
	
	if($op == TFTPOpcode::RRQ)
	  $reply = $transfer->rrq($a[0], $a[1]);
	else
	  $reply = $transfer->wrq($a[0], $a[1]);

	break;
      case TFTPOpcode::ACK:
	if(strlen($packet) != 4) {
	  $this->log_warning($peer, "request: malformed ACK");
	  return false;
	}

	$a = unpack("n", substr($packet, 2));
	if($transfer === false) {
	  // do not warn, some clients like BSD tftp sends ack on read error
	  $this->log_debug($peer, "request: ack from unknwon peer");
	} else
	  $reply = $transfer->ack($a[1]);
	break;
      case TFTPOpcode::DATA:
	if(strlen($packet) < 4) {
	  $this->log_warning($peer, "request: malformed DATA");
	  return false;
	}
	
	$a = unpack("n", substr($packet, 2));
	$data = substr($packet, 4, strlen($packet) - 4);
	if($transfer === false) {
	  $this->log_warning($peer, "request: data from unknwon peer");
	  $reply = TFTPServer::packet_error(TFTPError::UNKNOWN_TID,
					    "Unknown TID for DATA");
	} else
	  $reply = $transfer->data($a[1], $data);
	break;
      case TFTPOpcode::ERROR:
	$a = unpack("n", substr($packet, 2, 2));
	$message = substr($packet, 4, strlen($packet) - 5);

	if($transfer === false)
	  $this->log_warning($peer, "request: error from unknwon peer, " .
			     "{$a[1]}:$message");
	else
	  $transfer->error($a[1], $message);
	break;
      default:
	break;
    }

    if($transfer !== false &&
       $transfer->state == TFTPTransferState::TERMINATING) {
      $this->log_debug($peer, "request: terminating");
      unset($this->_transfers[$transfer->peer]);
    }

    return $reply;
  }
}

?>
