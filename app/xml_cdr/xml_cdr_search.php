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
require_once "includes/require.php";
require_once "includes/checkauth.php";
if (permission_exists('xml_cdr_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if (count($_POST)>0) {
	$cdr_id = $_POST["cdr_id"];
	$caller_id_name = $_POST["caller_id_name"];
	$caller_id_number = $_POST["caller_id_number"];
	$destination_number = $_POST["destination_number"];
	$context = $_POST["context"];
	$start_stamp = $_POST["start_stamp"];
	$answer_stamp = $_POST["answer_stamp"];
	$end_stamp = $_POST["end_stamp"];
	$duration = $_POST["duration"];
	$billsec = $_POST["billsec"];
	$hangup_cause = $_POST["hangup_cause"];
	$uuid = $_POST["uuid"];
	$bleg_uuid = $_POST["bleg_uuid"];
	$accountcode = $_POST["accountcode"];
	$read_codec = $_POST["read_codec"];
	$write_codec = $_POST["write_codec"];
	$remote_media_ip = $_POST["remote_media_ip"];
	$network_addr = $_POST["network_addr"];
}
else {

	require_once "includes/header.php";
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"left\">\n";
	echo "		<br>";

	echo "<form method='post' action='xml_cdr.php'>\n";
	echo "<table width='100%' cellpadding='6' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' nowrap valign='top'><b>Advanced Search</b></td>\n";
	echo "<td width='70%' align='right' valign='top'><input type='button' class='btn' name='' alt='back' onclick=\"window.location='xml_cdr.php'\" value='Back'><br /><br /></td>\n";
	echo "</tr>\n";

	echo "	<tr>";
	echo "		<td class='vncell'>Source Name:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='caller_id_name' value='$caller_id_name'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Source Number:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='caller_id_number' value='$caller_id_number'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Destination Number:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='destination_number' value='$destination_number'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Context:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='context' value='$context'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Start:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='start_stamp' value='$start_stamp'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Answer:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='answer_stamp' value='$answer_stamp'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>End:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='end_stamp' value='$end_stamp'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Duration:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='duration' value='$duration'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Bill Sec:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='billsec' value='$billsec'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Status:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='hangup_cause' value='$hangup_cause'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>UUID:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='uuid' value='$uuid'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Bridge UUID:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='bleg_uuid' value='$bridge_uuid'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Account Code:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='accountcode' value='$accountcode'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Read Codec:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='read_codec' value='$read_codec'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Write Codec:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='write_codec' value='$write_codec'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Remote Media IP:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='remote_media_ip' value='$remote_media_ip'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td class='vncell'>Network Address:</td>";
	echo "		<td class='vtable'><input type='text' class='formfld' name='network_addr' value='$network_addr'></td>";
	echo "	</tr>";
	echo "	<tr>";
	echo "		<td colspan='2' align='right'><input type='submit' name='submit' class='btn' value='Search'></td>";
	echo "	</tr>";
	echo "</table>";
	echo "</form>";

	echo "	</td>";
	echo "	</tr>";
	echo "</table>";
	echo "</div>";

	require_once "includes/footer.php";

} //end if not post
?>
