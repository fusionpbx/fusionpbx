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
	Portions created by the Initial Developer are Copyright (C) 2008-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('xml_cdr_details')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//connect to the database
	$database = database::new();

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//add the settings object
	$settings = new settings(["database" => $database, "domain_uuid" => $_SESSION['domain_uuid'], "user_uuid" => $_SESSION['user_uuid']]);
	$transcribe_enabled = $settings->get('transcribe', 'enabled', false);
	$transcribe_engine = $settings->get('transcribe', 'engine', '');
	$call_log_enabled = $settings->get('cdr', 'call_log_enabled', false);
	$summary_style = $settings->get('cdr', 'summary_style', 'horizontal');

//get the http values and set them to a variable
	if (is_uuid($_REQUEST["id"])) {
		$uuid = $_REQUEST["id"];
	}

//get the cdr string from the database
	$sql = "select * from v_xml_cdr ";
	if (permission_exists('xml_cdr_all')) {
		$sql .= "where xml_cdr_uuid  = :xml_cdr_uuid ";
	}
	else {
		$sql .= "where xml_cdr_uuid  = :xml_cdr_uuid ";
		$sql .= "and domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$parameters['xml_cdr_uuid'] = $uuid;
	$row = $database->select($sql, $parameters, 'row');
	if (!empty($row) && is_array($row) && @sizeof($row) != 0) {
		$caller_id_name = trim($row["caller_id_name"] ?? '');
		$caller_id_number = trim($row["caller_id_number"] ?? '');
		$caller_destination = trim($row["caller_destination"] ?? '');
		$destination_number = trim($row["destination_number"] ?? '');
		$duration = trim($row["billsec"] ?? '');
		$missed_call = trim($row["missed_call"] ?? '');
		$start_stamp = trim($row["start_stamp"] ?? '');
		$xml_string = trim($row["xml"] ?? '');
		$json_string = trim($row["json"] ?? '');
		$call_flow = trim($row["call_flow"] ?? '');
		$direction = trim($row["direction"] ?? '');
		$call_direction = trim($row["direction"] ?? '');
		$record_path = trim($row["record_path"] ?? '');
		$record_name = trim($row["record_name"] ?? '');
		$record_transcription = trim($row["record_transcription"] ?? '');
		$status = trim($row["status"] ?? '');
	}
	unset($sql, $parameters, $row);

//transcribe, if enabled
	if (
		!empty($_GET['action']) &&
		$_GET['action'] == 'transcribe' &&
		$transcribe_enabled &&
		!empty($transcribe_engine) &&
		empty($record_transcription) &&
		!empty($record_path) &&
		!empty($record_name) &&
		file_exists($record_path.'/'.$record_name)
		) {
		//add the transcribe object
			$transcribe = new transcribe($settings);
		//audio to text - get the transcription from the audio file
			$transcribe->audio_path = $record_path;
			$transcribe->audio_filename = $record_name;
			$record_transcription = $transcribe->transcribe();
		//build call recording data array
			if (!empty($record_transcription)) {
				$array['xml_cdr'][0]['xml_cdr_uuid'] = $uuid;
				$array['xml_cdr'][0]['record_transcription'] = $record_transcription;
			}
		//update the checked rows
			if (is_array($array) && @sizeof($array) != 0) {

				//add temporary permissions
					$p = permissions::new();
					$p->add('xml_cdr_edit', 'temp');

				//remove record_path, record_name and record_length
					$database->app_name = 'xml_cdr';
					$database->app_uuid = '4a085c51-7635-ff03-f67b-86e834422848';
					$database->save($array, false);
					$message = $database->message;
					unset($array);

				//remove the temporary permissions
					$p->delete('xml_cdr_edit', 'temp');

				//set message
					message::add($text['message-audio_transcribed']);

			}
		//redirect
			header('Location: '.$_SERVER['PHP_SELF'].'?id='.$uuid);
			exit;
	}

//get the cdr json from the database
	if (empty($json_string)) {
		$sql = "select * from v_xml_cdr_json ";
		if (permission_exists('xml_cdr_all')) {
			$sql .= "where xml_cdr_uuid  = :xml_cdr_uuid ";
		}
		else {
			$sql .= "where xml_cdr_uuid  = :xml_cdr_uuid ";
			$sql .= "and domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
		}
		$parameters['xml_cdr_uuid'] = $uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (!empty($row) && is_array($row) && @sizeof($row) != 0) {
			$json_string = trim($row["json"] ?? '');
		}
		unset($sql, $parameters, $row);
	}

//get the cdr flow from the database
	if (empty($call_flow)) {
		$sql = "select * from v_xml_cdr_flow ";
		if (permission_exists('xml_cdr_all')) {
			$sql .= "where xml_cdr_uuid  = :xml_cdr_uuid ";
		}
		else {
			$sql .= "where xml_cdr_uuid  = :xml_cdr_uuid ";
			$sql .= "and domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
		}
		$parameters['xml_cdr_uuid'] = $uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (!empty($row) && is_array($row) && @sizeof($row) != 0) {
			$call_flow = trim($row["call_flow"] ?? '');
		}
		unset($sql, $parameters, $row);
	}

//get the cdr log from the database
	if (permission_exists('xml_cdr_call_log') && $call_log_enabled) {
		$sql = "select * from v_xml_cdr_logs ";
		if (permission_exists('xml_cdr_all')) {
			$sql .= "where xml_cdr_uuid  = :xml_cdr_uuid ";
		}
		else {
			$sql .= "where xml_cdr_uuid  = :xml_cdr_uuid ";
			$sql .= "and domain_uuid = :domain_uuid ";
			$parameters['domain_uuid'] = $domain_uuid;
		}
		$parameters['xml_cdr_uuid'] = $uuid;

		$row = $database->select($sql, $parameters, 'row');
		if (!empty($row) && is_array($row) && @sizeof($row) != 0) {
			$log_content = $row["log_content"];
		}
		unset($sql, $parameters, $row);
	}

//get the format
	if (!empty($xml_string)) {
		$format = "xml";
	}
	if (!empty($json_string)) {
		$format = "json";
	}

//get cdr from the file system
	if ($format != "xml" && $format != "json") {
		$tmp_time = strtotime($start_stamp);
		$tmp_year = date("Y", $tmp_time);
		$tmp_month = date("M", $tmp_time);
		$tmp_day = date("d", $tmp_time);
		$tmp_dir = $_SESSION['switch']['log']['dir'].'/xml_cdr/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
		if (file_exists($tmp_dir.'/'.$uuid.'.json')) {
			$format = "json";
			$json_string = file_get_contents($tmp_dir.'/'.$uuid.'.json');
		}
		if (file_exists($tmp_dir.'/'.$uuid.'.xml')) {
			$format = "xml";
			$xml_string = file_get_contents($tmp_dir.'/'.$uuid.'.xml');
		}
	}

//parse the xml to get the call detail record info
	try {
		if ($format == 'json') {
			$array = json_decode($json_string,true);
			if (is_null($array)) {
				$j = stripslashes($json_string);
				$array = json_decode($j,true);
			}
		}
		if ($format == 'xml') {
			$array = json_decode(json_encode((array)simplexml_load_string($xml_string)),true);
		}
	}
	catch (Exception $e) {
		echo $e->getMessage();
	}

//get the variables
	$xml_cdr_uuid = urldecode($array["variables"]["uuid"]);
	$language = urldecode($array["variables"]["language"] ?? '');
	$start_epoch = urldecode($array["variables"]["start_epoch"]);
	$start_stamp = urldecode($array["variables"]["start_stamp"]);
	$start_uepoch = urldecode($array["variables"]["start_uepoch"]);
	$answer_stamp = urldecode($array["variables"]["answer_stamp"] ?? '');
	$answer_epoch = urldecode($array["variables"]["answer_epoch"]);
	$answer_uepoch = urldecode($array["variables"]["answer_uepoch"]);
	$end_epoch = urldecode($array["variables"]["end_epoch"]);
	$end_uepoch = urldecode($array["variables"]["end_uepoch"]);
	$end_stamp = urldecode($array["variables"]["end_stamp"]);
	//$duration = urldecode($array["variables"]["duration"]);
	$mduration = urldecode($array["variables"]["mduration"]);
	$billsec = urldecode($array["variables"]["billsec"]);
	$billmsec = urldecode($array["variables"]["billmsec"]);
	$bridge_uuid = urldecode($array["variables"]["bridge_uuid"] ?? '');
	$read_codec = urldecode($array["variables"]["read_codec"] ?? '');
	$write_codec = urldecode($array["variables"]["write_codec"] ?? '');
	$remote_media_ip = urldecode($array["variables"]["remote_media_ip"] ?? '');
	$hangup_cause = urldecode($array["variables"]["hangup_cause"]);
	$hangup_cause_q850 = urldecode($array["variables"]["hangup_cause_q850"]);
	$network_address = urldecode($array["variables"]["network_address"] ?? '');
	$outbound_caller_id_name = urldecode($array["variables"]["outbound_caller_id_name"] ?? '');
	$outbound_caller_id_number = urldecode($array["variables"]["outbound_caller_id_number"] ?? '');

//set the time zone
	date_default_timezone_set($settings->get('domain', 'time_zone', 'GMT'));

//create the destinations object
	$destinations = new destinations();

//build the call flow summary array
	$xml_cdr = new xml_cdr(["database" => $database, "settings" => $settings, "destinations" => $destinations]);
	$xml_cdr->domain_uuid = $_SESSION['domain_uuid'];
	$xml_cdr->call_direction = $call_direction; //used to determine when the call is outbound
	$xml_cdr->status = $status; //used to determine when the call is outbound
	if (empty($call_flow)) {
		//get the call flow summary from the xml_cdr_json table
		$xml_cdr->call_details = $array;
		$call_flow_array = $xml_cdr->call_flow();
	}
	else {
		//get the call flow summary from the xml_cdr_flow table
		$call_flow_array = json_decode($call_flow, true);
	}
	//prepares the raw call flow data to be displayed
	$call_flow_summary = $xml_cdr->call_flow_summary($call_flow_array);

//debug information
	if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'true') {
		$i = 0;
		foreach ($call_flow_array as $row) {
			foreach ($row["times"] as $name => $value) {
				if ($value > 0) {
					$call_flow_array[$i]["times"][$name.'stamp'] = date("Y-m-d H:i:s", (int) $value/1000000);
				}
			}
			$i++;
		}
	}

//set the year, month and date
	$tmp_year = date("Y", strtotime($start_stamp));
	$tmp_month = date("M", strtotime($start_stamp));
	$tmp_day = date("d", strtotime($start_stamp));

//set the row style
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

//set the status
	if (empty($status)) {
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
		if ($billsec > 0) {
			$status = 'answered';
		}
		if ($hangup_cause == 'NO_ANSWER') {
			$status = 'no_answer';
		}
		if ($missed_call == '1') {
			$status = 'missed';
		}
		if (substr($destination_number, 0, 3) == '*99') {
			$status = 'voicemail';
		}
		if ($hangup_cause == 'ORIGINATOR_CANCEL') {
			$status = 'cancelled';
		}
		if ($hangup_cause == 'USER_BUSY') {
			$status = 'busy';
		}
		if (in_array($hangup_cause, $failed_array)) {
			$status = 'failed';
		}
	}

//build the summary array
	$summary_array = array();
	$summary_array['direction'] = escape($direction);
	$summary_array['caller_id_name'] = escape($caller_id_name);
	$summary_array['caller_id_number'] = escape($caller_id_number);
	if ($call_direction == 'outbound') {
		$summary_array['outbound_caller_id_name'] = escape($outbound_caller_id_name);
		$summary_array['outbound_caller_id_number'] = escape($outbound_caller_id_number);
	}
	$summary_array['caller_destination'] = escape($caller_destination);
	$summary_array['destination'] = escape($destination_number);
	$summary_array['start'] = escape($start_stamp);
	$summary_array['end'] = escape($end_stamp);
	$summary_array['duration'] = escape(gmdate("G:i:s", (int)$duration));
	if (isset($status)) {
		$summary_array['status'] = escape($status);
	}
	if (permission_exists('xml_cdr_hangup_cause')) {
		$summary_array['hangup_cause'] = escape($hangup_cause);
	}

//get the header
	require_once "resources/header.php";

//page title and description
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td width='30%' align='left' valign='top' nowrap='nowrap'><b>".$text['title2']."</b><br><br></td>\n";
	echo "<td width='70%' align='right' valign='top'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$settings->get('theme', 'button_icon_back'),'link'=>'xml_cdr.php'.(!empty($_SESSION['xml_cdr']['last_query']) ? '?'.urlencode($_SESSION['xml_cdr']['last_query']) : null)]);
	if (permission_exists('xml_cdr_call_log') && $call_log_enabled && isset($log_content) && !empty($log_content)) {
		echo button::create(['type'=>'button','label'=>$text['button-call_log'],'icon'=>$settings->get('theme', 'button_icon_search'),'style'=>'margin-left: 15px;','link'=>'xml_cdr_log.php?id='.$uuid]);
	}
	if ($transcribe_enabled && !empty($transcribe_engine) && empty($record_transcription)) {
		echo button::create(['type'=>'button','label'=>$text['button-transcribe'],'icon'=>'quote-right','id'=>'btn_transcribe','name'=>'btn_transcribe','collapse'=>'hide-xs','style'=>'margin-left: 15px;','onclick'=>"window.location.href='?id=".$uuid."&action=transcribe';"]);
	}
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo "	".$text['description-details']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<br /><br />\n";

//show the content
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td align='left'><b>".$text['label-summary']."</b>&nbsp;</td>\n";
	echo "	<td></td>\n";
	echo "</tr>\n";
	echo "</table>\n";

//show the call summary - vertical
	if ($summary_style == 'vertical') {
		echo "<div class='card'>\n";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<th width='30%'>".$text['label-name']."</th>\n";
		echo "<th width='70%'>".$text['label-value']."</th>\n";
		echo "</tr>\n";
		if (is_array($summary_array)) {
			foreach($summary_array as $name => $value) {
				echo "<tr >\n";
				echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".$text['label-'.$name]."&nbsp;</td>\n";
				echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".$value."&nbsp;</td>\n";
				echo "</tr>\n";
				$c = $c ? 0 : 1;
			}
		}
		echo "</table>";
		echo "</div>\n";
		echo "<br /><br />\n";
	}

//show the call summary - horizontal
	if ($summary_style == 'horizontal') {
		echo "<div class='card'>\n";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<th></th>\n";
		echo "<th>".$text['label-direction']."</th>\n";
		//echo "<th>Language</th>\n";
		//echo "<th>Context</th>\n";
		echo "<th>".$text['label-name']."</th>\n";
		echo "<th>".$text['label-number']."</th>\n";
		echo "<th>".$text['label-destination']."</th>\n";
		echo "<th>".$text['label-start']."</th>\n";
		echo "<th>".$text['label-end']."</th>\n";
		if (permission_exists('xml_cdr_hangup_cause')) {
			echo "<th>".$text['label-hangup_cause']."</th>\n";
		}
		echo "<th>".$text['label-duration']."</th>\n";
		echo "<th align='center'>".$text['label-status']."</th>\n";
		echo "</tr>\n";
		echo "<tr >\n";
		echo "	<td style='width: 0' valign='top' class='".$row_style[$c]."'>\n";
		if (!empty($call_direction)) {
			$image_name = "icon_cdr_" . $call_direction . "_" . $status;
			if ($row['leg'] == 'b') {
				$image_name .= '_b';
			}
			$image_name .= ".png";
			echo "		<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/".escape($image_name)."' width='16' style='border: none; cursor: help;' title='".$text['label-'.$call_direction].": ".$text['label-'.$status]. ($row['leg']=='b'?'(b)':'') . "'>\n";
		}
		echo "	</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'><a href='xml_cdr_details.php?id=".urlencode($uuid)."'>".escape($direction)."</a></td>\n";
		//echo "	<td valign='top' class='".$row_style[$c]."'>".$language."</td>\n";
		//echo "	<td valign='top' class='".$row_style[$c]."'>".$context."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>";
		if (file_exists($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$uuid.'.wav')) {
			//echo "		<a href=\"../recordings/recordings.php?a=download&type=rec&t=bin&filename=".base64_encode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$uuid.'.wav')."\">\n";
			//echo "	  </a>";

			echo "	  <a href=\"javascript:void(0);\" onclick=\"window.open('../recordings/recording_play.php?a=download&type=moh&filename=".urlencode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$uuid.'.wav')."', 'play',' width=420,height=40,menubar=no,status=no,toolbar=no')\">\n";
			//$tmp_file_array = explode("\.",$file);
			echo 	$caller_id_name.' ';
			echo "	  </a>";
		}
		else {
			echo 	$caller_id_name.' ';
		}
		echo "	</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>";
		if (file_exists($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$uuid.'.wav')) {
			echo "		<a href=\"../recordings/recordings.php?a=download&type=rec&t=bin&filename=".urlencode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$uuid.'.wav')."\">\n";
			echo 	escape($caller_id_number).' ';
			echo "	  </a>";
		}
		else {
			echo 	escape($caller_id_number).' ';
		}
		echo "	</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape($destination_number)."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape(date("Y-m-d H:i:s", (int) $start_epoch))."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape(date("Y-m-d H:i:s", (int) $end_epoch))."</td>\n";
		if (permission_exists('xml_cdr_hangup_cause')) {
			echo "	<td valign='top' class='".$row_style[$c]."'>".escape($hangup_cause)."</td>\n";
		}
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape(gmdate("G:i:s", (int)$duration))."</td>\n";
		echo "	<td valign='top' class='".$row_style[$c]."'>".escape($text['label-'.$status])."</td>\n";
		echo "</table>";
		echo "</div>\n";
		echo "<br /><br />\n";
	}

//show the call flow summary
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "	<td align='left'><b>".$text['label-call_flow_summary']."</b>&nbsp;</td>\n";
	echo "	<td></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "<div class='card'>\n";
	echo "	<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<th></th>\n";
	echo "		<th>".$text['label-application']."</th>\n";
	if ($call_direction == 'local' || $call_direction == 'outbound') {
		echo "		<th>".$text['label-source']."</th>\n";
	}
	echo "		<th>".$text['label-destination']."</th>\n";
	echo "		<th>".$text['label-name']."</th>\n";
	echo "		<th>".$text['label-start']."</th>\n";
	echo "		<th>".$text['label-end']."</th>\n";
	echo "		<th>".$text['label-duration']."</th>\n";
	echo "		<th>".$text['label-status']."</th>\n";
	echo "	</tr>\n";
	$i = 1;
	foreach ($call_flow_summary as $row) {
		echo "	<tr>\n";
		echo "		<td style='width: 0; padding-right: 0;' valign='top' class='".$row_style[$c]."'><span class='fa-solid ".$row["application_icon"][$row["application_name"]]."' style='opacity: 0.8;'></span></td>";
		echo "		<td valign='top' class='".$row_style[$c]."'><a href=\"".$row["application_url"]."\">".escape($row["application_label"])."</a></td>\n";
		if ($call_direction == 'local' || $call_direction == 'outbound') {
			echo "		<td valign='top' class='".$row_style[$c]."'><a href=\"".$row["source_url"]."\">".escape($row["source_number"])."</a></td>\n";
		}
		echo "		<td valign='top' class='".$row_style[$c]."'><a href=\"".$row["destination_url"]."\">".escape($row["destination_number"])."</a></td>\n";
		echo "		<td valign='top' class='".$row_style[$c]."'><a href=\"".$row["destination_url"]."\">".escape($row["destination_label"])."</a></td>\n";
		echo "		<td valign='top' class='".$row_style[$c]."'>".escape($row["start_stamp"])."</td>\n";
		echo "		<td valign='top' class='".$row_style[$c]."'>".escape($row["end_stamp"])."</td>\n";
		echo "		<td valign='top' class='".$row_style[$c]."'>".escape($row["duration_formatted"])."</td>\n";
		echo "		<td valign='top' class='".$row_style[$c]."'>".escape($text['label-'.$row["destination_status"]] ?? '')."</td>\n";
		echo "	</tr>\n";

		//alternate $c
		$c = $c ? 0 : 1;

		//increment the row count
		$i++;
	}
	echo "	</table>";
	echo "</div>\n";
	echo "<br /><br />\n";

//call recording
	if (permission_exists('xml_cdr_recording') && !empty($record_path)) {
		//recording properties
		if (!empty($record_name) && permission_exists('xml_cdr_recording') && (permission_exists('xml_cdr_recording_play') || permission_exists('xml_cdr_recording_download'))) {
			$record_extension = pathinfo($record_name, PATHINFO_EXTENSION);
			switch ($record_extension) {
				case "wav" : $record_type = "audio/wav"; break;
				case "mp3" : $record_type = "audio/mpeg"; break;
				case "ogg" : $record_type = "audio/ogg"; break;
			}
		}

		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "	<td align='left'><b>".$text['label-recording']."</b>&nbsp;</td>\n";
		echo "	<td align='right'>\n";
		//controls
		if (!empty($record_path) || !empty($record_name)) {
			echo "<audio id='recording_audio_".escape($xml_cdr_uuid)."' style='display: none;' preload='none' ontimeupdate=\"update_progress('".escape($xml_cdr_uuid)."')\" onended=\"recording_reset('".escape($xml_cdr_uuid)."');\" src=\"download.php?id=".escape($xml_cdr_uuid)."\" type='".escape($record_type)."'></audio>";
			echo button::create(['type'=>'button','title'=>$text['label-play'].' / '.$text['label-pause'],'icon'=>$settings->get('theme', 'button_icon_play'),'label'=>$text['label-play'],'id'=>'recording_button_'.escape($xml_cdr_uuid),'onclick'=>"recording_play('".escape($xml_cdr_uuid)."', null, null, 'true')",'style'=>'margin-bottom: 8px; margin-top: -8px;']);
			if (permission_exists('xml_cdr_recording_download')) {
				echo button::create(['type'=>'button','title'=>$text['label-download'],'icon'=>$settings->get('theme', 'button_icon_download'),'label'=>$text['label-download'],'onclick'=>"window.location.href='download.php?id=".urlencode($xml_cdr_uuid)."&t=bin';",'style'=>'margin-bottom: 8px; margin-top: -8px;']);
			}
		}
		echo "	</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<div class='card'>\n";
		//progress bar
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr class='list-row' id='recording_progress_bar_".$xml_cdr_uuid."' onclick=\"recording_seek(event,'".escape($xml_cdr_uuid)."')\">\n";
				echo "<td id='playback_progress_bar_background_".escape($xml_cdr_uuid)."' class='playback_progress_bar_background' style='padding: 0; background-size: 100% 100% !important;'>\n";
					echo "<span class='playback_progress_bar' id='recording_progress_".$xml_cdr_uuid."'></span>\n";
				echo "</td>\n";
			echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
		echo "<br /><br />\n";
		echo "<script>recording_load('".escape($xml_cdr_uuid)."');</script>\n";
	}

//transcription, if enabled
	if ($transcribe_enabled == 'true' && !empty($transcribe_engine) && !empty($record_transcription)) {
		echo "<b>".$text['label-transcription']."</b><br>\n";
		echo "<div class='card'>\n";
		echo "	<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "	<tr>\n";
		echo "		<th>".$text['label-text']."</th>\n";
		echo "	</tr>\n";
		echo "	<tr >\n";
		echo "		<td valign='top' class='".$row_style[0]."'>".escape($record_transcription)."</td>\n";
		echo "	</tr>\n";
		echo "	</table>";
		echo "</div>\n";
		echo "<br /><br />\n";
	}

//call stats
	if (permission_exists('xml_cdr_call_stats')) {
		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";
		if (!empty($array["call-stats"]) && is_array($array["call-stats"])) {
			if (!empty($array["call-stats"]['audio']) && is_array($array["call-stats"]['audio'])) {
				foreach ($array["call-stats"]['audio'] as $audio_direction => $stat) {
					echo "<table width='95%' border='0' cellpadding='0' cellspacing='0'>\n";
					echo "<tr>\n";
					echo "	<td><b>".$text['label-call-stats'].": ".$audio_direction."</b>&nbsp;</td>\n";
					echo "	<td>&nbsp;</td>\n";
					echo "</tr>\n";
					echo "</table>\n";
					echo "<div class='card'>\n";
					echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
					echo "<tr>\n";
					echo "	<th width='30%'>".$text['label-name']."</th>\n";
					echo "	<th width='70%'>".$text['label-value']."</th>\n";
					echo "</tr>\n";
					foreach ($stat as $key => $value) {
						if (!empty($value) && is_array($value)) {
							echo "<tr >\n";
							echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".escape($key)."</td>\n";
							echo "	<td valign='top' align='left' class='".$row_style[$c]."'>";
							echo "		<table border='0' cellpadding='0' cellspacing='0'>\n";
							foreach ($value as $vk => $arrays) {
								echo "		<tr>\n";
								echo "			<td valign='top' width='15%' class='".$row_style[$c]."'>".$vk."&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
								echo "			<td valign='top'>\n";
									echo "			<table border='0' cellpadding='0' cellspacing='0'>\n";
									if (!empty($arrays) && is_array($arrays)) {
										foreach ($arrays as $k => $v) {
											echo "			<tr>\n";
											echo "				<td valign='top' class='".$row_style[$c]."'>".$k."&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
											echo "				<td valign='top' class='".$row_style[$c]."'>".$v."</td>\n";
											echo "			</tr>\n";
										}
									}
									echo "			</table>\n";
									echo "		<td>\n";
								echo "		</tr>\n";
							}
							echo "		</table>\n";
							echo "	</td>\n";
							echo "</tr>\n";
						}
						else {
							$value =  urldecode($value);
							echo "<tr >\n";
							echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".escape($key)."</td>\n";
							echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($value,75,"\n", true))."&nbsp;</td>\n";
							echo "</tr>\n";
						}
						$c = $c ? 0 : 1;
					}
					echo "</table>\n";
					echo "</div>\n";
					echo "<br /><br />\n";
				}
			}
		}
	}

//channel data loop
	if (permission_exists('xml_cdr_channel_data')) {
		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<td align='left'><b>".$text['label-channel']."</b>&nbsp;</td>\n";
		echo "<td></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<div class='card'>\n";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<th width='30%'>".$text['label-name']."</th>\n";
		echo "<th width='70%'>".$text['label-value']."</th>\n";
		echo "</tr>\n";
		if (is_array($array["channel_data"])) {
			foreach($array["channel_data"] as $key => $value) {
				if (!empty($value)) {
					$value = urldecode($value);
					echo "<tr >\n";
					echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".escape($key)."&nbsp;</td>\n";
					echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($value,75,"\n", TRUE))."&nbsp;</td>\n";
					echo "</tr>\n";
					$c = $c ? 0 : 1;
				}
			}
		}
		echo "</table>";
		echo "</div>\n";
		echo "<br /><br />\n";
	}

//variable loop
	if (permission_exists('xml_cdr_variables')) {
		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "	<td align='left'><b>".$text['label-variables']."</b>&nbsp;</td>\n";
		echo "<td></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<div class='card'>\n";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<th width='30%'>".$text['label-name']."</th>\n";
		echo "<th width='70%'>".$text['label-value']."</th>\n";
		echo "</tr>\n";
		if (is_array($array["variables"])) {
			foreach($array["variables"] as $key => $value) {
				if (is_array($value)) { $value = implode($value); }
				$value = urldecode($value);
				if ($key != "digits_dialed" && $key != "dsn") {
					echo "<tr >\n";
					echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".escape($key)."</td>\n";
					if ($key == "bridge_uuid" || $key == "signal_bond") {
						echo "	<td valign='top' align='left' class='".$row_style[$c]."'>\n";
						echo "		<a href='xml_cdr_details.php?id=".urlencode($value)."'>".escape($value)."</a>&nbsp;\n";
						$tmp_dir = $_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
						$tmp_name = '';
						if (file_exists($tmp_dir.'/'.$value.'.wav')) {
							$tmp_name = $value.".wav";
						}
						else if (file_exists($tmp_dir.'/'.$value.'_1.wav')) {
							$tmp_name = $value."_1.wav";
						}
						else if (file_exists($tmp_dir.'/'.$value.'.mp3')) {
							$tmp_name = $value.".mp3";
						}
						else if (file_exists($tmp_dir.'/'.$value.'_1.mp3')) {
							$tmp_name = $value."_1.mp3";
						}
						if (!empty($tmp_name) && file_exists($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)) {
							echo "	<a href=\"javascript:void(0);\" onclick=\"window.open('../recordings/recording_play.php?a=download&type=moh&filename=".base64_encode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)."', 'play',' width=420,height=150,menubar=no,status=no,toolbar=no')\">\n";
							echo "		play";
							echo "	</a>&nbsp;";
						}
						if (!empty($tmp_name) && file_exists($_SESSION['switch']['recordings']['dir'].'/'.$_SESSION['domain_name'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)) {
							echo "	<a href=\"../recordings/recordings.php?a=download&type=rec&t=bin&filename=".base64_encode("archive/".$tmp_year."/".$tmp_month."/".$tmp_day."/".$tmp_name)."\">\n";
							echo "		download";
							echo "	</a>";
						}
						echo "</td>\n";
					}
					else {
						echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($value,75,"\n", true))."&nbsp;</td>\n";
					}
					echo "</tr>\n";
				}
				$c = $c ? 0 : 1;
			}
		}
		echo "</table>";
		echo "</div>\n";
		echo "<br /><br />\n";
	}

//application log
	if (permission_exists('xml_cdr_application_log')) {
		$c = 0;
		$row_style["0"] = "row_style0";
		$row_style["1"] = "row_style1";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<td align='left'><b>".$text['label-application-log']."</b>&nbsp;</td>\n";
		echo "<td></td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<div class='card'>\n";
		echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
		echo "<tr>\n";
		echo "<th width='30%'>".$text['label-name']."</th>\n";
		echo "<th width='70%'>".$text['label-data']."</th>\n";
		echo "</tr>\n";

		//foreach($array["variables"] as $key => $value) {
		if (is_array($array["app_log"]["application"])) {
			foreach ($array["app_log"]["application"] as $key=>$row) {
				//single app
				if ($key === "@attributes") {
					$app_name = $row["app_name"];
					$app_data = urldecode($row["app_data"]);
				}

				//multiple apps
				else {
					$app_name = $row["@attributes"]["app_name"];
					$app_data = urldecode($row["@attributes"]["app_data"]);
				}
				echo "<tr >\n";
				echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".escape($app_name)."&nbsp;</td>\n";
				echo "	<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($app_data,75,"\n", true))."&nbsp;</td>\n";
				echo "</tr>\n";
				$c = $c ? 0 : 1;
			}
		}
		echo "</table>";
		echo "</div>\n";
		echo "<br /><br />\n";
	}

//call flow
	/*
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";
	if (is_array($call_flow_array)) {
		foreach ($call_flow_array as $row) {
			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "	<td align='left'>\n";

			//attributes
				echo "	<table width='95%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "		<tr>\n";
				echo "			<td><b>".$text['label-call-flow']."</b>&nbsp;</td>\n";
				echo "			<td>&nbsp;</td>\n";
				echo "		</tr>\n";
				echo "	</table>\n";
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "		<tr>\n";
				echo "			<th width='30%'>".$text['label-name']."</th>\n";
				echo "			<th width='70%'>".$text['label-value']."</th>\n";
				echo "		</tr>\n";
				if (is_array($row["@attributes"])) {
					foreach($row["@attributes"] as $key => $value) {
						$value = urldecode($value);
						echo "		<tr>\n";
						echo "				<td valign='top' align='left' class='".$row_style[$c]."'>".escape($key)."&nbsp;</td>\n";
						echo "				<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($value,75,"\n", true))."&nbsp;</td>\n";
						echo "		</tr>\n";
						$c = $c ? 0 : 1;
					}
				}
				echo "		<tr>\n";
				echo "			<td colspan='2'><br /><br /></td>\n";
				echo "		</tr>\n";
				echo "</table>\n";

			//extension attributes
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "		<tr>\n";
				echo "			<td><b>".$text['label-call-flow-2']."</b>&nbsp;</td>\n";
				echo "			<td>&nbsp;</td>\n";
				echo "		</tr>\n";
				echo "</table>\n";
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "		<tr>\n";
				echo "			<th width='30%'>".$text['label-name']."</th>\n";
				echo "			<th width='70%'>".$text['label-value']."</th>\n";
				echo "		</tr>\n";
				if (is_array($row["extension"]["@attributes"])) {
					foreach($row["extension"]["@attributes"] as $key => $value) {
						$value = urldecode($value);
						echo "		<tr >\n";
						echo "			<td valign='top' align='left' class='".$row_style[$c]."'>".escape($key)."&nbsp;</td>\n";
						echo "			<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($value,75,"\n", true))."&nbsp;</td>\n";
						echo "		</tr>\n";
						$c = $c ? 0 : 1;
					}
				}
				echo "		<tr>\n";
				echo "			<td colspan='2'><br /><br /></td>\n";
				echo "		</tr>\n";
				echo "</table>\n";

			//extension application
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "		<tr>\n";
				echo "			<td><b>".$text['label-call-flow-3']."</b>&nbsp;</td>\n";
				echo "			<td>&nbsp;</td>\n";
				echo "		</tr>\n";
				echo "</table>\n";
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "		<tr>\n";
				echo "			<th width='30%'>".$text['label-name']."</th>\n";
				echo "			<th width='70%'>".$text['label-data']."</th>\n";
				echo "		</tr>\n";
				if (!empty($row["extension"]["application"]) && is_array($row["extension"]["application"])) {
					foreach ($row["extension"]["application"] as $key => $tmp_row) {
						if (!is_numeric($key)) {
							$app_name = $tmp_row["app_name"] ?? '';
							$app_data = urldecode($tmp_row["app_data"] ?? '');
						}
						else {
							$app_name = $tmp_row["@attributes"]["app_name"] ?? '';
							$app_data = urldecode($tmp_row["@attributes"]["app_data"] ?? '');
						}
						echo "		<tr >\n";
						echo "			<td valign='top' align='left' class='".$row_style[$c]."'>".escape($app_name)."&nbsp;</td>\n";
						echo "			<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($app_data,75,"\n", true))."&nbsp;</td>\n";
						echo "		</tr>\n";
						$c = $c ? 0 : 1;
					}
				}
				echo "		<tr>\n";
				echo "			<td colspan='2'><br /><br /></td>\n";
				echo "		</tr>\n";
				echo "</table>\n";

			//caller profile
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "		<tr>\n";
				echo "			<td><b>".$text['label-call-flow-4']."</b>&nbsp;</td>\n";
				echo "			<td>&nbsp;</td>\n";
				echo "		</tr>\n";
				echo "</table>\n";
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "		<tr>\n";
				echo "			<th width='30%'>".$text['label-name']."</th>\n";
				echo "			<th width='70%'>".$text['label-value']."</th>\n";
				echo "		</tr>\n";
				if (is_array($row["caller_profile"])) {
					foreach ($row["caller_profile"] as $key => $value) {
						echo "		<tr>\n";
						if ($key != "originatee" && $key != "origination") {
							if (is_array($value)) {
								$value = implode('', $value);
							}
							else {
								$value = urldecode($value);
							}
							echo "			<td valign='top' align='left' class='".$row_style[$c]."'>".escape($key)."&nbsp;</td>\n";
							if ($key == "uuid") {
								echo "			<td valign='top' align='left' class='".$row_style[$c]."'><a href='xml_cdr_details.php?id=".urlencode($value)."'>".escape($value)."</a>&nbsp;</td>\n";
							}
							else {
								echo "			<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($value,75,"\n", true))."&nbsp;</td>\n";
							}
						}
						else {
							echo "			<td valign='top' align='left' class='".$row_style[$c]."'>".escape($key)."&nbsp;</td>\n";
							echo "			<td class='".$row_style[$c]."'>\n";
							if (isset($value[$key."_caller_profile"]) && is_array($value[$key."_caller_profile"])) {
								echo "				<table width='100%'>\n";
								foreach ($value[$key."_caller_profile"] as $key_2 => $value_2) {
									if (is_numeric($key_2)) {
										$group_output = false;
										foreach ($value_2 as $key_3 => $value_3) {
											echo "				<tr>\n";
											if ($group_output == false) {
												echo "					<td valign='top' align='left' width='10%' rowspan='".sizeof($value[$key."_caller_profile"][$key_2])."' class='".$row_style[$c]."'>".escape($key_2)."&nbsp;</td>\n";
												$group_output = true;
											}
											echo "					<td valign='top' align='left' width='20%' class='".$row_style[$c]."'>".escape($key_3)."&nbsp;</td>\n";
											if (is_array($value_3)) {
												echo "					<td valign='top' align='left' class='".$row_style[$c]."'>".escape(implode('', $value_3))."&nbsp;</td>\n";
											}
											else {
												echo "					<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($value_3,75,"\n", true))."&nbsp;</td>\n";
											}
											echo "				</tr>\n";
										}
									}
									else {
										echo "				<tr>\n";
										echo "					<td valign='top' align='left' width='20%' class='".$row_style[$c]."'>".escape($key_2)."&nbsp;</td>\n";
										if (is_array($value_2)) {
											echo "					<td valign='top' align='left' class='".$row_style[$c]."'>".escape(implode('', $value_2))."&nbsp;</td>\n";
										}
										else {
											echo "					<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($value_2,75,"\n", true))."&nbsp;</td>\n";
										}
										echo "				</tr>\n";
									}
								}
								unset($key_2, $value_2);
								echo "				</table>\n";
								echo "			</td>\n";
							}
						}
						echo "</tr>\n";
						$c = $c ? 0 : 1;
					}
				}
				echo "		<tr>\n";
				echo "			<td colspan='2'><br /><br /></td>\n";
				echo "		</tr>\n";
				echo "</table>\n";

			//times
				echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
				echo "		<tr>\n";
				echo "			<td><b>".$text['label-call-flow-5']."</b>&nbsp;</td>\n";
				echo "			<td></td>\n";
				echo "		</tr>\n";
				echo "		<tr>\n";
				echo "			<th width='30%'>".$text['label-name']."</th>\n";
				echo "			<th width='70%'>".$text['label-value']."</th>\n";
				echo "		</tr>\n";
				if (is_array($row["times"])) {
					foreach($row["times"] as $key => $value) {
						$value = urldecode($value);
						echo "		<tr >\n";
						echo "			<td valign='top' align='left' class='".$row_style[$c]."'>".escape($key)."&nbsp;</td>\n";
						echo "			<td valign='top' align='left' class='".$row_style[$c]."'>".escape(wordwrap($value,75,"\n", true))."&nbsp;</td>\n";
						echo "		</tr>\n";
						$c = $c ? 0 : 1;
					}
				}
				echo "	</table>";
				echo "	<br /><br />\n";

			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>";
		}
	}
	*/

//get the footer
	require_once "resources/footer.php";

?>
