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

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('ring_group_export')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//define available columns
	$available_columns = [
		'ring_group_uuid',
		'domain_uuid',
		'dialplan_uuid',
		'ring_group_name',
		'ring_group_extension',
		'ring_group_greeting',
		'ring_group_exit_key',
		'ring_group_call_timeout',
		'ring_group_strategy',
		'ring_group_caller_id_name',
		'ring_group_caller_id_number',
		'ring_group_cid_name_prefix',
		'ring_group_cid_number_prefix',
		'ring_group_distinctive_ring',
		'ring_group_ringback',
		'ring_group_call_screen_enabled',
		'ring_group_call_forward_enabled',
		'ring_group_follow_me_enabled',
		'ring_group_missed_call_app',
		'ring_group_missed_call_data',
		'ring_group_forward_enabled',
		'ring_group_forward_destination',
		'ring_group_forward_toll_allow',
		'ring_group_timeout_app',
		'ring_group_timeout_data',
		'ring_group_context',
		'ring_group_enabled',
		'ring_group_description',
	];
	$destination_column = 'destinations';

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
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename={$filename}");
		header("Content-Transfer-Encoding: binary");
	}

//get the ring groups from the database and send them as output
	if (!empty($_REQUEST["column_group"]) && is_array($_REQUEST["column_group"]) && @sizeof($_REQUEST["column_group"]) != 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'], 'negative');
				header('Location: ring_group_download.php');
				exit;
			}

		//validate submitted columns
			$selected_columns = [];
			$include_destinations = false;
			foreach ($_REQUEST["column_group"] as $column_name) {
				if (in_array($column_name, $available_columns)) {
					$selected_columns[] = $column_name;
				}
				else if ($column_name === $destination_column) {
					$include_destinations = true;
				}
			}

		if (!empty($selected_columns) || $include_destinations) {
			//ensure ring_group_uuid is selected when destinations are included
			if ($include_destinations && !in_array('ring_group_uuid', $selected_columns)) {
				array_unshift($selected_columns, 'ring_group_uuid');
				$prepended_uuid = true;
			}

			$sql = "select ".implode(', ', $selected_columns)." from v_ring_groups ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "order by ring_group_extension asc ";
			$parameters['domain_uuid'] = $domain_uuid;
			$ring_groups = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters);

			if ($include_destinations && is_array($ring_groups)) {
				$sql = "select ring_group_uuid, destination_number from v_ring_group_destinations ";
				$sql .= "where domain_uuid = :domain_uuid ";
				$sql .= "order by ring_group_uuid, destination_number ";
				$parameters['domain_uuid'] = $domain_uuid;
				$rows = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters);

				$dest_map = [];
				if (is_array($rows)) {
					foreach ($rows as $r) {
						$dest_map[$r['ring_group_uuid']][] = $r['destination_number'];
					}
				}

				foreach ($ring_groups as $i => $rg) {
					$ring_groups[$i][$destination_column] = isset($dest_map[$rg['ring_group_uuid']]) ? implode('|', $dest_map[$rg['ring_group_uuid']]) : '';
				}

				if (!empty($prepended_uuid)) {
					foreach ($ring_groups as $i => $rg) {
						unset($ring_groups[$i]['ring_group_uuid']);
					}
				}
			}

			download_send_headers("ring_group_export_".date("Y-m-d").".csv");
			echo array2csv($ring_groups);
			exit;
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-ring_group_export'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-ring_group_export']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','link'=>'ring_groups.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-export'],'icon'=>$settings->get('theme', 'button_icon_export'),'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-ring_group_export'];
	echo "<br /><br />\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th class='checkbox'>\n";
	echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".(empty($available_columns) ? "style='visibility: hidden;'" : null).">\n";
	echo "	</th>\n";
	echo "	<th>".$text['label-column_name']."</th>\n";
	echo "</tr>\n";

	$all_columns = $available_columns;
	$all_columns[] = $destination_column;

	$x = 0;
	foreach ($all_columns as $column_name) {
		$list_row_onclick = "if (!this.checked) { document.getElementById('checkbox_all').checked = false; }";
		echo "<tr class='list-row'>\n";
		echo "	<td class='checkbox'>\n";
		echo "		<input type='checkbox' name='column_group[]' id='checkbox_".$x."' value=\"".$column_name."\" onclick=\"".$list_row_onclick."\">\n";
		echo "	</td>\n";
		echo "	<td onclick=\"document.getElementById('checkbox_".$x."').checked = document.getElementById('checkbox_".$x."').checked ? false : true; ".$list_row_onclick."\">".$column_name."</td>";
		echo "</tr>";
		$x++;
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
