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
	Portions created by the Initial Developer are Copyright (C) 2010-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	James Rose <james.o.rose@gmail.com>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('ring_group_view')) {
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
	if (is_array($_POST['ring_groups'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$ring_groups = $_POST['ring_groups'];
	}

//process the http post data by action
	if ($action != '' && is_array($ring_groups) && @sizeof($ring_groups) != 0) {
		switch ($action) {
			case 'copy':
				$obj = new ring_groups;
				$obj->copy($ring_groups);
				break;
			case 'toggle':
				$obj = new ring_groups;
				$obj->toggle($ring_groups);
				break;
			case 'delete':
				$obj = new ring_groups;
				$obj->delete($ring_groups);
				break;
		}

		header('Location: ring_groups.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get total domain ring group count
	$sql = "select count(*) from v_ring_groups ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$total_ring_groups = $database->select($sql, $parameters, 'column');
	$num_rows = $total_ring_groups;

//get filtered ring group count
	if ($search) {
		$sql = "select count(*) from v_ring_groups where true ";
		if ($_GET['show'] != "all" || !permission_exists('ring_group_all')) {
			$sql .= "and domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
		}
		if (isset($search)) {
			$sql .= "and (";
			$sql .= "lower(ring_group_name) like :search ";
			$sql .= "or lower(ring_group_extension) like :search ";
			$sql .= "or lower(ring_group_description) like :search ";
			$sql .= "or lower(ring_group_enabled) like :search ";
			$sql .= "or lower(ring_group_strategy) like :search ";
			$sql .= ") ";
			$parameters['search'] = '%'.$search.'%';
		}
		$database = new database;
		$num_rows = $database->select($sql, $parameters, 'column');
	}
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	$param = ($_GET['show'] == "all" && permission_exists('ring_group_all')) ? "&show=all" : null;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * from v_ring_groups where true ";
	if ($_GET['show'] != "all" || !permission_exists('ring_group_all')) {
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (isset($search)) {
		$sql .= "and (";
		$sql .= "lower(ring_group_name) like :search ";
		$sql .= "or lower(ring_group_extension) like :search ";
		$sql .= "or lower(ring_group_description) like :search ";
		$sql .= "or lower(ring_group_enabled) like :search ";
		$sql .= "or lower(ring_group_strategy) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= ($order_by) ? order_by($order_by, $order) : "order by ring_group_name asc, ring_group_extension asc ";
	$sql .= limit_offset($rows_per_page, $offset);
	$ring_groups = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-ring_groups'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-ring_groups']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('ring_group_add') && (!is_numeric($_SESSION['limit']['ring_groups']['numeric']) || ($total_ring_groups < $_SESSION['limit']['ring_groups']['numeric']))) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'ring_group_edit.php']);
	}
	if (permission_exists('ring_group_add') && $ring_groups && (!is_numeric($_SESSION['limit']['ring_groups']['numeric']) || ($total_ring_groups < $_SESSION['limit']['ring_groups']['numeric']))) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('ring_group_edit') && $ring_groups) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('ring_group_delete') && $ring_groups) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('ring_group_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'ring_groups.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('ring_group_add') && $ring_groups && (!is_numeric($_SESSION['limit']['ring_groups']['numeric']) || ($total_ring_groups < $_SESSION['limit']['ring_groups']['numeric']))) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('ring_group_edit') && $ring_groups) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('ring_group_delete') && $ring_groups) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('ring_group_add') || permission_exists('ring_group_edit') || permission_exists('ring_group_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($ring_groups ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == "all" && permission_exists('ring_group_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	echo th_order_by('ring_group_name', $text['label-name'], $order_by, $order);
	echo th_order_by('ring_group_extension', $text['label-extension'], $order_by, $order);
	echo th_order_by('ring_group_strategy', $text['label-strategy'], $order_by, $order);
	echo th_order_by('ring_group_forward_enabled', $text['label-forwarding'], $order_by, $order);
	echo th_order_by('ring_group_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('ring_group_description', $text['header-description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('ring_group_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($ring_groups) && @sizeof($ring_groups) != 0) {
		$x = 0;
		foreach ($ring_groups as $row) {
			if (permission_exists('ring_group_edit')) {
				$list_row_url = "ring_group_edit.php?id=".urlencode($row['ring_group_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('ring_group_add') || permission_exists('ring_group_edit') || permission_exists('ring_group_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='ring_groups[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='ring_groups[$x][uuid]' value='".escape($row['ring_group_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == "all" && permission_exists('ring_group_all')) {
				echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			echo "	<td>";
			if (permission_exists('ring_group_edit')) {
				echo "<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['ring_group_name'])."</a>";
			}
			else {
				echo escape($row['ring_group_name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['ring_group_extension'])."&nbsp;</td>\n";
			echo "	<td>".$text['option-'.escape($row['ring_group_strategy'])]."&nbsp;</td>\n";
			echo "	<td>".($row['ring_group_forward_enabled'] == 'true' ? format_phone(escape($row['ring_group_forward_destination'])) : null)."&nbsp;</td>\n";
			if (permission_exists('ring_group_edit')) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['ring_group_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo $text['label-'.$row['ring_group_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['ring_group_description'])."&nbsp;</td>\n";
			if (permission_exists('ring_group_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($ring_groups);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
