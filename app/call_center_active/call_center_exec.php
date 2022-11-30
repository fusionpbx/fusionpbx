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
if (permission_exists('call_center_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//http get variables set to php variables
	if (count($_GET)>0) {
		$switch_cmd = trim($_GET["cmd"]);
		$action = trim(check_str($_GET["action"]));
		$data = trim(check_str($_GET["data"]));
		$username = trim(check_str($_GET["username"]));
	}

//authorized commands
	if (stristr($switch_cmd, 'user_status') == true) {
		//authorized;
	} elseif (stristr($switch_cmd, 'callcenter_config') == true) {
		//authorized;
	} elseif (stristr($switch_cmd, 'eavesdrop') == true) {
		//authorized;
	} elseif (stristr($switch_cmd, 'bridge') == true) {
		//authorized;
	} elseif (stristr($switch_cmd, 'uuid_transfer') == true) {
		//authorized;
	} else {
		//not found. this command is not authorized
		echo "access denied";
		exit;
	}

//set the username
	if (if_group("admin") || if_group("superadmin")) {
		//use the username that was provided
	}
	else {
		$username = $_SESSION['username'];
	}

//get to php variables
	if (count($_GET)>0) {
		if ($action == "user_status") {
			$user_status = $data;
			$sql  = "update v_users set ";
			$sql .= "user_status = '".trim($user_status, "'")."' ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and username = '".$username."' ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
		}

		//fs cmd
		if (strlen($switch_cmd) > 0) {
			//setup the event socket connection
				$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			//ensure the connection exists
				if ($fp) {
					//send the command
						$switch_result = event_socket_request($fp, 'api '.$switch_cmd);
					//set the user state
						$cmd = "api callcenter_config agent set state ".$username."@".$_SESSION['domain_name']." Waiting";
						$response = event_socket_request($fp, $cmd);
				}
		}
	}

?>
