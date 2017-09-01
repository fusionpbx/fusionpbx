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
	Portions created by the Initial Developer are Copyright (C) 2008-2017
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
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

//get the http values and set them as variables
	$search = check_str($_GET["search"]);

//show the header
	require_once "resources/header.php";
	$document['title'] = $text['header-registrations'];

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
	$profile = trim($_REQUEST["profile"]);
	$search = trim($_REQUEST["search"]);
	$show = trim($_REQUEST["show"]);
	if ($show == "all") { $profile = 'all'; }

//define variables
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

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

		//get the registrations
			$obj = new registrations;
			$registrations = $obj->get($profile);

		//count the registrations
			$registration_count = 0;
			if (count($registrations) > 0) {
				foreach ($registrations as $row) {
					//search 
					$matches = preg_grep ("/$search/i",$row);
					if ($matches != FALSE) {
						$registration_count++;
					}
				}
			}
		//show the registrations
			echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
			echo "<tr>\n";
			echo "<td width='100%'>\n";
			echo "	<b>".$text['header-registrations']." (".$registration_count.")</b>\n";
			echo "</td>\n";
			echo "<td valign='middle' nowrap='nowrap' style='padding-right: 15px'>";
			echo "		<form method='get' action=''>\n";
			echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".$search."'>";
			echo "				<input type='hidden' name='show' value='".$show."'>";
			echo "				<input type='hidden' name='profile' value='".$sip_profile_name."'>";
			echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
			echo "		</form>\n";
			echo "</td>";
			echo "<td valign='top' nowrap='nowrap'>";
			if (permission_exists('registration_all')) {
				if ($show == "all") {
					echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='registrations.php?profile=$sip_profile_name'\" value='".$text['button-back']."'>\n";
				}
				else {
					echo "	<input type='button' class='btn' name='' alt='".$text['button-show_all']."' onclick=\"window.location='registrations.php?profile=$sip_profile_name&show=all'\" value='".$text['button-show_all']."'>\n";
				}
			}
			echo "	<input type='button' class='btn' name='' alt='".$text['button-refresh']."' onclick=\"window.location='registrations.php?search=$search&show=$show'\" value='".$text['button-refresh']."'>\n";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
			echo "<br />\n";

			echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
			echo "<tr>\n";
			echo "	<th>".$text['label-user']."</th>\n";
			echo "	<th>".$text['label-agent']."</th>\n";
			echo "	<th>".$text['label-contact']."</th>\n";
			echo "	<th>".$text['label-lan_ip']."</th>\n";
			echo "	<th>".$text['label-ip']."</th>\n";
			echo "	<th>".$text['label-port']."</th>\n";
			echo "	<th>".$text['label-hostname']."</th>\n";
			echo "	<th>".$text['label-status']."</th>\n";
			echo "	<th>".$text['label-ping']."</th>\n";
			echo "	<th>".$text['label-tools']."&nbsp;</th>\n";
			echo "</tr>\n";

		//order the array
			require_once "resources/classes/array_order.php";
			$order = new array_order();
			$registrations = $order->sort($registrations, 'sip-auth-realm', 'user');

		//display the array
			if (count($registrations) > 0) {
				foreach ($registrations as $row) {
					//search 
						$matches = preg_grep ("/$search/i",$row);
						if ($matches != FALSE) {
							//set the user agent
								$agent = $row['agent'];

							//show the registrations
								echo "<tr>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['user']."&nbsp;</td>\n";
								echo "	<td class='".$row_style[$c]."'>".htmlentities($row['agent'])."&nbsp;</td>\n";
								echo "	<td class='".$row_style[$c]."'>".explode('"',$row['contact'])[1]."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['lan-ip']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['network-ip']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['network-port']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['host']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['status']."</td>\n";
								echo "	<td class='".$row_style[$c]."'>".$row['ping-time']."</td>\n";
								echo "	<td class='".$row_style[$c]."' style='text-align: right;' nowrap='nowrap'>\n";
								echo "		<input type='button' class='btn' value='".$text['button-unregister']."' onclick=\"document.location.href='cmd.php?cmd=unregister&profile=".$row['sip_profile_name']."&show=".$show."&user=".$row['user']."&domain=".$row['sip-auth-realm']."&agent=".urlencode($row['agent'])."';\">\n";
								echo "		<input type='button' class='btn' value='".$text['button-provision']."' onclick=\"document.location.href='cmd.php?cmd=check_sync&profile=".$row['sip_profile_name']."&show=".$show."&user=".$row['user']."&domain=".$row['sip-auth-realm']."&agent=".urlencode($row['agent'])."';\">\n";
								echo "		<input type='button' class='btn' value='".$text['button-reboot']."' onclick=\"document.location.href='cmd.php?cmd=reboot&profile=".$row['sip_profile_name']."&show=".$show."&user=".$row['user']."&domain=".$row['sip-auth-realm']."&agent=".urlencode($row['agent'])."';\">\n";
								echo "	</td>\n";
								echo "</tr>\n";
								if ($c==0) { $c=1; } else { $c=0; }
						}
				}
			}
			echo "</table>\n";

		//close the connection and unset the variable
			fclose($fp);
			unset($xml);
	}

//get the footer
	require_once "resources/footer.php";

?>
