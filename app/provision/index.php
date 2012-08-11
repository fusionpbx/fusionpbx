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
	Copyright (C) 2008-2012 All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";
require_once "includes/require.php";

//set default variables
	$dir_count = 0;
	$file_count = 0;
	$row_count = 0;
	$tmp_array = '';
	$phone_template = '';

//if password was defined in the system -> variables page then require the password.
	if (strlen($_SESSION['provision']['password']['var']) > 0) {
		//deny access if the password doesn't match
			if ($_SESSION['provision']['password']['var'] != $_REQUEST['password']) {
				//log the failed auth attempt to the system, to be available for fail2ban.
				openlog('FusionPBX', LOG_NDELAY, LOG_AUTH);
				syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] provision attempt bad password for ".$_REQUEST['mac']);
				closelog();

				usleep(rand(1000000,3500000));//1-3.5 seconds.
				echo "access denied";
				return;
			}
	}

//send a request to a remote server to validate the MAC address and secret
	if (strlen($_SERVER['auth_server']) > 0) {
		$result = send_http_request($_SERVER['auth_server'], 'mac='.$_REQUEST['mac'].'&secret='.$_REQUEST['secret']);
		if ($result == "false") {
			echo "access denied";
			exit;
		}
	}

//define PHP variables from the HTTP values
	$mac = $_REQUEST['mac'];
	$file = $_REQUEST['file'];
	if (strlen($_REQUEST['template']) > 0) {
		$phone_template = $_REQUEST['template'];
	}

//check alternate MAC source
	if (empty($mac)){
		if($_SERVER['HTTP_USER_AGENT'][strlen($_SERVER['HTTP_USER_AGENT'])-17-1] == " ") {
			$mac = substr($_SERVER['HTTP_USER_AGENT'],-17);
		} //Yealink: 17 digit mac appended to the user agent, so check for a space exactly 17 digits before the end.
	}//check alternates

//prepare the mac address
	$mac = strtolower($mac);
	$mac = preg_replace('#[^a-fA-F0-9./]#', '', $mac);

//use the mac address to find the vendor
	switch (substr($mac, 0, 6)) {
	case "00085d":
		$phone_vendor = "aastra";
		break;
	case "000e08":
		$phone_vendor = "linksys";
		break;
	case "0004f2":
		$phone_vendor = "polycom";
		break;
	case "00907a":
		$phone_vendor = "polycom";
		break;
	case "001873":
		$phone_vendor = "cisco";
		break;
	case "00045a":
		$phone_vendor = "linksys";
		break;
	case "000625":
		$phone_vendor = "linksys";
		break;
	case "001565":
		$phone_vendor = "yealink";
		break;
	case "000413":
		$phone_vendor = "snom";
	default:
		$phone_vendor = "";
	}

//check to see if the mac_address exists in v_hardware_phones
	if (mac_exists_in_v_hardware_phones($db, $mac)) {
		//get the phone_template
			if (strlen($phone_template) == 0) {
				$sql = "SELECT * FROM v_hardware_phones ";
				$sql .= "where domain_uuid=:domain_uuid ";
				$sql .= "and phone_mac_address=:mac ";
				$prep_statement_2 = $db->prepare(check_sql($sql));
				if ($prep_statement_2) {
					$prep_statement_2->bindParam(':domain_uuid', $domain_uuid);
					$prep_statement_2->bindParam(':mac', $mac);
					$prep_statement_2->execute();
					$row = $prep_statement_2->fetch();
					$phone_label = $row["phone_label"];
					if (strlen($row["phone_vendor"]) > 0) {
						$phone_vendor = $row["phone_vendor"];
					}
					$phone_model = $row["phone_model"];
					$phone_firmware_version = $row["phone_firmware_version"];
					$phone_provision_enable = $row["phone_provision_enable"];
					$phone_template = $row["phone_template"];
					$phone_username = $row["phone_username"];
					$phone_password = $row["phone_password"];
					$phone_time_zone = $row["phone_time_zone"];
					$phone_description = $row["phone_description"];
				}
			}
		//find a template that was defined on another phone and use that as the default.
			if (strlen($phone_template) == 0) {
				$sql = "SELECT * FROM v_hardware_phones ";
				$sql .= "where domain_uuid=:domain_uuid ";
				$sql .= "and phone_template like '%/%' ";
				$prep_statement3 = $db->prepare(check_sql($sql));
				if ($prep_statement3) {
					$prep_statement3->bindParam(':domain_uuid', $domain_uuid);
					$prep_statement3->bindParam(':mac', $mac);
					$prep_statement3->execute();
					$row = $prep_statement3->fetch();
					$phone_label = $row["phone_label"];
					$phone_vendor = $row["phone_vendor"];
					$phone_model = $row["phone_model"];
					$phone_firmware_version = $row["phone_firmware_version"];
					$phone_provision_enable = $row["phone_provision_enable"];
					$phone_template = $row["phone_template"];
					$phone_username = $row["phone_username"];
					$phone_password = $row["phone_password"];
					$phone_time_zone = $row["phone_time_zone"];
					$phone_description = $row["phone_description"];
				}
			}
	}
	else {
		//use the user_agent to pre-assign a template for 1-hit provisioning. Enter the a unique string to match in the user agent, and the template it should match.
			$template_list=array(  
					"Linksys/SPA-2102"=>"linksys/spa2102",
					"Linksys/SPA-3102"=>"linksys/spa3102"
					);

			foreach ($template_list as $key=>$val){
				if(stripos($_SERVER['HTTP_USER_AGENT'],$key)!== false) {
					$phone_template=$val;
					break;
				}
			}
			unset($template_list);

		//mac address does not exist in the table so add it
			$hardware_phone_uuid = uuid();
			$sql = "insert into v_hardware_phones ";
			$sql .= "(";
			$sql .= "domain_uuid, ";
			$sql .= "hardware_phone_uuid, ";
			$sql .= "phone_mac_address, ";
			$sql .= "phone_vendor, ";
			$sql .= "phone_model, ";
			$sql .= "phone_provision_enable, ";
			$sql .= "phone_template, ";
			$sql .= "phone_username, ";
			$sql .= "phone_password, ";
			$sql .= "phone_description ";
			$sql .= ")";
			$sql .= "values ";
			$sql .= "(";
			$sql .= "'$domain_uuid', ";
			$sql .= "'$hardware_phone_uuid', ";
			$sql .= "'$mac', ";
			$sql .= "'$phone_vendor', ";
			$sql .= "'', ";
			$sql .= "'true', ";
			$sql .= "'$phone_template', ";
			$sql .= "'', ";
			$sql .= "'', ";
			$sql .= "'auto {$_SERVER['HTTP_USER_AGENT']}' ";
			$sql .= ")";
			$db->exec(check_sql($sql));
			unset($sql);
	}

//if $file is not provided then look for a default file that exists
	if (strlen($file) == 0) { 
		if (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/provision/".$phone_template ."/{v_mac}")) {
			$file = "{v_mac}";
		}
		elseif (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/provision/".$phone_template ."/{v_mac}.xml")) {
			$file = "{v_mac}.xml";
		}
		elseif (file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/provision/".$phone_template ."/{v_mac}.cfg")) {
			$file = "{v_mac}.cfg";
		}
		else {
			echo "file not found";
			exit;
		}
	}
	else {
		//make sure the file exists
		if (!file_exists($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/provision/".$phone_template ."/".$file)) {
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

//get the contents of the template
	$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/provision/".$phone_template ."/".$file);

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
			$file_contents = str_replace("{v_time_zone_offset}", $time_zone_offset, $file_contents);
		}

	//create a mac address with back slashes for backwards compatability
		$mac_dash = substr($mac, 0,2).'-'.substr($mac, 2,2).'-'.substr($mac, 4,2).'-'.substr($mac, 6,2).'-'.substr($mac, 8,2).'-'.substr($mac, 10,2);

	//lookup the provisioning information for this MAC address.
		$sql = "select * from v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and (provisioning_list like '%|".$mac.":%' or provisioning_list like '%|".$mac_dash.":%') ";
		$sql .= "and enabled = 'true' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		foreach ($result as &$row) {
			$provisioning_list = $row["provisioning_list"];
			$provisioning_list_array = explode("|", $provisioning_list);
			foreach ($provisioning_list_array as &$prov_row) {
				$prov_row_array = explode(":", $prov_row);
				$prov_mac = strtolower($prov_row_array[0]);
				$prov_mac = preg_replace('#[^a-fA-F0-9./]#', '', $prov_mac);
				if ($prov_mac == $mac) {
					$line_number = $prov_row_array[1];
					$file_contents = str_replace("{v_line".$line_number."_server_address}", $_SESSION['domain_name'], $file_contents);
					$file_contents = str_replace("{v_line".$line_number."_displayname}", $row["effective_caller_id_name"], $file_contents);
					$file_contents = str_replace("{v_line".$line_number."_shortname}", $row["extension"], $file_contents);
					$file_contents = str_replace("{v_line".$line_number."_user_id}", $row["extension"], $file_contents);
					$file_contents = str_replace("{v_line".$line_number."_user_password}", $row["password"], $file_contents);
				}
			}
			//$vm_password = $row["vm_password"];
			//$vm_password = str_replace("#", "", $vm_password); //preserves leading zeros
			//$accountcode = $row["accountcode"];
			//$effective_caller_id_name = $row["effective_caller_id_name"];
			//$effective_caller_id_number = $row["effective_caller_id_number"];
			//$outbound_caller_id_name = $row["outbound_caller_id_name"];
			//$outbound_caller_id_number = $row["outbound_caller_id_number"];
			//$vm_mailto = $row["vm_mailto"];
			//$vm_attach_file = $row["vm_attach_file"];
			//$vm_keep_local_after_email = $row["vm_keep_local_after_email"];
			//$user_context = $row["user_context"];
			//$call_group = $row["call_group"];
			//$auth_acl = $row["auth_acl"];
			//$cidr = $row["cidr"];
			//$sip_force_contact = $row["sip_force_contact"];
			//$enabled = $row["enabled"];
			//$description = $row["description"];
		}
		unset ($prep_statement);

	//set the mac address in the correct format
		switch ($phone_vendor) {
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
		$file_contents = str_replace("{v_mac}", $mac, $file_contents);
		$file_contents = str_replace("{v_label}", $phone_label, $file_contents);
		$file_contents = str_replace("{v_firmware_version}", $phone_firmware_version, $file_contents);
		$file_contents = str_replace("{domain_time_zone}", $phone_time_zone, $file_contents);
		$file_contents = str_replace("{domain_name}", $_SESSION['domain_name'], $file_contents);
		$file_contents = str_replace("{v_project_path}", PROJECT_PATH, $file_contents);
		$file_contents = str_replace("{v_server1_address}", $server1_address, $file_contents);
		$file_contents = str_replace("{v_proxy1_address}", $proxy1_address, $file_contents);
		$file_contents = str_replace("{v_password}", $password, $file_contents);
		$file_contents = str_replace("{v_time_zone_offset}", $password, $file_contents);

	//cleanup any remaining variables
		for ($i = 1; $i <= 100; $i++) {
			$file_contents = str_replace("{v_line".$i."_server_address}", "", $file_contents);
			$file_contents = str_replace("{v_line".$i."_displayname}", "", $file_contents);
			$file_contents = str_replace("{v_line".$i."_shortname}", "", $file_contents);
			$file_contents = str_replace("{v_line".$i."_user_id}", "", $file_contents);
			$file_contents = str_replace("{v_line".$i."_user_password}", "", $file_contents);
		}

	//replace the dynamic provision variables that are defined in 'default settings' and 'domain settings'
		//example: category=provision, subcategory=sip_transport, name=var, value=tls - used in the template as {v_sip_transport}
		foreach($_SESSION['provision'] as $key=>$value) {
			$file_contents = str_replace('{v_'.$key.'}', $value['var'], $file_contents);
		}

//deliver the customized config over HTTP/HTTPS
	//need to make sure content-type is correct
	$cfg_ext = ".cfg";
	if ($phone_vendor === "aastra" && strrpos($file, $cfg_ext, 0) === strlen($file) - strlen($cfg_ext)) {
		header ("content-type: text/plain");
	} else {
		header ("content-type: text/xml");
	}
	echo $file_contents;

function mac_exists_in_v_hardware_phones($db, $mac) {
	global $domain_uuid;
	$sql = "SELECT count(*) as count FROM v_hardware_phones ";
	$sql .= "where domain_uuid=:domain_uuid ";
	$sql .= "and phone_mac_address=:mac ";
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->bindParam(':domain_uuid', $domain_uuid);
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