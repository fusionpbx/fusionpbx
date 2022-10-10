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
	Portions created by the Initial Developer are Copyright (C) 2008-2012
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
	if (permission_exists('fax_active_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

	if (!permission_exists('fax_active_all') && $show == 'all') {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get submitted values
	$fax_uuid = $_REQUEST["id"];
	$show = $_REQUEST["show"];

//include theme config for button images
	include_once("themes/".$_SESSION['domain']['template']['name']."/config.php");

//construct query
	$sql = "select ";
	$sql .= "t1.fax_task_uuid as uuid, ";
	$sql .= "t1.fax_uuid as fax_uuid, ";
	$sql .= "t3.domain_name, ";
	$sql .= "t3.domain_uuid, ";
	$sql .= "t1.task_next_time as next_time, ";
	$sql .= "t1.task_interrupted as interrupted, ";
	$sql .= "t1.task_status as status, ";
	$sql .= "t1.task_uri as uri, ";
	$sql .= "t1.task_dial_string as dial_string, ";
	$sql .= "t1.task_dtmf as dtmf, ";
	$sql .= "t1.task_fax_file as fax_file, ";
	$sql .= "t1.task_wav_file as wav_file, ";
	$sql .= "t1.task_reply_address as reply_address, ";
	$sql .= "t1.task_no_answer_counter as no_answer_counter, ";
	$sql .= "t1.task_no_answer_retry_counter as no_answer_retry_counter, ";
	$sql .= "t1.task_retry_counter as retry_counter, ";
	$sql .= "t2.fax_send_greeting as greeting, ";
	$sql .= "t2.fax_name as fax_server_name ";
	$sql .= "from v_fax_tasks t1 ";
	$sql .= "inner join v_fax t2 on t2.fax_uuid = t1.fax_uuid ";
	$sql .= "inner join v_domains t3 on t2.domain_uuid = t3.domain_uuid ";
	$sql .= "where true ";
	if ($show !== 'all'){
		$sql .= "and t3.domain_name = :domain_name ";
		$parameters['domain_name'] = $_SESSION['domain_name'];
	}
	else if (is_uuid($fax_uuid)) {
		if (!permission_exists('fax_active_all')) {
			$sql .= "and t3.domain_name = :domain_name ";
			$parameters['domain_name'] = $_SESSION['domain_name'];
		}
		$sql .= "and t1.fax_uuid = :fax_uuid ";
		$parameters['fax_uuid'] = $fax_uuid;
	}
	$sql .= "order by domain_name, fax_server_name, next_time ";
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	$message = $database->message;
	unset($sql, $parameters);

	if (is_array($result) && @sizeof($result) != 0) {
		//define js function call var
			$onhover_pause_refresh = " onmouseover='refresh_stop();' onmouseout='refresh_start();'";

		//show buttons
			echo "<table cellpadding='0' cellspacing='0' border='0' align='right'>";
			echo "	<tr>";
			echo "		<td valign='middle' nowrap='nowrap' style='padding-right: 15px' id='refresh_state'>";
			echo "			<img src='resources/images/refresh_active.gif' style='width: 16px; height: 16px; border: none; margin-top: 3px; cursor: pointer;' onclick='refresh_stop();' alt=\"".$text['label-refresh_pause']."\" title=\"".$text['label-refresh_pause']."\">";
			echo "		</td>";
			echo "		<td valign='top' nowrap='nowrap'>";
			if (permission_exists('fax_active_all')) {
				if ($show == "all") {
					echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"document.location='fax_active.php';\" value='".$text['button-back']."' ".$onhover_pause_refresh.">\n";
				}
				else {
					echo "	<input type='button' class='btn' name='' alt='".$text['button-show_all']."' onclick=\"document.location='fax_active.php?show=all';\" value='".$text['button-show_all']."' ".$onhover_pause_refresh.">\n";
				}
			}
			echo "		</td>";
			echo "	</tr>";
			echo "</table>";

		// show title
			echo "<b>".$text['fax-active_title']."</b>";
			echo "<br><br>\n";
			echo $text['fax-active_description']."\n";
			echo "<br><br>\n";

		//set the alternating color for each row
			$c = 0;
			$row_style["0"] = "row_style0";
			$row_style["1"] = "row_style1";

		//show the results
			echo "<div id='cmd_reponse'></div>\n";

		//show headers
			echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
			echo "<tr>\n";
			echo "<th>".$text['fax-active_title_fax_server']."</th>\n";
			echo "<th>".$text['fax-active_title_enabled']."</th>\n";
			echo "<th>".$text['fax-active_title_status']."</th>\n";
			echo "<th>".$text['fax-active_title_next_time']."</th>\n";
			echo "<th>".$text['fax-active_title_files']."</th>\n";
			echo "<th>".$text['fax-active_title_uri']."</th>\n";

			echo "<td class='list_control_icon'></td>\n";
			echo "</tr>\n";

			foreach ($result as &$row) {
				$fax_uri = $row['uri'];
				$domain_name = $row['domain_name'];
				$task_enabled = ($row['interrupted'] == 'true') ? 'Disable': 'Enable';
				$task_status  = $text['fax-active_status_wait'];
				$task_next_time  = $row['next_time'];

				if ($row['status'] > 0) {
					if ($row['status'] <= 3) {
						$task_status = $text['fax-active_status_execute'];
					}
					else if($row['status'] == 10){
						$task_status = $text['fax-active_status_success'];
					}
					else{
						$task_status = $text['fax-active_status_fail'];
					}
				}

				$fax_server = $row['fax_server_name'];
				if ($show == 'all') {
					$fax_server .= '@'.$domain_name;
				}

				$task_files = '';
				if (!empty($row['fax_file'])) {
					$task_files .= '&nbsp;'.basename($row['fax_file']);
				}
				if (!empty($row['wav_file'])) {
					$task_files .= '<br/>&nbsp;'.basename($row['wav_file']);
				}
				else if (!empty($row['greeting'])) {
					$task_files .= '<br/>&nbsp;'.basename($row['greeting']);
				}

				//replace gateway uuid with name
					if (sizeof($_SESSION['gateways']) > 0) {
						foreach ($_SESSION['gateways'] as $gateway_uuid => $gateway_name) {
							$fax_uri = str_replace($gateway_uuid, $gateway_name, $fax_uri);
						}
					}

				echo "<tr>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$fax_server."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$task_enabled."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$task_status."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$task_next_time."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$task_files."&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>".$fax_uri."&nbsp;</td>\n";

				echo "<td class='list_control_icons' style='width: 25px; text-align: left;'><a href='javascript:void(0);' alt='".$text['label-hangup']."' onclick=\"hangup(escape('".$row['uuid']."'));\">".$v_link_label_delete."</a></td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}

			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
	}
?>