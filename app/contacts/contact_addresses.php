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

//check permissions
	if (permission_exists('contact_address_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set the uuid
	if (is_uuid($_GET['id'])) {
		$contact_uuid = $_GET['id'];
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
			echo "<div class='action_bar sub shrink'>\n";
			echo "	<div class='heading'><b>".$text['label-addresses']."</b></div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			if (permission_exists('contact_address_delete')) {
				echo "	<th class='checkbox'>\n";
				echo "		<input type='checkbox' id='checkbox_all_addresses' name='checkbox_all' onclick=\"edit_all_toggle('addresses');\" ".($contact_addresses ?: "style='visibility: hidden;'").">\n";
				echo "	</th>\n";
			}
			echo "<th class='pct-15'>".$text['label-address_label']."</th>\n";
			echo "<th>".$text['label-address_address']."</th>\n";
			echo "<th>".$text['label-address_locality'].", ".$text['label-address_region']."</th>\n";
			echo "<th class='center'>".$text['label-address_country']."</th>\n";
			echo "<th class='shrink'>&nbsp;</th>\n";
			echo "<th class='hide-md-dn'>".$text['label-address_description']."</th>\n";
			if (permission_exists('contact_address_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>&nbsp;</td>\n";
			}
			echo "</tr>\n";

			if (is_array($contact_addresses) && @sizeof($contact_addresses) != 0) {
				$x = 0;
				foreach ($contact_addresses as $row) {
					$map_query = $row['address_street']." ".$row['address_extended'].", ".$row['address_locality'].", ".$row['address_region'].", ".$row['address_region'].", ".$row['address_postal_code'];
					if (permission_exists('contact_address_edit')) {
						$list_row_url = "contact_address_edit.php?contact_uuid=".urlencode($row['contact_uuid'])."&id=".urlencode($row['contact_address_uuid']);
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					if (permission_exists('contact_address_delete')) {
						echo "	<td class='checkbox'>\n";
						echo "		<input type='checkbox' name='contact_addresses[$x][checked]' id='checkbox_".$x."' class='chk_delete checkbox_addresses' value='true' onclick=\"edit_delete_action('addresses');\">\n";
						echo "		<input type='hidden' name='contact_addresses[$x][uuid]' value='".escape($row['contact_address_uuid'])."' />\n";
						echo "	</td>\n";
					}
					echo "	<td>".escape($row['address_label'])." ".($row['address_primary'] ? "&nbsp;<i class='fas fa-star fa-xs' style='float: right; margin-top: 0.5em; margin-right: -0.5em;' title=\"".$text['label-primary']."\"></i>" : null)."</td>\n";
					$address = escape($row['address_street']).($row['address_extended'] != '' ? " ".escape($row['address_extended']) : null);
					echo "	<td class='pct-25 overflow no-wrap'><a href='".$list_row_url."'>".$address."</a>&nbsp;</td>\n";
					echo "	<td class='no-wrap'>".escape($row['address_locality']).(($row['address_locality'] != '' && $row['address_region'] != '') ? ", " : null).escape($row['address_region'])."&nbsp;</td>\n";
					echo "	<td class='center'>".escape($row['address_country'])."&nbsp;</td>\n";
					echo "	<td class='button no-link'><a href=\"http://maps.google.com/maps?q=".urlencode($map_query)."&hl=en\" target=\"_blank\"><img src='resources/images/icon_gmaps.png' style='width: 21px; height: 21px; alt='".$text['label-google_map']."' title='".$text['label-google_map']."'></a></td>\n";
					echo "	<td class='description overflow hide-md-dn'>".escape($row['address_description'])."&nbsp;</td>\n";
					if (permission_exists('contact_address_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>\n";
						echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
				unset($contact_addresses);
			}

			echo "</table>\n";
			echo "<br />\n";

	}

?>