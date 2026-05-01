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
	Portions created by the Initial Developer are Copyright (C) 2026
	the Initial Developer. All Rights Reserved.
*/

// includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

// check permissions
	if (!permission_exists('service_view')) {
		echo "access denied";
		exit;
	}

// add multi-lingual support
	$language = new text;
	$text = $language->get();

// add the settings object
	$settings = new settings(["domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);

// set from session variables
	$list_row_edit_button = $settings->get('theme', 'list_row_edit_button', 'false');

// get the http post data
	if (!empty($_POST['services']) && is_array($_POST['services'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$services = $_POST['services'];
	}

// process the http post data by action
	if (!empty($action) && !empty($services) && is_array($services) && @sizeof($services) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('service_edit')) {
					$obj = new services;
					$obj->toggle($services);
				}
				break;
			case 'delete':
				if (permission_exists('service_delete')) {
					$obj = new services;
					$obj->delete($services);
				}
				break;
		}

		// redirect the user
		header('Location: services.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

// get order and order by
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

// define the variables
	$search = '';
	$show = '';
	$list_row_url = '';

// add the search variable
	if (!empty($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

// add the show variable
	if (!empty($_GET["show"])) {
		$show = $_GET["show"];
	}

// get the status of the services
	$service_object = new services;
	$service_object->add_missing();

// get the status of the services
	$service_array = $service_object->get_services('true');

// get the count
	$sql = "select count(service_uuid) ";
	$sql .= "from v_services ";
	$sql .= "where true ";
	if (!empty($search)) {
		$sql .= "and ( ";
		$sql .= " lower(service_name) like :search ";
		$sql .= " or lower(service_category) like :search ";
		$sql .= " or lower(service_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($sql, $parameters);

// get the list
	$sql = "select ";
	$sql .= "service_uuid, ";
	$sql .= "service_name, ";
	$sql .= "service_category, ";
	$sql .= "cast(service_enabled as text), ";
	$sql .= "service_description ";
	$sql .= "from v_services ";
	$sql .= "where true ";
	if (!empty($search)) {
		$sql .= "and ( ";
		$sql .= " lower(service_name) like :search ";
		$sql .= " or lower(service_category) like :search ";
		$sql .= " or lower(service_description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'service_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$services = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

// add the service details to the services array
	foreach($services as $i => $service) {
		foreach ($service_array as $row) {
			if ($service['service_name'] == $row['name']) {
				$services[$i]['service_status'] = $row['status'] ? 'true' : 'false';
				$services[$i]['service_pid'] = $row['pid'];
				$services[$i]['service_etime'] = $row['etime'];
				break;
			}
		}
	}

// create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

// additional includes
	$document['title'] = $text['title-services'];
	require_once "resources/header.php";

// show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-services']."</b><div class='count'>".$num_rows."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('service_edit') && $services) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('service_delete') && $services) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('service_add') && $services) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('service_edit') && $services) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('service_delete') && $services) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['title_description-services']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search ?? '')."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('service_add') || permission_exists('service_edit') || permission_exists('service_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".empty($services ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	echo th_order_by('service_name', $text['label-service_name'], $order_by, $order);
	echo th_order_by('service_status', $text['label-service_status'], $order_by, $order);
	echo th_order_by('service_category', $text['label-service_category'], $order_by, $order);
	echo "	<th class='hide-sm-dn'>".$text['label-service_runtime']."</th>\n";
	echo th_order_by('service_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo "	<th class='hide-sm-dn'>".$text['label-service_description']."</th>\n";
	if (permission_exists('service_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($services) && is_array($services) && @sizeof($services) != 0) {
		$x = 0;
		foreach ($services as $row) {
			$service_status = ($row['service_status'] == 'true')
				? "<span style='background-color: #28a745; color: white; padding: 2px 8px; border-radius: 10px;'>".$text['label-yes']."</span>"
				: "<span style='background-color: #dc3545; color: white; padding: 2px 8px; border-radius: 10px;'>".$text['label-no']."</span>";
			$etime = isset($row['service_etime']) ? $service_object->format_etime($row['service_etime']) : '-';
			$pid = $row['service_pid'] ?? '';
			$tooltip_attr = $pid ? "title='PID: $pid'" : '';

			if (permission_exists('service_edit')) {
				$list_row_url = "service_edit.php?id=".urlencode($row['service_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('service_add') || permission_exists('service_edit') || permission_exists('service_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='services[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='services[$x][uuid]' value='".escape($row['service_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('service_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['service_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['service_name']);
			}
			echo "	</td>\n";
			echo "	<td $tooltip_attr>".$service_status."</td>\n";
			echo "	<td>".escape($row['service_category'])."</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($etime)."</td>\n";
			if (permission_exists('service_edit')) {
				echo "	<td class='no-link center'>\n";
				echo "		<input type='hidden' name='number_translations[$x][service_enabled]' value='".escape($row['service_enabled'])."' />\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['service_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['service_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['service_description'])."</td>\n";
			if (permission_exists('service_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($services);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

// include the footer
	require_once "resources/footer.php";

?>
