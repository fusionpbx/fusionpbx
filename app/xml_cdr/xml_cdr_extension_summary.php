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
	$quick_select = (sizeof($_REQUEST) == 0) ? 3 : $quick_select; //set default

//get the summary
	$cdr = new xml_cdr;
	$cdr->domain_uuid = $_SESSION['domain_uuid'];
	$cdr->quick_select = $quick_select;
	$cdr->start_stamp_begin = $start_stamp_begin;
	$cdr->start_stamp_end = $start_stamp_end;
	$cdr->include_internal = $include_internal;
	$summary = $cdr->user_summary();

//set the http header
	if ($_REQUEST['type'] == "csv") {
	
		//set the headers
			header('Content-type: application/octet-binary');
			header('Content-Disposition: attachment; filename=user-summary.csv');

		//show the column names on the first line
			$z = 0;
			foreach($summary[1] as $key => $val) {
				if ($z == 0) {
					echo '"'.$key.'"';
				}
				else {
					echo ',"'.$key.'"';
				}
				$z++;
			}
			echo "\n";
		
		//add the values to the csv
			$x = 0;
			foreach($summary as $users) {
				$z = 0;
				foreach($users as $key => $val) {
					if ($z == 0) {
						echo '"'.$summary[$x][$key].'"';
					}
					else {
						echo ',"'.$summary[$x][$key].'"';
					}
					$z++;
				}
				echo "\n";
				$x++;
			}
			exit;
	}

//page title and description
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' width='50%' nowrap='nowrap' style='vertical-align: top;'>\n";
	echo "			<b>".$text['title-extension_summary']."</b><br><br>\n";
	echo "		</td>\n";
	echo "		<td align='right' width='100%' style='vertical-align: top;'>";
	echo "		<input type='button' class='btn' value='".$text['button-download_csv']."' ";
	echo "onclick=\"window.location='xml_cdr_extension_summary.php?";
	if (strlen($_SERVER["QUERY_STRING"]) > 0) { 
		echo $_SERVER["QUERY_STRING"]."&type=csv';\">\n";
	} else { 
		echo "type=csv';\">\n";
	}

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
		echo "						<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#start_stamp_begin' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px; max-width: 115px;' name='start_stamp_begin' id='start_stamp_begin' placeholder='".$text['label-from']."' value='".escape($start_stamp_begin)."'>\n";
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
		echo "						<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#start_stamp_end' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px; max-width: 115px;' name='start_stamp_end' id='start_stamp_end' placeholder='".$text['label-to']."' value='".escape($start_stamp_end)."'>\n";
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
	if (isset($summary)) foreach ($summary as $key => $row) {
		$tr_link = "xhref='xml_cdr.php?'";
		echo "<tr ".$tr_link.">\n";
		if ($_GET['showall'] && permission_exists('xml_cdr_all')) {
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['domain_name'])."</td>\n";
		}
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['extension'])."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['number_alias'])."&nbsp;</td>\n";
		//echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['answered'])."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['missed'])."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['no_answer'])."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape($row['busy'])."&nbsp;</td>\n";
		echo "  <td valign='top' class='".$row_style[$c]."'>".format_hours($row['aloc'])."&nbsp;</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>&nbsp;". escape($row['inbound_calls']) ."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>".(($row['inbound_duration'] != '0') ? format_hours($row['inbound_duration']) : '0:00:00')."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>&nbsp;".(($row['outbound_calls'] != '') ? escape($row['outbound_calls']) : "0")."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: right;'>".(($row['outbound_duration'] != '') ? format_hours($row['outbound_duration']) : '0:00:00')."</td>\n";
		echo "	<td valign='top' class='row_stylebg'>".escape($row['description'])."&nbsp;</td>\n";
		echo "</tr>\n";
		$c = ($c==0) ? 1 : 0;
	}

	echo "</table>";
	echo "<br><br>";

//show the footer
	require_once "resources/footer.php";

?>
