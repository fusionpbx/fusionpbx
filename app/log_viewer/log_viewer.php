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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
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

//define variables
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//set a default line number value (off)
	if (!isset($_POST['line_number']) || $_POST['line_number'] == '') { $_POST['line_number'] = 0; }

//set a default ordinal (descending)
	if (!isset($_POST['sort']) || $_POST['sort'] == '') { $_POST['sort'] = "asc"; }

//set a default file size
	if (!isset($_POST['size']) || strlen($_POST['size']) == 0) { $_POST['size'] = "32"; }

//set a default filter
	if (!isset($_POST['filter'])) { $_POST['filter'] = ""; }	

//download the log
	if (permission_exists('log_download')) {
		if (isset($_GET['a']) && $_GET['a'] == "download") {
			if (isset($_GET['t']) && $_GET['t'] == "logs") {
				$tmp = $_SESSION['switch']['log']['dir'].'/';
				$filename = 'freeswitch.log';
			}
			session_cache_limiter('public');
			$fd = fopen($tmp.$filename, "rb");
			header("Content-Type: binary/octet-stream");
			header("Content-Length: " . filesize($tmp.$filename));
			header('Content-Disposition: attachment; filename="'.escape($filename).'"');
			fpassthru($fd);
			exit;
		}
	}

//include the header
	require_once "resources/header.php";

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td align='left' valign='top' width='100%' style='padding-right: 15px;' nowrap>\n";
	echo "			<b>".$text['label-log_viewer']."</b><br />\n";
	echo "		</td>\n";
	echo "		<td align='right' valign='middle' nowrap>\n";
	echo "			<form action='log_viewer.php' method='POST'>\n";
	echo "			".$text['label-filter']." <input type='text' name='filter' class='formfld' style='width: 150px; text-align: center; margin-right: 20px;' value=\"".escape($_POST['filter'])."\" onclick='this.select();'>";
	echo "			<label style='margin-right: 20px; margin-top: 4px;'><input type='checkbox' name='line_number' id='line_number' value='1' ".(($_POST['line_number'] == 1) ? 'checked' : null)."> ".$text['label-line_number']."</label>";
	echo "			<label style='margin-right: 20px; margin-top: 4px;'><input type='checkbox' name='sort' id='sort' value='desc' ".(($_POST['sort'] == 'desc') ? 'checked' : null)."> ".$text['label-sort']."</label>";
	echo "			Display <input type='text' class='formfld' style='width: 50px; text-align: center;' name='size' value=\"".escape($_POST['size'])."\" onclick='this.select();'> ".$text['label-size']."";
	echo "			<input type='submit' class='btn' style='margin-left: 20px;' name='submit' value=\"".$text['button-reload']."\">";
	if (permission_exists('log_download')) {
		echo "		<input type='button' class='btn' value='".$text['button-download']."' onclick=\"document.location.href='log_viewer.php?a=download&t=logs';\" />\n";
	}
	echo "			</form>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "	<tr><td colspan='2'>&nbsp;</td></tr>";
	echo "	<tr>\n";
	echo "		<td colspan='2' style='background-color: #1c1c1c; padding: 8px; text-align: left;'>";

	if (permission_exists('log_view')) {

		$MAXEL = 3; //pattern2, pattern3|color2, color3 etc...

		$user_file_size = '0';
		$default_color = '#fff';
		$default_type = 'normal';
		$default_font = 'monospace';
		$default_file_size = '512000';
		$log_file = $_SESSION['switch']['log']['dir']."/freeswitch.log";

		//put the color matches here...
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

		$file_size = filesize($log_file);

		/*
		// removed: duplicate of above
		if (isset($_POST['submit'])) {
			if (strlen($_POST['size']) == 0) { $_POST['size'] = "32"; }
		}
		*/

		echo "		<table cellpadding='0' cellspacing='0' border='0' width='100%'>";
		echo "			<tr>";
		$user_file_size = '32768';
		if (isset($_POST['submit'])) {
			if (!is_numeric($_POST['size'])) {
				//should generate log warning here...
				$user_file_size = 1024 * 32;
			}
			else {
				$user_file_size = $_POST['size'] * 1024;
			}
			if (strlen($_REQUEST['filter']) > 0) {
				$uuid_filter = $_REQUEST['filter'];
				echo "		<td style='text-align: left; color: #FFFFFF;'>".$text['description-filter']." ".escape($uuid_filter)."</td>";
			}
		}

		//echo "Log File Size: " . $file_size . " bytes. <br />";
		echo "				<td style='text-align: right;color: #FFFFFF;'>".$text['label-displaying']." ".number_format($user_file_size,0,'.',',')." of ".number_format($file_size,0,'.',',')." ".$text['label-bytes'].". </td>";
		echo "			</tr>";
		echo "		</table>";
		echo "		<hr size='1' style='color: #fff;'>";

		$file = fopen($log_file, "r") or exit($text['error-open_file']);

		//set pointer in file
		if ($user_file_size >= '0') {
			if ($user_file_size == '0') {
				$user_file_size = $default_file_size;
			}
			if ($file_size >= $user_file_size) {
				//set an offset on fopen
				$byte_count=$file_size-$user_file_size;
				fseek($file, $byte_count);
				//echo "opening at " . $byte_count . " bytes<br>";
			}
			else {
				if ($file_size >= $default_file_size) {
					//set an offset on fopen
					$byte_count=$file_size-$default_file_size;
					fseek($file, $byte_count);
					echo $text['label-open_at']." " . $byte_count . " ".$text['label-bytes']."<br>";
				}
				else {
					//open the file
					$byte_count='0';
					fseek($file, 0);
					echo "<br>".$text['label-open_file']."<br>";
				}
			}
		}
		else {
			if ( $file_size >= $default_file_size ) {
				//set an offset on fopen
				$byte_count = $file_size - $default_file_size;
				fseek($file, $byte_count);
				echo $text['label-open_at']." " . $byte_count . " ".$text['label-bytes']."<br>";
			}
			else {
				//open the file
				$byte_count='0';
				fseek($file, 0);
				echo "<br>".$text['label-open_file']."<br>";
			}
		}

		//start processing
		$byte_count = 0;
		while(!feof($file)) {
			$log_line = escape(fgets($file));
			$byte_count++;
			$noprint = false;

			$skip_line = false;
			if (!empty($uuid_filter) ) {
				$uuid_match = strpos($log_line, $uuid_filter);
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

		// output according to ordinal selected
		if ($_POST['sort'] == 'desc') {
			$array_output = array_reverse($array_output);
			$adj_index = 0;
		}
		else {
			$adj_index = 1;
		}
		foreach ($array_output as $index => $line) {
			$line_num = "";
			if ($line != "<span style='color: #fff; font-family: monospace;'></span><br>") {
				if ($_POST['line_number']) {
					$line_num = "<span style='font-family: courier; color: #aaa; font-size: 10px;'>".($index + $adj_index)."&nbsp;&nbsp;&nbsp;</span>";
				}
				echo $line_num." ".$line;
			}
		}
		fclose($file);
		echo "		</div>";
	}
	echo "		</td>";
	echo "	</tr>\n";
	echo "</table>\n";

//include the footer
	require_once "resources/footer.php";

?>
