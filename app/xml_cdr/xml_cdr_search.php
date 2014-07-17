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
	Copyright (C) 2008-2012
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('xml_cdr_search_advanced')) {
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

require_once "resources/header.php";
echo "<div align='center'>";
echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
echo "<tr class='border'>\n";
echo "	<td align=\"left\">\n";
echo "		<br>";

echo "<form method='post' action='xml_cdr.php'>\n";
echo "<table width='100%' cellpadding='6' cellspacing='0'>\n";

echo "<tr>\n";
echo "<td width='30%' nowrap valign='top'><b>Advanced Search</b></td>\n";
echo "<td width='70%' align='right' valign='top'>";
echo "	<input type='button' class='btn' name='' alt='back' onclick=\"window.location='xml_cdr.php'\" value='Back'>";
echo "	<input type='submit' name='submit' class='btn' value='Search'>";
echo "	<br /><br />";
echo "</td>\n";
echo "</tr>\n";

echo "	<tr>\n";
echo "		<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
echo "			".$text['label-direction']."\n";
echo "		</td>\n";
echo "		<td class='vtable' width='70%' align='left'>\n";
echo "			<select name='direction' class='formfld'>\n";
echo "				<option value=''></option>\n";
if ($direction == "inbound") {
	echo "			<option value='inbound' selected='selected'>".$text['label-inbound']."</option>\n";
}
else {
	echo "			<option value='inbound'>".$text['label-inbound']."</option>\n";
}
if ($direction == "outbound") {
	echo "			<option value='outbound' selected='selected'>".$text['label-outbound']."</option>\n";
}
else {
	echo "			<option value='outbound'>".$text['label-outbound']."</option>\n";
}
if ($direction == "local") {
	echo "			<option value='local' selected='selected'>".$text['label-local']."</option>\n";
}
else {
	echo "			<option value='local'>".$text['label-local']."</option>\n";
}
echo "			</select>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-caller_id_name']."</td>"; //source name
echo "		<td class='vtable'><input type='text' class='formfld' name='caller_id_name' value='$caller_id_name'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-caller_id_number']."</td>"; //source number
echo "		<td class='vtable'><input type='text' class='formfld' name='caller_id_number' value='$caller_id_number'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-destination']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='destination_number' value='$destination_number'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-context']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='context' value='$context'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-start']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='start_stamp' value='$start_stamp'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-answer']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='answer_stamp' value='$answer_stamp'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-end_stamp']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='end_stamp' value='$end_stamp'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-duration']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='duration' value='$duration'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-billsec']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='billsec' value='$billsec'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-hangup_cause']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='hangup_cause' value='$hangup_cause'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-uuid']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='uuid' value='$uuid'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-bridge_uuid']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='bleg_uuid' value='$bridge_uuid'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-accountcode']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='accountcode' value='$accountcode'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-read_codec']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='read_codec' value='$read_codec'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-write_codec']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='write_codec' value='$write_codec'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-remote_media_ip']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='remote_media_ip' value='$remote_media_ip'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-network_addr']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='network_addr' value='$network_addr'></td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td colspan='2' align='right'><input type='submit' name='submit' class='btn' value='".$text['button-search']."'></td>";
echo "	</tr>";
echo "</table>";
echo "</form>";

echo "	</td>";
echo "	</tr>";
echo "</table>";
echo "</div>";

require_once "resources/footer.php";

?>