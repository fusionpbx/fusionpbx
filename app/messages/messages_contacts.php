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

//get selected number/contact
	$current_contact = $_GET['sel'];

//get the list
	if (isset($_SESSION['message']['display_last']['text']) && $_SESSION['message']['display_last']['text'] != '') {
		$array = explode(' ',$_SESSION['message']['display_last']['text']);
		if (is_array($array) && is_numeric($array[0]) && $array[0] > 0) {
			if ($array[1] == 'messages') {
				$limit = "limit ".$array[0]." offset 0 ";
			}
			else {
				$since = "and message_date >= '".date("Y-m-d H:i:s", strtotime('-'.$_SESSION['message']['display_last']['text']))."' ";
			}
		}
	}
	if ($limit == '' && $since == '') { $limit = "limit 25 offset 0"; } //default (message count)
	$sql = "select message_direction, message_from, message_to, contact_uuid from v_messages ";
	$sql .= "where user_uuid = '".$_SESSION['user_uuid']."' ";
	$sql .= "and (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
	$sql .= $since;
	$sql .= "order by message_date desc ";
	$sql .= $limit;
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
					$contact[$number]['contact_uuid'] = $field['contact_uuid'];
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
			if ($current_contact != '' && $number == $current_contact) {
				echo "<tr><td valign='top' class='".$row_style[$c]." contact_selected' style='cursor: default;'>\n";
				$selected = true;
			}
			else {
				echo "<tr><td valign='top' class='".$row_style[$c]."' onclick=\"load_thread('".urlencode($number)."', '".$contact[$number]['contact_uuid']."');\">\n";
				$selected = false;
			}
			if ($contact[$number]['contact_name_given'] != '' || $contact[$number]['contact_name_family'] != '') {
				echo "<i>".escape($contact[$number]['contact_name_given'].' '.$contact[$number]['contact_name_family']).'</i>';
				echo "<span style='float: right; font-size: 65%; line-height: 60%; margin-top: 5px; margin-left: 5px; margin-right: ".($selected ? '-4px' : '0').";'>".escape(format_phone($number)).'</span>';
				if ($selected) {
					$contact_name = escape($contact[$number]['contact_name_given'].' '.$contact[$number]['contact_name_family']);
					$contact_html = (permission_exists('contact_view') ? "<a href='".PROJECT_PATH."/app/contacts/contact_edit.php?id=".$contact[$number]['contact_uuid']."' target='_blank'>".$contact_name."</a>" : $contact_name)." : <a href='callto:".escape($number)."'>".escape(format_phone($number))."</a>";
					echo "<script>$('#contact_current_name').html(\"".$contact_html."\");</script>\n";
				}
			}
			else {
				echo escape(format_phone($number));
				if ($selected) {
					echo "<script>$('#contact_current_name').html(\"<a href='callto:".escape($number)."'>".escape(format_phone($number))."</a>\");</script>\n";
				}
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