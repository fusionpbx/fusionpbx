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
		$sql = "select event_socket_ip_address from v_settings ";
		$database = new database;
		$event_socket_ip_address = $database->select($sql, null, 'column');

	//check the row count
		if (strlen($event_socket_ip_address) == 0) {
			//add default settings
				$event_socket_ip_address = "127.0.0.1";
				$event_socket_port = "8021";
				$event_socket_password = "ClueCon";
				$xml_rpc_http_port = "8080";
				$xml_rpc_auth_realm = "freeswitch";
				$xml_rpc_auth_user = "freeswitch";
				$xml_rpc_auth_pass = "works";
				$mod_shout_decoder = "";
				$mod_shout_volume = "0.3";
			//build array
				$array['settings'][0]['setting_uuid'] = uuid();
				$array['settings'][0]['event_socket_ip_address'] = $event_socket_ip_address;
				$array['settings'][0]['event_socket_port'] = $event_socket_port;
				$array['settings'][0]['event_socket_password'] = $event_socket_password;
				$array['settings'][0]['xml_rpc_http_port'] = $xml_rpc_http_port;
				$array['settings'][0]['xml_rpc_auth_realm'] = $xml_rpc_auth_realm;
				$array['settings'][0]['xml_rpc_auth_user'] = $xml_rpc_auth_user;
				$array['settings'][0]['xml_rpc_auth_pass'] = $xml_rpc_auth_pass;
				$array['settings'][0]['mod_shout_decoder'] = $mod_shout_decoder;
				$array['settings'][0]['mod_shout_volume'] = $mod_shout_volume;
			//grant temporary permissions
				$p = new permissions;
				$p->add('setting_add', 'temp');
			//execute insert
				$database = new database;
				$database->app_name = 'settings';
				$database->app_uuid = 'b6b1b2e5-4ba5-044c-8a5c-18709a15eb60';
				$database->save($array);
				unset($array);
			//revoke temporary permissions
				$p->delete('setting_add', 'temp');
		}

		if (isset($_SESSION['event_socket_ip_address'])) {
			$event_socket_ip_address = $_SESSION['event_socket_ip_address'];
			if (isset($_SESSION['event_socket_port'])) { $event_socket_port = $_SESSION['event_socket_port']; }
			if (isset($_SESSION['event_socket_password'])) { $event_socket_password = $_SESSION['event_socket_password']; }
			//build array
				$array['settings'][0]['event_socket_ip_address'] = $event_socket_ip_address;
				$array['settings'][0]['event_socket_port'] = $event_socket_port;
				$array['settings'][0]['event_socket_password'] = $event_socket_password;
			//grant temporary permissions
				$p = new permissions;
				$p->add('setting_edit', 'temp');
			//execute update
				$database = new database;
				$database->app_name = 'settings';
				$database->app_uuid = 'b6b1b2e5-4ba5-044c-8a5c-18709a15eb60';
				$database->save($array);
				unset($array);
			//revoke temporary permissions
				$p->delete('setting_edit', 'temp');
		}
}

?>
