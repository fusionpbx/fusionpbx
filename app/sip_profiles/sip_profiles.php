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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
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
	if (permission_exists('sip_profile_view')) {
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
	if (is_array($_POST['sip_profiles'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$sip_profiles = $_POST['sip_profiles'];
	}
//process the http post data by action
	if ($action != '' && is_array($sip_profiles) && @sizeof($sip_profiles) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('sip_profile_edit')) {
					$obj = new sip_profiles;
					$obj->toggle($sip_profiles);
				}
				break;
			case 'delete':
				if (permission_exists('sip_profile_delete')) {
					$obj = new sip_profiles;
					$obj->delete($sip_profiles);
				}
				break;
		}

		header('Location: sip_profiles.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	if (isset($_GET["order_by"])) {
		$order_by = $_GET["order_by"];
	}
	else {
		$order_by = 'sip_profile_name';
	}
	$order = $_GET["order"];

//add the search string
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "where (";
		$sql_search .= "lower(sip_profile_name) like :search ";
		$sql_search .= "or lower(sip_profile_hostname) like :search ";
		$sql_search .= "or lower(sip_profile_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(sip_profile_uuid) from v_sip_profiles ";
	$sql .= $sql_search;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(sip_profile_uuid)', '*', $sql);
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$sip_profiles = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-sip_profiles'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-sip_profiles']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('sip_profile_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'sip_profile_edit.php']);
	}
	if (permission_exists('sip_profile_edit') && $sip_profiles) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('sip_profile_delete') && $sip_profiles) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	if (permission_exists('sofia_global_setting_view')) {
		echo button::create(['type'=>'button','label'=>$text['button-settings'],'icon'=>'code','collapse'=>'hide-xs','link'=>'/app/sofia_global_settings/sofia_global_settings.php']);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'sip_profiles.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('sip_profile_edit') && $sip_profiles) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('sip_profile_delete') && $sip_profiles) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-sip_profiles']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('sip_profile_add') || permission_exists('sip_profile_edit') || permission_exists('sip_profile_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($sip_profiles ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('sip_profile_name', $text['label-sip_profile_name'], $order_by, $order);
	echo th_order_by('sip_profile_hostname', $text['label-sip_profile_hostname'], $order_by, $order);
	echo th_order_by('sip_profile_enabled', $text['label-sip_profile_enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('sip_profile_description', $text['label-sip_profile_description'], $order_by, $order, null, "class='hide-sm-dn pct-70'");
	if (permission_exists('sip_profile_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($sip_profiles) && @sizeof($sip_profiles) != 0) {
		$x = 0;
		foreach ($sip_profiles as $row) {
			if (permission_exists('sip_profile_edit')) {
				$list_row_url = "sip_profile_edit.php?id=".urlencode($row['sip_profile_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('sip_profile_add') || permission_exists('sip_profile_edit') || permission_exists('sip_profile_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='sip_profiles[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='sip_profiles[$x][uuid]' value='".escape($row['sip_profile_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td class='no-wrap'>\n";
			if (permission_exists('sip_profile_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['sip_profile_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['sip_profile_name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['sip_profile_hostname'])."&nbsp;</td>\n";
			if (permission_exists('sip_profile_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['sip_profile_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['sip_profile_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['sip_profile_description'])."&nbsp;</td>\n";
			if (permission_exists('sip_profile_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($sip_profiles);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
