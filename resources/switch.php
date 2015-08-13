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
	Portions created by the Initial Developer are Copyright (C) 2008-2015
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
	Riccardo Granchi <riccardo.granchi@nems.it>
*/
require_once "root.php";
require_once "resources/require.php";

//get the event socket information
	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/settings/app_config.php")) {
		if (strlen($_SESSION['event_socket_ip_address']) == 0) {
			$sql = "select * from v_settings ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				foreach ($result as &$row) {
					$_SESSION['event_socket_ip_address'] = $row["event_socket_ip_address"];
					$_SESSION['event_socket_port'] = $row["event_socket_port"];
					$_SESSION['event_socket_password'] = $row["event_socket_password"];
					break; //limit to 1 row
				}
			}
		}
	}

//get the extensions that are assigned to this user
function load_extensions() {
	global $db;
	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/extensions/app_config.php")) {
		if (strlen($_SESSION["domain_uuid"]) > 0 && strlen($_SESSION["user_uuid"]) > 0 && count($_SESSION['user']['extension']) == 0) {
			//get the user extension list
				unset($_SESSION['user']['extension']);
				$sql = "select ";
				$sql .= "	e.extension, ";
				$sql .= "	e.number_alias, ";
				$sql .= "	e.user_context, ";
				$sql .= "	e.extension_uuid, ";
				$sql .= "	e.outbound_caller_id_name, ";
				$sql .= "	e.outbound_caller_id_number, ";
				$sql .= "	v.voicemail_uuid ";
				$sql .= "from ";
				$sql .= "	v_extension_users as u, ";
				$sql .= "	v_extensions as e ";
				$sql .= "		left outer join v_voicemails as v on ( ";
				$sql .= "			e.domain_uuid = v.domain_uuid ";
				$sql .= "			and v.voicemail_enabled = 'true' ";
				$sql .= "			and ( ";
				$sql .= "				e.extension = v.voicemail_id ";
				$sql .= "				or e.number_alias = v.voicemail_id ";
				$sql .= "			) ";
				$sql .= "		) ";
				$sql .= "where ";
				$sql .= "	e.domain_uuid = '".$_SESSION['domain_uuid']."' ";
				$sql .= "	and e.extension_uuid = u.extension_uuid ";
				$sql .= "	and u.user_uuid = '".$_SESSION['user_uuid']."' ";
				$sql .= "	and e.enabled = 'true' ";
				$sql .= "order by ";
				$sql .= "	e.extension asc ";
				$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
				if (count($result) > 0) {
					$x = 0;
					foreach($result as $row) {
						$_SESSION['user']['extension'][$x]['user'] = $row['extension'];
						$_SESSION['user']['extension'][$x]['number_alias'] = $row['number_alias'];
						$_SESSION['user']['extension'][$x]['extension_uuid'] = $row['extension_uuid'];
						$_SESSION['user']['extension'][$x]['outbound_caller_id_name'] = $row['outbound_caller_id_name'];
						$_SESSION['user']['extension'][$x]['outbound_caller_id_number'] = $row['outbound_caller_id_number'];
						if ($row['voicemail_uuid'] != '') {
							$_SESSION['user']['voicemail'][]['voicemail_uuid'] = $row['voicemail_uuid'];
						}
						$_SESSION['user_context'] = $row["user_context"];
						$x++;
					}
				}
			//if no extension has been assigned then setting the user_context will still need to be set
				if (strlen($_SESSION['user_context']) == 0) {
					$_SESSION['user_context'] = $_SESSION['domain_name'];
				}
		}
	}
}
load_extensions();

function event_socket_create($host, $port, $password) {
	$fp = fsockopen($host, $port, $errno, $errdesc, 3);
	socket_set_blocking($fp,false);

	if (!$fp) {
		//error "invalid handle<br />\n";
		//echo "error number: ".$errno."<br />\n";
		//echo "error description: ".$errdesc."<br />\n";
	}
	else {
		//connected to the socket return the handle
		while (!feof($fp)) {
			$buffer = fgets($fp, 1024);
			usleep(100); //allow time for reponse
			if (trim($buffer) == "Content-Type: auth/request") {
				 fputs($fp, "auth $password\n\n");
				 break;
			}
		}
		return $fp;
	}
} //end function

function event_socket_request($fp, $cmd) {
	if ($fp) {
		$cmd_array = explode("\n",$cmd);
		foreach ($cmd_array as &$value) {
		    fputs($fp, $value."\n");
		}
		fputs($fp, "\n"); //second line feed to end the headers

		$response = '';
		$i = 0;
		$content_length = 0;
		while (!feof($fp)) {
			$buffer = fgets($fp, 4096);
			if ($content_length > 0) {
				$response .= $buffer;
			}

			if ($content_length == 0) { //if the content has length don't process again
				if (strlen(trim($buffer)) > 0) { //run only if buffer has content
					$array = explode(":", trim($buffer));
					if ($array[0] == "Content-Length") {
						$content_length = trim($array[1]);
					}
				}
			}

			if ($content_length > 0) { //is content_length set
				//stop reading if all content has been read.
				if (strlen($response) >= $content_length) {
					break;
				}
			}
			else {
				//prevent an endless loop
				if ($i > 300000) { break; }	
			}
			$i++;
		}

		return $response;
	}
	else {
		echo "no handle";
	}
}

function event_socket_request_cmd($cmd) {
	global $db, $domain_uuid, $host;

	if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/settings/app_config.php")) {
		$sql = "select * from v_settings ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
			$event_socket_ip_address = $row["event_socket_ip_address"];
			$event_socket_port = $row["event_socket_port"];
			$event_socket_password = $row["event_socket_password"];
			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

	$fp = event_socket_create($event_socket_ip_address, $event_socket_port, $event_socket_password);
	$response = event_socket_request($fp, $cmd);
	fclose($fp);
}

function byte_convert($bytes, $decimals = 2) {
	if ($bytes <= 0) { return '0 Bytes'; }
	$convention = 1024;
	$formattedbytes = array_reduce( array(' B', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', 'ZB'), create_function( '$a,$b', 'return is_numeric($a)?($a>='.$convention.'?$a/'.$convention.':number_format($a,'.$decimals.').$b):$a;' ), $bytes );
	return $formattedbytes;
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

function save_setting_xml() {
	global $db, $domain_uuid, $host, $config;

	$sql = "select * from v_settings ";
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
			$fout = fopen($_SESSION['switch']['conf']['dir']."/directory/default/default.xml","w");
			$xml = "<include>\n";
			$xml .= "  <user id=\"default\"> <!--if id is numeric mailbox param is not necessary-->\n";
			$xml .= "    <variables>\n";
			$xml .= "      <!--all variables here will be set on all inbound calls that originate from this user -->\n";
			$xml .= "      <!-- set these to take advantage of a dialplan localized to this user -->\n";
			$xml .= "      <variable name=\"numbering_plan\" value=\"" . $row['numbering_plan'] . "\"/>\n";
			$xml .= "      <variable name=\"default_gateway\" value=\"" . $row['default_gateway'] . "\"/>\n";
			$xml .= "      <variable name=\"default_area_code\" value=\"" . $row['default_area_code'] . "\"/>\n";
			$xml .= "    </variables>\n";
			$xml .= "  </user>\n";
			$xml .= "</include>\n";
			fwrite($fout, $xml);
			unset($xml);
			fclose($fout);

			$event_socket_ip_address = $row['event_socket_ip_address'];
			if (strlen($event_socket_ip_address) == 0) { $event_socket_ip_address = '127.0.0.1'; }

			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/event_socket.conf.xml","w");
			$xml = "<configuration name=\"event_socket.conf\" description=\"Socket Client\">\n";
			$xml .= "  <settings>\n";
			$xml .= "    <param name=\"listen-ip\" value=\"" . $event_socket_ip_address . "\"/>\n";
			$xml .= "    <param name=\"listen-port\" value=\"" . $row['event_socket_port'] . "\"/>\n";
			$xml .= "    <param name=\"password\" value=\"" . $row['event_socket_password'] . "\"/>\n";
			$xml .= "    <!--<param name=\"apply-inbound-acl\" value=\"lan\"/>-->\n";
			$xml .= "  </settings>\n";
			$xml .= "</configuration>";
			fwrite($fout, $xml);
			unset($xml, $event_socket_password);
			fclose($fout);

			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/xml_rpc.conf.xml","w");
			$xml = "<configuration name=\"xml_rpc.conf\" description=\"XML RPC\">\n";
			$xml .= "  <settings>\n";
			$xml .= "    <!-- The port where you want to run the http service (default 8080) -->\n";
			$xml .= "    <param name=\"http-port\" value=\"" . $row['xml_rpc_http_port'] . "\"/>\n";
			$xml .= "    <!-- if all 3 of the following params exist all http traffic will require auth -->\n";
			$xml .= "    <param name=\"auth-realm\" value=\"" . $row['xml_rpc_auth_realm'] . "\"/>\n";
			$xml .= "    <param name=\"auth-user\" value=\"" . $row['xml_rpc_auth_user'] . "\"/>\n";
			$xml .= "    <param name=\"auth-pass\" value=\"" . $row['xml_rpc_auth_pass'] . "\"/>\n";
			$xml .= "  </settings>\n";
			$xml .= "</configuration>\n";
			fwrite($fout, $xml);
			unset($xml);
			fclose($fout);

			//shout.conf.xml
				$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/shout.conf.xml","w");
				$xml = "<configuration name=\"shout.conf\" description=\"mod shout config\">\n";
				$xml .= "  <settings>\n";
				$xml .= "    <!-- Don't change these unless you are insane -->\n";
				$xml .= "    <param name=\"decoder\" value=\"" . $row['mod_shout_decoder'] . "\"/>\n";
				$xml .= "    <param name=\"volume\" value=\"" . $row['mod_shout_volume'] . "\"/>\n";
				$xml .= "    <!--<param name=\"outscale\" value=\"8192\"/>-->\n";
				$xml .= "  </settings>\n";
				$xml .= "</configuration>";
				fwrite($fout, $xml);
				unset($xml);
				fclose($fout);

			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

	//apply settings
		$_SESSION["reload_xml"] = true;

	//$cmd = "api reloadxml";
	//event_socket_request_cmd($cmd);
	//unset($cmd);
}

function filename_safe($filename) {
	// lower case
		$filename = strtolower($filename);

	// replace spaces with a '_'
		$filename = str_replace(" ", "_", $filename);

	// loop through string
		$result = '';
		for ($i=0; $i<strlen($filename); $i++) {
			if (preg_match('([0-9]|[a-z]|_)', $filename[$i])) {
				$result .= $filename[$i];
			}
		}

	// return filename
		return $result;
}

function save_gateway_xml() {

	//skip saving the gateway xml if the directory is not set
		if (strlen($_SESSION['switch']['sip_profiles']['dir']) == 0) {
			return;
		}

	//declare the global variables
		global $db, $domain_uuid, $config;

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
		$sql .= "where (domain_uuid = '$domain_uuid' or domain_uuid is null) ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
			if ($row['enabled'] != "false") {
					//set the default profile as external
						$profile = $row['profile'];
						if (strlen($profile) == 0) {
							$profile = "external";
						}
					//open the xml file
						$fout = fopen($_SESSION['switch']['sip_profiles']['dir']."/".$profile."/v_".strtolower($row['gateway_uuid']).".xml","w");
					//build the xml
						$xml .= "<include>\n";
						$xml .= "    <gateway name=\"" . strtolower($row['gateway_uuid']) . "\">\n";
						if (strlen($row['username']) > 0) {
							$xml .= "      <param name=\"username\" value=\"" . $row['username'] . "\"/>\n";
						}
						if (strlen($row['distinct_to']) > 0) {
							$xml .= "      <param name=\"distinct-to\" value=\"" . $row['distinct_to'] . "\"/>\n";
						}
						if (strlen($row['auth_username']) > 0) {
							$xml .= "      <param name=\"auth-username\" value=\"" . $row['auth_username'] . "\"/>\n";
						}
						if (strlen($row['password']) > 0) {
							$xml .= "      <param name=\"password\" value=\"" . $row['password'] . "\"/>\n";
						}
						if (strlen($row['realm']) > 0) {
							$xml .= "      <param name=\"realm\" value=\"" . $row['realm'] . "\"/>\n";
						}
						if (strlen($row['from_user']) > 0) {
							$xml .= "      <param name=\"from-user\" value=\"" . $row['from_user'] . "\"/>\n";
						}
						if (strlen($row['from_domain']) > 0) {
							$xml .= "      <param name=\"from-domain\" value=\"" . $row['from_domain'] . "\"/>\n";
						}
						if (strlen($row['proxy']) > 0) {
							$xml .= "      <param name=\"proxy\" value=\"" . $row['proxy'] . "\"/>\n";
						}
						if (strlen($row['register_proxy']) > 0) {
							$xml .= "      <param name=\"register-proxy\" value=\"" . $row['register_proxy'] . "\"/>\n";
						}
						if (strlen($row['outbound_proxy']) > 0) {
							$xml .= "      <param name=\"outbound-proxy\" value=\"" . $row['outbound_proxy'] . "\"/>\n";
						}
						if (strlen($row['expire_seconds']) > 0) {
							$xml .= "      <param name=\"expire-seconds\" value=\"" . $row['expire_seconds'] . "\"/>\n";
						}
						if (strlen($row['register']) > 0) {
							$xml .= "      <param name=\"register\" value=\"" . $row['register'] . "\"/>\n";
						}

						if (strlen($row['register_transport']) > 0) {
							switch ($row['register_transport']) {
							case "udp":
								$xml .= "      <param name=\"register-transport\" value=\"udp\"/>\n";
								break;
							case "tcp":
								$xml .= "      <param name=\"register-transport\" value=\"tcp\"/>\n";
								break;
							case "tls":
								$xml .= "      <param name=\"register-transport\" value=\"tls\"/>\n";
								$xml .= "      <param name=\"contact-params\" value=\"transport=tls\"/>\n";
								break;
							default:
								$xml .= "      <param name=\"register-transport\" value=\"" . $row['register_transport'] . "\"/>\n";
							}
						}

						if (strlen($row['retry_seconds']) > 0) {
							$xml .= "      <param name=\"retry-seconds\" value=\"" . $row['retry_seconds'] . "\"/>\n";
						}
						if (strlen($row['extension']) > 0) {
							$xml .= "      <param name=\"extension\" value=\"" . $row['extension'] . "\"/>\n";
						}
						if (strlen($row['ping']) > 0) {
							$xml .= "      <param name=\"ping\" value=\"" . $row['ping'] . "\"/>\n";
						}
						if (strlen($row['context']) > 0) {
							$xml .= "      <param name=\"context\" value=\"" . $row['context'] . "\"/>\n";
						}
						if (strlen($row['caller_id_in_from']) > 0) {
							$xml .= "      <param name=\"caller-id-in-from\" value=\"" . $row['caller_id_in_from'] . "\"/>\n";
						}
						if (strlen($row['supress_cng']) > 0) {
							$xml .= "      <param name=\"supress-cng\" value=\"" . $row['supress_cng'] . "\"/>\n";
						}
						if (strlen($row['sip_cid_type']) > 0) {
							$xml .= "      <param name=\"sip_cid_type\" value=\"" . $row['sip_cid_type'] . "\"/>\n";
						}
						if (strlen($row['extension_in_contact']) > 0) {
							$xml .= "      <param name=\"extension-in-contact\" value=\"" . $row['extension_in_contact'] . "\"/>\n";
						}

						$xml .= "    </gateway>\n";
						$xml .= "</include>";

					//write the xml
						fwrite($fout, $xml);
						unset($xml);
						fclose($fout);
			}

		} //end foreach
		unset($prep_statement);

	//apply settings
		$_SESSION["reload_xml"] = true;

}

function save_module_xml() {
	global $config, $db, $domain_uuid;

	$xml = "";
	$xml .= "<configuration name=\"modules.conf\" description=\"Modules\">\n";
	$xml .= "	<modules>\n";

	$sql = "select * from v_modules order by module_category = 'Languages' OR  module_category = 'Loggers' DESC, module_category ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$prev_module_cat = '';
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as $row) {
		if ($prev_module_cat != $row['module_category']) {
			$xml .= "\n		<!-- ".$row['module_category']." -->\n";
		}
		if ($row['module_enabled'] == "true"){
			$xml .= "		<load module=\"".$row['module_name']."\"/>\n";
		}
		$prev_module_cat = $row['module_category'];
	}
	$xml .= "\n";
	$xml .= "	</modules>\n";
	$xml .= "</configuration>";

	$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/modules.conf.xml","w");
	fwrite($fout, $xml);
	unset($xml);
	fclose($fout);

	//apply settings
		$_SESSION["reload_xml"] = true;

	//$cmd = "api reloadxml";
	//event_socket_request_cmd($cmd);
	//unset($cmd);
}

function save_var_xml() {
	global $config, $db, $domain_uuid;

	$fout = fopen($_SESSION['switch']['conf']['dir']."/vars.xml","w");
	$xml = '';

	$sql = "select * from v_vars ";
	$sql .= "where var_enabled = 'true' ";
	$sql .= "order by var_cat, var_order asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$prev_var_cat = '';
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		if ($row['var_cat'] != 'Provision') {
			if ($prev_var_cat != $row['var_cat']) {
				$xml .= "\n<!-- ".$row['var_cat']." -->\n";
				if (strlen($row["var_description"]) > 0) {
					$xml .= "<!-- ".base64_decode($row['var_description'])." -->\n";
				}
			}
			if (strlen($row['var_hostname']) == 0) {
				$xml .= "<X-PRE-PROCESS cmd=\"set\" data=\"".$row['var_name']."=".$row['var_value']."\"/>\n";
			} elseif ($row['var_hostname'] == system('hostname')) {
				$xml .= "<X-PRE-PROCESS cmd=\"set\" data=\"".$row['var_name']."=".$row['var_value']."\"/>\n";
			}
		}
		$prev_var_cat = $row['var_cat'];
	}
	$xml .= "\n";
	fwrite($fout, $xml);
	unset($xml);
	fclose($fout);

	//apply settings
		$_SESSION["reload_xml"] = true;

	//$cmd = "api reloadxml";
	//event_socket_request_cmd($cmd);
	//unset($cmd);
}

function outbound_route_to_bridge ($domain_uuid, $destination_number) {
	global $db;

	$destination_number = trim($destination_number);
	if (is_numeric($destination_number)) {
		//not found, continue to process the function
	}
	else {
		//not a number, brige_array and exit the function
		$bridge_array[0] = $destination_number;
		return $bridge_array;
	}

	$sql = "select * from v_dialplans ";
	$sql .= "where (domain_uuid = '".$domain_uuid."' or domain_uuid is null) ";
	$sql .= "and app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	$sql .= "and dialplan_enabled = 'true' ";
	$sql .= "order by dialplan_order asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	$x = 0;
	foreach ($result as &$row) {
		//set as variables
			$dialplan_uuid = $row['dialplan_uuid'];
			$dialplan_detail_tag = $row["dialplan_detail_tag"];
			$dialplan_detail_type = $row['dialplan_detail_type'];
			$dialplan_continue = $row['dialplan_continue'];

		//get the extension number using the dialplan_uuid
			$sql = "select * ";
			$sql .= "from v_dialplan_details ";
			$sql .= "where dialplan_uuid = '$dialplan_uuid' ";
			$sql .= "order by dialplan_detail_order asc ";
			$sub_result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
			$regex_match = false;
			foreach ($sub_result as &$sub_row) {
				if ($sub_row['dialplan_detail_tag'] == "condition") {
					if ($sub_row['dialplan_detail_type'] == "destination_number") {
							$dialplan_detail_data = $sub_row['dialplan_detail_data'];
							$pattern = '/'.$dialplan_detail_data.'/';
							preg_match($pattern, $destination_number, $matches, PREG_OFFSET_CAPTURE);
							if (count($matches) == 0) {
								$regex_match = false;
							}
							else {
								$regex_match = true;
								$regex_match_1 = $matches[1][0];
								$regex_match_2 = $matches[2][0];
								$regex_match_3 = $matches[3][0];
								$regex_match_4 = $matches[4][0];
								$regex_match_5 = $matches[5][0];
							}
					}
				}
			}
			if ($regex_match) {
				foreach ($sub_result as &$sub_row) {
					$dialplan_detail_data = $sub_row['dialplan_detail_data'];
					if ($sub_row['dialplan_detail_tag'] == "action" && $sub_row['dialplan_detail_type'] == "bridge" && $dialplan_detail_data != "\${enum_auto_route}") {
					$dialplan_detail_data = str_replace("\$1", $regex_match_1, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$2", $regex_match_2, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$3", $regex_match_3, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$4", $regex_match_4, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$5", $regex_match_5, $dialplan_detail_data);
						//echo "dialplan_detail_data: $dialplan_detail_data";
						$bridge_array[$x] = $dialplan_detail_data;
						$x++;
						if ($dialplan_continue == "false") {
							break 2;
						}
					}
				}
			}
	}
	return $bridge_array;
	unset ($prep_statement);
}
//$destination_number = '1231234';
//$bridge_array = outbound_route_to_bridge ($domain_uuid, $destination_number);
//foreach ($bridge_array as &$bridge) {
//	echo "bridge: ".$bridge."<br />";
//}

function extension_exists($extension) {
	global $db, $domain_uuid;
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and (extension = '$extension' ";
	$sql .= "or number_alias = '$extension') ";
	$sql .= "and enabled = 'true' ";
	$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	if (count($result) > 0) {
		return true;
	}
	else {
		return false;
	}
}

function get_recording_filename($id) {
	global $domain_uuid, $db;
	$sql = "select * from v_recordings ";
	$sql .= "where recording_uuid = '$id' ";
	$sql .= "and domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		//$filename = $row["filename"];
		//$recording_name = $row["recording_name"];
		//$recording_uuid = $row["recording_uuid"];
		return $row["filename"];
		break; //limit to 1 row
	}
	unset ($prep_statement);
}

function dialplan_add($domain_uuid, $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid) {
	global $db, $db_type;
	$sql = "insert into v_dialplans ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "dialplan_uuid, ";
	if (strlen($app_uuid) > 0) {
		$sql .= "app_uuid, ";
	}
	$sql .= "dialplan_name, ";
	$sql .= "dialplan_order, ";
	$sql .= "dialplan_context, ";
	$sql .= "dialplan_enabled, ";
	$sql .= "dialplan_description ";
	$sql .= ")";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'$dialplan_uuid', ";
	if (strlen($app_uuid) > 0) {
		$sql .= "'$app_uuid', ";
	}
	$sql .= "'$dialplan_name', ";
	$sql .= "'$dialplan_order', ";
	$sql .= "'$dialplan_context', ";
	$sql .= "'$dialplan_enabled', ";
	$sql .= "'$dialplan_description' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);
}

function dialplan_detail_add($domain_uuid, $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data, $dialplan_detail_break, $dialplan_detail_inline) {
	global $db;
	$dialplan_detail_uuid = uuid();
	$sql = "insert into v_dialplan_details ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "dialplan_uuid, ";
	$sql .= "dialplan_detail_uuid, ";
	$sql .= "dialplan_detail_tag, ";
	$sql .= "dialplan_detail_group, ";
	$sql .= "dialplan_detail_order, ";
	$sql .= "dialplan_detail_type, ";
	$sql .= "dialplan_detail_data, ";
	$sql .= "dialplan_detail_break, ";
	$sql .= "dialplan_detail_inline ";
	$sql .= ") ";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'".check_str($dialplan_uuid)."', ";
	$sql .= "'".check_str($dialplan_detail_uuid)."', ";
	$sql .= "'".check_str($dialplan_detail_tag)."', ";
	if (strlen($dialplan_detail_group) == 0) {
		$sql .= "null, ";
	}
	else {
		$sql .= "'".check_str($dialplan_detail_group)."', ";
	}
	$sql .= "'".check_str($dialplan_detail_order)."', ";
	$sql .= "'".check_str($dialplan_detail_type)."', ";
	$sql .= "'".check_str($dialplan_detail_data)."', ";
	if (strlen($dialplan_detail_break) == 0) {
		$sql .= "null, ";
	}
	else {
		$sql .= "'".check_str($dialplan_detail_break)."', ";
	}
	if (strlen($dialplan_detail_inline) == 0) {
		$sql .= "null ";
	}
	else {
		$sql .= "'".check_str($dialplan_detail_inline)."' ";
	}
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);
}

function save_dialplan_xml() {
	global $db, $domain_uuid;

	//get the context based from the domain_uuid
		$user_context = $_SESSION['domains'][$domain_uuid]['domain_name'];

	//prepare for dialplan .xml files to be written. delete all dialplan files that are prefixed with dialplan_ and have a file extension of .xml
		$dialplan_list = glob($_SESSION['switch']['dialplan']['dir'] . "/*/*v_dialplan*.xml");
		foreach($dialplan_list as $name => $value) {
			unlink($value);
		}
		$dialplan_list = glob($_SESSION['switch']['dialplan']['dir'] . "/*/*_v_*.xml");
		foreach($dialplan_list as $name => $value) {
			unlink($value);
		}
		$dialplan_list = glob($_SESSION['switch']['dialplan']['dir'] . "/*/*/*_v_*.xml");
		foreach($dialplan_list as $name => $value) {
			unlink($value);
		}

	//if dialplan dir exists then build and save the dialplan xml
		if (is_dir($_SESSION['switch']['dialplan']['dir'])) {
			$sql = "select * from v_dialplans ";
			$sql .= "where dialplan_enabled = 'true' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				foreach ($result as &$row) {
					$tmp = "";
					$tmp .= "\n";
					$first_action = true;

					$dialplan_continue = '';
					if ($row['dialplan_continue'] == "true") {
						$dialplan_continue = "continue=\"true\"";
					}

					$tmp = "<extension name=\"".$row['dialplan_name']."\" $dialplan_continue>\n";

					$sql = " select * from v_dialplan_details ";
					$sql .= " where dialplan_uuid = '".$row['dialplan_uuid']."' ";
					$sql .= " order by dialplan_detail_group asc, dialplan_detail_order asc ";
					$prep_statement_2 = $db->prepare($sql);
					if ($prep_statement_2) {
						$prep_statement_2->execute();
						$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
						$result_count2 = count($result2);
						unset ($prep_statement_2, $sql);

						//create a new array that is sorted into groups and put the tags in order conditions, actions, anti-actions
							$details = '';
							$previous_tag = '';
							$details[$group]['condition_count'] = '';
							//conditions
								$x = 0;
								$y = 0;
								foreach($result2 as $row2) {
									if ($row2['dialplan_detail_tag'] == "condition") {
										//get the group
											$group = $row2['dialplan_detail_group'];
										//get the generic type
											switch ($row2['dialplan_detail_type']) {
												case "hour":
												case "minute":
												case "minute-of-day":
												case "time-of-day":
												case "mday":
												case "mweek":
												case "mon":
												case "yday":
												case "year":
												case "wday":
												case "week":
													$type = 'time';
													break;
												default:
													$type = 'default';
											}

										//add the conditions to the details array
											$details[$group]['condition-'.$x]['dialplan_detail_tag'] = $row2['dialplan_detail_tag'];
											$details[$group]['condition-'.$x]['dialplan_detail_type'] = $row2['dialplan_detail_type'];
											$details[$group]['condition-'.$x]['dialplan_uuid'] = $row2['dialplan_uuid'];
											$details[$group]['condition-'.$x]['dialplan_detail_order'] = $row2['dialplan_detail_order'];
											$details[$group]['condition-'.$x]['field'][$y]['type'] = $row2['dialplan_detail_type'];
											$details[$group]['condition-'.$x]['field'][$y]['data'] = $row2['dialplan_detail_data'];
											$details[$group]['condition-'.$x]['dialplan_detail_break'] = $row2['dialplan_detail_break'];
											$details[$group]['condition-'.$x]['dialplan_detail_group'] = $row2['dialplan_detail_group'];
											$details[$group]['condition-'.$x]['dialplan_detail_inline'] = $row2['dialplan_detail_inline'];
											if ($type == "time") {
												$y++;
											}
									}
									if ($type == "default") {
										$x++;
										$y = 0;
									}
								}

							//actions
								$x = 0;
								foreach($result2 as $row2) {
									if ($row2['dialplan_detail_tag'] == "action") {
										$group = $row2['dialplan_detail_group'];
										foreach ($row2 as $key => $val) {
											$details[$group]['action-'.$x][$key] = $val;
										}
									}
									$x++;
								}
							//anti-actions
								$x = 0;
								foreach($result2 as $row2) {
									if ($row2['dialplan_detail_tag'] == "anti-action") {
										$group = $row2['dialplan_detail_group'];
										foreach ($row2 as $key => $val) {
											$details[$group]['anti-action-'.$x][$key] = $val;
										}
									}
									$x++;
								}
							unset($result2);
					}

					$i=1;
					if ($result_count2 > 0) {
						foreach($details as $group) {
							$current_count = 0;
							$x = 0;
							foreach($group as $ent) {
								$close_condition_tag = true;
								if (empty($ent)) {
									$close_condition_tag = false;
								}
								$current_tag = $ent['dialplan_detail_tag'];
								$c = 0;
								if ($ent['dialplan_detail_tag'] == "condition") {
									//get the generic type
										switch ($ent['dialplan_detail_type']) {
											case "hour":
											case "minute":
											case "minute-of-day":
											case "time-of-day":
											case "mday":
											case "mweek":
											case "mon":
											case "yday":
											case "year":
											case "wday":
											case "week":
												$type = 'time';
												break;
											default:
												$type = 'default';
										}

									//set the attribute and expression
										$condition_attribute = '';
										foreach($ent['field'] as $field) {
											if ($type == "time") {
												if (strlen($field['type']) > 0) {
													$condition_attribute .= $field['type'].'="'.$field['data'].'" ';
												}
												$condition_expression = '';
											}
											if ($type == "default") {
												$condition_attribute = '';
												if (strlen($field['type']) > 0) {
													$condition_attribute = 'field="'.$field['type'].'" ';
												}
												$condition_expression = '';
												if (strlen($field['data']) > 0) {
													$condition_expression = 'expression="'.$field['data'].'" ';
												}
											}
										}

									//get the condition break attribute
										$condition_break = '';
										if (strlen($ent['dialplan_detail_break']) > 0) {
											$condition_break = "break=\"".$ent['dialplan_detail_break']."\" ";
										}

									//get the count
										$count = 0;
										foreach($details as $group2) {
											foreach($group2 as $ent2) {
												if ($ent2['dialplan_detail_group'] == $ent['dialplan_detail_group'] && $ent2['dialplan_detail_tag'] == "condition") {
													$count++;
												}
											}
										}

									//use the correct type of dialplan_detail_tag open or self closed
										if ($count == 1) { //single condition
											//start dialplan_detail_tag
											$tmp .= "   <condition ".$condition_attribute."".$condition_expression."".$condition_break.">\n";
										}
										else { //more than one condition
											$current_count++;
											if ($current_count < $count) {
												//all tags should be self-closing except the last one
												$tmp .= "   <condition ".$condition_attribute."".$condition_expression."".$condition_break."/>\n";
											}
											else {
												//for the last dialplan_detail_tag use the start dialplan_detail_tag
												$tmp .= "   <condition ".$condition_attribute."".$condition_expression."".$condition_break.">\n";
											}
										}
								}
								//actions
									if ($ent['dialplan_detail_tag'] == "action") {
										//set the domain info for the public context
										if ($row['dialplan_context'] == "public") {
											if ($first_action) {
												$tmp .= "       <action application=\"set\" data=\"call_direction=inbound\"/>\n";
												$tmp .= "       <action application=\"set\" data=\"domain_uuid=".$row['domain_uuid']."\"/>\n";
												$tmp .= "       <action application=\"set\" data=\"domain_name=".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."\"/>\n";
												$tmp .= "       <action application=\"set\" data=\"domain=".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."\"/>\n";
												$first_action = false;
											}
										}
										//get the action inline attribute
										$action_inline = '';
										if (strlen($ent['dialplan_detail_inline']) > 0) {
											$action_inline = "inline=\"".$ent['dialplan_detail_inline']."\"";
										}
										if (strlen($ent['dialplan_detail_data']) > 0) {
											$tmp .= "       <action application=\"".$ent['dialplan_detail_type']."\" data=\"".$ent['dialplan_detail_data']."\" $action_inline/>\n";
										}
										else {
											$tmp .= "       <action application=\"".$ent['dialplan_detail_type']."\" $action_inline/>\n";
										}
									}
								//anti-actions
									if ($ent['dialplan_detail_tag'] == "anti-action") {
										//get the action inline attribute
										$anti_action_inline = '';
										if (strlen($ent['dialplan_detail_inline']) > 0) {
											$anti_action_inline = "inline=\"".$ent['dialplan_detail_inline']."\"";
										}
										if (strlen($ent['dialplan_detail_data']) > 0) {
											$tmp .= "       <anti-action application=\"".$ent['dialplan_detail_type']."\" data=\"".$ent['dialplan_detail_data']."\" $anti_action_inline/>\n";
										}
										else {
											$tmp .= "       <anti-action application=\"".$ent['dialplan_detail_type']."\" $anti_action_inline/>\n";
										}
									}
								//set the previous dialplan_detail_tag
									$previous_tag = $ent['dialplan_detail_tag'];
								$i++;
							} //end foreach
							if ($close_condition_tag == true) {
								$tmp .= "   </condition>\n";
							}
							$x++;
						}
						if ($condition_count > 0) {
							$condition_count = $result_count2;
						}
						unset($sql, $result_count2, $result2, $row_count2);
					} //end if results
					$tmp .= "</extension>\n";

					$dialplan_order = $row['dialplan_order'];
					if (strlen($dialplan_order) == 0) { $dialplan_order = "000".$dialplan_order; }
					if (strlen($dialplan_order) == 1) { $dialplan_order = "00".$dialplan_order; }
					if (strlen($dialplan_order) == 2) { $dialplan_order = "0".$dialplan_order; }
					if (strlen($dialplan_order) == 4) { $dialplan_order = "999"; }
					if (strlen($dialplan_order) == 5) { $dialplan_order = "999"; }

					//remove invalid characters from the file names
					$dialplan_name = $row['dialplan_name'];
					$dialplan_name = str_replace(" ", "_", $dialplan_name);
					$dialplan_name = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $dialplan_name);

					$dialplan_filename = $dialplan_order."_v_".$dialplan_name.".xml";
					if (strlen($row['dialplan_context']) > 0) {
						if (!is_dir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'])) {
							mkdir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'],0755,true);
						}
						if ($row['dialplan_context'] == "public") {
							if (count($_SESSION['domains']) > 1 && strlen($row['domain_uuid']) > 0) {
								if (!is_dir($_SESSION['switch']['dialplan']['dir']."/public/".$_SESSION['domains'][$row['domain_uuid']]['domain_name'])) {
									mkdir($_SESSION['switch']['dialplan']['dir']."/public/".$_SESSION['domains'][$row['domain_uuid']]['domain_name'],0755,true);
								}
								file_put_contents($_SESSION['switch']['dialplan']['dir']."/public/".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."/".$dialplan_filename, $tmp);
							}
							else {
								file_put_contents($_SESSION['switch']['dialplan']['dir']."/public/".$dialplan_filename, $tmp);
							}
						}
						else {
							if (!is_dir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'])) {
								mkdir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'],0755,true);
							}
							file_put_contents($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context']."/".$dialplan_filename, $tmp);
						}
					}
					unset($dialplan_filename);
					unset($tmp);
				} //end while

				//apply settings
					$_SESSION["reload_xml"] = true;
			}
		} //end if (is_dir($_SESSION['switch']['dialplan']['dir']))
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
		global $db, $domain_uuid;

		if (strlen($_SESSION['switch']['call_center']['dir']) > 0) {

			//get the call center queue array
			$sql = "select * from v_call_center_queues ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$call_center_queues = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			$result_count = count($call_center_queues);
			unset ($prep_statement, $sql);
			if ($result_count > 0) {

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
						$v_queues .= "<queue name=\"$queue_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\">\n";
						$v_queues .= "			<param name=\"strategy\" value=\"$queue_strategy\"/>\n";
						if (strlen($queue_moh_sound) == 0) {
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
						if (strlen($queue_record_template) > 0) {
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
					unset ($prep_statement);

				//prepare Agent XML string
					$v_agents = '';
					$sql = "select * from v_call_center_agents ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					$x=0;
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
					unset ($prep_statement);

				//prepare Tier XML string
					$v_tiers = '';
					$sql = "select * from v_call_center_tiers ";
					$prep_statement = $db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
					$x=0;
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

				//set the path
					if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
						$path = "/usr/share/examples/fusionpbx/resources/templates/conf";
					}
					else {
						$path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf";
					}

				//get the contents of the template
					$file_contents = file_get_contents($path."/autoload_configs/callcenter.conf.xml.noload");

				//add the Call Center Queues, Agents and Tiers to the XML config
					$file_contents = str_replace("{v_queues}", $v_queues, $file_contents);
					unset ($v_queues);

					$file_contents = str_replace("{v_agents}", $v_agents, $file_contents);
					unset ($v_agents);

					$file_contents = str_replace("{v_tiers}", $v_tiers, $file_contents);
					unset ($v_tiers);

				//write the XML config file
					$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/callcenter.conf.xml","w");
					fwrite($fout, $file_contents);
					fclose($fout);

				//save the dialplan xml files
					save_dialplan_xml();

				//apply settings
					$_SESSION["reload_xml"] = true;
			}
		}
	}
}

if (!function_exists('switch_conf_xml')) {
	function switch_conf_xml() {
		//get the global variables
			global $db, $domain_uuid;

		//get the contents of the template
			if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
				$path = "/usr/share/examples/fusionpbx/resources/templates/conf";
			}
			else {
				$path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf";
			}
			$file_contents = file_get_contents($path."/autoload_configs/switch.conf.xml");

		//prepare the php variables
			if (stristr(PHP_OS, 'WIN')) {
				$bindir = getenv(PHPRC);
				$v_mailer_app ='""'. $bindir."/php". '" -f  '.$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/secure/v_mailto.php -- "';
				$v_mailer_app = sprintf("'%s'", $v_mailer_app);
				$v_mailer_app_args = "";
			}
			else {
				if (file_exists(PHP_BINDIR.'/php')) { define("PHP_BIN", "php"); }
				$v_mailer_app = PHP_BINDIR."/".PHP_BIN." ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/v_mailto.php";
				$v_mailer_app = sprintf('"%s"', $v_mailer_app);
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

		//get the global variables
			global $db, $domain_uuid;

		//get the contents of the template
		 	if (file_exists('/usr/share/examples/fusionpbx/resources/templates/conf')) {
				$path = "/usr/share/examples/fusionpbx/resources/templates/conf";
			}
			else {
				$path = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/conf";
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
			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/xml_cdr.conf.xml","w");
			fwrite($fout, $file_contents);
			fclose($fout);

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('save_sip_profile_xml')) {
	function save_sip_profile_xml() {

		//skip saving the sip profile xml if the directory is not set
			if (strlen($_SESSION['switch']['sip_profiles']['dir']) == 0) {
				return;
			}

		// make profile dir if needed
			$profile_dir = $_SESSION['switch']['conf']['dir']."/sip_profiles";
			if (!is_readable($profile_dir)) { mkdir($profile_dir,0775,true); }

		//get the global variables
			global $db, $domain_uuid;

		//get the sip profiles from the database
			$sql = "select * from v_sip_profiles";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll();
			$result_count = count($result);
			unset ($prep_statement, $sql);
			if ($result_count > 0) {
				foreach($result as $row) {
					$sip_profile_uuid    = $row['sip_profile_uuid'];
					$sip_profile_name    = $row['sip_profile_name'];
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
						$sql .= "where sip_profile_uuid = '$sip_profile_uuid' ";
						$sql .= "and sip_profile_setting_enabled = 'true' ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
						$result = $prep_statement->fetchAll();
						$sip_profile_settings = '';
						foreach ($result as &$row) {
							$sip_profile_settings .= "		<param name=\"".$row["sip_profile_setting_name"]."\" value=\"".$row["sip_profile_setting_value"]."\"/>\n";
						}
						unset ($prep_statement);

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
						if (!is_readable($profile_dir.'/'.$sip_profile_name)) { mkdir($profile_dir.'/'.$sip_profile_name,0775,true); }

				} //end foreach
				unset($sql, $result, $row_count);
			} //end if results

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('save_switch_xml')) {
	function save_switch_xml() {
		if (is_readable($_SESSION['switch']['dialplan']['dir'])) {
			save_dialplan_xml();
		}
		if (is_readable($_SESSION['switch']['extensions']['dir'])) {
			if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/extensions/resources/classes/extension.php")) {
				require_once $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."app/extensions/resources/classes/extension.php";
				$extension = new extension;
				$extension->xml();
			}
		}
		if (is_readable($_SESSION['switch']['conf']['dir'])) {
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/settings/app_config.php")) {
				save_setting_xml();
			}
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/modules/app_config.php")) {
				save_module_xml();
			}
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/vars/app_config.php")) {
				save_var_xml();
			}
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/call_center/app_config.php")) {
				save_call_center_xml();
			}
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/gateways/app_config.php")) {
				save_gateway_xml();
			}
			//if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/ivr_menu/app_config.php")) {
			//	save_ivr_menu_xml();
			//}
			if (file_exists($_SERVER['DOCUMENT_ROOT'].PROJECT_PATH."/app/sip_profiles/app_config.php")) {
				save_sip_profile_xml();
			}
		}
	}
}

?>