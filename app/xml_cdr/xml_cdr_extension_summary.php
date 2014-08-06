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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

if (permission_exists('xml_cdr_view')) {
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

//additional includes
	require_once "resources/header.php";

// retrieve submitted data
	$start_stamp_begin = check_str($_REQUEST['start_stamp_begin']);
	$start_stamp_end = check_str($_REQUEST['start_stamp_end']);
	$include_internal = check_str($_REQUEST['include_internal']);

//page title and description
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' width='50%' nowrap='nowrap' style='vertical-align: top;'>\n";
	echo "			<b>".$text['title-extension_summary']."</b><br><br>\n";
	echo "			".$text['description-extension_summary']."<br>\n";
	echo "		</td>\n";
	echo "		<td align='right' width='100%' style='vertical-align: top;'>&nbsp;</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>\n";

	if (permission_exists('xml_cdr_search')) {
		echo "<form name='frm' method='post' action=''>\n";

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "		<td width='33%' style='vertical-align: top;'>\n";

		echo "			<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
		echo "				<tr>\n";
		echo "					<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
		echo "						".$text['label-start_date_time']."\n";
		echo "					</td>\n";
		echo "					<td class='vtable' width='70%' align='left' style='white-space: nowrap;'>\n";
		echo "						<input type='text' class='formfld' style='min-width: 115px; width: 115px;' name='start_stamp_begin' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: false, fxName: null, showButtons: true}\" placeholder='".$text['label-from']."' value='$start_stamp_begin'>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";

		echo "		</td>";
		echo "		<td width='33%' style='vertical-align: top;'>\n";

		echo "			<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
		echo "				<tr>\n";
		echo "					<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
		echo "						".$text['label-end_date_time']."\n";
		echo "					</td>\n";
		echo "					<td class='vtable' width='70%' align='left' style='white-space: nowrap;'>\n";
		echo "						<input type='text' class='formfld' style='min-width: 115px; width: 115px;' name='start_stamp_end' data-calendar=\"{format: '%Y-%m-%d %H:%M', listYears: true, hideOnPick: false, fxName: null, showButtons: true}\" placeholder='".$text['label-to']."' value='$start_stamp_end'>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";

		echo "		</td>";
		echo "		<td width='33%' style='vertical-align: top;'>\n";

		echo "			<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
		echo "				<tr>\n";
		echo "					<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
		echo "						".$text['label-include_internal']."\n";
		echo "					</td>\n";
		echo "					<td class='vtable' width='70%' align='left' style='white-space: nowrap;'>\n";
		echo "						<select class='formfld' name='include_internal' id='include_internal'>\n";
		echo "							<option value='0'>".$text['option-false']."</option>\n";
		echo "							<option value='1' ".(($include_internal == 1) ? "selected" : null).">".$text['option-true']."</option>\n";
		echo "						</select>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";

		echo "		</td>";
		echo "	</tr>";
		echo "	<tr>";
		echo "		<td colspan='3' style='padding-top: 8px;' align='right'>";

		echo "			<input type='button' class='btn' value='".$text['button-reset']."' onclick=\"document.location.href='xml_cdr_extension_summary.php';\">\n";
		echo "			<input type='submit' class='btn' name='submit' value='".$text['button-update']."'>\n";

		echo "		</td>";
		echo "	</tr>";
		echo "</table>";

		echo "</form>";
		echo "<br /><br />";
	}

// get current extension info
	$sql = "select ";
	$sql .= "extension_uuid, ";
	$sql .= "extension, ";
	$sql .= "number_alias ";
	$sql .= "from ";
	$sql .= "v_extensions ";
	$sql .= "where ";
	$sql .= "enabled = 'true' ";
	$sql .= "and domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "order by ";
	$sql .= "extension asc";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	if ($result_count > 0) {
		foreach($result as $row) {
			$extensions[$row['extension']]['extension_uuid'] = $row['extension_uuid'];
			$extensions[$row['extension']]['number_alias'] = $row['number_alias'];
		}
	}
	unset ($sql, $prep_statement, $result, $row_count);
	// create list of extensions for query below
	foreach ($extensions as $extension => $blah) {
		$ext_array[] = $extension;
	}
	$ext_list = implode("','", $ext_array);

// calculate the summary data
	$sql = "select ";
	$sql .= "caller_id_number, ";
	$sql .= "destination_number, ";
	$sql .= "billsec ";
	$sql .= "from ";
	$sql .= "v_xml_cdr ";
	$sql .= "where ";
	$sql .= "domain_uuid = '".$_SESSION['domain_uuid']."' ";
	$sql .= "and ( ";
	$sql .= "	caller_id_number in ('".$ext_list."') or ";
	$sql .= "	destination_number in ('".$ext_list."') ";
	$sql .= ") ";
	if (!$include_internal) {
		$sql .= " and ( direction = 'inbound' or direction = 'outbound' ) ";
	}
	if (strlen($start_stamp_begin) == 0 && strlen($start_stamp_end) == 0) {
		$sql .= "and start_stamp >= '".date('Y-m-d H:i:s.000', strtotime("-1 week"))."' "; // show last 7 days if no range specified
	}
	else if (strlen($start_stamp_begin) > 0 && strlen($start_stamp_end) > 0) { $sql .= " and start_stamp BETWEEN '".$start_stamp_begin.":00.000' AND '".$start_stamp_end.":59.999'"; }
	else {
		if (strlen($start_stamp_begin) > 0) { $sql .= " and start_stamp >= '".$start_stamp_begin.":00.000'"; }
		if (strlen($start_stamp_end) > 0) { $sql .= " and start_stamp <= '".$start_stamp_end.":59.999'"; }
	}
	//echo $sql."<br><br>";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);

	if ($result_count > 0) {
		foreach($result as $row) {
			if (in_array($row['caller_id_number'], $ext_array)) {
				$summary[$row['caller_id_number']]['outbound']['count']++;
				$summary[$row['caller_id_number']]['outbound']['seconds'] += $row['billsec'];
			}
			if (in_array($row['destination_number'], $ext_array)) {
				$summary[$row['destination_number']]['inbound']['count']++;
				$summary[$row['destination_number']]['inbound']['seconds'] += $row['billsec'];
			}
		} //end foreach
	} //end if results
	unset ($sql, $prep_statement, $result, $row_count);

//show the results
	echo "<table xclass='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<th>".$text['label-extension']."</th>\n";
	echo "		<th>".$text['label-number_alias']."</th>\n";
	echo "		<th style='text-align: right;'>".$text['label-inbound_calls']."</th>\n";
	echo "		<th style='text-align: right;'>".$text['label-inbound_duration']."</th>\n";
	echo "		<th style='text-align: right;'>".$text['label-outbound_calls']."</th>\n";
	echo "		<th style='text-align: right;'>".$text['label-outbound_duration']."</th>\n";
	echo "	</tr>\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	foreach ($extensions as $extension => $ext) {
		$seconds['inbound'] = $summary[$extension]['inbound']['seconds'];
		$seconds['outbound'] = $summary[$extension]['outbound']['seconds'];
		$tr_link = "xhref='xml_cdr.php?'";
		echo "<tr ".$tr_link.">\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$extension."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$ext['number_alias']."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>&nbsp;".(($summary[$extension]['inbound']['count'] != '') ? $summary[$extension]['inbound']['count'] : "0")."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>".(($seconds['inbound'] != '') ? gmdate("G:i:s", $seconds['inbound']) : '0:00:00')."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>&nbsp;".(($summary[$extension]['outbound']['count'] != '') ? $summary[$extension]['outbound']['count'] : "0")."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>".(($seconds['outbound'] != '') ? gmdate("G:i:s", $seconds['outbound']) : '0:00:00')."</td>\n";
		echo "</tr>\n";
		$c = ($c==0) ? 1 : 0;
	}

	echo "</table>";
	echo "</div>";
	echo "<br><br>";
	echo "<br><br>";

//show the footer
	require_once "resources/footer.php";

?>