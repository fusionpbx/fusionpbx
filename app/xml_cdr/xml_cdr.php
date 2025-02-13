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
	Portions created by the Initial Developer are Copyright (C) 2008-2024
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	Tony Fernandez <tfernandez@smartip.ca>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permisions
	if (permission_exists('xml_cdr_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//connect to the database
	$database = database::new();

//set permissions
	$permission = array();
	$permission['xml_cdr_view'] = permission_exists('xml_cdr_view');
	$permission['xml_cdr_search_extension'] = permission_exists('xml_cdr_search_extension');
	$permission['xml_cdr_delete'] = permission_exists('xml_cdr_delete');
	$permission['xml_cdr_domain'] = permission_exists('xml_cdr_domain');
	$permission['xml_cdr_search_call_center_queues'] = permission_exists('xml_cdr_search_call_center_queues');
	$permission['xml_cdr_search_ring_groups'] = permission_exists('xml_cdr_search_ring_groups');
	$permission['xml_cdr_statistics'] = permission_exists('xml_cdr_statistics');
	$permission['xml_cdr_archive'] = permission_exists('xml_cdr_archive');
	$permission['xml_cdr_all'] = permission_exists('xml_cdr_all');
	$permission['xml_cdr_export'] = permission_exists('xml_cdr_export');
	$permission['xml_cdr_export_csv'] = permission_exists('xml_cdr_export_csv');
	$permission['xml_cdr_export_pdf'] = permission_exists('xml_cdr_export_pdf');
	$permission['xml_cdr_search'] = permission_exists('xml_cdr_search');
	$permission['xml_cdr_search_direction'] = permission_exists('xml_cdr_search_direction');
	$permission['xml_cdr_b_leg'] = permission_exists('xml_cdr_b_leg');
	$permission['xml_cdr_search_status'] = permission_exists('xml_cdr_search_status');
	$permission['xml_cdr_search_caller_id'] = permission_exists('xml_cdr_search_caller_id');
	$permission['xml_cdr_search_start_range'] = permission_exists('xml_cdr_search_start_range');
	$permission['xml_cdr_search_duration'] = permission_exists('xml_cdr_search_duration');
	$permission['xml_cdr_search_caller_destination'] = permission_exists('xml_cdr_search_caller_destination');
	$permission['xml_cdr_search_destination'] = permission_exists('xml_cdr_search_destination');
	$permission['xml_cdr_codecs'] = permission_exists('xml_cdr_codecs');
	$permission['xml_cdr_search_tta'] = permission_exists('xml_cdr_search_tta');
	$permission['xml_cdr_search_hangup_cause'] = permission_exists('xml_cdr_search_hangup_cause');
	$permission['xml_cdr_search_recording'] = permission_exists('xml_cdr_search_recording');
	$permission['xml_cdr_search_order'] = permission_exists('xml_cdr_search_order');
	$permission['xml_cdr_extension'] = permission_exists('xml_cdr_extension');
	$permission['xml_cdr_caller_id_name'] = permission_exists('xml_cdr_caller_id_name');
	$permission['xml_cdr_caller_id_number'] = permission_exists('xml_cdr_caller_id_number');
	$permission['xml_cdr_caller_destination'] = permission_exists('xml_cdr_caller_destination');
	$permission['xml_cdr_destination'] = permission_exists('xml_cdr_destination');
	$permission['xml_cdr_start'] = permission_exists('xml_cdr_start');
	$permission['xml_cdr_tta'] = permission_exists('xml_cdr_tta');
	$permission['xml_cdr_duration'] = permission_exists('xml_cdr_duration');
	$permission['xml_cdr_pdd'] = permission_exists('xml_cdr_pdd');
	$permission['xml_cdr_mos'] = permission_exists('xml_cdr_mos');
	$permission['xml_cdr_hangup_cause'] = permission_exists('xml_cdr_hangup_cause');
	$permission['xml_cdr_custom_fields'] = permission_exists('xml_cdr_custom_fields');
	$permission['xml_cdr_search_advanced'] = permission_exists('xml_cdr_search_advanced');
	$permission['xml_cdr_direction'] = permission_exists('xml_cdr_direction');
	$permission['xml_cdr_recording'] = permission_exists('xml_cdr_recording');
	$permission['xml_cdr_recording_play'] = permission_exists('xml_cdr_recording_play');
	$permission['xml_cdr_recording_download'] = permission_exists('xml_cdr_recording_download');
	$permission['xml_cdr_account_code'] = permission_exists('xml_cdr_account_code');
	$permission['xml_cdr_status'] = permission_exists('xml_cdr_status');
	$permission['xml_cdr_details'] = permission_exists('xml_cdr_details');
	$permission['xml_cdr_lose_race'] = permission_exists('xml_cdr_lose_race');
	$permission['xml_cdr_cc_agent_leg'] = permission_exists('xml_cdr_cc_agent_leg');
	$permission['xml_cdr_cc_side'] = permission_exists('xml_cdr_cc_side');
	$permission['xml_cdr_call_center_queues'] = permission_exists('xml_cdr_call_center_queues');

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set defaults
	$archive_request = false;
	$action = '';
	$xml_cdrs = [];
	$paging_controls_mini = '';
	$paging_controls = null;
	$order_by = "";
	$read_codec = '';
	$write_codec = '';
	if (!isset($_REQUEST['show'])) {
		//set to show only this domain
		$_REQUEST['show'] = 'domain';
	}

//get posted data
	if (!$archive_request && isset($_POST['xml_cdrs']) && is_array($_POST['xml_cdrs'])) {
		$action = $_POST['action'] ?? '';
		$xml_cdrs = $_POST['xml_cdrs'] ?? [];
	}

//process the http post data by action
	if (!$archive_request && $action != '' && count($xml_cdrs) > 0) {
		switch ($action) {
			case 'delete':
				if ($permission['xml_cdr_delete']) {
					$obj = new xml_cdr;
					$obj->delete($xml_cdrs);
				}
				break;
		}

		header('Location: xml_cdr.php');
		exit;
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//get the extensions
	if ($permission['xml_cdr_search_extension']) {
		$sql = "select extension_uuid, extension, number_alias from v_extensions ";
		$sql .= "where domain_uuid = :domain_uuid ";
		if (!$permission['xml_cdr_domain'] && is_array($extension_uuids) && @sizeof($extension_uuids != 0)) {
			$sql .= "and extension_uuid in ('".implode("','",$extension_uuids)."') "; //only show the user their extensions
		}
		$sql .= "order by extension asc, number_alias asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$extensions = $database->select($sql, $parameters, 'all');
	}

//get the ring groups
	if ($permission['xml_cdr_search_ring_groups']) {
		$sql = "select ring_group_uuid, ring_group_name, ring_group_extension from v_ring_groups ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "order by ring_group_extension asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$ring_groups = $database->select($sql, $parameters, 'all');
	}

//get the call center queues
	if ($permission['xml_cdr_search_call_center_queues']) {
		$sql = "select call_center_queue_uuid, queue_name, queue_extension from v_call_center_queues ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "order by queue_extension asc ";
		$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$call_center_queues = $database->select($sql, $parameters, 'all');
	}

//include the header
	if ($archive_request) {
		$document['title'] = $text['title-call_detail_records_archive'];
	}
	else {
		$document['title'] = $text['title-call_detail_records'];
	}
	require_once "resources/header.php";

//xml cdr include
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	require_once "xml_cdr_inc.php";

//javascript function: send_cmd
	echo "<script type=\"text/javascript\">\n";
	echo "	function send_cmd(url) {\n";
	echo "		if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari\n";
	echo "			xmlhttp=new XMLHttpRequest();\n";
	echo "		}\n";
	echo "		else {// code for IE6, IE5\n";
	echo "			xmlhttp=new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
	echo "		}\n";
	echo "		xmlhttp.open(\"GET\",url,true);\n";
	echo "		xmlhttp.send(null);\n";
	echo "		document.getElementById('cmd_reponse').innerHTML=xmlhttp.responseText;\n";
	echo "	}\n";
	echo "</script>\n";

//javascript to toggle export select box
	echo "<script language='javascript' type='text/javascript'>";
	echo "	var fade_speed = 400;";
	echo "	function toggle_select(select_id) {";
	echo "		$('#'+select_id).fadeToggle(fade_speed, function() {";
	echo "			document.getElementById(select_id).selectedIndex = 0;";
	echo "			document.getElementById(select_id).focus();";
	echo "		});";
	echo "	}";
	echo "</script>";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($archive_request) {
		echo "<b>".$text['title-call_detail_records_archive']."</b>";
	}
	else {
		echo "<b>".$text['title-call_detail_records']."</b>";
	}
	echo "</div>\n";
	echo "	<div class='actions'>\n";
	if (!$archive_request) {
		if ($permission['xml_cdr_statistics']) {
			echo button::create(['type'=>'button','label'=>$text['button-statistics'],'icon'=>'chart-area','link'=>'xml_cdr_statistics.php']);
		}
		if ($permission['xml_cdr_archive']) {
			echo button::create(['type'=>'button','label'=>$text['button-archive'],'icon'=>'archive','link'=>'xml_cdr_archive.php'.($_REQUEST['show'] == 'all' ? '?show=all' : null)]);
		}
	}
	echo 		"<form id='frm_export' class='inline' method='post' action='xml_cdr_export.php'>\n";
	if ($archive_request) {
		echo "	<input type='hidden' name='archive_request' value='true'>\n";
	}
	echo "		<input type='hidden' name='cdr_id' value='".escape($cdr_id ?? '')."'>\n";
	echo "		<input type='hidden' name='direction' value='".escape($direction ?? '')."'>\n";
	echo "		<input type='hidden' name='caller_id_name' value='".escape($caller_id_name ?? '')."'>\n";
	echo "		<input type='hidden' name='start_stamp_begin' value='".escape($start_stamp_begin ?? '')."'>\n";
	echo "		<input type='hidden' name='start_stamp_end' value='".escape($start_stamp_end ?? '')."'>\n";
	echo "		<input type='hidden' name='hangup_cause' value='".escape($hangup_cause ?? '')."'>\n";
	echo "		<input type='hidden' name='status' value='".escape($status ?? '')."'>\n";
	echo "		<input type='hidden' name='caller_id_number' value='".escape($caller_id_number ?? '')."'>\n";
	echo "		<input type='hidden' name='caller_destination' value='".escape($caller_destination ?? '')."'>\n";
	echo "		<input type='hidden' name='extension_uuid' value='".escape($extension_uuid ?? '')."'>\n";
	echo "		<input type='hidden' name='destination_number' value='".escape($destination_number ?? '')."'>\n";
	echo "		<input type='hidden' name='context' value='".escape($context ?? '')."'>\n";
	echo "		<input type='hidden' name='answer_stamp_begin' value='".escape($answer_stamp_begin ?? '')."'>\n";
	echo "		<input type='hidden' name='answer_stamp_end' value='".escape($answer_stamp_end ?? '')."'>\n";
	echo "		<input type='hidden' name='end_stamp_begin' value='".escape($end_stamp_begin ?? '')."'>\n";
	echo "		<input type='hidden' name='end_stamp_end' value='".escape($end_stamp_end ?? '')."'>\n";
	echo "		<input type='hidden' name='start_epoch' value='".escape($start_epoch ?? '')."'>\n";
	echo "		<input type='hidden' name='stop_epoch' value='".escape($stop_epoch ?? '')."'>\n";
	echo "		<input type='hidden' name='duration' value='".escape($duration ?? '')."'>\n";
	echo "		<input type='hidden' name='duration_min' value='".escape($duration_min ?? '')."'>\n";
	echo "		<input type='hidden' name='duration_max' value='".escape($duration_max ?? '')."'>\n";
	echo "		<input type='hidden' name='billsec' value='".escape($billsec ?? '')."'>\n";
	echo "		<input type='hidden' name='xml_cdr_uuid' value='".escape($xml_cdr_uuid ?? '')."'>\n";
	echo "		<input type='hidden' name='bleg_uuid' value='".escape($bleg_uuid ?? '')."'>\n";
	echo "		<input type='hidden' name='accountcode' value='".escape($accountcode ?? '')."'>\n";
	echo "		<input type='hidden' name='read_codec' value='".escape($read_codec ?? '')."'>\n";
	echo "		<input type='hidden' name='write_codec' value='".escape($write_codec ?? '')."'>\n";
	echo "		<input type='hidden' name='remote_media_ip' value='".escape($remote_media_ip ?? '')."'>\n";
	echo "		<input type='hidden' name='network_addr' value='".escape($network_addr ?? '')."'>\n";
	echo "		<input type='hidden' name='bridge_uuid' value='".escape($bridge_uuid ?? '')."'>\n";
	echo "		<input type='hidden' name='leg' value='".escape($leg ?? '')."'>\n";
	echo "		<input type='hidden' name='tta_min' value='".escape($tta_min ?? '')."'>\n";
	echo "		<input type='hidden' name='tta_max' value='".escape($tta_max ?? '')."'>\n";
	if ($permission['xml_cdr_all'] && $_REQUEST['show'] == 'all') {
		echo "	<input type='hidden' name='show' value='all'>\n";
	}
	if (isset($_SESSION['cdr']['field']) && is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = $array[count($array) - 1];
			if (isset($_REQUEST[$field_name])) {
				echo "	<input type='hidden' name='".escape($field_name)."' value='".escape($$field_name)."'>\n";
			}
		}
	}
	if (isset($order_by)) {
		echo "	<input type='hidden' name='order_by' value='".escape($order_by)."'>\n";
		echo "	<input type='hidden' name='order' value='".escape($order)."'>\n";
	}
	if ($archive_request) {
		echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'link'=>'xml_cdr.php']);
	}
	echo button::create(['type'=>'button','label'=>$text['button-refresh'],'icon'=>'sync-alt','style'=>'margin-left: 15px;','onclick'=>'location.reload(true);']);
	if (isset($_GET['status']) &&  $_GET['status'] != 'missed') {
		echo button::create(['type'=>'button','label'=>$text['button-missed'],'icon'=>'phone-slash','link'=>'?status=missed']);
	}

	if ($permission['xml_cdr_export']) {
		echo button::create(['type'=>'button','label'=>$text['button-export'],'icon'=>$_SESSION['theme']['button_icon_export'],'onclick'=>"toggle_select('export_format'); this.blur();"]);
		echo 		"<select class='formfld' style='display: none; width: auto;' name='export_format' id='export_format' onchange=\"display_message('".$text['message-preparing_download']."'); toggle_select('export_format'); document.getElementById('frm_export').submit();\">";
		echo "			<option value='' disabled='disabled' selected='selected'>".$text['label-format']."</option>";
		if ($permission['xml_cdr_export_csv']) {
			echo "			<option value='csv'>CSV</option>";
		}
		if ($permission['xml_cdr_export_pdf']) {
			echo "			<option value='pdf'>PDF</option>";
		}
		echo "		</select>";
	}
	if (!$archive_request && $permission['xml_cdr_delete']) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	if ($permission['xml_cdr_all'] && $_REQUEST['show'] !== 'all') {
		echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
	}
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (!$archive_request && $permission['xml_cdr_delete']) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}

	echo $text['description']." &nbsp; ".$text['description_search']."\n";
	echo "<br /><br />\n";

//basic search of call detail records
	if ($permission['xml_cdr_search']) {
		echo "<form name='frm' id='frm' method='get'>\n";

		echo "<div class='card'>\n";
		echo "<div class='form_grid'>\n";

		if ($permission['xml_cdr_search_direction']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-direction']."\n";
			echo "		</div>\n";
			echo "		<div class='field'>\n";
			echo "			<select name='direction' class='formfld'>\n";
			echo "				<option value=''></option>\n";
			echo "				<option value='inbound' ".(($direction == "inbound") ? "selected='selected'" : null).">".$text['label-inbound']."</option>\n";
			echo "				<option value='outbound' ".(($direction == "outbound") ? "selected='selected'" : null).">".$text['label-outbound']."</option>\n";
			echo "				<option value='local' ".(($direction == "local") ? "selected='selected'" : null).">".$text['label-local']."</option>\n";
			echo "			</select>\n";
			if ($permission['xml_cdr_b_leg']){
				echo "		<select name='leg' class='formfld'>\n";
				echo "			<option value=''></option>\n";
				echo "			<option value='a' ".(!empty($_REQUEST["leg"]) && $leg == 'a' ? "selected='selected'" : null).">a-leg</option>\n";
				echo "			<option value='b' ".($leg == 'b' ? "selected='selected'" : null).">b-leg</option>\n";
				echo "		</select>\n";
			}
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if ($permission['xml_cdr_search_status']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-status']."\n";
			echo "		</div>\n";
			echo "		<div class='field'>\n";
			echo "			<select name='status' class='formfld'>\n";
			echo "				<option value=''></option>\n";
			echo "				<option value='answered' ".(($status == 'answered') ? 'selected' : null).">".$text['label-answered']."</option>\n";
			echo "				<option value='no_answer' ".(($status == 'no_answer') ? 'selected' : null).">".$text['label-no_answer']."</option>\n";
			echo "				<option value='busy' ".(($status == 'busy') ? 'selected' : null).">".$text['label-busy']."</option>\n";
			echo "				<option value='missed' ".(($status == 'missed') ? 'selected' : null).">".$text['label-missed']."</option>\n";
			echo "				<option value='voicemail' ".(($status == 'voicemail') ? 'selected' : null).">".$text['label-voicemail']."</option>\n";
			echo "				<option value='cancelled' ".(($status == 'cancelled') ? 'selected' : null).">".$text['label-cancelled']."</option>\n";
			echo "				<option value='failed' ".(($status == 'failed') ? 'selected' : null).">".$text['label-failed']."</option>\n";
			echo "			</select>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if ($permission['xml_cdr_search_extension']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-extension']."\n";
			echo "		</div>\n";
			echo "		<div class='field'>\n";
			echo "			<select class='formfld' name='extension_uuid' id='extension_uuid'>\n";
			echo "				<option value=''></option>";
			if (is_array($extensions) && @sizeof($extensions) != 0) {
				foreach ($extensions as $row) {
					$selected = ($row['extension_uuid'] == $extension_uuid) ? "selected" : null;
					echo "		<option value='".escape($row['extension_uuid'])."' ".escape($selected).">".((is_numeric($row['extension'])) ? escape($row['extension']) : escape($row['number_alias'])." (".escape($row['extension']).")")."</option>";
				}
			}
			echo "			</select>\n";
			echo "		</div>\n";
			echo "	</div>\n";
			unset($sql, $parameters, $extensions, $row, $selected);
		}
		if ($permission['xml_cdr_search_caller_id']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-caller_id']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<input type='text' class='formfld' name='caller_id_name' style='min-width: 115px; width: 115px;' placeholder=\"".$text['label-name']."\" value='".escape($caller_id_name)."'>\n";
			echo "			<input type='text' class='formfld' name='caller_id_number' style='min-width: 115px; width: 115px;' placeholder=\"".$text['label-number']."\" value='".escape($caller_id_number)."'>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if ($permission['xml_cdr_search_start_range']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-start_range']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#start_stamp_begin' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px;' name='start_stamp_begin' id='start_stamp_begin' placeholder='".$text['label-from']."' value='".escape($start_stamp_begin)."' autocomplete='off'>\n";
			echo "			<input type='text' class='formfld datetimepicker' data-toggle='datetimepicker' data-target='#start_stamp_end' onblur=\"$(this).datetimepicker('hide');\" style='min-width: 115px; width: 115px;' name='start_stamp_end' id='start_stamp_end' placeholder='".$text['label-to']."' value='".escape($start_stamp_end)."' autocomplete='off'>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if ($permission['xml_cdr_search_duration']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-duration']." (".$text['label-seconds'].")\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<input type='text' class='formfld' style='min-width: 75px; width: 75px;' name='duration_min' value='".escape($duration_min)."' placeholder=\"".$text['label-minimum']."\">\n";
			echo "			<input type='text' class='formfld' style='min-width: 75px; width: 75px;' name='duration_max' value='".escape($duration_max)."' placeholder=\"".$text['label-maximum']."\">\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if ($permission['xml_cdr_search_caller_destination']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-caller_destination']."\n";
			echo "		</div>\n";
			echo "		<div class='field'>\n";
			echo "			<input type='text' class='formfld' name='caller_destination' value='".escape($caller_destination)."'>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if ($permission['xml_cdr_search_destination']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-destination']."\n";
			echo "		</div>\n";
			echo "		<div class='field'>\n";
			echo "			<input type='text' class='formfld' name='destination_number' id='destination_number' value='".escape($destination_number ?? '')."'>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if ($permission['xml_cdr_codecs']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-codecs']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<input type='text' class='formfld' style='min-width: 115px; width: 115px;' name='read_codec' id='read_codec' value='".escape($read_codec)."' placeholder=\"".$text['label-codec_read']."\">\n";
			echo "			<input type='text' class='formfld' style='min-width: 115px; width: 115px;' name='write_codec' id='write_codec' value='".escape($write_codec)."' placeholder=\"".$text['label-codec_write']."\">\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if ($permission['xml_cdr_search_tta']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-tta']." (".$text['label-seconds'].")\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<input type='text' class='formfld' style='min-width: 75px; width: 75px;' name='tta_min' id='tta_min' value='".escape($tta_min)."' placeholder=\"".$text['label-minimum']."\">\n";
			echo "			<input type='text' class='formfld' style='min-width: 75px; width: 75px;' name='tta_max' id='tta_max' value='".escape($tta_max)."' placeholder=\"".$text['label-maximum']."\">\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}

		if ($permission['xml_cdr_search_hangup_cause']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-hangup_cause']."\n";
			echo "		</div>\n";
			echo "		<div class='field'>\n";
			echo "			<select name='hangup_cause' class='formfld'>\n";
			echo "				<option value=''></option>\n";
			$cdr_status_options = array(
				'NORMAL_CLEARING',
				'ORIGINATOR_CANCEL',
				'BLIND_TRANSFER',
				'LOSE_RACE',
				'NO_ANSWER',
				'NORMAL_UNSPECIFIED',
				'NO_USER_RESPONSE',
				'NO_ROUTE_DESTINATION',
				'SUBSCRIBER_ABSENT',
				'NORMAL_TEMPORARY_FAILURE',
				'ATTENDED_TRANSFER',
				'PICKED_OFF',
				'USER_BUSY',
				'CALL_REJECTED',
				'INVALID_NUMBER_FORMAT',
				'NETWORK_OUT_OF_ORDER',
				'DESTINATION_OUT_OF_ORDER',
				'RECOVERY_ON_TIMER_EXPIRE',
				'MANAGER_REQUEST',
				'MEDIA_TIMEOUT',
				'UNALLOCATED_NUMBER',
				'NONE',
				'EXCHANGE_ROUTING_ERROR',
				'ALLOTTED_TIMEOUT',
				'CHAN_NOT_IMPLEMENTED',
				'INCOMPATIBLE_DESTINATION',
				'USER_NOT_REGISTERED',
				'SYSTEM_SHUTDOWN',
				'MANDATORY_IE_MISSING',
				'REQUESTED_CHAN_UNAVAIL'
				);
			sort($cdr_status_options);
			foreach ($cdr_status_options as $cdr_status) {
				$selected = ($hangup_cause == $cdr_status) ? "selected='selected'" : null;
				$cdr_status_label = ucwords(strtolower(str_replace("_", " ", $cdr_status)));
				echo "			<option value='".escape($cdr_status)."' ".escape($selected).">".escape($cdr_status_label)."</option>\n";
			}
			echo "			</select>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if ($permission['xml_cdr_search_recording']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-recording']."\n";
			echo "		</div>\n";
			echo "		<div class='field'>\n";
			echo "			<select name='recording' class='formfld'>\n";
			echo "				<option value=''></option>\n";
			echo "				<option value='true' ".($recording == 'true' ? "selected='selected'" : null).">".$text['label-true']."</option>\n";
			echo "				<option value='false' ".($recording == 'false' ? "selected='selected'" : null).">".$text['label-false']."</option>\n";
			echo "			</select>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if ($permission['xml_cdr_search_order']) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-order']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<select name='order_by' class='formfld'>\n";
			if ($permission['xml_cdr_extension']) {
				echo "			<option value='extension' ".($order_by == 'extension' ? "selected='selected'" : null).">".$text['label-extension']."</option>\n";
			}
			if ($permission['xml_cdr_all']) {
				echo "			<option value='domain_name' ".($order_by == 'domain_name' ? "selected='selected'" : null).">".$text['label-domain']."</option>\n";
			}
			if ($permission['xml_cdr_caller_id_name']) {
				echo "			<option value='caller_id_name' ".($order_by == 'caller_id_name' ? "selected='selected'" : null).">".$text['label-caller_id_name']."</option>\n";
			}
			if ($permission['xml_cdr_caller_id_number']) {
				echo "			<option value='caller_id_number' ".($order_by == 'caller_id_number' ? "selected='selected'" : null).">".$text['label-caller_id_number']."</option>\n";
			}
			if ($permission['xml_cdr_caller_destination']) {
				echo "			<option value='caller_destination' ".($order_by == 'caller_destination' ? "selected='selected'" : null).">".$text['label-caller_destination']."</option>\n";
			}
			if ($permission['xml_cdr_destination']) {
				echo "			<option value='destination_number' ".($order_by == 'destination_number' ? "selected='selected'" : null).">".$text['label-destination']."</option>\n";
			}
			if ($permission['xml_cdr_start']) {
				echo "			<option value='start_stamp' ".($order_by == 'start_stamp' || $order_by == '' ? "selected='selected'" : null).">".$text['label-start']."</option>\n";
			}
			if ($permission['xml_cdr_tta']) {
				echo "			<option value='tta' ".($order_by == 'tta' ? "selected='selected'" : null).">".$text['label-tta']."</option>\n";
			}
			if ($permission['xml_cdr_duration']) {
				echo "			<option value='duration' ".($order_by == 'duration' ? "selected='selected'" : null).">".$text['label-duration']."</option>\n";
			}
			if ($permission['xml_cdr_pdd']) {
				echo "			<option value='pdd_ms' ".($order_by == 'pdd_ms' ? "selected='selected'" : null).">".$text['label-pdd']."</option>\n";
			}
			if ($permission['xml_cdr_mos']) {
				echo "			<option value='rtp_audio_in_mos' ".($order_by == 'rtp_audio_in_mos' ? "selected='selected'" : null).">".$text['label-mos']."</option>\n";
			}
			if ($permission['xml_cdr_hangup_cause']) {
				echo "			<option value='hangup_cause' ".($order_by == 'desc' ? "selected='selected'" : null).">".$text['label-hangup_cause']."</option>\n";
			}
			if ($permission['xml_cdr_custom_fields']) {
				if (!empty($_SESSION['cdr']['field']) && is_array($_SESSION['cdr']['field'])) {
					echo "			<option value='' disabled='disabled'></option>\n";
					echo "			<optgroup label=\"".$text['label-custom_cdr_fields']."\">\n";
					foreach ($_SESSION['cdr']['field'] as $field) {
						$array = explode(",", $field);
						$field_name = end($array);
						$field_label = ucwords(str_replace("_", " ", $field_name));
						$field_label = str_replace("Sip", "SIP", $field_label);
						if ($field_name != "destination_number") {
							echo "		<option value='".$field_name."' ".($order_by == $field_name ? "selected='selected'" : null).">".$field_label."</option>\n";
						}
					}
					echo "			</optgroup>\n";
				}
			}
			echo "			</select>\n";
			echo "			<select name='order' class='formfld'>\n";
			echo "				<option value='desc' ".($order == 'desc' ? "selected='selected'" : null).">".$text['label-descending']."</option>\n";
			echo "				<option value='asc' ".($order == 'asc' ? "selected='selected'" : null).">".$text['label-ascending']."</option>\n";
			echo "			</select>\n";
			echo "		</div>\n";
			echo "	</div>\n";

			if ($permission['xml_cdr_search_call_center_queues']) {
				echo "	<div class='form_set'>\n";
				echo "		<div class='label'>\n";
				echo "			".$text['label-call_center_queue']."\n";
				echo "		</div>\n";
				echo "		<div class='field'>\n";
				echo "			<select class='formfld' name='call_center_queue_uuid' id='call_center_queue_uuid'>\n";
				echo "				<option value=''></option>";
				if (is_array($call_center_queues) && @sizeof($call_center_queues) != 0) {
					foreach ($call_center_queues as $row) {
						$selected = ($row['call_center_queue_uuid'] == $call_center_queue_uuid) ? "selected" : null;
						echo "		<option value='".escape($row['call_center_queue_uuid'])."' ".escape($selected).">".((is_numeric($row['queue_extension'])) ? escape($row['queue_extension']." (".$row['queue_name'].")") : escape($row['queue_extension'])." (".escape($row['queue_extension']).")")."</option>";
					}
				}
				echo "			</select>\n";
				echo "		</div>\n";
				echo "	</div>\n";
				unset($sql, $parameters, $call_center_queues, $row, $selected);
			}

			if ($permission['xml_cdr_search_ring_groups']) {
				echo "	<div class='form_set'>\n";
				echo "		<div class='label'>\n";
				echo "			".$text['label-ring_group']."\n";
				echo "		</div>\n";
				echo "		<div class='field'>\n";
				echo "			<select class='formfld' name='ring_group_uuid' id='ring_group_uuid'>\n";
				echo "				<option value=''></option>";
				if (is_array($ring_groups) && @sizeof($ring_groups) != 0) {
					foreach ($ring_groups as $row) {
						$selected = ($row['ring_group_uuid'] == $ring_group_uuid) ? "selected" : null;
						echo "		<option value='".escape($row['ring_group_uuid'])."' ".escape($selected).">".((is_numeric($row['ring_group_extension'])) ? escape($row['ring_group_extension']." (".$row['ring_group_name'].")") : escape($row['ring_group_extension'])." (".escape($row['ring_group_extension']).")")."</option>";
					}
				}
				echo "			</select>\n";
				echo "		</div>\n";
				echo "	</div>\n";
				unset($sql, $parameters, $ring_groups, $row, $selected);
			}
		}

		echo "</div>\n";

		button::$collapse = false;
		echo "<div style='display: flex; justify-content: flex-end; padding-top: 15px; margin-left: 20px; white-space: nowrap;'>";
		if ($permission['xml_cdr_all'] && $_REQUEST['show'] == 'all') {
			echo "<input type='hidden' name='show' value='all'>\n";
		}
		if (!$archive_request && $permission['xml_cdr_search_advanced']) {
			echo button::create(['type'=>'button','label'=>$text['button-advanced_search'],'icon'=>'tools','link'=>"xml_cdr_search.php".($_REQUEST['show'] == 'all' ? '?show=all' : null),'style'=>'margin-right: 15px;']);
		}
		echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','link'=>($archive_request ? 'xml_cdr_archive.php' : 'xml_cdr.php')]);
		echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_save','name'=>'submit']);
		echo "</div>\n";
		echo "</div>\n";
		echo "<br />\n";
		echo "</form>";
	}

//mod paging parameters for inclusion in column sort heading links
	$param = substr($param, 1); //remove leading '&'
	$param = substr($param, 0, strrpos($param, '&order_by=')); //remove trailing order by

//show the results
	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";

	echo "<div class='card'>\n";
	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	$col_count = 0;
	if (!$archive_request && $permission['xml_cdr_delete']) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".(empty($result) ? "style='visibility: hidden;'" : null).">\n";
		echo "	</th>\n";
		$col_count++;
	}

//column headings
 	if ($permission['xml_cdr_direction']) {
		echo "<th class='shrink'>&nbsp;</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_extension']) {
		echo "<th class='shrink'>".$text['label-extension']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_all'] && $_REQUEST['show'] == "all") {
		echo "<th>".$text['label-domain']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_caller_id_name']) {
		echo "<th class='hide-md-dn' style='min-width: 90px;'>".$text['label-caller_id_name']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_caller_id_number']) {
		echo "<th>".$text['label-caller_id_number']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_caller_destination']) {
		echo "<th class='no-wrap hide-md-dn'>".$text['label-caller_destination']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_destination']) {
		echo "<th class='shrink'>".$text['label-destination']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_recording'] && ($permission['xml_cdr_recording_play'] || $permission['xml_cdr_recording_download'])) {
		echo "<th class='center'>".$text['label-recording']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_account_code']) {
		echo "<th class='left'>".$text['label-accountcode']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_custom_fields']) {
		if (isset($_SESSION['cdr']['field']) && is_array($_SESSION['cdr']['field']) && @sizeof($_SESSION['cdr']['field'])) {
			foreach ($_SESSION['cdr']['field'] as $field) {
				$array = explode(",", $field);
				$field_name = end($array);
				$field_label = ucwords(str_replace("_", " ", $field_name));
				$field_label = str_replace("Sip", "SIP", $field_label);
				if ($field_name != "destination_number") {
					echo "<th class='right'>".$field_label."</th>\n";
					$col_count++;
				}
			}
		}
	}
	if ($permission['xml_cdr_start']) {
		echo "<th class='center shrink'>".$text['label-date']."</th>\n";
		echo "<th class='center shrink hide-md-dn'>".$text['label-time']."</th>\n";
		$col_count += 2;
	}
	if ($permission['xml_cdr_codecs']) {
		echo "<th class='center shrink'>".$text['label-codecs']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_tta']) {
		echo "<th class='right hide-md-dn' title=\"".$text['description-tta']."\">".$text['label-tta']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_pdd']) {
		echo "<th class='right hide-md-dn' title=\"".$text['description-pdd']."\">".$text['label-pdd']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_mos']) {
		echo "<th class='center hide-md-dn' title=\"".$text['description-mos']."\">".$text['label-mos']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_duration']) {
		echo "<th class='center hide-sm-dn'>".$text['label-duration']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_status']) {
		echo "<th class='hide-sm-dn shrink'>".$text['label-status']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_hangup_cause']) {
		echo "<th class='hide-sm-dn shrink'>".$text['label-hangup_cause']."</th>\n";
		$col_count++;
	}
	if ($permission['xml_cdr_details']) {
		echo "<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

//show results
	if (is_array($result)) {

		//determine if theme images exist
			$theme_image_path = $_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/";
			$theme_cdr_images_exist = (
				file_exists($theme_image_path."icon_cdr_inbound_answered.png") &&
				file_exists($theme_image_path."icon_cdr_inbound_no_answer.png") &&
				file_exists($theme_image_path."icon_cdr_inbound_voicemail.png") &&
				file_exists($theme_image_path."icon_cdr_inbound_missed.png") &&
				file_exists($theme_image_path."icon_cdr_inbound_cancelled.png") &&
				file_exists($theme_image_path."icon_cdr_inbound_busy.png") &&
				file_exists($theme_image_path."icon_cdr_inbound_failed.png") &&
				file_exists($theme_image_path."icon_cdr_outbound_answered.png") &&
				file_exists($theme_image_path."icon_cdr_outbound_no_answer.png") &&
				file_exists($theme_image_path."icon_cdr_outbound_cancelled.png") &&
				file_exists($theme_image_path."icon_cdr_outbound_busy.png") &&
				file_exists($theme_image_path."icon_cdr_outbound_failed.png") &&
				file_exists($theme_image_path."icon_cdr_local_answered.png") &&
				file_exists($theme_image_path."icon_cdr_local_no_answer.png") &&
				file_exists($theme_image_path."icon_cdr_local_voicemail.png") &&
				file_exists($theme_image_path."icon_cdr_local_cancelled.png") &&
				file_exists($theme_image_path."icon_cdr_local_busy.png") &&
				file_exists($theme_image_path."icon_cdr_local_failed.png")
				) ? true : false;

		//simplify the variables
			$outbound_caller_id_name = $_SESSION['user']['extension'][0]['outbound_caller_id_name'] ?? '';
			$outbound_caller_id_number = $_SESSION['user']['extension'][0]['outbound_caller_id_number'] ?? '';
			$user_extension = $_SESSION['user']['extension'][0]['user'] ?? '';

		//loop through the results
			$x = 0;
			foreach ($result as $index => $row) {

				//set the status
					$status = $row['status'];
					if (empty($row['status'])) {
						//define an array of failed hangup causes
						$failed_array = array(
						"CALL_REJECTED",
						"CHAN_NOT_IMPLEMENTED",
						"DESTINATION_OUT_OF_ORDER",
						"EXCHANGE_ROUTING_ERROR",
						"INCOMPATIBLE_DESTINATION",
						"INVALID_NUMBER_FORMAT",
						"MANDATORY_IE_MISSING",
						"NETWORK_OUT_OF_ORDER",
						"NORMAL_TEMPORARY_FAILURE",
						"NORMAL_UNSPECIFIED",
						"NO_ROUTE_DESTINATION",
						"RECOVERY_ON_TIMER_EXPIRE",
						"REQUESTED_CHAN_UNAVAIL",
						"SUBSCRIBER_ABSENT",
						"SYSTEM_SHUTDOWN",
						"UNALLOCATED_NUMBER"
						);

						//determine the call status
						if ($row['billsec'] > 0) {
							$status = 'answered';
						}
						if ($row['hangup_cause'] == 'NO_ANSWER') {
							$status = 'no_answer';
						}
						if ($row['missed_call'] == '1') {
							$status = 'missed';
						}
						if (substr($row['destination_number'], 0, 3) == '*99') {
							$status = 'voicemail';
						}
						if ($row['hangup_cause'] == 'ORIGINATOR_CANCEL') {
							$status = 'cancelled';
						}
						if ($row['hangup_cause'] == 'USER_BUSY') {
							$status = 'busy';
						}
						if (in_array($row['hangup_cause'], $failed_array)) {
							$status = 'failed';
						}
					}

				//clear previous variables
					unset($record_path, $record_name);

				//get the hangup cause
					$hangup_cause = $row['hangup_cause'];
					$hangup_cause = str_replace("_", " ", $hangup_cause);
					$hangup_cause = strtolower($hangup_cause);
					$hangup_cause = ucwords($hangup_cause);

				//get the duration if null use 0
					$duration = $row['duration'] ?? 0;

				//determine recording properties
					if (!empty($row['record_path']) && !empty($row['record_name']) && $permission['xml_cdr_recording'] && ($permission['xml_cdr_recording_play'] || $permission['xml_cdr_recording_download'])) {
						$record_path = $row['record_path'];
						$record_name = $row['record_name'];
						//$record_name = strtolower(pathinfo($tmp_name, PATHINFO_BASENAME));
						$record_extension = pathinfo($record_name, PATHINFO_EXTENSION);
						switch ($record_extension) {
							case "wav" : $record_type = "audio/wav"; break;
							case "mp3" : $record_type = "audio/mpeg"; break;
							case "ogg" : $record_type = "audio/ogg"; break;
						}
					}

				//set an empty content variable
					$content = '';

				//recording playback
					if ($permission['xml_cdr_recording_play']) {
						$content .= "<tr class='list-row' id='recording_progress_bar_".$row['xml_cdr_uuid']."' style='display: none;' onclick=\"recording_seek(event,'".escape($row['xml_cdr_uuid'])."')\"><td id='playback_progress_bar_background_".escape($row['xml_cdr_uuid'])."' class='playback_progress_bar_background' colspan='".$col_count."'><span class='playback_progress_bar' id='recording_progress_".$row['xml_cdr_uuid']."'></span></td></tr>\n";
						$content .= "<tr class='list-row' style='display: none;'><td></td></tr>\n"; // dummy row to maintain alternating background color
					}
					if ($permission['xml_cdr_details']) {
						$list_row_url = "xml_cdr_details.php?id=".urlencode($row['xml_cdr_uuid']).($_REQUEST['show'] ? "&show=all" : null);
					}
					$content .= "<tr class='list-row' href='".$list_row_url."'>\n";
					if (!$archive_request && $permission['xml_cdr_delete']) {
						$content .= "	<td class='checkbox middle'>\n";
						$content .= "		<input type='checkbox' name='xml_cdrs[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
						$content .= "		<input type='hidden' name='xml_cdrs[$x][uuid]' value='".escape($row['xml_cdr_uuid'])."' />\n";
						$content .= "	</td>\n";
					}

				//determine call result and appropriate icon
					if ($permission['xml_cdr_direction']) {
						$content .= "<td class='middle'>\n";
						if ($theme_cdr_images_exist) {
							if (!empty($row['direction'])) {
								$image_name = "icon_cdr_" . $row['direction'] . "_" . $status;
								if ($row['leg'] == 'b') {
									$image_name .= '_b';
								}
								$image_name .= ".png";
								if (file_exists($theme_image_path.$image_name)) {
									$content .= "<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/".escape($image_name)."' width='16' style='border: none; cursor: help;' title='".$text['label-'.$row['direction']].": ".$text['label-'.$status]. ($row['leg']=='b'?'(b)':'') . "'>\n";
								}
								else { $content .= "&nbsp;"; }
							}
						}
						else { $content .= "&nbsp;"; }
						$content .= "</td>\n";
					}
				//extension
					if ($permission['xml_cdr_extension']) {
						$content .= "	<td class='middle' nowrap='nowrap'>".$row['extension']." ".escape($row['extension_name'])."</td>\n";
					}
				//domain name
					if ($permission['xml_cdr_all'] && $_REQUEST['show'] == "all") {
						$content .= "	<td class='middle'>".$row['domain_name']."</td>\n";
					}
				//caller id name
					if ($permission['xml_cdr_caller_id_name']) {
						$content .= "	<td class='middle overflow hide-md-dn' title=\"".escape($row['caller_id_name'])."\">".escape($row['caller_id_name'])."</td>\n";
					}
				//source
					if ($permission['xml_cdr_caller_id_number']) {
						$content .= "	<td class='middle no-link no-wrap'>";
						$content .= "		<a href=\"javascript:void(0)\" onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode(escape($row['caller_id_name']))."&src_cid_number=".urlencode(escape($row['caller_id_number']))."&dest_cid_name=".urlencode($outbound_caller_id_name)."&dest_cid_number=".urlencode($outbound_caller_id_number)."&src=".urlencode($user_extension)."&dest=".urlencode(escape($row['caller_id_number']))."&rec=false&ringback=us-ring&auto_answer=true');\">\n";
						if (is_numeric($row['caller_id_number'])) {
							$content .= "		".escape(format_phone(substr($row['caller_id_number'], 0, 20))).' ';
						}
						else {
							$content .= "		".escape(substr($row['caller_id_number'], 0, 20)).' ';
						}
						$content .= "		</a>";
						$content .= "	</td>\n";
					}
				//caller destination
					if ($permission['xml_cdr_caller_destination']) {
						$content .= "	<td class='middle no-link no-wrap hide-md-dn'>";
						$content .= "		<a href=\"javascript:void(0)\" onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode(escape($row['caller_id_name']))."&src_cid_number=".urlencode(escape($row['caller_id_number']))."&dest_cid_name=".urlencode($outbound_caller_id_name)."&dest_cid_number=".urlencode($outbound_caller_id_number)."&src=".urlencode($user_extension)."&dest=".urlencode(escape($row['caller_destination']))."&rec=false&ringback=us-ring&auto_answer=true');\">\n";
						if (is_numeric($row['caller_destination'])) {
							$content .= "		".escape(format_phone(substr($row['caller_destination'], 0, 20))).' ';
						}
						else {
							$content .= "		".escape(substr($row['caller_destination'] ?? '', 0, 20)).' ';
						}
						$content .= "		</a>";
						$content .= "	</td>\n";
					}
				//destination
					if ($permission['xml_cdr_destination']) {
						$content .= "	<td class='middle no-link no-wrap'>";
						$content .= "		<a href=\"javascript:void(0)\" onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode(escape($row['destination_number']))."&src_cid_number=".urlencode(escape($row['destination_number']))."&dest_cid_name=".urlencode($outbound_caller_id_name)."&dest_cid_number=".urlencode($outbound_caller_id_number)."&src=".urlencode($user_extension)."&dest=".urlencode(escape($row['destination_number']))."&rec=false&ringback=us-ring&auto_answer=true');\">\n";
						if (is_numeric($row['destination_number'])) {
							$content .= escape(format_phone(substr($row['destination_number'], 0, 20)))."\n";
						}
						else {
							$content .= escape(substr($row['destination_number'], 0, 20))."\n";
						}
						$content .= "		</a>\n";
						$content .= "	</td>\n";
					}
				//recording
					if ($permission['xml_cdr_recording'] && ($permission['xml_cdr_recording_play'] || $permission['xml_cdr_recording_download'])) {
						if (!empty($record_path) || !empty($record_name)) {
							$content .= "	<td class='middle button center no-link no-wrap'>";
							if ($permission['xml_cdr_recording_play']) {
								$content .= 	"<audio id='recording_audio_".escape($row['xml_cdr_uuid'])."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".escape($row['xml_cdr_uuid'])."')\" onended=\"recording_reset('".escape($row['xml_cdr_uuid'])."');\" src=\"download.php?id=".escape($row['xml_cdr_uuid'])."\" type='".escape($record_type)."'></audio>";
								$content .= button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$_SESSION['theme']['button_icon_play'],'id'=>'recording_button_'.escape($row['xml_cdr_uuid']),'onclick'=>"recording_play('".escape($row['xml_cdr_uuid'])."')"]);
							}
							if ($permission['xml_cdr_recording_download']) {
								$content .= button::create(['type'=>'button','title'=>$text['label-download'],'icon'=>$_SESSION['theme']['button_icon_download'],'link'=>"download.php?id=".urlencode($row['xml_cdr_uuid'])."&t=bin"]);
							}
							$content .= 	"</td>\n";
						}
						else {
							$content .= "	<td>&nbsp;</td>\n";
						}
					}
				//account code
					if ($permission['xml_cdr_account_code']) {
						$content .= "	<td class='middle no-link no-wrap'>";
						$content .= 		$row['accountcode'];
						$content .= "	</td>\n";
					}
				//custom cdr fields
					if ($permission['xml_cdr_custom_fields']) {
						if (!empty($_SESSION['cdr']['field']) && is_array($_SESSION['cdr']['field'])) {
							foreach ($_SESSION['cdr']['field'] as $field) {
								$array = explode(",", $field);
								$field_name = $array[count($array) - 1];
								if ($field_name != "destination_number") {
									$content .= "	<td class='middle center no-wrap'>".escape($row[$field_name])."</td>\n";
								}
							}
						}
					}
				//start
					if ($permission['xml_cdr_start']) {
						$content .= "	<td class='middle right no-wrap'>".$row['start_date_formatted']."</td>\n";
						$content .= "	<td class='middle right no-wrap hide-md-dn'>".$row['start_time_formatted']."</td>\n";
					}
				//codec
					if ($permission['xml_cdr_codecs']) {
						$content .= "	<td class='middle right no-wrap'>".($row['read_codec'] ?? '').' / '.($row['write_codec'] ?? '')."</td>\n";
					}
				//tta (time to answer)
					if ($permission['xml_cdr_tta']) {
						$content .= "	<td class='middle right hide-md-dn'>".(!empty($row['tta']) && $row['tta'] >= 0 ? $row['tta']."s" : "&nbsp;")."</td>\n";
					}
				//pdd (post dial delay)
					if ($permission['xml_cdr_pdd']) {
						$content .= "	<td class='middle right hide-md-dn'>".number_format(escape($row['pdd_ms'])/1000,2)."s</td>\n";
					}
				//mos (mean opinion score)
					if ($permission['xml_cdr_mos']) {
						if(!empty($row['rtp_audio_in_mos']) && is_numeric($row['rtp_audio_in_mos'])) {
							$title = " title='".$text['label-mos_score-'.round($row['rtp_audio_in_mos'])]."'";
							$value = $row['rtp_audio_in_mos'];
						}
						$content .= "	<td class='middle center hide-md-dn' ".($title ?? '').">".($value ?? '')."</td>\n";
					}
				//duration
					if ($permission['xml_cdr_duration']) {
						$content .= "	<td class='middle center hide-sm-dn'>".gmdate("G:i:s", $duration)."</td>\n";
					}
				//call result/status
					if ($permission['xml_cdr_status']) {
						$content .= "	<td class='middle no-wrap hide-sm-dn'><a href='".$list_row_url."'>".escape($text['label-'.$status] ?? '')."</a></td>\n";
					}
				//hangup cause
					if ($permission['xml_cdr_hangup_cause']) {
						$content .= "	<td class='middle no-wrap hide-sm-dn'><a href='".$list_row_url."'>".escape($hangup_cause)."</a></td>\n";
					}
					$content .= "</tr>\n";
				//show the leg b only to those with the permission
					if ($row['leg'] == 'a') {
						echo $content;
					}
					else if ($row['leg'] == 'b' && $permission['xml_cdr_b_leg']) {
						echo $content;
					}
					unset($content);

				$x++;
			}
			unset($sql, $result, $row_count);
	}

	echo "</table>\n";
	echo "</div>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//store last search/sort query parameters in session
	$_SESSION['xml_cdr']['last_query'] = $_SERVER["QUERY_STRING"];

//show the footer
	require_once "resources/footer.php";

?>
