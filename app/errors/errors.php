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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('errors_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set defaults
	if (!is_numeric($_POST['line_number'])) { $_POST['line_number'] = 0; }
	if ($_POST['sort'] != 'asc' && $_POST['sort'] != 'desc') { $_POST['sort'] = 'asc'; }
	if (!is_numeric($_POST['lines'])) { $_POST['lines'] = '10'; }

//include the header
	$document['title'] = $text['title-server_errors'];
	require_once "resources/header.php";

//show the content
	$error_file = $_SESSION['server']['error']['text'].($_POST['log'] == 'previous' ? '.1' : null);
	if (file_exists($error_file)) {

		//colored lines
		$x = 0;
		$filters[$x]['pattern'] = '[error]';
		$filters[$x]['color'] = '#cc0000';
		$x++;
		$filters[$x]['pattern'] = '[crit]';
		$filters[$x]['color'] = 'gold';

		$file_lines = file($error_file, FILE_SKIP_EMPTY_LINES);

		echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
		echo "	<tr>\n";
		echo "		<td align='left' valign='top' width='100%' style='padding-right: 15px;' nowrap>\n";
		echo "			<b>".$text['header-server_errors']."</b><br />\n";
		echo "		</td>\n";
		echo "		<td align='right' valign='middle' nowrap>\n";
		echo "			<form method='post'>\n";
		echo "			".$text['label-log'];
		echo "			<select class='formfld' name='log' style='margin-right: 20px; margin-top: 4px;'>\n";
		echo "				<option value='current'>".$text['label-current']."</option>\n";
		if (file_exists($_SESSION['server']['error']['text'].'.1')) {
			echo "			<option value='previous' ".($_POST['log'] == 'previous' ? 'selected' : null).">".$text['label-previous']."</option>\n";
		}
		echo "			</select>\n";
		echo "			".$text['label-filter']." <input type='text' name='filter' class='formfld' style='width: 150px; text-align: center; margin-right: 20px;' value=\"".escape($_POST['filter'])."\" onclick='this.select();'>";
		echo "			<label style='margin-right: 20px; margin-top: 4px;'><input type='checkbox' name='line_number' id='line_number' value='1' ".(($_POST['line_number'] == 1) ? 'checked' : null)."> ".$text['label-line_numbers']."</label>";
		echo "			<label style='margin-right: 20px; margin-top: 4px;'><input type='checkbox' name='sort' id='sort' value='desc' ".(($_POST['sort'] == 'desc') ? 'checked' : null)."> ".$text['label-sort']."</label>";
		echo "			".$text['label-display']." <input type='text' class='formfld' style='min-width: 50px; max-width: 50px; width: 50px; text-align: center;' name='lines' maxlength='5' value=\"".escape($_POST['lines'])."\" onclick='this.select();'> of ".count($file_lines)." ".$text['label-lines'];
		echo "			<input type='submit' class='btn' style='margin-left: 20px;' name='submit' value=\"".$text['button-reload']."\">";
		echo "			</form>\n";
		echo "		</td>\n";
		echo "	</tr>\n";
		echo "</table>\n";
		echo "<br>\n";

		echo "<div id='file_content' style='max-height: 600px; overflow: auto; color: #aaa; background-color: #1c1c1c; border-radius: 4px; padding: 8px; text-align: left;'>\n";

		if (is_array($file_lines) && sizeof($file_lines) > 0) {
			echo "<span style='font-family: monospace;'>\n";
			if ($_POST['filter'] != '') {
				foreach ($file_lines as $index => $line) {
					if (strpos($line, $_POST['filter']) == false) {
						unset($file_lines[$index]);
					}
				}
			}
			if (is_numeric($_POST['lines']) && $_POST['lines'] > 0) {
				$file_lines = array_slice($file_lines, -$_POST['lines'], $_POST['lines'], true);
			}
			if ($_POST['sort'] == 'desc') {
				$file_lines = array_reverse($file_lines, true);
			}
			foreach ($file_lines as $index => $line) {
				foreach ($filters as $filter) {
					$pos = strpos($line, $filter['pattern']);
					if ($pos !== false){
						$filter_beg = "<span style='color: ".$filter['color'].";'>";
						$line = str_replace($_POST['filter'],"<span style='background-color: #ffd800; color: #ff6600; font-weight: bold;'>".$_POST['filter']."</span>", $line);
						$filter_end = "</span>";
					}
				}
				if ($_POST['line_number']) {
					$line_num = "<span style='font-family: courier; color: #aaa; font-size: 11px;'>".($index + 1)."&nbsp;&nbsp;&nbsp;</span>";
				}
				echo $line_num." ".$filter_beg.$line.$filter_end."<br><br>";
			}
			echo "</span>\n";
		}
		else {
			echo "<center style='font-family: monospace;'><br>[ EMPTY FILE ]<br><br></center>";
		}

		echo "	<span id='bottom'></span>\n";
		echo "</div>\n";

	}
	else {
		if ($_SESSION['server']['error']['text'] != '') {
			echo "Server error log file not found at: ".$_SESSION['server']['error']['text'];
		}
		else {
			echo "Server error log file path not defined in Settings.";
		}
	}

// scroll to bottom of displayed lines, when appropriate
	if ($_POST['sort'] != 'desc') {
		echo "<script>\n";
		//note: the order of the two lines below matters!
		echo "	$('#file_content').scrollTop(Number.MAX_SAFE_INTEGER);\n"; //chrome
		echo "	$('span#bottom')[0].scrollIntoView(true);\n"; //others
		echo "</script>\n";
	}

//include the footer
	require_once "resources/footer.php";

?>
