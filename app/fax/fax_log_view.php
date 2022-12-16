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

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('fax_log_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//validate the uuids
	if (is_uuid($_REQUEST["id"])) {
		$fax_log_uuid = $_REQUEST["id"];
	}
	if (is_uuid($_REQUEST["fax_uuid"])) {
		$fax_uuid = $_REQUEST["fax_uuid"];
	}

//process the http post data by submitted action
	if ($_POST['action'] != '' && is_uuid($fax_log_uuid) && is_uuid($fax_uuid)) {
		$array[0]['checked'] = 'true';
		$array[0]['uuid'] = $fax_log_uuid;

		switch ($_POST['action']) {
			case 'delete':
				if (permission_exists('fax_log_delete')) {
					$obj = new fax;
					$obj->fax_uuid = $fax_uuid;
					$obj->delete_logs($array);
				}
				break;
		}

		header('Location: fax_logs.php?id='.urlencode($fax_uuid));
		exit;
	}

//pre-populate the form
	if (is_uuid($fax_log_uuid) && is_uuid($fax_uuid)) {
		$sql = "select * from v_fax_logs ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and fax_log_uuid = :fax_log_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['fax_log_uuid'] = $fax_log_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$fax_log_uuid = $row["fax_log_uuid"];
			$fax_success = $row["fax_success"];
			$fax_result_code = $row["fax_result_code"];
			$fax_result_text = $row["fax_result_text"];
			$fax_file = $row["fax_file"];
			$fax_ecm_used = $row["fax_ecm_used"];
			$fax_local_station_id = $row["fax_local_station_id"];
			$fax_document_transferred_pages = $row["fax_document_transferred_pages"];
			$fax_document_total_pages = $row["fax_document_total_pages"];
			$fax_image_resolution = $row["fax_image_resolution"];
			$fax_image_size = $row["fax_image_size"];
			$fax_bad_rows = $row["fax_bad_rows"];
			$fax_transfer_rate = $row["fax_transfer_rate"];
			$fax_retry_attempts = $row["fax_retry_attempts"];
			$fax_retry_limit = $row["fax_retry_limit"];
			$fax_retry_sleep = $row["fax_retry_sleep"];
			$fax_uri = $row["fax_uri"];
			$fax_date = $row["fax_date"];
			$fax_epoch = $row["fax_epoch"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-fax_logs'];
	require_once "resources/header.php";

//show the content
	echo "<form method='post' name='frm' id='frm'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-fax_log']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'fax_logs.php?id='.urlencode($fax_uuid)]);
	if (permission_exists('fax_log_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('fax_log_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_success']."</td>\n";
	echo "<td width='70%' class='vtable'>".escape($fax_success)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_result_code']."</td>\n";
	echo "<td class='vtable'>".escape($fax_result_code)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_result_text']."</td>\n";
	echo "<td class='vtable'>".escape($fax_result_text)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_file']."</td>\n";
	echo "<td class='vtable'>".escape($fax_file)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_ecm_used']."</td>\n";
	echo "<td class='vtable'>".escape($fax_ecm_used)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_local_station_id']."</td>\n";
	echo "<td class='vtable'>".escape($fax_local_station_id)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_document_transferred_pages']."</td>\n";
	echo "<td class='vtable'>".$fax_document_transferred_pages."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_document_total_pages']."</td>\n";
	echo "<td class='vtable'>".escape($fax_document_total_pages)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_image_resolution']."</td>\n";
	echo "<td class='vtable'>".escape($fax_image_resolution)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_image_size']."</td>\n";
	echo "<td class='vtable'>".escape($fax_image_size)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_bad_rows']."</td>\n";
	echo "<td class='vtable'>".escape($fax_bad_rows)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_transfer_rate']."</td>\n";
	echo "<td class='vtable'>".escape($fax_transfer_rate)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_retry_attempts']."</td>\n";
	echo "<td class='vtable'>".escape($fax_retry_attempts)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_retry_limit']."</td>\n";
	echo "<td class='vtable'>".escape($fax_retry_limit)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_retry_sleep']."</td>\n";
	echo "<td class='vtable'>".escape($fax_retry_sleep)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_uri']."</td>\n";
	echo "<td class='vtable'>".escape($fax_uri)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_date']."</td>\n";
	echo "<td class='vtable'>".escape($fax_date)."</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' nowrap='nowrap'>".$text['label-fax_epoch']."</td>\n";
	echo "<td class='vtable'>".escape($fax_epoch)."</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='id' value='".escape($fax_log_uuid)."'>\n";
	echo "<input type='hidden' name='fax_uuid' value='".escape($fax_uuid)."'>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>