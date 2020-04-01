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

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";


//check permissions
	if (permission_exists('var_view')) {
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
	if (is_array($_POST['vars'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$vars = $_POST['vars'];
	}

//process the http post data by action
	if ($action != '' && is_array($vars) && @sizeof($vars) != 0) {
		switch ($action) {
			case 'copy':
				if (permission_exists('var_add')) {
					$obj = new vars;
					$obj->copy($vars);
				}
				break;
			case 'toggle':
				if (permission_exists('var_edit')) {
					$obj = new vars;
					$obj->toggle($vars);
				}
				break;
			case 'delete':
				if (permission_exists('var_delete')) {
					$obj = new vars;
					$obj->delete($vars);
				}
				break;
		}

		header('Location: vars.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search string
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "where (";
		$sql_search .= "	lower(var_category) like :search ";
		$sql_search .= "	or lower(var_name) like :search ";
		$sql_search .= "	or lower(var_value) like :search ";
		$sql_search .= "	or lower(var_hostname) like :search ";
		$sql_search .= "	or lower(var_enabled) like :search ";
		$sql_search .= "	or lower(var_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the count
	$sql = "select count(var_uuid) from v_vars ";
	$sql .= $sql_search;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	$param = $order_by ? "&order_by=".$order_by."&order=".$order : null;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(var_uuid)', '*', $sql);
	$sql .= $order_by != '' ? order_by($order_by, $order) : " order by var_category, var_order asc, var_name asc ";
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$vars = $database->select($sql, $parameters, 'all');
	unset($sql);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-variables'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-variables']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('var_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'var_edit.php']);
	}
	if (permission_exists('var_add') && $vars) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'name'=>'btn_copy','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('var_edit') && $vars) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('var_delete') && $vars) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'vars.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('var_add') && $vars) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('var_edit') && $vars) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('var_delete') && $vars) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-variables']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	function write_header($modifier) {
		global $text, $order_by, $order, $vars;
		$modifier = str_replace('/', '', $modifier);
		$modifier = str_replace('  ', ' ', $modifier);
		$modifier = str_replace(' ', '_', $modifier);
		$modifier = str_replace(':', '', $modifier);
		$modifier = strtolower(trim($modifier));
		echo "\n";
		echo "<tr class='list-header'>\n";
		if (permission_exists('var_edit') || permission_exists('var_delete')) {
			echo "	<th class='checkbox'>\n";
			echo "		<input type='checkbox' id='checkbox_all_".$modifier."' name='checkbox_all' onclick=\"list_all_toggle('".$modifier."');\" ".($vars ?: "style='visibility: hidden;'").">\n";
			echo "	</th>\n";
		}
		echo th_order_by('var_name', $text['label-name'], $order_by, $order, null, "class='pct-30'");
		echo th_order_by('var_value', $text['label-value'], $order_by, $order, null, "class='pct-40'");
		echo th_order_by('var_hostname', $text['label-hostname'], $order_by, $order, null, "class='hide-sm-dn'");
		echo th_order_by('var_enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
		echo "<th class='hide-sm-dn'>".$text['label-description']."</th>\n";
		if (permission_exists('var_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
			echo "<td class='action-button'>&nbsp;</td>\n";
		}
		echo "</tr>\n";
	}
	if (is_array($vars) && @sizeof($vars) != 0) {
		$previous_category = '';
		foreach ($vars as $x => $row) {
			//write category and column headings
				if ($previous_category != $row["var_category"]) {
					echo "<tr>\n";
					echo "<td colspan='7' class='no-link'>\n";
					echo ($previous_category != '' ? '<br />' : null)."<b>".$row["var_category"]."</b>";
					echo "</td>\n";
					echo "</tr>\n";
					write_header($row["var_category"]);
				}
			if (permission_exists('var_edit')) {
				$list_row_url = "var_edit.php?id=".urlencode($row['var_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('var_add') || permission_exists('var_edit') || permission_exists('var_delete')) {
				$modifier = strtolower(trim($row["var_category"]));
				$modifier = str_replace('/', '', $modifier);
				$modifier = str_replace('  ', ' ', $modifier);
				$modifier = str_replace(' ', '_', $modifier);
				$modifier = str_replace(':', '', $modifier);
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='vars[$x][checked]' id='checkbox_".$x."' class='checkbox_".$modifier."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all_".$modifier."').checked = false; }\">\n";
				echo "		<input type='hidden' name='vars[$x][uuid]' value='".escape($row['var_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "   <td class='overflow'>";
			if (permission_exists('var_edit')) {
				echo "<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['var_name'])."</a>";
			}
			else {
				echo escape($row['var_name']);
			}
			echo "	</td>\n";
			echo "	<td class='overflow'>".$row['var_value']."</td>\n";
			echo "	<td class='hide-sm-dn'>".$row['var_hostname']."&nbsp;</td>\n";
			if (permission_exists('var_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['var_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['var_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape(base64_decode($row['var_description']))."</td>\n";
			if (permission_exists('var_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";

			$previous_category = $row["var_category"];

			$x++;
		}
	}
	unset($vars);

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>