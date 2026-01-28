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
	if (!permission_exists('feature_codes_view')) {
		echo "access denied";
		exit;
	}

//function to format feature name for display
	function format_feature_name($name) {
		//replace underscores and hyphens with spaces
		$name = str_replace(array('_', '-'), ' ', $name);
		//capitalize each word
		$name = ucwords($name);
		return $name;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get globals
	global $database, $settings;

//backwards compatibility
	if (!isset($database) || !($database instanceof database)) {
		$database = database::new();
	}
	if (!isset($settings) || !($settings instanceof settings)) {
		$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid ?? $_SESSION['domain_uuid'] ?? '', 'user_uuid' => $user_uuid ?? $_SESSION['user_uuid'] ?? '']);
	}

//add multi-lingual support for dialplan text
	$dialplan_text = $language->get($settings->get('domain', 'language', 'en-us'), 'app/dialplans');

//get order and order by
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//get feature codes from dialplans
	$sql = "SELECT dialplan_uuid, dialplan_name, dialplan_number, dialplan_description ";
	if (permission_exists('feature_codes_raw')) {
		$sql .= ", dialplan_xml ";
	}
	$sql .= "FROM v_dialplans ";
	$sql .= "WHERE dialplan_enabled = 'true' ";
	$sql .= "AND dialplan_number LIKE '*%' ";
	$sql .= "AND (domain_uuid = :domain_uuid OR domain_uuid IS NULL) ";
	$sql .= order_by($order_by, $order, 'dialplan_number', 'asc');
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$features = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//handle PDF export
	if (isset($_GET['export']) && $_GET['export'] == 'pdf' && permission_exists('feature_codes_export')) {

		//include fpdf
		require_once "resources/fpdf/fpdf.php";

		//create pdf
		$pdf = new FPDF('P', 'mm', 'A4');
		$pdf->SetAutoPageBreak(false); //handle page breaks manually
		$pdf->AddPage();
		$pdf->SetFont('Arial', 'B', 16);

		//page dimensions
		$page_height = 297; //A4 height in mm
		$bottom_margin = 15;

		//title
		$pdf->Cell(0, 10, $text['title-feature_codes'], 0, 1, 'C');
		$pdf->Ln(5);

		//table header
		$pdf->SetFont('Arial', 'B', 10);
		$pdf->SetFillColor(240, 240, 240);

		if (permission_exists('feature_codes_raw')) {
			$col_widths = array(30, 50, 50, 60);
			$pdf->Cell($col_widths[0], 8, $text['label-feature_code'], 1, 0, 'L', true);
			$pdf->Cell($col_widths[1], 8, $text['label-feature_name'], 1, 0, 'L', true);
			$pdf->Cell($col_widths[2], 8, $text['label-description'], 1, 0, 'L', true);
			$pdf->Cell($col_widths[3], 8, $text['label-raw_dialplan'], 1, 1, 'L', true);
		}
		else {
			$col_widths = array(30, 50, 110);
			$pdf->Cell($col_widths[0], 8, $text['label-feature_code'], 1, 0, 'L', true);
			$pdf->Cell($col_widths[1], 8, $text['label-feature_name'], 1, 0, 'L', true);
			$pdf->Cell($col_widths[2], 8, $text['label-description'], 1, 1, 'L', true);
		}

		//table rows
		$pdf->SetFont('Arial', '', 9);
		if (is_array($features) && count($features) > 0) {
			foreach ($features as $row) {
				//set the feature dialing code eg. *67
				$feature_code = $row['dialplan_number'] ?? '';

				$dialplan_name = $row['dialplan_name'] ?? '';

				//set the dialplan name to use underscores for the text array lookup
				$array_key = str_replace(['-',' '],'_', $row['dialplan_name'] ?? '');

				//set the feature code name to be the internationalized name when possible
				$feature_name = format_feature_name($dialplan_text['label-dialplan_'.$array_key] ?? $dialplan_name);

				//set the dialplan description
				$feature_description = $row['dialplan_description'] ?? $dialplan_text['description-dialplan_'.$array_key] ?? '';

				//replace the ${number} variable in the description with the dialed number used to activate the dialplan
				$feature_description = str_replace('${number}', $feature_code, $feature_description);

				if (permission_exists('feature_codes_raw')) {
					$raw_value = isset($row['dialplan_xml']) ? substr($row['dialplan_xml'], 0, 50) : '';
					if (strlen($row['dialplan_xml'] ?? '') > 50) {
						$raw_value .= '...';
					}

					//calculate row height by measuring actual text width
					$desc_width = $col_widths[2] - 2; //account for cell padding
					$line_height = 5;

					//calculate number of lines needed using GetStringWidth
					$num_lines = 1;
					if (!empty($feature_description)) {
						$words = explode(' ', $feature_description);
						$current_line = '';
						$num_lines = 1;
						foreach ($words as $word) {
							$test_line = $current_line . ($current_line ? ' ' : '') . $word;
							if ($pdf->GetStringWidth($test_line) > $desc_width) {
								$num_lines++;
								$current_line = $word;
							} else {
								$current_line = $test_line;
							}
						}
					}
					$cell_padding = 2; //1mm top + 1mm bottom
					$row_height = max($line_height, $num_lines * $line_height) + $cell_padding;

					//check if row fits on current page, if not add new page
					if ($pdf->GetY() + $row_height > $page_height - $bottom_margin) {
						$pdf->AddPage();
						$pdf->SetFont('Arial', '', 9);
					}

					//save starting position
					$x = $pdf->GetX();
					$y = $pdf->GetY();

					//calculate vertical center offset for single-line text
					$text_height = $line_height;
					$vertical_padding = ($row_height - $text_height) / 2;

					//draw border rectangles for feature code and name columns
					$pdf->Rect($x, $y, $col_widths[0], $row_height);
					$pdf->Rect($x + $col_widths[0], $y, $col_widths[1], $row_height);

					//draw vertically centered text for feature code
					$pdf->SetXY($x + 1, $y + $vertical_padding);
					$pdf->Cell($col_widths[0] - 2, $text_height, $feature_code, 0, 0, 'L');

					//draw vertically centered text for feature name
					$pdf->SetXY($x + $col_widths[0] + 1, $y + $vertical_padding);
					$pdf->Cell($col_widths[1] - 2, $text_height, substr($feature_name, 0, 30), 0, 0, 'L');

					//draw description cell border and use MultiCell for text
					$desc_x = $x + $col_widths[0] + $col_widths[1];
					$pdf->Rect($desc_x, $y, $col_widths[2], $row_height);
					$pdf->SetXY($desc_x + 1, $y + ($cell_padding / 2));
					$pdf->MultiCell($col_widths[2] - 2, $line_height, $feature_description, 0, 'L');

					//draw border and vertically centered text for raw column
					$raw_x = $desc_x + $col_widths[2];
					$pdf->Rect($raw_x, $y, $col_widths[3], $row_height);
					$pdf->SetXY($raw_x + 1, $y + $vertical_padding);
					$pdf->Cell($col_widths[3] - 2, $text_height, $raw_value, 0, 0, 'L');

					//move to next row
					$pdf->SetXY($x, $y + $row_height);
				}
				else {
					//calculate row height by measuring actual text width
					$desc_width = $col_widths[2] - 2; //account for cell padding
					$line_height = 5;

					//calculate number of lines needed using GetStringWidth
					$num_lines = 1;
					if (!empty($feature_description)) {
						$words = explode(' ', $feature_description);
						$current_line = '';
						$num_lines = 1;
						foreach ($words as $word) {
							$test_line = $current_line . ($current_line ? ' ' : '') . $word;
							if ($pdf->GetStringWidth($test_line) > $desc_width) {
								$num_lines++;
								$current_line = $word;
							} else {
								$current_line = $test_line;
							}
						}
					}
					$cell_padding = 2; //1mm top + 1mm bottom
					$row_height = max($line_height, $num_lines * $line_height) + $cell_padding;

					//check if row fits on current page, if not add new page
					if ($pdf->GetY() + $row_height > $page_height - $bottom_margin) {
						$pdf->AddPage();
						$pdf->SetFont('Arial', '', 9);
					}

					//save starting position
					$x = $pdf->GetX();
					$y = $pdf->GetY();

					//calculate vertical center offset for single-line text
					$text_height = $line_height;
					$vertical_padding = ($row_height - $text_height) / 2;

					//draw border rectangles for feature code and name columns
					$pdf->Rect($x, $y, $col_widths[0], $row_height);
					$pdf->Rect($x + $col_widths[0], $y, $col_widths[1], $row_height);

					//draw vertically centered text for feature code
					$pdf->SetXY($x + 1, $y + $vertical_padding);
					$pdf->Cell($col_widths[0] - 2, $text_height, $feature_code, 0, 0, 'L');

					//draw vertically centered text for feature name
					$pdf->SetXY($x + $col_widths[0] + 1, $y + $vertical_padding);
					$pdf->Cell($col_widths[1] - 2, $text_height, substr(format_feature_name($feature_name), 0, 30), 0, 0, 'L');

					//draw description cell border and use MultiCell for text
					$desc_x = $x + $col_widths[0] + $col_widths[1];
					$pdf->Rect($desc_x, $y, $col_widths[2], $row_height);
					$pdf->SetXY($desc_x + 1, $y + ($cell_padding / 2));
					$pdf->MultiCell($col_widths[2] - 2, $line_height, $feature_description, 0, 'L');

					//move to next row
					$pdf->SetXY($x, $y + $row_height);
				}
			}
		}
		else {
			$col_span = permission_exists('feature_codes_raw') ? 190 : 190;
			$pdf->Cell($col_span, 7, $text['label-no_features'], 1, 1, 'C');
		}

		//output pdf
		$pdf->Output('feature_codes_' . date('Y-m-d') . '.pdf', 'D');
		exit;
	}

//include header
	$document['title'] = $text['title-feature_codes'];
	require_once "resources/header.php";

//javascript to toggle export select box
	echo "<script language='javascript' type='text/javascript'>";
	echo "	var fade_speed = 400;";
	echo "	function toggle_select(select_id) {";
	echo "		$('#'+select_id).fadeToggle(fade_speed, function() {";
	echo "			document.getElementById(select_id).selectedIndex = 0;";
	echo "			document.getElementById(select_id).focus();";
	echo "		});";
	echo "	}";
	echo "</script>";

//content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-feature_codes']."</b></div>\n";
	echo "	<div class='actions'>\n";

	if (permission_exists('feature_codes_export')) {
		echo button::create(array('type'=>'button','label'=>$text['button-export'],'icon'=>$settings->get('theme', 'button_icon_export'),'onclick'=>"toggle_select('export_format'); this.blur();"));
		echo "		<select class='formfld' style='display: none; width: auto;' name='export_format' id='export_format' onchange=\"toggle_select('export_format'); window.location.href='feature_codes.php?export=' + this.value;\">\n";
		echo "			<option value='' disabled='disabled' selected='selected'>".$text['label-format']."</option>\n";
		echo "			<option value='pdf'>PDF</option>\n";
		echo "		</select>\n";
	}

	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-feature_codes']."\n";
	echo "<br /><br />\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo th_order_by('dialplan_number', $text['label-feature_code'], $order_by, $order);
	echo th_order_by('dialplan_name', $text['label-feature_name'], $order_by, $order);
	echo "	<th class='hide-sm-dn'>".$text['label-description']."</th>\n";
	if (permission_exists('feature_codes_raw')) {
		echo "	<th class='hide-sm-dn'>".$text['label-raw_dialplan']."</th>\n";
	}
	echo "</tr>\n";

	if (is_array($features) && count($features) > 0) {
		foreach ($features as $row) {
			//set the feature dialing code eg. *67
			$feature_code = $row['dialplan_number'] ?? '';

			$dialplan_name = $row['dialplan_name'] ?? '';

			//set the dialplan name to use underscores for the text array lookup
			$array_key = str_replace(['-',' '],'_', $row['dialplan_name'] ?? '');

			//set the feature code name to be the internationalized name when possible
			$feature_name = format_feature_name($dialplan_text['label-dialplan_'.$array_key] ?? $dialplan_name);

			//set the dialplan description
			$feature_description = $row['dialplan_description'] ?? $dialplan_text['description-dialplan_'.$array_key] ?? '';

			//replace the ${number} variable in the description with the dialed number used to activate the dialplan
			$feature_description = str_replace('${number}', $feature_code, $feature_description);

			//output the row
			echo "<tr class='list-row'>\n";
			echo "	<td>".escape($feature_code)."</td>\n";
			echo "	<td>".escape($feature_name)."</td>\n";
			echo "	<td class='description hide-sm-dn'>".escape($feature_description)."</td>\n";

			//when raw permissions are enabled output the raw dialplan xml (first 100 characters) column
			if (permission_exists('feature_codes_raw')) {
				$raw_display = isset($row['dialplan_xml']) ? htmlspecialchars(substr($row['dialplan_xml'], 0, 100)) : '';
				if (isset($row['dialplan_xml']) && strlen($row['dialplan_xml']) > 100) {
					$raw_display .= '...';
				}
				echo "	<td class='description hide-sm-dn'><code>".$raw_display."</code></td>\n";
			}
			echo "</tr>\n";
		}
	} else {
		$colspan = permission_exists('feature_codes_raw') ? 4 : 3;
		echo "<tr class='list-row'>\n";
		echo "	<td colspan='".$colspan."' style='text-align: center;'>".$text['label-no_features']."</td>\n";
		echo "</tr>\n";
	}

	echo "</table>\n";
	echo "</div>\n";

//include footer
	require_once "resources/footer.php";
