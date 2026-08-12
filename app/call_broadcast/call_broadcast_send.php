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
	Portions created by the Initial Developer are Copyright (C) 2008-2026
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";

//detect if running from CLI or web browser
	$is_cli = php_sapi_name() === 'cli';

//check permissions/authentication
	if ($is_cli) {
		// CLI mode: require id for execution
		if (!isset($argv[1]) || empty($argv[1])) {
			echo "Error: Call broadcast UUID is required for CLI execution.\n";
			echo "\nUsage:\n";
			echo "  php call_broadcast_send.php --id=<call_broadcast_uuid> [options]\n";
			echo "\nOptions:\n";
			echo "  --u=<uuid>                  Required: Call broadcast UUID to send\n";
			echo "  --caller_id_name=<name>      Optional: Override caller ID name\n";
			echo "  --caller_id_number=<number>  Optional: Override caller ID number\n";
			echo "  --sched_seconds=<seconds>    Optional: Initial delay in seconds (default: 3)\n";
			echo "\nExample:\n";
			echo "  php call_broadcast_send.php --u=broadcast-uuid-123\n";
			echo "  php call_broadcast_send.php --id=broadcast-uuid-123 --caller_id_name='Announcer' --sched_seconds=5\n";
			exit(1);
		}

		// Define CLI options using command_option class
		$cli_options = [
			command_option::new([
				'short_option' => 'u',
				'long_option' => 'uuid:',
				'description' => 'Call Broadcast UUID to send'
			]),
			command_option::new([
				'short_option' => 'c',
				'long_option' => 'caller_id_name:',
				'description' => 'Override caller ID name'
			]),
			command_option::new([
				'short_option' => 'n',
				'long_option' => 'caller_id_number:',
				'description' => 'Override caller ID number'
			]),
			command_option::new([
				'short_option' => 's',
				'long_option' => 'sched_seconds:',
				'description' => 'Initial delay in seconds'
			]),
			command_option::new([
				'short_option' => 'd',
				'long_option' => 'domain_uuid:',
				'description' => 'Domain UUID'
			]),
		];

		// Parse CLI arguments using command_option definitions
		$cli_args = [];
		if (php_sapi_name() === 'cli' && isset($argv) && is_array($argv)) {
			foreach ($argv as $arg) {
				if (strpos($arg, '--') === 0 && strpos($arg, '=') !== false) {
					list($key, $value) = explode('=', $arg, 2);
					$cli_args[substr($key, 2)] = $value;
				}
				// Also support short options: -i, -c, -n, -s, -u, -k, -m
				elseif (preg_match('/^-(.)=(.+)$/', $arg, $matches)) {
					$short_to_long = [
						'u' => 'uuid',
						'c' => 'caller_id_name',
						'n' => 'caller_id_number',
						's' => 'sched_seconds',
						'd' => 'domain_uuid'
					];
					if (isset($short_to_long[$matches[1]])) {
						$cli_args[$short_to_long[$matches[1]]] = $matches[2];
					}
				}
			}
		}

		// Set session domain info from CLI parameters
		$domain_uuid = $cli_args['domain_uuid'] ?? '';
		$domain_name = $cli_args['domain_name'] ?? '';
	}
	else {
		// Include the check_auth file
		require_once "resources/check_auth.php";

		// Web mode: check permissions via session
		if (permission_exists('call_broadcast_send')) {
			// access granted
		}
		else {
			echo "access denied";
			exit;
		}
	}

//set the max execution time to 1 hour (only for web)
	if (!$is_cli) {
		ini_set('max_execution_time', 3600);
	}

//add multi-lingual support (only for web)
	$text = [];
	if (!$is_cli) {
		$language = new text;
		$text = $language->get();
	}

//define the asynchronous command function
	/**
	 * Asynchronously executes a command.
	 *
	 * This method runs the given $cmd as an asynchronous process. On Windows, it uses
	 * proc_open to create a new process with pipes for stdin, stdout, and stderr. On
	 * Posix systems (e.g., Linux, macOS), it uses exec to run the command in the background.
	 *
	 * @param string $cmd The command to execute asynchronously.
	 *
	 * @return int|bool The return value of proc_close() on Windows or false on failure; null if not executed successfully.
	 */
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

//get the call broadcast uuid from CLI or GET
	if ($is_cli) {
		$call_broadcast_uuid = $cli_args['uuid'] ?? $cli_args['u'];
	}
	else {
		$call_broadcast_uuid = $_GET["id"] ?? '';
	}

//validate call_broadcast_uuid
	if (empty($call_broadcast_uuid)) {
		if ($is_cli) {
			echo "Error: Call broadcast UUID is required. Use --uuid=<uuid>\n";
			exit(1);
		}
		else {
			$msg = "Call broadcast UUID is required.";
		}
	}

//get the domain uuid and name
	if (!$is_cli) {
		$domain_uuid = $_SESSION['domain_uuid'];
		$domain_name = $_SESSION['domain_name'];
	}

//get the call broadcast details from the database
	if (!empty($domain_uuid) && empty($domain_name)) {
		$sql = "select domain_name from v_domains ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
		$row = $database->select($sql, $parameters, 'row');
		if (!empty($row)) {
			$domain_name = $row['domain_name'];
		}
	}

//get the call broadcast details from the database
	$sql = "select * from v_call_broadcasts ";
	$sql .= "where call_broadcast_uuid = :call_broadcast_uuid ";
	$sql .= "and domain_uuid = :domain_uuid ";
	$parameters['call_broadcast_uuid'] = $call_broadcast_uuid;
	$parameters['domain_uuid'] = $domain_uuid;
	$row = $database->select($sql, $parameters, 'row');
	if (!empty($row)) {
		$domain_uuid = $row["domain_uuid"];
		$broadcast_name = $row["broadcast_name"] ?? 'Broadcast';
		$broadcast_start_time = $row["broadcast_start_time"];
		$broadcast_timeout = $row["broadcast_timeout"];
		$broadcast_concurrent_limit = $row["broadcast_concurrent_limit"];
		$recordingid = $row["recordingid"] ?? '';
		// Allow CLI override for caller_id fields
		$broadcast_caller_id_name = $cli_args['caller_id_name'] ?? $row["broadcast_caller_id_name"] ?? '';
		$broadcast_caller_id_number = $cli_args['caller_id_number'] ?? $row["broadcast_caller_id_number"] ?? '';
		$broadcast_destination_type = $row["broadcast_destination_type"];
		$broadcast_phone_numbers = $row["broadcast_phone_numbers"];
		$broadcast_destination_data = $row["broadcast_destination_data"];
		$broadcast_avmd = $row["broadcast_avmd"];
		$broadcast_accountcode = $row["broadcast_accountcode"];
		$broadcast_description = $row["broadcast_description"];
		//if (empty($row["broadcast_destination_data"])) {
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
	if (empty($broadcast_caller_id_name)) {
		$broadcast_caller_id_name = "anonymous";
	}
	if (empty($broadcast_caller_id_number)) {
		$broadcast_caller_id_number = "0000000000";
	}
	if (empty($broadcast_accountcode)) {
		$broadcast_accountcode = $domain_name ?? '';
	}
	// Allow CLI override of sched_seconds
	$sched_seconds = $cli_args['sched_seconds'] ?? (isset($broadcast_start_time) && is_numeric($broadcast_start_time) ? $broadcast_start_time : '3');

//get the recording name
	//$recording_filename = get_recording_filename($recordingid);

//remove unsafe characters from the name
	$broadcast_name = str_replace(" ", "", $broadcast_name);
	$broadcast_name = str_replace("'", "", $broadcast_name);

//create the event socket connection
	$fp = event_socket::create();

//helper function for output
	function output($message, $is_cli) {
		if ($is_cli) {
			echo $message . "\n";
		}
		else {
			echo $message;
		}
	}

//get information over event socket
	if (!$fp) {
		if ($is_cli) {
			echo "Error: Connection to Event Socket failed.\n";
			exit(1);
		}
		else {
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
	}
	else {
		//show the header (web only)
			if (!$is_cli) {
				require_once "resources/header.php";
			}
			else {
				echo "Starting call broadcast: $broadcast_name\n";
			}

		//send the call broadcast
			if (!empty($broadcast_phone_numbers)) {
				$broadcast_phone_number_array = explode ("\n", $broadcast_phone_numbers);
				$count = 1;
				foreach ($broadcast_phone_number_array as $tmp_value) {
					//set the variables
						$tmp_value = str_replace(";", "|", $tmp_value);
						$tmp_value_array = explode ("|", $tmp_value);

					//remove the number formatting
						$phone_number = preg_replace('{\D}', '', $tmp_value_array[0]);

					//skip the if empty or not numeric
						if (empty($phone_number) || !is_numeric($phone_number)) {
							continue;
						}

					//get the dialplan variables and bridge statement
						//$dialplan = new dialplan;
						//$dialplan->domain_uuid = $domain_uuid;
						//$dialplan->outbound_routes($phone_number);
						//$dialplan_variables = $dialplan->variables;
						//$bridge_array[0] = $dialplan->bridges;

					//prepare the string
						$channel_variables = "ignore_early_media=true";
						$channel_variables .= ",origination_number=".$phone_number;
						$channel_variables .= ",origination_caller_id_name='$broadcast_caller_id_name'";
						$channel_variables .= ",origination_caller_id_number=$broadcast_caller_id_number";
						$channel_variables .= ",domain_uuid=".$domain_uuid;
						$channel_variables .= ",domain=".$domain_name;
						$channel_variables .= ",domain_name=".$domain_name;
						$channel_variables .= ",accountcode='$broadcast_accountcode'";
						$channel_variables .= ",toll_allow='$broadcast_toll_allow'";
						if ($broadcast_avmd == "true") {
							$channel_variables .= ",execute_on_answer='avmd start'";
						}
						//$origination_url = "{".$channel_variables."}".$bridge_array[0];
						$origination_url = "{".$channel_variables."}loopback/".$phone_number.'/'.$domain_name;

					//get the context
						$context =  $domain_name;

					//set the command
						$command = "bgapi sched_api +".$sched_seconds." ".$call_broadcast_uuid." bgapi originate ".$origination_url." ".$broadcast_destination_data." XML $context";

					//if the event socket connection is lost then re-connect
						if (!$fp) {
							$fp = event_socket::create();
						}

					//method 1
						$response = event_socket::command($command);

					//method 2
						//cmd_async($settings->get('switch', 'bin')."/fs_cli -x \"".$command."\";");

					//spread the calls out so that they are scheduled with different times
						if (strlen($broadcast_concurrent_limit) > 0 && !empty($broadcast_timeout)) {
							if ($broadcast_concurrent_limit == $count) {
								$sched_seconds = $sched_seconds + $broadcast_timeout;
								$count=0;
							}
						}

					//increment the count
					$count++;
				}

				if ($is_cli) {
					echo "Call broadcast '$broadcast_name' has been sent successfully.\n";
				}
				else {
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
						echo "		<a href='".PROJECT_PATH."/app/active_calls/active_calls.php'>".$text['label-view-calls']."</a>\n";
						echo "	</td>\n";
						echo "	</table>\n";
					}

					echo "</td>\n";
					echo "</tr>\n";
					echo "</table>\n";
					echo "</div>\n";
				}

			}
			elseif ($is_cli) {
				echo "Warning: No phone numbers found for this broadcast.\n";
			}

		//show the footer (web only)
			if (!$is_cli) {
				require_once "resources/footer.php";
			}
	}

?>
