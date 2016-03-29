<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

if ($domains_processed == 1) {
	//get the data from the database
		$sql = "select * from v_settings ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		if ($prep_statement) {
			$row = $prep_statement->fetch(PDO::FETCH_NAMED);
			unset($prep_statement);
		}

	//check the row count
		if (strlen($row['event_socket_ip_address']) == 0) {
			//add default settings
			$event_socket_ip_address = "127.0.0.1";
			$event_socket_port = "8021";
			$event_socket_password = "ClueCon";
			$xml_rpc_http_port = "8787";
			$xml_rpc_auth_realm = "freeswitch";
			$xml_rpc_auth_user = "freeswitch";
			$xml_rpc_auth_pass = "works";
			$mod_shout_decoder = "";
			$mod_shout_volume = "0.3";

			$sql = "insert into v_settings ";
			$sql .= "(";
			$sql .= "setting_uuid, ";
			$sql .= "event_socket_ip_address, ";
			$sql .= "event_socket_port, ";
			$sql .= "event_socket_password, ";
			$sql .= "xml_rpc_http_port, ";
			$sql .= "xml_rpc_auth_realm, ";
			$sql .= "xml_rpc_auth_user, ";
			$sql .= "xml_rpc_auth_pass, ";
			$sql .= "mod_shout_decoder, ";
			$sql .= "mod_shout_volume ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'".uuid()."', ";
			$sql .= "'$event_socket_ip_address', ";
			$sql .= "'$event_socket_port', ";
			$sql .= "'$event_socket_password', ";
			$sql .= "'$xml_rpc_http_port', ";
			$sql .= "'$xml_rpc_auth_realm', ";
			$sql .= "'$xml_rpc_auth_user', ";
			$sql .= "'$xml_rpc_auth_pass', ";
			$sql .= "'$mod_shout_decoder', ";
			$sql .= "'$mod_shout_volume' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
		}

		if(isset($_SESSION['event_socket_ip_address'])) {
			$event_socket_ip_address = $_SESSION['event_socket_ip_address'];
			if(isset($_SESSION['event_socket_port'])) { $event_socket_port = $_SESSION['event_socket_port']; }
			if(isset($_SESSION['event_socket_password'])) { $event_socket_password = $_SESSION['event_socket_password']; }
			$sql = "update v_settings set ";
			$sql .= "event_socket_ip_address = '$event_socket_ip_address', ";
			$sql .= "event_socket_port = '$event_socket_port', ";
			$sql .= "event_socket_password = '$event_socket_password' ";
			$db->exec(check_sql($sql));
			unset($sql);
		}
}

?>