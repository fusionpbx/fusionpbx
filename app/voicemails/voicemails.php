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

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";
	
//check permissions
	if (permission_exists('voicemail_view')) {
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
	if (is_array($_POST['voicemails'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$voicemails = $_POST['voicemails'];
	}

//process the http post data by action
	if ($action != '' && is_array($voicemails) && @sizeof($voicemails) != 0) {
		switch ($action) {
			case 'toggle':
				if (permission_exists('voicemail_edit')) {
					$obj = new voicemail;
					$obj->voicemail_toggle($voicemails);
				}
				break;
			case 'delete':
				if (permission_exists('voicemail_delete')) {
					$obj = new voicemail;
					$obj->voicemail_delete($voicemails);
				}
				break;
		}

		header('Location: voicemails.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//set the voicemail uuid array
	if (isset($_SESSION['user']['voicemail'])) {
		foreach ($_SESSION['user']['voicemail'] as $row) {
			if (strlen($row['voicemail_uuid']) > 0) {
				$voicemail_uuids[]['voicemail_uuid'] = $row['voicemail_uuid'];
			}
		}
	}
	else {
		$voicemail = new voicemail;
		$rows = $voicemail->voicemails();
		if (is_array($rows) && @sizeof($rows) != 0) {
			foreach ($rows as $row) {
				$voicemail_uuids[]['voicemail_uuid'] = $row['voicemail_uuid'];
			}
		}
		unset($voicemail, $rows, $row);
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search string
	$search = strtolower($_GET["search"]);
	if (strlen($search) > 0) {
		$sql_search = "and (";
		$sql_search .= "	lower(cast(voicemail_id as text)) like :search ";
		$sql_search .= " 	or lower(voicemail_mail_to) like :search ";
		$sql_search .= " 	or lower(voicemail_local_after_email) like :search ";
		$sql_search .= " 	or lower(voicemail_enabled) like :search ";
		$sql_search .= " 	or lower(voicemail_description) like :search ";
		$sql_search .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}

//prepare to page the results
	$sql = "select count(voicemail_uuid) from v_voicemails ";
	$sql .= "where domain_uuid = :domain_uuid ";
	if (!permission_exists('voicemail_domain')) {
		if (is_array($voicemail_uuids) && @sizeof($voicemail_uuids) != 0) {
			$sql .= "and (";
			foreach ($voicemail_uuids as $x => $row) {
				$sql_where_or[] = 'voicemail_uuid = :voicemail_uuid_'.$x;
				$parameters['voicemail_uuid_'.$x] = $row['voicemail_uuid'];
			}
			if (is_array($sql_where_or) && @sizeof($sql_where_or) != 0) {
				$sql .= implode(' or ', $sql_where_or);
			}
			$sql .= ")";
		}
		else {
			$sql .= "and voicemail_uuid is null ";
		}
	}
	$sql .= $sql_search;
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = str_replace('count(voicemail_uuid)', '*', $sql);
	$sql .= order_by($order_by, $order, 'voicemail_id', 'asc');
	$sql .= limit_offset($rows_per_page, $offset);
	$database = new database;
	$voicemails = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//get vm count for each mailbox
	if (permission_exists('voicemail_message_view')) {
		$sql = "select voicemail_uuid, count(*) as voicemail_count ";
		$sql .= "from v_voicemail_messages where domain_uuid = :domain_uuid";
		$sql .= " group by voicemail_uuid";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$voicemails_count_tmp = $database->select($sql, $parameters, 'all');

		$voicemails_count = array();
		foreach ($voicemails_count_tmp as &$row) {
			$voicemails_count[$row['voicemail_uuid']] = $row['voicemail_count'];
		}
		unset($sql, $parameters, $voicemails_count_tmp);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-voicemails'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-voicemails']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('voicemail_import')) {
		echo button::create(['type'=>'button','label'=>$text['button-import'],'icon'=>$_SESSION['theme']['button_icon_import'],'style'=>'margin-right: 15px;','link'=>'voicemail_imports.php']);
	}
	if (permission_exists('voicemail_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','link'=>'voicemail_edit.php']);
	}
	if (permission_exists('voicemail_edit') && $voicemails) {
		echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'name'=>'btn_toggle','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	}
	if (permission_exists('voicemail_delete') && $voicemails) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'voicemails.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('voicemail_edit') && $voicemails) {
		echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	}
	if (permission_exists('voicemail_delete') && $voicemails) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description-voicemail']."\n";
	echo "<br /><br />\n";

	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('voicemail_edit') || permission_exists('voicemail_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($voicemails ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	echo th_order_by('voicemail_id', $text['label-voicemail_id'], $order_by, $order);
	echo th_order_by('voicemail_mail_to', $text['label-voicemail_mail_to'], $order_by, $order, null, "class='hide-sm-dn'");
	echo th_order_by('voicemail_file', $text['label-voicemail_file_attached'], $order_by, $order, null, "class='center hide-md-dn'");
	echo th_order_by('voicemail_local_after_email', $text['label-voicemail_local_after_email'], $order_by, $order, null, "class='center hide-md-dn'");
	if (is_array($_SESSION['voicemail']['transcribe_enabled']) && $_SESSION['voicemail']['transcribe_enabled']['boolean'] == 'true') {
		echo th_order_by('voicemail_transcription_enabled', $text['label-voicemail_transcribe_enabled'], $order_by, $order);
	}
	if (permission_exists('voicemail_message_view') || permission_exists('voicemail_greeting_view')) {
		echo "<th>".$text['label-tools']."</th>\n";
	}
	if (permission_exists('voicemail_message_view') && permission_exists('voicemail_greeting_view')) {
		echo "<th></th>\n";
	}
	echo th_order_by('voicemail_enabled', $text['label-voicemail_enabled'], $order_by, $order, null, "class='center'");
	echo th_order_by('voicemail_description', $text['label-voicemail_description'], $order_by, $order, null, "class='hide-sm-dn'");
	if (permission_exists('voicemail_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($voicemails) && @sizeof($voicemails) != 0) {
		$x = 0;
		foreach ($voicemails as $row) {
			if (permission_exists('voicemail_edit')) {
				$list_row_url = "voicemail_edit.php?id=".urlencode($row['voicemail_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('voicemail_edit') || permission_exists('voicemail_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='voicemails[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='voicemails[$x][uuid]' value='".escape($row['voicemail_uuid'])."' />\n";
				echo "	</td>\n";
			}
			echo "	<td>\n";
			if (permission_exists('voicemail_edit')) {
				echo "	<a href='".$list_row_url."' title=\"".$text['button-edit']."\">".escape($row['voicemail_id'])."</a>\n";
			}
			else {
				echo "	".escape($row['voicemail_id']);
			}
			echo "	</td>\n";

			echo "	<td class='hide-sm-dn'>".escape($row['voicemail_mail_to'])."&nbsp;</td>\n";
			echo "	<td class='center hide-md-dn'>".($row['voicemail_file'] == 'attach' ? $text['label-true'] : $text['label-false'])."</td>\n";
			echo "	<td class='center hide-md-dn'>".ucwords(escape($row['voicemail_local_after_email']))."&nbsp;</td>\n";
			if (is_array($_SESSION['voicemail']['transcribe_enabled']) && $_SESSION['voicemail']['transcribe_enabled']['boolean'] == 'true') {
				echo "	<td>".ucwords(escape($row['voicemail_transcription_enabled']))."&nbsp;</td>\n";
			}
			if (permission_exists('voicemail_message_view')) {
				echo "	<td class='no-wrap' width = '10%'>\n";
				$tmp_voicemail_string = (array_key_exists($row['voicemail_uuid'], $voicemails_count)) ? " (" . $voicemails_count[$row['voicemail_uuid']] . ")" : " (0)";
				echo "		<a href='voicemail_messages.php?id=".escape($row['voicemail_uuid'])."'>".$text['label-messages'].$tmp_voicemail_string."</a>\n";
				echo "	</td>\n";
			}
			if (permission_exists('voicemail_greeting_view')) {
				echo "	<td class='no-wrap' width = '10%'>\n";
				echo "		<a href='".PROJECT_PATH."/app/voicemail_greetings/voicemail_greetings.php?id=".$row['voicemail_id']."&back=".urlencode($_SERVER["REQUEST_URI"])."'>".$text['label-greetings']."</a>\n";
				echo "	</td>\n";
			}
			if (permission_exists('voicemail_edit')) {
				echo "	<td class='no-link center'>\n";
				echo button::create(['type'=>'submit','class'=>'link','label'=>$text['label-'.$row['voicemail_enabled']],'title'=>$text['button-toggle'],'onclick'=>"list_self_check('checkbox_".$x."'); list_action_set('toggle'); list_form_submit('form_list')"]);
			}
			else {
				echo "	<td class='center'>\n";
				echo $text['label-'.$row['voicemail_enabled']];
			}
			echo "	</td>\n";
			echo "	<td class='description overflow hide-sm-dn'>".escape($row['voicemail_description'])."&nbsp;</td>\n";
			if (permission_exists('voicemail_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
	}
	unset($voicemails);

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
