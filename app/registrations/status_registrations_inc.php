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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
	if (permission_exists("registration_domain") || permission_exists("registration_all") || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the HTTP values and set as variables
	$sip_profile_name = trim($_REQUEST["profile"]);
	$show = trim($_REQUEST["show"]);
	if ($show != "all") { $show = ''; }

//define variables
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//create the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		$msg = "<div align='center'>".$text['error-event-socket']."<br /></div>";
	}

//define js function call var
	$onhover_pause_refresh = " onmouseover='refresh_stop();' onmouseout='refresh_start();'";

//show the error message or show the content
	if (strlen($msg) > 0) {
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
		//get sofia status profile information including registrations
			$cmd = "api sofia xmlstatus profile ".$sip_profile_name." reg";
			$xml_response = trim(event_socket_request($fp, $cmd));
			if ($xml_response == "Invalid Profile!") { $xml_response = "<error_msg>".$text['label-message']."</error_msg>"; }
			$xml_response = str_replace("<profile-info>", "<profile_info>", $xml_response);
			$xml_response = str_replace("</profile-info>", "</profile_info>", $xml_response);
			try {
				$xml = new SimpleXMLElement($xml_response);
			}
			catch(Exception $e) {
				echo $e->getMessage();
				exit;
			}

		//build the registration array
			if (count($xml->registrations->registration) > 0) {
				$registrations = '';
				$x = 0;
				foreach ($xml->registrations->registration as $row) {
					//get the values from xml and set them to the channel array
						$registrations[$x]['user'] = $row->{'user'};
						$user_array = explode('@', $row->{'user'});
						$registrations[$x]['call-id'] = $row->{'call-id'};
						$registrations[$x]['contact'] = $row->{'contact'};
						$registrations[$x]['sip-auth-user'] = $row->{'sip-auth-user'};
						$registrations[$x]['agent'] = $row->{'agent'};
						$registrations[$x]['host'] = $row->{'host'};
						$registrations[$x]['network-ip'] = $row->{'network-ip'};
						$registrations[$x]['network-port'] = $row->{'network-port'};
						$registrations[$x]['sip-auth-realm'] = $row->{'sip-auth-realm'};
						$registrations[$x]['mwi-account'] = $row->{'mwi-account'};
						$registrations[$x]['status'] = $row->{'status'};

					//get the LAN IP address if it exists replace the external ip
						$call_id_array = explode('@', $row->{'call-id'});
						if (isset($call_id_array[1])) {
							$registrations[$x]['lan-ip'] = $call_id_array[1];
						}

					//remove unrelated domains
						if (count($_SESSION["domains"]) > 1) {
							if (!(permission_exists('registration_all') && $show == "all")) {
								if ($registrations[$x]['sip-auth-realm'] == $_SESSION['domain_name']) {}
								elseif ($user_array[1] == $_SESSION['domain_name']){}
								else {
									unset($registrations[$x]);
								}
							}
						}

					//increment the array id
						$x++;
				}
			}

		//show the registrations
			echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
			echo "<tr>\n";
			echo "<td width='100%'>\n";
			echo "	<b>".$text['header-registrations']." (".count($registrations).")</b>\n";
			echo "</td>\n";
			echo "<td valign='middle' nowrap='nowrap' style='padding-right: 15px' id='refresh_state'>";
			echo "	<img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 3px; cursor: pointer;' onclick='refresh_stop();' alt=\"".$text['label-refresh_pause']."\" title=\"".$text['label-refresh_pause']."\">";
			echo "</td>";
			echo "<td valign='top' nowrap='nowrap'>";
			if (permission_exists('registration_all')) {
				if ($show == "all") {
					echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='status_registrations.php?show_reg=1&profile=$sip_profile_name'\" value='".$text['button-back']."' ".$onhover_pause_refresh.">\n";
				}
				else {
					echo "	<input type='button' class='btn' name='' alt='".$text['button-show_all']."' onclick=\"window.location='status_registrations.php?show_reg=1&profile=$sip_profile_name&show=all'\" value='".$text['button-show_all']."' ".$onhover_pause_refresh.">\n";
				}
			}
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "<br />\n";

			echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
			echo "<tr>\n";
			echo "	<th>".$text['label-user']."</th>\n";
			echo "	<th>".$text['label-agent']."</th>\n";
			echo "	<th>".$text['label-lan_ip']."</th>\n";
			echo "	<th>".$text['label-ip']."</th>\n";
			echo "	<th>".$text['label-port']."</th>\n";
			echo "	<th>".$text['label-hostname']."</th>\n";
			echo "	<th>".$text['label-status']."</th>\n";
			echo "	<th>".$text['label-tools']."&nbsp;</th>\n";
			echo "</tr>\n";

		//order the array
			require_once "resources/classes/array_order.php";
			$order = new array_order();
			$registrations = $order->sort($registrations, 'sip-auth-realm', 'user');

		//display the array
			if (count($registrations) > 0) {
				foreach ($registrations as $row) {
					//set the user agent
						$agent = $row['agent'];

					//show the registrations
						echo "<tr>\n";
						echo "	<td class='".$row_style[$c]."'>".$row['user']."&nbsp;</td>\n";
						echo "	<td class='".$row_style[$c]."'>".htmlentities($row['agent'])."&nbsp;</td>\n";
						echo "	<td class='".$row_style[$c]."'><a href='http://".$row['lan-ip']."' target='_blank'>".$row['lan-ip']."</a>&nbsp;</td>\n";
						echo "	<td class='".$row_style[$c]."'><a href='http://".$row['network-ip']."' target='_blank'>".$row['network-ip']."</a>&nbsp;</td>\n";
						echo "	<td class='".$row_style[$c]."'>".$row['network-port']."&nbsp;</td>\n";
						echo "	<td class='".$row_style[$c]."'>".$row['host']."&nbsp;</td>\n";
						echo "	<td class='".$row_style[$c]."'>".$row['status']."&nbsp;</td>\n";
						echo "	<td class='".$row_style[$c]."' style='text-align: right;' nowrap='nowrap'>\n";
						echo "		<input type='button' class='btn' value='".$text['button-unregister']."' onclick=\"document.location.href='cmd.php?cmd=unregister&profile=".$sip_profile_name."&show=".$show."&user=".$row['user']."&domain=".$row['sip-auth-realm']."&agent=".urlencode($row['agent'])."';\" ".$onhover_pause_refresh.">\n";
						echo "		<input type='button' class='btn' value='".$text['button-provision']."' onclick=\"document.location.href='cmd.php?cmd=check_sync&profile=".$sip_profile_name."&show=".$show."&user=".$row['user']."&domain=".$row['sip-auth-realm']."&agent=".urlencode($row['agent'])."';\" ".$onhover_pause_refresh.">\n";
						echo "		<input type='button' class='btn' value='".$text['button-reboot']."' onclick=\"document.location.href='cmd.php?cmd=reboot&profile=".$sip_profile_name."&show=".$show."&user=".$row['user']."&domain=".$row['sip-auth-realm']."&agent=".urlencode($row['agent'])."';\" ".$onhover_pause_refresh.">\n";
						echo "	</td>\n";
						echo "</tr>\n";
						if ($c==0) { $c=1; } else { $c=0; }
				}
			}
			echo "</table>\n";

		//close the connection and unset the variable
			fclose($fp);
			unset($xml);
	}

?>