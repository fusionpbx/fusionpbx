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

//pre-defined variables
	$action = '';
	$search = '';
	$show = '';
	$destinations = '';

//get http variables
	if (isset($_REQUEST["action"]) && !empty($_REQUEST["action"])) {
		$action =  $_REQUEST["action"];
	}
	if (isset($_REQUEST["search"]) && !empty($_REQUEST["search"])) {
		$search =  strtolower($_REQUEST["search"]);
	}
	if (isset($_REQUEST["show"]) && !empty($_REQUEST["show"])) {
		$show =  strtolower($_REQUEST["show"]);
	}
	if (isset($_REQUEST["destinations"]) && !empty($_REQUEST["destinations"])) {
		$destinations =  $_REQUEST["destinations"];
	}

//process the http post data by action
	if (!empty($action) && !empty($destinations)) {
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

//function to return the action names in the order defined
	function action_name($destination_array, $destination_actions) {
		$actions = [];
		if (!empty($destination_array) && is_array($destination_array)) {
			if (!empty($destination_actions) && is_array($destination_actions)) {
				foreach ($destination_actions as $destination_action) {
					if (!empty($destination_action)) {
						foreach ($destination_array as $group => $row) {
							if (!empty($row) && is_array($row)) {
								foreach ($row as $key => $value) {
									if ($destination_action == $value) {
										if ($group == 'other') {
											if (!isset($language2) && !isset($text2)) {
												if (file_exists($_SERVER["PROJECT_ROOT"]."/app/dialplans/app_languages.php")) {
													$language2 = new text;
													$text2 = $language2->get($_SESSION['domain']['language']['code'], 'app/dialplans');
												}
											}
											$actions[] = trim($text2['title-other'].' &#x203A; '.$text2['option-'.str_replace('&lowbar;','_',$key)]);
										}
										else {
											if (file_exists($_SERVER["PROJECT_ROOT"]."/app/".$group."/app_languages.php")) {
												$language3 = new text;
												$text3 = $language3->get($_SESSION['domain']['language']['code'], 'app/'.$group);
												$actions[] = trim($text3['title-'.$group].' &#x203A; '.$key);
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $actions;
	}

//set the type
	$destination_type = 'inbound';
	if (!empty($_REQUEST['type'])) {
		switch ($_REQUEST['type']) {
			case 'inbound': $destination_type = 'inbound'; break;
			case 'outbound': $destination_type = 'outbound'; break;
			case 'local': $destination_type = 'local'; break;
			default: $destination_type = 'inbound';
		}
	}

//get variables used to control the order
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//set from session variables
	$list_row_edit_button = !empty($_SESSION['theme']['list_row_edit_button']['boolean']) ? $_SESSION['theme']['list_row_edit_button']['boolean'] : 'false';

//prepare to page the results
	$sql = "select count(*) from v_destinations ";
	if ($show == "all" && permission_exists('destination_all')) {
		$sql .= "where destination_type = :destination_type ";
	}
	else {
		$sql .= "where destination_type = :destination_type ";
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!empty($search)) {
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
	if ($show == "all" && permission_exists('destination_all')) {
		$param .= "&show=all";
	}
	if (!empty($_GET['page'])) {
		$page = $_GET['page'];
	}
	if (!isset($page)) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select ";
	$sql .= " d.destination_uuid, ";
	$sql .= " d.domain_uuid, ";
	if ($show == "all" && permission_exists('destination_all')) {
		$sql .= " domain_name, ";
	}
	$sql .= " d.destination_type, ";
	$sql .= " d.destination_prefix, ";
	$sql .= " d.destination_trunk_prefix, ";
	$sql .= " d.destination_area_code, ";
	$sql .= " d.destination_number, ";
	$sql .= " d.destination_actions, ";
	$sql .= " d.destination_context, ";
	$sql .= " d.destination_caller_id_name, ";
	$sql .= " d.destination_caller_id_number, ";
	$sql .= " d.destination_enabled, ";
	$sql .= " d.destination_description ";
	$sql .= "from v_destinations as d ";
	if ($show == "all" && permission_exists('destination_all')) {
		$sql .= "LEFT JOIN v_domains as dom ";
		$sql .= "ON d.domain_uuid = dom.domain_uuid ";
		$sql .= "where destination_type = :destination_type ";
	}
	else {
		$sql .= "where destination_type = :destination_type ";
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= " lower(destination_type) like :search ";
		$sql .= " or lower(destination_number) like :search ";
		$sql .= " or lower(destination_context) like :search ";
		$sql .= " or lower(destination_accountcode) like :search ";
		if (permission_exists('outbound_caller_id_select')) {
			$sql .= " or lower(destination_caller_id_name) like :search ";
			$sql .= " or destination_caller_id_number like :search ";
		}
		$sql .= " or lower(destination_enabled) like :search ";
		$sql .= " or lower(destination_description) like :search ";
		$sql .= " or lower(destination_data) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'destination_number, destination_order ', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$destinations = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//update the array to add the actions
	if (!$show == "all") {
		$x = 0;
		foreach ($destinations as $row) {
			if (!empty($row['destination_actions'])) {
				//prepare the destination actions
				if (!empty(json_decode($row['destination_actions'], true))) {
					foreach (json_decode($row['destination_actions'], true) as $action) {
						$destination_app_data[] = $action['destination_app'].':'.$action['destination_data'];
					}
				}

				//add the actions to the array
				$actions = action_name($destination_array, $destination_app_data);
				$destinations[$x]['actions'] = (!empty($actions)) ? implode(', ', $actions) : '';

				//empty the array before the next iteration
				unset($destination_app_data);
			}
			else {
				$destinations[$x]['actions'] = '';
			}
			$x++;
		}
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-destinations'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-destinations']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-inbound'],'icon'=>'location-arrow fa-rotate-90','link'=>'?type=inbound'.($show == 'all' ? '&show=all' : null).($search != '' ? "&search=".urlencode($search) : null)]);
	echo button::create(['type'=>'button','label'=>$text['button-outbound'],'icon'=>'location-arrow','link'=>'?type=outbound'.($show == 'all' ? '&show=all' : null).($search != '' ? "&search=".urlencode($search) : null)]);
	if (permission_exists('destination_local')) {
		echo button::create(['type'=>'button','label'=>$text['button-local'],'icon'=>'vector-square','link'=>'?type=local'.($show == 'all' ? '&show=all' : null).($search != '' ? "&search=".urlencode($search) : null)]);
	}
	if (permission_exists('destination_import')) {
		echo button::create(['type'=>'button','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'link'=>'destination_imports.php']);
	}
	if (permission_exists('destination_export')) {
		echo button::create(['type'=>'button','label'=>$text['button-export'],'icon'=>$_SESSION['theme']['button_icon_export'],'link'=>'destination_download.php']);
	}
	if (permission_exists('destination_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','style'=>'margin-left: 15px;','link'=>'destination_edit.php?type='.urlencode($destination_type)]);
	}
	if (permission_exists('destination_delete') && $destinations) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('destination_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?type='.urlencode($destination_type).'&show=all'.($search != '' ? "&search=".urlencode($search) : null)]);
		}
	}
	echo "		<input type='hidden' name='type' value=\"".escape($destination_type)."\">\n";
	echo "		<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'destinations.php','style'=>($search == '' ? 'display: none;' : null)]);
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

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('destination_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($destinations) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == "all" && permission_exists('destination_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param, "class='shrink'");
	}
	echo th_order_by('destination_type', $text['label-destination_type'], $order_by, $order, $param, "class='shrink'");
	echo th_order_by('destination_prefix', $text['label-destination_prefix'], $order_by, $order, $param, "class='shrink center'");
	if (permission_exists('destination_trunk_prefix')) {
		echo th_order_by('destination_trunk_prefix', $text['label-destination_trunk_prefix'], $order_by, $order, $param, "class='shrink'");
	}
	if (permission_exists('destination_area_code')) {
		echo th_order_by('destination_area_code', $text['label-destination_area_code'], $order_by, $order, $param, "class='shrink'");
	}
	echo th_order_by('destination_number', $text['label-destination_number'], $order_by, $order, $param, "class='shrink'");
	if (!$show == "all") {
		echo  "<th>". $text['label-destination_actions']."</th>";
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
	if (permission_exists('destination_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($destinations)) {
		$x = 0;
		foreach ($destinations as $row) {

			//create the row link
			if (permission_exists('destination_edit')) {
				$list_row_url = "destination_edit.php?id=".urlencode($row['destination_uuid']);
			}

			//show the data
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('destination_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='destinations[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='destinations[$x][uuid]' value='".escape($row['destination_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == "all" && permission_exists('destination_all')) {
				if (!empty($row['domain_name'])) {
					$domain = $row['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td>".escape($domain)."</td>\n";
			}
			echo "	<td>".escape($text['option-'.$row['destination_type']])."&nbsp;</td>\n";

			echo "	<td class='center'>".escape($row['destination_prefix'])."&nbsp;</td>\n";
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

			if (!$show == "all") {
				echo "	<td class='overflow' style='min-width: 125px;'>".$row['actions']."&nbsp;</td>\n";
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
			if (permission_exists('destination_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$list_row_edit_button,'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";

			//unset the destination app and data array
			unset($destination_app_data);

			//increment the id
			$x++;
		}
		unset($destinations);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
