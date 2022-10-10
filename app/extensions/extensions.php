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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('extension_view')) {
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
	if (is_array($_POST['extensions'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$extensions = $_POST['extensions'];
	}

//process the http post data by action
	if ($action != '' && is_array($extensions) && @sizeof($extensions) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('extension_enabled')) {
					$obj = new extension;
					$obj->toggle($extensions);
				}
				break;
			case 'delete_extension':
			case 'delete_extension_voicemail':
				if (permission_exists('extension_delete')) {
					$obj = new extension;
					if ($action == 'delete_extension_voicemail' && permission_exists('voicemail_delete')) {
						$obj->delete_voicemail = true;
					}
					$obj->delete($extensions);
				}
				break;
		}

		header('Location: extensions.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//get total extension count for domain
if (is_numeric($_SESSION['limit']['extensions']['numeric'])) {
	$sql = "select count(*) from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$database = new database;
	$total_extensions = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);
}

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = " and ( ";
		$sql_search .= "lower(extension) like :search ";
		$sql_search .= "or lower(number_alias) like :search ";
		$sql_search .= "or lower(effective_caller_id_name) like :search ";
		$sql_search .= "or lower(effective_caller_id_number) like :search ";
		$sql_search .= "or lower(outbound_caller_id_name) like :search ";
		$sql_search .= "or lower(outbound_caller_id_number) like :search ";
		$sql_search .= "or lower(emergency_caller_id_name) like :search ";
		$sql_search .= "or lower(emergency_caller_id_number) like :search ";
		$sql_search .= "or lower(directory_first_name) like :search ";
		$sql_search .= "or lower(directory_last_name) like :search ";
		$sql_search .= "or lower(call_group) like :search ";
		$sql_search .= "or lower(user_context) like :search ";
		$sql_search .= "or lower(enabled) like :search ";
		$sql_search .= "or lower(description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}



//get total extension count
	$sql = "select count(*) from v_extensions where true ";
	if (!($_GET['show'] == "all" && permission_exists('extension_all'))) {
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	$sql .= $sql_search;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if ($_GET['show'] == "all" && permission_exists('extension_all')) {
		$param .= "&show=all";
	}
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page); //bottom
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true); //top
	$offset = $rows_per_page * $page;

//get the extensions
	$sql = str_replace('count(*)', '*', $sql);
	if ($order_by == '' || $order_by == 'extension') {
		if ($db_type == 'pgsql') {
			$sql .= 'order by natural_sort(extension) '.$order; //function in app_defaults.php
		}
		else {
			$sql .= 'order by extension '.$order;
		}
	}
	else {
		$sql .= order_by($order_by, $order);
	}
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$extensions = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get the registrations
	if (permission_exists('extension_registered')) {
		$obj = new registrations;
		if ($_GET['show'] == 'all') {
			$obj->show = 'all';
		}
		$registrations = $obj->get('all');
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-extensions'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-extensions']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('extension_import') && (!is_numeric($_SESSION['limit']['extensions']['numeric']) || $total_extensions < $_SESSION['limit']['extensions']['numeric'])) {
		echo button::create(['type'=>'button','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'link'=>'extension_imports.php']);
	}
	if (permission_exists('extension_export')) {
		echo button::create(['type'=>'button','label'=>$text['button-export'],'icon'=>$_SESSION['theme']['button_icon_export'],'link'=>'extension_download.php']);
	}
	$margin_left = permission_exists('extension_import') || permission_exists('extension_export') ? "margin-left: 15px;" : null;
	if (permission_exists('extension_add') && (!is_numeric($_SESSION['limit']['extensions']['numeric']) || $total_extensions < $_SESSION['limit']['extensions']['numeric'])) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','style'=>$margin_left,'link'=>'extension_edit.php']);
		unset($margin_left);
	}
	if (permission_exists('extension_enabled') && $extensions) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none; '.$margin_left,'onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
		unset($margin_left);
	}
	if (permission_exists('extension_delete') && $extensions) {
		if (permission_exists('voicemail_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; '.$margin_left,'onclick'=>"modal_open('modal-delete-options');"]);
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; '.$margin_left,'onclick'=>"modal_open('modal-delete');"]);
		}
		unset($margin_left);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('extension_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'extensions.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('extension_enabled') && $extensions) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('extension_delete') && $extensions) {
		if (permission_exists('voicemail_delete')) {
			echo modal::create([
				'id'=>'modal-delete-options',
				'title'=>$text['modal_title-confirmation'],
				'message'=>$text['message-delete_selection'],
				'actions'=>
					button::create(['type'=>'button','label'=>$text['button-cancel'],'icon'=>$_SESSION['theme']['button_icon_cancel'],'collapse'=>'hide-xs','onclick'=>'modal_close();']).
					button::create(['type'=>'button','label'=>$text['label-extension_and_voicemail'],'icon'=>'voicemail','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete_extension_voicemail'); list_form_submit('form_list');"]).
					button::create(['type'=>'button','label'=>$text['label-extension_only'],'icon'=>'phone-alt','collapse'=>'never','style'=>'float: right;','onclick'=>"modal_close(); list_action_set('delete_extension'); list_form_submit('form_list');"])
				]);
		}
		else {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'id'=>'btn_delete','icon'=>'check','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete_extension'); list_form_submit('form_list');"])]);
		}
	}

	echo $text['description-extensions']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('extension_enabled') || permission_exists('extension_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($extensions ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == "all" && permission_exists('extension_all')) {
		echo "<th>".$text['label-domain']."</th>\n";
		//echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	echo th_order_by('extension', $text['label-extension'], $order_by, $order);
	echo th_order_by('effective_caller_id_name', $text['label-effective_cid_name'], $order_by, $order, null, "class='hide-xs'");
	if (permission_exists("outbound_caller_id_name")) {
		echo th_order_by('outbound_caller_id_name', $text['label-outbound_cid_name'], $order_by, $order, null, "class='hide-sm-dn'");
	}
	echo th_order_by('call_group', $text['label-call_group'], $order_by, $order);
	if (permission_exists("extension_user_context")) {
		echo th_order_by('user_context', $text['label-user_context'], $order_by, $order);
	}
	if (permission_exists('extension_registered')) {
		echo "<th>".$text['label-is_registered']."</th>\n";
 	}
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('extension_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($extensions) && @sizeof($extensions) != 0) {
		$x = 0;
		foreach($extensions as $row) {
			if (permission_exists('extension_edit')) {
				$list_row_url = "extension_edit.php?id=".urlencode($row['extension_uuid']).(is_numeric($page) ? '&page='.urlencode($page) : null);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('extension_enabled') || permission_exists('extension_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='extensions[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='extensions[$x][uuid]' value='".escape($row['extension_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == "all" && permission_exists('extension_all')) {
				echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			echo "	<td>";
			if (permission_exists('extension_edit')) {
				echo "<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['extension'])."</a>";
			}
			else {
				echo escape($row['extension']);
			}
			echo "	</td>\n";

			echo "	<td class='hide-xs'>".escape($row['effective_caller_id_name'])."&nbsp;</td>\n";
			if (permission_exists("outbound_caller_id_name")) {
				echo "	<td class='hide-sm-dn'>".escape($row['outbound_caller_id_name'])."&nbsp;</td>\n";
			}
			echo "	<td>".escape($row['call_group'])."&nbsp;</td>\n";
			if (permission_exists("extension_user_context")) {
				echo "	<td>".escape($row['user_context'])."</td>\n";
			}
			if (permission_exists('extension_registered')) {
				echo "	<td>";
				$extension_number = $row['extension'].'@'.$_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				$extension_number_alias = $row['number_alias'];
				if (strlen($extension_number_alias) > 0) {
					$extension_number_alias .= '@'.$_SESSION['domains'][$row['domain_uuid']]['domain_name'];
				}
				$found_count = 0;
				if (is_array($registrations)) {
					foreach ($registrations as $array) {
						if ($extension_number == $array['user'] || ($extension_number_alias != '' && $extension_number_alias == $array['user'])) {
							$found_count++;
						}
					}
				}
				if ($found_count > 0) {
					echo $text['label-true']." (".$found_count.")";
				}
				else {
					echo $text['label-false'];
				}
				unset($extension_number, $extension_number_alias, $found_count, $array);
				echo "&nbsp;</td>\n";
			}
			if (permission_exists('extension_enabled')) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo $text['label-'.$row['enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['description'])."</td>\n";
			if (permission_exists('extension_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

	unset($extensions);

//show the footer
	require_once "resources/footer.php";

?>
