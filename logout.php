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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

//destroy session
	session_unset();
	session_destroy();

//check for login return preference
	if ($_SESSION["user_uuid"] != '') {
		if (isset($_SESSION['login']['destination_last']) && ($_SESSION['login']['destination_last']['boolean'] == 'true')) {
			if ($_SERVER['HTTP_REFERER'] != '') {
				//convert to relative path
					$referrer = substr($_SERVER['HTTP_REFERER'], strpos($_SERVER['HTTP_REFERER'], $_SERVER["HTTP_HOST"]) + strlen($_SERVER["HTTP_HOST"]));
				//check if destination url already exists
					$sql = "select count(*) from v_user_settings ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$sql .= "and user_uuid = :user_uuid ";
					$sql .= "and user_setting_category = 'login' ";
					$sql .= "and user_setting_subcategory = 'destination' ";
					$sql .= "and user_setting_name = 'url' ";
					$paramters['domain_uuid'] = $_SESSION['domain_uuid'];
					$paramters['user_uuid'] = $_SESSION['user_uuid'];
					$database = new database;
					$num_rows = $database->select($sql, $parameters, 'column');
					$exists = ($num_rows > 0) ? true : false;
					unset($sql, $parameters, $num_rows);

				//if exists, update
					if ($exists) {
						$sql = "update v_user_settings set ";
						$sql .= "user_setting_value = :user_setting_value ";
						$sql .= "user_setting_enabled = 'true' ";
						$sql .= "where domain_uuid = :domain_uuid ";
						$sql .= "and user_uuid = :user_uuid ";
						$sql .= "and user_setting_category = 'login' ";
						$sql .= "and user_setting_subcategory = 'destination' ";
						$sql .= "and user_setting_name = 'url' ";
						$parameters['user_setting_value'] = $referrer;
						$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
						$parameters['user_uuid'] = $_SESSION["user_uuid"];
						$database = new database;
						$database->execute($sql, $parameters);
						unset($sql, $parameters);
					}
				//otherwise, insert
					else {
						//build insert array
							$user_setting_uuid = uuid();
							$array['user_settings'][0]['user_setting_uuid'] = $user_setting_uuid;
							$array['user_settings'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
							$array['user_settings'][0]['user_uuid'] = $_SESSION["user_uuid"];
							$array['user_settings'][0]['user_setting_category'] = 'login';
							$array['user_settings'][0]['user_setting_subcategory'] = 'destination';
							$array['user_settings'][0]['user_setting_name'] = 'url';
							$array['user_settings'][0]['user_setting_value'] = $referrer;
							$array['user_settings'][0]['user_setting_enabled'] = 'true';
						//grant temporary permissions
							$p = new permissions;
							$p->add('user_setting_add', 'temp');
						//execute insert
							$database = new database;
							$database->app_name = 'logout';
							$database->app_uuid = 'e9f24006-5da2-417f-94fb-7458348bae29';
							$database->save($array);
							unset($array);
						//revoke temporary permissions
							$p->delete('user_setting_add', 'temp');
					}
			}
		}
	}

//redirect the user to the index page
	header("Location: ".PROJECT_PATH."/login.php");
	exit;

?>