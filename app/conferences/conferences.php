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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
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
	if (permission_exists('conference_view')) {
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
	if (is_array($_POST['conferences'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$conferences = $_POST['conferences'];
	}

//process the http post data by action
	if ($action != '' && is_array($conferences) && @sizeof($conferences) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('conference_add')) {
					$obj = new conferences;
					$obj->copy($conferences);
				}
				break;
			case 'toggle':
				if (permission_exists('conference_edit')) {
					$obj = new conferences;
					$obj->toggle($conferences);
				}
				break;
			case 'delete':
				if (permission_exists('conference_delete')) {
					$obj = new conferences;
					$obj->delete($conferences);
				}
				break;
		}

		header('Location: conferences.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "lower(conference_name) like :search ";
		$sql_search .= "or lower(conference_extension) like :search ";
		$sql_search .= "or lower(conference_pin_number) like :search ";
		$sql_search .= "or lower(conference_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	if (if_group("superadmin") || if_group("admin")) {
		//show all extensions
		$sql = "select count(*) from v_conferences ";
		$sql .= "where true ";
		if ($_GET['show'] != "all" || !permission_exists('conference_all')) {
			$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		}
	}
	else {
		//show only assigned extensions
		$sql = "select count(*) from v_conferences as c, v_conference_users as u ";
		$sql .= "where c.conference_uuid = u.conference_uuid ";
		$sql .= "and c.domain_uuid = :domain_uuid ";
		$sql .= "and u.user_uuid = :user_uuid ";
		$parameters['user_uuid'] = $_SESSION['user_uuid'];
	}
	$sql .= $sql_search;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".urlencode($search);
	if ($_GET['show'] == "all" && permission_exists('conference_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$conferences = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-conferences'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-conferences']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('conference_active_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-view_active'],'icon'=>'comments','style'=>'margin-right: 15px;','link'=>PROJECT_PATH.'/app/conferences_active/conferences_active.php']);
	}
	if (permission_exists('conference_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'conference_edit.php']);
	}
	if (permission_exists('conference_add') && $conferences) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('conference_edit') && $conferences) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('conference_delete') && $conferences) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('conference_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?type=&show=all'.($search != '' ? "&search=".urlencode($search) : null)]);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'conferences.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('conference_add') && $conferences) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('conference_edit') && $conferences) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('conference_delete') && $conferences) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('conference_add') || permission_exists('conference_edit') || permission_exists('conference_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($conferences ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == "all" && permission_exists('conference_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param, "class='shrink'");
	}
	echo th_order_by('conference_name', $text['table-name'], $order_by, $order);
	echo th_order_by('conference_extension', $text['table-extension'], $order_by, $order);
	echo th_order_by('conference_profile', $text['table-profile'], $order_by, $order);
	echo th_order_by('conference_order', $text['table-order'], $order_by, $order, null, "class='center'");
	echo "<th style='text-align: center;'>".$text['label-tools']."</th>\n";
	echo th_order_by('conference_enabled', $text['table-enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('conference_description', $text['table-description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('conference_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($conferences) && @sizeof($conferences) != 0) {
		$x = 0;
		foreach($conferences as $row) {
			if (permission_exists('conference_edit')) {
				$list_row_url = "conference_edit.php?id=".urlencode($row['conference_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('conference_add') || permission_exists('conference_edit') || permission_exists('conference_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='conferences[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='conferences[$x][uuid]' value='".escape($row['conference_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == "all" && permission_exists('conference_all')) {
				if (strlen($_SESSION['domains'][$row['domain_uuid']]['domain_name']) > 0) {
					$domain = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				else {
					$domain = $text['label-global'];
				}
				echo "	<td>".escape($domain)."</td>\n";
			}
			echo "	<td><a href='".$list_row_url."'>".str_replace('-', ' ', $row['conference_name'])."</a>&nbsp;</td>\n";
			echo "	<td>".escape($row['conference_extension'])."&nbsp;</td>\n";
			echo "	<td>".escape($row['conference_profile'])."&nbsp;</td>\n";
			echo "	<td class='center'>".escape($row['conference_order'])."&nbsp;</td>\n";
			echo "	<td class='no-link center'>\n";
			if (permission_exists('conference_interactive_view')) {
				echo "		<a href='".PROJECT_PATH."/app/conferences_active/conference_interactive.php?c=".urlencode($row['conference_extension'])."'>".$text['label-view']."</a>\n";
			}
			else if (permission_exists('conference_active_view')) {
				echo "		<a href='".PROJECT_PATH."/app/conferences_active/conferences_active.php'>".$text['label-view']."</a>\n";
			}
			else {
				echo "		&nsbp;\n";
			}
			if (permission_exists('conference_cdr_view')) {
				echo "		<a href='".PROJECT_PATH.'/app/conference_cdr/conference_cdr.php?id='.urlencode($row['conference_uuid'])."'>".$text['label-cdr']."</a>\n";
			}
			echo "	</td>\n";
			if (permission_exists('conference_edit')) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['conference_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo $text['label-'.$row['conference_enabled']];
			}
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['conference_description'])."&nbsp;</td>\n";
			if (permission_exists('conference_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($conferences);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
