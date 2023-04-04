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
	if (permission_exists('contact_address_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//get the address list
	$sql = "select * from v_contact_addresses ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and contact_uuid = :contact_uuid ";
	$sql .= "order by address_primary desc, address_label asc ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['contact_uuid'] = $contact_uuid;
	$database = new database;
	$contact_addresses = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//show if exists
	if (is_array($contact_addresses) && @sizeof($contact_addresses) != 0) {

		//show the content
			echo "<div class='grid' style='grid-template-columns: 70px auto 30px;'>\n";
			$x = 0;
			foreach ($contact_addresses as $row) {
				$map_query = $row['address_street']." ".$row['address_extended'].", ".$row['address_locality'].", ".$row['address_region'].", ".$row['address_region'].", ".$row['address_postal_code'];
				echo "<div class='box contact-details-label'>".escape($row['address_label'])."</div>\n";
// 				($row['address_primary'] ? "&nbsp;<i class='fas fa-star fa-xs' style='float: right; margin-top: 0.5em; margin-right: -0.5em;' title=\"".$text['label-primary']."\"></i>" : null)."</td>\n";
				echo "<div class='box'>";
				echo "<a href='http://maps.google.com/maps?q=".urlencode($map_query)."&hl=en' target='_blank' alt='".$text['label-google_map']."' title='".$text['label-google_map']."'>";
				$previous_data = false;
				if ($row['address_street']) {
					echo escape($row['address_street']);
					$previous_data = true;
				}
				if ($row['address_extended']) {
					echo $previous_data ? ', ' : null;
					echo escape($row['address_extended']);
					$previous_data = true;
				}
				if ($row['address_locality']) {
					echo $previous_data ? ', ' : null;
					echo escape($row['address_locality']);
					$previous_data = true;
				}
				if ($row['address_region']) {
					echo $previous_data ? ', ' : null;
					echo escape($row['address_region']);
					$previous_data = true;
				}
				if ($row['address_country']) {
					echo $previous_data ? ', ' : null;
					echo escape($row['address_country']);
					$previous_data = true;
				}
				echo "</a>";
				echo "</div>\n";
				echo "<div class='box' style='text-align: right;'><i class='fas fa-map-marked-alt fa-fw'></i></div>\n";
				$x++;
			}
			echo "</div>\n";
			unset($contact_addresses);

	}

?>