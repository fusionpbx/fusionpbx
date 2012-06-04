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
	Ken Rice <krice@tollfreegateway.com>
	Mark J Crane <markjcrane@fusionpbx.com>
*/
function make_xmpp_xml($input) {
	$xml_out .= "<include>\n";
	$xml_out .= "  <profile type=\"client\">\n";
	$xml_out .= sprintf("    <param name=\"name\" value=\"%s\"/>\n", $input['profile_name']);
	$xml_out .= sprintf("    <param name=\"login\" value=\"%s/talk\"/>\n", $input['profile_username']);
	$xml_out .= sprintf("    <param name=\"password\" value=\"%s\"/>\n", $input['profile_password']);
	$xml_out .= sprintf("    <param name=\"dialplan\" value=\"XML\"/>\n", $input['dialplan']);
	$xml_out .= sprintf("    <param name=\"context\" value=\"%s\"/>\n", $input['context']);
	$xml_out .= "    <param name=\"message\" value=\"Jingle all the way\"/>\n";
	$xml_out .= sprintf("    <param name=\"rtp-ip\" value=\"%s\"/>\n", $input['rtp_ip']);
	$xml_out .= sprintf("    <param name=\"ext-rtp-ip\" value=\"%s\"/>\n", $input['ext_rtp_ip']);
	$xml_out .= sprintf("    <param name=\"auto-login\" value=\"%s\"/>\n", $input['auto_login']);
	$xml_out .= sprintf("    <param name=\"sasl\" value=\"%s\"/>\n", $input['sasl_type']);
	$xml_out .= sprintf("    <param name=\"server\" value=\"%s\"/>\n", $input['xmpp_server']);
	$xml_out .= sprintf("    <param name=\"tls\" value=\"%s\"/>\n", $input['tls_enable']);
	$xml_out .= sprintf("    <param name=\"use-rtp-timer\" value=\"%s\"/>\n", $input['use_rtp_timer']);
	$xml_out .= sprintf("    <param name=\"exten\" value=\"%s\"/>\n", $input['default_exten']);
	$xml_out .= sprintf("    <param name=\"vad\" value=\"%s\"/>\n", $input['vad']);
	$xml_out .= sprintf("    <param name=\"candidate-acl\" value=\"%s\"/>\n", $input['candidate_acl']);
	$xml_out .= sprintf("    <param name=\"local-network-acl\" value=\"%s\"/>\n", $input['local_network_acl']);
	$xml_out .= "  </profile>\n";
	$xml_out .= "</include>\n";

	return $xml_out;
}

?>