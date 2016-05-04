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
	Portions created by the Initial Developer are Copyright (C) 2008-2016
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";

//permisisions
	require_once "resources/check_auth.php";
	if (permission_exists('xml_cdr_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//additional includes
	require_once "resources/header.php";

//retrieve submitted data
	$quick_select = check_str($_REQUEST['quick_select']);
	$start_stamp_begin = check_str($_REQUEST['start_stamp_begin']);
	$start_stamp_end = check_str($_REQUEST['start_stamp_end']);
	$include_internal = check_str($_REQUEST['include_internal']);
	$quick_select = (sizeof($_REQUEST) == 0) ? 1 : $quick_select; //set default

//get current extension info
	$sql = "select ";
	$sql .= "domain_uuid, ";
	$sql .= "extension_uuid, ";
	$sql .= "extension, ";
	$sql .= "number_alias, ";
	$sql .= "description ";
	$sql .= "from ";
	$sql .= "v_extensions ";
	$sql .= "where ";
	$sql .= "enabled = 'true' ";
	if (!($_GET['showall'] == 'true' && permission_exists('xml_cdr_all'))) {
		$sql .= "and domain_uuid = '".$_SESSION['domain_uuid']."' ";
	}
	if (!(if_group("admin") || if_group("superadmin"))) {
		if (count($_SESSION['user']['extension']) > 0) {
			$sql .= "and (";
			$x = 0;
			foreach($_SESSION['user']['extension'] as $row) {
				if ($x > 0) { $sql .= "or "; }
				$sql .= "extension = '".$row['user']."' ";
				$x++;
			}
			$sql .= ")";
		}
		else {
			//used to hide any results when a user has not been assigned an extension
			$sql .= "and extension = 'disabled' ";
		}
	}

	$sql .= "order by ";
	$sql .= "extension asc";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	if ($result_count > 0) {
		foreach($result as $row) {
			$ext = $row['extension'];
			if(strlen($row['number_alias']) > 0) {
				$ext = $row['number_alias'];
			}
			$extensions[$ext]['domain_uuid'] = $row['domain_uuid'];
			$extensions[$ext]['extension'] = $row['extension'];
			$extensions[$ext]['extension_uuid'] = $row['extension_uuid'];
			$extensions[$ext]['number_alias'] = $row['number_alias'];
			$extensions[$ext]['description'] = $row['description'];
		}
	}
	unset ($sql, $prep_statement, $result, $row_count);
	// create list of extensions for query below
	if (isset($extensions)) foreach ($extensions as $extension => $blah) {
		$ext_array[] = $extension;
	}
	$ext_list = (isset($ext_array)) ? implode("','", $ext_array) : "";

//calculate the summary data
	$sql = "select ";
	$sql .= "caller_id_number, ";
	$sql .= "destination_number, ";
	$sql .= "billsec, ";
	$sql .= "hangup_cause ";
	$sql .= "from v_xml_cdr ";
	$sql .= "where ";
	if (!($_GET['showall'] && permission_exists('xml_cdr_all'))) {
		$sql .= " domain_uuid = '".$_SESSION['domain_uuid']."' and ";
	}
	$sql .= "( ";
	$sql .= "	caller_id_number in ('".$ext_list."') or ";
	$sql .= "	destination_number in ('".$ext_list."') ";
	$sql .= ") ";
	if (!$include_internal) {
		$sql .= " and (direction = 'inbound' or direction = 'outbound') ";
	}
	if (strlen($start_stamp_begin) > 0 || strlen($start_stamp_end) > 0) {
		unset($quick_select);
		if (strlen($start_stamp_begin) > 0 && strlen($start_stamp_end) > 0) {
			$sql .= " and start_stamp between '".$start_stamp_begin.":00.000' and '".$start_stamp_end.":59.999'";
		}
		else {
			if (strlen($start_stamp_begin) > 0) { $sql .= "and start_stamp >= '".$start_stamp_begin.":00.000' "; }
			if (strlen($start_stamp_end) > 0) { $sql .= "and start_stamp <= '".$start_stamp_end.":59.999' "; }
		}
	}
	else {
		switch ($quick_select) {
			case 1: $sql .= "and start_stamp >= '".date('Y-m-d H:i:s.000', strtotime("-1 week"))."' "; break; //last 7 days
			case 2: $sql .= "and start_stamp >= '".date('Y-m-d H:i:s.000', strtotime("-1 hour"))."' "; break; //last hour
			case 3: $sql .= "and start_stamp >= '".date('Y-m-d')." "."00:00:00.000' "; break; //today
			case 4: $sql .= "and start_stamp between '".date('Y-m-d',strtotime("-1 day"))." "."00:00:00.000' and '".date('Y-m-d',strtotime("-1 day"))." "."23:59:59.999' "; break; //yesterday
			case 5: $sql .= "and start_stamp >= '".date('Y-m-d',strtotime("this week"))." "."00:00:00.000' "; break; //this week
			case 6: $sql .= "and start_stamp >= '".date('Y-m-')."01 "."00:00:00.000' "; break; //this month
			case 7: $sql .= "and start_stamp >= '".date('Y-')."01-01 "."00:00:00.000' "; break; //this year
		}
	}
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);

	if ($result_count > 0) {
		foreach($result as $row) {
			if ($summary[$row['destination_number']]['missed'] == null) {
				$summary[$row['destination_number']]['missed'] = 0;
			}
			if (in_array($row['caller_id_number'], $ext_array)) {
				$summary[$row['caller_id_number']]['outbound']['count']++;
				$summary[$row['caller_id_number']]['outbound']['seconds'] += $row['billsec'];
			}
			if (in_array($row['destination_number'], $ext_array)) {
				$summary[$row['destination_number']]['inbound']['count']++;
				$summary[$row['destination_number']]['inbound']['seconds'] += $row['billsec'];
				if ($row['billsec'] == "0") {
					$summary[$row['destination_number']]['missed']++;
				}
			}
			if ($row['hangup_cause'] == "NO_ANSWER") {
				$summary[$row['destination_number']]['no_answer']++;
			}
			if ($row['hangup_cause'] == "USER_BUSY") {
				$summary[$row['destination_number']]['busy']++;
			}
		} //end foreach
	} //end if results
	unset ($sql, $prep_statement, $result, $row_count);

//page title and description
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' width='50%' nowrap='nowrap' style='vertical-align: top;'>\n";
	echo "			<b>".$text['title-extension_summary']."</b><br><br>\n";
	echo "		</td>\n";
	echo "		<td align='right' width='100%' style='vertical-align: top;'>";
	if (permission_exists('xml_cdr_all') && $_GET['showall'] != 'true') {
		echo "		<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='xml_cdr_extension_summary.php?showall=true';\">\n";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br>\n";

	if (permission_exists('xml_cdr_search')) {
		echo "<form name='frm' id='frm' method='get' action=''>\n";

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";

		echo "		<td width='25%' style='vertical-align: top;'>\n";
		echo "			<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "				<tr>\n";
		echo "					<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
		echo "						".$text['label-preset']."\n";
		echo "					</td>\n";
		echo "					<td class='vtable' width='70%' align='left' style='white-space: nowrap;'>\n";
		echo "						<select class='formfld' name='quick_select' id='quick_select' onchange=\"if (this.selectedIndex != 0) { document.getElementById('start_stamp_begin').value = ''; document.getElementById('start_stamp_end').value = ''; document.getElementById('frm').submit(); }\">\n";
		echo "							<option value=''></option>\n";
		echo "							<option value='1' ".(($quick_select == 1) ? "selected='selected'" : null).">".$text['option-last_seven_days']."</option>\n";
		echo "							<option value='2' ".(($quick_select == 2) ? "selected='selected'" : null).">".$text['option-last_hour']."</option>\n";
		echo "							<option value='3' ".(($quick_select == 3) ? "selected='selected'" : null).">".$text['option-today']."</option>\n";
		echo "							<option value='4' ".(($quick_select == 4) ? "selected='selected'" : null).">".$text['option-yesterday']."</option>\n";
		echo "							<option value='5' ".(($quick_select == 5) ? "selected='selected'" : null).">".$text['option-this_week']."</option>\n";
		echo "							<option value='6' ".(($quick_select == 6) ? "selected='selected'" : null).">".$text['option-this_month']."</option>\n";
		echo "							<option value='7' ".(($quick_select == 7) ? "selected='selected'" : null).">".$text['option-this_year']."</option>\n";
		echo "						</select>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";
		echo "		</td>";

		echo "		<td width='25%' style='vertical-align: top;'>\n";
		echo "			<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "				<tr>\n";
		echo "					<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
		echo "						".$text['label-start_date_time']."\n";
		echo "					</td>\n";
		echo "					<td class='vtable' width='70%' align='left' style='position: relative; min-width: 135px;'>\n";
		echo "						<input type='text' class='formfld datetimepicker' style='min-width: 115px; width: 115px; max-width: 115px;' name='start_stamp_begin' id='start_stamp_begin' placeholder='".$text['label-from']."' value='$start_stamp_begin'>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";
		echo "		</td>";

		echo "		<td width='25%' style='vertical-align: top;'>\n";
		echo "			<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "				<tr>\n";
		echo "					<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
		echo "						".$text['label-end_date_time']."\n";
		echo "					</td>\n";
		echo "					<td class='vtable' width='70%' align='left' style='position: relative; min-width: 135px;'>\n";
		echo "						<input type='text' class='formfld datetimepicker' style='min-width: 115px; width: 115px; max-width: 115px;' name='start_stamp_end' id='start_stamp_end' placeholder='".$text['label-to']."' value='$start_stamp_end'>\n";
		echo "					</td>\n";
		echo "				</tr>\n";
		echo "			</table>\n";
		echo "		</td>";

		echo "		<td width='25%' style='vertical-align: top;'>\n";
		echo "			<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
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
		echo "		<td colspan='4' style='padding-top: 8px;' align='right'>";
		echo "			<input type='button' class='btn' value='".$text['button-reset']."' onclick=\"document.location.href='xml_cdr_extension_summary.php';\">\n";
		echo "			<input type='submit' class='btn' value='".$text['button-update']."'>\n";
		echo "		</td>";
		echo "	</tr>";
		echo "</table>";

		echo "</form>";
		echo "<br /><br />";
	}

//show the results
	echo "<table xclass='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
		echo "		<th>".$text['label-domain']."</th>\n";
	}
	echo "		<th>".$text['label-extension']."</th>\n";
	echo "		<th>".$text['label-number_alias']."</th>\n";
	echo "		<th>".$text['label-missed']."</th>\n";
	echo "		<th>".$text['label-no_answer']."</th>\n";
	echo "		<th>".$text['label-busy']."</th>\n";
	echo "		<th>".$text['label-aloc']."</th>\n";
	echo "		<th style='text-align: right;'>".$text['label-inbound_calls']."</th>\n";
	echo "		<th style='text-align: right;'>".$text['label-inbound_duration']."</th>\n";
	echo "		<th style='text-align: right;'>".$text['label-outbound_calls']."</th>\n";
	echo "		<th style='text-align: right;'>".$text['label-outbound_duration']."</th>\n";
	echo "		<th style='text-align: left;'>".$text['label-description']."</th>\n";
	echo "	</tr>\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";
	if (isset($extensions)) foreach ($extensions as $extension => $ext) {
		$seconds['inbound'] = $summary[$extension]['inbound']['seconds'];
		$seconds['outbound'] = $summary[$extension]['outbound']['seconds'];
		if ($summary[$extension]['missed'] == null) {
			$summary[$extension]['missed'] = 0;
		}
		if ($summary[$extension]['no_answer'] == null) {
			$summary[$extension]['no_answer'] = 0;
		}
		if ($summary[$extension]['busy'] == null) {
			$summary[$extension]['busy'] = 0;
		}

		//missed
		$missed = $summary[$extension]['missed'];

		//volume
		$volume = $summary[$extension]['inbound']['count'] + $summary[$extension]['outbound']['count'];

		//average length of call
		$summary[$extension]['aloc'] = $volume==0 ? 0 : ($seconds['inbound'] + $seconds['outbound']) / ($volume - $missed);

		$tr_link = "xhref='xml_cdr.php?'";
		echo "<tr ".$tr_link.">\n";
		if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
			echo "	<td valign='top' class='".$row_style[$c]."'>".$_SESSION['domains'][$ext['domain_uuid']]['domain_name']."</td>\n";
		}
		echo "	<td valign='top' class='".$row_style[$c]."'>".$extension."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$ext['number_alias']."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$summary[$extension]['missed']."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$summary[$extension]['no_answer']."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$summary[$extension]['busy']."&nbsp;</td>\n";
		echo "  <td valign='top' class='".$row_style[$c]."'>".gmdate("H:i:s",$summary[$extension]['aloc'])."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>&nbsp;".(($summary[$extension]['inbound']['count'] != '') ? $summary[$extension]['inbound']['count'] : "0")."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>".(($seconds['inbound'] != '') ? gmdate("G:i:s", $seconds['inbound']) : '0:00:00')."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>&nbsp;".(($summary[$extension]['outbound']['count'] != '') ? $summary[$extension]['outbound']['count'] : "0")."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>".(($seconds['outbound'] != '') ? gmdate("G:i:s", $seconds['outbound']) : '0:00:00')."</td>\n";
		echo "	<td valign='top' class='row_stylebg'>".$ext['description']."&nbsp;</td>\n";
		echo "</tr>\n";
		$c = ($c==0) ? 1 : 0;
	}

	echo "</table>";
	echo "<br><br>";

//show the footer
	require_once "resources/footer.php";

?>
