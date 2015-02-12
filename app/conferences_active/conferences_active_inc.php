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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('conference_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();


$switch_cmd = 'conference xml_list';
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if (!$fp) {
	$msg = "<div align='center'>".$text['message-connection']."<br /></div>";
	echo "<div align='center'>\n";
	echo "<table width='40%'>\n";
	echo "<tr>\n";
	echo "<th align='left'>".$text['label-message']."</th>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td class='row_style1'><strong>$msg</strong></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</div>\n";
}
else {
	$xml_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
	try {
		$xml = new SimpleXMLElement($xml_str);
	}
	catch(Exception $e) {
		//echo $e->getMessage();
	}

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>".$text['label-name']."</th>\n";
	echo "<th>".$text['label-participant-pin']."</th>\n";
	echo "<th>".$text['label-member-count']."</th>\n";
	echo "<th>&nbsp;</th>\n";
	echo "</tr>\n";

	foreach ($xml->conference as $row) {
		//set the variables
			$name = $row['name'];
			$member_count = $row['member-count'];
		//show the conferences that have a matching domain
			$tmp_domain = substr($name, -strlen($_SESSION['domain_name']));
			if ($tmp_domain == $_SESSION['domain_name']) {
				$conference_name = substr($name, 0, strlen($name) - strlen('-'.$_SESSION['domain_name']));
				if (is_uuid($conference_name)) {
					$meeting_uuid = $conference_name;
					$sql = "select ";
					$sql .= "cr.conference_room_name, ";
					$sql .= "v.participant_pin ";
					$sql .= "from ";
					$sql .= "v_meetings as v, ";
					$sql .= "v_conference_rooms as cr ";
					$sql .= "where ";
					$sql .= "v.meeting_uuid = cr.meeting_uuid ";
					$sql .= "and v.meeting_uuid = '".$conference_name."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll();
					foreach ($result as $row2) {
						$conference_name = $row2['conference_room_name'];
						$participant_pin = $row2['participant_pin'];
					}
					unset ($prep_statement, $row2);
				}
				else {
					$meeting_uuid = $conference_name;
					$sql = "select ";
					$sql .= "conference_pin_number ";
					$sql .= "from ";
					$sql .= "v_conferences ";
					$sql .= "where ";
					$sql .= "domain_uuid = '".$_SESSION['domain_uuid']."' ";
					$sql .= "and conference_name = '".$conference_name."' ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll();
					foreach ($result as $row3) {
						$participant_pin = $row3['conference_pin_number'];
					}
					unset ($prep_statement, $row3);
				}

				if (permission_exists('conference_interactive_view')) {
					$td_onclick = "onclick=\"document.location.href='conference_interactive.php?c=".$meeting_uuid."'\"";
				}
				echo "<tr>\n";
				echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">";
				echo (permission_exists('conference_interactive_view')) ? "<a href='conference_interactive.php?c=".$meeting_uuid."'>".$conference_name."</a>" : $conference_name;
				echo "</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">".$participant_pin."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">".$member_count."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">";
				echo (permission_exists('conference_interactive_view')) ? "<a href='conference_interactive.php?c=".$meeting_uuid."'>".$text['button-view']."</a>" : "&nbsp;";
				echo "</td>\n";
				echo "</tr>\n";

				if ($c==0) { $c=1; } else { $c=0; }
			}
	}
	echo "</table>\n";
	echo "<br /><br />";
}
?>