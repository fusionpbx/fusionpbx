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
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('call_center_agent_view')) {
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
	if (is_array($_POST['call_center_agents'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$call_center_agents = $_POST['call_center_agents'];
	}

//process the http post data by action
	if ($action != '' && is_array($call_center_agents) && @sizeof($call_center_agents) != 0) {
		switch ($action) {
			case 'delete':
				if (permission_exists('call_center_agent_delete')) {
					$obj = new call_center;
					$obj->delete_agents($call_center_agents);
				}
				break;
		}

		header('Location: call_center_agents.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get http variables and set them to php variables
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = " (";
		$sql_search .= "lower(agent_name) like :search ";
		$sql_search .= "or lower(agent_id) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//get total call center agent count from the database
	$sql = "select count(*) from v_call_center_agents ";
	$sql .= "where domain_uuid = :domain_uuid ";
	if (isset($sql_search)) {
		$sql .= "and ".$sql_search;
	}
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
	$sql .= order_by($order_by, $order, 'agent_name', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//includes and title
	$document['title'] = $text['title-call_center_agents'];
	require_once "resources/header.php";

//show content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['header-call_center_agents']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'call_center_queues.php','style'=>'margin-right: 15px;']);
	if (permission_exists('call_center_imports')) {
		echo button::create(['type'=>'button','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'link'=>PROJECT_PATH.'/app/call_center_imports/call_center_imports.php?type=call_center_agents']);
	}
	if ($num_rows) {
		echo button::create(['type'=>'button','label'=>$text['button-status'],'icon'=>'user-clock','style'=>'margin-right: 15px;','link'=>'call_center_agent_status.php']);
	}
	if (permission_exists('call_center_agent_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'call_center_agent_edit.php']);
	}
	if (permission_exists('call_center_agent_delete') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'call_center_agents.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('call_center_agent_delete') && $result) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-call_center_agents']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('call_center_agent_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($result ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	//echo th_order_by('domain_uuid', 'domain_uuid', $order_by, $order);
	echo th_order_by('agent_name', $text['label-agent_name'], $order_by, $order);
	echo th_order_by('agent_id', $text['label-agent_id'], $order_by, $order);
	echo th_order_by('agent_type', $text['label-type'], $order_by, $order);
	echo th_order_by('agent_call_timeout', $text['label-call_timeout'], $order_by, $order);
	echo th_order_by('agent_contact', $text['label-contact'], $order_by, $order);
	echo th_order_by('agent_max_no_answer', $text['label-max_no_answer'], $order_by, $order);
	echo th_order_by('agent_status', $text['label-default_status'], $order_by, $order);
	//echo th_order_by('agent_wrap_up_time', $text['label-wrap_up_time'], $order_by, $order);
	//echo th_order_by('agent_reject_delay_time', $text['label-reject_delay_time'], $order_by, $order);
	//echo th_order_by('agent_busy_delay_time', $text['label-busy_delay_time'], $order_by, $order);
	if (permission_exists('call_center_agent_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($result)) {
		$x = 0;
		foreach($result as $row) {
			if (permission_exists('call_center_agent_edit')) {
				$list_row_url = "call_center_agent_edit.php?id=".urlencode($row['call_center_agent_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('call_center_agent_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='call_center_agents[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='call_center_agents[$x][uuid]' value='".escape($row['call_center_agent_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>";
			if (permission_exists('call_center_agent_edit')) {
				echo "<a href='call_center_agent_edit.php?id=".escape($row['call_center_agent_uuid'])."'>".escape($row['agent_name'])."</a>";
			}
			else {
				echo escape($row['agent_name']);
			}
			echo "	</td>\n";
			echo "	<td>".escape($row['agent_id'])."</td>\n";
			echo "	<td>".escape($row['agent_type'])."</td>\n";
			echo "	<td>".escape($row['agent_call_timeout'])."</td>\n";
			$agent_contact = $row['agent_contact'];
			// parse out gateway uuid
			$bridge_statement = explode('/', $row['agent_contact']);
			if ($bridge_statement[0] == 'sofia' && $bridge_statement[1] == 'gateway' && is_uuid($bridge_statement[2])) {
				// retrieve gateway name from db
				$sql = "select gateway from v_gateways ";
				$sql .= "where gateway_uuid = :gateway_uuid ";
				$parameters['gateway_uuid'] = $bridge_statement[2];
				$database = new database;
				$result = $database->select($sql, $parameters, 'all');
				if (count($result) > 0) {
					$gateway_name = $result[0]['gateway'];
					$agent_contact = str_replace($bridge_statement[2], $gateway_name, $agent_contact);
				}
				unset($sql, $parameters, $bridge_statement);
			}
			echo "	<td>".escape($agent_contact)."</td>\n";
			echo "	<td>".escape($row['agent_max_no_answer'])."</td>\n";
			echo "	<td>".escape($row['agent_status'])."</td>\n";
			//echo "	<td>".$row[agent_wrap_up_time]."</td>\n";
			//echo "	<td>".$row[agent_reject_delay_time]."</td>\n";
			//echo "	<td>".$row[agent_busy_delay_time]."</td>\n";
			if (permission_exists('call_center_agent_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($result);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>\n";

//show the footer
	require_once "resources/footer.php";

?>
