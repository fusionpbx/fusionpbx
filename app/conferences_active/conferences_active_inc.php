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
if (permission_exists('conference_active_advanced_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

$tmp_conference_name = str_replace("_", " ", $conference_name);

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

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>".$text['label-name']."</th>\n";
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
				$conference_display_name = str_replace("-", " ", $conference_name);
				$conference_display_name = str_replace("_", " ", $conference_display_name);

				//$id = $row->members->member->id;
				//$flag_can_hear = $row->members->member->flags->can_hear;
				//$flag_can_speak = $row->members->member->flags->can_speak;
				//$flag_talking = $row->members->member->flags->talking;
				//$flag_has_video = $row->members->member->flags->has_video;
				//$flag_has_floor = $row->members->member->flags->has_floor;
				//$uuid = $row->members->member->uuid;
				//$caller_id_name = $row->members->member->caller_id_name;
				//$caller_id_name = str_replace("%20", " ", $caller_id_name);
				//$caller_id_number = $row->members->member->caller_id_number;

				echo "<tr>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$conference_display_name."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$member_count."</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'><a href='conference_interactive.php?c=".$conference_name."'>".$text['button-view']."</a></td>\n";
				echo "</tr>\n";

				if ($c==0) { $c=1; } else { $c=0; }
			}
	}
	echo "</table>\n";
}
?>