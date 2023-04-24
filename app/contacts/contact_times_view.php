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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('contact_time_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the contact list
	$sql = "select ct.*, u.username, u.domain_uuid as user_domain_uuid ";
	$sql .= "from v_contact_times as ct, v_users as u ";
	$sql .= "where ct.user_uuid = u.user_uuid ";
	$sql .= "and ct.domain_uuid = :domain_uuid ";
	$sql .= "and ct.contact_uuid = :contact_uuid ";
	$sql .= "order by ct.time_start desc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_times = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_times) && @sizeof($contact_times) != 0) {

		//show the content
			echo "<div class='grid' style='grid-template-columns: 70px auto auto;'>\n";
			$x = 0;
			foreach ($contact_times as $row) {
				if ($row["time_start"] != '' && $row['time_stop'] != '') {
					$time_start = strtotime($row["time_start"]);
					$time_stop = strtotime($row['time_stop']);
					$time = gmdate("H:i:s", ($time_stop - $time_start));
				}
				else {
					unset($time);
				}
				$tmp = explode(' ', $row['time_start']);
				$time_start = $tmp[0];
				echo "<div class='box contact-details-label'><span ".($row['user_domain_uuid'] != $domain_uuid ? "title='".$_SESSION['domains'][escape($row['user_domain_uuid'])]['domain_name']."' style='cursor: help;'" : null).">".escape($row["username"])."</span></div>\n";
				echo "<div class='box'>".$time_start."</div>\n";
				echo "<div class='box'>".$time."</div>\n";
				$x++;
			}
			echo "</div>\n";
			unset($contact_times);

	}

?>