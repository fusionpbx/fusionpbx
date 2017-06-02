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
	KonradSC <konrd@yahoo.com>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//include the class
	require_once "resources/check_auth.php";

//check permissions
	require_once "resources/check_auth.php";
	if (permission_exists('bulk_account_settings_extensions')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}
	
//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http values and set them as variables
	$order_by = check_str($_GET["order_by"]);
	$order = check_str($_GET["order"]);
	$option_selected = check_str($_GET["option_selected"]);
	
//handle search term
	$search = check_str($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_mod = "and ( ";
		$sql_mod .= "extension ILIKE '%".$search."%' ";
		$sql_mod .= "or accountcode ILIKE '%".$search."%' ";		
		$sql_mod .= "or call_group ILIKE '%".$search."%' ";
		$sql_mod .= "or description ILIKE '%".$search."%' ";
		if (($option_selected == "") or ($option_selected == 'call_group') or ($option_selected == 'accountcode')) {} else {
			$sql_mod .= "or ".$option_selected." ILIKE '%".$search."%' ";
		}
		$sql_mod .= ") ";
	}
	if (strlen($order_by) < 1) {
		$order_by = "extension";
		$order = "ASC";
	}

	$domain_uuid = $_SESSION['domain_uuid'];
	

//get total extension count from the database
	$sql = "select count(*) as num_rows from v_extensions where domain_uuid = '".$_SESSION['domain_uuid']."' ".$sql_mod." ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$total_extensions = $row['num_rows'];
		if (($db_type == "pgsql") or ($db_type == "mysql")) {
			$numeric_extensions = $row['num_rows'];
		}
	}
	unset($prep_statement, $row);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if (!isset($_GET['page'])) { $_GET['page'] = 0; }
	$_GET['page'] = check_str($_GET['page']);
	list($paging_controls_mini, $rows_per_page, $var_3) = paging($total_extensions, $param, $rows_per_page, true); //top
	list($paging_controls, $rows_per_page, $var_3) = paging($total_extensions, $param, $rows_per_page); //bottom
	$offset = $rows_per_page * $_GET['page'];

//get all the extensions from the database
	$sql = "SELECT \n";
	$sql .= "description, \n";
	$sql .= "extension, \n";
	$sql .= "extension_uuid, \n";
	if (($option_selected == "") or ($option_selected == 'call_group') or ($option_selected == 'accountcode')) {} else {
		$sql .= "".$option_selected.", \n";
	}
	$sql .= "accountcode, \n";
	$sql .= "call_group \n";
	$sql .= "FROM v_extensions \n";
	$sql .= "WHERE domain_uuid = '$domain_uuid' \n";
	$sql .= $sql_mod; //add search mod from above
	$sql .= "ORDER BY ".$order_by." ".$order." \n";
	$sql .= "limit $rows_per_page offset $offset ";
	$database = new database;
	$database->select($sql);
	$directory = $database->result;
	unset($database,$result);


//additional includes
	require_once "resources/header.php";
	$document['title'] = $text['title-extensions_settings'];

//set the alternating styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";
	
//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'>\n";
	echo "		<b>".$text['header-extensions']." (".$numeric_extensions.")</b><br>\n";

//options list
		echo "<form name='frm' method='get' id=option_selected>\n";
		echo "    <select class='formfld' name='option_selected'  onchange=\"this.form.submit();\">\n";
		echo "    <option value=''>".$text['label-extension_null']."</option>\n";
		if ($option_selected == "accountcode") {
			echo "    <option value='accountcode' selected='selected'>".$text['label-accountcode']."</option>\n";
		}
		else {
			echo "    <option value='accountcode'>".$text['label-accountcode']."</option>\n";
		}
		if ($option_selected == "call_group") {
			echo "    <option value='call_group' selected='selected'>".$text['label-call_group']."</option>\n";
		}
		else {
			echo "    <option value='call_group'>".$text['label-call_group']."</option>\n";
		}
		if ($option_selected == "call_timeout") {
			echo "    <option value='call_timeout' selected='selected'>".$text['label-call_timeout']."</option>\n";
		}
		else {
			echo "    <option value='call_timeout'>".$text['label-call_timeout']."</option>\n";
		}
		if ($option_selected == "emergency_caller_id_name") {
			echo "    <option value='emergency_caller_id_name' selected='selected'>".$text['label-emergency_caller_id_name']."</option>\n";
		}
		else {
			echo "    <option value='emergency_caller_id_name'>".$text['label-emergency_caller_id_name']."</option>\n";
		}
		if ($option_selected == "emergency_caller_id_number") {
			echo "    <option value='emergency_caller_id_number' selected='selected'>".$text['label-emergency_caller_id_number']."</option>\n";
		}
		else {
			echo "    <option value='emergency_caller_id_number'>".$text['label-emergency_caller_id_number']."</option>\n";
		}
		if ($option_selected == "enabled") {
			echo "    <option value='enabled' selected='selected'>".$text['label-enabled']."</option>\n";
		}
		else {
			echo "    <option value='enabled'>".$text['label-enabled']."</option>\n";
		}
		if ($option_selected == "hold_music") {
			echo "    <option value='hold_music' selected='selected'>".$text['label-hold_music']."</option>\n";
		}
		else {
			echo "    <option value='hold_music'>".$text['label-hold_music']."</option>\n";
		}
		if ($option_selected == "limit_max") {
			echo "    <option value='limit_max' selected='selected'>".$text['label-limit_max']."</option>\n";
		}
		else {
			echo "    <option value='limit_max'>".$text['label-limit_max']."</option>\n";
		}
		if ($option_selected == "outbound_caller_id_name") {
			echo "    <option value='outbound_caller_id_name' selected='selected'>".$text['label-outbound_caller_id_name']."</option>\n";
		}
		else {
			echo "    <option value='outbound_caller_id_name'>".$text['label-outbound_caller_id_name']."</option>\n";
		}
		if ($option_selected == "outbound_caller_id_number") {
			echo "    <option value='outbound_caller_id_number' selected='selected'>".$text['label-outbound_caller_id_number']."</option>\n";
		}
		else {
			echo "    <option value='outbound_caller_id_number'>".$text['label-outbound_caller_id_number']."</option>\n";
		}
		if ($option_selected == "toll_allow") {
			echo "    <option value='toll_allow' selected='selected'>".$text['label-toll_allow']."</option>\n";
		}
		else {
			echo "    <option value='toll_allow'>".$text['label-toll_allow']."</option>\n";
		}

		echo "    </select>\n";
		echo "    </form>\n";
		echo "<br />\n";
		echo $text['description-extension_settings_description']."\n";
		echo "</td>\n";
	
	
	
	echo "		<td align='right' width='100%' style='vertical-align: top;'>";
	echo "		<form method='get' action=''>\n";
	echo "			<td style='vertical-align: top; text-align: right; white-space: nowrap;'>\n";
	echo "				<input type='button' class='btn' alt='".$text['button-back']."' onclick=\"window.location='bulk_account_settings.php'\" value='".$text['button-back']."'>\n";	
	echo "				<input type='text' class='txt' style='width: 150px' name='search' id='search' value='".$search."'>";
	echo "				<input type='hidden' class='txt' style='width: 150px' name='option_selected' id='option_selected' value='".$option_selected."'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	if ($paging_controls_mini != '') {
		echo 			"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "			</td>\n";
	echo "		</form>\n";	
	echo "  </tr>\n";
	
	
	echo "	<tr>\n";
	echo "		<td colspan='2'>\n";
	echo "			".$text['description-extensions_settings']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

	if (strlen($option_selected) > 0) {
		echo "<form name='extensions' method='post' action='bulk_account_settings_extensions_update.php'>\n";
		echo "<input class='formfld' type='hidden' name='option_selected' maxlength='255' value=\"$option_selected\">\n";
		echo "<table width='auto' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		//options with a free form input
		if($option_selected == 'accountcode' || $option_selected == 'call_group' || $option_selected == 'call_timeout' || $option_selected == 'emergency_caller_id_name' || $option_selected == 'emergency_caller_id_number' || $option_selected == 'limit_max' || $option_selected == 'outbound_caller_id_name' || $option_selected == 'outbound_caller_id_number' || $option_selected == 'toll_allow') {
			echo "<td class='vtable' align='left'>\n";
			echo "    <input class='formfld' type='text' name='new_setting' maxlength='255' value=\"$new_setting\">\n";
			echo "<br />\n";
			echo $text["description-".$option_selected.""]."\n";
			echo "</td>\n";
		}
		//option is Enabled
		if($option_selected == 'enabled') {
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='new_setting'>\n";
			echo "    <option value='true'>".$text['label-true']."</option>\n";
			echo "    <option value='false'>".$text['label-false']."</option>\n";
			echo "    </select>\n";
			echo "    <br />\n";
			echo $text["description-".$option_selected.""]."\n";
			echo "</td>\n";
		}
		//option is hold_music
		if($option_selected == 'hold_music') {
			echo "<td class='vtable' align='left'>\n";
			if (is_dir($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/app/music_on_hold')) {
				require_once "app/music_on_hold/resources/classes/switch_music_on_hold.php";
				$options = '';
				$moh = new switch_music_on_hold;
				echo $moh->select('new_setting', $hold_music, $options);
			}
			$new_setting = $hold_music;
			echo "    <br />\n";
			echo $text["description-".$option_selected.""]."\n";
			echo "</td>\n";
		}
		echo "<td align='left'>\n";
		echo "<input type='button' class='btn' alt='".$text['button-submit']."' onclick=\"if (confirm('".$text['confirm-update']."')) { document.forms.extensions.submit(); }\" value='".$text['button-submit']."'>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>";
		echo "<br />";
	}
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	if (is_array($directory)) {
		echo "<th style='width: 30px; text-align: center; padding: 0px;'><input type='checkbox' id='chk_all' onchange=\"(this.checked) ? check('all') : check('none');\"></th>";
	}
	echo th_order_by('extension', $text['label-extension'], $order_by,$order,'','',"option_selected=".$option_selected."&search=".$search."");
	if (($option_selected == "") or ($option_selected == 'call_group') or ($option_selected == 'accountcode')) {
		} else {
			echo th_order_by($option_selected, $text["label-".$option_selected.""], $order_by,$order,'','',"option_selected=".$option_selected."&search=".$search."");
		}
	echo th_order_by('accountcode', $text['label-accountcode'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");	
	echo th_order_by('call_group', $text['label-call_group'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo th_order_by('description', $text['label-description'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo "</tr>\n";



if (is_array($directory)) {

		foreach($directory as $key => $row) {
			$tr_link = (permission_exists('extension_edit')) ? " href='/app/extensions/extension_edit.php?id=".$row['extension_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";

			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; vertical-align: middle; padding: 0px;'>";
			echo "		<input type='checkbox' name='id[]' id='checkbox_".$row['extension_uuid']."' value='".$row['extension_uuid']."' onclick=\"if (!this.checked) { document.getElementById('chk_all').checked = false; }\">";
			echo "	</td>";
			$ext_ids[] = 'checkbox_'.$row['extension_uuid'];

			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['extension']."&nbsp;</td>\n";
			if (($option_selected == "") or ($option_selected == 'call_group') or ($option_selected == 'accountcode')) {
			} else {
				echo "	<td valign='top' class='".$row_style[$c]."'> ".$row[$option_selected]."&nbsp;</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['accountcode']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['call_group']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['description']."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}

		unset($directory, $row);
	}

	echo "</table>";
	echo "</form>";

	if (strlen($paging_controls) > 0) {
		echo "<br />";
		echo $paging_controls."\n";
	}
	echo "<br /><br />".((is_array($directory)) ? "<br /><br />" : null);

	// check or uncheck all checkboxes
	if (sizeof($ext_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what) {\n";
		echo "		document.getElementById('chk_all').checked = (what == 'all') ? true : false;\n";
		foreach ($ext_ids as $ext_id) {
			echo "		document.getElementById('".$ext_id."').checked = (what == 'all') ? true : false;\n";
		}
		echo "	}\n";
		echo "</script>\n";
	}

	if (is_array($directory)) {
		// check all checkboxes
		key_press('ctrl+a', 'down', 'document', null, null, "check('all');", true);

		// delete checked
		key_press('delete', 'up', 'document', array('#search'), $text['confirm-delete'], 'document.forms.frm.submit();', true);
	}

//show the footer
	require_once "resources/footer.php";
?>