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
	Copyright (C) 2008-2013 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/

include "root.php";
require_once "resources/require.php";

//set default variables
	$dir_count = 0;
	$file_count = 0;
	$row_count = 0;
	$tmp_array = '';
	$device_template = '';

//get the domain_name
	$domain_array = explode(":", $_SERVER["HTTP_HOST"]);
	$domain_name = $domain_array[0];

//get the domain_uuid
	$sql = "SELECT * FROM v_domains ";
	$sql .= "WHERE domain_name = '".$domain_name."' ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach($result as $row) {
		$domain_uuid = $row["domain_uuid"];
	}
	unset($result, $prep_statement);

//build the provision array
	foreach($_SESSION['provision'] as $key=>$val) {
		if (strlen($val['var']) > 0) { $value = $val['var']; }
		if (strlen($val['text']) > 0) { $value = $val['text']; }
		$provision[$key] = $value;
	}

//check if provisioning has been enabled
	if ($provision["enabled"] != "true") {
		echo "access denied";
		exit;
	}

//if password was defined in the system -> variables page then require the password.
	if (strlen($provision['password']) > 0) {
		//deny access if the password doesn't match
			if ($provision['password'] != check_str($_REQUEST['password'])) {
				//log the failed auth attempt to the system, to be available for fail2ban.
				openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
				syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt bad password for ".check_str($_REQUEST['mac']));
				closelog();

				usleep(rand(1000000,3500000));//1-3.5 seconds.
				echo "access denied";
				return;
			}
	}

//send a request to a remote server to validate the MAC address and secret
	if (strlen($_SERVER['auth_server']) > 0) {
		$result = send_http_request($_SERVER['auth_server'], 'mac='.check_str($_REQUEST['mac']).'&secret='.check_str($_REQUEST['secret']));
		if ($result == "false") {
			echo "access denied";
			exit;
		}
	}

//define PHP variables from the HTTP values
	$mac = check_str($_REQUEST['mac']);
	$file = check_str($_REQUEST['file']);
	if (strlen(check_str($_REQUEST['template'])) > 0) {
		$device_template = check_str($_REQUEST['template']);
	}

//check alternate MAC source
	if (empty($mac)){
		//set the http user agent
			//$_SERVER['HTTP_USER_AGENT'] = "Yealink SIP-T38G  38.70.0.125 00:15:65:00:00:00";
		//Yealink: 17 digit mac appended to the user agent, so check for a space exactly 17 digits before the end.
			if (strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,7)) == "yealink") {
				$mac = substr($_SERVER['HTTP_USER_AGENT'],-17);
				$mac = preg_replace("#[^a-fA-F0-9./]#", "", $mac);
			}
	}

//prepare the mac address
	//normalize the mac address to lower case
		$mac = strtolower($mac);
	//replace all non hexadecimal values and validate the mac address
		$mac = preg_replace("#[^a-fA-F0-9./]#", "", $mac);
		if (strlen($mac) != 12) {
			echo "invalid mac address";
			exit;
		}

//use the mac address to get the vendor
	require_once "app/devices/resources/classes/device.php";
	$device_vendor = device::get_vendor($mac);

//check to see if the mac_address exists in v_devices
	if (mac_exists_in_devices($db, $mac)) {
		//get the device_template
			if (strlen($device_template) == 0) {
				$sql = "SELECT * FROM v_devices ";
				$sql .= "WHERE device_mac_address=:mac ";
				$prep_statement_2 = $db->prepare(check_sql($sql));
				if ($prep_statement_2) {
					//use the prepared statement
						$prep_statement_2->bindParam(':mac', $mac);
						$prep_statement_2->execute();
						$row = $prep_statement_2->fetch();
					//set the variables from values in the database
						$device_uuid = $row["device_uuid"];
						$device_label = $row["device_label"];
						if (strlen($row["device_vendor"]) > 0) {
							$device_vendor = strtolower($row["device_vendor"]);
						}
						$device_model = $row["device_model"];
						$device_firmware_version = $row["device_firmware_version"];
						$device_provision_enable = $row["device_provision_enable"];
						$device_template = $row["device_template"];
						$device_username = $row["device_username"];
						$device_password = $row["device_password"];
						$device_time_zone = $row["device_time_zone"];
						$device_description = $row["device_description"];
				}
			}

		//find a template that was defined on another phone and use that as the default.
			if (strlen($device_template) == 0) {
				$sql = "SELECT * FROM v_devices ";
				$sql .= "WHERE device_template LIKE '%/%' ";
				$sql .= "AND domain_uuid=:domain_uuid ";
				$prep_statement_3 = $db->prepare(check_sql($sql));
				if ($prep_statement_3) {
					$prep_statement_3->bindParam(':domain_uuid', $domain_uuid);
					$prep_statement_3->bindParam(':mac', $mac);
					$prep_statement_3->execute();
					$row = $prep_statement_3->fetch();
					$device_label = $row["device_label"];
					$device_vendor = strtolower($row["device_vendor"]);
					$device_model = $row["device_model"];
					$device_firmware_version = $row["device_firmware_version"];
					$device_provision_enable = $row["device_provision_enable"];
					$device_template = $row["device_template"];
					$device_username = $row["device_username"];
					$device_password = $row["device_password"];
					$device_time_zone = $row["device_time_zone"];
					$device_description = $row["device_description"];
				}
			}
	}
	else {
		//use the user_agent to pre-assign a template for 1-hit provisioning. Enter the a unique string to match in the user agent, and the template it should match.
			$template_list=array(  
				"Linksys/SPA-2102"=>"linksys/spa2102",
				"Linksys/SPA-3102"=>"linksys/spa3102",
				"Linksys/SPA-9212"=>"linksys/spa921",
				"Cisco/SPA301"=>"cisco/spa301",
				"Cisco/SPA301D"=>"cisco/spa302d",
				"Cisco/SPA303"=>"cisco/spa303",
				"Cisco/SPA501G"=>"cisco/spa501g",
				"Cisco/SPA502G"=>"cisco/spa502g",
				"Cisco/SPA504G"=>"cisco/spa504g",
				"Cisco/SPA508G"=>"cisco/spa508g",
				"Cisco/SPA509G"=>"cisco/spa509g",
				"Cisco/SPA512G"=>"cisco/spa512g",
				"Cisco/SPA514G"=>"cisco/spa514g",
				"Cisco/SPA525G2"=>"cisco/spa525g2",
				"snom300-SIP"=>"snom/300",
				"snom320-SIP"=>"snom/320",
				"snom360-SIP"=>"snom/360",
				"snom370-SIP"=>"snom/370",
				"snom820-SIP"=>"snom/820",
				"snom-m3-SIP"=>"snom/m3",
				"yealink SIP-T20"=>"yealink/t20",
				"yealink SIP-T22"=>"yealink/t22",
				"yealink SIP-T26"=>"yealink/t26",
				"Yealink SIP-T32"=>"yealink/t32",
				"HW GXP1450"=>"grandstream/gxp1450",
				"HW GXP2124"=>"grandstream/gxp2124",
				"HW GXV3140"=>"grandstream/gxv3140",
				"HW GXV3175"=>"grandstream/gxv3175",
				"Wget/1.11.3"=>"konftel/kt300ip"
				);

			foreach ($template_list as $key=>$val){
				if(stripos($_SERVER['HTTP_USER_AGENT'],$key)!== false) {
					$device_template = $val;
					break;
				}
			}
			unset($template_list);

		//mac address does not exist in the table so add it
			$device_uuid = uuid();
			$sql = "INSERT INTO v_devices ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "device_uuid, ";
			$sql .= "device_mac_address, ";
			$sql .= "device_vendor, ";
			$sql .= "device_model, ";
			$sql .= "device_provision_enable, ";
			$sql .= "device_template, ";
			$sql .= "device_username, ";
			$sql .= "device_password, ";
			$sql .= "device_description ";
			$sql .= ") ";
			$sql .= "VALUES ";
			$sql .= "(";
			$sql .= "'".$domain_uuid."', ";
			$sql .= "'$device_uuid', ";
			$sql .= "'$mac', ";
			$sql .= "'$device_vendor', ";
			$sql .= "'', ";
			$sql .= "'true', ";
			$sql .= "'$device_template', ";
			$sql .= "'', ";
			$sql .= "'', ";
			$sql .= "'auto {$_SERVER['HTTP_USER_AGENT']}' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
	}

//get the device settings table in the provision category and update the provision array
	$sql = "SELECT * FROM v_device_settings ";
	$sql .= "WHERE device_uuid = '".$device_uuid."' ";
	$sql .= "AND device_setting_category = 'provision' ";
	$sql .= "AND device_setting_enabled = 'true' ";
	$sql .= "AND domain_uuid = '".$domain_uuid."' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	$result_count = count($result);
	foreach($result as $row) {
		$key = $row['device_setting_subcategory'];
		$value = $row['device_setting_value'];
		$provision[$key] = $value;
	}
	unset ($prep_statement);

//check to see if the IP address is in the CIDR range
	if (strlen($provision["cidr"]) > 0) {
		function check_cidr ($cidr,$ip_address) {
			list ($subnet, $mask) = explode ('/', $cidr);
			return ( ip2long ($ip_address) & ~((1 << (32 - $mask)) - 1) ) == ip2long ($subnet);
		}
		if (!check_cidr($provision["cidr"], $_SERVER['REMOTE_ADDR'])) {
			echo "access denied";
			exit;
		}
	}

//set the template directory
	$template_directory = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
	if (strlen($provision["template_directory"]) > 0) {
		$template_directory = $provision["template_directory"];
	}

//if the domain name directory exists then only use templates from it
	if (is_dir($template_directory.'/'.$domain_name)) {
		$device_template = $domain_name.'/'.$device_template;
	}

//if $file is not provided then look for a default file that exists
	if (strlen($file) == 0) {
		if (file_exists($template_directory."/".$device_template ."/{\$mac}")) {
			$file = "{\$mac}";
		}
		elseif (file_exists($template_directory."/".$device_template ."/{\$mac}.xml")) {
			$file = "{\$mac}.xml";
		}
		elseif (file_exists($template_directory."/".$device_template ."/{\$mac}.cfg")) {
			$file = "{\$mac}.cfg";
		}
		else {
			echo "file not found";
			exit;
		}
	}
	else {
		//make sure the file exists
		if (!file_exists($template_directory."/".$device_template ."/".$file)) {
			echo "file not found";
			exit;
		}
	}

//log file for testing
	//$tmp_file = "/tmp/provisioning_log.txt";
	//$fh = fopen($tmp_file, 'w') or die("can't open file");
	//$tmp_string = $mac."\n";
	//fwrite($fh, $tmp_string);
	//fclose($fh);

	//set variables for testing
		//$line1_displayname= "1001";
		//$line1_shortname= "1001";
		//$line1_user_id= "1001";
		//$line1_user_password= "1234.";
		//$line1_server_address= "10.2.0.2";
		//$line2_server_address= "";
		//$line2_displayname= "";
		//$line2_shortname= "";
		//$line2_user_uuid= "";
		//$line2_user_password= "";
		//$line2_server_address= "";
		//$server1_address= "10.2.0.2";
		//$server2_address= "";
		//$server3_address= "";
		//$proxy1_address= "10.2.0.2";
		//$proxy2_address= "";
		//$proxy3_address= "";

//initialize a template object
	include "resources/classes/template.php";
	$view = new template();
	if (strlen($_SESSION['provision']['template_engine']['text']) > 0) {
		$view->engine = $_SESSION['provision']['template_engine']['text']; //raintpl, smarty, twig
	}
	else {
		$view->engine = "smarty";
	}
	$view->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/".$device_template."/";
	$view->cache_dir = $_SESSION['server']['temp']['dir'];
	$view->init();

//replace the variables in the template in the future loop through all the line numbers to do a replace for each possible line number

	//get the time zone
		$time_zone_name = $_SESSION['domain']['time_zone']['name'];
		if (strlen($time_zone_name) > 0) {
			$time_zone_offset_raw = get_time_zone_offset($time_zone_name)/3600;
			$time_zone_offset_hours = floor($time_zone_offset_raw);
			$time_zone_offset_minutes = ($time_zone_offset_raw - $time_zone_offset_hours) * 60;
			$time_zone_offset_minutes = number_pad($time_zone_offset_minutes, 2);
			if ($time_zone_offset_raw > 0) {
				$time_zone_offset_hours = number_pad($time_zone_offset_hours, 2);
				$time_zone_offset_hours = "+".$time_zone_offset_hours;
			}
			else {
				$time_zone_offset_hours = str_replace("-", "", $time_zone_offset_hours);
				$time_zone_offset_hours = "-".number_pad($time_zone_offset_hours, 2);
			}
			$time_zone_offset = $time_zone_offset_hours.":".$time_zone_offset_minutes;
			$view->assign("time_zone_offset" , $time_zone_offset);
		}

	//create a mac address with back slashes for backwards compatability
		$mac_dash = substr($mac, 0,2).'-'.substr($mac, 2,2).'-'.substr($mac, 4,2).'-'.substr($mac, 6,2).'-'.substr($mac, 8,2).'-'.substr($mac, 10,2);

	//get the provisioning information from device lines table
		$sql = "SELECT * FROM v_device_lines ";
		$sql .= "WHERE device_uuid = '".$device_uuid."' ";
		$sql .= "AND domain_uuid = '".$domain_uuid."' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		foreach($result as $row) {
			//set the variables
				$line_number = $row['line_number'];
				$register_expires = $row['register_expires'];
				$sip_transport = strtolower($row['sip_transport']);
				$sip_port = $row['sip_port'];

			//set defaults
				if (strlen($register_expires) == 0) { $register_expires = "90"; }
				if (strlen($sip_transport) == 0) { $sip_transport = "tcp"; }
				if (strlen($sip_port) == 0) {
					if ($line_number == "" || $line_number == "1") {
						$sip_port = "5060";
					}
					else {
						$sip_port = "506".($line_number + 1);
					}
				}

			//assign the variables
				$view->assign("server_address_".$line_number, $row["server_address"]);
				$view->assign("outbound_proxy_".$line_number, $row["outbound_proxy"]);
				$view->assign("display_name_".$line_number, $row["display_name"]);
				$view->assign("auth_id_".$line_number, $row["auth_id"]);
				$view->assign("user_id_".$line_number, $row["user_id"]);
				$view->assign("user_password_".$line_number, $row["password"]);
				$view->assign("sip_transport_".$line_number, $sip_transport);
				$view->assign("sip_port_".$line_number, $sip_port);
				$view->assign("register_expires_".$line_number, $register_expires);
		}
		unset ($prep_statement);

	//get the provisioning information from device keys table
		$sql = "SELECT * FROM v_device_keys ";
		$sql .= "WHERE device_uuid = '".$device_uuid."' ";
		$sql .= "AND domain_uuid = '".$domain_uuid."' ";
		$sql .= "ORDER BY device_key_id asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	//assign the keys array
		$view->assign("keys", $result);
	//set the variables
		foreach($result as $row) {
			//set the variables
				$device_key_id = $row['device_key_id']; //1
				$device_key_type = $row['device_key_type']; //line
				$device_key_value = $row['device_key_value']; //1
				$device_key_label = $row['device_key_label']; //label
			//assign the variables
				if (strlen($device_key_type) == 0) {
					$view->assign("key_id_".$device_key_id, $device_key_id);
					$view->assign("key_type_".$device_key_id, $device_key_type);
					$view->assign("key_value_".$device_key_id, $device_key_value);
					$view->assign("key_label_".$device_key_id, $device_key_label);
				}
				else {
					$view->assign($device_key_type."_key_id_".$device_key_id, $device_key_id);
					$view->assign($device_key_type."_key_type_".$device_key_id, $device_key_type);
					$view->assign($device_key_type."_key_value_".$device_key_id, $device_key_value);
					$view->assign($device_key_type."_key_label_".$device_key_id, $device_key_label);
				}
		}
		unset ($prep_statement);

	//set the mac address in the correct format
		switch ($device_vendor) {
		case "aastra":
			$mac = strtoupper($mac);
			break;
		case "snom":
			$mac = strtoupper($mac);
			$mac = str_replace("-", "", $mac);
		default:
			$mac = strtolower($mac);
			$mac = substr($mac, 0,2).'-'.substr($mac, 2,2).'-'.substr($mac, 4,2).'-'.substr($mac, 6,2).'-'.substr($mac, 8,2).'-'.substr($mac, 10,2);
		}

	//replace the variables in the template in the future loop through all the line numbers to do a replace for each possible line number
		$view->assign("mac" , $mac);
		$view->assign("label", $device_label);
		$view->assign("firmware_version", $device_firmware_version);
		$view->assign("domain_time_zone", $device_time_zone);
		$view->assign("domain_name", $domain_name);
		$view->assign("project_path", PROJECT_PATH);
		$view->assign("server1_address", $server1_address);
		$view->assign("proxy1_address", $proxy1_address);
		$view->assign("password",$password);

	//replace the dynamic provision variables that are defined in default, domain, and device settings
		foreach($provision as $key=>$val) {
			$view->assign($key, $val);
		}

//output template to string for header processing
	$file_contents = $view->render($file);

//deliver the customized config over HTTP/HTTPS
	//need to make sure content-type is correct
	$cfg_ext = ".cfg";
	if ($device_vendor === "aastra" && strrpos($file, $cfg_ext, 0) === strlen($file) - strlen($cfg_ext)) {
		header("Content-Type: text/plain");
		header("Content-Length: ".strlen($file_contents));
	} else if ($device_vendor === "yealink") {
		header("Content-Type: text/plain");
		header("Content-Length: ".strval(strlen($file_contents)));
	} else if ($device_vendor === "snom" && $device_template === "snom/m3") {
		$file_contents = utf8_decode($file_contents);
		header("Content-Type: text/plain; charset=iso-8859-1");
		header("Content-Length: ".strlen($file_contents));
	} else {
		header("Content-Type: text/xml; charset=utf-8");
		header("Content-Length: ".strlen($file_contents));
	}
	echo $file_contents;

//define the function which checks to see if the mac address exists in devices
	function mac_exists_in_devices($db, $mac) {
		$sql = "SELECT count(*) as count FROM v_devices ";
		//$sql .= "WHERE domain_uuid=:domain_uuid ";
		$sql .= "WHERE device_mac_address=:mac ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			//$prep_statement->bindParam(':domain_uuid', $domain_uuid);
			$prep_statement->bindParam(':mac', $mac);
			$prep_statement->execute();
			$row = $prep_statement->fetch();
			$count = $row['count'];
			if ($row['count'] > 0) {
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}

?>