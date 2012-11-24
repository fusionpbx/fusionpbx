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
require_once "includes/require.php";
require_once "includes/checkauth.php";

//check permissions
	if (permission_exists("registrations_domain") || permission_exists("registrations_all") || if_group("superadmin")) {
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

//request form values and set them as variables
	$sip_profile_name = trim($_REQUEST["profile"]);

//define variables
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//create the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		$msg = "<div align='center'>".$text['error-event-socket']."<br /></div>"; 
	}

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

					//remove unrelated domains
						if (count($_SESSION["domains"]) > 1) {
							if (count($_SESSION["domains"]) > 1 && !permission_exists('registrations_all')) {
								if ($registrations[$x]['sip-auth-realm'] != $_SESSION['domain_name']) {
									unset($registrations[$x]);
								}
							}
							else {
								if ($registrations[$x]['sip-auth-realm'] != $_SESSION['domain_name']) {
									unset($registrations[$x]);
								}
							}
						}
					//increment the array id
						$x++;
				}
			}

		//show the header
			require_once "includes/header.php";

		//show the registrations
			echo "<table width='100%' border='0' cellspacing='0' cellpadding='5'>\n";
			echo "<tr>\n";
			echo "<td colspan='4'>\n";
			echo "	<b>Registrations: ".count($registrations)."</b>\n";
			echo "</td>\n";
			echo "<td colspan='1' align='right'>\n";
			echo "  <input type='button' class='btn' value='".$text['button-back']."' onclick=\"history.back();\" />\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";

			echo "<table width='100%' border='0' cellspacing='0' cellpadding='5'>\n";
			echo "<tr>\n";
			if (count($_SESSION["domains"]) > 1) {
				echo "	<th>".$text['label-domain']."</th>\n";
			}
			//echo "	<th>User</th>\n";
			//echo "	<th class='vncell'>Caller ID</th>\n";
			echo "	<th>".$text['label-user']."</th>\n";
			//echo "	<th class='vncell'>Contact</th>\n";
			//echo "	<th class='vncell'>sip-auth-user</th>\n";
			echo "	<th>".$text['label-agent']."</th>\n";
			//echo "	<th class='vncell'>Host</th>\n";
			echo "	<th>".$text['label-ip']."</th>\n";
			echo "	<th>".$text['label-port']."</th>\n";
			//echo "	<th class='vncell'>mwi-account</th>\n";
			echo "	<th>".$text['label-status']."</th>\n";
			echo "</tr>\n";

		//order the array
			require_once "includes/classes/array_order.php";
			$order = new array_order();
			$registrations = $order->sort($registrations, 'domain', 'user');

		//display the array
			if (count($registrations) > 0) {
				foreach ($registrations as $row) {
					echo "<tr>\n";
					if (count($_SESSION["domains"]) > 1) {
						echo "<td class='".$row_style[$c]."'>&nbsp;".$row['sip-auth-realm']."&nbsp;</td>\n";
					}
					//<td class='".$row_style[$c]."'>&nbsp;".$row['call-id']."&nbsp;</td>\n";
					//echo "	<td class='".$row_style[$c]."'>&nbsp;".$row['user']."&nbsp;</td>\n";
					//echo "	<td class='".$row_style[$c]."'>&nbsp;".$row['contact']."&nbsp;</td>\n";
					echo "	<td class='".$row_style[$c]."'>&nbsp;".$row['sip-auth-user']."&nbsp;</td>\n";
					echo "	<td class='".$row_style[$c]."'>&nbsp;".htmlentities($row['agent'])."&nbsp;</td>\n";
					//echo "	<td class='".$row_style[$c]."'>&nbsp;".$row['host']."&nbsp;</td>\n";
					echo "	<td class='".$row_style[$c]."'>&nbsp;<a href='http://".$row['network-ip']."' target='_blank'>".$row['network-ip']."</a>&nbsp;</td>\n";
					echo "	<td class='".$row_style[$c]."'>&nbsp;".$row['network-port']."&nbsp;</td>\n";
					//echo "	<td class='".$row_style[$c]."'>&nbsp;".$row['mwi-account']."&nbsp;</td>\n";
					echo "	<td class='".$row_style[$c]."'>&nbsp;".$row['status']."&nbsp;</td>\n";
					echo "</tr>\n";
					if ($c==0) { $c=1; } else { $c=0; }
				}
			}
			echo "</table>\n";

			fclose($fp);
			unset($xml);
	}

//add some space at the bottom of the page
	echo "<br />\n";
	echo "<br />\n";
	echo "<br />\n";

//get the footer
	require_once "includes/footer.php";

?>