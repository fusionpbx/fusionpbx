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
	Portions created by the Initial Developer are Copyright (C) 2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('sip_profile_domain_delete')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the id
	$sip_profile_domain_uuid = $_GET["id"];

//delete the data
	if (is_uuid($sip_profile_domain_uuid)) {

		//get the details of the sip profile
			$sql = "select sip_profile_uuid ";
			$sql .= "from v_sip_profile_domains ";
			$sql .= "where sip_profile_domain_uuid = :sip_profile_domain_uuid ";
			$parameters['sip_profile_domain_uuid'] = $sip_profile_domain_uuid;
			$database = new database;
			$sip_profile_uuid = $database->select($sql, $parameters, 'column');

		//build array
			$array['sip_profile_domains'][0]['sip_profile_domain_uuid'] = $sip_profile_domain_uuid;

		//execute delete
			$database = new database;
			$database->app_name = 'sip_profiles';
			$database->app_uuid = '159a8da8-0e8c-a26b-6d5b-19c532b6d470';
			$database->delete($array);
			unset($array);

		//set message
			message::add($text['message-delete']);

		//redirect the user
			header('Location: sip_profile_edit.php?id='.$sip_profile_uuid);
			exit;
	}

//default redirect
	header('Location: sip_profiles.php');
	exit;

?>