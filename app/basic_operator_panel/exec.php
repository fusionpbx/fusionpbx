<?php
/* $Id$ */
/*
	v_exec.php
	Copyright (C) 2008-2019 Mark J Crane
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
	if (permission_exists('operator_panel_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//authorized referrer
// 	if(stristr($_SERVER["HTTP_REFERER"], '/index.php') === false) {
// 		if(stristr($_SERVER["HTTP_REFERER"], '/index_inc.php') === false) {
// 			echo " access denied";
// 			exit;
// 		}
// 	}

//process the requests
if (count($_GET) > 0) {
	//set the variables
		$switch_cmd = trim($_GET["cmd"]);
		$action = trim($_GET["action"]);
		$data = trim($_GET["data"]);
		$direction = trim($_GET["direction"]);

	//setup the event socket connection
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

	//allow specific commands
		if (strlen($switch_cmd) > 0) {
			$api_cmd = '';
			$uuid_pattern = '/[^-A-Fa-f0-9]/';
			$num_pattern = '/[^-A-Za-z0-9()*#]/';

			if ($switch_cmd == 'originate') {
				$source = preg_replace($num_pattern,'',$_GET['source']);
				$destination = preg_replace($num_pattern,'',$_GET['destination']);
				$api_cmd = 'bgapi originate {sip_auto_answer=true,origination_caller_id_number=' . $source . ',sip_h_Call-Info=_undef_}user/' . $source . '@' . $_SESSION['domain_name'] . ' ' . $destination . ' XML ' . trim($_SESSION['user_context']);
			} elseif ($switch_cmd == 'uuid_record') {
				$uuid = preg_replace($uuid_pattern,'',$_GET['uuid']);
				$api_cmd = 'uuid_record ' . $uuid . ' start ' . $_SESSION['switch']['recordings']['dir'] . '/' . $_SESSION['domain_name'] . '/archive/' . date('Y/M/d') . '/' . $uuid . '.wav';
			} elseif ($switch_cmd == 'uuid_transfer') {
				$uuid = preg_replace($uuid_pattern,'',$_GET['uuid']);
				$destination = preg_replace($num_pattern,'',$_GET['destination']);
				$api_cmd = 'uuid_transfer ' . $uuid . ' ' . $destination . ' XML ' . trim($_SESSION['user_context']);
			} elseif ($switch_cmd == 'uuid_eavesdrop') {
				$chan_uuid = preg_replace($uuid_pattern,'',$_GET['chan_uuid']);
				$ext = preg_replace($num_pattern,'',$_GET['ext']);
				$destination = preg_replace($num_pattern,'',$_GET['destination']);

				$language = new text;
				$text = $language->get();

				$api_cmd = 'bgapi originate {origination_caller_id_name=' . $text['label-eavesdrop'] . ',origination_caller_id_number=' . $ext . '}user/' . $destination . '@' . $_SESSION['domain_name'] . ' &eavesdrop(' . $chan_uuid . ')';
			} elseif ($switch_cmd == 'uuid_kill') {
				$call_id = preg_replace($uuid_pattern,'',$_GET['call_id']);
				$api_cmd = 'uuid_kill ' . $call_id;
			} else {
				echo 'access denied';
				return;
			}

			//run the command
			$switch_result = event_socket_request($fp, 'api '.$api_cmd);

			/*
			//record stop
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
			*/
		}
}

?>
