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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (!permission_exists('contact_time_add')) { echo "access denied"; exit; }

//get contact and time uuids
	$domain_uuid = $_REQUEST['domain_uuid'];
	$contact_uuid = $_REQUEST['contact_uuid'];
	$contact_time_uuid = $_REQUEST['contact_time_uuid'];

//get time quantity
	$sql = "select ";
	$sql .= "time_start ";
	$sql .= "from v_contact_times ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_time_uuid = :contact_time_uuid ";
	$sql .= "and user_uuid = :user_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "and time_start is not null ";
	$sql .= "and time_stop is null ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$parameters['user_uuid'] = $_SESSION['user']['user_uuid'];
	$parameters['contact_time_uuid'] = $contact_time_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && @sizeof($row) != 0) {
		$time_start = strtotime($row["time_start"]);
		$time_now = strtotime(date("Y-m-d H:i:s"));
		$time_diff = gmdate("H:i:s", ($time_now - $time_start));
		echo $time_diff;
		echo "<script id='title_script'>set_title('".$time_diff."');</script>";
	}
	unset ($sql, $parameters, $row);
?>