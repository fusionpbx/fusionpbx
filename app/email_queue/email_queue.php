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
	Portions created by the Initial Developer are Copyright (C) 2022-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('email_queue_view')) {
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
	if (!empty($_POST['email_queue']) && is_array($_POST['email_queue'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$email_queue = $_POST['email_queue'];
	}

//process the http post data by action
	if (!empty($action) && !empty($email_queue) && is_array($email_queue) && @sizeof($email_queue) != 0) {

		//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: email_queue.php');
			exit;
		}

		//prepare the array
		$x = 0;
		foreach ($email_queue as $row) {
			//email class queue uuid
			$array[$x]['checked'] = $row['checked'] ?? null;
			$array[$x]['uuid'] = $row['email_queue_uuid']; 

			// database class uuid
			//$array['email_queue'][$x]['checked'] = $row['checked'];
			//$array['email_queue'][$x]['email_queue_uuid'] = $row['email_queue_uuid'];
			$x++;
		}

		//prepare the database object
		$database = new database;
		$database->app_name = 'email_queue';
		$database->app_uuid = '5befdf60-a242-445f-91b3-2e9ee3e0ddf7';

		//send the array to the database class
		switch ($action) {
			case 'copy':
				//if (permission_exists('email_queue_add')) {
				//	$database->copy($array);
				//}
				break;
			case 'toggle':
				//if (permission_exists('email_queue_edit')) {
				//	$database->toggle($array);
				//}
				break;
			case 'delete':
				if (permission_exists('email_queue_delete')) {
					$obj = new email_queue;
					$obj->delete($array);
					//$database->delete($array);
				}
				break;
		}

		//redirect the user
		header('Location: email_queue.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//set the time zone
	if (isset($_SESSION['domain']['time_zone']['name'])) {
		$time_zone = $_SESSION['domain']['time_zone']['name'];
	}
	else {
		$time_zone = date_default_timezone_get();
	}

//get order and order by
	$order_by = $_GET["order_by"] ?? null;
	$order = $_GET["order"] ?? null;

//add the search
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get the count
	$sql = "select count(email_queue_uuid) ";
	$sql .= "from v_email_queue ";
	$sql .= "where true ";
	if (isset($search)) {
		$sql .= "and (";
		$sql .= "	lower(email_from) like :search ";
		$sql .= "	or lower(email_to) like :search ";
		$sql .= "	or lower(email_subject) like :search ";
		$sql .= "	or lower(email_body) like :search ";
		$sql .= "	or lower(email_status) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	if (isset($_GET["email_status"]) && $_GET["email_status"] != '') {
		$sql .= "and email_status = :email_status ";
		$parameters['email_status'] = $_GET["email_status"];
	}
	//else {
	//	$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
	//	$parameters['domain_uuid'] = $domain_uuid;
	//}
	$database = new database;
	$num_rows = $database->select($sql, $parameters ?? null, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = !empty($_GET["email_status"]) ? "&email_status=".urlencode($_GET["email_status"]) : null;
	$param .= !empty($search) ? "&search=".urlencode($search) : null;
	$param .= !empty($_REQUEST['show']) && $_REQUEST['show'] == 'all' && permission_exists('email_queue_all') ? "&show=all" : null;
	$page = !empty($_REQUEST['page']) && is_numeric($_REQUEST['page']) ? $_REQUEST['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select ";
	$sql .= "email_date, ";
	$sql .= "to_char(timezone(:time_zone, email_date), 'DD Mon YYYY') as email_date_formatted, \n";
	$sql .= "to_char(timezone(:time_zone, email_date), 'HH12:MI:SS am') as email_time_formatted, \n";	
	$sql .= "email_queue_uuid, ";
	$sql .= "hostname, ";
	$sql .= "email_from, ";
	$sql .= "email_to, ";
	$sql .= "email_subject, ";
	$sql .= "substring(email_body, 0, 80) as email_body, ";
	//$sql .= "email_action_before, ";
	$sql .= "email_action_after, ";
	$sql .= "email_status, ";
	$sql .= "email_retry_count ";
	$sql .= "from v_email_queue ";
	$sql .= "where true ";
	if (isset($search)) {
		$sql .= "and (";
		$sql .= "	lower(email_from) like :search ";
		$sql .= "	or lower(email_to) like :search ";
		$sql .= "	or lower(email_subject) like :search ";
		$sql .= "	or lower(email_body) like :search ";
		$sql .= "	or lower(email_status) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	if (isset($_GET["email_status"]) && $_GET["email_status"] != '') {
		$sql .= "and email_status = :email_status ";
		$parameters['email_status'] = $_GET["email_status"];
	}
	$sql .= order_by($order_by, $order, 'email_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$parameters['time_zone'] = $time_zone;
	$database = new database;
	$email_queue = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create('/app/email_queue/email_queue.php');

//additional includes
	$document['title'] = $text['title-email_queue'];
	require_once "resources/header.php";

//test result layer
	echo "<style>\n";
	echo "	#test_result_layer {\n";
	echo "		z-index: 999999;\n";
	echo "		position: absolute;\n";
	echo "		left: 0px;\n";
	echo "		top: 0px;\n";
	echo "		right: 0px;\n";
	echo "		bottom: 0px;\n";
	echo "		text-align: center;\n";
	echo "		vertical-align: middle;\n";
	echo "		}\n";
	echo "	#test_result_container {\n";
	echo "		display: block;\n";
	echo "		overflow: auto;\n";
	echo "		background-color: #fff;\n";
	echo "		padding: 25px 25px;\n";
	if (http_user_agent('mobile')) {
		echo "	margin: 0;\n";
	}
	else {
		echo "	margin: auto 10%;\n";
	}
	echo "		text-align: left;\n";
	echo "		-webkit-box-shadow: 0px 1px 20px #888;\n";
	echo "		-moz-box-shadow: 0px 1px 20px #888;\n";
	echo "		box-shadow: 0px 1px 20px #888;\n";
	echo "		}\n";
	echo "</style>\n";

	echo "<div id='test_result_layer' style='display: none;'>\n";
	echo "	<table cellpadding='0' cellspacing='0' border='0' width='100%' height='100%'>\n";
	echo "		<tr>\n";
	echo "			<td align='center' valign='middle'>\n";
	echo "				<span id='test_result_container'></span>\n";
	echo "			</td>\n";
	echo "		</tr>\n";
	echo "	</table>\n";
	echo "</div>\n";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-email_queue']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	//if (permission_exists('email_queue_add')) {
	//	echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'email_queue_edit.php']);
	//}
	//if (permission_exists('email_queue_add') && $email_queue) {
	//	echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	//}
	//if (permission_exists('email_queue_edit') && $email_queue) {
	//	echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	//}
	echo 		"<form id='form_test' class='inline' method='post' action='email_test.php' target='_blank'>\n";
	echo button::create(['label'=>$text['button-test'],'icon'=>'tools','type'=>'button','id'=>'test_button','style'=>'margin-right: 15px;','onclick'=>"$(this).fadeOut(400, function(){ $('span#form_test').fadeIn(400); $('#to').trigger('focus'); });"]);
	echo "		<span id='form_test' style='display: none;'>\n";
	echo "			<input type='text' class='txt' style='width: 150px;' name='to' id='to' placeholder='recipient@domain.com'>";
	echo button::create(['label'=>$text['button-send'],'icon'=>'envelope','type'=>'submit','id'=>'send_button','style'=>'margin-right: 15px;']);
	echo "		</span>\n";
	echo "		</form>";
	if (permission_exists('email_queue_delete') && $email_queue) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "		<form id='form_search' class='inline' method='get'>\n";
	echo "		<select class='formfld' name='email_status'>\n";
    echo "			<option value='' selected='selected' disabled hidden>".$text['label-email_status']."...</option>";
	echo "			<option value=''></option>\n";
	echo "			<option value='waiting' ".(!empty($_GET["email_status"]) && $_GET["email_status"] == "waiting" ? "selected='selected'" : null).">".ucwords($text['label-waiting'])."</option>\n";
	echo "			<option value='trying' ".(!empty($_GET["email_status"]) && $_GET["email_status"] == "trying" ? "selected='selected'" : null).">".ucwords($text['label-trying'])."</option>\n";
	echo "			<option value='sent' ".(!empty($_GET["email_status"]) && $_GET["email_status"] == "sent" ? "selected='selected'" : null).">".ucwords($text['label-sent'])."</option>\n";
	echo "			<option value='failed' ".(!empty($_GET["email_status"]) && $_GET["email_status"] == "failed" ? "selected='selected'" : null).">".ucwords($text['label-failed'])."</option>\n";
	echo "		</select>\n";
	//if (permission_exists('email_queue_all')) {
	//	if ($_GET['show'] == 'all') {
	//		echo "		<input type='hidden' name='show' value='all'>\n";
	//	}
	//	else {
	//		echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
	//	}
	//}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search ?? '')."\" placeholder=\"".$text['label-search']."\" />";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search']);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('email_queue_add') && $email_queue) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('email_queue_edit') && $email_queue) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('email_queue_delete') && $email_queue) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search ?? '')."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('email_queue_add') || permission_exists('email_queue_edit') || permission_exists('email_queue_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(empty($email_queue) ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
	}
	//if ($_GET['show'] == 'all' && permission_exists('email_queue_all')) {
	//	echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	//}
	//echo th_order_by('email_date', $text['label-email_date'], $order_by, $order);
	echo "<th class='center shrink'>".$text['label-date']."</th>\n";
	echo "<th class='center shrink hide-md-dn'>".$text['label-time']."</th>\n";
	echo "<th class='shrink hide-md-dn'>".$text['label-hostname']."</th>\n";
	echo "<th class='shrink hide-md-dn'>".$text['label-email_from']."</th>\n";
	echo th_order_by('email_to', $text['label-email_to'], $order_by, $order);
	echo th_order_by('email_subject', $text['label-email_subject'], $order_by, $order);
	echo th_order_by('email_status', $text['label-email_status'], $order_by, $order);
	echo th_order_by('email_retry_count', $text['label-email_retry_count'], $order_by, $order);
	//echo th_order_by('email_action_before', $text['label-email_action_before'], $order_by, $order);
	echo "<th class='hide-md-dn'>".$text['label-email_action_after']."</th>\n";
	if (permission_exists('email_queue_edit') && !empty($_SESSION['theme']['list_row_edit_button']['boolean']) && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($email_queue) && @sizeof($email_queue) != 0) {
		$x = 0;
		foreach ($email_queue as $row) {
			if (permission_exists('email_queue_edit')) {
				$list_row_url = "email_queue_edit.php?id=".urlencode($row['email_queue_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('email_queue_add') || permission_exists('email_queue_edit') || permission_exists('email_queue_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='email_queue[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='email_queue[$x][email_queue_uuid]' value='".escape($row['email_queue_uuid'])."' />\n";
				echo "	</td>\n";
			}
			//if ($_GET['show'] == 'all' && permission_exists('email_queue_all')) {
			//	echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			//}
			if (permission_exists('email_queue_edit')) {
				//echo "	<td><a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['email_date'])."</a></td>\n";
				echo "	<td nowrap='nowrap'><a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['email_date_formatted'])."</a></td>\n";
				echo "	<td nowrap='nowrap' class='center shrink hide-md-dn'><a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['email_time_formatted'])."</a></td>\n";
			}
			else {
				//echo "	<td>".escape($row['email_date'])."	</td>\n";
				echo "	<td nowrap='nowrap'>".escape($row['email_date_formatted'])."	</td>\n";
				echo "	<td nowrap='nowrap'>".escape($row['email_time_formatted'])."	</td>\n";
			}
			echo "	<td class='hide-md-dn'>".escape($row['hostname'])."</td>\n";
			echo "	<td class='shrink hide-md-dn'>".escape($row['email_from'])."</td>\n";
			echo "	<td class='overflow' style='width: 20%; max-width: 200px;'>".escape($row['email_to'])."</td>\n";
			echo "	<td class='overflow' style='width: 30%; max-width: 200px;'>".iconv_mime_decode($row['email_subject'])."</td>\n";
// 			echo "	<td class='hide-md-dn'>".escape($row['email_body'])."</td>\n";
			echo "	<td>".ucwords($text['label-'.$row['email_status']])."</td>\n";
			echo "	<td>".escape($row['email_retry_count'])."</td>\n";
			//echo "	<td>".escape($row['email_action_before'])."</td>\n";
			echo "	<td class='hide-md-dn'>".escape($row['email_action_after'])."</td>\n";
			if (permission_exists('email_queue_edit') && !empty($_SESSION['theme']['list_row_edit_button']['boolean']) && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($email_queue);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";


//test script
	echo "<script>\n";
	echo "	$('#form_test').submit(function(event) {\n";
	echo "		event.preventDefault();\n";
	echo "		$.ajax({\n";
	echo "			url: $(this).attr('action'),\n";
	echo "			type: $(this).attr('method'),\n";
	echo "			data: new FormData(this),\n";
	echo "			processData: false,\n";
	echo "			contentType: false,\n";
	echo "			cache: false,\n";
	echo "			success: function(response){\n";
	echo "				$('#test_result_container').html(response);\n";
	echo "				$('#test_result_layer').fadeIn(400);\n";
	echo "				$('span#form_test').fadeOut(400, function(){\n";
	echo "					$('#test_button').fadeIn(400);\n";
	echo "					$('#to').val('');\n";
	echo "				});\n";
	echo "			}\n";
	echo "		});\n";
	echo "	});\n";
	echo "</script>\n";

//include the footer
	require_once "resources/footer.php";

?>