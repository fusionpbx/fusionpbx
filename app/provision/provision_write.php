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
require_once "resources/check_auth.php";
if (if_group("admin") || if_group("superadmin")) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//set default variables
	$dir_count = 0;
	$file_count = 0;
	$row_count = 0;
	$tmp_array = '';

//get the hardware phone list
	$sql = "select * from v_devices ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$device_uuid = $row["device_uuid"];
		$device_mac_address = $row["device_mac_address"];
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

		//use the mac address to find the vendor
			if (strlen($device_vendor) == 0) {
				switch (substr($device_mac_address, 0, 6)) {
				case "00085d":
					$device_vendor = "aastra";
					break;
				case "000e08":
					$device_vendor = "linksys";
					break;
				case "0004f2":
					$device_vendor = "polycom";
					break;
				case "00907a":
					$device_vendor = "polycom";
					break;
				case "0080f0":
					$device_vendor = "panasonic";
					break;
				case "001873":
					$device_vendor = "cisco";
					break;
				case "a44c11":
					$device_vendor = "cisco";
					break;
				case "0021A0":
					$device_vendor = "cisco";
					break;
				case "30e4db":
					$device_vendor = "cisco";
					break;
				case "002155":
					$device_vendor = "cisco";
					break;
				case "68efbd":
					$device_vendor = "cisco";
					break;
				case "00045a":
					$device_vendor = "linksys";
					break;
				case "000625":
					$device_vendor = "linksys";
					break;
				case "001565":
					$device_vendor = "yealink";
					break;
				case "000413":
					$device_vendor = "snom";
					break;
				case "000b82":
					$device_vendor = "grandstream";
					break;
				case "00177d":
					$device_vendor = "konftel";
					break;
				default:
					$device_vendor = "";
				}
			}

		//set the mac address in the correct format
			switch ($device_vendor) {
			case "aastra":
				//upper case no formatting
				$device_mac_address = strtoupper($device_mac_address);
				break;
			case "polycom":
				//lower case no formatting
				$device_mac_address = strtolower($device_mac_address);
				$device_mac_address = str_replace("-", "", $device_mac_address);
				break;
			case "snom":
				//upper case with dashes
				$device_mac_address = strtoupper($device_mac_address);
				$device_mac_address = str_replace("-", "", $device_mac_address);
			default:
				//lower case with dashes
				$device_mac_address = strtolower($device_mac_address);
				$device_mac_address = substr($device_mac_address, 0,2).'-'.substr($device_mac_address, 2,2).'-'.substr($device_mac_address, 4,2).'-'.substr($device_mac_address, 6,2).'-'.substr($device_mac_address, 8,2).'-'.substr($device_mac_address, 10,2);
			}

		//loop through the provision template directory
			$provision_template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/".$device_template;

			clearstatcache();
			$dir_list = '';
			$file_list = '';
			$dir_list = opendir($provision_template_dir);
			$dir_array = array();
			while (false !== ($file = readdir($dir_list))) { 
				if ($file != "." AND $file != ".."){
					$new_path = $dir.'/'.$file;
					$level = explode('/',$new_path);
					if (substr($new_path, -4) == ".svn") {
						//ignore .svn dir and subdir
					}
					elseif (substr($new_path, -3) == ".db") {
						//ignore .db files
					}
					else {
						$dir_array[] = $new_path;
					}
					if ($x > 1000) { break; };
					$x++;
				}
			}
			//asort($dir_array);
			foreach ($dir_array as $new_path){
					$level = explode('/',$new_path);
					if (is_dir($new_path)) { 
						//$mod_array[] = array(
							//'level'=>count($level)-1,
							//'path'=>$new_path,
							//'name'=>end($level),
							//'type'=>'dir',
							//'mod_time'=>filemtime($new_path),
							//'size'=>'');
							//$mod_array[] = recur_dir($new_path);
						$dir_name = end($level);
						//$file_list .=  "$dir_name\n";
						//$dir_list .= recur_dir($new_path);
					}
					else {
						//$mod_array[] = array(
							//'level'=>count($level)-1,
							//'path'=>$new_path,
							//'name'=>end($level),
							//'type'=>'dir',
							//'mod_time'=>filemtime($new_path),
							//'size'=>'');
							//$mod_array[] = recur_dir($new_path);
						$file_name = end($level);
						$file_size = round(filesize($new_path)/1024, 2);

						//get the contents of the template
							$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/resources/templates/provision/".$device_template ."/".$file_name);

						//prepare the files
							//replace the variables in the template in the future loop through all the line numbers to do a replace for each possible line number
								$file_contents = str_replace("{v_mac}", $device_mac_address, $file_contents);
								$file_contents = str_replace("{v_label}", $device_label, $file_contents);
								$file_contents = str_replace("{v_firmware_version}", $device_firmware_version, $file_contents);
								$file_contents = str_replace("{domain_time_zone}", $device_time_zone, $file_contents);
								$file_contents = str_replace("{domain_name}", $_SESSION['domain_name'], $file_contents);
								$file_contents = str_replace("{v_server1_address}", $server1_address, $file_contents);
								$file_contents = str_replace("{v_proxy1_address}", $proxy1_address, $file_contents);

						//replace the dynamic provision variables that are defined in 'default settings' and 'domain settings';
							//example: category=provision, subcategory=sip_transport, name=var, value=tls - used in the template as {v_sip_transport}
							foreach($_SESSION['provision'] as $key=>$value) {
								$file_contents = str_replace('{v_'.$key.'}', $value['var'], $file_contents);
							}

						//create a mac address with back slashes for backwards compatability
							$mac_dash = substr($device_mac_address, 0,2).'-'.substr($device_mac_address, 2,2).'-'.substr($device_mac_address, 4,2).'-'.substr($device_mac_address, 6,2).'-'.substr($device_mac_address, 8,2).'-'.substr($device_mac_address, 10,2);

						//lookup the provisioning information for this MAC address.
							$sql = "SELECT e.extension, e.password, e.effective_caller_id_name, d.device_extension_uuid, d.extension_uuid, d.device_line ";
							$sql .= "FROM v_device_extensions as d, v_extensions as e ";
							$sql .= "WHERE e.extension_uuid = d.extension_uuid ";
							$sql .= "AND d.device_uuid = '".$device_uuid."' ";
							$sql .= "AND d.domain_uuid = '".$_SESSION['domain_uuid']."' ";
							$sql .= "and e.enabled = 'true' ";
							$sub_prep_statement = $db->prepare(check_sql($sql));
							$sub_prep_statement->execute();
							$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach($sub_result as $field) {
								$line_number = $field['device_line'];
								$file_contents = str_replace("{v_line".$line_number."_server_address}", $_SESSION['domain_name'], $file_contents);
								$file_contents = str_replace("{v_line".$line_number."_displayname}", $field["effective_caller_id_name"], $file_contents);
								$file_contents = str_replace("{v_line".$line_number."_shortname}", $field["extension"], $file_contents);
								$file_contents = str_replace("{v_line".$line_number."_user_id}", $field["extension"], $file_contents);
								$file_contents = str_replace("{v_line".$line_number."_user_password}", $field["password"], $file_contents);
							}
							unset ($sub_prep_statement);

						//get the provisioning information from device lines table
							$sql = "SELECT * FROM v_device_lines ";
							$sql .= "WHERE device_uuid = '".$device_uuid."' ";
							$sql .= "AND domain_uuid = '".$_SESSION['domain_uuid']."' ";
							$sub_prep_statement = $db->prepare(check_sql($sql));
							$sub_prep_statement->execute();
							$sub_result = $sub_prep_statement->fetchAll(PDO::FETCH_NAMED);
							foreach($sub_result as $field) {
								$line_number = $field['line_number'];
								$file_contents = str_replace("{v_line".$line_number."_server_address}", $field["server_address"], $file_contents);
								$file_contents = str_replace("{v_line".$line_number."_outbound_proxy}", $field["outbound_proxy"], $file_contents);
								$file_contents = str_replace("{v_line".$line_number."_displayname}", $field["display_name"], $file_contents);
								$file_contents = str_replace("{v_line".$line_number."_user_id}", $field["user_id"], $file_contents);
								$file_contents = str_replace("{v_line".$line_number."_auth_id}", $field["auth_id"], $file_contents);
								$file_contents = str_replace("{v_line".$line_number."_user_password}", $field["password"], $file_contents);
							}
							unset ($sub_prep_statement);

						//cleanup any remaining variables
							for ($i = 1; $i <= 100; $i++) {
								$file_contents = str_replace("{v_line".$i."_server_address}", "", $file_contents);
								$file_contents = str_replace("{v_line".$i."_outbound_proxy}", "", $file_contents);
								$file_contents = str_replace("{v_line".$i."_displayname}", "", $file_contents);
								$file_contents = str_replace("{v_line".$i."_shortname}", "", $file_contents);
								$file_contents = str_replace("{v_line".$i."_user_id}", "", $file_contents);
								$file_contents = str_replace("{v_line".$i."_auth_id}", "", $file_contents);
								$file_contents = str_replace("{v_line".$i."_user_password}", "", $file_contents);
							}

						//replace {v_mac} in the file name
							if ($device_vendor == "aastra" || $device_vendor == "cisco") {
								//upper case the mac address for aastra phones
								$file_name = str_replace("{v_mac}", strtoupper($device_mac_address), $file_name);
							}
							else {
								//all other phones
								$file_name = str_replace("{v_mac}", $device_mac_address, $file_name);
							}

						//write the configuration to the directory
							if (strlen($_SESSION['switch']['provision']['dir']) > 0) {
								$dir_array = explode(";", $_SESSION['switch']['provision']['dir']);
								foreach($dir_array as $directory) {
									//echo $directory.'/'.$file_name."<br />\n";
									$fh = fopen($directory.'/'.$file_name,"w") or die("Unable to write to $directory for provisioning. Make sure the path exists and permissons are set correctly.");
									fwrite($fh, $file_contents);
									fclose($fh);
								}
								unset($file_name);
							}
					}
			} //end for each
			closedir($dir_list);
	}
	unset ($prep_statement);

?>
