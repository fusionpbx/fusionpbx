<?php
/* $Id$ */
/*
	v_exec.php
	Copyright (C) 2008 Mark J Crane
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>

*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('conference_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//get the http values and set them as php variables
	if (count($_GET)>0) {
		$cmd = trim(check_str($_GET["cmd"]));
		$name = trim(check_str($_GET["name"]));
		$uuid = trim(check_str($_GET["uuid"]));
		$data = trim(check_str($_GET["data"]));
		$id = trim(check_str($_GET["id"]));
		$direction = trim(check_str($_GET["direction"]));
	}

//authorized commands
	if ($cmd == "conference") {
		//authorized;
	} else {
		//not found. this command is not authorized
		echo "access denied";
		exit;
	}

//check if the domain is in the switch_cmd
	if(stristr($name, $_SESSION['domain_name']) === FALSE) {
		echo "access denied";
		exit;
	}

//define an alternative kick all
	function conference_end($fp, $name) {
		$switch_cmd = "conference '".$name."' xml_list";
		$xml_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
		try {
			$xml = new SimpleXMLElement($xml_str);
		}
		catch(Exception $e) {
			//echo $e->getMessage();
		}
		$session_uuid = $xml->conference['uuid'];
		$x = 0;
		foreach ($xml->conference->members->member as $row) {
			$switch_result = event_socket_request($fp, 'api uuid_kill '.$row->uuid);
			if ($x < 1) {
				usleep(500000); //500000 = 0.5 seconds
			}
			else {
				usleep(10000);  //1000000 = 0.01 seconds
			}
			$x++;
		}
	}

//execute the command
	if (count($_GET) > 0) {
		if (strlen($cmd) > 0) {
			//prepare the switch cmd
				$switch_cmd = $cmd . " ";
				$switch_cmd .= $name . " ";
				$switch_cmd .= $data . " ";
				if (strlen($id) > 0) {
					$switch_cmd .= " ".$id;
				}

			//connect to event socket
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
				if ($fp) {
					if ($data == "energy") {
						//conference 3001-example-domain.org energy 103
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
						$result_array = explode("=",$switch_result);
						$tmp_value = $result_array[1];
						if ($direction == "up") { $tmp_value = $tmp_value + 100; }
						if ($direction == "down") { $tmp_value = $tmp_value - 100; }
						//echo "energy $tmp_value<br />\n";
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd.' '.$tmp_value);
					}
					elseif ($data == "volume_in") {
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
						$result_array = explode("=",$switch_result);
						$tmp_value = $result_array[1];
						if ($direction == "up") { $tmp_value = $tmp_value + 1; }
						if ($direction == "down") { $tmp_value = $tmp_value - 1; }
						//echo "volume $tmp_value<br />\n";
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd.' '.$tmp_value);
					}
					elseif ($data == "volume_out") {
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
						$result_array = explode("=",$switch_result);
						$tmp_value = $result_array[1];
						if ($direction == "up") { $tmp_value = $tmp_value + 1; }
						if ($direction == "down") { $tmp_value = $tmp_value - 1; }
						//echo "volume $tmp_value<br />\n";
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd.' '.$tmp_value);
					}
					elseif ($data == "record") {
						$recording_dir = $_SESSION['switch']['recordings']['dir'].'/archive/'.date("Y").'/'.date("M").'/'.date("d");
						$switch_cmd .= $recording_dir."/".$uuid.".wav";
						if (!file_exists($recording_dir."/".$uuid.".wav")) {
							$switch_result = event_socket_request($fp, "api ".$switch_cmd);
						}
					}
					elseif ($data == "norecord") {
						//stop recording and rename the file
						$recording_dir = $_SESSION['switch']['recordings']['dir'].'/archive/'.date("Y").'/'.date("M").'/'.date("d");
						$switch_cmd .= $recording_dir."/".$uuid.".wav";
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}
					elseif ($data == "kick") {
						$switch_result = event_socket_request($fp, 'api uuid_kill '.$uuid);
					}
					elseif ($data == "kick all") {
						//$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
						conference_end($fp, $name);
					}
					elseif ($data == "mute" || $data == "unmute" || $data == "mute non_moderator" || $data == "unmute non_moderator") {
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}
					elseif ($data == "deaf" || $data == "undeaf" ) {
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}
					elseif ($data == "lock" || $data == "unlock" ) {
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					}
					//echo "command: ".$switch_cmd." result: ".$switch_result."<br\n>";
				}
		}
	}

?>