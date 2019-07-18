<?php
/* $Id$ */
/*
	v_exec.php
	Copyright (C) 2008 - 2019 Mark J Crane
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

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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

//authorized commands
	if ($_GET["command"] == 'hangup') {
		if (is_uuid($_GET["uuid"])) {
			//setup the event socket connection
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

			//run the command
			$switch_result = event_socket_request($fp, 'api uuid_kill '.$_GET["uuid"]);
		}
	}
	else {
		//not found. this command is not authorized
		echo "access denied";
		exit;
	}

?>
