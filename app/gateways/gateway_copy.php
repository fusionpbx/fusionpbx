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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('gateway_add')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the http get/post variable(s) to a php variable
	if (is_uuid($_REQUEST["id"])) {
		$gateway_uuid = $_REQUEST["id"];

		//get the data
			$sql = "select * from v_gateways ";
			$sql .= "where gateway_uuid = :gateway_uuid ";
			$parameters['gateway_uuid'] = $gateway_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			if (is_array($row) && @sizeof($row) != 0) {
				$domain_uuid = $row["domain_uuid"];
				$gateway = $row["gateway"];
				$username = $row["username"];
				$password = $row["password"];
				$auth_username = $row["auth_username"];
				$realm = $row["realm"];
				$from_user = $row["from_user"];
				$from_domain = $row["from_domain"];
				$proxy = $row["proxy"];
				$register_proxy = $row["register_proxy"];
				$outbound_proxy = $row["outbound_proxy"];
				$expire_seconds = $row["expire_seconds"];
				$register = $row["register"];
				$register_transport = $row["register_transport"];
				$contact_params = $row["contact_params"];
				$retry_seconds = $row["retry_seconds"];
				$extension = $row["extension"];
				$codec_prefs = $row["codec_prefs"];
				$ping = $row["ping"];
				$channels = $row["channels"];
				$caller_id_in_from = $row["caller_id_in_from"];
				$supress_cng = $row["supress_cng"];
				$sip_cid_type = $row["sip_cid_type"];
				$extension_in_contact = $row["extension_in_contact"];
				$effective_caller_id_name = $row["effective_caller_id_name"];
				$effective_caller_id_number = $row["effective_caller_id_number"];
				$outbound_caller_id_name = $row["outbound_caller_id_name"];
				$outbound_caller_id_number = $row["outbound_caller_id_number"];
				$context = $row["context"];
				$profile = $row["profile"];
				$enabled = $row["enabled"];
				$description = $row["description"]." (".$text['label-copy'].")";
			}
			unset($sql, $parameters, $row);

		//set defaults
			if (strlen($expire_seconds) == 0) {
				$expire_seconds = '800';
			}
			if (strlen($retry_seconds) == 0) {
				$retry_seconds = '30';
			}

		//copy the gateways
			$gateway_uuid = uuid();
			$array['gateways'][0]['domain_uuid'] = is_uuid($domain_uuid) ? $domain_uuid : null;
			$array['gateways'][0]['gateway_uuid'] = $gateway_uuid;
			$array['gateways'][0]['gateway'] = $gateway;
			$array['gateways'][0]['username'] = $username;
			$array['gateways'][0]['password'] = $password;
			$array['gateways'][0]['auth_username'] = $auth_username;
			$array['gateways'][0]['realm'] = $realm;
			$array['gateways'][0]['from_user'] = $from_user;
			$array['gateways'][0]['from_domain'] = $from_domain;
			$array['gateways'][0]['proxy'] = $proxy;
			$array['gateways'][0]['register_proxy'] = $register_proxy;
			$array['gateways'][0]['outbound_proxy'] = $outbound_proxy;
			$array['gateways'][0]['expire_seconds'] = $expire_seconds;
			$array['gateways'][0]['register'] = $register;
			$array['gateways'][0]['register_transport'] = $register_transport;
			$array['gateways'][0]['contact_params'] = $contact_params;
			$array['gateways'][0]['retry_seconds'] = $retry_seconds;
			$array['gateways'][0]['extension'] = $extension;
			$array['gateways'][0]['codec_prefs'] = $codec_prefs;
			$array['gateways'][0]['ping'] = $ping;
			//$array['gateways'][0]['channels'] = $channels;
			$array['gateways'][0]['caller_id_in_from'] = $caller_id_in_from;
			$array['gateways'][0]['supress_cng'] = $supress_cng;
			$array['gateways'][0]['sip_cid_type'] = $sip_cid_type;
			$array['gateways'][0]['extension_in_contact'] = $extension_in_contact;
			$array['gateways'][0]['context'] = $context;
			$array['gateways'][0]['profile'] = $profile;
			$array['gateways'][0]['enabled'] = $enabled;
			$array['gateways'][0]['description'] = $description;

			$database = new database;
			$database->app_name = 'gateways';
			$database->app_uuid = '297ab33e-2c2f-8196-552c-f3567d2caaf8';
			$database->save($array);
			unset($array);

		//add new gateway to session variable
			if ($enabled == 'true') {
				$_SESSION['gateways'][$gateway_uuid] = $gateway;
			}

		//synchronize the xml config
			save_gateway_xml();

		//clear the cache
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			$hostname = trim(event_socket_request($fp, 'api switchname'));
			$cache = new cache;
			$cache->delete("configuration:sofia.conf:".$hostname);

		//set message
			message::add($text['message-copy']);
	}

//redirect the user
	header("Location: gateways.php");
	return;

?>
