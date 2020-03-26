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
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('email_log_view')) {
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
	if (is_array($_POST['emails'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$emails = $_POST['emails'];
	}

//process the http post data by action
	if ($action != '' && is_array($emails) && @sizeof($emails) != 0) {
		switch ($action) {
			case 'download':
				if (permission_exists('email_log_download')) {
					$obj = new email_logs;
					$obj->download($emails);
					message::add($text['message-download_failed'],'negative',7000); //download failed, set message
				}
				break;
			case 'resend':
				if (permission_exists('email_log_resend')) {
					$obj = new email_logs;
					$obj->resend($emails);
				}
				break;
			case 'delete':
				if (permission_exists('email_log_delete')) {
					$obj = new email_logs;
					$obj->delete($emails);
				}
				break;
		}

		header('Location: email_logs.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//get order and order by and sanatize the values
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search term
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "lower(type) like :search ";
		$sql_search .= "or lower(email) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(*) from v_email_logs ";
	$sql .= "where true ";
	if (permission_exists('email_log_all') && $_REQUEST['show'] != 'all') {
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$sql .= $sql_search;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = "search=".$search;
	if ($_GET['show'] == "all" && permission_exists('email_log_all')) {
		$param .= "&show=all";
	}
	$page = $_GET['page'];
	if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(*)', '*', $sql);
	$sql .= order_by($order_by, $order, 'sent_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get call details
	if (is_array($result) && @sizeof($result) != 0) {
		foreach ($result as $row) {
			$sql = "select caller_id_name, caller_id_number, destination_number ";
			$sql .= "from v_xml_cdr ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and uuid = :uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
			$parameters['uuid'] = $row['call_uuid'];
			$database = new database;
			$result2 = $database->select($sql, $parameters, 'all');
			if (is_array($result2) && @sizeof($result2) != 0) {
				foreach($result2 as $row2) {
					$call[$row['call_uuid']]['caller_id_name'] = $row2['caller_id_name'];
					$call[$row['call_uuid']]['caller_id_number'] = $row2['caller_id_number'];
					$call[$row['call_uuid']]['destination_number'] = $row2['destination_number'];
				}
			}
			unset($sql, $parameters, $result2, $row2);
		}
	}

//create token
	$object = new token;
	$token = $object->create('/app/email_logs/email_logs.php');

//include the header
	$document['title'] = $text['title-emails'];
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
	echo "		padding: 20px 30px;\n";
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
	echo "	<div class='heading'><b>".$text['header-emails']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	echo 		"<form id='form_test' class='inline' method='post' action='email_test.php' target='_blank'>\n";
	echo button::create(['label'=>$text['button-test'],'icon'=>'tools','type'=>'button','id'=>'test_button','style'=>'margin-right: 15px;','onclick'=>"$(this).fadeOut(400, function(){ $('span#form_test').fadeIn(400); $('#to').trigger('focus'); });"]);
	echo "		<span id='form_test' style='display: none;'>\n";
	echo "			<input type='text' class='txt' style='width: 150px;' name='to' id='to' placeholder='recipient@domain.com'>";
	echo button::create(['label'=>$text['button-send'],'icon'=>'envelope','type'=>'submit','id'=>'send_button','style'=>'margin-right: 15px;']);
	echo "		</span>\n";
	echo "		<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "		</form>";
	if (permission_exists('email_log_resend') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-resend'],'icon'=>'paper-plane','onclick'=>"modal_open('modal-resend','btn_resend');"]);
	}
	if (permission_exists('email_log_download') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'onclick'=>"list_action_set('download'); list_form_submit('form_list');"]);
	}
	if (permission_exists('email_log_delete') && $result) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('email_log_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo button::create(['label'=>$text['button-refresh'],'icon'=>$_SESSION['theme']['button_icon_refresh'],'type'=>'button','onclick'=>'document.location.reload();']);
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'email_logs.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('email_log_resend') && $result) {
		echo modal::create(['id'=>'modal-resend','type'=>'general','message'=>$text['confirm-resend'],'actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_resend','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('resend'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('email_log_delete') && $result) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-emails']."\n";
	echo "<br /><br />\n";

	/*
	echo "<form id='test_form' method='post' action='email_test.php' target='_blank'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' valign='top' nowrap='nowrap'>";
	echo "			<b>".$text['header-emails']."</b>";
	echo "			<br /><br />";
	echo "			".$text['description-emails'];
	echo "		</td>\n";
	echo "		<td width='50%' align='right' valign='top'>\n";
	echo "			<input type='button' class='btn' id='test_button' alt=\"".$text['button-test']."\" onclick=\"$(this).fadeOut(400, function(){ $('span#test_form').fadeIn(400); $('#to').trigger('focus'); });\" value='".$text['button-test']."'>\n";
	echo "			<span id='test_form' style='display: none;'>\n";
	echo "				<input type='text' class='formfld' style='min-width: 150px; width:150px; max-width: 150px;' name='to' id='to' placeholder='recipient@domain.com'>\n";
	echo "				<input type='submit' class='btn' id='send_button' alt=\"".$text['button-send']."\" value='".$text['button-send']."'>\n";
	echo "			</span>\n";
	if (permission_exists('email_log_all')) {
		if ($_REQUEST['showall'] != 'true') {
			echo "		<input type='button' class='btn' value='".$text['button-show_all']."' onclick=\"window.location='email_logs.php?showall=true';\">\n";
		}
	}
	echo "			<input type='button' class='btn' alt=\"".$text['button-refresh']."\" onclick=\"document.location.reload();\" value='".$text['button-refresh']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";
	echo "<br />\n";
	*/

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('email_log_download') || permission_exists('email_log_resend') || permission_exists('email_log_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($result ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == "all" && permission_exists('email_log_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order, null, null, $param);
	}
	echo th_order_by('sent_date', $text['label-sent'], $order_by, $order, null, null, $param);
	echo th_order_by('type', $text['label-type'], $order_by, $order, null, null, $param);
	echo th_order_by('status', $text['label-status'], $order_by, $order, null, null, $param);
	echo "<th class='center'>".$text['label-actions']."</th>\n";
	echo "<th class='hide-sm-dn'>".$text['label-reference']."</th>\n";
	if ($_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($result) && @sizeof($result) != 0) {
		$x = 0;
		foreach($result as $row) {
			$list_row_url = "email_log_view.php?id=".urlencode($row['email_log_uuid']);
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('email_log_download') || permission_exists('email_log_resend') || permission_exists('email_log_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='emails[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='emails[$x][uuid]' value='".escape($row['email_log_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == "all" && permission_exists('email_log_all')) {
				echo "	<td>".escape($_SESSION['domains'][$row['domain_uuid']]['domain_name'])."</td>\n";
			}
			$sent_date = explode('.', $row['sent_date']);
			echo "	<td><a href='".$list_row_url."' title=\"".$text['label-message_view']."\">".$sent_date[0]."</td>\n";
			echo "	<td>".$text['label-type_'.escape($row['type'])]."</td>\n";
			echo "	<td>".$text['label-status_'.escape($row['status'])]."</td>\n";
			echo "	<td class='middle button center no-link no-wrap'>";
			if (permission_exists('email_log_resend')) {
				echo button::create(['type'=>'button','title'=>$text['button-resend'],'icon'=>'paper-plane','onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('resend'); list_form_submit('form_list')"]);
			}
			if (permission_exists('email_log_download')) {
				echo button::create(['type'=>'button','title'=>$text['button-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('download'); list_form_submit('form_list')"]);
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn no-link'>";
			echo button::create(['type'=>'button','class'=>'link','label'=>$text['label-reference_cdr'],'link'=>PROJECT_PATH.'/app/xml_cdr/xml_cdr_details.php?id='.urlencode($row['call_uuid'])]);
			echo "		".($call[$row['call_uuid']]['caller_id_name'] != '' ? "&nbsp;&nbsp;".$call[$row['call_uuid']]['caller_id_name'].(is_numeric($call[$row['call_uuid']]['caller_id_number']) ? ' ('.format_phone($call[$row['call_uuid']]['caller_id_number']).')' : null) : $call[$row['call_uuid']]['caller_id_number']);
			if ($call[$row['call_uuid']]['destination_number']) {
				echo 	"&nbsp;&nbsp;<span style='font-size: 150%; line-height: 10px;'>&#8674;</span>&nbsp;&nbsp;".$call[$row['call_uuid']]['destination_number'];
			}
			echo "	</td>\n";
			if ($_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>";
				echo button::create(['type'=>'button','title'=>$text['label-message_view'],'icon'=>$_SESSION['theme']['button_icon_view'],'link'=>$list_row_url]);
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