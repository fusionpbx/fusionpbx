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
	Portions created by the Initial Developer are Copyright (C) 2008-2021
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('extension_export')) {
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
	$available_columns[] = 'extension_uuid';
	$available_columns[] = 'domain_uuid';
	$available_columns[] = 'extension';
	$available_columns[] = 'number_alias';
	$available_columns[] = 'password';
	$available_columns[] = 'accountcode';
	$available_columns[] = 'effective_caller_id_name';
	$available_columns[] = 'effective_caller_id_number';
	$available_columns[] = 'outbound_caller_id_name';
	$available_columns[] = 'outbound_caller_id_number';
	$available_columns[] = 'emergency_caller_id_name';
	$available_columns[] = 'emergency_caller_id_number';
	$available_columns[] = 'directory_first_name';
	$available_columns[] = 'directory_last_name';
	$available_columns[] = 'directory_visible';
	$available_columns[] = 'directory_exten_visible';
	$available_columns[] = 'limit_max';
	$available_columns[] = 'limit_destination';
	$available_columns[] = 'missed_call_app';
	$available_columns[] = 'missed_call_data';
	$available_columns[] = 'user_context';
	$available_columns[] = 'toll_allow';
	$available_columns[] = 'call_timeout';
	$available_columns[] = 'call_group';
	$available_columns[] = 'call_screen_enabled';
	$available_columns[] = 'user_record';
	$available_columns[] = 'hold_music';
	$available_columns[] = 'auth_acl';
	$available_columns[] = 'cidr';
	$available_columns[] = 'sip_force_contact';
	$available_columns[] = 'nibble_account';
	$available_columns[] = 'sip_force_expires';
	$available_columns[] = 'mwi_account';
	$available_columns[] = 'sip_bypass_media';
	$available_columns[] = 'unique_id';
	$available_columns[] = 'dial_string';
	$available_columns[] = 'dial_user';
	$available_columns[] = 'dial_domain';
	$available_columns[] = 'do_not_disturb';
	$available_columns[] = 'forward_all_destination';
	$available_columns[] = 'forward_all_enabled';
	$available_columns[] = 'forward_busy_destination';
	$available_columns[] = 'forward_busy_enabled';
	$available_columns[] = 'forward_no_answer_destination';
	$available_columns[] = 'forward_no_answer_enabled';
	$available_columns[] = 'follow_me_uuid';
	$available_columns[] = 'enabled';
	$available_columns[] = 'description';
	$available_columns[] = 'absolute_codec_string';
	$available_columns[] = 'forward_user_not_registered_destination';
	$available_columns[] = 'forward_user_not_registered_enabled';

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

//get the extensions from the database and send them as output
	if (is_array($_REQUEST["column_group"]) && @sizeof($_REQUEST["column_group"]) != 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: extension_download.php');
				exit;
			}

		//validate submitted columns
		foreach ($_REQUEST["column_group"] as $column_name) {
			if (in_array($column_name, $available_columns)) {
				$selected_columns[] = $column_name;
			}
		}
		if (is_array($selected_columns) && @sizeof($selected_columns) != 0) {
			$sql = "select ".implode(', ', $selected_columns)." from v_extensions ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$database = new database;
			$extensions = $database->select($sql, $parameters, 'all');
			unset($sql, $parameters, $selected_columns);

			download_send_headers("extension_export_".date("Y-m-d").".csv");
			echo array2csv($extensions);
			exit;
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-extension_export'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-extension_export']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'extensions.php']);
	echo button::create(['type'=>'submit','label'=>$text['button-export'],'icon'=>$_SESSION['theme']['button_icon_export'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['description-extension_export'];
	echo "<br /><br />\n";
	
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	echo "	<th class='checkbox'>\n";
	echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($available_columns ?: "style='visibility: hidden;'").">\n";
	echo "	</th>\n";
	echo "	<th>".$text['label-column_name']."</th>\n";
	echo "</tr>\n";

	if (is_array($available_columns) && @sizeof($available_columns) != 0) {
		$x = 0;
		foreach ($available_columns as $column_name) {
			$list_row_onclick = "if (!this.checked) { document.getElementById('checkbox_all').checked = false; }";
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			echo "	<td class='checkbox'>\n";
			echo "		<input type='checkbox' name='column_group[]' id='checkbox_".$x."' value=\"".$column_name."\" onclick=\"".$list_row_onclick."\">\n";
			echo "	</td>\n";
			echo "	<td onclick=\"document.getElementById('checkbox_".$x."').checked = document.getElementById('checkbox_".$x."').checked ? false : true; ".$list_row_onclick."\">".$column_name."</td>";
			echo "</tr>";
			$x++;
		}
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
