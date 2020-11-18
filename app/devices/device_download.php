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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('device_export')) {
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

//define possible columns in the array
	$available_columns['devices'][] = 'device_uuid';
	$available_columns['devices'][] = 'device_profile_uuid';
	$available_columns['devices'][] = 'device_mac_address';
	$available_columns['devices'][] = 'device_label';
	$available_columns['devices'][] = 'device_vendor';
	$available_columns['devices'][] = 'device_template';
	$available_columns['devices'][] = 'device_enabled_date';
	$available_columns['devices'][] = 'device_username';
	$available_columns['devices'][] = 'device_password';
	$available_columns['devices'][] = 'device_uuid_alternate';
	$available_columns['devices'][] = 'device_provisioned_date';
	$available_columns['devices'][] = 'device_provisioned_method';
	$available_columns['devices'][] = 'device_provisioned_ip';
	$available_columns['devices'][] = 'device_enabled';
	$available_columns['devices'][] = 'device_description';

	$available_columns['device_lines'][] = 'device_line_uuid';
	$available_columns['device_lines'][] = 'device_uuid';
	$available_columns['device_lines'][] = 'line_number';
	$available_columns['device_lines'][] = 'server_address';
	$available_columns['device_lines'][] = 'server_address_primary';
	$available_columns['device_lines'][] = 'server_address_secondary';
	$available_columns['device_lines'][] = 'outbound_proxy_primary';
	$available_columns['device_lines'][] = 'outbound_proxy_secondary';
	$available_columns['device_lines'][] = 'display_name';
	$available_columns['device_lines'][] = 'user_id';
	$available_columns['device_lines'][] = 'auth_id';
	$available_columns['device_lines'][] = 'password';
	$available_columns['device_lines'][] = 'sip_port';
	$available_columns['device_lines'][] = 'sip_transport';
	$available_columns['device_lines'][] = 'register_expires';
	$available_columns['device_lines'][] = 'shared_line';
	$available_columns['device_lines'][] = 'enabled';

//get the devices and send them as output
	$column_group = $_REQUEST["column_group"];
	if (is_array($column_group) && @sizeof($column_group) != 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: device_download.php');
				exit;
			}

		//validate table names
			foreach($column_group as $table_name => $columns) {
				if (!isset($available_columns[$table_name])) {
					unset($column_group[$table_name]);
				}
			}

		//validate columns
			foreach($column_group as $table_name => $columns) {
				foreach ($columns as $column_name) {
					if (!in_array($column_name, $available_columns[$table_name])) {
						unset($column_group[$table_name][$column_name]);
					}
				}
			}

		//iterate columns
			if (is_array($column_group) && @sizeof($column_group) != 0) {

				$column_names = implode(", ", $column_group['devices']);
				$sql = "select ".$column_names." from v_devices ";
				$sql .= " where domain_uuid = :domain_uuid ";
				$parameters['domain_uuid'] = $domain_uuid;
				$database = new database;
				$devices = $database->select($sql, $parameters, 'all');
				unset($sql, $parameters, $column_names);

				foreach($column_group as $table_name => $columns) {
					if ($table_name !== 'devices') {
						$column_names = implode(", ", $columns);
						$sql = "select ".$column_names." from v_".$table_name." ";
						$sql .= " where domain_uuid = :domain_uuid ";
						$parameters['domain_uuid'] = $domain_uuid;
						$database = new database;
						$$table_name = $database->select($sql, $parameters, 'all');
						$x = 0;
						foreach($devices as $device) {
							foreach($$table_name as $row) {
								if ($device['device_uuid'] == $row['device_uuid']) {
									foreach($row as $key => $value) {
										$devices[$x][$key] = $value;
									}
									break;
								}
							}
							$x++;
						}
						unset($sql, $parameters, $column_names);
					}
				}

				if (is_array($devices) && @sizeof($devices) != 0) {
					download_send_headers("device_export_".date("Y-m-d").".csv");
					echo array2csv($devices);
					exit;
				}
			}
			unset($column_group);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-device_export'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-device_export']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'devices.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-export'],'icon'=>$_SESSION['theme']['button_icon_export'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-device_export'];
	echo "<br /><br />\n";

	if (is_array($available_columns) && @sizeof($available_columns) != 0) {
		$x = 0;
		foreach ($available_columns as $table_name => $columns) {
			$table_name_label = ucwords(str_replace(['-','_',],' ', $table_name));
			echo "<div class='category'>\n";
			echo "<b>".$table_name_label."</b>\n";
			echo "<br>\n";
			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			echo "	<th class='checkbox'>\n";
			echo "		<input type='checkbox' id='checkbox_all_".$table_name."' name='checkbox_all' onclick=\"list_all_toggle('".$table_name."');\" ".($available_columns ?: "style='visibility: hidden;'").">\n";
			echo "	</th>\n";
			echo "	<th>".$text['label-column_name']."</th>\n";
			echo "</tr>\n";
			foreach ($columns as $column_name) {
				$list_row_onclick = "if (!this.checked) { document.getElementById('checkbox_all').checked = false; }";
				echo "<tr class='list-row' href='".$list_row_url."'>\n";
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' class='checkbox_".$table_name."' name='column_group[".$table_name."][".$column_name."]' id='checkbox_".$x."' value=\"".$column_name."\" onclick=\"".$list_row_onclick."\">\n";
				echo "	</td>\n";
				echo "	<td onclick=\"document.getElementById('checkbox_".$x."').checked = document.getElementById('checkbox_".$x."').checked ? false : true; ".$list_row_onclick."\">".$column_name."</td>";
				echo "</tr>";
				$x++;
			}
			echo "</table>\n";
			echo "<br>\n";
			echo "</div>\n";
		}
	}
	
	//test the validation
	//echo "		<input type='hidden' name='column_group[devices][xxx]'  value=\"xxx\">\n";
	//echo "		<input type='hidden' name='column_group[device_lines][yyy]' value=\"yyy\">\n";
	//echo "		<input type='hidden' name='column_group[device_zzz][zzz]' value=\"zzz\">\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>