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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
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

//set a default line number value (off)
	if (!isset($_POST['line_number']) || $_POST['line_number'] == '') {
		$_POST['line_number'] = 0;
	}

//set a default ordinal (descending)
	if (!isset($_POST['sort']) || $_POST['sort'] == '') {
		$_POST['sort'] = "asc";
	}

//set a default file size
	if (!isset($_POST['size']) || strlen($_POST['size']) == 0) {
		$_POST['size'] = "32";
	}

//set a default filter
	if (!isset($_POST['filter'])) {
		$_POST['filter'] = '';
	}

//set default default log file
	if (isset($_POST['log_file'])) {
		$approved_files = glob($_SESSION['switch']['log']['dir'].'/freeswitch.log*');
		if (is_array($approved_files)) {
			foreach($approved_files as $approved_file) {
				if ($approved_file == $_SESSION['switch']['log']['dir'].'/'.$_POST['log_file']) {
					$log_file = $approved_file;
				}
			}
		}
	}
	else {
		$log_file = $_SESSION['switch']['log']['dir'].'/freeswitch.log';
	}

//download the log
	if (permission_exists('log_download')) {
		if (isset($_GET['n'])) {
			if (isset($filename)) { unset($filename); }
			$approved_files = glob($_SESSION['switch']['log']['dir'].'/freeswitch.log*');
			if (is_array($approved_files)) {
				foreach($approved_files as $approved_file) {
					if ($approved_file == $_SESSION['switch']['log']['dir'].'/'.$_GET['n']) {
						$filename = $approved_file;
					}
				}
			}
			if (isset($filename) && file_exists($filename)) {
				session_cache_limiter('public');
				$fd = fopen($filename, "rb");
				header("Content-Type: binary/octet-stream");
				header("Content-Length: " . filesize($filename));
				header('Content-Disposition: attachment; filename="'.basename($filename).'"');
				fpassthru($fd);
				exit;
			}
		}
	}

//get the file size
	if (file_exists($log_file)) {
		$file_size = filesize($log_file);
	}

//open the log file
	if (file_exists($log_file)) {
		$file = fopen($log_file, "r") or exit($text['error-open_file']);
	}

//include the header
	$document['title'] = $text['title-log_viewer'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-log_viewer']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo 		"<form name='frm' id='frm' class='inline' method='post'>\n";
	echo "			".$text['label-log_file']." <select name='log_file' class='formfld' style='width: 150px; margin-right: 20px;'>";
	$files = glob($_SESSION['switch']['log']['dir'].'/freeswitch.log*');
	if (is_array($files)) {
		foreach($files as $file_name) {
			$selected = ($file_name == $log_file) ? "selected='selected'" : "";
			echo "			<option value='".basename($file_name)."'".$selected.">".basename($file_name)."</option>";
		}
	}
	echo "			</select>\n";
	echo 		$text['label-filter']." <input type='text' name='filter' class='formfld' style='width: 150px; text-align: center; margin-right: 20px;' value=\"".escape($_POST['filter'])."\" onclick='this.select();'>";
	echo 		"<label style='margin-right: 20px; margin-top: 4px;'><input type='checkbox' name='line_number' id='line_number' value='1' ".($_POST['line_number'] == 1 ? 'checked' : null)."> ".$text['label-line_number']."</label>";
	echo 		"<label style='margin-right: 20px; margin-top: 4px;'><input type='checkbox' name='sort' id='sort' value='desc' ".($_POST['sort'] == 'desc' ? 'checked' : null)."> ".$text['label-sort']."</label>";
	echo 		$text['label-display']." <input type='text' class='formfld' style='width: 50px; text-align: center;' name='size' value=\"".escape($_POST['size'])."\" onclick='this.select();'> ".$text['label-size'];
	echo button::create(['type'=>'submit','label'=>$text['button-update'],'icon'=>$_SESSION['theme']['button_icon_save'],'style'=>'margin-left: 15px;','name'=>'submit']);
	if (permission_exists('log_download')) {
		echo button::create(['type'=>'button','label'=>$text['button-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'style'=>'margin-left: 15px;','link'=>'log_viewer.php?a=download&n='.basename($log_file)]);
	}
	echo 		"</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td style='background-color: #1c1c1c; padding: 8px; text-align: left;'>";

	if (permission_exists('log_view')) {

		$MAXEL = 3; //pattern2, pattern3|color2, color3 etc...

		$user_file_size = '0';
		$default_color = '#fff';
		$default_type = 'normal';
		$default_font = 'monospace';
		$default_file_size = '512000';

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

		$array_filter[6]['pattern'] = '[CRIT]';
		$array_filter[6]['color'] = 'red';
		$array_filter[6]['type'] = 'bold';
		$array_filter[6]['font'] = 'monospace';

		$file_size = filesize($log_file);

		/*
		// removed: duplicate of above
		if (isset($_POST['submit'])) {
			if (strlen($_POST['size']) == 0) { $_POST['size'] = "32"; }
		}
		*/

		echo "<div style='padding-bottom: 10px; text-align: right; color: #fff; margin-bottom: 15px; border-bottom: 1px solid #fff;'>";
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
				$filter = $_REQUEST['filter'];
			}
		}
		//echo "Log File Size: " . $file_size . " bytes. <br />";
		echo "	".$text['label-displaying']." ".number_format($user_file_size,0,'.',',')." of ".number_format($file_size,0,'.',',')." ".$text['label-bytes'].".";
		echo "</div>";

		//set pointer in file
		if ($user_file_size >= '0') {
			if ($user_file_size == '0') {
				$user_file_size = $default_file_size;
			}
			if ($file_size >= $user_file_size) {
				//set an offset on fopen
				$byte_count = $file_size-$user_file_size;
				fseek($file, $byte_count);
				//echo "opening at " . $byte_count . " bytes<br>";
			}
			else {
				if ($file_size >= $default_file_size) {
					//set an offset on fopen
					$byte_count = $file_size-$default_file_size;
					fseek($file, $byte_count);
					echo $text['label-open_at']." " . $byte_count . " ".$text['label-bytes']."<br>";
				}
				else {
					//open the file
					$byte_count ='0';
					if ($file) {
						fseek($file, 0);
					}
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
		if ($file) {
			while(!feof($file)) {
				$log_line = escape(fgets($file));
				$byte_count++;
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
		if ($_POST['sort'] == 'desc') {
			$array_output = array_reverse($array_output);
			$adj_index = 0;
		}
		else {
			$adj_index = 1;
		}
		if (is_array($array_output)) {
			foreach ($array_output as $index => $line) {
				$line_num = "";
				if ($line != "<span style='color: #fff; font-family: monospace;'></span><br>") {
					if ($_POST['line_number']) {
						$line_num = "<span style='font-family: courier; color: #aaa; font-size: 10px;'>".($index + $adj_index)."&nbsp;&nbsp;&nbsp;</span>";
					}
					echo $line_num." ".$line;
				}
			}
		}

		echo "		</div>";
	}
	echo "		</td>";
	echo "	</tr>\n";
	echo "</table>\n";

//include the footer
	require_once "resources/footer.php";

//close the file
	if ($file) {
		fclose($file);
	}

?>
