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
	Portions created by the Initial Developer are Copyright (C) 2008-2018
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	Riccardo Granchi <riccardo.granchi@nems.it>
*/

//includes files
require_once __DIR__ . "/require.php";

function event_socket_create($host = null, $port = null, $password = null) {
	$esl = new event_socket;
	if ($esl->connect($host, $port, $password)) {
		return $esl->reset_fp();
	}
	return false;
}

function event_socket_request($fp, $cmd) {
	$esl = new event_socket($fp);
	$result = $esl->request($cmd);
	$esl->reset_fp();
	return $result;
}

function event_socket_request_cmd($cmd) {
	$esl = new event_socket;
	if (!$esl->connect()) {
		return false;
	}
	$response = $esl->request($cmd);
	$esl->close();
	return $response;
}

function remove_config_from_cache($name) {
	$cache = new cache;
	$cache->delete($name);
	$hostname = trim(event_socket_request_cmd('api switchname'));
	if($hostname){
		$cache->delete($name . ':' . $hostname);
	}
}

function ListFiles($dir) {
	if($dh = opendir($dir)) {
		$files = Array();
		$inner_files = Array();

		while($file = readdir($dh)) {
			if($file != "." && $file != ".." && $file[0] != '.') {
				if(is_dir($dir . "/" . $file)) {
					//$inner_files = ListFiles($dir . "/" . $file); //recursive
					if(is_array($inner_files)) $files = array_merge($files, $inner_files);
			} else {
					array_push($files, $file);
					//array_push($files, $dir . "/" . $file);
				}
			}
		}
		closedir($dh);
		return $files;
	}
}

function filename_safe($filename) {
	//lower case
		$filename = strtolower($filename);

	//replace spaces with a '_'
		$filename = str_replace(" ", "_", $filename);

	//loop through string
		$result = '';
		for ($i=0; $i<strlen($filename); $i++) {
			if (preg_match('([0-9]|[a-z]|_)', $filename[$i])) {
				$result .= $filename[$i];
			}
		}

	//return filename
		return $result;
}

function save_gateway_xml() {

	//skip saving the gateway xml if the directory is not set
		if (empty($_SESSION['switch']['sip_profiles']['dir'])) {
			return;
		}

	//declare the global variables
		global $domain_uuid, $config;

	//delete all old gateways to prepare for new ones
		if (count($_SESSION["domains"]) > 1) {
			$v_needle = 'v_'.$_SESSION['domain_name'].'-';
		}
		else {
			$v_needle = 'v_';
		}
		$gateway_list = glob($_SESSION['switch']['sip_profiles']['dir'] . "/*/".$v_needle."*.xml");
		foreach ($gateway_list as $gateway_file) {
			unlink($gateway_file);
		}

	//get the list of gateways and write the xml
		$sql = "select * from v_gateways ";
		$sql .= "where (domain_uuid = :domain_uuid or domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
		$database = new database;
		$result = $database->select($sql, $parameters, 'all');
		if (!empty($result)) {
			foreach ($result as &$row) {
				if ($row['enabled'] != "false") {
						//set the default profile as external
							$profile = $row['profile'];
							if (empty($profile)) {
								$profile = "external";
							}
						//open the xml file
							$fout = fopen($_SESSION['switch']['sip_profiles']['dir']."/".$profile."/v_".strtolower($row['gateway_uuid']).".xml","w");
						//build the xml
							$xml .= "<include>\n";
							$xml .= "    <gateway name=\"" . strtolower($row['gateway_uuid']) . "\">\n";
							if (!empty($row['username'])) {
								$xml .= "      <param name=\"username\" value=\"" . $row['username'] . "\"/>\n";
							}
							if (!empty($row['distinct_to'])) {
								$xml .= "      <param name=\"distinct-to\" value=\"" . $row['distinct_to'] . "\"/>\n";
							}
							if (!empty($row['auth_username'])) {
								$xml .= "      <param name=\"auth-username\" value=\"" . $row['auth_username'] . "\"/>\n";
							}
							if (!empty($row['password'])) {
								$xml .= "      <param name=\"password\" value=\"" . $row['password'] . "\"/>\n";
							}
							if (!empty($row['realm'])) {
								$xml .= "      <param name=\"realm\" value=\"" . $row['realm'] . "\"/>\n";
							}
							if (!empty($row['from_user'])) {
								$xml .= "      <param name=\"from-user\" value=\"" . $row['from_user'] . "\"/>\n";
							}
							if (!empty($row['from_domain'])) {
								$xml .= "      <param name=\"from-domain\" value=\"" . $row['from_domain'] . "\"/>\n";
							}
							if (!empty($row['proxy'])) {
								$xml .= "      <param name=\"proxy\" value=\"" . $row['proxy'] . "\"/>\n";
							}
							if (!empty($row['register_proxy'])) {
								$xml .= "      <param name=\"register-proxy\" value=\"" . $row['register_proxy'] . "\"/>\n";
							}
							if (!empty($row['outbound_proxy'])) {
								$xml .= "      <param name=\"outbound-proxy\" value=\"" . $row['outbound_proxy'] . "\"/>\n";
							}
							if (!empty($row['expire_seconds'])) {
								$xml .= "      <param name=\"expire-seconds\" value=\"" . $row['expire_seconds'] . "\"/>\n";
							}
							if (!empty($row['register'])) {
								$xml .= "      <param name=\"register\" value=\"" . $row['register'] . "\"/>\n";
							}

							if (!empty($row['register_transport'])) {
								switch ($row['register_transport']) {
								case "udp":
									$xml .= "      <param name=\"register-transport\" value=\"udp\"/>\n";
									break;
								case "tcp":
									$xml .= "      <param name=\"register-transport\" value=\"tcp\"/>\n";
									break;
								case "tls":
									$xml .= "      <param name=\"register-transport\" value=\"tls\"/>\n";
									break;
								default:
									$xml .= "      <param name=\"register-transport\" value=\"" . $row['register_transport'] . "\"/>\n";
								}
							}

							if (!empty($row['contact_params'])) {
								$xml .= "      <param name=\"contact-params\" value=\"" . $row['contact_params'] . "\"/>\n";
							}

							if (!empty($row['retry_seconds'])) {
								$xml .= "      <param name=\"retry-seconds\" value=\"" . $row['retry_seconds'] . "\"/>\n";
							}
							if (!empty($row['extension'])) {
								$xml .= "      <param name=\"extension\" value=\"" . $row['extension'] . "\"/>\n";
							}
							if (!empty($row['ping'])) {
								$xml .= "      <param name=\"ping\" value=\"" . $row['ping'] . "\"/>\n";
							}
							if (!empty($row['context'])) {
								$xml .= "      <param name=\"context\" value=\"" . $row['context'] . "\"/>\n";
							}
							if (!empty($row['caller_id_in_from'])) {
								$xml .= "      <param name=\"caller-id-in-from\" value=\"" . $row['caller_id_in_from'] . "\"/>\n";
							}
							if (!empty($row['supress_cng'])) {
								$xml .= "      <param name=\"supress-cng\" value=\"" . $row['supress_cng'] . "\"/>\n";
							}
							if (!empty($row['sip_cid_type'])) {
								$xml .= "      <param name=\"sip_cid_type\" value=\"" . $row['sip_cid_type'] . "\"/>\n";
							}
							if (!empty($row['extension_in_contact'])) {
								$xml .= "      <param name=\"extension-in-contact\" value=\"" . $row['extension_in_contact'] . "\"/>\n";
							}

							$xml .= "    </gateway>\n";
							$xml .= "</include>";

						//write the xml
							fwrite($fout, $xml);
							unset($xml);
							fclose($fout);
				}

			}
		}
		unset($sql, $parameters, $result, $row);

	//apply settings
		$_SESSION["reload_xml"] = true;

}

function save_var_xml() {
	if (is_array($_SESSION['switch']['conf'])) {
		global $config, $domain_uuid;

		//skip this function if the conf directory is empty
		if (empty($_SESSION['switch']['conf']['dir'])) {
			return false;
		}

		//open the vars.xml file
		$fout = fopen($_SESSION['switch']['conf']['dir']."/vars.xml","w");

		//get the hostname
		$hostname = trim(event_socket_request_cmd('api switchname'));
		if (empty($hostname)){
			$hostname = trim(gethostname());
		}
		if (empty($hostname)){
			return;
		}

		//build the xml
		$sql = "select * from v_vars ";
		$sql .= "where var_enabled = 'true' ";
		$sql .= "order by var_category, var_order asc ";
		$database = new database;
		$variables = $database->select($sql, null, 'all');
		$prev_var_category = '';
		$xml = '';
		if (!empty($variables)) {
			foreach ($variables as &$row) {
				if ($row['var_category'] != 'Provision') {
					if ($prev_var_category != $row['var_category']) {
						$xml .= "\n<!-- ".$row['var_category']." -->\n";
					}
					if (empty($row['var_command'])) { $row['var_command'] = 'set'; }
					if ($row['var_category'] == 'Exec-Set') { $row['var_command'] = 'exec-set'; }
					if (empty($row['var_hostname'])) {
						$xml .= "<X-PRE-PROCESS cmd=\"".$row['var_command']."\" data=\"".$row['var_name']."=".$row['var_value']."\" />\n";
					} elseif ($row['var_hostname'] == $hostname) {
						$xml .= "<X-PRE-PROCESS cmd=\"".$row['var_command']."\" data=\"".$row['var_name']."=".$row['var_value']."\" />\n";
					}
				}
				$prev_var_category = $row['var_category'];
			}
		}
		$xml .= "\n";
		fwrite($fout, $xml);
		unset($sql, $variables, $xml);
		fclose($fout);

		//apply settings
		$_SESSION["reload_xml"] = true;

		//$cmd = "api reloadxml";
		//event_socket_request_cmd($cmd);
		//unset($cmd);
	}
}

function outbound_route_to_bridge($domain_uuid, $destination_number, array $channel_variables=null) {

	$destination_number = trim($destination_number);
	preg_match('/^[\*\+0-9]*$/', $destination_number, $matches, PREG_OFFSET_CAPTURE);
	if (count($matches) > 0) {
		//not found, continue to process the function
	}
	else {
		//not a number, brige_array and exit the function
		$bridge_array[0] = $destination_number;
		return $bridge_array;
	}

	//get the hostname
	$hostname = trim(event_socket_request_cmd('api switchname'));
	if (empty($hostname)) {
		$hostname = 'unknown';
	}

	$sql = "select d.dialplan_uuid, ";
	$sql .= "d.dialplan_name, "; 
	$sql .= "dd.dialplan_detail_uuid, ";
	$sql .= "dd.dialplan_detail_tag, ";
	$sql .= "dd.dialplan_detail_type, ";
	$sql .= "dd.dialplan_detail_data , ";
	$sql .= "d.dialplan_continue ";
	$sql .= "from v_dialplans d, v_dialplan_details dd  ";
	$sql .= "where d.dialplan_uuid = dd.dialplan_uuid ";
	if (is_uuid($domain_uuid)) {
		$sql .= "and (d.domain_uuid = :domain_uuid or d.domain_uuid is null) ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	else {
		$sql .= "and (d.domain_uuid is null) ";
	}
	$sql .= "and (hostname = :hostname or hostname is null) ";
	$sql .= "and d.app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	$sql .= "and d.dialplan_enabled = 'true' ";
	$sql .= "and (dd.dialplan_detail_enabled = 'true' or dd.dialplan_detail_enabled is null) ";
	$sql .= "order by d.domain_uuid,  d.dialplan_order, dd.dialplan_detail_order ";
	$parameters['hostname'] = $hostname;
	$database = new database;
	$result = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);
	if (!empty($result)) {
		foreach ($result as &$row) {
			$dialplan_uuid = $row["dialplan_uuid"];
			$dialplan_detail_uuid = $row["dialplan_detail_uuid"];
			$outbound_routes[$dialplan_uuid][$dialplan_detail_uuid]["dialplan_detail_tag"] = $row["dialplan_detail_tag"];
			$outbound_routes[$dialplan_uuid][$dialplan_detail_uuid]["dialplan_detail_type"] = $row["dialplan_detail_type"];
			$outbound_routes[$dialplan_uuid][$dialplan_detail_uuid]["dialplan_detail_data"] = $row["dialplan_detail_data"];
			$outbound_routes[$dialplan_uuid]["dialplan_continue"] = $row["dialplan_continue"];
		}
	}
	
	if (!empty($outbound_routes)) {
		$x = 0;
		foreach ($outbound_routes as &$dialplan) {
			$condition_match = [];
			foreach ($dialplan as &$dialplan_details) {
				if (!empty($dialplan_details['dialplan_detail_tag']) && $dialplan_details['dialplan_detail_tag'] == "condition") {
					if ($dialplan_details['dialplan_detail_type'] == "destination_number") {
							$pattern = '/'.$dialplan_details['dialplan_detail_data'].'/';
							preg_match($pattern, $destination_number, $matches, PREG_OFFSET_CAPTURE);
							if (count($matches) == 0) {
								$condition_match[] = 'false';
							}
							else {
								$condition_match[] = 'true';
								$regex_match_1 = $matches[1][0] ?? '';
								$regex_match_2 = $matches[2][0] ?? '';
								$regex_match_3 = $matches[3][0] ?? '';
								$regex_match_4 = $matches[4][0] ?? '';
								$regex_match_5 = $matches[5][0] ?? '';
							}
					}
					elseif ($dialplan_details['dialplan_detail_type'] == "\${toll_allow}") {
						$pattern = '/'.$dialplan_details['dialplan_detail_data'].'/';
						preg_match($pattern, $channel_variables['toll_allow'], $matches, PREG_OFFSET_CAPTURE);
						if (count($matches) == 0) {
							$condition_match[] = 'false';
						} 
						else {
							$condition_match[] = 'true';
						}
					}
				}
			}

			if (!in_array('false', $condition_match)) {
				foreach ($dialplan as &$dialplan_details) {
					$dialplan_detail_data = $dialplan_details['dialplan_detail_data'] ?? '';
					if (
						!empty($dialplan_details['dialplan_detail_tag']) &&
						$dialplan_details['dialplan_detail_tag'] == "action" &&
						!empty($dialplan_details['dialplan_detail_type']) &&
						$dialplan_details['dialplan_detail_type'] == "bridge" &&
						$dialplan_detail_data != "\${enum_auto_route}"
						) {
						$dialplan_detail_data = str_replace("\$1", $regex_match_1, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$2", $regex_match_2, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$3", $regex_match_3, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$4", $regex_match_4, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$5", $regex_match_5, $dialplan_detail_data);
						$bridge_array[$x] = $dialplan_detail_data;
						$x++;
					}
				}

				if (!empty($bridge_array) && $dialplan["dialplan_continue"] == "false") {
					break;
				}
			}
		}
	}
	return $bridge_array ?? [];
}
//$destination_number = '1231234';
//$bridge_array = outbound_route_to_bridge ($domain_uuid, $destination_number);
//foreach ($bridge_array as &$bridge) {
//	echo "bridge: ".$bridge."<br />";
//}

function extension_exists($extension) {
	global $domain_uuid;

	$sql = "select count(*) from v_extensions ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and ( ";
	$sql .= "extension = :extension ";
	$sql .= "or number_alias = :extension ";
	$sql .= ") ";
	$sql .= "and enabled = 'true' ";
	$parameters['domain_uuid'] = $domain_uuid;
	$parameters['extension'] = $extension;
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);
	return $num_rows > 0 ? true : false;
}

function extension_presence_id($extension, $number_alias = false) {
	global $domain_uuid;

	if ($number_alias === false) {
		$sql = "select extension, number_alias from v_extensions ";
		$sql .= "where domain_uuid = :domain_uuid ";
		$sql .= "and ( ";
		$sql .= "extension = :extension ";
		$sql .= "or number_alias = :extension ";
		$sql .= ") ";
		$parameters['domain_uuid'] = $domain_uuid;
		$parameters['extension'] = $extension;
		$database = new database;
		$row = $database->select($sql, $parameters, 'row');
		if (!empty($row)) {
			$extension = $row['extension'];
			$number_alias = $row['number_alias'];
		}
		else {
			return false;
		}
		unset($sql, $parameters, $row);
	}

	if (!empty($number_alias)) {
		if ($_SESSION['provision']['number_as_presence_id']['text'] === 'true') {
			return $number_alias;
		}
	}
	return $extension;
}

function get_recording_filename($id) {
	global $domain_uuid;

	$sql = "select * from v_recordings ";
	$sql .= "where recording_uuid = :recording_uuid ";
	$sql .= "and domain_uuid = :domain_uuid ";
	$parameters['recording_uuid'] = $id;
	$parameters['domain_uuid'] = $domain_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (!empty($row)) {
		//$filename = $row["filename"];
		//$recording_name = $row["recording_name"];
		//$recording_uuid = $row["recording_uuid"];
		return $row["filename"];
	}
	unset($sql, $parameters, $row);
}

function dialplan_add($domain_uuid, $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid) {
	//build insert array
		$array['dialplans'][0]['dialplan_uuid'] = $dialplan_uuid;
		$array['dialplans'][0]['domain_uuid'] = $domain_uuid;
		if (is_uuid($app_uuid)) {
			$array['dialplans'][0]['app_uuid'] = $app_uuid;
		}
		$array['dialplans'][0]['dialplan_name'] = $dialplan_name;
		$array['dialplans'][0]['dialplan_order'] = $dialplan_order;
		$array['dialplans'][0]['dialplan_context'] = $dialplan_context;
		$array['dialplans'][0]['dialplan_enabled'] = $dialplan_enabled;
		$array['dialplans'][0]['dialplan_description'] = $dialplan_description;
	//grant temporary permissions
		$p = new permissions;
		$p->add('dialplan_add', 'temp');
	//execute insert
		$database = new database;
		$database->app_name = 'switch-function-dialplan_add';
		$database->app_uuid = '2fa2243c-47a1-41a0-b144-eb2b609219e0';
		$database->save($array);
		unset($array);
	//revoke temporary permissions
		$p->delete('dialplan_add', 'temp');
}

if (!function_exists('phone_letter_to_number')) {
	function phone_letter_to_number($tmp) {
		$tmp = strtolower($tmp);
		if ($tmp == "a" | $tmp == "b" | $tmp == "c") { return 2; }
		if ($tmp == "d" | $tmp == "e" | $tmp == "f") { return 3; }
		if ($tmp == "g" | $tmp == "h" | $tmp == "i") { return 4; }
		if ($tmp == "j" | $tmp == "k" | $tmp == "l") { return 5; }
		if ($tmp == "m" | $tmp == "n" | $tmp == "o") { return 6; }
		if ($tmp == "p" | $tmp == "q" | $tmp == "r" | $tmp == "s") { return 7; }
		if ($tmp == "t" | $tmp == "u" | $tmp == "v") { return 8; }
		if ($tmp == "w" | $tmp == "x" | $tmp == "y" | $tmp == "z") { return 9; }
	}
}

if (!function_exists('save_call_center_xml')) {
	function save_call_center_xml() {
		global $domain_uuid;

		if (!empty($_SESSION['switch']['call_center']['dir'])) {

			//get the call center queue array
			$sql = "select * from v_call_center_queues ";
			$database = new database;
			$call_center_queues = $database->select($sql, null, 'all');
			unset($sql);

			if (!empty($call_center_queues)) {

				//prepare Queue XML string
					$x=0;
					foreach ($call_center_queues as &$row) {
						$queue_name = $row["queue_name"];
						$queue_extension = $row["queue_extension"];
						$queue_strategy = $row["queue_strategy"];
						$queue_moh_sound = $row["queue_moh_sound"];
						$queue_record_template = $row["queue_record_template"];
						$queue_time_base_score = $row["queue_time_base_score"];
						$queue_max_wait_time = $row["queue_max_wait_time"];
						$queue_max_wait_time_with_no_agent = $row["queue_max_wait_time_with_no_agent"];
						$queue_tier_rules_apply = $row["queue_tier_rules_apply"];
						$queue_tier_rule_wait_second = $row["queue_tier_rule_wait_second"];
						$queue_tier_rule_wait_multiply_level = $row["queue_tier_rule_wait_multiply_level"];
						$queue_tier_rule_no_agent_no_wait = $row["queue_tier_rule_no_agent_no_wait"];
						$queue_discard_abandoned_after = $row["queue_discard_abandoned_after"];
						$queue_abandoned_resume_allowed = $row["queue_abandoned_resume_allowed"];
						$queue_announce_sound = $row["queue_announce_sound"];
						$queue_announce_frequency = $row ["queue_announce_frequency"];
						$queue_description = $row["queue_description"];

						//replace space with an underscore
						$queue_name = str_replace(" ", "_", $queue_name);

						if ($x > 0) {
							$v_queues .= "\n";
							$v_queues .= "		";
						}
						$v_queues .= "		<queue name=\"$queue_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\">\n";
						$v_queues .= "			<param name=\"strategy\" value=\"$queue_strategy\"/>\n";
						if (empty($queue_moh_sound)) {
							$v_queues .= "			<param name=\"moh-sound\" value=\"local_stream://default\"/>\n";
						}
						else {
							if (substr($queue_moh_sound, 0, 15) == 'local_stream://') {
								$v_queues .= "			<param name=\"moh-sound\" value=\"".$queue_moh_sound."\"/>\n";
							}
							elseif (substr($queue_moh_sound, 0, 2) == '${' && substr($queue_moh_sound, -5) == 'ring}') {
								$v_queues .= "			<param name=\"moh-sound\" value=\"tone_stream://".$queue_moh_sound.";loops=-1\"/>\n";
							}
							else {
								$v_queues .= "			<param name=\"moh-sound\" value=\"".$queue_moh_sound."\"/>\n";
							}
						}
						if (!empty($queue_record_template)) {
							$v_queues .= "			<param name=\"record-template\" value=\"$queue_record_template\"/>\n";
						}
						$v_queues .= "			<param name=\"time-base-score\" value=\"$queue_time_base_score\"/>\n";
						$v_queues .= "			<param name=\"max-wait-time\" value=\"$queue_max_wait_time\"/>\n";
						$v_queues .= "			<param name=\"max-wait-time-with-no-agent\" value=\"$queue_max_wait_time_with_no_agent\"/>\n";
						$v_queues .= "			<param name=\"max-wait-time-with-no-agent-time-reached\" value=\"$queue_max_wait_time_with_no_agent_time_reached\"/>\n";
						$v_queues .= "			<param name=\"tier-rules-apply\" value=\"$queue_tier_rules_apply\"/>\n";
						$v_queues .= "			<param name=\"tier-rule-wait-second\" value=\"$queue_tier_rule_wait_second\"/>\n";
						$v_queues .= "			<param name=\"tier-rule-wait-multiply-level\" value=\"$queue_tier_rule_wait_multiply_level\"/>\n";
						$v_queues .= "			<param name=\"tier-rule-no-agent-no-wait\" value=\"$queue_tier_rule_no_agent_no_wait\"/>\n";
						$v_queues .= "			<param name=\"discard-abandoned-after\" value=\"$queue_discard_abandoned_after\"/>\n";
						$v_queues .= "			<param name=\"abandoned-resume-allowed\" value=\"$queue_abandoned_resume_allowed\"/>\n";
						$v_queues .= "			<param name=\"announce-sound\" value=\"$queue_announce_sound\"/>\n";
						$v_queues .= "			<param name=\"announce-frequency\" value=\"$queue_announce_frequency\"/>\n";
						$v_queues .= "		</queue>";
						$x++;
					}

				//prepare Agent XML string
					$v_agents = '';
					$sql = "select * from v_call_center_agents ";
					$database = new database;
					$result = $database->select($sql, null, 'all');
					unset($sql);

					$x=0;
					if (!empty($result)) {
						foreach ($result as &$row) {
							//get the values from the db and set as php variables
								$agent_name = $row["agent_name"];
								$agent_type = $row["agent_type"];
								$agent_call_timeout = $row["agent_call_timeout"];
								$agent_contact = $row["agent_contact"];
								$agent_status = $row["agent_status"];
								$agent_no_answer_delay_time = $row["agent_no_answer_delay_time"];
								$agent_max_no_answer = $row["agent_max_no_answer"];
								$agent_wrap_up_time = $row["agent_wrap_up_time"];
								$agent_reject_delay_time = $row["agent_reject_delay_time"];
								$agent_busy_delay_time = $row["agent_busy_delay_time"];
								if ($x > 0) {
									$v_agents .= "\n";
									$v_agents .= "		";
								}

							//get and then set the complete agent_contact with the call_timeout and when necessary confirm
								//$tmp_confirm = "group_confirm_file=custom/press_1_to_accept_this_call.wav,group_confirm_key=1";
								//if you change this variable also change app/call_center/call_center_agent_edit.php
								$tmp_confirm = "group_confirm_file=custom/press_1_to_accept_this_call.wav,group_confirm_key=1,group_confirm_read_timeout=2000,leg_timeout=".$agent_call_timeout;
								if(strstr($agent_contact, '}') === FALSE) {
									//not found
									if(stristr($agent_contact, 'sofia/gateway') === FALSE) {
										//add the call_timeout
										$tmp_agent_contact = "{call_timeout=".$agent_call_timeout."}".$agent_contact;
									}
									else {
										//add the call_timeout and confirm
										$tmp_agent_contact = $tmp_first.',call_timeout='.$agent_call_timeout.$tmp_last;
										$tmp_agent_contact = "{".$tmp_confirm.",call_timeout=".$agent_call_timeout."}".$agent_contact;
									}
								}
								else {
									//found
									if(stristr($agent_contact, 'sofia/gateway') === FALSE) {
										//not found
										if(stristr($agent_contact, 'call_timeout') === FALSE) {
											//add the call_timeout
											$tmp_pos = strrpos($agent_contact, "}");
											$tmp_first = substr($agent_contact, 0, $tmp_pos);
											$tmp_last = substr($agent_contact, $tmp_pos);
											$tmp_agent_contact = $tmp_first.',call_timeout='.$agent_call_timeout.$tmp_last;
										}
										else {
											//the string has the call timeout
											$tmp_agent_contact = $agent_contact;
										}
									}
									else {
										//found
										$tmp_pos = strrpos($agent_contact, "}");
										$tmp_first = substr($agent_contact, 0, $tmp_pos);
										$tmp_last = substr($agent_contact, $tmp_pos);
										if(stristr($agent_contact, 'call_timeout') === FALSE) {
											//add the call_timeout and confirm
											$tmp_agent_contact = $tmp_first.','.$tmp_confirm.',call_timeout='.$agent_call_timeout.$tmp_last;
										}
										else {
											//add confirm
											$tmp_agent_contact = $tmp_first.','.$tmp_confirm.$tmp_last;
										}
									}
								}

							$v_agents .= "<agent ";
							$v_agents .= "name=\"$agent_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\" ";
							$v_agents .= "type=\"$agent_type\" ";
							$v_agents .= "contact=\"$tmp_agent_contact\" ";
							$v_agents .= "status=\"$agent_status\" ";
							$v_agents .= "no-answer-delay-time=\"$agent_no_answer_delay_time\" ";
							$v_agents .= "max-no-answer=\"$agent_max_no_answer\" ";
							$v_agents .= "wrap-up-time=\"$agent_wrap_up_time\" ";
							$v_agents .= "reject-delay-time=\"$agent_reject_delay_time\" ";
							$v_agents .= "busy-delay-time=\"$agent_busy_delay_time\" ";
							$v_agents .= "/>";
							$x++;
						}
					}
					unset($result, $row);

				//prepare Tier XML string
					$v_tiers = '';
					$sql = "select * from v_call_center_tiers ";
					$database = new database;
					$result = $database->select($sql, null, 'all');
					unset($sql);

					$x=0;
					if (!empty($result)) {
						foreach ($result as &$row) {
							$agent_name = $row["agent_name"];
							$queue_name = $row["queue_name"];
							$tier_level = $row["tier_level"];
							$tier_position = $row["tier_position"];
							if ($x > 0) {
								$v_tiers .= "\n";
								$v_tiers .= "		";
							}
							$v_tiers .= "<tier agent=\"$agent_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\" queue=\"$queue_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\" level=\"$tier_level\" position=\"$tier_position\"/>";
							$x++;
						}
					}
					unset($result, $row);

				//set the path
					if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
						$path = "/usr/share/examples/fusionpbx/resources/templates/conf";
					}
					else {
						$path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/switch/resources/conf";
					}

				//get the contents of the template
					$file_contents = file_get_contents($path."/autoload_configs/callcenter.conf.xml.noload");

				//add the Call Center Queues, Agents and Tiers to the XML config
					$file_contents = str_replace("{v_queues}", $v_queues, $file_contents);
					unset($v_queues);

					$file_contents = str_replace("{v_agents}", $v_agents, $file_contents);
					unset($v_agents);

					$file_contents = str_replace("{v_tiers}", $v_tiers, $file_contents);
					unset($v_tiers);

				//write the XML config file
					$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/callcenter.conf.xml","w");
					fwrite($fout, $file_contents);
					fclose($fout);

				//apply settings
					$_SESSION["reload_xml"] = true;

			}
			unset($call_center_queues);
		}
	}
}

if (!function_exists('switch_conf_xml')) {
	function switch_conf_xml() {
		//get the contents of the template
			if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
				$path = "/usr/share/examples/fusionpbx/resources/templates/conf";
			}
			else {
				$path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/switch/resources/conf";
			}
			$file_contents = file_get_contents($path."/autoload_configs/switch.conf.xml");

		//prepare the php variables
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				$php_bin = win_find_php('php.exe');
				if(!$php_bin){ // relay on system path
					$php_bin = 'php.exe';
				}

				$secure_path = path_join($_SERVER["DOCUMENT_ROOT"], PROJECT_PATH, 'secure');

				$v_mail_bat = path_join($secure_path, 'mailto.bat');
				$v_mail_cmd = '@' .
					'"' . str_replace('/', '\\', $php_bin) . '" ' .
					'"' . str_replace('/', '\\', path_join($secure_path, 'v_mailto.php')) . '" ';

				$fout = fopen($v_mail_bat, "w+");
				fwrite($fout, $v_mail_cmd);
				fclose($fout);

				$v_mailer_app = '"' .  str_replace('/', '\\', $v_mail_bat) . '"';
				$v_mailer_app_args = "";
				unset($v_mail_bat, $v_mail_cmd, $secure_path, $php_bin, $fout);
			}
			else {
				if (file_exists(PHP_BINDIR.'/php')) { define("PHP_BIN", "php"); }
				$v_mailer_app = PHP_BINDIR."/".PHP_BIN." ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/v_mailto.php";
				$v_mailer_app_args = "-t";
			}

		//replace the values in the template
			$file_contents = str_replace("{v_mailer_app}", $v_mailer_app, $file_contents);
			unset ($v_mailer_app);

		//replace the values in the template
			$file_contents = str_replace("{v_mailer_app_args}", $v_mailer_app_args, $file_contents);
			unset ($v_mailer_app_args);

		//write the XML config file
			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/switch.conf.xml","w");
			fwrite($fout, $file_contents);
			fclose($fout);

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('xml_cdr_conf_xml')) {
	function xml_cdr_conf_xml() {
		//get the contents of the template
		 	if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
				$path = "/usr/share/examples/fusionpbx/resources/templates/conf";
			}
			else {
				$path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/switch/resources/conf";
			}
			$file_contents = file_get_contents($path."/autoload_configs/xml_cdr.conf.xml");

		//replace the values in the template
			$file_contents = str_replace("{v_http_protocol}", "http", $file_contents);
			$file_contents = str_replace("{domain_name}", "127.0.0.1", $file_contents);
			$file_contents = str_replace("{v_project_path}", PROJECT_PATH, $file_contents);

			$v_user = generate_password();
			$file_contents = str_replace("{v_user}", $v_user, $file_contents);
			unset ($v_user);

			$v_pass = generate_password();
			$file_contents = str_replace("{v_pass}", $v_pass, $file_contents);
			unset ($v_pass);

		//write the XML config file
			$switch_configuration_dir = !empty($_SESSION['switch']['conf']['dir']) ? $_SESSION['switch']['conf']['dir'] : '/etc/freeswitch';
			$fout = fopen($switch_configuration_dir . "/autoload_configs/xml_cdr.conf.xml","w");
			fwrite($fout, $file_contents);
			fclose($fout);

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('save_sip_profile_xml')) {
	function save_sip_profile_xml() {
		//skip saving the sip profile xml if the directory is not set
			if (empty($_SESSION['switch']['sip_profiles']['dir'])) {
				return;
			}

		// make profile dir if needed
			$profile_dir = $_SESSION['switch']['conf']['dir']."/sip_profiles";
			if (!is_readable($profile_dir)) {
				mkdir($profile_dir, 0770, false);
			}

		//get the sip profiles from the database
			$sql = "select * from v_sip_profiles";
			$database = new database;
			$result = $database->select($sql, null, 'all');
			unset($sql);

			if (!empty($result)) {
				foreach($result as $row) {
					$sip_profile_uuid = $row['sip_profile_uuid'];
					$sip_profile_name = $row['sip_profile_name'];
					$sip_profile_enabled = $row['sip_profile_enabled'];

					if ($sip_profile_enabled == 'false') {
						$fout = fopen($profile_dir.'/'.$sip_profile_name.".xml","w");
						if ($fout) {
							fclose($fout);
						}
						continue;
					}

					//get the xml sip profile template
						if ($sip_profile_name == "internal" || $sip_profile_name == "external" || $sip_profile_name == "internal-ipv6") {
							$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/sip_profiles/resources/xml/sip_profiles/".$sip_profile_name.".xml");
						}
						else {
							$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/sip_profiles/resources/xml/sip_profiles/default.xml");
						}

					//get the sip profile settings
						$sql = "select * from v_sip_profile_settings ";
						$sql .= "where sip_profile_uuid = :sip_profile_uuid ";
						$sql .= "and sip_profile_setting_enabled = 'true' ";
						$parameters['sip_profile_uuid'] = $sip_profile_uuid;
						$database = new database;
						$result_2 = $database->select($sql, $parameters, 'all');
						if (!empty($result_2)) {
							$sip_profile_settings = '';
							foreach ($result_2 as &$row_2) {
								$sip_profile_settings .= "		<param name=\"".$row_2["sip_profile_setting_name"]."\" value=\"".$row_2["sip_profile_setting_value"]."\"/>\n";
							}
						}
						unset($sql, $parameters, $result_2, $row_2);

					//replace the values in the template
						$file_contents = str_replace("{v_sip_profile_name}", $sip_profile_name, $file_contents);
						$file_contents = str_replace("{v_sip_profile_settings}", $sip_profile_settings, $file_contents);

					//write the XML config file
						if (is_readable($profile_dir.'/')) {
							$fout = fopen($profile_dir.'/'.$sip_profile_name.".xml","w");
							fwrite($fout, $file_contents);
							fclose($fout);
						}

					//if the directory does not exist then create it
						if (!is_readable($profile_dir.'/'.$sip_profile_name)) {
							mkdir($profile_dir.'/'.$sip_profile_name, 0770, false);
						}

				}
				unset($result, $row);
			}

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('save_switch_xml')) {
	function save_switch_xml() {
		if (is_readable($_SESSION['switch']['extensions']['dir'])) {
			if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/extensions/resources/classes/extension.php")) {
				require_once $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."app/extensions/resources/classes/extension.php";
				$extension = new extension;
				$extension->xml();
			}
		}
		if (is_readable($_SESSION['switch']['conf']['dir'])) {
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/settings/app_config.php")) {
				save_setting_xml();
			}
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/modules/app_config.php")) {
				require_once $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/modules/resources/classes/modules.php";
				$module = new modules;
				$module->xml();
				//$msg = $module->msg;
			}
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/vars/app_config.php")) {
				save_var_xml();
			}
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/call_center/app_config.php")) {
				save_call_center_xml();
			}
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/gateways/app_config.php")) {
				save_gateway_xml();
			}
			//if (file_exists($_SERVER["PROJECT_ROOT"]."/app/ivr_menu/app_config.php")) {
			//	save_ivr_menu_xml();
			//}
			if (file_exists($_SERVER["PROJECT_ROOT"]."/app/sip_profiles/app_config.php")) {
				save_sip_profile_xml();
			}
		}
	}
}

if(!function_exists('path_join')) {
	function path_join() {
		$args = func_get_args();
		$paths = array();
		foreach ($args as $arg) {
			$paths = array_merge($paths, (array)$arg);
		}

		$prefix = null;
		foreach($paths as &$path) {
			if($prefix === null && !empty($path)) {
				if(substr($path, 0, 1) == '/') $prefix = '/';
				else $prefix = '';
			}
			$path = trim( $path, '/' );
			$path = trim( $path, '\\' );
		}

		if($prefix === null){
			return '';
		}

		$paths = array_filter($paths);

		return $prefix . join('/', $paths);
	}
}

if(!function_exists('win_find_php')) {
	function win_find_php_in_root($root, $bin){
		while(true) {
			$php_bin = path_join($root, $bin);
			if(file_exists($php_bin)){
				$php_bin = str_replace('/', '\\', $php_bin);
				return $php_bin;
			}
			$prev_root = $root;
			$root = dirname($root);
			if((!$root)&&($prev_root == $root)){
				return false;
			}
		}
	}

	//Tested on WAMP and OpenServer
	//Can get wrong result if `extension_dir` set as relative path.
	function win_find_php_by_extension($bin_name){
		$bin_dir = get_cfg_var('extension_dir');
		return win_find_php_in_root($bin_dir, $bin_name);
	}

	// Works since PHP 5.4
	function win_find_php_by_binary($bin_name){
		if(!defined('PHP_BINARY')){
			return false;
		}
		$bin_dir = realpath(PHP_BINARY);
		if(!$bin_dir){
			$bin_dir = PHP_BINARY;
		}
		$bin_dir = dirname($bin_dir);
		return win_find_php_in_root($bin_dir, $bin_name);
	}

	function win_find_php_by_phprc($bin_name){
		$bin_dir = getenv(PHPRC);
		if(!$bin_dir){
			return false;
		}
		$bin_dir = realpath($bin_dir);
		return win_find_php_in_root($bin_dir, $bin_name);
	}

	//on Windows PHP_BIN set in compile time to c:\php
	//It possible redifine it in env, but not all installation do it
	function win_find_php_by_bin($bin_name){
		if(!defined('PHP_BIN')){
			return false;
		}
		$bin_dir = realpath(PHP_BIN);
		if(!$bin_dir){
			$bin_dir = PHP_BIN;
		}
		$bin_dir = dirname($bin_dir);
		return win_find_php_in_root($bin_dir, $bin_name);
	}

	function win_find_php($bin_name){
		$php_bin = win_find_php_by_binary($bin_name);
		if($php_bin) return $php_bin;
		$php_bin = win_find_php_by_extension($bin_name);
		if($php_bin) return $php_bin;
		$php_bin = win_find_php_by_bin($bin_name);
		if($php_bin) return $php_bin;
		$php_bin = win_find_php_by_phprc($bin_name);
		if($php_bin) return $php_bin;
		return false;
	}
}

?>
