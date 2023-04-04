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
	Portions created by the Initial Developer are Copyright (C) 2008-2020
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('xml_cdr_export')) {
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
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$archive_request = $_POST['archive_request'] == 'true' ? true : false;
	require_once "xml_cdr_inc.php";

//get the format
	$export_format = $_REQUEST['export_format'];

//export the csv
	if (permission_exists('xml_cdr_export_csv') && $export_format == 'csv') {

		//define file name
			if ($_GET['show'] == 'all' && permission_exists('xml_cdr_all')) {
				$csv_filename = "cdr_".date("Ymd_His").".csv";
			}
			else {
				$csv_filename = "cdr_".$_SESSION['domain_name']."_".date("Ymd_His").".csv";
			}

		//set the http headers
			header('Content-type: application/octet-binary');
			header('Content-Disposition: attachment; filename='.$csv_filename);

		//set the csv headers
			$z = 0;
			foreach ($result[0] as $key => $val) {
				if ($key != "xml" && $key != "json") {
					if ($z == 0) {
						echo '"'.$key.'"';
					}
					else {
						echo ',"'.$key.'"';
					}
				}
				$z++;
			}
			echo "\n";

		//show the csv data
			$x=0;
			while (true) {
				$z = 0;
				foreach ($result[0] as $key => $val) {
					if ($key != "xml" && $key != "json") {
						if ($z == 0) {
							echo '"'.$result[$x][$key].'"';
						}
						else {
							echo ',"'.$result[$x][$key].'"';
						}
					}
					$z++;
				}
				echo "\n";
				++$x;
				if ($x > ($result_count-1)) {
					break;
				}
			}
	}

//export as a PDF
	if (permission_exists('xml_cdr_export_pdf') && $export_format == 'pdf') {

		//load pdf libraries
		require_once "resources/tcpdf/tcpdf.php";
		require_once "resources/fpdi/fpdi.php";

		//determine page size
		switch ($_SESSION['fax']['page_size']['text']) {
			case 'a4':
				$page_width = 11.7; //in
				$page_height = 8.3; //in
				break;
			case 'legal':
				$page_width = 14; //in
				$page_height = 8.5; //in
				break;
			case 'letter':
			default	:
				$page_width = 11; //in
				$page_height = 8.5; //in
		}

		// initialize pdf
		$pdf = new FPDI('L', 'in');
		$pdf->SetAutoPageBreak(false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(0.5, 0.5, 0.5, true);

		//set default font
		$pdf->SetFont('helvetica', '', 7);
		//add new page
		$pdf->AddPage('L', array($page_width, $page_height));

		//set the number of columns
		$columns = 12;

		//write the table column headers
		$data_start = '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		$data_end = '</table>';

		$data_head = '<tr>';
		$data_head .= '<td width="5%"><b>'.$text['label-direction'].'</b></td>';
		$data_head .= '<td width="9%"><b>'.$text['label-caller_id_name'].'</b></td>';
		$data_head .= '<td width="9%"><b>'.$text['label-caller_id_number'].'</b></td>';
		$data_head .= '<td width="9%"><b>'.$text['label-destination'].'</b></td>';
		$data_head .= '<td width="10%" nowrap="nowrap"><b>'.$text['label-start'].'</b></td>';
		$data_head .= '<td width="3%" align="right"><b>'.$text['label-tta'].'</b></td>';
		$data_head .= '<td width="8%" align="right"><b>'.$text['label-duration'].'</b></td>';
		$data_head .= '<td width="8%" align="right"><b>'.$text['label-billsec'].'</b></td>';
		$data_head .= '<td width="5%" align="right"><b>'."PDD".'</b></td>';
		$data_head .= '<td width="5%" align="center"><b>'."MOS".'</b></td>';
		if (is_array($_SESSION['cdr']['field'])) {
			foreach ($_SESSION['cdr']['field'] as $field) {
				$array = explode(",", $field);
				$field_name = end($array);
				$field_label = ucwords(str_replace("_", " ", $field_name));
				$field_label = str_replace("Sip", "SIP", $field_label);
				if ($field_name != "destination_number") {
					$data_head .= '<td width="10%" align="left"><b>'.$field_label.'</b></td>';
				}
				$columns = $columns + 1;
			}
		}
		$data_head .= '<td width="1%"></td>';
		$data_head .= '<td width="10%"><b>'.$text['label-hangup_cause'].'</b></td>';
		$data_head .= '</tr>';
		$data_head .= '<tr><td colspan="'.$columns.'"><hr></td></tr>';

		//initialize total variables
		$total['duration'] = 0;
		$total['billmsec'] = 0;
		$total['pdd_ms'] = 0;
		$total['rtp_audio_in_mos'] = 0;
		$total['tta'] = 0;

		//write the row cells
		$z = 0; // total counter
		$p = 0; // per page counter
		if (sizeof($result) > 0) {
			foreach ($result as $cdr_num => $fields) {
				$data_body[$p] .= '<tr>';
				$data_body[$p] .= '<td>'.$text['label-'.$fields['direction']].'</td>';
				$data_body[$p] .= '<td>'.$fields['caller_id_name'].'</td>';
				$data_body[$p] .= '<td>'.$fields['caller_id_number'].'</td>';
				$data_body[$p] .= '<td>'.format_phone($fields['destination_number']).'</td>';
				$data_body[$p] .= '<td>'.$fields['start_stamp'].'</td>';
				$total['tta'] += ($fields['tta'] > 0) ? $fields['tta'] : 0;
				$data_body[$p] .= '<td align="right">'.(($fields['tta'] >= 0) ? $fields['tta'].'s' : null).'</td>';
				$seconds = ($fields['hangup_cause'] == "ORIGINATOR_CANCEL") ? $fields['duration'] : round(($fields['billmsec'] / 1000), 0, PHP_ROUND_HALF_UP);
				$total['duration'] += $seconds;
				$data_body[$p] .= '<td align="right">'.gmdate("G:i:s", $seconds).'</td>';
				$total['billmsec'] += $fields['billmsec'];
				$data_body[$p] .= '<td align="right">'.number_format(round($fields['billmsec'] / 1000, 2), 2).'s</td>';
				$data_body[$p] .= '<td align="right">';
				if (permission_exists("xml_cdr_pdd")) {
					$total['pdd_ms'] += $fields['pdd_ms'];
					$data_body[$p] .= number_format(round($fields['pdd_ms'] / 1000, 2), 2).'s';
				}
				$data_body[$p] .= '</td>';
				$data_body[$p] .= '<td align="center">';
				if (permission_exists("xml_cdr_mos")) {
					$total['rtp_audio_in_mos'] += $fields['rtp_audio_in_mos'];
					$data_body[$p] .= (strlen($total['rtp_audio_in_mos']) > 0) ? $fields['rtp_audio_in_mos'] : null;
				}
				$data_body[$p] .= '</td>';

				if (is_array($_SESSION['cdr']['field'])) {
					foreach ($_SESSION['cdr']['field'] as $field) {
						$array = explode(",", $field);
						$field_name = end($array);
						$field_label = ucwords(str_replace("_", " ", $field_name));
						$field_label = str_replace("Sip", "SIP", $field_label);
						if ($field_name != "destination_number") {
							$data_body[$p] .= '<td align="right">';
							$data_body[$p] .= $fields[$field_name];
							$data_body[$p] .= '</td>';
						}
					}
				}

				$data_body[$p] .= '<td>&nbsp;</td>';
				$data_body[$p] .= '<td>'.ucwords(strtolower(str_replace("_", " ", $fields['hangup_cause']))).'</td>';
				$data_body[$p] .= '</tr>';

				$z++;
				$p++;

				if ($p == 60) {
					//output data
					$data_body_chunk = $data_start.$data_head;
					foreach ($data_body as $data_body_row) {
						$data_body_chunk .= $data_body_row;
					}
					$data_body_chunk .= $data_end;
					$pdf->writeHTML($data_body_chunk, true, false, false, false, '');
					unset($data_body_chunk);
					unset($data_body);
					$p = 0;

					//add new page
					$pdf->AddPage('L', array($page_width, $page_height));
				}

			}

		}

		//write divider
		$data_footer = '<tr><td colspan="'.$columns.'"></td></tr>';

		//write totals
		$data_footer .= '<tr>';
		$data_footer .= '<td><b>'.$text['label-total'].'</b></td>';
		$data_footer .= '<td>'.$z.'</td>';
		$data_footer .= '<td colspan="3"></td>';
		$data_footer .= '<td align="right"><b>'.number_format(round($total['tta'], 1), 0).'s</b></td>';
		$data_footer .= '<td align="right"><b>'.gmdate("G:i:s", $total['duration']).'</b></td>';
		$data_footer .= '<td align="right"><b>'.gmdate("G:i:s", round($total['billmsec'] / 1000, 0)).'</b></td>';
		$data_footer .= '<td align="right"><b>'.number_format(round(($total['pdd_ms'] / 1000), 2), 2).'s</b></td>';
		$data_footer .= '<td colspan="2"></td>';
		$data_footer .= '</tr>';

		//write divider
		$data_footer .= '<tr><td colspan="'.$columns.'"><hr></td></tr>';

		//write averages
		$data_footer .= '<tr>';
		$data_footer .= '<td><b>'.$text['label-average'].'</b></td>';
		$data_footer .= '<td colspan="4"></td>';
		$data_footer .= '<td align="right"><b>'.round(($total['tta'] / $z), 1).'</b></td>';
		$data_footer .= '<td align="right"><b>'.gmdate("G:i:s", ($total['duration'] / $z)).'</b></td>';
		$data_footer .= '<td align="right"><b>'.gmdate("G:i:s", round($total['billmsec'] / $z / 1000, 0)).'</b></td>';
		$data_footer .= '<td align="right"><b>'.number_format(round(($total['pdd_ms'] / $z / 1000), 2), 2).'s</b></td>';
		$data_footer .= '<td align="right"><b>'.round(($total['rtp_audio_in_mos'] / $z), 2).'</b></td>';
		$data_footer .= '<td></td>';
		$data_footer .= '</tr>';

		//write divider
		$data_footer .= '<tr><td colspan="'.$columns.'"><hr></td></tr>';

		//add last page
		if ($p >= 55) {
			$pdf->AddPage('L', array($page_width, $page_height));
		}
		//output remaining data
		$data_body_chunk = $data_start.$data_head;
		foreach ($data_body as $data_body_row) {
			$data_body_chunk .= $data_body_row;
		}
		$data_body_chunk .= $data_footer.$data_end;
		$pdf->writeHTML($data_body_chunk, true, false, false, false, '');
		unset($data_body_chunk);

		//define file name
		$pdf_filename = "cdr_".$_SESSION['domain_name']."_".date("Ymd_His").".pdf";

		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="'.$pdf_filename.'"');
		header("Content-Type: application/pdf");
		header('Accept-Ranges: bytes');
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past

		// push pdf download
		$pdf -> Output($pdf_filename, 'D');	// Display [I]nline, Save to [F]ile, [D]ownload

	}

?>
