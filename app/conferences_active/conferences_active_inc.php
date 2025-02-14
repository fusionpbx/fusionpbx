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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
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

//show content
	$fp = event_socket::create();
	if (!$fp->is_connected()) {
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
		$xml_string = trim(event_socket::api('conference xml_list'));
		try {
			$xml = new SimpleXMLElement($xml_string);
		}
		catch(Exception $e) {
			//echo $e->getMessage();
		}

		echo "<div class='card'>\n";
		echo "<table class='list'>\n";
		echo "<tr class='list-header'>\n";
		echo "	<th>".$text['label-name']."</th>\n";
		echo "	<th>".$text['label-extension']."</th>\n";
		echo "	<th>".$text['label-participant-pin']."</th>\n";
		echo "	<th class='center'>".$text['label-member-count']."</th>\n";
		if (permission_exists('conference_interactive_view')) {
			echo "	<td class='action-button'>&nbsp;</td>\n";
		}
		echo "</tr>\n";
		$x = 0;
		foreach ($xml->conference as $row) {

			//set the variables
				$name = $row['name'];
				$member_count = $row['member-count'];

			//show the conferences that have a matching domain
				$name_array = explode('@', $name);
				if ($name_array[1] == $_SESSION['domain_name']) {
					$conference_uuid = $name_array[0];

					//if uuid then lookup the conference name
					if (isset($name_array[0]) && is_uuid($name_array[0])) {
						//check for the conference center room
						$sql = "select ";
						$sql .= "cr.conference_room_name, ";
						$sql .= "cc.conference_center_extension, ";
						$sql .= "cr.participant_pin ";
						$sql .= "from v_conference_rooms as cr ";
						$sql .= "left join v_conference_centers as cc on cr.conference_center_uuid = cc.conference_center_uuid ";
						$sql .= "where cr.conference_room_uuid = :conference_room_uuid ";
						$parameters['conference_room_uuid'] = $conference_uuid;
						$database = new database;
						$conference = $database->select($sql, $parameters, 'row');
						$conference_name = $conference['conference_room_name'];
						$conference_extension = $conference['conference_center_extension'];
						$participant_pin = $conference['participant_pin'];
						unset ($parameters, $conference, $sql);
					}
					else if (isset($name_array[0]) && is_numeric($name_array[0])) {
						//check the conference table
						$sql = "select ";
						$sql .= "conference_name, ";
						$sql .= "conference_extension, ";
						$sql .= "conference_pin_number ";
						$sql .= "from ";
						$sql .= "v_conferences ";
						$sql .= "where ";
						$sql .= "domain_uuid = :domain_uuid ";
						$sql .= "and conference_extension = :conference_extension ";
						$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
						$parameters['conference_extension'] = $name_array[0];
						$database = new database;
						$conference = $database->select($sql, $parameters, 'row');
						$conference_name = $conference['conference_name'];
						$conference_extension = $conference['conference_extension'];
						$participant_pin = $conference['conference_pin_number'];
						unset ($parameters, $sql);
					}

					if (permission_exists('conference_interactive_view')) {
						$list_row_url = 'conference_interactive.php?c='.urlencode($conference_uuid);
					}

					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					echo "	<td>";
					if (permission_exists('conference_interactive_view')) {
						echo "	<a href='".$list_row_url."'>".escape($conference_name)."</a>";
					}
					else {
						echo escape($conference_name);
					}
					echo "	</td>\n";
					echo "	<td>".escape($conference_extension)."</td>\n";
					echo "	<td>".escape($participant_pin)."</td>\n";
					echo "	<td class='center'>".escape($member_count)."</td>\n";
					if (permission_exists('conference_interactive_view') && !empty($_SESSION['theme']['list_row_edit_button']['boolean']) && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
						echo "	<td class='action-button'>";
						echo button::create(['type'=>'button','title'=>$text['button-view'],'icon'=>$_SESSION['theme']['button_icon_view'],'link'=>$list_row_url]);
						echo "	</td>\n";
					}
					echo "</tr>\n";
					$x++;
				}
		}
		echo "</table>\n";
		echo "</div>\n";
		echo "<br /><br />";
	}

?>