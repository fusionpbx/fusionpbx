<?php
/* $Id$ */
/*
	click_to_call.php
	Copyright (C) 2008, 2021 Mark J Crane
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('click_to_call_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include the header
	require_once "resources/header.php";

//send the call
	if (is_array($_GET) && isset($_GET['src']) && isset($_GET['dest'])) {

		//retrieve submitted variables
			$src = check_str($_GET['src']);
			$src_cid_name = check_str($_GET['src_cid_name']);
			$src_cid_number = check_str($_GET['src_cid_number']);

			$dest = check_str($_GET['dest']);
			$dest_cid_name = check_str($_GET['dest_cid_name']);
			$dest_cid_number = check_str($_GET['dest_cid_number']);

			$auto_answer = check_str($_GET['auto_answer']); //true,false
			$rec = check_str($_GET['rec']); //true,false
			$ringback = check_str($_GET['ringback']);
			$context = $_SESSION['domain_name'];

		//clean up variable values
			$src = str_replace(array('(',')',' '), '', $src);
			$dest = (strpbrk($dest, '@') != FALSE) ? str_replace(array('(',')',' '), '', $dest) : str_replace(array('.','(',')','-',' '), '', $dest); //don't strip periods or dashes in sip-uri calls, only phone numbers

		//adjust variable values
			$sip_auto_answer = ($auto_answer == "true") ? ",sip_auto_answer=true" : null;

		//mozilla thunderbird TBDialout workaround (seems it can only handle the first %NUM%)
			$dest = ($dest == "%NUM%") ? $src_cid_number : $dest;

		//translate ringback
			switch ($ringback) {
				case "music": $ringback_value = "\'local_stream://moh\'"; break;
				case "uk-ring": $ringback_value = "\'%(400,200,400,450);%(400,2200,400,450)\'"; break;
				case "fr-ring": $ringback_value = "\'%(1500,3500,440.0,0.0)\'"; break;
				case "pt-ring": $ringback_value = "\'%(1000,5000,400.0,0.0)\'"; break;
				case "rs-ring": $ringback_value = "\'%(1000,4000,425.0,0.0)\'"; break;
				case "it-ring": $ringback_value = "\'%(1000,4000,425.0,0.0)\'"; break;
				case "de-ring": $ringback_value = "\'%(1000,4000,425.0,0.0)\'"; break;
				case "us-ring":
				default:
					$ringback = 'us-ring';
					$ringback_value = "\'%(2000,4000,440.0,480.0)\'";
			}

		//create the even socket connection and send the event socket command
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if (!$fp) {
				//error message
				echo "<div align='center'><strong>Connection to Event Socket failed.</strong></div>";
			}

		//set call uuid
			$origination_uuid = trim(event_socket_request($fp, "api create_uuid"));

		//add record path and name
			if ($rec == "true") {
				$record_path = $_SESSION['switch']['recordings']['dir']."/".$_SESSION['domain_name']."/archive/".date("Y")."/".date("M")."/".date("d");
				if (isset($_SESSION['recordings']['extension']['text'])) {
					$record_extension = $_SESSION['recordings']['extension']['text'];
				}
				else {
					$record_extension = 'wav';
				}
				if (isset($_SESSION['recordings']['template']['text'])) {
					//${year}${month}${day}-${caller_id_number}-${caller_destination}-${uuid}.${record_extension}
					$record_name = $_SESSION['recordings']['template']['text'];
					$record_name = str_replace('${year}', date("Y"), $record_name);
					$record_name = str_replace('${month}', date("M"), $record_name);
					$record_name = str_replace('${day}', date("d"), $record_name);
					$record_name = str_replace('${source}', $src, $record_name);
					$record_name = str_replace('${caller_id_name}', $src_cid_name, $record_name);
					$record_name = str_replace('${caller_id_number}', $src_cid_number, $record_name);
					$record_name = str_replace('${caller_destination}', $dest, $record_name);
					$record_name = str_replace('${destination}', $dest, $record_name);
					$record_name = str_replace('${uuid}', $origination_uuid, $record_name);
					$record_name = str_replace('${record_extension}', $record_extension, $record_name);
				}
				else {
					$record_name = $origination_uuid.'.'.$record_extension;
				}
			}

		//determine call direction
			$dir = (user_exists($dest)) ? 'local' : 'outbound';

		//define a leg - set source to display the defined caller id name and number
			$source_common = "{";
			$source_common .= "click_to_call=true";
			$source_common .= ",origination_caller_id_name='".$src_cid_name."'";
			$source_common .= ",origination_caller_id_number=".$src_cid_number;
			$source_common .= ",instant_ringback=true";
			$source_common .= ",ringback=".$ringback_value;
			$source_common .= ",presence_id=".$src."@".$_SESSION['domains'][$domain_uuid]['domain_name'];
			$source_common .= ",call_direction=".$dir;
			if ($rec == "true") {
				$source_common .= ",record_path='".$record_path."'";
				$source_common .= ",record_name='".$record_name."'";
			}

			if (user_exists($src)) {
				//source is a local extension
				$source = $source_common.$sip_auto_answer.
					",domain_uuid=".$domain_uuid.
					",domain_name=".$_SESSION['domains'][$domain_uuid]['domain_name']."}user/".$src."@".$_SESSION['domains'][$domain_uuid]['domain_name'];
			}
			else {
				//source is an external number
				$bridge_array = outbound_route_to_bridge($_SESSION['domain_uuid'], $src);
				$source = $source_common."}".$bridge_array[0];
			}
			unset($source_common);

		//define b leg - set destination to display the defined caller id name and number
			$destination_common = " &bridge({origination_caller_id_name='".$dest_cid_name."',origination_caller_id_number=".$dest_cid_number;
			if (user_exists($dest)) {
				//destination is a local extension
				if (strpbrk($dest, '@') != FALSE) { //sip-uri
					$switch_cmd = $destination_common.",call_direction=outbound}sofia/external/".$dest.")";
				}
				else { //not sip-uri
					$switch_cmd = " &transfer('".$dest." XML ".$context."')";
				}
			}
			else {
				//local extension (source) > external number (destination)
				if (user_exists($src) && strlen($dest_cid_number) == 0) {
					//retrieve outbound caller id from the (source) extension
					$sql = "select outbound_caller_id_name, outbound_caller_id_number from v_extensions where domain_uuid = :domain_uuid and extension = :src ";
					$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
					$parameters['src'] = $src;
					$database = new database;
					$result = $database->select($sql, $parameters, 'all');
					foreach ($result as &$row) {
						$dest_cid_name = $row["outbound_caller_id_name"];
						$dest_cid_number = $row["outbound_caller_id_number"];
						break; //limit to 1 row
					}
				}
				if (permission_exists('click_to_call_call')) {
					if (strpbrk($dest, '@') != FALSE) { //sip-uri
						$switch_cmd = $destination_common.",call_direction=outbound}sofia/external/".$dest.")";
					}
					else { //not sip-uri
						$bridge_array = outbound_route_to_bridge($_SESSION['domain_uuid'], $dest);
						//$switch_cmd = $destination_common."}".$bridge_array[0].")";  // wouldn't set cdr destination correctly, so below used instead
						$switch_cmd = " &transfer('".$dest." XML ".$context."')";
					}
				}
			}
			unset($destination_common);

		//create the even socket connection and send the event socket command
			$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
			if (!$fp) {
				//error message
				echo "<div align='center'><strong>Connection to Event Socket failed.</strong></div>";
			}
			else {
				//display the last command
					$switch_cmd = "api originate ".$source.$switch_cmd;
					echo "<div align='center'><strong>".escape($src)." has called ".escape($dest)."</strong></div>\n";
				//show the command result
				$result = trim(event_socket_request($fp, $switch_cmd));
				if (substr($result, 0,3) == "+OK") {
					//$uuid = substr($result, 4);
					if ($rec == "true") {
						//use the server's time zone to ensure it matches the time zone used by freeswitch
							date_default_timezone_set($_SESSION['time_zone']['system']);
						//create the api record command and send it over event socket
							if (is_uuid($origination_uuid) && file_exists($record_path)) {
								$switch_cmd = "api uuid_record ".$origination_uuid." start ".$record_path."/".$record_name;
							}
							$result2 = trim(event_socket_request($fp, $switch_cmd));
					}
				}
				echo "<div align='center'><br />".escape($result)."<br /><br /></div>\n";
			}
	}

//show html form
	echo "	<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "	<tr>\n";
	echo "	<td align='left'>\n";
	echo "		<span class=\"title\">\n";
	echo "			<strong>".$text['label-click2call']."</strong>\n";
	echo "		</span>\n";
	echo "	</td>\n";
	echo "	<td align='right'>\n";
	echo "		&nbsp;\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "	<td align='left' colspan='2'>\n";
	echo "		<span class=\"vexpl\">\n";
	echo "			".$text['desc-click2call']."\n";
	echo "		</span>\n";
	echo "	</td>\n";
	echo "\n";
	echo "	</tr>\n";
	echo "	</table>";

	echo "	<br />";

	echo "<form method=\"get\">\n";
	echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'\n";
	echo "<tr>\n";
	echo "	<td class='vncellreq' width='40%'>".$text['label-src-caller-id-nam']."</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<input name=\"src_cid_name\" value='".escape($src_cid_name)."' class='formfld'>\n";
	echo "		<br />\n";
	echo "		".$text['desc-src-caller-id-nam']."\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td class='vncellreq'>".$text['label-src-caller-id-num']."</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<input name=\"src_cid_number\" value='".escape($src_cid_number)."' class='formfld'>\n";
	echo "		<br />\n";
	echo "		".$text['desc-src-caller-id-num']."\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td class='vncell' width='40%'>".$text['label-dest-caller-id-nam']."</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<input name=\"dest_cid_name\" value='".escape($dest_cid_name)."' class='formfld'>\n";
	echo "		<br />\n";
	echo "		".$text['desc-dest-caller-id-nam']."\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td class='vncell'>".$text['label-dest-caller-id-num']."</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<input name=\"dest_cid_number\" value='".escape($dest_cid_number)."' class='formfld'>\n";
	echo "		<br />\n";
	echo "		".$text['desc-dest-caller-id-num']."\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td class='vncellreq'>".$text['label-src-num']."</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<input name=\"src\" value='".escape($src)."' class='formfld'>\n";
	echo "		<br />\n";
	echo "		".$text['desc-src-num']."\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td class='vncellreq'>".$text['label-dest-num']."</td>\n";
	echo "	<td class='vtable' align='left'>\n";
	echo "		<input name=\"dest\" value='".escape($dest)."' class='formfld'>\n";
	echo "		<br />\n";
	echo "		".$text['desc-dest-num']."\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo" <tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-auto-answer']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='auto_answer'>\n";
	echo "    <option value=''></option>\n";
	if ($auto_answer == "true") {
			echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
			echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($auto_answer == "false") {
			echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
			echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['desc-auto-answer']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-record']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='rec'>\n";
	echo "    <option value=''></option>\n";
	if ($rec == "true") {
		echo "    <option value='true' selected='selected'>".$text['label-true']."</option>\n";
	}
	else {
		echo "    <option value='true'>".$text['label-true']."</option>\n";
	}
	if ($rec == "false") {
		echo "    <option value='false' selected='selected'>".$text['label-false']."</option>\n";
	}
	else {
		echo "    <option value='false'>".$text['label-false']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['desc-record']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "    ".$text['label-ringback']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='ringback'>\n";
	echo "    <option value=''></option>\n";
	if ($ringback == "us-ring") {
		echo "    <option value='us-ring' selected='selected'>".$text['opt-usring']."</option>\n";
	}
	else {
		echo "    <option value='us-ring'>".$text['opt-usring']."</option>\n";
	}
	if ($ringback == "fr-ring") {
		echo "    <option value='fr-ring' selected='selected'>".$text['opt-frring']."</option>\n";
	}
	else {
		echo "    <option value='fr-ring'>".$text['opt-frring']."</option>\n";
	}
	if ($ringback == "pt-ring") {
		echo "    <option value='pt-ring' selected='selected'>".$text['opt-ptring']."</option>\n";
	}
	else {
		echo "    <option value='pt-ring'>".$text['opt-ptring']."</option>\n";
	}
	if ($ringback == "uk-ring") {
		echo "    <option value='uk-ring' selected='selected'>".$text['opt-ukring']."</option>\n";
	}
	else {
		echo "    <option value='uk-ring'>".$text['opt-ukring']."</option>\n";
	}
	if ($ringback == "rs-ring") {
		echo "    <option value='rs-ring' selected='selected'>".$text['opt-rsring']."</option>\n";
	}
	else {
		echo "    <option value='rs-ring'>".$text['opt-rsring']."</option>\n";
	}
	if ($ringback == "ru-ring") {
		echo "    <option value='ru-ring' selected='selected'>".$text['opt-ruring']."</option>\n";
	}
	else {
		echo "    <option value='ru-ring'>".$text['opt-ruring']."</option>\n";
	}
	if ($ringback == "it-ring") {
		echo "    <option value='it-ring' selected='selected'>".$text['opt-itring']."</option>\n";
	}
	else {
		echo "    <option value='it-ring'>".$text['opt-itring']."</option>\n";
	}
	if ($ringback == "de-ring") {
		echo "    <option value='de-ring' selected='selected'>".$text['opt-dering']."</option>\n";
	}
	else {
		echo "    <option value='de-ring'>".$text['opt-dering']."</option>\n";
	}
	if ($ringback == "music") {
		echo "    <option value='music' selected='selected'>".$text['opt-moh']."</option>\n";
	}
	else {
		echo "    <option value='music'>".$text['opt-moh']."</option>\n";
	}
	echo "    </select>\n";
	echo "<br />\n";
	echo $text['desc-ringback']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "	<td colspan='2' align='right'>\n";
	echo "		<br>";
	echo "		<input type=\"submit\" class='btn' value=\"".$text['button-call']."\">\n";
	echo "	</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br><br>";
	echo "</form>";

//show the footer
	require_once "resources/footer.php";

?>
