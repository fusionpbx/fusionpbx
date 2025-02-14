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
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('gateway_view')) {
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
	if (!empty($_POST['gateways'])) {
		$action = $_POST['action'] ?? '';
		$search = $_POST['search'] ?? '';
		$gateways = $_POST['gateways'] ?? '';
	}

//process the http post data by action
	if (!empty($action) && !empty($gateways)) {
		switch ($action) {
			case 'copy':
				if (permission_exists('gateway_add')) {
					$obj = new gateways;
					$obj->copy($gateways);
				}
				break;
			case 'toggle':
				if (permission_exists('gateway_edit')) {
					$obj = new gateways;
					$obj->toggle($gateways);
				}
				break;
			case 'delete':
				if (permission_exists('gateway_delete')) {
					$obj = new gateways;
					$obj->delete($gateways);
				}
			case 'start':
				$esl = event_socket::create();
				if ($esl && permission_exists('gateway_edit')) {
					$obj = new gateways;
					$obj->start($gateways);
				}
				break;
			case 'stop':
				$esl = event_socket::create();
				if ($esl && permission_exists('gateway_edit')) {
					$obj = new gateways;
					$obj->stop($gateways);
				}
				break;
		}

		header('Location: gateways.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//connect to event socket
	$esl = event_socket::create();

//gateway status function
	if (!function_exists('switch_gateway_status')) {
		function switch_gateway_status($gateway_uuid, $result_type = 'xml') {
			global $esl;
			if ($esl->is_connected()) {
				$esl = event_socket::create();
				$cmd = 'sofia xmlstatus gateway '.$gateway_uuid;
				$response = trim(event_socket::api($cmd));
				if ($response == "Invalid Gateway!") {
					$cmd = 'sofia xmlstatus gateway '.strtoupper($gateway_uuid);
					$response = trim(event_socket::api($cmd));
				}
				return $response;
			}
		}
	}

//get order and order by
	$order_by = $_GET["order_by"] ?? '';
	$order = $_GET["order"] ?? '';

//set additional variables
	$search = !empty($_GET["search"]) ? $_GET["search"] : '';
	$show = !empty($_GET["show"]) ? $_GET["show"] : '';

//set from session variables
	$list_row_edit_button = !empty($_SESSION['theme']['list_row_edit_button']['boolean']) ? $_SESSION['theme']['list_row_edit_button']['boolean'] : 'false';

//get total gateway count from the database
	$sql = "select count(*) from v_gateways where true ";
	if (!($show == "all" && permission_exists('gateway_all'))) {
		$sql .= "and (domain_uuid = :domain_uuid ".(permission_exists('gateway_domain') ? " or domain_uuid is null " : null).") ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!empty($search)) {
		$search = strtolower($_GET["search"]);
		$sql .= "and (";
		$sql .= "lower(gateway) like :search ";
		$sql .= "or lower(username) like :search ";
		$sql .= "or lower(auth_username) like :search ";
		$sql .= "or lower(from_user) like :search ";
		$sql .= "or lower(from_domain) like :search ";
		$sql .= "or lower(proxy) like :search ";
		$sql .= "or lower(register_proxy) like :search ";
		$sql .= "or lower(outbound_proxy) like :search ";
		$sql .= "or lower(description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$total_gateways = $database->select($sql, $parameters ?? '', 'column');
	$num_rows = $total_gateways;

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	$param .= $order_by ? "&order_by=".$order_by."&order=".$order : null;
	$page = !empty($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select * ";
	$sql .= "from v_gateways ";
	$sql .= "where true ";
	if (!($show == "all" && permission_exists('gateway_all'))) {
		$sql .= "and (domain_uuid = :domain_uuid ".(permission_exists('gateway_domain') ? " or domain_uuid is null " : null).") ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	}
	if (!empty($search)) {
		$search = strtolower($_GET["search"]);
		$sql .= "and (";
		$sql .= "lower(gateway) like :search ";
		$sql .= "or lower(username) like :search ";
		$sql .= "or lower(auth_username) like :search ";
		$sql .= "or lower(from_user) like :search ";
		$sql .= "or lower(from_domain) like :search ";
		$sql .= "or lower(proxy) like :search ";
		$sql .= "or lower(register_proxy) like :search ";
		$sql .= "or lower(outbound_proxy) like :search ";
		$sql .= "or lower(description) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'gateway', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$gateways = $database->select($sql, $parameters ?? '', 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-gateways'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-gateways']."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('gateway_edit') && $gateways) {
		echo button::create(['type'=>'button','label'=>$text['button-stop'],'icon'=>$_SESSION['theme']['button_icon_stop'],'onclick'=>"modal_open('modal-stop','btn_stop');"]);
		echo button::create(['type'=>'button','label'=>$text['button-start'],'icon'=>$_SESSION['theme']['button_icon_start'],'onclick'=>"modal_open('modal-start','btn_start');"]);
	}
	echo button::create(['type'=>'button','label'=>$text['button-refresh'],'icon'=>$_SESSION['theme']['button_icon_refresh'],'style'=>'margin-right: 15px;','link'=>'gateways.php']);
	if (permission_exists('gateway_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'gateway_edit.php']);
	}
	if (permission_exists('gateway_add') && $gateways) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('gateway_edit') && $gateways) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('gateway_delete') && $gateways) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('gateway_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	//echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'gateways.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('gateway_edit') && $gateways) {
		echo modal::create(['id'=>'modal-stop','type'=>'general','message'=>$text['confirm-stop_gateways'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_stop','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('stop'); list_form_submit('form_list');"])]);
		echo modal::create(['id'=>'modal-start','type'=>'general','message'=>$text['confirm-start_gateways'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_start','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('start'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('gateway_add') && $gateways) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('gateway_edit') && $gateways) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('gateway_delete') && $gateways) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-gateway']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('gateway_add') || permission_exists('gateway_edit') || permission_exists('gateway_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($gateways) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == "all" && permission_exists('gateway_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, $param);
	}
	echo th_order_by('gateway', $text['label-gateway'], $order_by, $order);
	echo "<th class='hide-sm-dn'>".$text['label-proxy']."</th>\n";
	echo th_order_by('context', $text['label-context'], $order_by, $order);
	echo th_order_by('register', $text['label-register'], $order_by, $order);
	if ($esl->is_connected()) {
		echo "<th class='hide-sm-dn'>".$text['label-status']."</th>\n";
		if (permission_exists('gateway_edit')) {
			echo "<th class='center'>".$text['label-action']."</th>\n";
		}
		echo "<th>".$text['label-state']."</th>\n";
	}
	echo th_order_by('hostname', $text['label-hostname'], $order_by, $order, null, "class='hide-sm-dn'");
	echo th_order_by('enabled', $text['label-enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('description', $text['label-description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('gateway_edit') && $list_row_edit_button == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (!empty($gateways)) {
		$x = 0;
		foreach($gateways as $row) {
			if (permission_exists('gateway_edit')) {
				$list_row_url = "gateway_edit.php?id=".urlencode($row['gateway_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('gateway_add') || permission_exists('gateway_edit') || permission_exists('gateway_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='gateways[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='gateways[$x][uuid]' value='".escape($row['gateway_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == "all" && permission_exists('gateway_all')) {
				echo "	<td>";
				if (is_uuid($row['domain_uuid'])) {
					echo escape($_SESSION['domains'][$row['domain_uuid']]['domain_name']);
				}
				else {
					echo $text['label-global'];
				}
				echo "</td>\n";
			}
			echo "	<td>";
			if (permission_exists('gateway_edit')) {
				echo "<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['gateway'])."</a>";
			}
			else {
				echo escape($row['gateway']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row["proxy"])."</td>\n";
			echo "	<td>".escape($row["context"])."</td>\n";
			echo "	<td>".ucwords(escape($row["register"]))."</td>\n";
			if ($esl->is_connected()) {
				if ($row["enabled"] == "true") {
					$response = switch_gateway_status($row["gateway_uuid"]);
					if ($response == "Invalid Gateway!") {
						//not running
						echo "	<td class='hide-sm-dn'>".$text['label-status-stopped']."</td>\n";
						if (permission_exists('gateway_edit')) {
							echo "	<td class='no-link center'>";
							echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-action-start'],'title'=>$text['button-start'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('start'); list_form_submit('form_list')"]);
							echo "	</td>\n";
						}
						echo "	<td>&nbsp;</td>\n";
					}
					else {
						//running
						try {
							$xml = new SimpleXMLElement($response);
							$state = $xml->state;
							echo "	<td class='hide-sm-dn'>".$text['label-status-running']."</td>\n";
							if (permission_exists('gateway_edit')) {
								echo "	<td class='no-link center'>";
								echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-action-stop'],'title'=>$text['button-stop'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('stop'); list_form_submit('form_list')"]);
								echo "	</td>\n";
							}
							echo "	<td>".escape($state)."</td>\n"; //REGED, NOREG, UNREGED
						}
						catch (Exception $e) {
								//echo $e->getMessage();
						}
					}
				}
				else {
					echo "	<td class='hide-sm-dn'>&nbsp;</td>\n";
					if (permission_exists('gateway_edit')) {
						echo "	<td>&nbsp;</td>\n";
					}
					echo "	<td>&nbsp;</td>\n";
				}
			}
			echo "	<td class='hide-sm-dn'>".escape($row["hostname"])."</td>\n";
			if (permission_exists('gateway_edit')) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo $text['label-'.$row['enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row["description"])."&nbsp;</td>\n";
			if (permission_exists('gateway_edit') && $list_row_edit_button == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$list_row_edit_button,'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
	}
	unset($gateways);

	echo "</table>\n";
	echo "</div>\n";
	echo "<br /><br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
