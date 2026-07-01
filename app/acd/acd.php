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
	Portions created by the Initial Developer are Copyright (C) 2010-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	BlueCloud <support@blueuc.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('acd_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set additional variables
	$show = $_GET["show"] ?? '';

//set the defaults
	$search = '';

//get posted data
	if (!empty($_POST['acd_queues'])) {
		$action  = $_POST['action'];
		$search  = $_POST['search'];
		$queues  = $_POST['acd_queues'];
	}

//process the http post data by action
	if (!empty($action) && !empty($queues)) {
		switch ($action) {
			case 'copy':
				$obj = new acd;
				$obj->copy($queues);
				break;
			case 'toggle':
				$obj = new acd;
				$obj->toggle($queues);
				break;
			case 'delete':
				$obj = new acd;
				$obj->delete($queues);
				break;
		}

		header('Location: acd.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by
	$order_by = $_GET["order_by"] ?? 'queue_name';
	$order    = $_GET["order"]    ?? 'asc';

//add the search term
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get total domain queue count
	$sql = "select count(*) from v_acd_queues ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$total_queues = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//get filtered queue count
	if ($show == "all" && permission_exists('acd_all')) {
		$sql = "select count(*) from v_acd_queues ";
		$sql .= "where true ";
	}
	else {
		$sql = "select count(*) from v_acd_queues ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= "lower(queue_name) like :search ";
		$sql .= "or lower(queue_extension) like :search ";
		$sql .= "or lower(queue_description) like :search ";
		$sql .= "or lower(queue_enabled) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = $settings->get('domain', 'paging', 50);
	$param = $search ? "&search=".$search : null;
	if ($show == "all" && permission_exists('acd_all')) {
		$param = "&show=all";
	}
	$page = isset($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page)      = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	if ($show == "all" && permission_exists('acd_all')) {
		$sql = "select q.queue_uuid, q.queue_name, q.queue_extension, q.queue_hold_music, q.queue_timeout, q.queue_enabled, q.queue_description, ";
		$sql .= "(SELECT COUNT(*) FROM v_acd_queue_members m WHERE m.queue_uuid = q.queue_uuid AND m.queue_member_enabled = 'true') AS member_count ";
		$sql .= "from v_acd_queues q ";
		$sql .= "where true ";
	}
	else {
		$sql = "select q.queue_uuid, q.queue_name, q.queue_extension, q.queue_hold_music, q.queue_timeout, q.queue_enabled, q.queue_description, ";
		$sql .= "(SELECT COUNT(*) FROM v_acd_queue_members m WHERE m.queue_uuid = q.queue_uuid AND m.queue_member_enabled = 'true') AS member_count ";
		$sql .= "from v_acd_queues q ";
		$sql .= "where q.domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (!empty($search)) {
		$sql .= "and (";
		$sql .= "lower(q.queue_name) like :search ";
		$sql .= "or lower(q.queue_extension) like :search ";
		$sql .= "or lower(q.queue_description) like :search ";
		$sql .= "or lower(q.queue_enabled) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order);
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$acd_queues = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token  = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-acd'] ?? 'Advanced Call Distribution';
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".($text['title-acd'] ?? 'Advanced Call Distribution')."</b><div class='count'>".number_format($num_rows)."</div></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-active'] ?? 'Active Calls','icon'=>$settings->get('theme', 'button_icon_view') ?: 'eye','id'=>'btn_active','link'=>'acd_active.php']);
	if (permission_exists('acd_add') && (!isset($_SESSION['limit']['acd_queues']['numeric']) || ($total_queues < $_SESSION['limit']['acd_queues']['numeric']))) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$settings->get('theme', 'button_icon_add'),'id'=>'btn_add','link'=>'acd_edit.php']);
	}
	if (permission_exists('acd_add') && $acd_queues && (!isset($_SESSION['limit']['acd_queues']['numeric']) || ($total_queues < $_SESSION['limit']['acd_queues']['numeric']))) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$settings->get('theme', 'button_icon_copy'),'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	if (permission_exists('acd_edit') && $acd_queues) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$settings->get('theme', 'button_icon_toggle'),'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display: none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('acd_delete') && $acd_queues) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$settings->get('theme', 'button_icon_delete'),'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('acd_all')) {
		if ($show == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$settings->get('theme', 'button_icon_all'),'link'=>'?show=all']);
		}
	}
	echo "		<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".($text['label-search'] ?? 'Search')."\" onkeydown=''>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$settings->get('theme', 'button_icon_search'),'type'=>'submit','id'=>'btn_search']);
	if ($paging_controls_mini != '') {
		echo "	<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('acd_add') && $acd_queues && (!isset($_SESSION['limit']['acd_queues']['numeric']) || ($total_queues < $_SESSION['limit']['acd_queues']['numeric']))) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('acd_edit') && $acd_queues) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('acd_delete') && $acd_queues) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('acd_add') || permission_exists('acd_edit') || permission_exists('acd_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(!empty($acd_queues) ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($show == "all" && permission_exists('acd_all')) {
		echo th_order_by('domain_name', $text['label-domain'] ?? 'Domain', $order_by, $order);
	}
	echo th_order_by('queue_name',        $text['label-name']        ?? 'Name',        $order_by, $order);
	echo th_order_by('queue_extension',   $text['label-extension']   ?? 'Extension',   $order_by, $order);
	echo "	<th>".($text['label-members']    ?? 'Members')."</th>\n";
	echo th_order_by('queue_timeout',     $text['label-timeout']     ?? 'Timeout',     $order_by, $order);
	echo th_order_by('queue_enabled',     $text['label-enabled']     ?? 'Enabled',     $order_by, $order, null, "class='center'");
	echo th_order_by('queue_description', $text['header-description'] ?? 'Description', $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('acd_edit') && filter_var($_SESSION['theme']['list_row_edit_button']['boolean'] ?? false, FILTER_VALIDATE_BOOL)) {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($acd_queues) && @sizeof($acd_queues) != 0) {
		$x = 0;
		foreach ($acd_queues as $row) {
			$list_row_url = '';
			if (permission_exists('acd_edit')) {
				$list_row_url = "acd_edit.php?id=".urlencode($row['queue_uuid']);
				if (!empty($row['domain_uuid']) && $row['domain_uuid'] != $_SESSION['domain_uuid'] && permission_exists('domain_select')) {
					$list_row_url .= '&domain_uuid='.urlencode($row['domain_uuid']).'&domain_change=true';
				}
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('acd_add') || permission_exists('acd_edit') || permission_exists('acd_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='acd_queues[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='acd_queues[$x][uuid]' value='".escape($row['queue_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($show == "all" && permission_exists('acd_all')) {
				echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'] ?? '')."</td>\n";
			}
			echo "	<td>";
			if (permission_exists('acd_edit')) {
				echo "<a href='".$list_row_url."' title=\"".($text['button-edit'] ?? 'Edit')."\">".escape($row['queue_name'])."</a>";
			}
			else {
				echo escape($row['queue_name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['queue_extension'])."&nbsp;</td>\n";
			echo "	<td>".escape($row['member_count'])."&nbsp;</td>\n";
			echo "	<td>".escape($row['queue_timeout'])."&nbsp;</td>\n";
			if (permission_exists('acd_edit')) {
				echo "	<td class='no-link center'>";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['queue_enabled']] ?? escape($row['queue_enabled']),'title'=>$text['button-toggle'] ?? 'Toggle','onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>";
				echo escape($row['queue_enabled']);
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['queue_description'])."&nbsp;</td>\n";
			if (permission_exists('acd_edit') && filter_var($_SESSION['theme']['list_row_edit_button']['boolean'] ?? false, FILTER_VALIDATE_BOOL)) {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'] ?? 'Edit','icon'=>$settings->get('theme', 'button_icon_edit'),'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($acd_queues);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//how Advanced Call Distribution works (admin-facing overview)
	echo "<br />\n";
	echo "<div class='card'>\n";
	echo "	<b>".($text['header-how_it_works'] ?? 'How Advanced Call Distribution Works')."</b>\n";
	echo "	<ul style='margin: 8px 0 0 18px; padding: 0; line-height: 1.7;'>\n";
	echo "		<li>Each queue has its own <b>extension</b>. Send calls to it from inbound routes, IVRs, time conditions, or ring groups &mdash; it appears in destination lists as <b>Advanced Call Distribution</b>.</li>\n";
	echo "		<li>While a caller waits, they hear <b>hold music</b> and the system rings the <b>logged-in agents</b> in <b>tier order</b> (lower tiers first, then the next tier after the timeout).</li>\n";
	echo "		<li>Agents are reached on their <b>extension and follow-me</b>, including <b>Microsoft Teams</b>. The <b>first agent to answer</b> is connected and the rest stop ringing.</li>\n";
	echo "		<li>Whether a <b>busy agent</b> is skipped depends on each member's <b>Busy Handling</b> setting in the queue: <b>Always Ring</b> (offer the call even if they're already on one), <b>Never Ring (Any Call)</b> (skip while on any call &mdash; their extension or follow-me), or <b>Never Ring (Queue Calls Only)</b> (skip only while on another queue call).</li>\n";
	echo "		<li>Login/logout is <b>self-service</b> via the feature codes below, and is stored <b>per queue</b>, so changes take effect <b>immediately</b>.</li>\n";
	echo "		<li>Open <b>Active Calls</b> to see, in real time, who is <b>waiting</b>, who is <b>connected</b> (with talk time), and which agents are currently <b>ringing</b>.</li>\n";
	echo "	</ul>\n";
	echo "</div>\n";

//agent login / logout feature-code instructions
	echo "<br />\n";
	echo "<div class='card'>\n";
	echo "	<b>".($text['header-agent_codes'] ?? 'Agent Login / Logout')."</b>\n";
	echo "	<ul style='margin: 8px 0 0 18px; padding: 0; line-height: 1.7;'>\n";
	echo "		<li><b>*86</b> &mdash; ".($text['desc-agent_codes_all'] ?? 'log in or out of <b>all</b> queues you belong to. Each time you dial it, your status toggles.')."</li>\n";
	echo "		<li><b>*86&lt;extension&gt;</b> &mdash; ".($text['desc-agent_codes_one'] ?? 'log in or out of a <b>single</b> queue by its extension. For example, dial <b>*86771</b> to toggle queue <b>771</b>.')."</li>\n";
	echo "	</ul>\n";
	echo "	<div style='margin-top: 8px; color: #888; font-size: 12px;'>".($text['desc-agent_codes_note'] ?? 'You are identified by your extension automatically (including from Microsoft Teams). The system plays &ldquo;you are now logged in&rdquo; or &ldquo;you are now logged out&rdquo; to confirm.')."</div>\n";
	echo "</div>\n";

//include the footer
	require_once "resources/footer.php";

?>
