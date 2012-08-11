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
require_once "includes/checkauth.php";
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
	$sql = "select * from v_hardware_phones ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
	foreach ($result as &$row) {
		$phone_mac_address = $row["phone_mac_address"];
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

		//use the mac address to find the vendor
			if (strlen($phone_vendor) == 0) {
				switch (substr($phone_mac_address, 0, 6)) {
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
			}

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

		//loop through the provision template directory
			$provision_template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/provision/".$phone_template;

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
							$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/provision/".$phone_template ."/".$file_name);

						//prepare the files
							//replace the variables in the template in the future loop through all the line numbers to do a replace for each possible line number
								$file_contents = str_replace("{v_mac}", $phone_mac_address, $file_contents);
								$file_contents = str_replace("{v_label}", $phone_label, $file_contents);
								$file_contents = str_replace("{v_firmware_version}", $phone_firmware_version, $file_contents);
								$file_contents = str_replace("{domain_time_zone}", $phone_time_zone, $file_contents);
								$file_contents = str_replace("{domain_name}", $_SESSION['domain_name'], $file_contents);
								$file_contents = str_replace("{v_server1_address}", $server1_address, $file_contents);
								$file_contents = str_replace("{v_proxy1_address}", $proxy1_address, $file_contents);

						//replace the dynamic provision variables that are defined in 'default settings' and 'domain settings';
							//example: category=provision, subcategory=sip_transport, name=var, value=tls - used in the template as {v_sip_transport}
							foreach($_SESSION['provision'] as $key=>$value) {
								$file_contents = str_replace('{v_'.$key.'}', $value['var'], $file_contents);
							}

						//create a mac address with back slashes for backwards compatability
							$mac_dash = substr($phone_mac_address, 0,2).'-'.substr($phone_mac_address, 2,2).'-'.substr($phone_mac_address, 4,2).'-'.substr($phone_mac_address, 6,2).'-'.substr($phone_mac_address, 8,2).'-'.substr($phone_mac_address, 10,2);

						//lookup the provisioning information for this MAC address.
							$sql2 = "select * from v_extensions ";
							$sql2 .= "where domain_uuid = '$domain_uuid' ";
							$sql2 .= "and (provisioning_list like '%|".$phone_mac_address.":%' or provisioning_list like '%|".$mac_dash.":%') ";
							$sql2 .= "and enabled = 'true' ";
							$prep_statement_2 = $db->prepare(check_sql($sql2));
							$prep_statement_2->execute();
							$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
							foreach ($result2 as &$row2) {
								$provisioning_list = $row2["provisioning_list"];
								if (strlen($provisioning_list) > 1) {
									$provisioning_list_array = explode("|", $provisioning_list);
									foreach ($provisioning_list_array as $prov_row) {
										$prov_row_array = explode(":", $prov_row);
										if (strlen($prov_row_array[0]) > 0) {
											//echo "mac address: ".$prov_row_array[0]."<br />";
											//echo "line_number: ".$prov_row_array[1]."<br />";
											if ($prov_row_array[0] == $phone_mac_address) {
												$line_number = $prov_row_array[1];
												//echo "prov_row: ".$prov_row."<br />";
												//echo "line_number: ".$line_number."<br />";
												//echo "<hr><br />\n";
											}
											$file_contents = str_replace("{v_line".$line_number."_server_address}", $_SESSION['domain_name'], $file_contents);
											$file_contents = str_replace("{v_line".$line_number."_displayname}", $row2["effective_caller_id_name"], $file_contents);
											$file_contents = str_replace("{v_line".$line_number."_shortname}", $row2["extension"], $file_contents);
											$file_contents = str_replace("{v_line".$line_number."_user_id}", $row2["extension"], $file_contents);
											$file_contents = str_replace("{v_line".$line_number."_user_password}", $row2["password"], $file_contents);
										}
									}
									//$vm_password = $row["vm_password"];
									//$vm_password = str_replace("#", "", $vm_password); //preserves leading zeros
									//$accountcode = $row["accountcode"];
									//$effective_caller_id_name = $row["effective_caller_id_name"];
									//$effective_caller_id_number = $row["effective_caller_id_number"];
									//$outbound_caller_id_name = $row["outbound_caller_id_name"];
									//$outbound_caller_id_number = $row["outbound_caller_id_number"];
									//$vm_enabled = $row["vm_enabled"];
									//$vm_mailto = $row["vm_mailto"];
									//$vm_attach_file = $row["vm_attach_file"];
									//$vm_keep_local_after_email = $row["vm_keep_local_after_email"];
									//$user_context = $row["user_context"];
									//$call_group = $row["call_group"];
									//$auth_acl = $row["auth_acl"];
									//$cidr = $row["cidr"];
									//$sip_force_contact = $row["sip_force_contact"];
									//$enabled = $row["enabled"];
									//$description = $row["description"]
								}
							}
							unset ($prep_statement_2);

						//cleanup any remaining variables
							for ($i = 1; $i <= 100; $i++) {
								$file_contents = str_replace("{v_line".$i."_server_address}", "", $file_contents);
								$file_contents = str_replace("{v_line".$i."_displayname}", "", $file_contents);
								$file_contents = str_replace("{v_line".$i."_shortname}", "", $file_contents);
								$file_contents = str_replace("{v_line".$i."_user_id}", "", $file_contents);
								$file_contents = str_replace("{v_line".$i."_user_password}", "", $file_contents);
							}

						//replace {v_mac} in the file name
							if (substr($phone_mac_address, 0, 6) == "00085d") {
								//upper case the mac address for aastra phones
								$file_name = str_replace("{v_mac}", strtoupper($phone_mac_address), $file_name);
							}
							else {
								//all other phones
								$file_name = str_replace("{v_mac}", $phone_mac_address, $file_name);
							}

						//write the configuration to the directory
							if (strlen($_SESSION['switch']['provision']['dir']) > 0) {
								$dir_array = explode(";", $_SESSION['switch']['provision']['dir']);
								foreach($dir_array as $directory) {
									//echo $directory.'/'.$file_name."\n";
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