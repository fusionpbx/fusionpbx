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
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('call_center_agent_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//includes and title
	require_once "resources/header.php";
	$document['title'] = $text['title-call_center_agent_status'];
	require_once "resources/paging.php";

//setup the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//get the http post values and set them as php variables
	if (count($_POST) > 0) {

		//include the dnd php class
		include PROJECT_PATH."/app/calls/resources/classes/do_not_disturb.php";
		foreach($_POST['agents'] as $row) {
			if (strlen($row['agent_status']) > 0) {
				//agent set status
					if ($fp) {
						//set the user_status
							$sql  = "update v_users set ";
							$sql .= "user_status = '".$row['agent_status']."' ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and username = '".$row['agent_name']."' ";
							//echo $sql."\n";
							$prep_statement = $db->prepare(check_sql($sql));
							$prep_statement->execute();
						//set the call center status
							if ($row['agent_status'] == "Do Not Disturb") {
								//set the default dnd action
									$dnd_action = "add";
								//set the call center status to Logged Out
									$cmd = "api callcenter_config agent set status ".$row['agent_name']."@".$_SESSION['domains'][$domain_uuid]['domain_name']." 'Logged Out'";
							}
							else {
								$cmd = "api callcenter_config agent set status ".$row['agent_name']."@".$_SESSION['domains'][$domain_uuid]['domain_name']." '".$row['agent_status']."'";
							}
							$response = event_socket_request($fp, $cmd);
							//echo $cmd."\n";
							usleep(200);
					}

				//loop through the list of assigned extensions
					//foreach ($_SESSION['user']['extension'] as &$sub_row) {
					//	//update dnd
					//		$dnd = new do_not_disturb;
					//		//$dnd->debug = false;
					//		$dnd->domain_uuid = $domain_uuid;
					//		$dnd->domain_name = $_SESSION['domain_name'];
					//		$dnd->extension = $sub_row["user"];
					//		if ($row['status'] == "Do Not Disturb") {
					//			$dnd->enabled = "true";
					//		}
					//		else {
					//			$dnd->enabled = "false";
					//		}
					//		$dnd->set();
					//		unset($dnd);
					//}
					//unset ($prep_statement);
			}
		}
	}

//get the agents from the database
	$sql = "select * from v_call_center_agents ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$agents = $prep_statement->fetchAll(PDO::FETCH_NAMED);

//get the agent list from event socket
	$switch_cmd = 'callcenter_config agent list';
	$event_socket_str = trim(event_socket_request($fp, 'api '.$switch_cmd));
	$call_center_agents = csv_to_named_array($event_socket_str, '|');
	//echo "<pre>\n";
	//print_r($call_center_agents);
	//echo "</pre>\n";

//get the agent status from mod_callcenter and update the agent status in the agents array
	$x = 0;
	foreach ($agents as $row) {
		//add the domain name
			$domain_name = $_SESSION['domains'][$row['domain_uuid']]['domain_name'];
			$agents[$x]['domain_name'] = $domain_name;
		//update the agent status
			foreach ($call_center_agents as $r) {
				if ($r['name'] == $row[agent_name].'@'.$domain_name) {
					$agents[$x]['agent_status'] = $r['status'];
				}
			}
		//increment x
			$x++;

	}
	//echo "<pre>\n";
	//print_r($agents);
	//echo "</pre>\n";

//set the status on the user_array by using the extension as the key
	//foreach ($call_center_agents as $row) {
	//	if (count($_SESSION['domains']) == 1) {
	//		//get the extension status from the call center agent list
	//		preg_match('/user\/(\d{2,7})/', $row['contact'], $matches);
	//		$extension = $matches[1];
	//		$user_array[$extension]['username'] = $tmp[0];
	//		if ($user_array[$extension]['user_status'] != "Do Not Disturb") {
	//			$user_array[$extension]['user_status'] = $row['status'];
	//		}
	//	} else {
	//		$tmp = explode('@',$row["name"]);
	//		if ($tmp[1] == $_SESSION['domain_name']) {
	//			//get the extension status from the call center agent list
	//			preg_match('/user\/(\d{2,7})/', $row['contact'], $matches);
	//			$extension = $matches[1];
	//			$user_array[$extension]['username'] = $tmp[0];
	//			if ($user_array[$extension]['user_status'] != "Do Not Disturb") {
	//				$user_array[$extension]['user_status'] = $row['status'];
	//			}
	//		}
	//	}
	//}

//show the content
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	echo "<td width='50%' align='left' nowrap='nowrap'><b>".$text['header-call_center_agent_status']."</b></td>\n";
	echo "<td width='50%' align='right'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-back']."' onclick=\"window.location='call_center_queues.php'\" value='".$text['button-back']."'>\n";
	echo "	<input type='button' class='btn' name='' alt='".$text['button-refresh']."' onclick=\"window.location='call_center_agent_status.php'\" value='".$text['button-refresh']."'>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td align='left' colspan='2'>\n";
	echo $text['description-call_center_agent_status']."<br /><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<form method='post' name='frm' action=''>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "<th>".$text['label-agent']."</th>\n";
	echo "<th>".$text['label-status']."</th>\n";
	echo "<th>".$text['label-options']."</th>\n";
	echo "</tr>\n";
	$x = 0;
	foreach($agents as $row) {
		$str = '';
		$str .= "<tr>\n";
		$str .= "	<td valign='middle' class='".$row_style[$c]."'>".$row['agent_name']."&nbsp;</td>\n";
		$str .= "	<td valign='middle' class='".$row_style[$c]."'>".$row['agent_status']."&nbsp;</td>\n";
		$str .= "	<td valign='middle' class='".$row_style[$c]."'>";
		$str .= "		<input type='hidden' name='agents[".$x."][agent_name]' id='agent_".$x."_name' value='".$row['agent_name']."'>\n";
		$str .= "		<input type='hidden' name='agents[".$x."][id]' id='agent_".$x."_name' value='".$row['call_center_agent_uuid']."'>\n";
		$str .= "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_no_change' value='' checked='checked'><label for='agent_".$x."_status_no_change'>".$text['option-no_change']."</label>\n";
		$str .= "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_available' value='Available'><label for='agent_".$x."_status_available'>".$text['option-available']."</label>\n";
		$str .= "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_logged_out' value='Logged Out'><label for='agent_".$x."_status_logged_out'>".$text['option-logged_out']."</label>\n";
		$str .= "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_on_break' value='On Break'><label for='agent_".$x."_status_on_break'>".$text['option-on_break']."</label>\n";
		//$str .= "		<input type='radio' name='agents[".$x."][agent_status]' id='agent_".$x."_status_dnd' value='Do Not Disturb'><label for='agent_".$x."_status_dnd'>".$text['option-do_not_disturb']."</label>\n";
		$str .= "	</td>\n";
		$str .= "</tr>\n";
		if (count($_SESSION['domains']) > 1) {
			if ($row['domain_name'] == $_SESSION['domain_name']) {
				echo $str;
				if ($c==0) { $c=1; } else { $c=0; }
			}
		}
		else {
			echo $str;
			if ($c==0) { $c=1; } else { $c=0; }
		}
		$x++;
	} //end foreach
	unset($sql, $result, $row_count);

	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap>$paging_controls</td>\n";
	echo "		<td width='33.3%' align='right'>\n";
	echo "			<br />\n";
	echo "			<input type='submit' name='submit' class='btn' value='".$text['button-update_status']."'>\n";
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "<br><br>";
	echo "</form>\n";

//show the footer
	require_once "resources/footer.php";

?>