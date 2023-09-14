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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//process this only one time
if ($domains_processed == 1) {

	//includes files
	require 'app/switch/resources/classes/scripts.php';
	$obj = new scripts;
	$obj->copy_files();

	//move the default settings
	$database = new database;
	if ($database->table_exists('v_settings')) {

		$sql = "select * from v_settings ";
		$database = new database;
		$row = $database->select($sql, null, 'row');
		if (!empty($row)) {

			//initialize the setting object
			$setting = new settings(["category" => "switch"]);

			//save the default settings
			if ($setting->get('switch', 'event_socket_ip_address') !== $row["event_socket_ip_address"]) {
				$array['setting_uuid'] = "7ca40076-b66b-4fe5-90e8-ceb4fe27391c";
				$array['setting_category'] = "switch";
				$array['setting_subcategory'] = "event_socket_ip_address";
				$array['setting_name'] = "text";
				$array['setting_value'] = $row["event_socket_ip_address"];
				$array['setting_enabled'] = "true";
				$array['setting_description'] = "";
				$setting->set($array);
			}
			if ($setting->get('switch', 'event_socket_port') !== $row["event_socket_port"]) {
				$array['setting_uuid'] = "570804f2-65b9-423a-9758-f09c1b4b1482";
				$array['setting_category'] = "switch";
				$array['setting_subcategory'] = "event_socket_port";
				$array['setting_name'] = "numeric";
				$array['setting_value'] = $row["event_socket_port"];
				$array['setting_enabled'] = "true";
				$array['setting_description'] = "";
				$setting->set($array);
			}
			if ($setting->get('switch', 'event_socket_password') !== $row["event_socket_password"]) {
				$array['setting_uuid'] = "ecd4923b-8396-4d83-b52d-809fe179f93a";
				$array['setting_category'] = "switch";
				$array['setting_subcategory'] = "event_socket_password";
				$array['setting_name'] = "text";
				$array['setting_value'] = $row["event_socket_password"];
				$array['setting_enabled'] = "true";
				$array['setting_description'] = "";
				$setting->set($array);
			}
			if ($setting->get('switch', 'event_socket_acl') !== $row["event_socket_acl"]) {
				$array['setting_uuid'] = "2541e9e5-ac09-46f2-8d22-ebc3756b22b7";
				$array['setting_category'] = "switch";
				$array['setting_subcategory'] = "event_socket_acl";
				$array['setting_name'] = "text";
				$array['setting_value'] = $row["event_socket_acl"];
				$array['setting_enabled'] = (empty($row["event_socket_acl"])) ? "false" : "true";
				$array['setting_description'] = "";
				$setting->set($array);
			}
			if ($setting->get('switch', 'xml_rpc_http_port') !== $row["xml_rpc_http_port"]) {
				$array['setting_uuid'] = "90c7638c-8ecc-4210-bd7b-c301aef3ae7a";
				$array['setting_category'] = "switch";
				$array['setting_subcategory'] = "xml_rpc_http_port";
				$array['setting_name'] = "numeric";
				$array['setting_value'] = $row["xml_rpc_http_port"];
				$array['setting_enabled'] = "true";
				$array['setting_description'] = "";
				$setting->set($array);
			}
			if ($setting->get('switch', 'xml_rpc_auth_realm') !== $row["xml_rpc_auth_realm"]) {
				$array['setting_uuid'] = "d7a91830-6faf-4b26-80e6-c9f9e1898425";
				$array['setting_category'] = "switch";
				$array['setting_subcategory'] = "xml_rpc_auth_realm";
				$array['setting_name'] = "text";
				$array['setting_value'] = $row["xml_rpc_auth_realm"];
				$array['setting_enabled'] = "true";
				$array['setting_description'] = "";
				$setting->set($array);
			}
			if ($setting->get('switch', 'xml_rpc_auth_user') !== $row["xml_rpc_auth_user"]) {
				$array['setting_uuid'] = "4a1a4b4c-1a5b-45bb-8393-36c1cb42c875";
				$array['setting_category'] = "switch";
				$array['setting_subcategory'] = "xml_rpc_auth_user";
				$array['setting_name'] = "text";
				$array['setting_value'] = $row["xml_rpc_auth_user"];
				$array['setting_enabled'] = "true";
				$array['setting_description'] = "";
				$setting->set($array);
			}
			if ($setting->get('switch', 'xml_rpc_auth_pass') !== $row["xml_rpc_auth_pass"]) {
				$array['setting_uuid'] = "8b51edfe-13f7-4ca3-989a-516072bec0b7";
				$array['setting_category'] = "switch";
				$array['setting_subcategory'] = "xml_rpc_auth_pass";
				$array['setting_name'] = "text";
				$array['setting_value'] = $row["xml_rpc_auth_pass"];
				$array['setting_enabled'] = "true";
				$array['setting_description'] = "";
				$setting->set($array);
			}
	
		}
		unset($sql, $row);
	}

}

?>
