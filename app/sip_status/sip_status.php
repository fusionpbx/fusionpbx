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

if (permission_exists('system_status_sofia_status')
	|| permission_exists('system_status_sofia_status_profile')
	|| if_group("superadmin")) {
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
//define variables
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

if ($_GET['a'] == "download") {
	if ($_GET['t'] == "cdrcsv") {
		$tmp = $_SESSION['switch']['log']['dir'].'/cdr-csv/';
		$filename = 'Master.csv';
	}
	if ($_GET['t'] == "backup") {
		$tmp = $backup_dir.'/';
		$filename = 'backup.tgz';
		if (!is_dir($backup_dir.'/')) {
			exec("mkdir ".$backup_dir."/");
		}
		$parent_dir = realpath($_SESSION['switch']['base']['dir']."/..");
		chdir($parent_dir);
		shell_exec('tar cvzf freeswitch '.$backup_dir.'/backup.tgz');
	}
	session_cache_limiter('public');
	$fd = fopen($tmp.$filename, "rb");
	header("Content-Type: binary/octet-stream");
	header("Content-Length: " . filesize($tmp.$filename));
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	fpassthru($fd);
	exit;
}

require_once "includes/header.php";

$msg = $_GET["savemsg"];
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if (!$fp) {
	$msg = "<div align='center'>".$text['error-event-socket']."<br /></div>"; 
}
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

//sofia status
	if ($fp && permission_exists('system_status_sofia_status')) {
		$cmd = "api sofia xmlstatus";
		$xml_response = trim(event_socket_request($fp, $cmd));
		try {
			$xml = new SimpleXMLElement($xml_response);
		}
		catch(Exception $e) {
			echo $e->getMessage();
		}
		echo "<br />\n";
		echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<td width='50%'>\n";
		echo "  <b>".$text['title-sofia-status']."</b> \n";
		echo "</td>\n";
		echo "<td width='50%' align='right'>\n";
		echo "  <input type='button' class='btn' value='Reload ACL' onclick=\"document.location.href='cmd.php?cmd=api+reloadacl';\" />\n";
		echo "  <input type='button' class='btn' value='Reload XML' onclick=\"document.location.href='cmd.php?cmd=api+reloadxml';\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<table width='100%' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<th>Name</th>\n";
		echo "<th>Type</th>\n";
		echo "<th>Data</th>\n";
		echo "<th>State</th>\n";
		echo "</tr>\n";
		foreach ($xml->profile as $row) {
			//print_r($row);
			echo "<tr>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->name."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->type."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->data."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->state."</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
		foreach ($xml->gateway as $row) {
			//print_r($row);
			echo "<tr>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->name."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->type."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->data."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->state."</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
		foreach ($xml->alias as $row) {
			//print_r($row);
			echo "<tr>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->name."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->type."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->data."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".$row->state."</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
		echo "</table>\n";
		unset($xml);
		echo "<br />\n\n";
	}

//sofia status profile
	if (permission_exists('system_status_sofia_status_profile')) {
		foreach (ListFiles($_SESSION['switch']['conf']['dir'].'/sip_profiles') as $key=>$sip_profile_file){
			if (substr($sip_profile_file, -4) == ".xml") {
				$sip_profile_name = str_replace(".xml", "", $sip_profile_file);
				if ($fp) {
					$cmd = "api sofia xmlstatus profile ".$sip_profile_name."";
					$xml_response = trim(event_socket_request($fp, $cmd));
					if ($xml_response == "Invalid Profile!") { $xml_response = "<error_msg>Invalid Profile!</error_msg>"; }
					$xml_response = str_replace("<profile-info>", "<profile_info>", $xml_response);
					$xml_response = str_replace("</profile-info>", "</profile_info>", $xml_response);
					try {
						$xml = new SimpleXMLElement($xml_response);
					}
					catch(Exception $e) {
						echo $e->getMessage();
						exit;
					}
					echo "<br />\n";
					echo "<br />\n";
					echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
					echo "<tr>\n";
					echo "<td width='50%'>\n";
					echo "  <b>".$text['title-sofia-status-profile']." $sip_profile_name</b> \n";
					echo "</td>\n";
					echo "<td width='50%' align='right'>\n";
					echo "  <input type='button' class='btn' value='registrations' onclick=\"document.location.href='".PROJECT_PATH."/app/registrations/status_registrations.php?show_reg=1&profile=".$sip_profile_name."';\" />\n";
					echo "  <input type='button' class='btn' value='start' onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".$sip_profile_name."+start';\" />\n";
					echo "  <input type='button' class='btn' value='stop' onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".$sip_profile_name."+stop';\" />\n";
					echo "  <input type='button' class='btn' value='restart' onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".$sip_profile_name."+restart';\" />\n";
					echo "  <input type='button' class='btn' value='rescan' onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".$sip_profile_name."+rescan';\" />\n";
					if ($sip_profile_name != "external") {
						echo "  <input type='button' class='btn' value='flush_inbound_reg' onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".$sip_profile_name."+flush_inbound_reg';\" />\n";
					}
					echo "</td>\n";
					echo "</tr>\n";
					echo "</table>\n";

					echo "<table width='100%' cellspacing='0' cellpadding='5'>\n";
					echo "<tr>\n";
					echo "<th width='20%'>&nbsp;</th>\n";
					echo "<th>&nbsp;</th>\n";
					echo "</tr>\n";

					foreach ($xml->profile_info as $row) {
						echo "	<tr><td class='vncell'>name</td><td class='vtable'>&nbsp; &nbsp;".$row->name."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>domain-name</td><td class='vtable'>&nbsp; &nbsp;".$row->{'domain-name'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>auto-nat</td><td class='vtable'>&nbsp;".$row->{'auto-nat'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>db-name</td><td class='vtable'>&nbsp;".$row->{'db-name'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>pres-hosts</td><td class='vtable'>&nbsp;".$row->{'pres-hosts'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>dialplan</td><td class='vtable'>&nbsp;".$row->dialplan."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>context</td><td class='vtable'>&nbsp;".$row->context."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>challenge-realm</td><td class='vtable'>&nbsp;".$row->{'challenge-realm'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>rtp-ip</td><td class='vtable'>&nbsp;".$row->{'rtp-ip'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>ext-rtp-ip</td><td class='vtable'>&nbsp;".$row->{'ext-rtp-ip'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>sip-ip</td><td class='vtable'>&nbsp;".$row->{'sip-ip'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>ext-sip-ip</td><td class='vtable'>&nbsp;".$row->{'ext-sip-ip'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>url</td><td class='vtable'>&nbsp;".$row->url."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>bind-url</td><td class='vtable'>&nbsp;".$row->{'bind-url'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>tls-url</td><td class='vtable'>&nbsp;".$row->{'tls-url'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>tls-bind-url</td><td class='vtable'>&nbsp;".$row->{'tls-bind-url'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>hold-music</td><td class='vtable'>&nbsp;".$row->{'hold-music'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>outbound-proxy</td><td class='vtable'>&nbsp;".$row->{'outbound-proxy'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>inbound-codecs</td><td class='vtable'>&nbsp;".$row->{'inbound-codecs'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>outbound-codecs</td><td class='vtable'>&nbsp;".$row->{'outbound-codecs'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>tel-event</td><td class='vtable'>&nbsp;".$row->{'tel-event'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>dtmf-mode</td><td class='vtable'>&nbsp;".$row->{'dtmf-mode'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>cng</td><td class='vtable'>&nbsp;".$row->cng."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>session-to</td><td class='vtable'>&nbsp;".$row->{'session-to'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>max-dialog</td><td class='vtable'>&nbsp;".$row->{'max-dialog'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>nomedia</td><td class='vtable'>&nbsp;".$row->nomedia."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>late-neg</td><td class='vtable'>&nbsp;".$row->{'late-neg'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>proxy-media</td><td class='vtable'>&nbsp;".$row->{'proxy-media'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>aggressive-nat</td><td class='vtable'>&nbsp;".$row->{'aggressive-nat'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>stun-enabled</td><td class='vtable'>&nbsp;".$row->{'stun-enabled'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>stun-auto-disable</td><td class='vtable'>&nbsp;".$row->{'stun-auto-disable'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>user-agent-filter</td><td class='vtable'>&nbsp;".$row->{'user-agent-filter'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>max-registrations-per-extension</td><td class='vtable'>&nbsp;".$row->{'max-registrations-per-extension'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>calls-in</td><td class='vtable'>&nbsp;".$row->{'calls-in'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>calls-out</td><td class='vtable'>&nbsp;".$row->{'calls-out'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>failed-calls-in</td><td class='vtable'>&nbsp;".$row->{'failed-calls-in'}."&nbsp;</td></tr>\n";
						echo "	<tr><td class='vncell'>failed-calls-out</td><td class='vtable'>&nbsp;".$row->{'failed-calls-out'}."&nbsp;</td></tr>\n";
					}
					echo "</table>\n";
					unset($xml);
					echo "<br /><br />\n\n";
				}
			}
		}
	}

//status
	if ($fp && permission_exists('sip_status_switch_status')) {
		$cmd = "api status";
		$response = event_socket_request($fp, $cmd);
		echo "<b>".$text['title-status']."</b><br />\n";
		echo "<pre style=\"font-size: 9pt;\">";
		echo trim($response);
		echo "</pre>\n";
		fclose($fp);
		echo "<br /><br />\n\n";
	}

//include the footer
	require_once "includes/footer.php";

?>