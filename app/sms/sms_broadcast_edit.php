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
	Portions created by the Initial Developer are Copyright (C) 2008-2019
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('sms_broadcast_edit')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the action with add or update
	if (is_uuid($_REQUEST["id"])) {
		$action = "update";
		$sms_broadcast_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//function to Upload CSV/TXT file
	function upload_file($sql, $sms_broadcast_phone_numbers) {
		$upload_csv = $sql = '';
		if (isset($_FILES['sms_broadcast_phone_numbers_file']) && !empty($_FILES['sms_broadcast_phone_numbers_file']) && $_FILES['sms_broadcast_phone_numbers_file']['size'] > 0) {
			$filename=$_FILES["sms_broadcast_phone_numbers_file"]["tmp_name"];
			$file_extension = array('application/octet-stream','application/vnd.ms-excel','text/plain','text/csv','text/tsv');
			if (in_array($_FILES['sms_broadcast_phone_numbers_file']['type'],$file_extension)) {
				$file = fopen($filename, "r");
				$count = 0;
				while (($getData = fgetcsv($file, 0, "\n")) !== FALSE)
				{
					$count++;
					if ($count == 1) { continue; }
					$getData = preg_split('/[ ,|]/', $getData[0], null, PREG_SPLIT_NO_EMPTY);
					$separator = $getData[0];
					$separator .= (isset($getData[1]) && $getData[1] != '')? '|'.$getData[1] : '';
					$separator .= (isset($getData[2]) && $getData[2] != '')? ','.$getData[2] : '';
					$separator .= '\n';
					$upload_csv .= $separator;
				}
				 fclose($file);
			}
			else {
				return array('code'=>false,'sql'=>'');
			}
		}
		if (!empty($sms_broadcast_phone_numbers) && !empty($upload_csv)) {
			$sql .= $sms_broadcast_phone_numbers.'\n'.$upload_csv;
		}
		elseif (empty($sms_broadcast_phone_numbers) && !empty($upload_csv)) {
			$sql .= $upload_csv;
		}
		else {
			$sql .= $sms_broadcast_phone_numbers;
		}
		return array('code'=>true,'sql'=> $sql);
	}

//get the http post variables and set them to php variables
	if (count($_POST)>0) {
		$sms_broadcast_name = $_POST["sms_broadcast_name"];
		$sms_broadcast_caller_id_number = $_POST["sms_broadcast_caller_id_number"];
		$sms_broadcast_phone_numbers = $_POST["sms_broadcast_phone_numbers"];
		$sms_broadcast_destination_data = $_POST["sms_broadcast_destination_data"];
		$sms_broadcast_description = $_POST["sms_broadcast_description"];
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	//delete the call broadcast
		if (permission_exists('sms_broadcast_delete')) {
			if ($_POST['action'] == 'delete' && is_uuid($sms_broadcast_uuid)) {
				//prepare
					$sms_broadcast[0]['checked'] = 'true';
					$sms_broadcast[0]['uuid'] = $sms_broadcast_uuid;
				//delete
					$obj = new sms_broadcast;
					$obj->delete($sms_broadcast);
				//redirect
					header('Location: sms_broadcast.php');
					exit;
			}
		}

	$msg = '';
	if ($action == "update") {
		$sms_broadcast_uuid = $_POST["sms_broadcast_uuid"];
	}

	//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: sms_broadcast.php');
			exit;
		}

	//check for all required data
		if (strlen($sms_broadcast_name) == 0) { $msg .= "".$text['confirm-name']."<br>\n"; }
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

	//add or update the database
	if ($_POST["persistformvar"] != "true") {

		//prep insert
			if ($action == "add" && permission_exists('sms_broadcast_add')) {
				//begin insert array
					$sms_broadcast_uuid = uuid();
					$array['sms_broadcast'][0]['sms_broadcast_uuid'] = $sms_broadcast_uuid;

				//set message
					message::add($text['confirm-add']);

				//set return url on error
					$error_return_url = "sms_broadcast_edit.php";
			}

		//prep update
			if ($action == "update" && permission_exists('sms_broadcast_edit')) {
				//begin update array
					$array['sms_broadcast'][0]['sms_broadcast_uuid'] = $sms_broadcast_uuid;

				//set message
					message::add($text['confirm-update']);

				//set return url on error
					$error_return_url = "sms_broadcast_edit.php?id=".urlencode($_GET['id']);
			}

		//execute
			if (is_array($array) && @sizeof($array) != 0) {

				//add file selection and download sample
					$file_res = upload_file($sql, $sms_broadcast_phone_numbers);
					if ($file_res['code'] != true) {
						$_SESSION["message_mood"] = "negative";
						$_SESSION["message"] = $text['file-error'];
						header("Location: ".$error_return_url);
						exit;
					}
					$sms_broadcast_phone_numbers = $file_res['sql'];

				//common array items
					$array['sms_broadcast'][0]['domain_uuid'] = $domain_uuid;
					$array['sms_broadcast'][0]['sms_broadcast_name'] = $sms_broadcast_name;
					$array['sms_broadcast'][0]['sms_broadcast_caller_id_number'] = $sms_broadcast_caller_id_number;
					$array['sms_broadcast'][0]['sms_broadcast_phone_numbers'] = $sms_broadcast_phone_numbers;
					$array['sms_broadcast'][0]['sms_broadcast_destination_data'] = $sms_broadcast_destination_data;
					$array['sms_broadcast'][0]['sms_broadcast_description'] = $sms_broadcast_description;

				//execute
					$database = new database;
					$database->app_name = 'sms';
					$database->app_uuid = 'f1381f06-1d33-11e6-b6ba-3e1d05defe78';
					$database->save($array);
					//print_r($database);
					//die();
					unset($array);

				//redirect
					header("Location: sms_broadcast.php");
					exit;

			}

	}
}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$sms_broadcast_uuid = $_GET["id"];
		$sql = "select * from v_sms_broadcast ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and sms_broadcast_uuid = :sms_broadcast_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['sms_broadcast_uuid'] = $sms_broadcast_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$sms_broadcast_name = $row["sms_broadcast_name"];
			$sms_broadcast_caller_id_number = $row["sms_broadcast_caller_id_number"];
			$sms_broadcast_phone_numbers = $row["sms_broadcast_phone_numbers"];
			$sms_broadcast_destination_data = $row["sms_broadcast_destination_data"];
			$sms_broadcast_description = $row["sms_broadcast_description"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//begin header
	$document['title'] = $text['title-call_broadcast'];
	require_once "resources/header.php";

//begin content
	echo "<form name='frm' id='frm' method='post' enctype='multipart/form-data'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-call_broadcast']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'sms_broadcast.php']);
	if ($action == "update") {
		//echo button::create(['type'=>'button','label'=>$text['button-start'],'icon'=>$_SESSION['theme']['button_icon_start'],'style'=>'margin-left: 15px;','link'=>'call_broadcast_send.php?id='.urlencode($sms_broadcast_uuid)]);
		//echo button::create(['type'=>'button','label'=>$text['button-stop'],'icon'=>$_SESSION['theme']['button_icon_stop'],'link'=>'call_broadcast_stop.php?id='.urlencode($sms_broadcast_uuid)]);
		if (permission_exists('sms_broadcast_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update' && permission_exists('sms_broadcast_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sms_broadcast_name' maxlength='255' value=\"".escape($sms_broadcast_name)."\" required='required'>\n";
	echo "<br />\n";
	echo "".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-callerid-number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='sms_broadcast_caller_id_number' maxlength='255' min='0' step='1' value=\"".escape($sms_broadcast_caller_id_number)."\">\n";
	echo "<br />\n";
	echo "".$text['description-caller-id-number']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-message']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sms_broadcast_destination_data' maxlength='255' value=\"".escape($sms_broadcast_destination_data)."\">\n";
	echo "<br />\n";
	echo "".$text['description-message']." <br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-phone']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	echo "	<textarea class='formfld' style='width: 300px; height: 200px;' type='text' name='sms_broadcast_phone_numbers' placeholder=\"".$text['label-list_example']."\">".str_replace('\n', "\n", $sms_broadcast_phone_numbers)."</textarea>";
	echo "<br><br>";
	echo " <input type='file' name='sms_broadcast_phone_numbers_file' accept='.csv,.txt' style=\"display:inline-block;\"><a href='sample.csv' download><i class='fas fa-cloud-download-alt' style='margin-right: 5px;'></i>".$text['label-sample_file']."</a>";
	echo "<br /><br />";

	echo "".$text['description-phone']." <br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='sms_broadcast_description' maxlength='255' value=\"".escape($sms_broadcast_description)."\">\n";
	echo "<br />\n";
	echo "".$text['description-info']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='sms_broadcast_uuid' value='".escape($sms_broadcast_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>