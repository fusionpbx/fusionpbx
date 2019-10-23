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

//define the functions
	function array2csv(array &$array)
	{
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


//define possible columns in the array
	$allowed_columns[] = 'device_uuid';
	$allowed_columns[] = 'domain_uuid';
	$allowed_columns[] = 'device_mac_address';
	$allowed_columns[] = 'device_label';
	$allowed_columns[] = 'device_template';
	$allowed_columns[] = 'device_description';

//get the devices and send them as output
	$column_group = $_REQUEST["column_group"];
	if (is_array($column_group) && @sizeof($column_group) != 0) {
		//validate columns
			foreach ($column_group as $index => $column_name) {
				if (!in_array($column_name, $allowed_columns)) {
					unset($column_group[$index]);
				}
			}
		//iterate columns
			if (is_array($column_group) && @sizeof($column_group) != 0) {
				$column_names = implode(", ", $column_group);
				$sql = "select ".$column_names." from v_devices ";
				$sql .= " where domain_uuid = :domain_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
				$database = new database;
				$devices = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters, $column_names);
				//print_r($extensions);

				if (is_array($devices) && @sizeof($devices) != 0) {
					download_send_headers("data_export_".date("Y-m-d").".csv");
					echo array2csv($devices);
					exit();
				}
			}
			unset($column_group);
	}

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//begin the page content
	require_once "resources/header.php";

	echo "<form method='post' name='frm' action='device_download.php' autocomplete='off'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td valign='top' align='left' nowrap='nowrap'><b>".$text['header-export']."</b><br /></td>\n";
	echo "	<td valign='top' align='right' colspan='2'>\n";
	echo "		<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='devices.php'\" value='".$text['button-back']."'>\n";
	echo "	</td>\n";
	echo "	</tr>\n";
	echo "	<th><input type=\"checkbox\" id=\"selectall\" onclick=\"checkbox_toggle();\"/></th>\n";
	echo "	<th>Column Name</th>\n";
	echo "	<th>Description</th>\n";
	echo "</tr>\n";

	foreach ($allowed_columns as $column_name) {
		echo "<tr>\n";
		echo "	<td width = '20px' valign='top' class='".$row_style[$c]."'>\n";
		echo "		<input class=\"checkbox1\" type=\"checkbox\" name=\"column_group[]\" value=\"".$column_name."\"/>";
		echo "	</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".$column_name."</td>";
		echo "	<td valign='top' class='".$row_style[$c]."'></td>";
		echo "</tr>";
		if ($c==0) { $c=1; } else { $c=0; }
	}

	echo "	<tr>\n";
	echo "		<td colspan='3' align='right'>\n";
	echo "			<br>";
	echo "			<input type='submit' class='btn' value='".$text['button-export']."'>\n";
	echo "		</td>\n";
	echo "	</tr>";

	echo "</table>";
	echo "<br><br>";
	echo "</form>";

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
