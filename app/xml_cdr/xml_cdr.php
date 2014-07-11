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
	Portions created by the Initial Developer are Copyright (C) 2008-2014
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "app_languages.php";
if (permission_exists('xml_cdr_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//import xml_cdr files
	require_once "v_xml_cdr_import.php";

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";


//xml cdr include
	$rows_per_page = 100;
	require_once "xml_cdr_inc.php";

//javascript function: send_cmd
	echo "<script type=\"text/javascript\">\n";
	echo "function send_cmd(url) {\n";
	echo "	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari\n";
	echo "		xmlhttp=new XMLHttpRequest();\n";
	echo "	}\n";
	echo "	else {// code for IE6, IE5\n";
	echo "		xmlhttp=new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
	echo "	}\n";
	echo "	xmlhttp.open(\"GET\",url,true);\n";
	echo "	xmlhttp.send(null);\n";
	echo "	document.getElementById('cmd_reponse').innerHTML=xmlhttp.responseText;\n";
	echo "}\n";
	echo "</script>\n";

//page title and description
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<td align='left' width='50%' nowrap='nowrap' style='vertical-align: top;'><b>".$text['title']."</b><br><br><br></td>\n";
	echo "<td align='right' width='100%' style='vertical-align: top;'>\n";
	echo "<table>\n";
	echo "<tr>\n";
	echo "<td>\n";
	if (permission_exists('xml_cdr_search_advanced')) {
		echo "	<input type='button' class='btn' value='".$text['button-advanced_search']."' onclick=\"window.location='xml_cdr_search.php';\">\n";
	}
	echo "	<input type='button' class='btn' value='".$text['button-missed']."' onclick=\"document.location.href='xml_cdr.php?missed=true';\">\n";
	echo "	<input type='button' class='btn' value='".$text['button-statistics']."' onclick=\"document.location.href='xml_cdr_statistics.php';\">\n";
	echo "</td>\n";
	echo "<form method='post' action='xml_cdr_csv.php'>";
	echo "<td>\n";
	echo "	<input type='hidden' name='direction' value='$direction'>\n";
	echo "	<input type='hidden' name='caller_id_name' value='$caller_id_name'>\n";
	echo "	<input type='hidden' name='start_stamp' value='$start_stamp'>\n";
	echo "	<input type='hidden' name='hangup_cause' value='$hangup_cause'>\n";
	echo "	<input type='hidden' name='caller_id_number' value='$caller_id_number'>\n";
	echo "	<input type='hidden' name='destination_number' value='$destination_number'>\n";
	echo "	<input type='hidden' name='answer_stamp' value='$answer_stamp'>\n";
	echo "	<input type='hidden' name='end_stamp' value='$end_stamp'>\n";
	echo "	<input type='hidden' name='duration' value='$duration'>\n";
	echo "	<input type='hidden' name='billsec' value='$billsec'>\n";
	echo "	<input type='hidden' name='uuid' value='$uuid'>\n";
	echo "	<input type='hidden' name='bleg_uuid' value='$bleg_uuid'>\n";
	echo "	<input type='hidden' name='accountcode' value='$accountcode'>\n";
	echo "	<input type='hidden' name='read_codec' value='$read_codec'>\n";
	echo "	<input type='hidden' name='write_codec' value='$write_codec'>\n";
	echo "	<input type='hidden' name='remote_media_ip' value='$remote_media_ip'>\n";
	echo "	<input type='hidden' name='network_addr' value='$network_addr'>\n";
	echo "	<input type='submit' class='btn' name='submit' value=' csv '>\n";
	echo "</td>\n";
	echo "	</form>\n";
	echo "</tr>\n";
	echo "</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";

	echo "".$text['description']." \n";
	echo "".$text['description2']." \n";
	echo "".$text['description-3']." \n";
	echo "".$text['description-4']." \n";
	//To do an advanced search of the call detail records click on the following advanced button.

	echo "<br />\n";
	echo "<br />\n";

	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	//basic search of call detail records
		if (permission_exists('xml_cdr_search')) {

			echo "<form method='post' action=''>\n";

			echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<td width='44%' style='vertical-align: top;'>\n";

				echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
				echo "	<tr>\n";
				echo "		<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
				echo "			".$text['label-direction']."\n";
				echo "		</td>\n";
				echo "		<td class='vtable' width='70%' align='left'>\n";
				echo "			<select name='direction' class='formfld'>\n";
				echo "				<option value=''></option>\n";
				if ($direction == "inbound") {
					echo "			<option value='inbound' selected='selected'>".$text['label-inbound']."</option>\n";
				}
				else {
					echo "			<option value='inbound'>".$text['label-inbound']."</option>\n";
				}
				if ($direction == "outbound") {
					echo "			<option value='outbound' selected='selected'>".$text['label-outbound']."</option>\n";
				}
				else {
					echo "			<option value='outbound'>".$text['label-outbound']."</option>\n";
				}
				if ($direction == "local") {
					echo "			<option value='local' selected='selected'>".$text['label-local']."</option>\n";
				}
				else {
					echo "			<option value='local'>".$text['label-local']."</option>\n";
				}
				echo "			</select>\n";
				echo "		</td>\n";
				echo "	</tr>\n";

				echo "	<tr>\n";
				echo "		<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
				echo "			".$text['label-status']."\n";
				echo "		</td>\n";
				echo "		<td class='vtable' width='70%' align='left'>\n";
				echo "			<select name=\"hangup_cause\" class='formfld'>\n";
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
					'MANDATORY_IE_MISSING'
					);
				sort($cdr_status_options);
				foreach ($cdr_status_options as $cdr_status) {
					$selected = ($hangup_cause == $cdr_status) ? "selected='selected'" : null;
					$cdr_status_label = ucwords(strtolower(str_replace("_", " ", $cdr_status)));
					echo "			<option value='".$cdr_status."' ".$selected.">".$cdr_status_label."</option>";
				}
				echo "			</select>\n";
				echo "		</td>\n";
				echo "	</tr>\n";
				echo "</table>\n";

			echo "</td>";
			echo "<td width='28%' style='vertical-align: top;'>\n";

				echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
				echo "	<tr>\n";
				echo "		<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
				echo "			".$text['label-source']."\n";
				echo "		</td>\n";
				echo "		<td class='vtable' width='70%' align='left'>\n";
				echo "			<input type='text' class='formfld' name='caller_id_number' style='width:100%' value='$caller_id_number'>\n";
				echo "		</td>\n";
				echo "	</tr>\n";

				echo "	<tr>\n";
				echo "		<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
				echo "			".$text['label-destination']."\n";
				echo "		</td>\n";
				echo "		<td class='vtable' width='70%' align='left'>\n";
				echo "			<input type='text' class='formfld' name='destination_number' style='width:100%' value='$destination_number'>\n";
				echo "		</td>\n";
				echo "	</tr>\n";
				echo "</table>\n";

			echo "</td>";
			echo "<td width='28%' style='vertical-align: top;'>\n";

				echo "<table width='100%' border='0' cellpadding='6' cellspacing='0'>\n";
				echo "	<tr>\n";
				echo "		<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
				echo "			".$text['label-cid-name']."\n";
				echo "		</td>\n";
				echo "		<td class='vtable' width='70%' align='left'>\n";
				echo "			<input type='text' class='formfld' name='caller_id_name' style='width:100%' value='$caller_id_name'>\n";
				echo "		</td>\n";
				echo "	</tr>\n";
				echo "	<tr>\n";
				echo "		<td class='vncell' valign='top' nowrap='nowrap' width='30%'>\n";
				echo "			".$text['label-start']."\n";
				echo "		</td>\n";
				echo "		<td class='vtable' width='70%' align='left'>\n";
				echo "			<input type='text' class='formfld' name='start_stamp' style='width:100%' value='$start_stamp'>\n";
				echo "		</td>\n";
				echo "	</tr>\n";
				echo "</table>\n";

			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2' style='padding-top: 8px;' align='left'>";
			echo 	$text['description_search'];
			echo "</td>";
			echo "<td style='padding-top: 8px;' align='right'>";

				echo "<input type='button' class='btn' value='".$text['button-reset']."' onclick=\"document.location.href='xml_cdr.php';\">\n";
				echo "<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>\n";

			echo "</td>";
			echo "</tr>";
			echo "</table>";

			echo "</form>";
			echo "<br /><br />";

		}

//show the results
	echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>&nbsp;</th>\n";
	//echo th_order_by('direction', 'Direction', $order_by, $order);
	//echo th_order_by('default_language', 'Language', $order_by, $order);
	//echo th_order_by('context', 'Context', $order_by, $order);
	//echo th_order_by('leg', 'Leg', $order_by, $order);
	echo th_order_by('caller_id_name', $text['label-cid-name'], $order_by, $order);
	echo th_order_by('caller_id_number', $text['label-source'], $order_by, $order);
	echo th_order_by('destination_number', $text['label-destination'], $order_by, $order);
	echo "<th>".$text['label-tools']."</th>\n";
	echo th_order_by('start_stamp', $text['label-start'], $order_by, $order);
	//echo th_order_by('end_stamp', 'End', $order_by, $order);
	echo th_order_by('duration', $text['label-duration'], $order_by, $order);
	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/billings/app_config.php")){
		// billing collumns
		echo "<th>".$text['label-price']."</th>\n";
	}
	if (permission_exists('xml_cdr_pdd')) {
		echo th_order_by('pdd_ms', 'PDD', $order_by, $order);
	}
	if (permission_exists('xml_cdr_mos')) {
		echo th_order_by('rtp_audio_in_mos', 'MOS', $order_by, $order);
	}
	echo th_order_by('hangup_cause', $text['label-status'], $order_by, $order);
	if (if_group("admin") || if_group("superadmin")) {
		echo "<td class='list_control_icon'>&nbsp;</td>\n";
	}
	echo "</tr>\n";
	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/billings/app_config.php")){
		require_once "app/billings/functions.php";
		require_once "resources/classes/database.php";
		$database = new database;
		$database->table = "v_billings";
		$tv = (strlen($_GET["accountcode"])?$_GET["accountcode"]:$_SESSION[domain_name]);
		$database->sql = "SELECT currency from v_billings WHERE type_value='$tv'";
		$database->result = $database->execute();
		$currency = (strlen($database->result[0]['currency'])?$database->result[0]['currency']:'USD');
		unset($database->sql);
		unset($database->result);

	}
	if ($result_count > 0) {
		foreach($result as $row) {
			$tmp_year = date("Y", strtotime($row['start_stamp']));
			$tmp_month = date("M", strtotime($row['start_stamp']));
			$tmp_day = date("d", strtotime($row['start_stamp']));

			if (defined('TIME_24HR') && TIME_24HR == 1) {
				$tmp_start_epoch = date("j M Y H:i:s", $row['start_epoch']);
			} else {
				$tmp_start_epoch = date("j M Y h:i:sa", $row['start_epoch']);
			}

			$hangup_cause = $row['hangup_cause'];
			$hangup_cause = str_replace("_", " ", $hangup_cause);
			$hangup_cause = strtolower($hangup_cause);
			$hangup_cause = ucwords($hangup_cause);

			$tmp_dir = $_SESSION['switch']['recordings']['dir'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day;
			$tmp_name = '';
			if(!empty($row['recording_file']) && file_exists($row['recording_file'])){
				$tmp_name=$row['recording_file'];
			}
			elseif (file_exists($tmp_dir.'/'.$row['uuid'].'.wav')) {
				$tmp_name = $row['uuid'].".wav";
			}
			elseif (file_exists($tmp_dir.'/'.$row['uuid'].'_1.wav')) {
				$tmp_name = $row['uuid']."_1.wav";
			}
			elseif (file_exists($tmp_dir.'/'.$row['uuid'].'.mp3')) {
				$tmp_name = $row['uuid'].".mp3";
			}
			elseif (file_exists($tmp_dir.'/'.$row['uuid'].'_1.mp3')) {
				$tmp_name = $row['uuid']."_1.mp3";
			}
			$tr_link = (if_group("admin") || if_group("superadmin")) ? "href='xml_cdr_details.php?uuid=".$row['uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			if (
				file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_missed.png") &&
				file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_connected.png") &&
				file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_outbound_failed.png") &&
				file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_outbound_connected.png") &&
				file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_failed.png") &&
				file_exists($_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_connected.png")
				) {
				echo "	<td valign='top' class='".$row_style[$c]."' style='text-align: center;'>";
				switch ($row['direction']) {
					case "inbound" :
						if ($row['billsec'] == 0)
							echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_missed.png' style='border: none;' alt='".$text['label-inbound']." ".$text['label-missed']."'>\n";
						else
							echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_inbound_connected.png' style='border: none;' alt='".$text['label-inbound']."'>\n";
						break;
					case "outbound" :
						if ($row['billsec'] == 0)
							echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_outbound_failed.png' style='border: none;' alt='".$text['label-outbound']." ".$text['label-failed']."'>\n";
						else
							echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_outbound_connected.png' style='border: none;' alt='".$text['label-outbound']."'>\n";
						break;
					case "local" :
						if ($row['billsec'] == 0)
							echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_failed.png' style='border: none;' alt='".$text['label-local']." ".$text['label-failed']."'>\n";
						else
							echo "<img src='/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_local_connected.png' style='border: none;' alt='".$text['label-local']."'>\n";
						break;
				}
				echo "	</td>\n";
			}
			else {
				echo "	<td class='".$row_style[$c]."'>&nbsp;</td>";
			}
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['default_language']."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['context']."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['leg']."</td>\n";

			echo "	<td valign='top' class='".$row_style[$c]."'>";
			echo 	$row['caller_id_name'].' ';
			echo "	</td>\n";

			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void'>";
			echo "		<a href=\"javascript:void(0)\" onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode($row['caller_id_name'])."&src_cid_number=".urlencode($row['caller_id_number'])."&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'])."&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'])."&src=".urlencode($_SESSION['user']['extension'][0]['user'])."&dest=".urlencode($row['caller_id_number'])."&rec=false&ringback=us-ring&auto_answer=true');\">\n";
			if (is_numeric($row['caller_id_number'])) {
				echo "		".format_phone($row['caller_id_number']).' ';
			}
			else {
				echo "		".$row['caller_id_number'].' ';
			}
			echo "		</a>";
			echo "	</td>\n";

			echo "	<td valign='top' class='".$row_style[$c]." tr_link_void'>";
			echo "		<a href=\"javascript:void(0)\" onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php?src_cid_name=".urlencode($row['destination_number'])."&src_cid_number=".urlencode($row['destination_number'])."&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'])."&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'])."&src=".urlencode($_SESSION['user']['extension'][0]['user'])."&dest=".urlencode($row['destination_number'])."&rec=false&ringback=us-ring&auto_answer=true');\">\n";
			if (is_numeric($row['destination_number'])) {
				echo format_phone($row['destination_number'])."\n";
			}
			else {
				echo "		".$row['destination_number']."\n";
			}
			echo "		</a>\n";
			echo "	</td>\n";

			echo "	<td valign='top' class='".$row_style[$c]."' nowrap=\"nowrap\">";
			if (strlen($tmp_name) > 0 && file_exists($_SESSION['switch']['recordings']['dir'].'/archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)) {
				echo "		<a href=\"javascript:void(0);\" onclick=\"window.open('".PROJECT_PATH."/app/recordings/recording_play.php?a=download&type=moh&filename=".base64_encode('archive/'.$tmp_year.'/'.$tmp_month.'/'.$tmp_day.'/'.$tmp_name)."', 'play',' width=420,height=150,menubar=no,status=no,toolbar=no')\">\n";
				echo "			".$text['label-play']."\n";
				echo "		</a>\n";
				echo "		&nbsp;\n";
				echo "		<a href=\"../recordings/recordings.php?a=download&type=rec&t=bin&filename=".base64_encode("archive/".$tmp_year."/".$tmp_month."/".$tmp_day."/".$tmp_name)."\">\n";
				echo "			".$text['label-download']."\n";
				echo "		</a>\n";
			}
			else {
				echo "		&nbsp;\n";
			}
			echo "	</td>\n";

			echo "	<td valign='top' class='".$row_style[$c]."'>".$tmp_start_epoch."</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['end_stamp']."</td>\n";

			//If they cancelled, show the ring time, not the bill time.
			$seconds = ($row['hangup_cause']=="ORIGINATOR_CANCEL") ? $row['duration'] : $row['billsec'];

			echo "	<td valign='top' class='".$row_style[$c]."'>".gmdate("G:i:s", $seconds)."</td>\n";

			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/billings/app_config.php")){

				$price = $row['call_sell'];
				$lcr_direction = (strlen($row['direction'])?$row['direction']:"outbound");

				$n = $row['destination_number'];
				
				if ($lcr_direction == "inbound"){
					$n = $row['caller_id_number'];
				}
				$database->table = "v_lcr";
				$database->sql = "SELECT currency FROM v_lcr WHERE v_lcr.carrier_uuid= '' AND v_lcr.lcr_direction='$lcr_direction' AND v_lcr.digits IN (".number_series($n).") ORDER BY digits DESC, rate ASC, date_start DESC LIMIT 1";
				$database->result = $database->execute();
				//print "<pre>"; print_r($database->result); print "[".$database->result[0]['currency']."]"; print "</pre>";

				$billed_currency = ((is_string($database->result[0]['currency']) && strlen($database->result[0]['currency']))?$database->result[0]['currency']:'USD');	//billed currency
				unset($database->sql);
				unset($database->result);
				$price = currency_convert($price, $currency, $billed_currency);
				echo "	<td valign='top' class='".$row_style[$c]."'>".number_format($price,6)." $billed_currency</td>\n";
			}
			if (permission_exists("xml_cdr_pdd")) {
				echo "	<td valign='top' class='".$row_style[$c]."'>".number_format($row['pdd_ms']/1000,2)."s</td>\n";
			}
			if (permission_exists("xml_cdr_mos")) {
				echo "	<td valign='top' class='".$row_style[$c]."' ".((strlen($row['rtp_audio_in_mos']) > 0) ? "title='".($row['rtp_audio_in_mos'] / 5 * 100)."%'" : null).">".((strlen($row['rtp_audio_in_mos']) > 0) ? $row['rtp_audio_in_mos'] : "&nbsp;")."</td>\n";
			}
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (if_group("admin") || if_group("superadmin")) {
				echo "<a href='xml_cdr_details.php?uuid=".$row['uuid']."'>".$hangup_cause."</a>";
			}
			else {
				echo $hangup_cause;
			}
			echo "	</td>\n";
			if (if_group("admin") || if_group("superadmin")) {
				echo "	<td class='list_control_icon'>";
				echo "		<a href='xml_cdr_details.php?uuid=".$row['uuid']."' alt='".$text['button-view']."'>$v_link_label_view</a>";
				echo "	</td>\n";
			}
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<br><br>";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br><br>";
	echo "<br><br>";

//show the footer
	require_once "resources/footer.php";
?>
