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
	require_once "resources/paging.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_flow_view')) {
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
	if (is_array($_POST['call_flows'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$call_flows = $_POST['call_flows'];
		$toggle_field = $_POST['toggle_field'];
	}

//process the http post data by action
	if ($action != '' && is_array($call_flows) && @sizeof($call_flows) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('call_flow_add')) {
					$obj = new call_flows;
					$obj->copy($call_flows);
				}
				break;
			case 'toggle':
				if (permission_exists('call_flow_edit')) {
					$obj = new call_flows;
					$obj->toggle_field = $toggle_field;
					$obj->toggle($call_flows);
				}
				break;
			case 'delete':
				if (permission_exists('call_flow_delete')) {
					$obj = new call_flows;
					$obj->delete($call_flows);
				}
				break;
		}

		header('Location: call_flows.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "lower(call_flow_name) like :search ";
		$sql_search .= "or lower(call_flow_extension) like :search ";
		$sql_search .= "or lower(call_flow_feature_code) like :search ";
		$sql_search .= "or lower(call_flow_context) like :search ";
		$sql_search .= "or lower(call_flow_pin_number) like :search ";
		$sql_search .= "or lower(call_flow_label) like :search ";
		$sql_search .= "or lower(call_flow_alternate_label) like :search ";
		$sql_search .= "or lower(call_flow_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(*) from v_call_flows ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'call_flow_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$call_flows = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include header
	$document['title'] = $text['title-call_flows'];
	require_once "resources/header.php";

//javascript for toggle select box
	echo "<script language='javascript' type='text/javascript'>\n";
	echo "	function toggle_select() {\n";
	echo "		$('#call_flow_feature').fadeToggle(400, function() {\n";
	echo "			document.getElementById('call_flow_feature').selectedIndex = 0;\n";
	echo "			document.getElementById('call_flow_feature').focus();\n";
	echo "		});\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-call_flows']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('call_flow_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'call_flow_edit.php']);
	}
	if (permission_exists('call_flow_add') && $call_flows) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('call_flow_edit') && $call_flows) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','onclick'=>"toggle_select(); this.blur();"]);
		echo 		"<select class='formfld' style='display: none; width: auto;' id='call_flow_feature' onchange=\"if (this.selectedIndex != 0) { modal_open('modal-toggle','btn_toggle'); }\">";
		echo "			<option value='' selected='selected'>".$text['label-select']."</option>";
		echo "			<option value='call_flow_status'>".$text['label-call_flow_status']."</option>";
		echo "			<option value='call_flow_enabled'>".$text['label-enabled']."</option>";
		echo "		</select>";
	}
	if (permission_exists('call_flow_delete') && $call_flows) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_flows.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('call_flow_add') && $call_flows) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('call_flow_edit') && $call_flows) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); document.getElementById('toggle_field').value = document.getElementById('call_flow_feature').options[document.getElementById('call_flow_feature').selectedIndex].value; list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('call_flow_delete') && $call_flows) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-call_flows']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' id='toggle_field' name='toggle_field' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('call_flow_add') || permission_exists('call_flow_edit') || permission_exists('call_flow_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($call_flows ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('call_flow_name', $text['label-call_flow_name'], $order_by, $order);
	echo th_order_by('call_flow_extension', $text['label-call_flow_extension'], $order_by, $order);
	echo th_order_by('call_flow_feature_code', $text['label-call_flow_feature_code'], $order_by, $order);
	echo th_order_by('call_flow_status', $text['label-call_flow_status'], $order_by, $order);
	if (permission_exists('call_flow_context')) {
		echo th_order_by('call_flow_context', $text['label-call_flow_context'], $order_by, $order);
	}
	echo th_order_by('call_flow_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('call_flow_description', $text['label-call_flow_description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('call_flow_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($call_flows)) {
		$x = 0;
		foreach ($call_flows as $row) {
			if (permission_exists('call_flow_edit')) {
				$list_row_url = "call_flow_edit.php?id=".urlencode($row['call_flow_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('call_flow_add') || permission_exists('call_flow_edit') || permission_exists('call_flow_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='call_flows[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='call_flows[$x][uuid]' value='".escape($row['call_flow_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td><a href='".$list_row_url."'>".escape($row['call_flow_name'])."</a>&nbsp;</td>\n";
			echo "	<td>".escape($row['call_flow_extension'])."&nbsp;</td>\n";
			echo "	<td>".escape($row['call_flow_feature_code'])."&nbsp;</td>\n";
			$status_label = $row['call_flow_status'] != 'false' ? $row['call_flow_label'] : $row['call_flow_alternate_label'];
			if (permission_exists('call_flow_edit')) {
				echo "	<td class='no-link'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>escape($status_label),'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); document.getElementById('toggle_field').value = 'call_flow_status'; list_form_submit('form_list')"]);
			}
			else {
				echo "	<td>";
				echo escape($status_label);
			}
			echo "	</td>\n";
			if (permission_exists('call_flow_context')) {
				echo "	<td>".escape($row['call_flow_context'])."&nbsp;</td>\n";
			}
			if (permission_exists('call_flow_edit')) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.($row['call_flow_enabled'] == "true" ? 'true' : 'false')],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); document.getElementById('toggle_field').value = 'call_flow_enabled'; list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo escape($row['call_flow_enabled']);
			}
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['call_flow_description'])."&nbsp;</td>\n";
			if (permission_exists('call_flow_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($call_flows);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
