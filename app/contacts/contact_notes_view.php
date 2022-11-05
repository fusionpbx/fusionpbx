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
	if (permission_exists('contact_note_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the contact list
	$sql = "select * from v_contact_notes ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by last_mod_date desc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_notes = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_notes) && @sizeof($contact_notes) != 0) {

		//show the content
			echo "<div class='grid' style='grid-template-columns: auto 190px;'>\n";
			$x = 0;
			foreach ($contact_notes as $row) {
				$contact_note = str_replace("\n","<br />",escape($row['contact_note']));
				echo "<div class='box' style='padding-bottom: 15px;'>".$contact_note."</div>\n";
				echo "<div class='box contact-details-label' style='padding-bottom: 15px; text-align: right;'><strong>".escape($row['last_mod_user'])."</strong>: ".date("j M Y @ H:i:s", strtotime($row['last_mod_date']))."</div>\n";
				$x++;
			}
			echo "</div>\n";
			unset($contact_notes);

	}

?>