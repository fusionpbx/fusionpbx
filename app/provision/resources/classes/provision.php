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
	Copyright (C) 2014
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
include "root.php";

//define the directory class
	class provision {
		public $db;
		public $domain_uuid;
		public $domain_name;
		public $template_dir;
		public $mac;

		public function __construct() {
			//get the database object
				global $db;
				$this->db = $db;
			//set the default template directory
				switch (PHP_OS) {
				case "Linux":
					//set the default template dir
						if (strlen($this->template_dir) == 0) {
							if (file_exists('/etc/fusionpbx/templates/provision')) {
								$this->template_dir = '/etc/fusionpbx/templates/provision';
							}
						}
					break;
				case "FreeBSD":
					//if the FreeBSD port is installed use the following paths by default.
						if (file_exists('/usr/local/etc/fusionpbx/templates/provision')) {
							if (strlen($this->template_dir) == 0) {
								$this->template_dir = '/usr/local/etc/fusionpbx/templates/provision';
							}
						}
						else {
							if (strlen($this->template_dir) == 0) {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
					break;
				case "NetBSD":
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
					break;
				case "OpenBSD":
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
					break;
				default:
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				}

			//normalize the mac address
				if (isset($this->mac)) {
					$this->mac = strtolower(preg_replace('#[^a-fA-F0-9./]#', '', $this->mac));
				}
		}

		public function __destruct() {
			foreach ($this as $key => $value) {
				unset($this->$key);
			}
		}

		public function get_domain_uuid() {
			return $this->domain_uuid;
		}

		//define the function which checks to see if the mac address exists in devices
		private function mac_exists($mac) {
			//normalize the mac address
				$mac = strtolower(preg_replace('#[^a-fA-F0-9./]#', '', $mac));
			//check in the devices table for a specific mac address
				$sql = "SELECT count(*) as count FROM v_devices ";
				$sql .= "WHERE device_mac_address=:mac ";
				$prep_statement = $this->db->prepare(check_sql($sql));
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

		function render() {

			//get the variables
				$domain_uuid = $this->domain_uuid;
				$device_template = $this->device_template;
				$template_dir = $this->template_dir;
				$mac = $this->mac;
				$file = $this->file;

			//get the domain_name
				if (strlen($domain_name) == 0) {
					$sql = "SELECT domain_name FROM v_domains ";
					$sql .= "WHERE domain_uuid=:domain_uuid ";
					$prep_statement = $this->db->prepare(check_sql($sql));
					if ($prep_statement) {
						//use the prepared statement
							$prep_statement->bindParam(':domain_uuid', $domain_uuid);
							$prep_statement->execute();
							$row = $prep_statement->fetch();
							unset($prep_statement);
						//set the variables from values in the database
							$domain_name = $row["domain_name"];
					}
				}

			//build the provision array
				foreach($_SESSION['provision'] as $key=>$val) {
					if (strlen($val['var']) > 0) { $value = $val['var']; }
					if (strlen($val['text']) > 0) { $value = $val['text']; }
					$provision[$key] = $value;
				}

			//check to see if the mac_address exists in v_devices
				if ($this->mac_exists($mac)) {
					//get the device_template
						//if (strlen($device_template) == 0) {
							$sql = "SELECT * FROM v_devices ";
							$sql .= "WHERE device_mac_address=:mac ";
							//$sql .= "WHERE device_mac_address= '$mac' ";
							$prep_statement_2 = $this->db->prepare(check_sql($sql));
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
						//}

					//find a template that was defined on another phone and use that as the default.
						if (strlen($device_template) == 0) {
							$sql = "SELECT * FROM v_devices ";
							$sql .= "WHERE device_template LIKE '%/%' ";
							$sql .= "AND domain_uuid=:domain_uuid ";
							$prep_statement_3 = $this->db->prepare(check_sql($sql));
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
						$this->db->exec(check_sql($sql));
						unset($sql);
				}

			//get the device settings table in the provision category and update the provision array
				$sql = "SELECT * FROM v_device_settings ";
				$sql .= "WHERE device_uuid = '".$device_uuid."' ";
				$sql .= "AND device_setting_enabled = 'true' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				$result_count = count($result);
				foreach($result as $row) {
					$key = $row['device_setting_subcategory'];
					$value = $row['device_setting_value'];
					$provision[$key] = $value;
				}
				unset ($prep_statement);

			//initialize a template object
				$view = new template();
				if (strlen($_SESSION['provision']['template_engine']['text']) > 0) {
					$view->engine = $_SESSION['provision']['template_engine']['text']; //raintpl, smarty, twig
				}
				else {
					$view->engine = "smarty";
				}
				$view->template_dir = $template_dir ."/".$device_template."/";
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
					$prep_statement = $this->db->prepare(check_sql($sql));
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
					$prep_statement = $this->db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				//assign the keys array
					$view->assign("keys", $result);
				//set the variables
					foreach($result as $row) {
						//set the variables
							$device_key_category = $row['device_key_category'];
							$device_key_id = $row['device_key_id']; //1
							$device_key_type = $row['device_key_type']; //line
							$device_key_line = $row['device_key_line'];
							$device_key_value = $row['device_key_value']; //1
							$device_key_extension = $row['device_key_extension'];
							$device_key_label = $row['device_key_label']; //label

						//grandstream modes are different based on the category
							if ($device_vendor == "grandstream") {
								if ($device_key_category == "line") {
									switch ($device_key_type) {
										case "line": $device_key_type  = "0"; break;
										case "shared line": $device_key_type  = "1"; break;
										case "speed dial": $device_key_type  = "10"; break;
										case "blf": $device_key_type  = "11"; break;
										case "presence watcher": $device_key_type  = "12"; break;
										case "eventlist blf": $device_key_type  = "13"; break;
										case "speed dial active": $device_key_type  = "14"; break;
										case "dial dtmf": $device_key_type  = "15"; break;
										case "voicemail": $device_key_type  = "16"; break;
										case "call return": $device_key_type  = "17"; break;
										case "transfer": $device_key_type  = "18"; break;
										case "call park": $device_key_type  = "19"; break;
										case "intercom": $device_key_type  = "20"; break;
										case "ldap search": $device_key_type  = "21"; break;
									}
								}
								if ($device_key_category == "memory") {
										switch ($device_key_type) {
											case "speed dial": $device_key_type  = "0"; break;
											case "blf": $device_key_type  = "1"; break;
											case "presence watcher": $device_key_type  = "2"; break;
											case "eventlist blf": $device_key_type  = "3"; break;
											case "speed dial active": $device_key_type  = "4"; break;
											case "dial dtmf": $device_key_type  = "5"; break;
											case "voicemail": $device_key_type  = "6"; break;
											case "call return": $device_key_type  = "7"; break;
											case "transfer": $device_key_type  = "8"; break;
											case "call park": $device_key_type  = "9"; break;
											case "intercom": $device_key_type  = "10"; break;
											case "ldap search": $device_key_type  = "11"; break;
										}
								}
							}

						//assign the variables
							if (strlen($device_key_category) == 0) {
								$view->assign("key_id_".$device_key_id, $device_key_id);
								$view->assign("key_type_".$device_key_id, $device_key_type);
								$view->assign("key_line_".$device_key_id, $device_key_line);
								$view->assign("key_value_".$device_key_id, $device_key_value);
								$view->assign("key_extension_".$device_key_id, $device_key_extension);
								$view->assign("key_label_".$device_key_id, $device_key_label);
							}
							else {
								$view->assign($device_key_category."_key_id_".$device_key_id, $device_key_id);
								$view->assign($device_key_category."_key_type_".$device_key_id, $device_key_type);
								$view->assign($device_key_category."_key_line_".$device_key_id, $device_key_line);
								$view->assign($device_key_category."_key_value_".$device_key_id, $device_key_value);
								$view->assign($device_key_category."_key_extension_".$device_key_id, $device_key_extension);
								$view->assign($device_key_category."_key_label_".$device_key_id, $device_key_label);
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

				//set the template directory
					if (strlen($provision["template_dir"]) > 0) {
						$template_dir = $provision["template_dir"];
					}

				//if the domain name directory exists then only use templates from it
					if (is_dir($template_dir.'/'.$domain_name)) {
						$device_template = $domain_name.'/'.$device_template;
					}

				//if $file is not provided then look for a default file that exists
					if (strlen($file) == 0) {
						if (file_exists($template_dir."/".$device_template ."/{\$mac}")) {
							$file = "{\$mac}";
						}
						elseif (file_exists($template_dir."/".$device_template ."/{\$mac}.xml")) {
							$file = "{\$mac}.xml";
						}
						elseif (file_exists($template_dir."/".$device_template ."/{\$mac}.cfg")) {
							$file = "{\$mac}.cfg";
						}
						else {
							echo "file not found";
							exit;
						}
					}
					else {
						//make sure the file exists
						if (!file_exists($template_dir."/".$device_template ."/".$file)) {
							echo "file not found";
							exit;
						}
					}

				//output template to string for header processing
					$file_contents = $view->render($file);

				//log file for testing
					//$tmp_file = "/tmp/provisioning_log.txt";
					//$fh = fopen($tmp_file, 'w') or die("can't open file");
					//$tmp_string = $mac."\n";
					//fwrite($fh, $tmp_string);
					//fclose($fh);

				//returned the rendered template
					return $file_contents;

		} //end render function


		function write() {

			//set default variables
				$dir_count = 0;
				$file_count = 0;
				$row_count = 0;
				$tmp_array = '';
				$i = 0;

			//get the devices
				$sql = "select * from v_devices ";
				//$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				foreach ($result as &$row) {
					//get the values from the database and set as variables
						$domain_uuid = $row["domain_uuid"];
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

					//loop through the provision template directory
						clearstatcache();
						$dir_list = '';
						$file_list = '';
						if (strlen($device_template) > 0) {
							$dir_list = opendir($this->template_dir."/".$device_template);
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
						}

						//asort($dir_array);
						foreach ($dir_array as $new_path){
								$level = explode('/',$new_path);
								if (is_dir($new_path)) {
									$dir_name = end($level);
									//$file_list .=  "$dir_name\n";
									//$dir_list .= recur_dir($new_path);
								}
								else {
									$file_name = end($level);
									//debug information
										//$file_size = round(filesize($new_path)/1024, 2);
										//echo $this->template_dir."/".$device_template."/".$file_name." $file_size\n";
									//write the configuration to the directory
										if (strlen($_SESSION['switch']['provision']['dir']) > 0) {
											$dir_array = explode(";", $_SESSION['switch']['provision']['dir']);
											foreach($dir_array as $directory) {

												if (file_exists($this->template_dir."/".$device_template."/".$file_name)) {
													//output template to string for header processing
														//output template to string for header processing
															$prov->domain_uuid = $domain_uuid;
															$this->mac = $device_mac_address;
															$this->file = $file_name;
															$file_contents = $this->render();

													//replace {$mac} in the file name
														if ($device_vendor == "aastra" || $device_vendor == "cisco") {
															//upper case the mac address for aastra phones
															$file_name = str_replace("{\$mac}", strtoupper($device_mac_address), $file_name);
														}
														else {
															//all other phones
															$file_name = str_replace("{\$mac}", $device_mac_address, $file_name);
														}

													//write the file
														//echo $directory.'/'.$file_name."\n";
														$fh = fopen($directory.'/'.$file_name,"w") or die("Unable to write to $directory for provisioning. Make sure the path exists and permissons are set correctly.");
														fwrite($fh, $file_contents);
														fclose($fh);
												}
											}
											unset($file_name);
										}
								}
						} //end for each
						closedir($dir_list);
						//echo "<hr size='1'>\n";
				}
				unset ($prep_statement);
		} //end write function
	} //end provision class

?>