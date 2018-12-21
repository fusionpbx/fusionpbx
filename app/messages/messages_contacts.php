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
	Portions created by the Initial Developer are Copyright (C) 2016-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('message_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the list
	$since = date("Y-m-d H:i:s", strtotime("-24 hours"));
	$sql = "select message_direction, message_from, message_to, contact_uuid from v_messages ";
	$sql .= "where user_uuid = '".$_SESSION['user_uuid']."' ";
	$sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
	//$sql .= "and message_date >= '".$since."' ";
	$sql .= "order by message_date desc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$messages = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	unset ($prep_statement, $sql);

//parse out numbers
	if (is_array($messages) && sizeof($messages) != 0) {
		$numbers = [];
		foreach($messages as $message) {
			$number_from = preg_replace('{[\D]}', '', $message['message_from']);
			$number_to = preg_replace('{[\D]}', '', $message['message_to']);
			if (!in_array($number_from, $numbers)) {
				$numbers[] = $number_from;
			}
			if (!in_array($number_to, $numbers)) {
				$numbers[] = $number_to;
			}
			switch ($message['message_direction']) {
				case 'inbound': $contact[$number_from]['contact_uuid'] = $message['contact_uuid']; break;
				case 'outbound': $contact[$number_to]['contact_uuid'] = $message['contact_uuid']; break;
			}
			unset($number_from, $number_to);
		}
	}

//get contact details, if uuid available
	if (is_array($contact) && sizeof($contact) != 0) {
		foreach ($contact as $number => $field) {
			if (is_uuid($field['contact_uuid'])) {
				$sql = "select contact_name_given, contact_name_family from v_contacts ";
				$sql .= "where contact_uuid = '".$field['contact_uuid']."' ";
				$sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$row = $prep_statement->fetch(PDO::FETCH_NAMED);
				if (is_array($row) && sizeof($row) != 0) {
					$contact[$number]['contact_name_given'] = $row['contact_name_given'];
					$contact[$number]['contact_name_family'] = $row['contact_name_family'];
				}
				unset($prep_statement, $sql);
			}
			else {
				unset($contact[$number]);
			}
		}
	}

//get destinations and remove from numbers array
	$sql = "select destination_number from v_destinations ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$sql .= "and destination_enabled = 'true' ";
	$sql .= "order by destination_number asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$rows = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	//view_array($rows);
	if (is_array($rows) && sizeof($rows)) {
		foreach ($rows as $row) {
			$destinations[] = $row['destination_number'];
		}
	}
	unset ($prep_statement, $sql, $row, $record);
	$numbers = array_diff($numbers, $destinations);

//alternate the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//contacts list
	if (is_array($numbers) && sizeof($numbers) != 0) {
		echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		foreach($numbers as $number) {
			echo "	<tr><td valign='top' class='".$row_style[$c]."' onclick=\"load_thread('".urlencode($number)."');\">";
			if ($contact[$number]['contact_name_given'] != '' || $contact[$number]['contact_name_family'] != '') {
				echo "		<i>".$contact[$number]['contact_name_given'].' '.$contact[$number]['contact_name_family'].'</i>';
				echo "<span style='float: right; font-size: 65%; line-height: 60%; margin-top: 5px; margin-left: 5px;'>".format_phone($number).'</span>';
			}
			else {
				echo "		".format_phone($number);
			}
			echo "</td></tr>\n";
			$c = $c == 0 ? 1 : 0;
		}
		echo "</table>\n";
		echo "<center>\n";
		echo "	<span id='contacts_refresh_state'><img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 3px; cursor: pointer;' onclick=\"refresh_contacts_stop();\" alt=\"".$text['label-refresh_pause']."\" title=\"".$text['label-refresh_pause']."\"></span> ";
		echo "</center>\n";
	}

?>