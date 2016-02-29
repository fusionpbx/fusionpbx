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
	Copyright (C) 2008-2016
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
	$language = new text;
	$text = $language->get();

//send the header
require_once "resources/header.php";

//javascript to toggle input/select boxes
echo "<script type='text/javascript'>";
echo "	function toggle(field) {";
echo "		if (field == 'source') {";
echo "			document.getElementById('caller_extension_uuid').selectedIndex = 0;";
echo "			document.getElementById('caller_id_number').value = '';";
echo "			$('#caller_extension_uuid').toggle();";
echo "			$('#caller_id_number').toggle();";
echo "			if ($('#caller_id_number').is(':visible')) { $('#caller_id_number').focus(); } else { $('#caller_extension_uuid').focus(); }";
echo "		}";
echo "	}";
echo "</script>";

//start the html form
if (strlen(check_str($_GET['redirect'])) > 0) {
	echo "<form method='get' action='" . $_GET['redirect'] . ".php'>\n";
} else {
	echo "<form method='get' action='xml_cdr.php'>\n";
}

echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
echo "	<tr>\n";
echo "		<td width='30%' nowrap='nowrap' valign='top'><b>Advanced Search</b></td>\n";
echo "		<td width='70%' align='right' valign='top'>";
echo "			<input type='button' class='btn' name='' alt='back' onclick=\"window.location='xml_cdr.php'\" value='Back'>";
echo "			<input type='submit' name='submit' class='btn' value='Search'>";
echo "			<br /><br />";
echo "		</td>\n";
echo "	</tr>\n";
echo "</table>\n";

echo "<table cellpadding='0' cellspacing='0' border='0' width='100%'>\n";
echo "	<tr>\n";
echo "		<td width='50%' style='vertical-align: top;'>\n";

echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
echo "	<tr>\n";
echo "		<td width='30%' class='vncell' valign='top' nowrap='nowrap'>\n";
echo "			".$text['label-direction']."\n";
echo "		</td>\n";
echo "		<td width='70%' class='vtable' align='left'>\n";
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
echo "		<td class='vtable'>";
echo "			<select class='formfld' name='caller_extension_uuid' id='caller_extension_uuid'>\n";
echo "				<option value=''></option>";
$sql = "select extension_uuid, extension, number_alias from v_extensions ";
$sql .= "where domain_uuid = '".$_SESSION['domain_uuid']."' ";
$sql .= "order by ";
$sql .= "extension asc ";
$sql .= ", number_alias asc ";
$prep_statement = $db->prepare(check_sql($sql));
$prep_statement -> execute();
$result_e = $prep_statement -> fetchAll(PDO::FETCH_NAMED);
foreach ($result_e as &$row) {
	$selected = ($row['extension_uuid'] == $caller_extension_uuid) ? "selected" : null;
	echo "			<option value='".$row['extension_uuid']."' ".$selected.">".((is_numeric($row['extension'])) ? $row['extension'] : $row['number_alias']." (".$row['extension'].")")."</option>";
}
unset ($prep_statement);
echo "			</select>\n";
echo "			<input type='text' class='formfld' style='display: none;'  name='caller_id_number' id='caller_id_number' value='".$caller_id_number."'>\n";
echo "			<input type='button' id='btn_toggle_source' class='btn' name='' alt='".$text['button-back']."' value='&#9665;' onclick=\"toggle('source');\">\n";
echo "		</td>";
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
echo "		<td class='vncell'>".$text['label-start_range']."</td>";
echo "		<td class='vtable'>";
echo "			<input type='text' class='formfld' style='min-width: 115px; width: 115px;' name='start_stamp_begin' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: false, fxName: null, showButtons: true}\" placeholder='".$text['label-from']."' value='$start_stamp_begin'>";
echo "			<input type='text' class='formfld' style='min-width: 115px; width: 115px;' name='start_stamp_end' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: false, fxName: null, showButtons: true}\" placeholder='".$text['label-to']."' value='$start_stamp_end'>";
echo "		</td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-answer_range']."</td>";
echo "		<td class='vtable'>";
echo "			<input type='text' class='formfld' style='min-width: 115px; width: 115px;' name='answer_stamp_begin' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: false, fxName: null, showButtons: true}\" placeholder='".$text['label-from']."' value='$answer_stamp_begin'>";
echo "			<input type='text' class='formfld' style='min-width: 115px; width: 115px;' name='answer_stamp_end' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: false, fxName: null, showButtons: true}\" placeholder='".$text['label-to']."' value='$answer_stamp_end'>";
echo "		</td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-end_range']."</td>";
echo "		<td class='vtable'>";
echo "			<input type='text' class='formfld' style='min-width: 115px; width: 115px;' name='end_stamp_begin' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: false, fxName: null, showButtons: true}\" placeholder='".$text['label-from']."' value='$end_stamp_begin'>";
echo "			<input type='text' class='formfld' style='min-width: 115px; width: 115px;' name='end_stamp_end' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: false, fxName: null, showButtons: true}\" placeholder='".$text['label-to']."' value='$end_stamp_end'>";
echo "		</td>";
echo "	</tr>";
echo "	<tr>";
echo "		<td class='vncell'>".$text['label-duration']."</td>";
echo "		<td class='vtable'><input type='text' class='formfld' name='duration' value='$duration'></td>";
echo "	</tr>";
if (permission_exists('xml_cdr_all')) {
	echo "	<tr>";
	echo "		<td class='vncell'>".$text['button-show_all']."</td>";
	echo "		<td class='vtable'>\n";
	if (permission_exists('xml_cdr_all') && $_REQUEST['showall'] == "true") {
		echo "			<input type='checkbox' class='formfld' name='showall' checked='checked' value='true'>";
	}
	else {
		echo "			<input type='checkbox' class='formfld' name='showall' value='true'>";
	}
	echo "		<td>";
	echo "	</tr>";
}
echo "</table>";

echo "		</td>";
echo "		<td width='50%' style='vertical-align: top;'>\n";

echo "<table width='100%' cellpadding='0' cellspacing='0'>\n";
echo "	<tr>";
echo "		<td width='30%' class='vncell'>".$text['label-billsec']."</td>";
echo "		<td width='70%' class='vtable'><input type='text' class='formfld' name='billsec' value='$billsec'></td>";
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
echo "		<td class='vncell'>".$text['label-mos_score']."</td>";
echo "		<td class='vtable'>";
echo "			<select name='mos_comparison' class='formfld'>\n";
echo "			<option value=''></option>\n";
echo "			<option value='less'>&lt;</option>\n";
echo "			<option value='greater'>&gt;</option>\n";
echo "			<option value='lessorequal'>&lt;&#61;</option>\n";
echo "			<option value='greaterorequal'>&gt;&#61;</option>\n";
echo "			<option value='equal'>&#61;</option>\n";
echo "			<option value='notequal'>&lt;&gt;</option>\n";
echo "			</select>\n";
echo "			<input type='text' class='formfld' name='mos_score' value='$mos_score'>\n";
echo "		</td>";
echo "	</tr>\n";

echo "	<tr>\n";
echo "		<td colspan='2' align='right'><br>\n";
echo "			<input type='submit' name='submit' class='btn' value='".$text['button-search']."'>\n";
echo "		</td>\n";
echo "	</tr>\n";
echo "</table>\n";

echo "		</td>";
echo "	</tr>";
echo "</table>";
echo "<br><br>";

echo "</form>";

require_once "resources/footer.php";

?>