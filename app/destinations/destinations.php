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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('destination_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get posted data
	if (is_array($_POST['destinations'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$destinations = $_POST['destinations'];
	}

//process the http post data by action
	if ($action != '' && is_array($destinations) && @sizeof($destinations) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('destination_edit')) {
					$obj = new destinations;
					$obj->toggle($destinations);
				}
				break;
			case 'delete':
				if (permission_exists('destination_delete')) {
					$obj = new destinations;
					$obj->delete($destinations);
				}
				break;
		}

		header('Location: destinations.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get the destination select list
	$destination = new destinations;
	$destination_array = $destination->all('dialplan');

//add a function to return the action_name
	function action_name($destination_array, $detail_action) {
		if (is_array($destination_array)) {
			foreach($destination_array as $group => $row) {
				if (is_array($row)) {
					foreach ($row as $key => $value) {
						if ($value == $detail_action) {
							//add multi-lingual support
								if (file_exists($_SERVER["PROJECT_ROOT"]."/app/".$group."/app_languages.php")) {
									$language2 = new text;
									$text2 = $language2->get($_SESSION['domain']['language']['code'], 'app/'.$group);
								}
							//return the group and destination name
								return trim($text2['title-'.$group].' '.$key);
						}
					}
				}
			}
		}
	}

//set the type
	switch ($_REQUEST['type']) {
		case 'inbound': $destination_type = 'inbound'; break;
		case 'outbound': $destination_type = 'outbound'; break;
		case 'local': $destination_type = 'local'; break;
		default: $destination_type = 'inbound';
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);

//prepare to page the results
	$sql = "select count(*) from v_destinations ";
	$sql .= "where destination_type = :destination_type ";
	if ($_GET['show'] != "all" || !permission_exists('destination_all')) {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= "lower(destination_type) like :search ";
		$sql .= "or lower(destination_number) like :search ";
		$sql .= "or lower(destination_context) like :search ";
		$sql .= "or lower(destination_accountcode) like :search ";
		if (permission_exists('outbound_caller_id_select')) {
			$sql .= "or lower(destination_caller_id_name) like :search ";
			$sql .= "or destination_caller_id_number like :search ";
		}
		$sql .= "or lower(destination_enabled) like :search ";
		$sql .= "or lower(destination_description) like :search ";
		$sql .= "or lower(destination_data) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$parameters['destination_type'] = $destination_type;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".urlencode($search);
	$param .= "&type=".$destination_type;
	if ($_GET['show'] == "all" && permission_exists('destination_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_destinations ";
	$sql .= "where destination_type = :destination_type ";
	if ($_GET['show'] != "all" || !permission_exists('destination_all')) {
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (strlen($search) > 0) {
		$sql .= "and (";
		$sql .= "lower(destination_type) like :search ";
		$sql .= "or lower(destination_number) like :search ";
		$sql .= "or lower(destination_context) like :search ";
		$sql .= "or lower(destination_accountcode) like :search ";
		if (permission_exists('outbound_caller_id_select')) {
			$sql .= "or lower(destination_caller_id_name) like :search ";
			$sql .= "or destination_caller_id_number like :search ";
		}
		$sql .= "or lower(destination_enabled) like :search ";
		$sql .= "or lower(destination_description) like :search ";
		$sql .= "or lower(destination_data) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'destination_number, destination_order ', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$destinations = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-destinations'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-destinations']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-inbound'],'icon'=>'location-arrow fa-rotate-90','link'=>'?type=inbound'.($_GET['show'] == 'all' ? '&show=all' : null).($search != '' ? "&search=".urlencode($search) : null)]);
	echo button::create(['type'=>'button','label'=>$text['button-outbound'],'icon'=>'location-arrow','link'=>'?type=outbound'.($_GET['show'] == 'all' ? '&show=all' : null).($search != '' ? "&search=".urlencode($search) : null)]);
	echo button::create(['type'=>'button','label'=>$text['button-local'],'icon'=>'vector-square','link'=>'?type=local'.($_GET['show'] == 'all' ? '&show=all' : null).($search != '' ? "&search=".urlencode($search) : null)]);
	if (permission_exists('destination_import')) {
		echo button::create(['type'=>'button','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'link'=>'destination_imports.php']);
	}
	if (permission_exists('destination_export')) {
		echo button::create(['type'=>'button','label'=>$text['button-export'],'icon'=>$_SESSION['theme']['button_icon_export'],'link'=>'destination_download.php']);
	}
	if (permission_exists('destination_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','style'=>'margin-left: 15px;','link'=>'destination_edit.php']);
	}
	if (permission_exists('destination_delete') && $destinations) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('destination_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?type='.urlencode($destination_type).'&show=all'.($search != '' ? "&search=".urlencode($search) : null)]);
		}
	}
	echo "		<input type='hidden' name='type' value=\"".escape($destination_type)."\">\n";
	echo "		<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'destinations.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('destination_delete') && $destinations) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-destinations']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='POST'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='type' value=\"".escape($destination_type)."\">\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('destination_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($destinations ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == "all" && permission_exists('destination_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param, "class='shrink'");
	}
	echo th_order_by('destination_type', $text['label-destination_type'], $order_by, $order, $param, "class='shrink'");
	echo th_order_by('destination_prefix', $text['label-destination_prefix'], $order_by, $order, $param, "class='shrink'");
	if (permission_exists('destination_trunk_prefix')) {
		echo th_order_by('destination_trunk_prefix', '', $order_by, $order, $param, "class='shrink'");
	}
	if (permission_exists('destination_area_code')) {
		echo th_order_by('destination_area_code', '', $order_by, $order, $param, "class='shrink'");
	}
	echo th_order_by('destination_number', $text['label-destination_number'], $order_by, $order, $param, "class='shrink'");
	if (!$_GET['show'] == "all") {
		echo  "<th>". $text['label-detail_action']."</th>";
	}
	if (permission_exists("destination_context")) {
		echo th_order_by('destination_context', $text['label-destination_context'], $order_by, $order, $param);
	}
	if (permission_exists('outbound_caller_id_select')) {
		echo th_order_by('destination_caller_id_name', $text['label-destination_caller_id_name'], $order_by, $order, $param);
		echo th_order_by('destination_caller_id_number', $text['label-destination_caller_id_number'], $order_by, $order, $param);
	}
	echo th_order_by('destination_enabled', $text['label-destination_enabled'], $order_by, $order, $param);
	echo th_order_by('destination_description', $text['label-destination_description'], $order_by, $order, $param, "class='hide-sm-dn'");
	if (permission_exists('destination_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($destinations) && @sizeof($destinations) != 0) {
		$x = 0;
		foreach($destinations as $row) {
			if (permission_exists('destination_edit')) {
				$list_row_url = "destination_edit.php?id=".urlencode($row['destination_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('destination_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='destinations[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='destinations[$x][uuid]' value='".escape($row['destination_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == "all" && permission_exists('destination_all')) {
				if (strlen($_SESSION['domains'][$row['domain_uuid']]['domain_name']) > 0) {
					$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td>".escape($domain)."</td>\n";
			}
			echo "	<td>".escape($row['destination_type'])."&nbsp;</td>\n";
			
			echo "	<td>".escape($row['destination_prefix'])."&nbsp;</td>\n";
			if (permission_exists('destination_trunk_prefix')) {
				echo "	<td>".escape($row['destination_trunk_prefix'])."&nbsp;</td>\n";
			}
			if (permission_exists('destination_area_code')) {
				echo "	<td>".escape($row['destination_area_code'])."&nbsp;</td>\n";
			}

			echo "	<td class='no-wrap'>\n";
			if (permission_exists('destination_edit')) {
				echo "		<a href='".$list_row_url."'>".escape(format_phone($row['destination_number']))."</a>\n";
			}
			else {
				echo "		".escape(format_phone($row['destination_number']));
			}
			echo "	</td>\n";

			if (!$_GET['show'] == "all") {
				echo "	<td class='overflow' style='min-width: 125px;'>".action_name($destination_array, $row['destination_app'].':'.$row['destination_data'])."&nbsp;</td>\n";
			}
			if (permission_exists("destination_context")) {
				echo "	<td>".escape($row['destination_context'])."&nbsp;</td>\n";
			}
			if (permission_exists('outbound_caller_id_select')) {
				echo "	<td>".escape($row['destination_caller_id_name'])."&nbsp;</td>\n";
				echo "	<td>".escape($row['destination_caller_id_number'])."&nbsp;</td>\n";
			}
			echo "	<td>".escape($text['label-'.$row['destination_enabled']])."&nbsp;</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['destination_description'])."&nbsp;</td>\n";
			if (permission_exists('destination_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($destinations);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
