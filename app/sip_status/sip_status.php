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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
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
	if (permission_exists('system_status_sofia_status') || permission_exists('system_status_sofia_status_profile') || if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//create event socket
	$socket_ip = $_SESSION['event_socket_ip_address'] != '0.0.0.0' ? $_SESSION['event_socket_ip_address'] : '127.0.0.1';
	$fp = event_socket_create($socket_ip, $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
	if (!$fp) {
		message::add($text['error-event-socket'], 'negative', 5000);
	}

//get the gateways
	$sql = "select g.domain_uuid, g.gateway, g.gateway_uuid, d.domain_name ";
	$sql .= "from v_gateways as g left ";
	$sql .= "outer join v_domains as d on d.domain_uuid = g.domain_uuid";
	$database = new database;
	$gateways = $database->select($sql, null, 'all');
	unset($sql);

//get the sip profiles
	if ($fp) {
		$hostname = trim(event_socket_request($fp, 'api switchname'));
	}
	$sql = "select sip_profile_uuid, sip_profile_name from v_sip_profiles ";
	$sql .= "where sip_profile_enabled = 'true' ";
	if ($hostname) {
		$sql .= "and (sip_profile_hostname = :sip_profile_hostname ";
		$sql .= "or sip_profile_hostname = '' ";
		$sql .= "or sip_profile_hostname is null) ";
		$parameters['sip_profile_hostname'] = $hostname;
	}
	$sql .= "order by sip_profile_name asc ";
	$database = new database;
	$rows = $database->select($sql, $parameters, 'all');
	if (is_array($rows) && @sizeof($rows) != 0) {
		foreach ($rows as $row) {
			$sip_profiles[$row['sip_profile_name']] = $row['sip_profile_uuid'];
		}
	}
	unset($sql, $parameters, $rows, $row);

//get status
	try {
		$cmd = "api sofia xmlstatus";
		$xml_response = trim(event_socket_request($fp, $cmd));
		if ($xml_response) {
			$xml = new SimpleXMLElement($xml_response);
		}
	}
	catch(Exception $e) {
		$message = $e->getMessage();
		message::add($message, 'negative', 5000);
	}
	try {
		$cmd = "api sofia xmlstatus gateway";
		$xml_response = trim(event_socket_request($fp, $cmd));
		if ($xml_response) {
			$xml_gateways = new SimpleXMLElement($xml_response);
		}
	}
	catch(Exception $e) {
		$message = $e->getMessage();
		message::add($message, 'negative', 5000);
	}

//include the header
	$document['title'] = $text['title-sip_status'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-sip_status']."</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('system_status_sofia_status')) {
		echo button::create(['type'=>'button','label'=>$text['button-flush_cache'],'icon'=>'eraser','collapse'=>'hide-xs','link'=>'cmd.php?action=cache-flush']);
		echo button::create(['type'=>'button','label'=>$text['button-reload_acl'],'icon'=>'shield-alt','collapse'=>'hide-xs','link'=>'cmd.php?action=reloadacl']);
		echo button::create(['type'=>'button','label'=>$text['button-reload_xml'],'icon'=>'code','collapse'=>'hide-xs','link'=>'cmd.php?action=reloadxml']);
	}
	echo button::create(['type'=>'button','label'=>$text['button-refresh'],'icon'=>$_SESSION['theme']['button_icon_refresh'],'collapse'=>'hide-xs','style'=>'margin-left: 15px;','link'=>'sip_status.php']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-sip_status']."\n";
	echo "<br /><br />\n";

	if (permission_exists('system_status_sofia_status')) {
		echo "<b><a href='javascript:void(0);' onclick=\"$('#sofia_status').slideToggle();\">".$text['title-sofia-status']."</a></b>\n";
		echo "<br />\n";

		echo "<div id='sofia_status' style='margin-top: 20px; margin-bottom: 40px;'>";
		echo "<table class='list'>\n";
		echo "<tr class='list-header'>\n";
		echo "	<th>".$text['label-name']."</th>\n";
		echo "	<th>".$text['label-type']."</th>\n";
		echo "	<th class='hide-sm-dn'>".$text['label-data']."</th>\n";
		echo "	<th>".$text['label-state']."</th>\n";
		echo "	<th class='center'>".$text['label-action']."</th>\n";
		echo "</tr>\n";

		//profiles
			if ($xml->profile) {
				foreach ($xml->profile as $row) {
					unset($list_row_url);
					$profile_name = (string) $row->name;
					if (is_uuid($sip_profiles[$profile_name]) && permission_exists('sip_profile_edit')) {
						$list_row_url = PROJECT_PATH."/app/sip_profiles/sip_profile_edit.php?id=".$sip_profiles[$profile_name];
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					echo "	<td>";
					if ($list_row_url) {
						echo "<a href='".$list_row_url."'>".escape($profile_name)."</a>";
					}
					else {
						echo escape($profile_name);
					}
					echo "	</td>\n";
					echo "	<td>".($row->type == 'profile' ? $text['label-profile'] : escape($row->type))."</td>\n";
					echo "	<td class='hide-sm-dn'>".escape($row->data)."</td>\n";
					echo "	<td class='no-wrap'>".escape($row->state)."</td>\n";
					echo "	<td>&nbsp;</td>\n";
					echo "</tr>\n";
				}
			}

		//gateways
			if ($xml_gateways->gateway) {
				foreach ($xml_gateways->gateway as $row) {
					unset($gateway_name, $gateway_domain_name, $list_row_url);

					if (is_array($gateways) && @sizeof($gateways) != 0) {
						foreach($gateways as $field) {
							if ($field["gateway_uuid"] == strtolower($row->name)) {
								$gateway_uuid = $field["gateway_uuid"];
								$gateway_name = $field["gateway"];
								$gateway_domain_name = $field["domain_name"];
								break;
							}
						}
					}
					if ($_SESSION["domain_name"] == $gateway_domain_name) {
						$list_row_url = PROJECT_PATH."/app/gateways/gateway_edit.php?id=".strtolower(escape($row->name));
					}
					echo "<tr class='list-row' href='".$list_row_url."'>\n";
					echo "	<td>";
					if ($_SESSION["domain_name"] == $gateway_domain_name) {
						echo "<a class='hide-sm-dn' href='".$list_row_url."'>".escape($gateway_name)."@".escape($gateway_domain_name)."</a>";
						echo "<a class='hide-md-up' href='".$list_row_url."'>".escape($gateway_name)."@...</a>";
					}
					else if ($gateway_domain_name == '') {
						echo $gateway_name ? escape($gateway_name) : $row->name;
					}
					else {
						echo escape($gateway_name."@".$gateway_domain_name);
					}
					echo "	</td>\n";
					echo "	<td>".$text['label-gateway']."</td>\n";
					echo "	<td class='hide-sm-dn'>".escape($row->to)."</td>\n";
					echo "	<td class='no-wrap'>".escape($row->state)."</td>\n";
					echo "	<td class='center no-link'>";
					echo button::create(['type'=>'button','class'=>'link','label'=>$text['button-stop'],'link'=>"cmd.php?profile=".urlencode($row->profile)."&gateway=".urlencode(($gateway_uuid ? $gateway_uuid : $row->name))."&action=killgw"]);
					echo "	</td>\n";
					echo "</tr>\n";
				}
			}

		//aliases
			if ($xml->alias) {
				foreach ($xml->alias as $row) {
					echo "<tr class='list-row'>\n";
					echo "	<td>".escape($row->name)."</td>\n";
					echo "	<td>".escape($row->type)."</td>\n";
					echo "	<td class='hide-sm-dn'>".escape($row->data)."</td>\n";
					echo "	<td class='no-wrap'>".escape($row->state)."</td>\n";
					echo "	<td>&nbsp;</td>\n";
					echo "</tr>\n";
				}
			}

		echo "</table>\n";
		echo "</div>\n";
		unset($gateways, $xml, $xml_gateways);
	}

//sofia status profile
	if ($fp && permission_exists('system_status_sofia_status_profile')) {
		foreach ($sip_profiles as $sip_profile_name => $sip_profile_uuid) {
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

			echo "<div class='action_bar sub'>\n";
			echo "	<div class='heading'><b><a href='javascript:void(0);' onclick=\"$('#".escape($sip_profile_name)."').slideToggle();\">".$text['title-sofia-status-profile']." ".urlencode($sip_profile_name)."</a></b></div>\n";
			echo "	<div class='actions'>\n";
			echo button::create(['type'=>'button','label'=>$text['button-flush_registrations'],'icon'=>'eraser','collapse'=>'hide-xs','link'=>'cmd.php?profile='.urlencode($sip_profile_name).'&action=flush_inbound_reg']);
			echo button::create(['type'=>'button','label'=>$text['button-registrations'],'icon'=>'phone-alt','collapse'=>'hide-xs','link'=>PROJECT_PATH.'/app/registrations/registrations.php?profile='.urlencode($sip_profile_name)]);
			if ($profile_state == 'stopped') {
				echo button::create(['type'=>'button','label'=>$text['button-start'],'icon'=>$_SESSION['theme']['button_icon_start'],'collapse'=>'hide-xs','link'=>'cmd.php?profile='.urlencode($sip_profile_name).'&action=start']);
			}
			if ($profile_state == 'running') {
				echo button::create(['type'=>'button','label'=>$text['button-stop'],'icon'=>$_SESSION['theme']['button_icon_stop'],'collapse'=>'hide-xs','link'=>'cmd.php?profile='.urlencode($sip_profile_name).'&action=stop']);
			}
			echo button::create(['type'=>'button','label'=>$text['button-restart'],'icon'=>$_SESSION['theme']['button_icon_reload'],'collapse'=>'hide-xs','link'=>'cmd.php?profile='.urlencode($sip_profile_name).'&action=restart']);
			echo button::create(['type'=>'button','label'=>$text['button-rescan'],'icon'=>$_SESSION['theme']['button_icon_search'],'collapse'=>'hide-xs','link'=>'cmd.php?profile='.urlencode($sip_profile_name).'&action=rescan']);
			echo "	</div>\n";
			echo "	<div style='clear: both;'></div>\n";
			echo "</div>\n";

			echo "<div id='".escape($sip_profile_name)."' style='display: none; margin-bottom: 30px;'>";
			echo "<table width='100%' cellspacing='0' cellpadding='5'>\n";
			echo "<tr><th colspan='2' style='font-size: 1px; padding: 0;'>&nbsp;</th></tr>\n";

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
