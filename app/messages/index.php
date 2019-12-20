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
	Portions created by the Initial Developer are Copyright (C) 2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//default authorized to false
	$authorized = false;

//get the user settings
	$sql = "select user_uuid, domain_uuid from v_user_settings ";
	$sql .= "where user_setting_category = 'message' ";
	$sql .= "and user_setting_subcategory = 'key' ";
	$sql .= "and user_setting_value = :user_setting_value ";
	$sql .= "and user_setting_enabled = 'true' ";
	$parameters['user_setting_value'] = $_GET['key'];
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && @sizeof($row) != 0 && is_uuid($row['user_uuid'])) {
		$domain_uuid = $row['domain_uuid'];
		$user_uuid = $row['user_uuid'];
		$authorized = true;
	}
	unset($sql, $parameters, $row);

//authorization failed
	if (!$authorized) {
		//log the failed auth attempt to the system, to be available for fail2ban.
			openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
			syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] authentication failed for ".$_GET['key']);
			closelog();

		//send http 404
			header("HTTP/1.0 404 Not Found");
			echo "<html>\n";
			echo "<head><title>404 Not Found</title></head>\n";
			echo "<body bgcolor=\"white\">\n";
			echo "<center><h1>404 Not Found</h1></center>\n";
			echo "<hr><center>nginx/1.12.1</center>\n";
			echo "</body>\n";
			echo "</html>\n";
			exit();
	}

	require "resources/vendors/".$_GET['vendor'].".php";

?>
