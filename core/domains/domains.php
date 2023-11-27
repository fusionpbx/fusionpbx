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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2022
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//redirect admin to app instead
	if (file_exists($_SERVER["PROJECT_ROOT"]."/app/domains/app_config.php") && !permission_exists('domain_all') && !is_cli()) {
		header("Location: ".PROJECT_PATH."/app/domains/domains.php");
		exit;
	}

//change the domain
	if (!empty($_GET["domain_uuid"]) && is_uuid($_GET["domain_uuid"]) && $_GET["domain_change"] == "true") {
		if (permission_exists('domain_select')) {

			//update the domain session variables
				$domain_uuid = $_GET["domain_uuid"];
				$_SESSION["previous_domain_uuid"] = $_SESSION['domain_uuid'];
				$_SESSION['domain_uuid'] = $domain_uuid;

			//get the domain details
				$sql = "select * from v_domains ";
				$sql .= "order by domain_name asc ";
				$database = new database;
				$domains = $database->select($sql, null, 'all');
				if (!empty($domains)) {
					foreach($domains as $row) {
						$_SESSION['domains'][$row['domain_uuid']] = $row;
					}
				}
				unset($sql, $result);

			//update the domain session variables
				$_SESSION["domain_name"] = $_SESSION['domains'][$domain_uuid]['domain_name'];
				$_SESSION['domain']['template']['name'] = $_SESSION['domains'][$domain_uuid]['template_name'] ?? null;
				$_SESSION["context"] = $_SESSION["domain_name"];

			//clear the extension array so that it is regenerated for the selected domain
				unset($_SESSION['extension_array']);

			//set the setting arrays
				$domain = new domains();
				$domain->set();

			//redirect the user
				if (!empty($_SESSION["login"]["destination"])) {
					// to default, or domain specific, login destination
					header("Location: ".PROJECT_PATH.$_SESSION["login"]["destination"]["text"]);
				}
				else {
					header("Location: ".PROJECT_PATH."/core/dashboard/");
				}
				exit;
		}
	}

//check permission
	if (permission_exists('domain_all') && permission_exists('domain_view')) {
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
	if (!empty($_POST['domains'])) {
		$action = $_POST['action'] ?? '';
		$search = $_POST['search'] ?? '';
		$domains = $_POST['domains'] ?? '';
	}

//process the http post data by action
	if (!empty($action) && !empty($domains)) {
		switch ($action) {
			case 'copy':
				if (permission_exists('domain_add')) {
					$obj = new domains;
					$obj->copy($domains);
				}
				break;
			case 'toggle':
				if (permission_exists('domain_edit')) {
					$obj = new domains;
					$obj->toggle($domains);
				}
				break;
			case 'delete':
				if (permission_exists('domain_delete')) {
					$obj = new domains;
					$obj->delete($domains);
				}
				break;
		}

		header('Location: domains.php'.(!empty($search) ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by and sanitize the values
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//set additional variables
	$search = $_GET["search"] ?? '';
	$show = $_GET["show"] ?? '';

//set from session variables
	$list_row_edit_button = !empty($_SESSION['theme']['list_row_edit_button']['boolean']) ? $_SESSION['theme']['list_row_edit_button']['boolean'] : 'false';

//add the search string
	if (!empty($search)) {
		$search =  strtolower($_GET["search"]);
		$sql_search = " (";
		$sql_search .= "	lower(domain_name) like :search ";
		$sql_search .= "	or lower(domain_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get the count
	$sql = "select count(domain_uuid) from v_domains ";
	if (!empty($sql_search)) {
		$sql .= "where ".$sql_search;
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters ?? null, 'column');

//prepare to page the results
	$rows_per_page = (!empty($_SESSION['domain']['paging']['numeric'])) ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	$page = !empty($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select domain_uuid, domain_name, cast(domain_enabled as text), domain_description ";
	$sql .= "from v_domains ";
	if (!empty($sql_search)) {
		$sql .= "where ".$sql_search;
	}
	$sql .= order_by($order_by, $order, 'domain_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$domains = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-domains'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-domains']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('domain_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'domain_edit.php']);
	}
	if (permission_exists('domain_edit') && $domains) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
 	if (permission_exists('domain_delete') && $domains) {
 		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete_domain');"]);
 	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'domains.php','style'=>($search == '' ? 'display: none;' : null)]);
	if (!empty($paging_controls_mini)) {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('domain_edit') && !empty($domains)) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
 	if (permission_exists('domain_delete') && !empty($domains)) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
 	}

	echo $text['description-domains']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('domain_edit') || permission_exists('domain_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($domains) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == 'all' && permission_exists('domain_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	echo th_order_by('domain_name', $text['label-domain_name'], $order_by, $order);
	echo "<th class='center'>".$text['label-tools']."</th>";
	echo th_order_by('domain_enabled', $text['label-domain_enabled'], $order_by, $order, null, "class='center'");
	echo "	<th class='hide-sm-dn'>".$text['label-domain_description']."</th>\n";
	if (permission_exists('domain_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($domains)) {
		$x = 0;
		foreach ($domains as $row) {
			$list_row_url = '';
			if (permission_exists('domain_edit')) {
				$list_row_url = "domain_edit.php?id=".urlencode($row['domain_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('domain_edit') || permission_exists('domain_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='domains[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='domains[$x][uuid]' value='".escape($row['domain_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == 'all' && permission_exists('domain_all')) {
				echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('domain_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['domain_name'])."</a>\n";
			}
			else {
				echo "	".escape($row['domain_name']);
			}
			echo "	</td>\n";
			echo "	<td class='no-link center'>\n";
			echo "		<a href='".PROJECT_PATH."/core/domains/domains.php?domain_uuid=".escape($row['domain_uuid'])."&domain_change=true'>".$text['label-manage']."</a>";
			if (permission_exists('domain_setting_view')) {
				$list_setting_url = PROJECT_PATH."/core/domain_settings/domain_settings.php?id=".urlencode($row['domain_uuid']);
				echo "&nbsp;&nbsp; <a href='".$list_setting_url."'\">".$text['button-settings'];
			}
			echo "	</td>\n";
			if (permission_exists('domain_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['domain_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
				echo "	</td>\n";
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['domain_enabled']];
				echo "	</td>\n";
			}
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['domain_description'])."</td>\n";
			if (permission_exists('domain_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($domains);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
