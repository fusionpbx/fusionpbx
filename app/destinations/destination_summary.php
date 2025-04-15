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
	Portions created by the Initial Developer are Copyright (C) 2023-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	//require_once "resources/paging.php";

//check permissions
	if (permission_exists('destination_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//retrieve submitted data
	if (!empty($_REQUEST)) {
		$quick_select = $_REQUEST['quick_select'] ?? '';
		if (empty($quick_select)) {
			$start_stamp_begin = $_REQUEST['start_stamp_begin'] ?? '';
			$start_stamp_end = $_REQUEST['start_stamp_end'] ?? '';
		}
		//$include_internal = $_REQUEST['include_internal'];
	}
	else {
		$quick_select = 3; //set default
	}

//get the summary
	$destination = new destinations;
	$destination->domain_uuid = $_SESSION['domain_uuid'];
	if (!empty($quick_select)) {
		$destination->quick_select = $quick_select;
	}
	else {
		$destination->start_stamp_begin = $start_stamp_begin ?? '';
		$destination->start_stamp_end = $start_stamp_end ?? '';
	}
	//$destination->include_internal = $include_internal ?? null;
	$summary = $destination->destination_summary();

//set the http header
	if (!empty($_REQUEST['type']) && $_REQUEST['type'] == "csv") {

		//set the headers
			header('Content-type: application/octet-binary');
			header('Content-Disposition: attachment; filename=destination-summary.csv');

		//show the column names on the first line
			$z = 0;
			if (!empty($summary) && is_array($summary)) {
				foreach ($summary[1] as $key => $val) {
					if ($z == 0) {
						echo '"'.$key.'"';
					}
					else {
						echo ',"'.$key.'"';
					}
					$z++;
				}
				echo "\n";
			}

		//add the values to the csv
			$x = 0;
			if (!empty($summary) && is_array($summary)) {
				foreach ($summary as $users) {
					$z = 0;
					foreach ($users as $key => $val) {
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
			}
			exit;
	}

//include the header
	$document['title'] = $text['title-destination_summary'];
	require_once "resources/header.php";

//css grid adjustment
	echo "<style>\n";
	echo "	div.form_grid {\n";
	echo "		grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));\n";
	echo "		}\n";
	echo "</style>\n";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-destination_summary']."</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('destination_summary_all') && $_GET['show'] != 'all') {
		echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$settings->get('theme', 'button_icon_all'),'collapse'=>'hide-sm-dn','link'=>'destination_summary.php?show=all']);
	}
	echo button::create(['type'=>'button','label'=>$text['button-download_csv'],'icon'=>$settings->get('theme', 'button_icon_download'),'collapse'=>'hide-sm-dn','link'=>'destination_summary.php?'.(!empty($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"].'&' : null).'type=csv']);
	echo button::create(['type'=>'button','label'=>$text['button-reset'],'icon'=>$settings->get('theme', 'button_icon_reset'),'collapse'=>'hide-xs','style'=>'margin-left: 15px;','link'=>'destination_summary.php']);
	echo button::create(['type'=>'button','label'=>$text['button-update'],'icon'=>$settings->get('theme', 'button_icon_save'),'id'=>'btn_save','collapse'=>'hide-xs','onclick'=>"document.getElementById('frm').submit();"]);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('xml_cdr_search')) {
		echo "<form name='frm' id='frm' method='get'>\n";

		echo "<div class='card' style='margin-bottom: 30px;'>\n";
		echo "<div class='form_grid'>\n";

		echo "	<div class='form_set'>\n";
		echo "		<div class='label'>\n";
		echo "			".$text['label-preset']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<select class='formfld' name='quick_select' id='quick_select' onchange=\"if (this.selectedIndex != 0) { document.getElementById('start_stamp_begin').value = ''; document.getElementById('start_stamp_end').value = ''; document.getElementById('frm').submit(); }\">\n";
		echo "				<option value=''></option>\n";
		echo "				<option value='1' ".($quick_select == 1 ? "selected='selected'" : null).">".$text['option-last_seven_days']."</option>\n";
		echo "				<option value='2' ".($quick_select == 2 ? "selected='selected'" : null).">".$text['option-last_hour']."</option>\n";
		echo "				<option value='3' ".($quick_select == 3 ? "selected='selected'" : null).">".$text['option-today']."</option>\n";
		echo "				<option value='4' ".($quick_select == 4 ? "selected='selected'" : null).">".$text['option-yesterday']."</option>\n";
		echo "				<option value='5' ".($quick_select == 5 ? "selected='selected'" : null).">".$text['option-this_week']."</option>\n";
		echo "				<option value='6' ".($quick_select == 6 ? "selected='selected'" : null).">".$text['option-this_month']."</option>\n";
		echo "				<option value='7' ".($quick_select == 7 ? "selected='selected'" : null).">".$text['option-this_year']."</option>\n";
		echo "			</select>\n";
		echo "		</div>\n";
		echo "	</div>\n";

		//echo "	<div class='form_set'>\n";
		//echo "		<div class='label'>\n";
		//echo "			".$text['label-include_internal']."\n";
		//echo "		</div>\n";
		//echo "		<div class='field'>\n";
		//echo "			<select class='formfld' name='include_internal' id='include_internal'>\n";
		//echo "				<option value='0'>".$text['option-false']."</option>\n";
		//echo "				<option value='1' ".((!empty($include_internal) && $include_internal == 1) ? "selected" : null).">".$text['option-true']."</option>\n";
		//echo "			</select>\n";
		//echo "		</div>\n";
		//echo "	</div>\n";

		echo "	<div class='form_set'>\n";
		echo "		<div class='label'>\n";
		echo "			".$text['label-start_date_time']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#start_stamp_begin' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px; max-width: 115px;' name='start_stamp_begin' id='start_stamp_begin' placeholder='".$text['label-from']."' value='".escape($start_stamp_begin ?? '')."'>\n";
		echo "		</div>\n";
		echo "	</div>\n";

		echo "	<div class='form_set'>\n";
		echo "		<div class='label'>\n";
		echo "			".$text['label-end_date_time']."\n";
		echo "		</div>\n";
		echo "		<div class='field'>\n";
		echo "			<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#start_stamp_end' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px; max-width: 115px;' name='start_stamp_end' id='start_stamp_end' placeholder='".$text['label-to']."' value='".escape($start_stamp_end ?? '')."'>\n";
		echo "		</div>\n";
		echo "	</div>\n";

		echo "</div>\n";
		echo "</div>\n";

		if (!empty($_GET['show']) && $_GET['show'] == 'all' && permission_exists('destination_summary_all')) {
			echo "<input type='hidden' name='show' value='all'>";
		}

		echo "</form>";
	}

//show the results
	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "	<tr class='list-header'>\n";
	if (!empty($_GET['show']) && $_GET['show'] === "all" && permission_exists('destination_summary_all')) {
		echo "		<th>".$text['label-domain']."</th>\n";
	}
	echo "		<th>".$text['label-destination_number']."</th>\n";
	if (permission_exists('number_alias')) {
		echo "		<th>".$text['label-number_alias']."</th>\n";
	}
	echo "		<th class='center'>".$text['label-answered']."</th>\n";
	echo "		<th class='center'>".$text['label-unique_callers']."</th>\n";
	echo "		<th class='center'>".$text['label-total_calls']."</th>\n";
	echo "		<th class='center'>".$text['label-duration']."</th>\n";
	echo "		<th class='hide-sm-dn'>".$text['label-description']."</th>\n";
	echo "	</tr>\n";

	if (!empty($summary) && is_array($summary)) {
		foreach ($summary as $key => $row) {
			echo "<tr class='list-row'>\n";
			if (!empty($_GET['show']) && $_GET['show'] === "all" && permission_exists('destination_summary_all')) {
				echo "	<td>".escape($row['domain_name'])."</td>\n";
			}
			echo "	<td>".escape($row['destination_number'])."</td>\n";
			if (permission_exists('number_alias')) {
				echo "	<td>".escape($row['number_alias'])."&nbsp;</td>\n";
			}
			echo "	<td class='center'>".escape($row['answered_calls'])."&nbsp;</td>\n";
			echo "	<td class='center'>".escape($row['unique_callers'])."&nbsp;</td>\n";
			echo "	<td class='center'>".escape($row['total_calls'])."&nbsp;</td>\n";
			//echo "	<td class='center'>".(($row['outbound_calls'] != '') ? escape($row['outbound_calls']) : "0")."&nbsp;</td>\n";
			echo "	<td class='center'>".(($row['total_seconds'] != '') ? format_hours($row['total_seconds']) : '0:00:00')."</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['destination_description'])."&nbsp;</td>\n";
			echo "</tr>\n";
		}
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";

//show the footer
	require_once "resources/footer.php";

?>
