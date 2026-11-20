<?php
/* $Id$ */
/*

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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>

*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('conference_active_view')) {
		echo "access denied";
		exit;
	}

//validate the token
	$token = new token;
	if (!$token->validate($_SERVER['PHP_SELF'])) {
		echo "access denied";
		exit;
	}

//get the http values and set them as php variables
	if (count($_GET) > 0) {
		$cmd = trim($_GET["cmd"]);
		$name = trim($_GET["name"]);
		$uuid = trim($_GET["uuid"] ?? '');
		$data = trim($_GET["data"]);
		$id = trim($_GET["id"] ?? '');
		$direction = trim($_GET["direction"] ?? '');
	}

//authorized commands
	if ($cmd == "conference") {
		//authorized;
	} else {
		//not found. this command is not authorized
		echo "access denied";
		exit;
	}

//get the conference name
	if (isset($name) && !empty($name)) {
		$name_array = explode('@', $name);
		$name = $name_array[0];
	}

//validate the name
	if (!is_uuid($name)) {
		$sql = "select conference_extension ";
		$sql .= "from v_conferences ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and conference_extension = :conference_extension ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['conference_extension'] = $name;
		$name = $database->select($sql, $parameters, 'column');
		unset ($parameters, $sql);
	}
	elseif (!if_group('superadmin')) {
		//verify the conference belongs to the current domain, a uuid name is a conference center room
		$sql = "select conference_room_uuid ";
		$sql .= "from v_conference_rooms ";
		$sql .= "where conference_room_uuid = :conference_room_uuid ";
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['conference_room_uuid'] = $name;
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$row = $database->select($sql, $parameters, 'row');
		if (empty($row)) {
			echo "access denied";
			exit;
		}
		unset ($parameters, $sql, $row);
	}

//validate the conference name to prevent command injection into the switch commands
	if (!preg_match('/^[0-9A-Za-z\-_.]+$/D', (string) ($name ?? ''))) {
		echo "access denied";
		exit;
	}

//append the domain name to the conference name
	$name = $name .'@'.$_SESSION['domain_name'];

//validate the uuid
	if (!is_uuid($uuid)) {
		$uuid = null;
	}

//limit the commands to channels of the current domain, superadmin is exempt
	$domain_name = $_SESSION['domain_name'] ?? '';
	$channel_in_domain = function (?string $uuid) use ($domain_name): bool {
		if (if_group('superadmin')) {
			return true;
		}
		if ($uuid === '' || !is_uuid($uuid)) {
			return false;
		}

		//get the domain of the channel from the switch
		$channel_domain = trim((string) event_socket::api('uuid_getvar ' . $uuid . ' domain_name'));
		if ($channel_domain === '' || $channel_domain === '_undef_' || stripos($channel_domain, '-ERR') !== false) {
			//fall back to the presence id which is user@domain
			$presence_id = str_replace('%40', '@', trim((string) event_socket::api('uuid_getvar ' . $uuid . ' presence_id')));
			$presence = explode('@', $presence_id);
			$channel_domain = $presence[1] ?? '';
		}

		//compare the domain, a channel without a known domain is not allowed
		return $channel_domain !== '' && $channel_domain === $domain_name;
	};

//validate direction
	switch ($direction) {
		case "up":
			break;
		case "down":
			break;
		default:
			$direction = null;
	}

//validate the data
	switch ($data) {
		case "energy":
			break;
		case "volume_in":
			break;
		case "volume_out":
			break;
		case "record":
			break;
		case "norecord":
			break;
		case "kick":
			break;
		case "kick all":
			break;
		case "mute":
			break;
		case "unmute":
			break;
		case "mute non_moderator":
			break;
		case "unmute non_moderator":
			break;
		case "deaf":
			break;
		case "undeaf":
			break;
		case "lock":
			break;
		case "unlock":
			break;
		default:
			$data = null;
	}

//require the permission for the requested action, the view permission alone does not authorize changes
	$data_permissions = [
		"lock" => "conference_interactive_lock",
		"unlock" => "conference_interactive_lock",
		"mute" => "conference_interactive_mute",
		"unmute" => "conference_interactive_mute",
		"mute non_moderator" => "conference_interactive_mute",
		"unmute non_moderator" => "conference_interactive_mute",
		"deaf" => "conference_interactive_deaf",
		"undeaf" => "conference_interactive_deaf",
		"kick" => "conference_interactive_kick",
		"kick all" => "conference_interactive_kick",
		"energy" => "conference_interactive_energy",
		"volume_in" => "conference_interactive_volume",
		"volume_out" => "conference_interactive_gain",
	];
	if (!empty($data)) {
		if (!isset($data_permissions[$data]) || !permission_exists($data_permissions[$data])) {
			echo "access denied";
			exit;
		}
	}

//validate the numeric id
	if (!is_numeric($id)) {
		$id = null;
		$direction = null;
	}

//define an alternative kick all
	/**
	 * Ends a conference by killing all its members and destroying the session.
	 *
	 * @param string $name The name of the conference to end.
	 *
	 * @return void
	 */
	function conference_end($name) {
		$switch_cmd = "conference '".$name."' xml_list";
		$xml_str = trim(event_socket::api($switch_cmd));
		try {
			$xml = new SimpleXMLElement($xml_str);
		}
		catch(Exception $e) {
			//echo $e->getMessage();
		}
		if (!isset($xml)) {
			return;
		}
		$session_uuid = $xml->conference['uuid'];
		$x = 0;
		foreach ($xml->conference->members->member as $row) {
			$uuid = (string)$row->uuid;
			if (is_uuid($uuid)) {
				$switch_result = event_socket::api("uuid_kill $uuid");
			}
			if ($x < 1) {
				usleep(500000); //500000 = 0.5 seconds
			}
			else {
				usleep(10000);  //1000000 = 0.01 seconds
			}
			$x++;
		}
		unset($uuid);
	}



//execute the command
	if (count($_GET) > 0) {
		if (!empty($cmd)) {
			//prepare the switch cmd
				$switch_cmd = $cmd . " ";
				$switch_cmd .= $name . " ";
				$switch_cmd .= $data . " ";
				if ($id && !empty($id)) {
					$switch_cmd .= " ".$id;
				}

			//connect to event socket
				$esl = event_socket::create();
				if ($esl->is_connected()) {
					if ($data == "energy") {
						//conference 3001-example-domain.org energy 103
						$switch_result = event_socket::api($switch_cmd);
						$result_array = explode("=",$switch_result);
						$tmp_value = $result_array[1];
						if ($direction == "up") { $tmp_value = $tmp_value + 100; }
						if ($direction == "down") { $tmp_value = $tmp_value - 100; }
						//echo "energy $tmp_value<br />\n";
						$switch_result = event_socket::api("$switch_cmd $tmp_value");
					}
					elseif ($data == "volume_in") {
						$switch_result = event_socket::api($switch_cmd);
						$result_array = explode("=",$switch_result);
						$tmp_value = $result_array[1];
						if ($direction == "up") { $tmp_value = $tmp_value + 1; }
						if ($direction == "down") { $tmp_value = $tmp_value - 1; }
						//echo "volume $tmp_value<br />\n";
						$switch_result = event_socket::api($switch_cmd.' '.$tmp_value);
					}
					elseif ($data == "volume_out") {
						$switch_result = event_socket::api($switch_cmd);
						$result_array = explode("=",$switch_result);
						$tmp_value = $result_array[1];
						if ($direction == "up") { $tmp_value = $tmp_value + 1; }
						if ($direction == "down") { $tmp_value = $tmp_value - 1; }
						//echo "volume $tmp_value<br />\n";
						$switch_result = event_socket::api($switch_cmd.' '.$tmp_value);
					}
					elseif ($data == "record") {
						$recording_dir = $settings->get('switch', 'recordings').'/'.$_SESSION['domain_name'].'/archive/'.date("Y").'/'.date("M").'/'.date("d");
						$switch_cmd .= $recording_dir."/{$uuid}.wav";
						if (!file_exists($switch_cmd)) {
							$switch_result = event_socket::api($switch_cmd);
						}
					}
					elseif ($data == "norecord") {
						//stop recording and rename the file
						$recording_dir = $settings->get('switch', 'recordings').'/'.$_SESSION['domain_name'].'/archive/'.date("Y").'/'.date("M").'/'.date("d");
						$switch_cmd .= $recording_dir."/".$uuid.".wav";
						$switch_result = event_socket::api($switch_cmd);
					}
					elseif ($data == "kick") {
						if (!$channel_in_domain($uuid)) {
							echo "access denied";
							exit;
						}
						$switch_result = event_socket::api("uuid_kill $uuid");
					}
					elseif ($data == "kick all") {
						//$switch_result = event_socket::api($switch_cmd);
						conference_end($name);
					}
					elseif ($data == "mute" || $data == "unmute" || $data == "mute non_moderator" || $data == "unmute non_moderator") {
						$switch_result = event_socket::api($switch_cmd);
						//the channel uuid is optional, update it only when it belongs to the current domain
						if ($channel_in_domain($uuid)) {
							$switch_cmd = "uuid_setvar ".$uuid. " hand_raised false";
							event_socket::api($switch_cmd);
						}
					}
					elseif ($data == "deaf" || $data == "undeaf" ) {
						$switch_result = event_socket::api($switch_cmd);
					}
					elseif ($data == "lock" || $data == "unlock" ) {
						$switch_result = event_socket::api($switch_cmd);
					}
					//echo "command: ".$switch_cmd." result: ".$switch_result."<br\n>";
				}
		}
	}

?>
