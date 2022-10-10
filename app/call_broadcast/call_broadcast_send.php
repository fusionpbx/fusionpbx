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
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//chec permissions
	if (permission_exists('call_broadcast_send')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the max execution time to 1 hour
	ini_set(max_execution_time,3600);

//define the asynchronous command function
	function cmd_async($cmd) {
		//windows
		if (stristr(PHP_OS, 'WIN')) {
			$descriptorspec = array(
				0 => array("pipe", "r"),  // stdin
				1 => array("pipe", "w"),  // stdout
				2 => array("pipe", "w")   // stderr
			);
			$process = proc_open("start ".$cmd, $descriptorspec, $pipes);
			//sleep(1);
			proc_close($process);
		}
		else { //posix
			exec ($cmd ." /dev/null 2>&1 &");
		}
	}

//get the http get values and set as php variables
	$group_name = $_GET["group_name"];
	$call_broadcast_uuid = $_GET["id"];
	$user_category = $_GET["user_category"];
	$gateway = $_GET["gateway"];
	$phonetype1 = $_GET["phonetype1"];
	$phonetype2 = $_GET["phonetype2"];

//get the call broadcast details from the database
	$sql = "select * from v_call_broadcasts ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and call_broadcast_uuid = :call_broadcast_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['call_broadcast_uuid'] = $call_broadcast_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (is_array($row) && sizeof($row) != 0) {
		$broadcast_name = $row["broadcast_name"];
		$broadcast_start_time = $row["broadcast_start_time"];
		$broadcast_timeout = $row["broadcast_timeout"];
		$broadcast_concurrent_limit = $row["broadcast_concurrent_limit"];
		$recordingid = $row["recordingid"];
		$broadcast_caller_id_name = $row["broadcast_caller_id_name"];
		$broadcast_caller_id_number = $row["broadcast_caller_id_number"];
		$broadcast_destination_type = $row["broadcast_destination_type"];
		$broadcast_phone_numbers = $row["broadcast_phone_numbers"];
		$broadcast_destination_data = $row["broadcast_destination_data"];
		$broadcast_avmd = $row["broadcast_avmd"];
		$broadcast_accountcode = $row["broadcast_accountcode"];
		$broadcast_description = $row["broadcast_description"];
		//if (strlen($row["broadcast_destination_data"]) == 0) {
		//	$broadcast_destination_application = '';
		//	$broadcast_destination_data = '';
		//}
		//else {
		//	$broadcast_destination_array = explode(":", $row["broadcast_destination_data"]);
		//	$broadcast_destination_application = $broadcast_destination_array[0];
		//	$broadcast_destination_data = $broadcast_destination_array[1];
		//}
	}
	unset($sql, $parameters, $row);

//set the defaults
	if (strlen($broadcast_caller_id_name) == 0) {
		$broadcast_caller_id_name = "anonymous";
	}
	if (strlen($broadcast_caller_id_number) == 0) {
		$broadcast_caller_id_number = "0000000000";
	}
	if (strlen($broadcast_accountcode) == 0) {
		$broadcast_accountcode = $_SESSION['domain_name'];;
	}
	if (isset($broadcast_start_time) && is_numeric($broadcast_start_time)) {
		$sched_seconds = $broadcast_start_time;
	}
	else {
		$sched_seconds = '3';
	}

//get the recording name
	//$recording_filename = get_recording_filename($recordingid);

//remove unsafe characters from the name
	$broadcast_name = str_replace(" ", "", $broadcast_name);
	$broadcast_name = str_replace("'", "", $broadcast_name);

//create the event socket connection
	$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);

//get information over event socket
	if (!$fp) {
		require_once "resources/header.php";
		$msg = "<div align='center'>Connection to Event Socket failed.<br /></div>";
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
		require_once "resources/footer.php";
	}
	else {
		//show the header
			require_once "resources/header.php";

		//send the call broadcast
			if (strlen($broadcast_phone_numbers) > 0) {
				$broadcast_phone_number_array = explode ("\n", $broadcast_phone_numbers);
				$count = 1;
				foreach ($broadcast_phone_number_array as $tmp_value) {
					//set the variables
						$tmp_value = str_replace(";", "|", $tmp_value);
						$tmp_value_array = explode ("|", $tmp_value);

					//remove the number formatting
						$phone_1 = preg_replace('{\D}', '', $tmp_value_array[0]);

					if (is_numeric($phone_1)) {
						//get the dialplan variables and bridge statement
							//$dialplan = new dialplan;
							//$dialplan->domain_uuid = $_SESSION['domain_uuid'];
							//$dialplan->outbound_routes($phone_1);
							//$dialplan_variables = $dialplan->variables;
							//$bridge_array[0] = $dialplan->bridges;

						//prepare the string
							$channel_variables = "ignore_early_media=true";
							$channel_variables .= ",origination_number=".$phone_1;
							$channel_variables .= ",origination_caller_id_name='$broadcast_caller_id_name'";
							$channel_variables .= ",origination_caller_id_number=$broadcast_caller_id_number";
							$channel_variables .= ",domain_uuid=".$_SESSION['domain_uuid'];
							$channel_variables .= ",domain=".$_SESSION['domain_name'];
							$channel_variables .= ",domain_name=".$_SESSION['domain_name'];
							$channel_variables .= ",accountcode='$broadcast_accountcode'";
							$channel_variables .= ",toll_allow='$broadcast_toll_allow'";
							if ($broadcast_avmd == "true") {
								$channel_variables .= ",execute_on_answer='avmd start'";
							}
							//$origination_url = "{".$channel_variables."}".$bridge_array[0];
							$origination_url = "{".$channel_variables."}loopback/".$phone_1.'/'.$_SESSION['domain_name'];

						//get the context
							$context =  $_SESSION['domain_name'];

						//set the command
							$cmd = "bgapi sched_api +".$sched_seconds." ".$call_broadcast_uuid." bgapi originate ".$origination_url." ".$broadcast_destination_data." XML $context";

						//if the event socket connection is lost then re-connect
							if (!$fp) {
								$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
							}

						//method 1
							$response = trim(event_socket_request($fp, 'api '.$cmd));

						//method 2
							//cmd_async($_SESSION['switch']['bin']['dir']."/fs_cli -x \"".$cmd."\";");

						//spread the calls out so that they are scheduled with different times
							if (strlen($broadcast_concurrent_limit) > 0 && strlen($broadcast_timeout) > 0) {
								if ($broadcast_concurrent_limit == $count) {
									$sched_seconds = $sched_seconds + $broadcast_timeout;
									$count=0;
								}
							}

						$count++;
					}
				}
				fclose($fp);

				echo "<div align='center'>\n";
				echo "<table width='50%'>\n";
				echo "<tr>\n";
				echo "<th align='left'>Message</th>\n";
				echo "</tr>\n";
				echo "<tr>\n";
				echo "<td class='row_style1' align='center'>\n";
				echo "	<strong>".$text['label-call-broadcast']." ".$broadcast_name." ".$text['label-has-been']."</strong>\n";

				if (permission_exists('call_active_view')) {
					echo "	<br /><br />\n";
					echo "	<table width='100%'>\n";
					echo "	<tr>\n";
					echo "	<td align='center'>\n";
					echo "		<a href='".PROJECT_PATH."/app/calls_active/calls_active.php'>".$text['label-view-calls']."</a>\n";
					echo "	</td>\n";
					echo "	</table>\n";
				}

				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";
				echo "</div>\n";

			}

		//show the footer
			require_once "resources/footer.php";
	}

?>
