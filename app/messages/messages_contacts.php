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
	Portions created by the Initial Developer are Copyright (C) 2016-2019
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
				$limit = limit_offset($array[0], 0);
			}
			else {
				$since = "and message_date >= :message_date ";
				$parameters['message_date'] = date("Y-m-d H:i:s", strtotime('-'.$_SESSION['message']['display_last']['text']));
			}
		}
	}
	if ($limit == '' && $since == '') { $limit = limit_offset(25, 0); } //default (message count)
	$sql = "select message_direction, message_from, message_to, contact_uuid ";
	$sql .= "from v_messages ";
	$sql .= "where user_uuid = :user_uuid ";
	$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
	$sql .= $since;
	$sql .= "order by message_date desc ";
	$sql .= $limit;
	$parameters['user_uuid'] = $_SESSION['user_uuid'];
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$messages = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//parse out numbers
	if (is_array($messages) && @sizeof($messages) != 0) {
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
				case 'inbound':
					if (!is_uuid($contact[$number_from]['contact_uuid'])) {
						$contact[$number_from]['contact_uuid'] = $message['contact_uuid'];
					}
					break;
				case 'outbound':
					if (!is_uuid($contact[$number_to]['contact_uuid'])) {
						$contact[$number_to]['contact_uuid'] = $message['contact_uuid'];
					}
					break;
			}
			unset($number_from, $number_to);
		}
	}
	unset($messages, $message);

//get contact details, if uuid available
	if (is_array($contact) && sizeof($contact) != 0) {
		foreach ($contact as $number => $field) {
			if (is_uuid($field['contact_uuid'])) {
				$sql = "select c.contact_name_given, c.contact_name_family, ";
				$sql .= "(select ce.email_address from v_contact_emails as ce where ce.contact_uuid = c.contact_uuid and ce.email_primary = 1) as contact_email ";
				$sql .= "from v_contacts as c ";
				$sql .= "where c.contact_uuid = :contact_uuid ";
				$sql .= "and (c.domain_uuid = :domain_uuid or c.domain_uuid is null) ";
				$parameters['contact_uuid'] = $field['contact_uuid'];
				$parameters['domain_uuid'] = $domain_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				if (is_array($row) && @sizeof($row) != 0) {
					$contact[$number]['contact_uuid'] = $field['contact_uuid'];
					$contact[$number]['contact_name_given'] = $row['contact_name_given'];
					$contact[$number]['contact_name_family'] = $row['contact_name_family'];
					$contact[$number]['contact_email'] = $row['contact_email'];
				}
				unset($sql, $parameters, $row);
			}
			else {
				unset($contact[$number]);
			}
		}
	}

//get destinations and remove from numbers array
	$sql = "select destination_number from v_destinations ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and destination_enabled = 'true' ";
	$sql .= "order by destination_number asc ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$rows = $database->select($sql, $parameters, 'all');
	if (is_array($rows) && @sizeof($rows)) {
		foreach ($rows as $row) {
			$destinations[] = $row['destination_number'];
		}
	}
	unset($sql, $parameters, $rows, $row);

	if (
		is_array($numbers) &&
		@sizeof($numbers) != 0 &&
		is_array($destinations) &&
		@sizeof($destinations) != 0 &&
		!is_null(array_diff($numbers, $destinations))
		) {
		$numbers = array_diff($numbers, $destinations);
	}

//get contact (primary attachment) images and cache them
	if (is_array($numbers) && @sizeof($numbers) != 0) {
		foreach ($numbers as $number) {
			$contact_uuids[] = $contact[$number]['contact_uuid'];
		}
		if (is_array($contact_uuids) && @sizeof($contact_uuids) != 0) {
			$sql = "select contact_uuid as uuid, attachment_filename as filename, attachment_content as image ";
			$sql .= "from v_contact_attachments ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and (";
			foreach ($contact_uuids as $index => $contact_uuid) {
				$sql_where[] = "contact_uuid = :contact_uuid_".$index;
				$parameters['contact_uuid_'.$index] = $contact_uuid;
			}
			$sql .= implode(' or ', $sql_where);
			$sql .= ") ";
			$sql .= "and attachment_primary = 1 ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$database = new database;
			$contact_ems = $database->select($sql, $parameters, 'all');
			if (is_array($contact_ems) && @sizeof($contact_ems) != 0) {
				foreach ($contact_ems as $contact_em) {
					$_SESSION['tmp']['messages']['contact_em'][$contact_em['uuid']]['filename'] = $contact_em['filename'];
					$_SESSION['tmp']['messages']['contact_em'][$contact_em['uuid']]['image'] = $contact_em['image'];
				}
			}
		}
		unset($sql, $sql_where, $parameters, $contact_uuids, $contact_ems, $contact_em);
	}

//contacts list
	if (is_array($numbers) && @sizeof($numbers) != 0) {
		echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		foreach($numbers as $number) {
			if ($current_contact != '' && $number == $current_contact) {
				echo "<tr><td valign='top' class='row_style0 contact_selected' style='cursor: default;'>\n";
				$selected = true;
			}
			else {
				echo "<tr><td valign='top' class='row_style1' onclick=\"load_thread('".urlencode($number)."', '".$contact[$number]['contact_uuid']."');\">\n";
				$selected = false;
			}
			//contact image
				if (is_array($_SESSION['tmp']['messages']['contact_em'][$contact[$number]['contact_uuid']]) && sizeof($_SESSION['tmp']['messages']['contact_em'][$contact[$number]['contact_uuid']]) != 0) {
					$attachment_type = strtolower(pathinfo($_SESSION['tmp']['messages']['contact_em'][$contact[$number]['contact_uuid']]['filename'], PATHINFO_EXTENSION));
					echo "<img id='src_message-bubble-image-em_".$contact[$number]['contact_uuid']."' style='display: none;' src='data:image/".$attachment_type.";base64,".$_SESSION['tmp']['messages']['contact_em'][$contact[$number]['contact_uuid']]['image']."'>\n";
					echo "<img id='contact_image_".$contact[$number]['contact_uuid']."' class='contact_list_image' src='data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'>\n";
				}
			//contact name/number
				if ($contact[$number]['contact_name_given'] != '' || $contact[$number]['contact_name_family'] != '') {
					echo "<div style='float: right; margin-top: 8px; margin-right: ".($selected ? '-1' : '4')."px;' title=\"".$text['label-view_contact']."\"><a href='/app/contacts/contact_edit.php?id=".$contact[$number]['contact_uuid']."' target='_blank'><i class='fas fa-user'></i></a></div>\n";
					echo "<div style='display: table;'>\n";
					echo "	<strong style='display: inline-block; margin: 8px 0 5px 0; white-space: nowrap;'>".escape($contact[$number]['contact_name_given'].' '.$contact[$number]['contact_name_family']).'</strong><br>';
					echo "	<span style='font-size: 80%; white-space: nowrap;'><a href='callto:".escape($number)."'><i class='fas fa-phone-alt' style='margin-right: 5px;'></i>".escape(format_phone($number)).'</a></span><br>';
					if (valid_email($contact[$number]['contact_email'])) {
						echo "<span style='font-size: 80%; white-space: nowrap;'><a href='mailto:".escape($contact[$number]['contact_email'])."'><i class='fas fa-envelope' style='margin-right: 5px;'></i>".$text['label-send_email']."</a></span><br>";
					}
					if ($selected) {
						$contact_name = escape($contact[$number]['contact_name_given'].' '.$contact[$number]['contact_name_family']);
						$contact_html = (permission_exists('contact_view') ? "<a href='".PROJECT_PATH."/app/contacts/contact_edit.php?id=".$contact[$number]['contact_uuid']."' target='_blank'>".$contact_name."</a>" : $contact_name)." : <a href='callto:".escape($number)."'>".escape(format_phone($number))."</a>";
						echo "<script>$('#contact_current_name').html(\"".$contact_html."\");</script>\n";
					}
					echo "</div>\n";
				}
				else {
					echo escape(format_phone($number));
					if ($selected) {
						echo "<script>$('#contact_current_name').html(\"<a href='callto:".escape($number)."'>".escape(format_phone($number))."</a>\");</script>\n";
					}
				}
			echo "</td></tr>\n";
		}
		echo "</table>\n";

		echo "<script>\n";
		foreach ($numbers as $number) {
			if (is_array($_SESSION['tmp']['messages']['contact_em'][$contact[$number]['contact_uuid']]) && @sizeof($_SESSION['tmp']['messages']['contact_em'][$contact[$number]['contact_uuid']]) != 0) {
				echo "$('img#contact_image_".$contact[$number]['contact_uuid']."').css('backgroundImage', 'url(' + $('img#src_message-bubble-image-em_".$contact[$number]['contact_uuid']."').attr('src') + ')');\n";
			}
		}
		echo "</script>\n";
	}
	else {
		echo "<div style='padding: 15px;'><center>&middot;&middot;&middot;</center>";
	}

	echo "<center>\n";
	echo "	<span id='contacts_refresh_state'><img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 3px; cursor: pointer;' onclick=\"refresh_contacts_stop();\" alt=\"".$text['label-refresh_pause']."\" title=\"".$text['label-refresh_pause']."\"></span> ";
	echo "</center>\n";

?>