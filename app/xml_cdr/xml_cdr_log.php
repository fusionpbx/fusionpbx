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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('log_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the ID
	$xml_cdr_uuid = $_GET['id'] ?? '';
	$action = $_GET['a'] ?? '';

//get the cdr json from the database
	$sql = "select * from v_xml_cdr_logs ";
	if (permission_exists('xml_cdr_all')) {
		$sql .= "where xml_cdr_uuid  = :xml_cdr_uuid ";
	}
	else {
		$sql .= "where xml_cdr_uuid  = :xml_cdr_uuid ";
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$parameters['xml_cdr_uuid'] = $xml_cdr_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (!empty($row) && is_array($row) && @sizeof($row) != 0) {
		$log_content = trim($row["log_content"]);
	}
	unset($sql, $parameters, $row);

//start processing
	$byte_count = strlen($log_content);

//download the log
	if (permission_exists('log_download')) {
		$file_name = 'call_log.txt';
		if (isset($file_name) && $action == 'download' && isset($log_content)) {
			header("Content-Type: binary/octet-stream");
			header("Content-Length: " . strlen($log_content));
			header('Content-Disposition: attachment; filename="'.basename($file_name).'"');
			echo $log_content;
			exit;
		}
	}

//define the variables
	$MAXEL = 3; //pattern2, pattern3|color2, color3 etc...
	$user_file_size = '0';
	$default_color = '#fff';
	$default_type = 'normal';
	$default_font = 'monospace';
	$default_file_size = '512000';

//create the filter array, put the color matches here...
	$array_filter[0]['pattern'] = '[NOTICE]';
	$array_filter[0]['color'] = 'cyan';
	$array_filter[0]['type'] = 'normal';
	$array_filter[0]['font'] = 'monospace';

	$array_filter[1]['pattern'] = '[INFO]';
	$array_filter[1]['color'] = 'chartreuse';
	$array_filter[1]['type'] = 'normal';
	$array_filter[1]['font'] = 'monospace';

	$array_filter[2]['pattern'] = 'Dialplan:';
	$array_filter[2]['color'] = 'burlywood';
	$array_filter[2]['type'] = 'normal';
	$array_filter[2]['font'] = 'monospace';
	$array_filter[2]['pattern2'] = 'Regex (PASS)';
	$array_filter[2]['color2'] = 'chartreuse';
	$array_filter[2]['pattern3'] = 'Regex (FAIL)';
	$array_filter[2]['color3'] = 'red';

	$array_filter[3]['pattern'] = '[WARNING]';
	$array_filter[3]['color'] = 'fuchsia';
	$array_filter[3]['type'] = 'normal';
	$array_filter[3]['font'] = 'monospace';

	$array_filter[4]['pattern'] = '[ERR]';
	$array_filter[4]['color'] = 'red';
	$array_filter[4]['type'] = 'bold';
	$array_filter[4]['font'] = 'monospace';

	$array_filter[5]['pattern'] = '[DEBUG]';
	$array_filter[5]['color'] = 'gold';
	$array_filter[5]['type'] = 'bold';
	$array_filter[5]['font'] = 'monospace';

	$array_filter[6]['pattern'] = '[CRIT]';
	$array_filter[6]['color'] = 'red';
	$array_filter[6]['type'] = 'bold';
	$array_filter[6]['font'] = 'monospace';

//include the header
	$document['title'] = $text['label-call_log'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['label-call_log']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'style'=>'margin-left: 15px;','link'=>'xml_cdr_details.php?id='.$xml_cdr_uuid]);
	if (permission_exists('log_download')) {
		echo button::create(['type'=>'button','label'=>$text['button-download'],'icon'=>$settings->get('theme', 'button_icon_download'),'style'=>'margin-left: 15px;','link'=>'xml_cdr_log.php?id='.$xml_cdr_uuid.'&a=download']);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<div class='card'>\n";
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td style='background-color: #1c1c1c; padding: 8px; text-align: left;'>";

	if (!empty($log_content)) {
		$log_array = explode("\n", $log_content);
		foreach ($log_array as $log_line) {
			$log_line = escape($log_line);
			$noprint = false;

			$skip_line = false;
			if (!empty($filter)) {
				$uuid_match = strpos($log_line, $filter);
				if ($uuid_match === false) {
					$skip_line = true;
				}
				else {
					$skip_line = false;
				}
			}

			if ($skip_line === false) {
				foreach ($array_filter as $v1) {
					$pos = strpos($log_line, escape($v1['pattern']));
					//echo "</br> POS is: '$pos'</br>";
					if ($pos !== false) {
						//color adjustments on words in log line
						for ($i=2; $i<=$MAXEL; $i++) {
							if (isset($v1["pattern".$i])) {
								$log_line = str_replace(escape($v1["pattern".$i]), "<span style='color: ".$v1["color".$i].";'>".$v1["pattern".$i]."</span>", $log_line);
							}
						}
						$array_output[] = "<span style='color: ".$v1['color']."; font-family: ".$v1['font'].";'>".$log_line."</span><br>";
						$noprint = true;
					}
				}

				if ($noprint !== true) {
					$array_output[] = "<span style='color: ".$default_color."; font-family: ".$default_font.";'>".$log_line."</span><br>";
				}
			}
		}
	}

	// output according to ordinal selected
	if (isset($_POST['sort']) && $_POST['sort'] == 'desc') {
		$array_output = array_reverse($array_output);
		$adj_index = 0;
	}
	else {
		$adj_index = 1;
	}
	if (!empty($array_output) && is_array($array_output)) {
		foreach ($array_output as $index => $line) {
			$line_num = "";
			if ($line != "<span style='color: #fff; font-family: monospace;'></span><br>") {
				if (isset($_POST['line_number']) && $_POST['line_number']) {
					$line_num = "<span style='font-family: courier; color: #aaa; font-size: 10px;'>".($index + $adj_index)."&nbsp;&nbsp;&nbsp;</span>";
				}
				echo $line_num." ".$line;
			}
		}
	}

	echo "		</div>";

	echo "		</td>";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "</div>\n";

//include the footer
	require_once "resources/footer.php";

?>
