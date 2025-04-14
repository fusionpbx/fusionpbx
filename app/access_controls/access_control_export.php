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
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('access_control_node_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//initialize the database object
	$database = new database;

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//define available columns
	$available_columns[] = 'node_type';
	$available_columns[] = 'node_cidr';
	$available_columns[] = 'node_description';
	$available_columns[] = 'insert_date';
	$available_columns[] = 'insert_user';
	$available_columns[] = 'update_date';
	$available_columns[] = 'update_user';

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$access_control_uuid = $_REQUEST["id"];
	}

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

//send download headers
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

//get the extensions from the database and send them as output
	if (!empty($_REQUEST["column_group"]) && is_array($_REQUEST["column_group"]) && @sizeof($_REQUEST["column_group"]) != 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: access_control_export.php');
				exit;
			}

		//validate submitted columns
		foreach ($_REQUEST["column_group"] as $column_name) {
			if (in_array($column_name, $available_columns)) {
				$selected_columns[] = $column_name;
			}
		}
		if (!empty($access_control_uuid) && is_uuid($access_control_uuid) && is_array($selected_columns) && @sizeof($selected_columns) != 0) {
			//get the child data
			$sql = "select ".implode(', ', $selected_columns)." from v_access_control_nodes ";
			$sql .= "where access_control_uuid = :access_control_uuid ";
			$sql .= "order by node_cidr asc";
			$parameters['access_control_uuid'] = $access_control_uuid;
			$access_control_nodes = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters, $selected_columns);

			//send the download headers
			download_send_headers("access_control_export_".date("Y-m-d").".csv");

			//output the data
			echo array2csv($access_control_nodes);
			exit;
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-access_control_export'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-access_control_export']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','link'=>'access_control_edit.php?id='.$access_control_uuid]);
	echo button::create(['type'=>'submit','label'=>$text['button-export'],'icon'=>$settings->get('theme', 'button_icon_export'),'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-access_control_export'];
	echo "<br /><br />\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th class='checkbox'>\n";
	echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".(empty($available_columns) ? "style='visibility: hidden;'" : null).">\n";
	echo "	</th>\n";
	echo "	<th>".$text['label-column_name']."</th>\n";
	echo "</tr>\n";

	if (!empty($available_columns) && is_array($available_columns) && @sizeof($available_columns) != 0) {
		$x = 0;
		foreach ($available_columns as $column_name) {
			$list_row_onclick = "if (!this.checked) { document.getElementById('checkbox_all').checked = false; }";
			echo "<tr class='list-row'>\n";
			echo "	<td class='checkbox'>\n";
			echo "		<input type='checkbox' name='column_group[]' id='checkbox_".$x."' value=\"".$column_name."\" onclick=\"".$list_row_onclick."\">\n";
			echo "	</td>\n";
			echo "	<td onclick=\"document.getElementById('checkbox_".$x."').checked = document.getElementById('checkbox_".$x."').checked ? false : true; ".$list_row_onclick."\">".$column_name."</td>";
			echo "</tr>";
			$x++;
		}
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
