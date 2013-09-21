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
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('call_active_view') || permission_exists('extension_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//authorized referrer
	if(stristr($_SERVER["HTTP_REFERER"], '/calls_active_extensions.php') === false) {
		if(stristr($_SERVER["HTTP_REFERER"], '/calls_active.php') === false) {
			echo " access denied";
			exit;
		}
	}

//http get variables set to php variables
	if (count($_GET)>0) {
		$switch_cmd = trim(check_str($_GET["cmd"]));
		$action = trim(check_str($_GET["action"]));
		$data = trim(check_str($_GET["data"]));
		$direction = trim(check_str($_GET["direction"]));
		$username = $_SESSION['username'];
	}

//authorized commands
	if (stristr($switch_cmd, '&uuid=') == true) {
		//authorized;
	} elseif (stristr($switch_cmd, 'uuid_kill') == true) {
		//authorized;
	} elseif (stristr($switch_cmd, 'uuid_transfer') == true) {
		//authorized;
	} elseif (stristr($switch_cmd, 'uuid_record') == true) {
		//authorized;
	} elseif (stristr($action, 'user_status') == true) {
		//authorized;
	} elseif (stristr($action, 'callcenter_config') == true) {
		//authorized;
	} else {
		//not found. this command is not authorized
		echo "access denied";
		exit;
	}

if (count($_GET)>0) {

	//setup the event socket connection
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

	if (stristr($action, 'user_status') == true) {
		$user_status = $data;
		switch ($user_status) {
		case "Available":
			$user_status = "Available";
			//update the user state
			$cmd = "api callcenter_config agent set state ".$username."@".$_SESSION['domain_name']." Waiting";
			$response = event_socket_request($fp, $cmd);
			break;
		case "Available_On_Demand":
			$user_status = "Available (On Demand)";
			//update the user state
			$cmd = "api callcenter_config agent set state ".$username."@".$_SESSION['domain_name']." Waiting";
			$response = event_socket_request($fp, $cmd);
			break;
		case "Logged_Out":
			$user_status = "Logged Out";
			//update the user state
			$cmd = "api callcenter_config agent set state ".$username."@".$_SESSION['domain_name']." Waiting";
			$response = event_socket_request($fp, $cmd);
			break;
		case "On_Break":
			$user_status = "On Break";
			//update the user state
			$cmd = "api callcenter_config agent set state ".$username."@".$_SESSION['domain_name']." Waiting";
			$response = event_socket_request($fp, $cmd);
			break;
		case "Do_Not_Disturb":
			$user_status = "Do Not Disturb";
			//update the user state
			$cmd = "api callcenter_config agent set state ".$username."@".$_SESSION['domain_name']." Waiting";
			$response = event_socket_request($fp, $cmd);
			break;
		default:
			$user_status = "";
		}

		//update the v_users table with the status
			$sql  = "update v_users set ";
			$sql .= "user_status = '$user_status' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and username = '".$username."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();

		if (strlen($user_status) > 0) {
			//include the dnd class
				include "app/calls/resources/classes/do_not_disturb.php";
			//loop through the list of assigned extensions
				foreach ($_SESSION['user']['extension'] as &$row) {
					$extension = $row["user"];

					//set the default action
						if ($user_status == "Do Not Disturb") {
							$dnd_action = "add";
						}

					//hunt_group information used to determine if this is an add or an update
						$sql  = "select * from v_hunt_groups ";
						$sql .= "where domain_uuid = '$domain_uuid' ";
						$sql .= "and hunt_group_extension = '$extension' ";
						$prep_statement_2 = $db->prepare(check_sql($sql));
						$prep_statement_2->execute();
						$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
						foreach ($result2 as &$row2) {
							if ($row2["hunt_group_type"] == 'dnd') {
								$dnd_action = "update";
								$dnd_uuid = $row2["hunt_group_uuid"];
							}
						}
						unset ($prep_statement_2, $result, $row2);

					//add or update dnd
						$dnd = new do_not_disturb;
						$dnd->domain_uuid = $domain_uuid;
						$dnd->dnd_uuid = $dnd_uuid;
						$dnd->domain_name = $_SESSION['domain_name'];
						$dnd->extension = $extension;
						if ($user_status == "Do Not Disturb") {
							$dnd->enabled = "true";
						}
						else {
							//for other status disable dnd
							if ($dnd_action == "update") {
								$dnd->enabled = "false";
							}
						}
						//$dnd->debug = false;
						$dnd->set();
						unset($dnd);
				}
				unset ($prep_statement);
		}

		//synchronize the xml config
			save_dialplan_xml();

		//reloadxml
			$cmd = 'api reloadxml';
			$response = event_socket_request($fp, $cmd);

		//apply settings reminder
			$_SESSION["reload_xml"] = false;
	}

	//fs cmd
	if (strlen($switch_cmd) > 0) {

		//set the status so they are compatible with mod_callcenter
			$switch_cmd = str_replace("Available_On_Demand", "'Available (On Demand)'", $switch_cmd);
			$switch_cmd = str_replace("Logged_Out", "'Logged Out'", $switch_cmd);
			$switch_cmd = str_replace("On_Break", "'On Break'", $switch_cmd);
			$switch_cmd = str_replace("Do_Not_Disturb", "'Logged Out'", $switch_cmd);

		/*
		//if ($action == "energy") {
			//conference 3001-example.org energy 103
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
			$result_array = explode("=",$switch_result);
			$tmp_value = $result_array[1];
			//if ($direction == "up") { $tmp_value = $tmp_value + 100; }
			//if ($direction == "down") { $tmp_value = $tmp_value - 100; }
			//echo "energy $tmp_value<br />\n";
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd.' '.$tmp_value);
		//}
		if ($action == "volume_in") {
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
			$result_array = explode("=",$switch_result);
			$tmp_value = $result_array[1];
			if ($direction == "up") { $tmp_value = $tmp_value + 1; }
			if ($direction == "down") { $tmp_value = $tmp_value - 1; }
			//echo "volume $tmp_value<br />\n";
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd.' '.$tmp_value);
		}
		if ($action == "volume_out") {
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
			$result_array = explode("=",$switch_result);
			$tmp_value = $result_array[1];
			if ($direction == "up") { $tmp_value = $tmp_value + 1; }
			if ($direction == "down") { $tmp_value = $tmp_value - 1; }
			//echo "volume $tmp_value<br />\n";
			$switch_result = event_socket_request($fp, 'api '.$switch_cmd.' '.$tmp_value);
		}
		*/

		$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
		if ($action == "record") {
			if (trim($_GET["action2"]) == "stop") {
				$x=0;
				while (true) {
					if ($x > 0) {
						$dest_file = $_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d")."/".$_GET["uuid"]."_".$x.".wav";
					}
					else {
						$dest_file = $_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d")."/".$_GET["uuid"].".wav";
					}
					if (!file_exists($dest_file)) {
						rename($_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d")."/".$_GET["uuid"].".wav", $dest_file);
						break;
					}
					$x++;
				}
			}
		}
	}
}

?>