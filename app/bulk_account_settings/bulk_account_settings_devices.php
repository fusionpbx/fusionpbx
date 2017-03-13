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
	if (permission_exists('bulk_account_settings_devices')) {
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
	
//	if (count($_POST)>0) {
//		$option_selected = check_str($_POST["option_selected"]);
//	}
	
//handle search term
	$search = check_str($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_mod = "and ( ";
		$sql_mod .= "device_mac_address ILIKE '%".$search."%' ";
		$sql_mod .= "or device_label ILIKE '%".$search."%' ";		
		$sql_mod .= "or device_vendor ILIKE '%".$search."%' ";
		$sql_mod .= "or device_model ILIKE '%".$search."%' ";
		$sql_mod .= "or device_description ILIKE '%".$search."%' ";
		$sql_mod .= "or device_template ILIKE '%".$search."%' ";		
		$sql_mod .= ") ";
	}
	if (strlen($order_by) < 1) {
		$order_by = "device_label";
		$order = "ASC";
	}

	$domain_uuid = $_SESSION['domain_uuid'];
	

//get total device count from the database
	$sql = "select count(*) as num_rows from v_devices where domain_uuid = '".$_SESSION['domain_uuid']."' ".$sql_mod." ";
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$total_devices = $row['num_rows'];
		if (($db_type == "pgsql") or ($db_type == "mysql")) {
			$numeric_devices = $row['num_rows'];
		}
	}
	unset($prep_statement, $row);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if (!isset($_GET['page'])) { $_GET['page'] = 0; }
	$_GET['page'] = check_str($_GET['page']);
	list($paging_controls_mini, $rows_per_page, $var_3) = paging($total_devices, $param, $rows_per_page, true); //top
	list($paging_controls, $rows_per_page, $var_3) = paging($total_devices, $param, $rows_per_page); //bottom
	$offset = $rows_per_page * $_GET['page'];

//get all the devices from the database
	$sql = "SELECT \n";
	$sql .= "d.device_uuid, \n";
	$sql .= "d.device_label, \n";
	$sql .= "d.device_mac_address, \n";
	$sql .= "d.device_vendor, \n";
	$sql .= "d.device_template, \n";
	$sql .= "d.device_enabled, \n";
	$sql .= "d.device_description, \n";
	$sql .= "(\n";
	$sql .= "select dp.device_profile_name from v_device_profiles as dp \n";
	$sql .= "where d.device_profile_uuid = dp.device_profile_uuid \n";
	$sql .= ") as device_profile_name \n";
	$sql .= "FROM v_devices as d \n";
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
	$document['title'] = $text['title-devices_settings'];

//set the alternating styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";
	
//show the content
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
	echo "  <tr>\n";
	echo "	<td align='left' width='100%'>\n";
	echo "		<b>".$text['header-devices']." (".$numeric_devices.")</b><br>\n";

//options list
		echo "<form name='frm' method='get' id=option_selected>\n";
		echo "    <select class='formfld' name='option_selected'  onchange=\"this.form.submit();\">\n";
		echo "    <option value=''>".$text['label-devices_null']."</option>\n";
		if ($option_selected == "device_enabled") {
			echo "    <option value='device_enabled' selected='selected'>".$text['label-enabled']."</option>\n";
		}
		else {
			echo "    <option value='device_enabled'>".$text['label-enabled']."</option>\n";
		}
		if ($option_selected == "device_profile_uuid") {
			echo "    <option value='device_profile_uuid' selected='selected'>".$text['label-device_profile']."</option>\n";
		}
		else {
			echo "    <option value='device_profile_uuid'>".$text['label-device_profile']."</option>\n";
		}
		if ($option_selected == "device_template") {
			echo "    <option value='device_template' selected='selected'>".$text['label-device_template']."</option>\n";
		}
		else {
			echo "    <option value='device_template'>".$text['label-device_template']."</option>\n";
		}

		echo "    </select>\n";
		echo "    </form>\n";
		echo "<br />\n";
		echo $text['description-device_settings_description']."\n";
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
	echo "			".$text['description-devices_settings']."\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<br />";

	if (strlen($option_selected) > 0) {
		echo "<form name='devices' method='post' action='bulk_account_settings_devices_update.php'>\n";
		echo "<input class='formfld' type='hidden' name='option_selected' maxlength='255' value=\"$option_selected\">\n";
		echo "<table width='auto' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		//option is Enabled
		if($option_selected == 'device_enabled') {
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='new_setting'>\n";
			echo "    <option value='true'>".$text['label-true']."</option>\n";
			echo "    <option value='false'>".$text['label-false']."</option>\n";
			echo "    </select>\n";
			echo "    <br />\n";
			echo $text["description-".$option_selected.""]."\n";
			echo "</td>\n";
		}

		//option is Device Profile
		if($option_selected == 'device_profile_uuid' && permission_exists('device_profile_edit')) {
			$sql = "select * from v_device_profiles ";
			$sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
			$sql .= "order by device_profile_name asc ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			$result_count = count($result);
			unset ($prep_statement, $sql);
			if ($result_count > 0) {
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='new_setting'>\n";
			echo "				<option value=''></option>\n";
			foreach($result as $row) {
				echo "			<option value='".$row['device_profile_uuid']."' ".(($row['device_profile_uuid'] == $device_profile_uuid) ? "selected='selected'" : null).">".$row['device_profile_name']." ".(($row['domain_uuid'] == '') ? "&nbsp;&nbsp;(".$text['select-global'].")" : null)."</option>\n";
			}
			//echo "			</select>\n";
			echo "    </select>\n";
			echo "    <br />\n";
			echo $text["description-".$option_selected.""]."\n";
			echo "</td>\n";
			}
		}

		//option is Device Templates
		if($option_selected == 'device_template' && permission_exists('device_template')) {
			$device = new device;
			$template_dir = $device->get_template_dir();
			
			echo "<td class='vtable' align='left'>\n";
			echo "    <select class='formfld' name='new_setting'>\n";
			echo "<option value=''></option>\n";
			if (is_dir($template_dir)) {
					$templates = scandir($template_dir);
					foreach($templates as $dir) {
						if($file != "." && $dir != ".." && $dir[0] != '.') {
							if(is_dir($template_dir . "/" . $dir)) {
								echo "<optgroup label='$dir'>";
								$dh_sub=$template_dir . "/" . $dir;
								if(is_dir($dh_sub)) {
									$templates_sub = scandir($dh_sub);
									foreach($templates_sub as $dir_sub) {
										if($file_sub != '.' && $dir_sub != '..' && $dir_sub[0] != '.') {
											if(is_dir($template_dir . '/' . $dir .'/'. $dir_sub)) {
												if ($device_template == $dir."/".$dir_sub) {
													echo "<option value='".$dir."/".$dir_sub."' selected='selected'>".$dir."/".$dir_sub."</option>\n";
												}
												else {
													echo "<option value='".$dir."/".$dir_sub."'>".$dir."/".$dir_sub."</option>\n";
												}
											}
										}
									}
								}
								echo "</optgroup>";
							}
						}
					}
				}
			echo "</select>\n";
			echo "    <br />\n";
			echo $text["description-".$option_selected.""]."\n";
			echo "</td>\n";
		}

		echo "<td align='left'>\n";
		echo "<input type='button' class='btn' alt='".$text['button-submit']."' onclick=\"if (confirm('".$text['confirm-update']."')) { document.forms.devices.submit(); }\" value='".$text['button-submit']."'>\n";
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
	echo th_order_by('device_mac_address', $text['label-device_mac_address'], $order_by,$order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo th_order_by('device_label', $text['label-device_label'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");	
	echo th_order_by('device_vendor', $text['label-device_vendor'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo th_order_by('device_template', $text['label-device_template'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo th_order_by('device_label', $text['label-device_profile'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo th_order_by('device_enabled', $text['label-device_enabled'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo th_order_by('device_description', $text['label-device_description'], $order_by, $order,'','',"option_selected=".$option_selected."&search=".$search."");
	echo "</tr>\n";



if (is_array($directory)) {

		foreach($directory as $key => $row) {
			$tr_link = (permission_exists('device_edit')) ? " href='/app/devices/device_edit.php?id=".$row['device_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";

			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void' style='text-align: center; vertical-align: middle; padding: 0px;'>";
			echo "		<input type='checkbox' name='id[]' id='checkbox_".$row['device_uuid']."' value='".$row['device_uuid']."' onclick=\"if (!this.checked) { document.getElementById('chk_all').checked = false; }\">";
			echo "	</td>";
			$device_ids[] = 'checkbox_'.$row['device_uuid'];
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['device_mac_address']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['device_label']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['device_vendor']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['device_template']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['device_profile_name']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['device_enabled']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'> ".$row['device_description']."&nbsp;</td>\n";
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
	if (sizeof($device_ids) > 0) {
		echo "<script>\n";
		echo "	function check(what) {\n";
		echo "		document.getElementById('chk_all').checked = (what == 'all') ? true : false;\n";
		foreach ($device_ids as $device_id) {
			echo "		document.getElementById('".$device_id."').checked = (what == 'all') ? true : false;\n";
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