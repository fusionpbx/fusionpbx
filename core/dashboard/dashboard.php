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
	Portions created by the Initial Developer are Copyright (C) 2021-2024
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('dashboard_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (!empty($_POST['dashboard'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$dashboard = $_POST['dashboard'];
	}

//process the http post data by action
	if (!empty($action) && is_array($dashboard) && @sizeof($dashboard) != 0) {

		switch ($action) {
			case 'copy':
				if (permission_exists('dashboard_add')) {
					$obj = new dashboard;
					$obj->copy($dashboard);
				}
				break;
			case 'toggle':
				if (permission_exists('dashboard_edit')) {
					$obj = new dashboard;
					$obj->toggle($dashboard);
				}
				break;
			case 'delete':
				if (permission_exists('dashboard_delete')) {
					$obj = new dashboard;
					$obj->delete($dashboard);
				}
				break;
		}

		//redirect the user
		header('Location: dashboard.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

//add the search
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get the count
	$sql = "select count(dashboard_uuid) ";
	$sql .= "from v_dashboard ";
	if (isset($search)) {
		$sql .= "where (\n";
		$sql .= "	dashboard_name = :search \n";
		$sql .= "	or dashboard_description = :search \n";
		$sql .= ")\n";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($sql, $parameters);

//get the list
	$sql = "select \n";
	$sql .= "dashboard_uuid, \n";
	$sql .= "dashboard_name, \n";
	$sql .= "dashboard_icon, \n";
	$sql .= "( \n";
	$sql .= "	select \n";
	$sql .= "	string_agg(g.group_name, ', ') \n";
	$sql .= "	from \n";
	$sql .= "	v_dashboard_groups as dg, \n";
	$sql .= "	v_groups as g \n";
	$sql .= "	where \n";
	$sql .= "	dg.group_uuid = g.group_uuid \n";
	$sql .= "	and d.dashboard_uuid = dg.dashboard_uuid \n";
	$sql .= ") AS dashboard_groups, \n";
	$sql .= "dashboard_order, \n";
	$sql .= "cast(dashboard_enabled as text), \n";
	$sql .= "dashboard_description \n";
	$sql .= "from v_dashboard as d \n";
	if (isset($_GET["search"])) {
		$sql .= "where (\n";
		$sql .= "	lower(dashboard_name) like :search \n";
		$sql .= "	or lower(dashboard_description) like :search \n";
		$sql .= ")\n";
		$parameters['search'] = '%'.strtolower($search).'%';
	}
	$sql .= order_by($order_by, $order, 'dashboard_order, dashboard_name', 'asc');
	$sql .= limit_offset($rows_per_page ?? null, $offset ?? null);
	$database = new database;
	$dashboard = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-dashboard'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-dashboard']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'id'=>'btn_back','name'=>'btn_back','style'=>'margin-right: 15px;','link'=>'index.php']);
	if (permission_exists('dashboard_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','name'=>'btn_add','link'=>'dashboard_edit.php']);
	}
	if (permission_exists('dashboard_add') && !empty($dashboard)) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('dashboard_edit') && !empty($dashboard)) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('dashboard_delete') && !empty($dashboard)) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search ?? '')."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$settings->get('theme', 'button_icon_search'),'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$settings->get('theme', 'button_icon_reset'),'type'=>'button','id'=>'btn_reset','link'=>'dashboard.php','style'=>($search == '' ? 'display: none;' : null)]);
	if (!empty($paging_controls_mini)) {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('dashboard_add') && !empty($dashboard)) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('dashboard_edit') && !empty($dashboard)) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('dashboard_delete') && !empty($dashboard)) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search ?? '')."\">\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('dashboard_add') || permission_exists('dashboard_edit') || permission_exists('dashboard_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($dashboard) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('dashboard_name', $text['label-dashboard_name'], $order_by, $order);
	echo th_order_by('dashboard_groups', $text['label-dashboard_groups'], $order_by, $order);
	echo th_order_by('dashboard_icon', $text['label-icons'], $order_by, $order);
	echo th_order_by('dashboard_order', $text['label-dashboard_order'], $order_by, $order);
	echo th_order_by('dashboard_enabled', $text['label-dashboard_enabled'], $order_by, $order, null, "class='center'");
	echo "	<th class='hide-sm-dn'>".$text['label-dashboard_description']."</th>\n";
	if (permission_exists('dashboard_edit') && filter_var($_SESSION['theme']['list_row_edit_button']['boolean'] ?? false, FILTER_VALIDATE_BOOL)) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($dashboard)) {
		$x = 0;
		foreach ($dashboard as $row) {
			$list_row_url = '';
			if (permission_exists('dashboard_edit')) {
				$list_row_url = "dashboard_edit.php?id=".urlencode($row['dashboard_uuid']);
				if ($row['domain_uuid'] != $_SESSION['domain_uuid'] && permission_exists('domain_select')) {
					$list_row_url .= '&domain_uuid='.urlencode($row['domain_uuid']).'&domain_change=true';
				}
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('dashboard_add') || permission_exists('dashboard_edit') || permission_exists('dashboard_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='dashboard[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='dashboard[$x][dashboard_uuid]' value='".escape($row['dashboard_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('dashboard_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['dashboard_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['dashboard_name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['dashboard_groups'])."</td>\n";
			echo "	<td>".escape($row['dashboard_icon'])."</td>\n";
			echo "	<td>".escape($row['dashboard_order'])."</td>\n";
			if (permission_exists('dashboard_edit')) {
				echo "	<td class='no-link center'>\n";
				echo "		<input type='hidden' name='number_translations[$x][dashboard_enabled]' value='".escape($row['dashboard_enabled'])."' />\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['dashboard_enabled']?:'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.($row['dashboard_enabled']?:'false')];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['dashboard_description'])."</td>\n";
			if (permission_exists('dashboard_edit') && filter_var($_SESSION['theme']['list_row_edit_button']['boolean'] ?? false, FILTER_VALIDATE_BOOL)) {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$settings->get('theme', 'button_icon_edit'),'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($dashboard);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".($paging_controls ?? '')."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
