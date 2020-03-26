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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('dialplan_add')
		|| permission_exists('inbound_route_add')
		|| permission_exists('outbound_route_add')
		|| permission_exists('time_condition_add')) {
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
	$sip_profile_uuid = $_REQUEST["id"];
	$sip_profile_name = $_REQUEST["name"];

if (is_uuid($sip_profile_uuid) && $sip_profile_name != '') {

	//get the sip profile data
		if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
			$sql = "select sip_profile_hostname, sip_profile_enabled, sip_profile_description from v_sip_profiles ";
			$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
			$parameters['sip_profile_uuid'] = $sip_profile_uuid;
			$database = new database;
			$row = $database->select($sql, $parameters, 'row');
			$sip_profile_hostname = $row['sip_profile_hostname'];
			$sip_profile_enabled = $row['sip_profile_enabled'];
			$sip_profile_description = $row['sip_profile_description'];
			unset($sql, $parameters);
		}

	//copy the sip profile
		$sip_profile_uuid_new = uuid();
		$array['sip_profiles'][0]['sip_profile_uuid'] = $sip_profile_uuid_new;
		$array['sip_profiles'][0]['sip_profile_name'] = $sip_profile_name;
		$array['sip_profiles'][0]['sip_profile_hostname'] = $sip_profile_hostname;
		$array['sip_profiles'][0]['sip_profile_enabled'] = $sip_profile_enabled;
		$array['sip_profiles'][0]['sip_profile_description'] = $sip_profile_description.' ('.$text['label-copy'].')';

	//get the the sip profile settings
		$sql = "select * from v_sip_profile_domains ";
		$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
		$parameters['sip_profile_uuid'] = $sip_profile_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $x => &$row) {
				$array['sip_profile_domains'][$x]['sip_profile_domain_uuid'] = uuid();
				$array['sip_profile_domains'][$x]['sip_profile_uuid'] = $sip_profile_uuid_new;
				$array['sip_profile_domains'][$x]['sip_profile_domain_name'] = $row["sip_profile_domain_name"];
				$array['sip_profile_domains'][$x]['sip_profile_domain_alias'] = $row["sip_profile_domain_alias"];
				$array['sip_profile_domains'][$x]['sip_profile_domain_parse'] = $row["sip_profile_domain_parse"];
			}
		}
		unset($sql, $parameters, $result, $row);

	//get the the sip profile settings
		$sql = "select * from v_sip_profile_settings ";
		$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
		$parameters['sip_profile_uuid'] = $sip_profile_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (is_array($result) && @sizeof($result) != 0) {
			foreach ($result as $x => &$row) {
				$array['sip_profile_settings'][$x]['sip_profile_setting_uuid'] = uuid();
				$array['sip_profile_settings'][$x]['sip_profile_uuid'] = $sip_profile_uuid_new;
				$array['sip_profile_settings'][$x]['sip_profile_setting_name'] = $row["sip_profile_setting_name"];
				$array['sip_profile_settings'][$x]['sip_profile_setting_value'] = $row["sip_profile_setting_value"];
				$array['sip_profile_settings'][$x]['sip_profile_setting_enabled'] = $row["sip_profile_setting_enabled"];
				$array['sip_profile_settings'][$x]['sip_profile_setting_description'] = $row["sip_profile_setting_description"];
			}
		}
		unset($sql, $parameters, $result, $row);

	//execute insert
		$database = new database;
		$database->app_name = 'sip_profiles';
		$database->app_uuid = '159a8da8-0e8c-a26b-6d5b-19c532b6d470';
		$database->save($array);
		unset($array);

	//save the sip profile xml
		save_sip_profile_xml();

	//apply settings reminder
		$_SESSION["reload_xml"] = true;

	//set message
		message::add($text['message-copy']);

}

//redirect the user
	header("Location: sip_profiles.php");
	exit;

?>