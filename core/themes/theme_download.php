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
	if (!permission_exists('theme_export')) {
		echo "access denied";
		exit;
	}

//get the theme uuid
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$theme_uuid = $_REQUEST["id"] ?? '';
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//define label
	$label_required = $text['label-required'];

//define available columns
	$available_columns['themes'][] = 'theme_uuid';
	$available_columns['themes'][] = 'theme_category';
	$available_columns['themes'][] = 'theme_name';
	$available_columns['themes'][] = 'theme_enabled';
	$available_columns['themes'][] = 'theme_description';

	$available_columns['theme_settings'][] = 'theme_uuid';
	$available_columns['theme_settings'][] = 'theme_setting_uuid';
	$available_columns['theme_settings'][] = 'theme_setting_name';
	$available_columns['theme_settings'][] = 'theme_setting_type';
	$available_columns['theme_settings'][] = 'theme_setting_value';
	$available_columns['theme_settings'][] = 'theme_setting_order';
	$available_columns['theme_settings'][] = 'theme_setting_enabled';
	$available_columns['theme_settings'][] = 'theme_setting_description';

//define the functions
	/**
	 * Converts a multi-dimensional array to CSV format.
	 *
	 * This function assumes that the input array is a collection of themes,
	 * where each theme has an array of columns. The function will take all
	 * column headers from all themes and use them as the header row in the
	 * generated CSV file.
	 *
	 * If any duplicate column headers are found, they will be removed by
	 * truncating at the pipe character (|).
	 *
	 * @param array &$array A multi-dimensional array of theme data.
	 *
	 * @return string The CSV formatted data as a string. Returns null if the input array is empty.
	 */
	function array2csv(array &$array) {
		if (count($array) == 0) {
			return null;
		}

		//get all headers as first theme may not have all columns
		$headers = [];
		foreach ($array as $theme) {
			//get the column headers for this theme
			$columns = array_keys($theme);
			//check if there are more column headers than previous themes
			if (count($columns) > count($headers)) {
				//use the theme with all columns
				$headers = $columns;
			}
		}

		//find and remove the "|2" that denotes a duplicate header
		foreach ($headers as $header) {
			$pos = strpos($header, '|');
			if ($pos !== false) {
				$header = substr($header, 0, $pos);
			}
		}

		ob_start();
		$file_pointer = fopen("php://output", 'w');
		fputcsv($file_pointer, $headers);
		foreach ($array as $row) {
			fputcsv($file_pointer, $row);
		}
		fclose($file_pointer);
		return ob_get_clean();
	}

	/**
	 * Sends HTTP headers to force a file download.
	 *
	 * This function sets various HTTP headers to instruct the browser to download the file instead of displaying it in the browser window.
	 *
	 * @param string $filename The filename to use for the downloaded file.
	 *
	 * @return void No return value. This function only sends HTTP headers and does not generate any output.
	 */
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

//get the themes and send them as output
	$column_group = $_REQUEST["column_group"] ?? null;
	if (is_array($column_group) && @sizeof($column_group) != 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: theme_download.php');
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

				//theme_uuid must be exported
				$column_group['themes']['theme_uuid'] = 'theme_uuid';

				$column_names = implode(", ", $column_group['themes']);
				$sql = "select ".$column_names." from v_themes ";
				if (!empty($theme_uuid)) {
					$sql .= "where theme_uuid = :theme_uuid ";
					$parameters['theme_uuid'] = $theme_uuid;
				}
				$themes = $database->select($sql, $parameters ?? null, 'all');
				unset($sql, $parameters, $column_names);

				foreach($column_group as $table_name => $columns) {
					if ($table_name !== 'themes') {
						//theme_uuid must be included in child table to match export row
						$columns['theme_uuid'] = 'theme_uuid';
						$column_names = implode(", ", $columns);
						$sql = "select ".$column_names." from v_".$table_name." ";
						if (!empty($theme_uuid)) {
 							$sql .= "where theme_uuid = :theme_uuid ";
							$parameters['theme_uuid'] = $theme_uuid;
						}
						$child_table_result = $database->select($sql, $parameters ?? null, 'all');
						$x = 0;
						foreach($themes as $theme) {
							$matching = array_filter($child_table_result, fn($r) => $r['theme_uuid'] === $theme['theme_uuid']);
							if (empty($matching)) {
								$themes[] = array_merge($theme, array_fill_keys(
									array_diff($columns, ['theme_uuid']), ''
								));
							} else {
								foreach ($matching as $setting) {
									$themes[] = array_merge($theme, $setting);
								}
							}
						}
						unset($sql, $parameters, $column_names);
					}
				}

				if (is_array($themes) && @sizeof($themes) != 0) {
					$file_prefix = (!empty($theme_uuid) ? str_replace(' ', '_', strtolower($themes[0]['theme_name'])).'_' : null);
					download_send_headers($file_prefix."theme_export_".date("Y-m-d").".csv");
					echo array2csv($themes);
					exit;
				}
			}
			unset($column_group);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-theme_export'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-theme_export']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','link'=>'themes.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-export'],'icon'=>$settings->get('theme', 'button_icon_export'),'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-theme_export'];
	echo "<br /><br />\n";

	if (is_array($available_columns) && @sizeof($available_columns) != 0) {
		$x = 0;
		foreach ($available_columns as $table_name => $columns) {
			$table_name_label = ucwords(str_replace(['-','_',],' ', $table_name));
			echo "<div class='card'>\n";
			echo "<div class='category'>\n";
			echo "<b>".$table_name_label."</b>\n";
			echo "<br>\n";
			echo "<table class='list'>\n";
			echo "<tr class='list-header'>\n";
			echo "	<th class='checkbox'>\n";
			echo "		<input type='checkbox' id='checkbox_all_".$table_name."' name='checkbox_all' onclick=\"list_all_toggle('".$table_name."');\" ".(empty($available_columns) ? "style='visibility: hidden;'" : null)." checked>\n";
			echo "	</th>\n";
			echo "	<th>".$text['label-column_name']."</th>\n";
			echo "</tr>\n";
			foreach ($columns as $column_name) {
				$list_row_onclick = "if (!this.checked) { document.getElementById('checkbox_all').checked = false; }";
				echo "<tr class='list-row' href='".($list_row_url ?? '')."'>\n";
				echo "	<td class='checkbox'>\n";
				//theme_uuid must be selected on themes to avoid duplication on import
				if ($table_name == 'themes' && $column_name == 'theme_uuid') {
					echo "		<input type='checkbox' title='$label_required' class='disabled_checkbox_themes' name='column_group[$table_name][$column_name]' id='checkbox_$x' value='$column_name' onclick='return false;' checked>\n";
				} else {
					echo "		<input type='checkbox' class='checkbox_".$table_name."' name='column_group[".$table_name."][".$column_name."]' id='checkbox_".$x."' value=\"".$column_name."\" onclick=\"".$list_row_onclick."\" checked>\n";
				}
				echo "	</td>\n";
				if ($table_name == 'themes' && $column_name == 'theme_uuid') {
					echo "	<td title='$label_required'>".$column_name."</td>";
				} else {
					echo "	<td onclick=\"document.getElementById('checkbox_".$x."').checked = document.getElementById('checkbox_".$x."').checked ? false : true; ".$list_row_onclick."\">".$column_name."</td>";
				}
				echo "</tr>";
				$x++;
			}
			echo "</table>\n";
			echo "<br>\n";
			echo "</div>\n";
			echo "</div>\n";
		}
	}

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
