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
	Copyright (C) 2010 - 2019
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('active_queue_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include theme config for button images
	include_once("themes/".$_SESSION['domain']['template']['name']."/config.php");

//show the list
	$switch_cmd = 'fifo list';
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if ($fp) {
		$xml_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
		try {
			$xml = new SimpleXMLElement($xml_str);
		}
		catch(Exception $e) {
			//echo $e->getMessage();
		}

		/*
		<fifo_report>
		  <fifo name="5900@voip.fusionpbx.com" consumer_count="0" caller_count="1" waiting_count="1" importance="0">
			<callers>
			  <caller uuid="73a9324f-2a87-df11-bedf-0019dbe93b1f" status="WAITING" timestamp="2010-07-04 05:09:23">
				<caller_profile></caller_profile>
			  </caller>
			</callers>
			<consumers></consumers>
		  </fifo>
		  <fifo name="cool_fifo@voip.fusionpbx.com" consumer_count="0" caller_count="0" waiting_count="0" importance="0">
			<callers></callers>
			<consumers></consumers>
		  </fifo>
		</fifo_report>
		*/

		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";

		echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<th>".$text['label-name']."</th>\n";
		echo "<th>".$text['label-consumer_count']."</th>\n";
		echo "<th>".$text['label-caller_count']."</th>\n";
		echo "<th>".$text['label-waiting_count']."</th>\n";
		echo "<th>".$text['label-importance']."</th>\n";
		echo "<td class='list_control_icon'></td>\n";
		echo "</tr>\n";

		foreach ($xml->fifo as $row) {

			foreach($row->attributes() as $tmp_name => $tmp_value) {
				$$tmp_name = $tmp_value;
			}
			unset($tmp_name, $tmp_value);

			//remove the domain from name
				$tmp_name = str_replace('_', ' ', $name);
				$tmp_name_array = explode('@', $name);
				$tmp_name = $tmp_name_array[0];

			$td_onclick = "onclick=\"document.location.href='fifo_interactive.php?c=".escape($name)."';\"";

			if (if_group("superadmin")) {
				//show all fifo queues
					echo "<tr >\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick."><a href='fifo_interactive.php?c=".escape($name)."'>".escape($tmp_name)."</a></td>\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">".escape($consumer_count)."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">".escape($caller_count)."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">".escape($waiting_count)."</td>\n";
					echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">".escape($importance)."</td>\n";
					echo "<td class='list_control_icon'><a href='fifo_interactive.php?c=".escape($name)."' alt='".$text['button-view']."'>$v_link_label_view</a></td>\n";
					echo "</tr>\n";
			}
			else {
				//show only the fifo queues that match the domain_name
					if (stripos($name, $_SESSION['domain_name']) !== false) {
						echo "<tr >\n";
						echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick."><a href='fifo_interactive.php?c=".$name."'>".escape($tmp_name)."</a></td>\n";
						echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">".escape($consumer_count)."</td>\n";
						echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">".escape($caller_count)."</td>\n";
						echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">".escape($waiting_count)."</td>\n";
						echo "<td valign='top' class='".$row_style[$c]."' ".$td_onclick.">".escape($importance)."</td>\n";
						echo "<td class='list_control_icon'><a href='fifo_interactive.php?c=".escape($name)."' alt='".$text['button-view']."'>$v_link_label_view</a></td>\n";
						echo "</tr>\n";
					}
			}

			$c = $c ? 0 : 1;
		}
		echo "</table>\n";
	}

?>
