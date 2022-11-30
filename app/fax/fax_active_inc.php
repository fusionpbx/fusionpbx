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
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('fax_active_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

if ((!permission_exists('fax_active_all')) && ($show == 'all')) {
	echo "access denied";
	exit;
}

$fax_uuid = false;
if(isset($_REQUEST['id'])) {
	$fax_uuid = check_str($_REQUEST["id"]);
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the HTTP values and set as variables
	$show = trim($_REQUEST["show"]);
	if ($show != "all") { $show = ''; }

//include theme config for button images
	include_once("themes/".$_SESSION['domain']['template']['name']."/config.php");

$where = 'where (1 = 1)';

if($show !== 'all'){
	$where .= 'and (t3.domain_name = \'' . check_str($_SESSION['domain_name']) . '\')';
}
else if($fax_uuid){
	if(!permission_exists('fax_active_all')){
		$where .= 'and (t3.domain_name = \'' . check_str($_SESSION['domain_name']) . '\')';
	}
	$where .= 'and (t1.fax_uuid =\'' . check_str($fax_uuid) . '\')';
}

	$sql = <<<HERE
select
  t1.fax_task_uuid as uuid,
  t1.fax_uuid as fax_uuid,
  t3.domain_name,
  t3.domain_uuid,
  t1.task_next_time as next_time,
  t1.task_interrupted as interrupted,
  t1.task_status as status,
  t1.task_uri as uri,
  t1.task_dial_string as dial_string,
  t1.task_dtmf as dtmf,
  t1.task_fax_file as fax_file,
  t1.task_wav_file as wav_file,
  t1.task_reply_address as reply_address,
  t1.task_no_answer_counter as no_answer_counter,
  t1.task_no_answer_retry_counter as no_answer_retry_counter,
  t1.task_retry_counter as retry_counter,
  t2.fax_send_greeting as greeting,
  t2.fax_name as fax_server_name
from v_fax_tasks t1
  inner join v_fax t2 on t2.fax_uuid = t1.fax_uuid
  inner join v_domains t3 on t2.domain_uuid = t3.domain_uuid
$where
order by domain_name, fax_server_name, next_time
HERE;

	$result = false;
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		if($prep_statement->execute()) {
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		}
	}
	unset($prep_statement, $sql, $where);

//if the connnection is available then run it and return the results
	if ($result === false) {
		var_dump($db->errorInfo());
		$msg = "<div align='center'>".$text['message-fail']."<br /></div>";
		echo "<div align='center'>\n";
		echo "<table width='40%'>\n";
		echo "<tr>\n";
		echo "<th align='left'>".$text['label-message']."</th>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td class='row_style1'><strong>$msg</strong></td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</div>\n";
	}
	else {
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
			echo "<th>" . $text['fax-active_title_fax_server'] . "</th>\n";
			echo "<th>" . $text['fax-active_title_enabled']    . "</th>\n";
			echo "<th>" . $text['fax-active_title_status']     . "</th>\n";
			echo "<th>" . $text['fax-active_title_next_time']  . "</th>\n";
			echo "<th>" . $text['fax-active_title_files']      . "</th>\n";
			echo "<th>" . $text['fax-active_title_uri']        . "</th>\n";

			echo "<td class='list_control_icon'></td>\n";
			echo "</tr>\n";

			foreach ($result as &$row) {
				$fax_uri = $row['uri'];
				$domain_name = $row['domain_name'];
				$task_enabled = ($row['interrupted'] == 'true') ? 'Disable': 'Enable';
				$task_status  = $text['fax-active_status_wait'];
				$task_next_time  = $row['next_time'];

				if($row['status'] > 0){
					if($row['status'] <= 3){
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
					$fax_server .= '@' . $domain_name;
				}

				$task_files = '';
				if(!empty($row['fax_file'])){
					$task_files .= '&nbsp;' . basename($row['fax_file']);
				}
				if(!empty($row['wav_file'])){
					$task_files .= '<br/>&nbsp;' . basename($row['wav_file']);
				} else if(!empty($row['greeting'])){
					$task_files .= '<br/>&nbsp;' . basename($row['greeting']);
				}

				//replace gateway uuid with name
					if (sizeof($_SESSION['gateways']) > 0) {
						foreach ($_SESSION['gateways'] as $gateway_uuid => $gateway_name) {
							$fax_uri = str_replace($gateway_uuid, $gateway_name, $fax_uri);
						}
					}

				echo "<tr>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>" . $fax_server     . "&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>" . $task_enabled   . "&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>" . $task_status    . "&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>" . $task_next_time . "&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>" . $task_files     . "&nbsp;</td>\n";
				echo "<td valign='top' class='".$row_style[$c]."'>" . $fax_uri        . "&nbsp;</td>\n";

				echo "<td class='list_control_icons' style='width: 25px; text-align: left;'><a href='javascript:void(0);' alt='".$text['label-hangup']."' onclick=\"hangup(escape('".$row['uuid']."'));\">".$v_link_label_delete."</a></td>\n";
				echo "</tr>\n";
				$c = ($c) ? 0 : 1;
			}

			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";
	}
?>