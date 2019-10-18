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
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (if_group("superadmin")) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//define available columns
	$available_columns[] = 'pin_number_uuid';
	$available_columns[] = 'domain_uuid';
	$available_columns[] = 'pin_number';
	$available_columns[] = 'accountcode';
	$available_columns[] = 'enabled';
	$available_columns[] = 'description';

//define the functions
	function array2csv(array &$array) {
		if (count($array) == 0) {
			return null;
		}
		ob_start();
		$df = fopen("php://output", 'w');
		fputcsv($df, array_keys(reset($array)));
		foreach ($array as $row) {
			fputcsv($df, $row);
		}
		fclose($df);
		return ob_get_clean();
	}

	function download_send_headers($filename) {
		// disable caching
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");

		// force download
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");

		// disposition / encoding on response body
		header("Content-Disposition: attachment;filename={$filename}");
		header("Content-Transfer-Encoding: binary");
	}

//get the pin numbers from the database and send them as output
	if (is_array($_REQUEST["column_group"]) && @sizeof($_REQUEST["column_group"]) != 0) {
		//validate submitted columns
		foreach($_REQUEST["column_group"] as $column_name) {
			if (in_array($column_name, $available_columns)) {
				$selected_columns[] = $column_name;
			}
		}
		if (is_array($selected_columns) && @sizeof($selected_columns) != 0) {
			$sql = "select ".implode(', ', $selected_columns)." from v_pin_numbers ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$database = new database;
			$pin_numbers = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters, $selected_columns);

			download_send_headers("data_export_".date("Y-m-d") . ".csv");
			echo array2csv($pin_numbers);
			exit;
		}
	}

//set the row styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//begin the page content
	require_once "resources/header.php";

	echo "<form method='post' name='frm' action='pin_download.php' autocomplete='off'>\n";

	echo "<div style='float: right;'>\n";
	echo "<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='pin_numbers.php'\" value='".$text['button-back']."'>\n";
	echo "<input type='submit' class='btn' value='".$text['button-export']."'>\n";
	echo "</div>\n";
	echo "<b>".$text['header-export']."</b>\n";
	echo "<br /><br />\n";

	echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<th style='padding: 0;'><input type='checkbox' id='selectall' onclick='checkbox_toggle();'/></th>\n";
	echo "	<th width='100%'>".$text['label-column_name']."</th>\n";
	echo "</tr>\n";

	foreach ($available_columns as $column_name) {
		$tr_link = "onclick=\"document.getElementById('checkbox_".$column_name."').checked = document.getElementById('checkbox_".$column_name."').checked ? false : true;\"";
		echo "<tr>\n";
		echo "  <td valign='middle' class='".$row_style[$c]."' style='padding: 0;'><input class='checkbox1' type='checkbox' name='column_group[]' id=\"checkbox_".$column_name."\" value=\"".$column_name."\" /></td>\n";
		echo "  <td valign='middle' class='".$row_style[$c]."' ".$tr_link.">".$column_name."</td>\n";
		echo "</tr>\n";
		$c = $c ? 0 : 1;
	}

	echo "<tr>\n";
	echo "	<td colspan='2' align='right'>\n";
	echo "		<br>\n";
	echo "		<input type='submit' class='btn' value='".$text['button-export']."'>\n";
	echo "	</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "<br><br>\n";
	echo "</form>\n";

	//define the checkbox_toggle function
	echo "<script type=\"text/javascript\">\n";
	echo "	function checkbox_toggle(item) {\n";
	echo "		var inputs = document.getElementsByTagName(\"input\");\n";
	echo "		for (var i = 0, max = inputs.length; i < max; i++) {\n";
	echo "			if (inputs[i].type === 'checkbox') {\n";
	echo "				if (document.getElementById('selectall').checked == true) {\n";
	echo "				inputs[i].checked = true;\n";
	echo "			}\n";
	echo "				else {\n";
	echo "					inputs[i].checked = false;\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>
