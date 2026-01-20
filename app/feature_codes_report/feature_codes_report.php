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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('feature_report_view')) {
		echo "access denied";
		exit;
	}

//set permission variables
	$has_feature_report_export = permission_exists('feature_report_export');
	$has_feature_report_raw = permission_exists('feature_report_raw');

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get settings
	$settings = new settings(array('domain_uuid' => $_SESSION['domain_uuid']));

//get feature codes from dialplans
	$sql = "SELECT dialplan_uuid, dialplan_name, dialplan_number, dialplan_description ";
	if ($has_feature_report_raw) {
		$sql .= ", dialplan_xml ";
	}
	$sql .= "FROM v_dialplans ";
	$sql .= "WHERE dialplan_enabled = 'true' ";
	$sql .= "AND dialplan_number LIKE '*%' ";
	$sql .= "AND (domain_uuid = :domain_uuid OR domain_uuid IS NULL) ";
	$sql .= "ORDER BY dialplan_number ASC ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$features = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//handle PDF export
	if (isset($_GET['export']) && $_GET['export'] == 'pdf' && $has_feature_report_export) {

		//include fpdf
		require_once "resources/fpdf/fpdf.php";

		//create pdf
		$pdf = new FPDF('P', 'mm', 'A4');
		$pdf->SetAutoPageBreak(true, 15);
		$pdf->AddPage();
		$pdf->SetFont('Arial', 'B', 16);

		//title
		$pdf->Cell(0, 10, $text['title-feature_report'], 0, 1, 'C');
		$pdf->Ln(5);

		//table header
		$pdf->SetFont('Arial', 'B', 10);
		$pdf->SetFillColor(240, 240, 240);

		if ($has_feature_report_raw) {
			$pdf->Cell(30, 8, $text['label-feature_code'], 1, 0, 'L', true);
			$pdf->Cell(50, 8, $text['label-feature_name'], 1, 0, 'L', true);
			$pdf->Cell(50, 8, $text['label-description'], 1, 0, 'L', true);
			$pdf->Cell(60, 8, $text['label-raw_dialplan'], 1, 1, 'L', true);
		}
		else {
			$pdf->Cell(40, 8, $text['label-feature_code'], 1, 0, 'L', true);
			$pdf->Cell(60, 8, $text['label-feature_name'], 1, 0, 'L', true);
			$pdf->Cell(90, 8, $text['label-description'], 1, 1, 'L', true);
		}

		//table rows
		$pdf->SetFont('Arial', '', 9);
		if (is_array($features) && count($features) > 0) {
			foreach ($features as $row) {
				$feature_code = $row['dialplan_number'];
				$feature_name = $row['dialplan_name'];
				$feature_description = $row['dialplan_description'];

				if ($has_feature_report_raw) {
					$raw_value = isset($row['dialplan_xml']) ? substr($row['dialplan_xml'], 0, 50) : '';
					if (strlen($row['dialplan_xml']) > 50) {
						$raw_value .= '...';
					}
					$pdf->Cell(30, 7, $feature_code, 1, 0, 'L');
					$pdf->Cell(50, 7, substr($feature_name, 0, 30), 1, 0, 'L');
					$pdf->Cell(50, 7, substr($feature_description, 0, 30), 1, 0, 'L');
					$pdf->Cell(60, 7, $raw_value, 1, 1, 'L');
				}
				else {
					$pdf->Cell(40, 7, $feature_code, 1, 0, 'L');
					$pdf->Cell(60, 7, substr($feature_name, 0, 35), 1, 0, 'L');
					$pdf->Cell(90, 7, substr($feature_description, 0, 55), 1, 1, 'L');
				}
			}
		}
		else {
			$col_span = $has_feature_report_raw ? 190 : 190;
			$pdf->Cell($col_span, 7, $text['label-no_features'], 1, 1, 'C');
		}

		//output pdf
		$pdf->Output('D', 'feature_codes_' . date('Y-m-d') . '.pdf');
		exit;
	}

//include header
	$document['title'] = $text['title-feature_report'];
	require_once "resources/header.php";

//content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-feature_report']."</b></div>\n";
	echo "	<div class='actions'>\n";

	if ($has_feature_report_export) {
		echo button::create(array('type'=>'button','label'=>$text['button-export'],'icon'=>$settings->get('theme', 'button_icon_export'),'onclick'=>"toggle_select('export_format'); this.blur();"));
		echo "		<select class='formfld' style='display: none; width: auto;' name='export_format' id='export_format' onchange=\"toggle_select('export_format'); window.location.href='feature_report.php?export=' + this.value;\">\n";
		echo "			<option value='' disabled='disabled' selected='selected'>".$text['label-format']."</option>\n";
		echo "			<option value='pdf'>PDF</option>\n";
		echo "		</select>\n";
	}

	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-feature_report']."\n";
	echo "<br /><br />\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th>".$text['label-feature_code']."</th>\n";
	echo "	<th>".$text['label-feature_name']."</th>\n";
	echo "	<th class='hide-sm-dn'>".$text['label-description']."</th>\n";
	if ($has_feature_report_raw) {
		echo "	<th class='hide-sm-dn'>".$text['label-raw_dialplan']."</th>\n";
	}
	echo "</tr>\n";

	if (is_array($features) && count($features) > 0) {
		foreach ($features as $row) {
			echo "<tr class='list-row'>\n";
			echo "	<td>".escape($row['dialplan_number'])."</td>\n";
			echo "	<td>".escape($row['dialplan_name'])."</td>\n";
			echo "	<td class='description hide-sm-dn'>".escape($row['dialplan_description'])."</td>\n";
			if ($has_feature_report_raw) {
				$raw_display = isset($row['dialplan_xml']) ? htmlspecialchars(substr($row['dialplan_xml'], 0, 100)) : '';
				if (isset($row['dialplan_xml']) && strlen($row['dialplan_xml']) > 100) {
					$raw_display .= '...';
				}
				echo "	<td class='description hide-sm-dn'><code>".$raw_display."</code></td>\n";
			}
			echo "</tr>\n";
		}
	}
	else {
		$colspan = $has_feature_report_raw ? 4 : 3;
		echo "<tr class='list-row'>\n";
		echo "	<td colspan='".$colspan."' style='text-align: center;'>".$text['label-no_features']."</td>\n";
		echo "</tr>\n";
	}

	echo "</table>\n";
	echo "</div>\n";

//include footer
	require_once "resources/footer.php";

?>
