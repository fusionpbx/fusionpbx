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
	if (permission_exists('call_broadcast_edit')) {
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
		$call_broadcast_uuid = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//function to Upload CSV/TXT file
	function upload_file($sql, $broadcast_phone_numbers) {
		$upload_csv = $sql = '';
		if (isset($_FILES['broadcast_phone_numbers_file']) && !empty($_FILES['broadcast_phone_numbers_file']) && $_FILES['broadcast_phone_numbers_file']['size'] > 0) {
			$filename=$_FILES["broadcast_phone_numbers_file"]["tmp_name"];
			$file_extension = array('application/octet-stream','application/vnd.ms-excel','text/plain','text/csv','text/tsv');
			if (in_array($_FILES['broadcast_phone_numbers_file']['type'],$file_extension)) {
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
		if (!empty($broadcast_phone_numbers) && !empty($upload_csv)) {
			$sql .= $broadcast_phone_numbers.'\n'.$upload_csv;
		}
		elseif (empty($broadcast_phone_numbers) && !empty($upload_csv)) {
			$sql .= $upload_csv;
		}
		else {
			$sql .= $broadcast_phone_numbers;
		}
		return array('code'=>true,'sql'=> $sql);
	}

//get the http post variables and set them to php variables
	if (count($_POST)>0) {
		$broadcast_name = $_POST["broadcast_name"];
		$broadcast_start_time = $_POST["broadcast_start_time"];
		$broadcast_timeout = $_POST["broadcast_timeout"];
		$broadcast_concurrent_limit = $_POST["broadcast_concurrent_limit"];
		$broadcast_caller_id_name = $_POST["broadcast_caller_id_name"];
		$broadcast_caller_id_number = $_POST["broadcast_caller_id_number"];
		$broadcast_destination_type = $_POST["broadcast_destination_type"];
		$broadcast_phone_numbers = $_POST["broadcast_phone_numbers"];
		$broadcast_avmd = $_POST["broadcast_avmd"];
		$broadcast_destination_data = $_POST["broadcast_destination_data"];
		$broadcast_description = $_POST["broadcast_description"];
		$broadcast_toll_allow = $_POST["broadcast_toll_allow"];

		if (if_group("superadmin")) {
			$broadcast_accountcode = $_POST["broadcast_accountcode"];
		}
		else if (if_group("admin") && file_exists($_SERVER["PROJECT_ROOT"]."/app/billing/app_config.php")){
			$sql = "select count(*) ";
			$sql .= "from v_billings ";
			$sql .= "where domain_uuid = :domain_uuid ";
			$sql .= "and type_value = :type_value ";
			$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
			$parameters['type_value'] = $_POST['accountcode'];
			$database = new database;
			$num_rows = $database->select($sql, $parameters, 'column');
			$broadcast_accountcode = $num_rows > 0 ? $_POST["broadcast_accountcode"] : $_SESSION['domain_name'];
			unset($sql, $parameters, $num_rows);
		}
		else{
			$broadcast_accountcode = $_SESSION['domain_name'];
		}
	}

if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

	//delete the call broadcast
		if (permission_exists('call_broadcast_delete')) {
			if ($_POST['action'] == 'delete' && is_uuid($call_broadcast_uuid)) {
				//prepare
					$call_broadcasts[0]['checked'] = 'true';
					$call_broadcasts[0]['uuid'] = $call_broadcast_uuid;
				//delete
					$obj = new call_broadcast;
					$obj->delete($call_broadcasts);
				//redirect
					header('Location: call_broadcast.php');
					exit;
			}
		}

	$msg = '';
	if ($action == "update") {
		$call_broadcast_uuid = $_POST["call_broadcast_uuid"];
	}

	//validate the token
		$token = new token;
		if (!$token->validate($_SERVER['PHP_SELF'])) {
			message::add($text['message-invalid_token'],'negative');
			header('Location: call_broadcast.php');
			exit;
		}

	//check for all required data
		if (strlen($broadcast_name) == 0) { $msg .= "".$text['confirm-name']."<br>\n"; }
		//if (strlen($broadcast_description) == 0) { $msg .= "Please provide: Description<br>\n"; }
		//if (strlen($broadcast_timeout) == 0) { $msg .= "Please provide: Timeout<br>\n"; }
		//if (strlen($broadcast_concurrent_limit) == 0) { $msg .= "Please provide: Concurrent Limit<br>\n"; }
		//if (strlen($recording_uuid) == 0) { $msg .= "Please provide: Recording<br>\n"; }
		//if (strlen($broadcast_caller_id_name) == 0) { $msg .= "Please provide: Caller ID Name<br>\n"; }
		//if (strlen($broadcast_caller_id_number) == 0) { $msg .= "Please provide: Caller ID Number<br>\n"; }
		//if (strlen($broadcast_destination_type) == 0) { $msg .= "Please provide: Type<br>\n"; }
		//if (strlen($broadcast_phone_numbers) == 0) { $msg .= "Please provide: Phone Number List<br>\n"; }
		//if (strlen($broadcast_avmd) == 0) { $msg .= "Please provide: Voicemail Detection<br>\n"; }
		//if (strlen($broadcast_destination_data) == 0) { $msg .= "Please provide: Destination<br>\n"; }
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
			if ($action == "add" && permission_exists('call_broadcast_add')) {
				//begin insert array
					$call_broadcast_uuid = uuid();
					$array['call_broadcasts'][0]['call_broadcast_uuid'] = $call_broadcast_uuid;

				//set message
					message::add($text['confirm-add']);

				//set return url on error
					$error_return_url = "call_broadcast_edit.php";
			}

		//prep update
			if ($action == "update" && permission_exists('call_broadcast_edit')) {
				//begin update array
					$array['call_broadcasts'][0]['call_broadcast_uuid'] = $call_broadcast_uuid;

				//set message
					message::add($text['confirm-update']);

				//set return url on error
					$error_return_url = "call_broadcast_edit.php?id=".urlencode($_GET['id']);
			}

		//execute
			if (is_array($array) && @sizeof($array) != 0) {

				//add file selection and download sample
					$file_res = upload_file($sql, $broadcast_phone_numbers);
					if ($file_res['code'] != true) {
						$_SESSION["message_mood"] = "negative";
						$_SESSION["message"] = $text['file-error'];
						header("Location: ".$error_return_url);
						exit;
					}
					$broadcast_phone_numbers = $file_res['sql'];

				//common array items
					$array['call_broadcasts'][0]['domain_uuid'] = $domain_uuid;
					$array['call_broadcasts'][0]['broadcast_name'] = $broadcast_name;
					$array['call_broadcasts'][0]['broadcast_start_time'] = $broadcast_start_time;
					$array['call_broadcasts'][0]['broadcast_timeout'] = strlen($broadcast_timeout) != 0 ? $broadcast_timeout : null;
					$array['call_broadcasts'][0]['broadcast_concurrent_limit'] = strlen($broadcast_concurrent_limit) != 0 ? $broadcast_concurrent_limit : null;
					$array['call_broadcasts'][0]['broadcast_caller_id_name'] = $broadcast_caller_id_name;
					$array['call_broadcasts'][0]['broadcast_caller_id_number'] = $broadcast_caller_id_number;
					$array['call_broadcasts'][0]['broadcast_destination_type'] = $broadcast_destination_type;
					$array['call_broadcasts'][0]['broadcast_phone_numbers'] = $broadcast_phone_numbers;
					$array['call_broadcasts'][0]['broadcast_avmd'] = $broadcast_avmd;
					$array['call_broadcasts'][0]['broadcast_destination_data'] = $broadcast_destination_data;
					$array['call_broadcasts'][0]['broadcast_accountcode'] = $broadcast_accountcode;
					$array['call_broadcasts'][0]['broadcast_description'] = $broadcast_description;
					$array['call_broadcasts'][0]['broadcast_toll_allow'] = $broadcast_toll_allow;

				//execute
					$database = new database;
					$database->app_name = 'call_broadcast';
					$database->app_uuid = 'efc11f6b-ed73-9955-4d4d-3a1bed75a056';
					$database->save($array);
					unset($array);

				//redirect
					header("Location: call_broadcast.php");
					exit;

			}

	}
}

//pre-populate the form
	if (count($_GET) > 0 && $_POST["persistformvar"] != "true") {
		$call_broadcast_uuid = $_GET["id"];
		$sql = "select * from v_call_broadcasts ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and call_broadcast_uuid = :call_broadcast_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['call_broadcast_uuid'] = $call_broadcast_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (is_array($row) && @sizeof($row) != 0) {
			$broadcast_name = $row["broadcast_name"];
			$broadcast_start_time = $row["broadcast_start_time"];
			$broadcast_timeout = $row["broadcast_timeout"];
			$broadcast_concurrent_limit = $row["broadcast_concurrent_limit"];
			$broadcast_caller_id_name = $row["broadcast_caller_id_name"];
			$broadcast_caller_id_number = $row["broadcast_caller_id_number"];
			$broadcast_destination_type = $row["broadcast_destination_type"];
			$broadcast_phone_numbers = $row["broadcast_phone_numbers"];
			$broadcast_avmd = $row["broadcast_avmd"];
			$broadcast_destination_data = $row["broadcast_destination_data"];
			$broadcast_accountcode = $row["broadcast_accountcode"];
			$broadcast_description = $row["broadcast_description"];
			$broadcast_toll_allow = $row["broadcast_toll_allow"];
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
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'call_broadcast.php']);
	if ($action == "update") {
		echo button::create(['type'=>'button','label'=>$text['button-start'],'icon'=>$_SESSION['theme']['button_icon_start'],'style'=>'margin-left: 15px;','link'=>'call_broadcast_send.php?id='.urlencode($call_broadcast_uuid)]);
		echo button::create(['type'=>'button','label'=>$text['button-stop'],'icon'=>$_SESSION['theme']['button_icon_stop'],'link'=>'call_broadcast_stop.php?id='.urlencode($call_broadcast_uuid)]);
		if (permission_exists('call_broadcast_delete')) {
			echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-left: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
		}
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update' && permission_exists('call_broadcast_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-name']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_name' maxlength='255' value=\"".escape($broadcast_name)."\" required='required'>\n";
	echo "<br />\n";
	echo "".$text['description-name']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-start_time']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='broadcast_start_time' value=\"".escape($broadcast_start_time)."\">\n";
	echo "<br />\n";
	echo "".$text['description-start_time']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	if (if_group("superadmin")){
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
		echo "    ".$text['label-accountcode']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		if ($action == "add") { $accountcode=$_SESSION['domain_name']; }
		echo "    <input class='formfld' type='text' name='broadcast_accountcode' maxlength='255' value=\"".escape($broadcast_accountcode)."\">\n";
		echo "<br />\n";
		echo $text['description-accountcode']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-timeout']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='broadcast_timeout' maxlength='255' min='1' step='1' value=\"".escape($broadcast_timeout)."\">\n";
	echo "<br />\n";
	echo "".$text['description-timeout']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-concurrent-limit']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='number' name='broadcast_concurrent_limit' maxlength='255' min='1' step='1' value=\"".escape($broadcast_concurrent_limit)."\">\n";
	echo "<br />\n";
	echo "".$text['description-concurrent-limit']."\n";
	echo "</td>\n";
	echo "</tr>\n";


	//echo "<tr>\n";
	//echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	//echo "	Recording\n";
	//echo "</td>\n";
	//echo "<td class='vtable' align='left'>\n";
	//echo "		<select name='recording_uuid' class='formfld'>\n";
	//echo "		<option></option>\n";
	//$sql = "";
	//$sql .= "select * from v_recordings ";
	//$sql .= "where domain_uuid = :domain_uuid ";
	//$parameters['domain_uuid'] = $domain_uuid;
	//$database = new database;
	//$rows = $database->select($sql, $parameters, 'all');
	//if (is_array($rows) && @sizeof($rows) != 0) {
	//	foreach ($rows as $row) {
	//		if ($recording_uuid == $row['recording_uuid']) {
	//			echo "		<option value='".$row['recording_uuid']."' selected='yes'>".escape($row['recordingname'])."</option>\n";
	//		}
	//		else {
	//			echo "		<option value='".$row['recording_uuid']."'>".escape($row['recordingname'])."</option>\n";
	//		}
	//	}
	//}
	//unset($sql, $parameters, $rows, $row);
	//echo "		</select>\n";
	//echo "<br />\n";
	//echo "Recording to play when the call is answered.<br />\n";
	//echo "\n";
	//echo "</td>\n";
	//echo "</tr>\n";

	if (permission_exists("call_broadcast_caller_id")) {
		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-caller-id-name']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='text' name='broadcast_caller_id_name' maxlength='255' value=\"".escape($broadcast_caller_id_name)."\">\n";
		echo "<br />\n";
		echo "".$text['description-caller-id-name']."\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	".$text['label-callerid-number']."\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "	<input class='formfld' type='number' name='broadcast_caller_id_number' maxlength='255' min='0' step='1' value=\"".escape($broadcast_caller_id_number)."\">\n";
		echo "<br />\n";
		echo "".$text['description-caller-id-number']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
/*
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Type\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_destination_type' maxlength='255' value=\"".escape($broadcast_destination_type)."\">\n";
	echo "<br />\n";
	echo "Optional, Destination Type: bridge, transfer, voicemail, conference, fifo, etc.\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	Destination\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_destination_data' maxlength='255' value=\"".escape($broadcast_destination_data)."\">\n";
	echo "<br />\n";
	echo "Optional, send the call to an auto attendant, conference room, or any other destination. <br /><br />\n";
	echo "conference (8khz): 01-\${domain}@default <br />\n";
	echo "bridge (external number): sofia/gateway/gatewayname/12081231234 <br />\n";
	echo "bridge (auto attendant): sofia/internal/5002@\${domain} <br />\n";
	echo "transfer (external number): 12081231234 XML default <br />\n";
	echo "</td>\n";
	echo "</tr>\n";
*/

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-destination']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_destination_data' maxlength='255' value=\"".escape($broadcast_destination_data)."\">\n";
	echo "<br />\n";
	echo "".$text['description-destination']." <br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-phone']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";

	echo "	<textarea class='formfld' style='width: 300px; height: 200px;' type='text' name='broadcast_phone_numbers' placeholder=\"".$text['label-list_example']."\">".str_replace('\n', "\n", $broadcast_phone_numbers)."</textarea>";
	echo "<br><br>";
	echo " <input type='file' name='broadcast_phone_numbers_file' accept='.csv,.txt' style=\"display:inline-block;\"><a href='sample.csv' download><i class='fas fa-cloud-download-alt' style='margin-right: 5px;'></i>".$text['label-sample_file']."</a>";
	echo "<br /><br />";

	echo "".$text['description-phone']." <br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "    ".$text['label-avmd']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "    <select class='formfld' name='broadcast_avmd'>\n";
	echo "    	<option value='false' ".(($broadcast_avmd == "false") ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
	echo "    	<option value='true' ".(($broadcast_avmd == "true") ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
	echo "    </select>\n";
	echo "<br />\n";
	echo "<br />\n";
	echo $text['description-avmd']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-broadcast_toll_allow']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_toll_allow' maxlength='255' value=".escape($broadcast_toll_allow).">\n";
	echo "<br />\n";
	echo $text['description-broadcast_toll_allow']."\n";
	echo "</td>\n";
	echo "</tr>\n";
		
	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap>\n";
	echo "	".$text['label-description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<input class='formfld' type='text' name='broadcast_description' maxlength='255' value=\"".escape($broadcast_description)."\">\n";
	echo "<br />\n";
	echo "".$text['description-info']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";

	if ($action == "update") {
		echo "<input type='hidden' name='call_broadcast_uuid' value='".escape($call_broadcast_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

	/*
	if ($action == "update") {

		echo "<table width='100%' border='0'>\n";
		echo "<tr>\n";
		echo "<td width='50%' nowrap><b>Call Broadcast</b></td>\n";
		echo "<td width='50%' align='right'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<form method='get' name='frm' action='call_broadcast_send.php'>\n";

		echo "<div align='center'>\n";
		echo "<table width='100%'  border='0' cellpadding='6' cellspacing='0'>\n";

		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Category\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		echo "		<select name='user_category' class='formfld'>\n";
		echo "		<option></option>\n";
		$sql = "";
		$sql .= "select distinct user_category as user_category from v_users ";
		//$sql .= "where domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$rows = $database->select($sql, null, 'all');
		if (is_array($rows) && @sizeof($rows) != 0) {
			foreach ($rows as $row) {
				if ($user_category   == $row['user_category']) {
					echo "		<option value='".escape($row['user_category'])."' selected='yes'>".escape($row['user_category'])."</option>\n";
				}
				else {
					echo "		<option value='".escape($row['user_category'])."'>".escape($row['user_category'])."</option>\n";
				}
			}
		}
		unset($sql, $parameters, $rows, $row);
		echo "		</select>\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Group\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		echo "		<select name='group_name' class='formfld'>\n";
		echo "		<option></option>\n";
		$sql = "";
		$sql .= "select * from v_groups ";
		//$sql .= "where domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$rows = $database->select($sql, null, 'all');
		if (is_array($rows) && @sizeof($rows) != 0) {
			foreach ($rows as $row) {
				if ($recording_uuid == $row['group_name']) {
					echo "		<option value='".escape($row['group_name'])."' selected='yes'>".escape($row['group_name'])."</option>\n";
				}
				else {
					echo "		<option value='".escape($row['group_name'])."'>".escape($row['group_name'])."</option>\n";
				}
			}
		}
		unset($sql, $parameters, $rows, $row);
		echo "		</select>\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";


		echo "<tr>\n";
		echo "<td width='30%' class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Gateway\n";
		echo "</td>\n";
		echo "<td width='70%' class='vtable' align='left'>\n";
		echo "		<select name='gateway' class='formfld'>\n";
		echo "		<option></option>\n";
		$sql = "";
		$sql .= "select * from v_gateways ";
		//$sql .= "where domain_uuid = :domain_uuid ";
		//$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$rows = $database->select($sql, null, 'all');
		if (is_array($rows) && @sizeof($rows) != 0) {
			foreach ($rows as $row) {
				if ($gateway == $row['gateway']) {
					echo "		<option value='".escape($row['gateway'])."' selected='yes'>".escape($row['gateway'])."</option>\n";
				}
				else {
					echo "		<option value='".escape($row['gateway'])."'>".escape($row['gateway'])."</option>\n";
				}
			}
		}
		unset($sql, $parameters, $rows, $row);
		echo "		<option value='loopback'>loopback</option>\n";
		echo "		</select>\n";
		echo "<br />\n";
		//echo "zzz.<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";


		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Phone Type\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "		<select name='phonetype1' class='formfld'>\n";
		echo "		<option></option>\n";
		echo "		<option value='phone1'>phone1</option>\n";
		echo "		<option value='phone2'>phone2</option>\n";
		echo "		<option value='cell'>cell</option>\n";
		//echo "		<option value='zzz'>cell</option>\n";
		echo "		</select>\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";


		echo "<tr>\n";
		echo "<td class='vncell' valign='top' align='left' nowrap>\n";
		echo "	Phone Type\n";
		echo "</td>\n";
		echo "<td class='vtable' align='left'>\n";
		echo "		<select name='phonetype2' class='formfld'>\n";
		echo "		<option></option>\n";
		echo "		<option value='phone1'>phone1</option>\n";
		echo "		<option value='phone2'>phone2</option>\n";
		echo "		<option value='cell'>cell</option>\n";
		//echo "		<option value='zzz'>cell</option>\n";
		echo "		</select>\n";
		echo "<br />\n";
		echo "\n";
		echo "</td>\n";
		echo "</tr>\n";


		echo "	<tr>\n";
		echo "		<td colspan='2' align='right'>\n";
		echo "				<input type='hidden' name='call_broadcast_uuid' value='".escape($call_broadcast_uuid)."'>\n";
		echo "				<input type='submit' name='submit' class='btn' value='Send Broadcast'>\n";
		echo "		</td>\n";
		echo "	</tr>";

		echo "</table>";
		echo "</form>";
	}
	*/

//include the footer
	require_once "resources/footer.php";

?>
