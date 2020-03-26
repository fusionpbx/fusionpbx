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
	Portions created by the Initial Developer are Copyright (C) 2016-2020
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
	if (permission_exists('message_view')) {
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
	if (is_array($_POST['messages'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$messages = $_POST['messages'];
	}

//process the http post data by action
	if ($action != '' && is_array($messages) && @sizeof($messages) != 0) {
		switch ($action) {
			case 'delete':
				if (permission_exists('message_delete')) {
					$obj = new messages;
					$obj->delete($messages);
				}
				break;
		}

		header('Location: messages_log.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get variables used to control the order
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = " (";
		$sql_search .= "lower(message_type) like :search ";
		$sql_search .= "or lower(message_direction) like :search ";
		$sql_search .= "or lower(message_from) like :search ";
		$sql_search .= "or lower(message_to) like :search ";
		$sql_search .= "or lower(message_text) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(*) from v_messages ";
	if ($_GET['show'] == "all" && permission_exists('message_all')) {
		if (isset($sql_search)) {
			$sql .= "where ".$sql_search;
		}
	}
	else {
		$sql .= "where user_uuid = :user_uuid ";
		$sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
		if (isset($sql_search)) {
			$sql .= "and ".$sql_search;
		}
		$parameters['user_uuid'] = $_SESSION['user_uuid'];
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "&search=".$search;
	if ($_GET['show'] == "all" && permission_exists('message_all')) {
		$param .= "&show=all";
	}
	if (isset($_GET['page'])) {
		$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
		$offset = $rows_per_page * $page;
	}

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= "order by message_date desc ";
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$messages = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include header
	$document['title'] = $text['title-message_log'];
	require_once "resources/header.php";

//define the checkbox_toggle function
	echo "<script type=\"text/javascript\">\n";
	echo "	function checkbox_toggle(item) {\n";
	echo "		var inputs = document.getElementsByTagName(\"input\");\n";
	echo "		for (var i = 0, max = inputs.length; i < max; i++) {\n";
	echo "		    if (inputs[i].type === 'checkbox') {\n";
	echo "		       	if (document.getElementById('checkbox_all').checked == true) {\n";
	echo "				inputs[i].checked = true;\n";
	echo "			}\n";
	echo "				else {\n";
	echo "					inputs[i].checked = false;\n";
	echo "				}\n";
	echo "			}\n";
	echo "		}\n";
	echo "	}\n";
	echo "</script>\n";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-message_log']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'messages.php']);
	if (permission_exists('message_delete') && $messages) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('message_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'messages_log.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('message_delete') && $messages) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('message_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($messages ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('message_type', $text['label-message_type'], $order_by, $order);
	echo th_order_by('message_direction', $text['label-message_direction'], $order_by, $order);
	echo th_order_by('message_date', $text['label-message_date'], $order_by, $order);
	echo th_order_by('message_from', $text['label-message_from'], $order_by, $order);
	echo th_order_by('message_to', $text['label-message_to'], $order_by, $order);
	echo th_order_by('message_text', $text['label-message_text'], $order_by, $order, null, "class='pct-20 hide-xs'");
	if (permission_exists('message_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($messages) && @sizeof($messages) != 0) {
		$x = 0;
		foreach ($messages as $row) {
			if (permission_exists('message_edit')) {
				$list_row_url = "message_edit.php?id=".urlencode($row['message_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('message_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='messages[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='messages[$x][uuid]' value='".escape($row['message_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>";
			switch ($row['message_type']) {
				case 'sms': echo $text['label-sms']; break;
				case 'mms': echo $text['label-mms']; break;
				case 'chat': echo $text['label-chat']; break;
			}
			echo "	</td>\n";
			echo "	<td>";
			switch ($row['message_direction']) {
				case "inbound": echo $text['label-inbound']; break;
				case "outbound": echo $text['label-outbound']; break;
			}
			echo "	</td>\n";
			echo "	<td>";
			$message_date = explode(' ', $row['message_date']);
			$message_date = escape($message_date[0])." <span class='hide-sm-dn'>".$message_date[1]."</span>";
			if (permission_exists('message_edit')) {
				echo "<a href='".$list_row_url."'>".$message_date."</a>";
			}
			else {
				echo $message_date;
			}
			echo "	</td>\n";
			echo "	<td>".escape(format_phone($row['message_from']))."&nbsp;</td>\n";
			echo "	<td>".escape(format_phone($row['message_to']))."&nbsp;</td>\n";
			echo "	<td class='description overflow hide-xs'>".escape($row['message_text'])."&nbsp;</td>\n";
			if (permission_exists('message_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($messages);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>