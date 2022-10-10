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
	Portions created by the Initial Developer are Copyright (C) 2022
	the Initial Developer. All Rights Reserved.
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('fax_queue_add') || permission_exists('fax_queue_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$fax_queue_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		$fax_uuid = $_POST["fax_uuid"];
		$fax_date = $_POST["fax_date"];
		$hostname = $_POST["hostname"];
		$fax_caller_id_name = $_POST["fax_caller_id_name"];
		$fax_caller_id_number = $_POST["fax_caller_id_number"];
		$fax_number = $_POST["fax_number"];
		$fax_prefix = $_POST["fax_prefix"];
		$fax_email_address = $_POST["fax_email_address"];
		$fax_file = $_POST["fax_file"];
		$fax_status = $_POST["fax_status"];
		$fax_retry_date = $_POST["fax_retry_date"];
		$fax_notify_date = $_POST["fax_notify_date"];
		$fax_retry_count = $_POST["fax_retry_count"];
		$fax_accountcode = $_POST["fax_accountcode"];
		$fax_command = $_POST["fax_command"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: fax_queue.php');
				exit;
			}

		//process the http post data by submitted action
			if ($_POST['action'] != '' && strlen($_POST['action']) > 0) {

				//prepare the array(s)
				//send the array to the database class
				switch ($_POST['action']) {
					case 'copy':
						if (permission_exists('fax_queue_add')) {
							$obj = new database;
							$obj->copy($array);
						}
						break;
					case 'delete':
						if (permission_exists('fax_queue_delete')) {
							$obj = new database;
							$obj->delete($array);
						}
						break;
					case 'toggle':
						if (permission_exists('fax_queue_update')) {
							$obj = new database;
							$obj->toggle($array);
						}
						break;
				}

				//redirect the user
				if (in_array($_POST['action'], array('copy', 'delete', 'toggle') && is_uuid($id))) {
					header('Location: fax_queue_edit.php?id='.$id);
					exit;
				}
			}

		//check for all required data
			$msg = '';
			//if (strlen($fax_uuid) == 0) { $msg .= $text['message-required']." ".$text['label-fax_uuid']."<br>\n"; }
			if (strlen($fax_date) == 0) { $msg .= $text['message-required']." ".$text['label-fax_date']."<br>\n"; }
			if (strlen($hostname) == 0) { $msg .= $text['message-required']." ".$text['label-hostname']."<br>\n"; }
			//if (strlen($fax_caller_id_name) == 0) { $msg .= $text['message-required']." ".$text['label-fax_caller_id_name']."<br>\n"; }
			//if (strlen($fax_caller_id_number) == 0) { $msg .= $text['message-required']." ".$text['label-fax_caller_id_number']."<br>\n"; }
			if (strlen($fax_number) == 0) { $msg .= $text['message-required']." ".$text['label-fax_number']."<br>\n"; }
			//if (strlen($fax_prefix) == 0) { $msg .= $text['message-required']." ".$text['label-fax_prefix']."<br>\n"; }
			//if (strlen($fax_email_address) == 0) { $msg .= $text['message-required']." ".$text['label-fax_email_address']."<br>\n"; }
			if (strlen($fax_file) == 0) { $msg .= $text['message-required']." ".$text['label-fax_file']."<br>\n"; }
			if (strlen($fax_status) == 0) { $msg .= $text['message-required']." ".$text['label-fax_status']."<br>\n"; }
			//if (strlen($fax_retry_date) == 0) { $msg .= $text['message-required']." ".$text['label-fax_retry_date']."<br>\n"; }
			//if (strlen($fax_retry_count) == 0) { $msg .= $text['message-required']." ".$text['label-fax_retry_count']."<br>\n"; }
			//if (strlen($fax_accountcode) == 0) { $msg .= $text['message-required']." ".$text['label-fax_accountcode']."<br>\n"; }
			//if (strlen($fax_command) == 0) { $msg .= $text['message-required']." ".$text['label-fax_command']."<br>\n"; }
			if (strlen($msg) > 0 && strlen($_POST["persistformvar"]) == 0) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}

		//add the fax_queue_uuid
			if (!is_uuid($_POST["fax_queue_uuid"])) {
				$fax_queue_uuid = uuid();
			}

		//prepare the array
			$array['fax_queue'][0]['fax_queue_uuid'] = $fax_queue_uuid;
			$array['fax_queue'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
			//$array['fax_queue'][0]['fax_uuid'] = $fax_uuid;
			$array['fax_queue'][0]['fax_date'] = $fax_date;
			$array['fax_queue'][0]['hostname'] = $hostname;
			$array['fax_queue'][0]['fax_caller_id_name'] = $fax_caller_id_name;
			$array['fax_queue'][0]['fax_caller_id_number'] = $fax_caller_id_number;
			$array['fax_queue'][0]['fax_number'] = $fax_number;
			$array['fax_queue'][0]['fax_prefix'] = $fax_prefix;
			$array['fax_queue'][0]['fax_email_address'] = $fax_email_address;
			$array['fax_queue'][0]['fax_file'] = $fax_file;
			$array['fax_queue'][0]['fax_status'] = $fax_status;
			$array['fax_queue'][0]['fax_retry_date'] = $fax_retry_date;
			$array['fax_queue'][0]['fax_notify_date'] = $fax_notify_date;
			$array['fax_queue'][0]['fax_retry_count'] = $fax_retry_count;
			$array['fax_queue'][0]['fax_accountcode'] = $fax_accountcode;
			$array['fax_queue'][0]['fax_command'] = $fax_command;

		//save the data
			$database = new database;
			$database->app_name = 'fax queue';
			$database->app_uuid = '3656287f-4b22-4cf1-91f6-00386bf488f4';
			$database->save($array);

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				//header('Location: fax_queue.php');
				header('Location: fax_queue_edit.php?id='.urlencode($fax_queue_uuid));
				return;
			}
	}

//pre-populate the form
	if (is_array($_GET) && $_POST["persistformvar"] != "true") {
		$sql = "select ";
		$sql .= " fax_uuid, ";
		$sql .= " fax_date, ";
		$sql .= " hostname, ";
		$sql .= " fax_caller_id_name, ";
		$sql .= " fax_caller_id_number, ";
		$sql .= " fax_number, ";
		$sql .= " fax_prefix, ";
		$sql .= " fax_email_address, ";
		$sql .= " fax_file, ";
		$sql .= " fax_status, ";
		$sql .= " fax_retry_date, ";
		$sql .= " fax_notify_date, ";
		$sql .= " fax_retry_count, ";
		$sql .= " fax_accountcode, ";
		$sql .= " fax_command ";
		$sql .= "from v_fax_queue ";
		$sql .= "where fax_queue_uuid = :fax_queue_uuid ";
		//$sql .= "and domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
		$parameters['fax_queue_uuid'] = $fax_queue_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$fax_uuid = $row["fax_uuid"];
			$fax_date = $row["fax_date"];
			$hostname = $row["hostname"];
			$fax_caller_id_name = $row["fax_caller_id_name"];
			$fax_caller_id_number = $row["fax_caller_id_number"];
			$fax_number = $row["fax_number"];
			$fax_prefix = $row["fax_prefix"];
			$fax_email_address = $row["fax_email_address"];
			$fax_file = $row["fax_file"];
			$fax_status = $row["fax_status"];
			$fax_retry_date = $row["fax_retry_date"];
			$fax_notify_date = $row["fax_notify_date"];
			$fax_retry_count = $row["fax_retry_count"];
			$fax_accountcode = $row["fax_accountcode"];
			$fax_command = $row["fax_command"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-fax_queue'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post' action=''>\n";
	echo "<input class='formfld' type='hidden' name='fax_queue_uuid' value='".escape($fax_queue_uuid)."'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-fax_queue']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','collapse'=>'hide-xs','style'=>'margin-right: 15px;','link'=>'fax_queue.php']);
	if ($action == 'update') {
		if (permission_exists('_add')) {
			echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display: none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
		}
		if (permission_exists('_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none; margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','collapse'=>'hide-xs']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	echo $text['title_description-fax_queue']."\n";
	echo "<br /><br />\n";

	if ($action == 'update') {
		if (permission_exists('fax_queue_add')) {
			echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'copy','onclick'=>"modal_close();"])]);
		}
		if (permission_exists('fax_queue_delete')) {
			echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
		}
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	//echo "	".$text['label-fax_uuid']."\n";
	//echo "</td>\n";
	//echo "<td class='vtable' style='position: relative;' align='left'>\n";
	//echo "  <input class='formfld' type='text' name='fax_uuid' maxlength='255' value='".escape($fax_uuid)."'>\n";
	//echo "<br />\n";
	//echo $text['description-fax_uuid']."\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_date']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_date' maxlength='255' value='".escape($fax_date)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_date']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-hostname']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='hostname' maxlength='255' value='".escape($hostname)."'>\n";
	echo "<br />\n";
	echo $text['description-hostname']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_caller_id_name']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_caller_id_name' maxlength='255' value='".escape($fax_caller_id_name)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_caller_id_name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_caller_id_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_caller_id_number' maxlength='255' value='".escape($fax_caller_id_number)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_caller_id_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_number' maxlength='255' value='".escape($fax_number)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_prefix']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_prefix' maxlength='255' value='".escape($fax_prefix)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_prefix']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_email_address']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_email_address' maxlength='255' value='".escape($fax_email_address)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_email_address']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_file']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_file' maxlength='255' value='".escape($fax_file)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_file']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_status']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_status' maxlength='255' value='".escape($fax_status)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_status']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_retry_date']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_retry_date' maxlength='255' value='".escape($fax_retry_date)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_retry_date']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_notify_date']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_retry_date' maxlength='255' value='".escape($fax_notify_date)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_notify_date']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_retry_count']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "  <input class='formfld' type='text' name='fax_retry_count' maxlength='255' value='".escape($fax_retry_count)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_retry_count']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_accountcode']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='fax_accountcode' maxlength='255' value='".escape($fax_accountcode)."'>\n";
	echo "<br />\n";
	echo $text['description-fax_accountcode']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-fax_command']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<textarea class='formfld' name='fax_command' style='width: 185px; height: 80px;'>".$fax_command."</textarea>\n";
	echo "<br />\n";
	echo $text['description-fax_command']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br /><br />";

	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
