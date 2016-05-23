<?php
/* $Id$ */
/*
	call.php
	Copyright (C) 2008, 2009 Mark J Crane
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
	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>

*/
include "root.php";

//luarun /var/www/fusionpbx/app/sms/sms.lua TO FROM 'BODY'

$debug = false;

require_once "resources/require.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$data = json_decode(file_get_contents("php://input"));
	
	if ($debug) {
		error_log('DATA: ' .  print_r($data, true));
	}
	//create the even socket connection and send the event socket command
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
		if (!$fp) {
			//error message
			echo "<div align='center'><strong>Connection to Event Socket failed.</strong></div>";
		}
		else {

				$to = preg_replace('/(^[1])/','', $data->to);
				if ($debug) {
					error_log("TO: " . print_r($to,true));
				}
				
				$sql = "select domain_name, ";
				$sql .= "dialplan_detail_data, ";
				$sql .= "v_domains.domain_uuid as domain_uuid ";
				$sql .= "from v_destinations, ";
				$sql .= "v_dialplan_details, ";
				$sql .= "v_domains ";
				$sql .= "where v_destinations.dialplan_uuid = v_dialplan_details.dialplan_uuid ";
				$sql .= "and v_destinations.domain_uuid = v_domains.domain_uuid";
				$sql .= " and destination_number like '" . $to . "' and dialplan_detail_type = 'transfer'";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					$domain_name = $row["domain_name"];
					preg_match('/(\d{2,7})/',$row["dialplan_detail_data"],$match);
					$domain_uuid = $row["domain_uuid"];
					break; //limit to 1 row
				}
				unset ($prep_statement);

				if ($debug) {
					error_log("MATCH: " . print_r($match,true));
					error_log("DOMAIN_NAME: " . print_r($domain_name,true));
				}

				$sql = "select destination_number ";
				$sql .= "from v_ring_groups, v_ring_group_destinations ";
				$sql .= "where v_ring_groups.ring_group_uuid = v_ring_group_destinations.ring_group_uuid ";
				$sql .= "and ring_group_extension = '" . $match[0] . "' ";
				$sql .= "and v_ring_groups.domain_uuid = '" . $domain_uuid . "'";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				if (count($result)) {
					foreach ($result as &$row) {
						$switch_cmd = "api luarun app.lua sms inbound ";
						$switch_cmd .= $row['destination_number'] . "@" . $domain_name;
						$switch_cmd .= " " . $data->from . " '" . $data->body . "'";
						if ($debug) {
							error_log(print_r($switch_cmd,true));
						}
						$result2 = trim(event_socket_request($fp, $switch_cmd));
					}
				} else {
					$switch_cmd = "api luarun app.lua sms inbound " . $match[0] . "@" . $domain_name . " " . $data->from . " '" . $data->body . "'";
					if ($debug) {
						error_log(print_r($switch_cmd,true));
					}
					$result2 = trim(event_socket_request($fp, $switch_cmd));
				}
				if ($debug) {
					error_log("RESULT: " . print_r($result2,true));
				}
				
				unset ($prep_statement);
				
		}

}
die();

?>