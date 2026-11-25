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
	Portions created by the Initial Developer are Copyright (C) 2018-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permission
	if (!permission_exists('domain_view')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//the domains view holds the parent child relationship between domains, it is defined in
//resources/database/views/domains.php and created by the schema update. when the view has
//not been created yet the list falls back to a flat list of domains.
	$domains_view_available = false;
	if ($database->select("select domain_uuid from view_domains limit 1 ", null, 'column') !== false) {
		$domains_view_available = true;
	}

//a user without domain_all is limited to their own domain and the domains below it in the
//parent child tree. the same restriction is used by the list, the count and the actions.
	$user_domain_uuid = $_SESSION['user']['domain_uuid'] ?? '';
	$restrict_domains = !permission_exists('domain_all') && is_uuid($user_domain_uuid);

//build the domain restrictions used by the count and the list queries
	$sql_restrict = '';
	if ($restrict_domains) {
		if ($domains_view_available) {
			//a domain is included when the user's domain appears anywhere in its path,
			//this gives the user's own domain and every domain below it
			$sql_restrict = "domain_uuid in (select domain_uuid from view_domains where :user_domain_uuid = any(domain_uuid_path)) ";
			$restrict_parameters['user_domain_uuid'] = $user_domain_uuid;
		}
		else {
			$sql_restrict = "domain_uuid = :user_domain_uuid ";
			$restrict_parameters['user_domain_uuid'] = $user_domain_uuid;
		}
	}

//get the http post data
	if (!empty($_POST['domains']) && is_array($_POST['domains'])) {
		$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
		$domains = $_POST['domains'];
	}

// Set variables from http GET parameters
	$page = is_numeric($_GET['page'] ?? '') ? $_GET['page'] : 0;
	$order_by = preg_replace('#[^a-zA-Z0-9_\-]#', '', is_string($_GET['order_by'] ?? null) ? $_GET['order_by'] : '');
	$order = ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc';
	$search = is_string($_GET['search'] ?? null) ? $_GET['search'] : '';
	$show = is_string($_GET['show'] ?? null) ? $_GET['show'] : '';

// Build the query string
	$url_params = [];
	if (!empty($page)) {
		$url_params['page'] = $page;
	}
	if (!empty($_GET['order_by'])) {
		$url_params['order_by'] = $order_by;
	}
	if (!empty($_GET['order'])) {
		$url_params['order'] = $order;
	}
	if (!empty($search)) {
		$url_params['search'] = $search;
	}
	if (!empty($show) && $show == 'all' && permission_exists('domain_all')) {
		$url_params['show'] = $show;
	}
	$query_string = http_build_query($url_params);

//get the domain uuids the user is allowed to manage, used to validate posted records
	$allowed_domain_uuids = [];
	if ($restrict_domains) {
		if ($domains_view_available) {
			$sql = "select domain_uuid from view_domains ";
			$sql .= "where :user_domain_uuid = any(domain_uuid_path) ";
			$result = $database->select($sql, ['user_domain_uuid' => $user_domain_uuid], 'all');
			if (is_array($result)) {
				$allowed_domain_uuids = array_column($result, 'domain_uuid');
			}
			unset($sql, $result);
		}
		else {
			$allowed_domain_uuids[] = $user_domain_uuid;
		}
	}

//change the domain
	if (!empty($_GET["domain_uuid"]) && is_uuid($_GET["domain_uuid"]) && !empty($_GET["domain_change"]) && $_GET["domain_change"] == "true" && permission_exists('domain_select')) {

		//the requested domain must be in the user's own tree unless access to all domains is granted
			if (permission_exists('domain_all') || in_array($_GET["domain_uuid"], $allowed_domain_uuids, true)) {

				//get the domain details from the database to prove the domain exists
					$sql = "select * from v_domains ";
					$sql .= "where domain_uuid = :domain_uuid ";
					$parameters['domain_uuid'] = $_GET["domain_uuid"];
					$row = $database->select($sql, $parameters, 'row');
					unset($sql, $parameters);

				//update the domain session variables with values proven to exist in v_domains
					if (is_array($row) && @sizeof($row) != 0) {
						$domain_uuid = $_GET["domain_uuid"];
						$_SESSION['domains'][$domain_uuid] = $row;
						$_SESSION["previous_domain_uuid"] = $_SESSION['domain_uuid'];
						$_SESSION['domain_uuid'] = $domain_uuid;
						$_SESSION["domain_name"] = $row['domain_name'];
						$_SESSION["context"] = $row['domain_name'];

						//clear the extension array so that it is regenerated for the selected domain
							unset($_SESSION['extension_array']);

						//set the setting arrays
							$domain = new domains();
							$domain->set();

						//initialize the settings object
							$settings = new settings(['database' => $database, 'domain_uuid' => $domain_uuid, 'user_uuid' => $_SESSION['user_uuid'] ?? '']);

						//reload domain on domain change, if enabled
							if ($settings->get('menu', 'domain_change_reload', false)) {
								//unset the session menu array
									unset($_SESSION['menu']['array']);

								//get the menu array and save it to the session
									$menu = new menu;
									$menu->menu_uuid = $settings->get('domain', 'menu');
									$_SESSION['menu']['array'] = $menu->menu_array();
									unset($menu);
							}

						//redirect the user
							if (!empty($_SESSION["login"]["destination"])) {
								// to default, or domain specific, login destination
								header("Location: ".PROJECT_PATH.$settings->get('login', 'destination'));
							}
							else {
								header("Location: ".PROJECT_PATH."/core/dashboard/");
							}
							exit;
					}
			}

		//the domain was not found or is not in the user's tree
			die("invalid domain");
	}

//process the http post data by action
	if (!empty($action) && is_array($domains) && @sizeof($domains) != 0) {

		//limit the posted records to the domains the user is allowed to manage
			if ($restrict_domains) {
				foreach ($domains as $i => $row) {
					if (!in_array($row['uuid'] ?? '', $allowed_domain_uuids, true)) {
						unset($domains[$i]);
					}
				}
				$domains = array_values($domains);
			}

		//process the action on the allowed records
			if (@sizeof($domains) != 0) {
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
			}

		header('Location: domains.php'.($query_string ? '?'.$query_string : ''));
		exit;
	}

//set from session variables
	$list_row_edit_button = $settings->get('theme', 'list_row_edit_button', false);

//build the search and restriction conditions
	$sql_conditions = [];
	$parameters = $restrict_parameters ?? [];
	if (!empty($search)) {
		$sql_conditions[] = "( \n";
		$sql_conditions[] = "	lower(domain_name) like :search \n";
		$sql_conditions[] = "	or lower(domain_description) like :search \n";
		$sql_conditions[] = ") ";
		$parameters['search'] = '%'.lower_case($search).'%';
	}
	if (!empty($sql_restrict)) {
		$sql_conditions[] = $sql_restrict;
	}

//get the count
	$sql = "select count(domain_uuid) from v_domains ";
	if (!empty($sql_conditions)) {
		$sql .= "where " . implode(" and ", $sql_conditions);
	}
	$num_rows = (int) $database->select($sql, $parameters ?? null, 'column');

//prepare to page the results
	$rows_per_page = $settings->get('domain', 'paging', 50);
	list($paging_controls, $rows_per_page) = paging($num_rows, $query_string, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $query_string, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	if ($domains_view_available) {
		//the view provides the level and path used to indent child domains under their parent
		$sql = "select level, domain_uuid, domain_parent_uuid, domain_name, parent_domain, cast(domain_enabled as text) as domain_enabled, domain_description ";
		$sql .= "from view_domains \n";
	}
	else {
		$sql = "select 1 as level, domain_uuid, domain_parent_uuid, domain_name, null as parent_domain, cast(domain_enabled as text) as domain_enabled, domain_description ";
		$sql .= "from v_domains \n";
	}
	if (!empty($sql_conditions)) {
		$sql .= "where " . implode(" and ", $sql_conditions);
	}
	if ($domains_view_available && empty($order_by)) {
		//order by the path so that each child domain follows its parent in the list
		$sql .= "order by domain_name_path asc \n";
	}
	else {
		$sql .= order_by($order_by, $order, 'domain_name', 'asc');
	}
	$sql .= limit_offset($rows_per_page, $offset);
	$domains = $database->select($sql, $parameters ?? null, 'all');
	unset($sql, $parameters, $sql_conditions, $restrict_parameters, $sql_restrict);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	$document['title'] = $text['title-domains'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-domains']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('domain_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','link'=>'domain_edit.php']);
	}
	if (permission_exists('domain_edit') && $domains) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_display_selected('modal-toggle'); modal_open('modal-toggle','btn_toggle');"]);
	}
 	if (permission_exists('domain_delete') && $domains) {
 		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_display_selected('modal-delete'); modal_open('modal-delete','btn_delete_domain');"]);
 	}
	echo "		<form id='form_search' class='inline' method='get'>\n";
	foreach ($url_params as $key => $value) {
		if (in_array($key, ['order_by', 'order', 'show'])) {
			echo "		<input type='hidden' name='".escape($key)."' value='".escape($value)."'>\n";
		}
	}
	echo "		<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$settings->get('theme', 'button_icon_search'),'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$settings->get('theme', 'button_icon_reset'),'type'=>'button','id'=>'btn_reset','link'=>'domains.php','style'=>($search == '' ? 'display: none;' : null)]);
	if (!empty($paging_controls_mini)) {
		echo "	<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('domain_edit') && !empty($domains)) {
		echo modal::create(['id'=>'modal-toggle','type'=>'general','message'=>$text['confirm-toggle_domains'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
 	if (permission_exists('domain_delete') && !empty($domains)) {
		echo modal::create(['id'=>'modal-delete','type'=>'general','message'=>$text['confirm-delete_domains'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
 	}

	echo $text['description-domains']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('domain_edit') || permission_exists('domain_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($domains) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == 'all' && permission_exists('domain_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, null, null, $query_string);
	}
	echo th_order_by('domain_name', $text['label-domain_name'], $order_by, $order, null, null, $query_string);
	if ($domains_view_available) {
		echo "	<th class='hide-sm-dn'>".$text['label-parent_domain']."</th>\n";
	}
	echo "<th class='center'>".$text['label-tools']."</th>";
	echo th_order_by('domain_enabled', $text['label-domain_enabled'], $order_by, $order, null, "class='center'", $query_string);
	echo "	<th class='hide-sm-dn'>".$text['label-domain_description']."</th>\n";
	if (permission_exists('domain_edit') && $list_row_edit_button) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($domains)) {
		$x = 0;
		foreach ($domains as $row) {
			$list_row_url = '';
			if (permission_exists('domain_edit')) {
				$list_row_url = "domain_edit.php?id=".urlencode($row['domain_uuid']).($query_string ? '&'.$query_string : '');
			}

			//indent child domains under their parent domain
				$indent = !empty($row['level']) && $num_rows > 1 ? str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", (int) $row['level'] - 1) : '';

			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('domain_edit') || permission_exists('domain_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='domains[$x][checked]' id='checkbox_".$x."' value='true' data-item-name='".escape($row['domain_name'])."' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='domains[$x][uuid]' value='".escape($row['domain_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == 'all' && permission_exists('domain_all')) {
				echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'] ?? '')."</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('domain_edit')) {
				echo "	".$indent."<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['domain_name'])."</a>\n";
			}
			else {
				echo "	".$indent.escape($row['domain_name']);
			}
			echo "	</td>\n";
			if ($domains_view_available) {
				echo "	<td class='hide-sm-dn'>".escape(!empty($row['parent_domain']) ? $row['parent_domain'] : '')."</td>\n";
			}
			echo "	<td class='no-link center'>\n";
			if (permission_exists('domain_select') && (permission_exists('domain_all') || in_array($row['domain_uuid'], $allowed_domain_uuids, true))) {
				echo "		<a href='".PROJECT_PATH."/core/domains/domains.php?domain_uuid=".urlencode($row['domain_uuid'])."&domain_change=true'>".$text['label-manage']."</a>";
			}
			if (permission_exists('domain_setting_view')) {
				$list_setting_url = PROJECT_PATH."/core/domain_settings/domain_settings.php?id=".urlencode($row['domain_uuid']);
				echo "&nbsp;&nbsp; <a href='".$list_setting_url."'\">".$text['button-settings'];
			}
			echo "	</td>\n";
			if (permission_exists('domain_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'button','class'=>'link','label'=>$text['label-'.$row['domain_enabled']],'title'=>$text['button-toggle'],'id'=>'btn_toggle_enabled','name'=>'btn_toggle_enabled','onclick'=>"list_self_check('checkbox_".$x."'); modal_open('modal-toggle_enabled','btn_toggle_enabled');"]);
				echo modal::create(['id'=>'modal-toggle_enabled','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle_enabled','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
				echo "	</td>\n";
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['domain_enabled']];
				echo "	</td>\n";
			}
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['domain_description'])."</td>\n";
			if (permission_exists('domain_edit') && $list_row_edit_button) {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$settings->get('theme', 'button_icon_edit'),'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($domains);
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