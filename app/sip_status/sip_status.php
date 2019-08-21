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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
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
	$language = new text;
	$text = $language->get();

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

//show the content
	require_once "resources/header.php";
	$document['title'] = $text['title-sip-status'];

	$msg = $_GET["savemsg"];
	if ($_SESSION['event_socket_ip_address'] == "0.0.0.0") {
		$socket_ip = '127.0.0.1';
		$fp = event_socket_create($socket_ip, $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	} else {
		$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	}
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
		echo "<td class='row_style1'><strong>".escape($msg)."</strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}

//get the gateways
	$sql = "select g.domain_uuid, g.gateway, g.gateway_uuid, d.domain_name ";
	$sql .= "from v_gateways as g left ";
	$sql .= "outer join v_domains as d on d.domain_uuid = g.domain_uuid";
	$database = new database;
	$gateways = $database->select($sql, null, 'all');
	unset($sql);

	if ($fp) {
		$hostname = trim(event_socket_request($fp, 'api switchname'));
	}

//get the sip profiles
	$sql = "select sip_profile_name from v_sip_profiles ";
	$sql .= "where sip_profile_enabled = 'true' ";
	if ($hostname) {
		$sql .= "and (sip_profile_hostname = :sip_profile_hostname ";
		$sql .= "or sip_profile_hostname = '' ";
		$sql .= "or sip_profile_hostname is null) ";
		$parameters['sip_profile_hostname'] = $hostname;
	}
	$sql .= "order by sip_profile_name asc ";
	$database = new database;
	$sip_profiles = $database->select($sql, (is_array($parameters) && @sizeof($parameters) != 0 ? $parameters : null), 'all');
	unset($sql, $parameters);

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
		$cmd = "api sofia xmlstatus gateway";
		$xml_response = trim(event_socket_request($fp, $cmd));
		try {
			$xml_gateways = new SimpleXMLElement($xml_response);
		}
		catch(Exception $e) {
			echo $e->getMessage();
		}
		echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<td width='50%'>\n";
		echo "	<b>".$text['header-sip-status']."</b>";
		echo "	<br><br>";
		echo "</td>\n";
		echo "<td width='50%' align='right'>\n";
		echo "  <input type='button' class='btn' value='".$text['button-flush_cache']."' onclick=\"document.location.href='cmd.php?cmd=api+cache+flush';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-reload_acl']."' onclick=\"document.location.href='cmd.php?cmd=api+reloadacl';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-reload_xml']."' onclick=\"document.location.href='cmd.php?cmd=api+reloadxml';\" />\n";
		echo "  <input type='button' class='btn' value='".$text['button-refresh']."' onclick=\"document.location.href='sip_status.php';\" />\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-bottom: 10px;'>\n";
		echo "<tr>\n";
		echo "<td><b><a href='javascript:void(0);' onclick=\"$('#sofia_status').slideToggle();\">".$text['title-sofia-status']."</a></b></td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<div id='sofia_status' style='margin-top: 20px; margin-bottom: 30px;'>";
		echo "<table width='100%' cellspacing='0' border='0'>\n";
		echo "<tr>\n";
		echo "<th>".$text['label-name']."</th>\n";
		echo "<th>".$text['label-type']."</th>\n";
		echo "<th>".$text['label-data']."</th>\n";
		echo "<th>".$text['label-state']."</th>\n";
		echo "<th>".$text['label-action']."</th>\n";
		echo "</tr>\n";
		foreach ($xml->profile as $row) {
			echo "<tr>\n";
			echo "	<td class='".$row_style[$c]."'>".escape($row->name)."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".escape($row->type)."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".escape($row->data)."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".escape($row->state)."</td>\n";
			echo "	<td class='".$row_style[$c]."'>&nbsp;</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
		foreach ($xml_gateways->gateway as $row) {
			$gateway_name = '';
			$gateway_domain_name = '';
			foreach($gateways as $field) {
				if ($field["gateway_uuid"] == strtolower($row->name)) {
					$gateway_name = $field["gateway"];
					$gateway_domain_name = $field["domain_name"];
					break;
				}
			}
			echo "<tr>\n";
			echo "	<td class='".$row_style[$c]."'>";
			if ($_SESSION["domain_name"] == $gateway_domain_name) {
				echo "<a href='".PROJECT_PATH."/app/gateways/gateway_edit.php?id=".strtolower(escape($row->name))."'>".escape($gateway_name)."@".escape($gateway_domain_name)."</a>";
			}
			else if ($gateway_domain_name == '') {
				echo $gateway_name ? $gateway_name : $row->name;
			}
			else {
				echo $gateway_name."@".$gateway_domain_name;
			}
			echo "	</td>\n";
			echo "	<td class='".$row_style[$c]."'>Gateway</td>\n";
			echo "	<td class='".$row_style[$c]."'>".escape($row->to)."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".escape($row->state)."</td>\n";
			echo "	<td class='".$row_style[$c]."'><a onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".escape($row->profile)."+killgw+".escape($row->name)."';\" />".$text['button-stop']."</a></td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
		foreach ($xml->alias as $row) {
			//print_r($row);
			echo "<tr>\n";
			echo "	<td class='".$row_style[$c]."'>".escape($row->name)."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".escape($row->type)."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".escape($row->data)."</td>\n";
			echo "	<td class='".$row_style[$c]."'>".escape($row->state)."</td>\n";
			echo "	<td class='".$row_style[$c]."'>&nbsp;</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		}
		echo "</table>\n";
		echo "</div>\n";
		unset($xml);
	}

//sofia status profile
	if (permission_exists('system_status_sofia_status_profile')) {
		foreach ($sip_profiles as $row) {
			$sip_profile_name = $row["sip_profile_name"];

			if ($fp) {
				$cmd = "api sofia xmlstatus profile ".$sip_profile_name."";
				$xml_response = trim(event_socket_request($fp, $cmd));
				if ($xml_response == "Invalid Profile!") {
					$xml_response = "<error_msg>Invalid Profile!</error_msg>";
					$profile_state = 'stopped';
				}
				else {
					$profile_state = 'running';
				}
				$xml_response = str_replace("<profile-info>", "<profile_info>", $xml_response);
				$xml_response = str_replace("</profile-info>", "</profile_info>", $xml_response);
				try {
					$xml = new SimpleXMLElement($xml_response);
				}
				catch(Exception $e) {
					echo $e->getMessage();
					exit;
				}
				echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' style='margin-bottom: 10px;'>\n";
				echo "<tr>\n";
				echo "<td width='100%'>\n";
				echo "  <b><a href='javascript:void(0);' onclick=\"$('#".escape($sip_profile_name)."').slideToggle();\">".$text['title-sofia-status-profile']." ".escape($sip_profile_name)."</a></b> \n";
				echo "</td>\n";
				echo "<td align='right' nowrap>\n";
				if ($sip_profile_name != "external") {
					echo "  <input type='button' class='btn' value='".$text['button-flush_registrations']."' onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".escape($sip_profile_name)."+flush_inbound_reg';\" />\n";
				}
				echo "  <input type='button' class='btn' value='".$text['button-registrations']."' onclick=\"document.location.href='".PROJECT_PATH."/app/registrations/registrations.php?show_reg=1&profile=".escape($sip_profile_name)."';\" />\n";
				if ($profile_state == 'stopped') {
					echo "  <input type='button' class='btn' value='".$text['button-start']."' onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".escape($sip_profile_name)."+start';\" />\n";
				}
				if ($profile_state == 'running') {
					echo "  <input type='button' class='btn' value='".$text['button-stop']."' onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".escape($sip_profile_name)."+stop';\" />\n";
				}
				echo "  <input type='button' class='btn' value='".$text['button-restart']."' onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".escape($sip_profile_name)."+restart';\" />\n";
				echo "  <input type='button' class='btn' value='".$text['button-rescan']."' onclick=\"document.location.href='cmd.php?cmd=api+sofia+profile+".escape($sip_profile_name)."+rescan';\" />\n";
				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";

				echo "<div id='".escape($sip_profile_name)."' style='display: none; margin-bottom: 30px;'>";
				echo "<table width='100%' cellspacing='0' cellpadding='5'>\n";
				echo "<tr>\n";
				echo "<th width='20%'>&nbsp;</th>\n";
				echo "<th>&nbsp;</th>\n";
				echo "</tr>\n";

				foreach ($xml->profile_info as $row) {
					echo "	<tr><td class='vncell'>name</td><td class='vtable'>&nbsp; &nbsp;".escape($row->name)."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>domain-name</td><td class='vtable'>&nbsp; &nbsp;".escape($row->{'domain-name'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>auto-nat</td><td class='vtable'>&nbsp;".escape($row->{'auto-nat'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>db-name</td><td class='vtable'>&nbsp;".escape($row->{'db-name'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>pres-hosts</td><td class='vtable'>&nbsp;".escape($row->{'pres-hosts'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>dialplan</td><td class='vtable'>&nbsp;".escape($row->dialplan)."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>context</td><td class='vtable'>&nbsp;".escape($row->context)."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>challenge-realm</td><td class='vtable'>&nbsp;".escape($row->{'challenge-realm'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>rtp-ip</td><td class='vtable'>&nbsp;".escape($row->{'rtp-ip'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>ext-rtp-ip</td><td class='vtable'>&nbsp;".escape($row->{'ext-rtp-ip'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>sip-ip</td><td class='vtable'>&nbsp;".escape($row->{'sip-ip'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>ext-sip-ip</td><td class='vtable'>&nbsp;".escape($row->{'ext-sip-ip'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>url</td><td class='vtable'>&nbsp;".escape($row->url)."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>bind-url</td><td class='vtable'>&nbsp;".escape($row->{'bind-url'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>tls-url</td><td class='vtable'>&nbsp;".escape($row->{'tls-url'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>tls-bind-url</td><td class='vtable'>&nbsp;".escape($row->{'tls-bind-url'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>hold-music</td><td class='vtable'>&nbsp;".escape($row->{'hold-music'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>outbound-proxy</td><td class='vtable'>&nbsp;".escape($row->{'outbound-proxy'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>inbound-codecs</td><td class='vtable'>&nbsp;".escape($row->{'inbound-codecs'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>outbound-codecs</td><td class='vtable'>&nbsp;".$row->{'outbound-codecs'}."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>tel-event</td><td class='vtable'>&nbsp;".escape($row->{'tel-event'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>dtmf-mode</td><td class='vtable'>&nbsp;".escape($row->{'dtmf-mode'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>cng</td><td class='vtable'>&nbsp;".escape($row->cng)."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>session-to</td><td class='vtable'>&nbsp;".escape($row->{'session-to'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>max-dialog</td><td class='vtable'>&nbsp;".escape($row->{'max-dialog'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>nomedia</td><td class='vtable'>&nbsp;".escape($row->{'nomedia'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>late-neg</td><td class='vtable'>&nbsp;".escape($row->{'late-neg'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>proxy-media</td><td class='vtable'>&nbsp;".escape($row->{'proxy-media'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>aggressive-nat</td><td class='vtable'>&nbsp;".escape($row->{'aggressive-nat'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>stun-enabled</td><td class='vtable'>&nbsp;".escape($row->{'stun-enabled'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>stun-auto-disable</td><td class='vtable'>&nbsp;".escape($row->{'stun-auto-disable'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>user-agent-filter</td><td class='vtable'>&nbsp;".escape($row->{'user-agent-filter'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>max-registrations-per-extension</td><td class='vtable'>&nbsp;".escape($row->{'max-registrations-per-extension'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>calls-in</td><td class='vtable'>&nbsp;".escape($row->{'calls-in'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>calls-out</td><td class='vtable'>&nbsp;".escape($row->{'calls-out'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>failed-calls-in</td><td class='vtable'>&nbsp;".escape($row->{'failed-calls-in'})."&nbsp;</td></tr>\n";
					echo "	<tr><td class='vncell'>failed-calls-out</td><td class='vtable'>&nbsp;".escape($row->{'failed-calls-out'})."&nbsp;</td></tr>\n";
				}
				echo "</table>\n";
				echo "</div>";
				unset($xml);
			}
		}
	}

//status
	if ($fp && permission_exists('sip_status_switch_status')) {
		$cmd = "api status";
		$response = event_socket_request($fp, $cmd);
		echo "<b><a href='javascript:void(0);' onclick=\"$('#status').slideToggle();\">".$text['title-status']."</a></b>\n";
		echo "<div id='status' style='margin-top: 20px; font-size: 9pt;'>";
		echo "<pre>";
		echo trim(escape($response));
		echo "</pre>\n";
		echo "</div>";
		fclose($fp);
	}

//include the footer
	require_once "resources/footer.php";

?>
