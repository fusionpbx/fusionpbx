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
	Copyright (C) 2014-2016
	All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/
include "root.php";

//define the provision class
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
				if (PHP_OS == "Linux") {
					//set the default template dir
						if (strlen($this->template_dir) == 0) {
							if (file_exists('/etc/fusionpbx/resources/templates/provision')) {
								$this->template_dir = '/etc/fusionpbx/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
				} elseif (PHP_OS == "FreeBSD") {
					//if the FreeBSD port is installed use the following paths by default.
						if (file_exists('/usr/local/etc/fusionpbx/resources/templates/provision')) {
							if (strlen($this->template_dir) == 0) {
								$this->template_dir = '/usr/local/etc/fusionpbx/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
						else {
							if (strlen($this->template_dir) == 0) {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
							else {
								$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
							}
						}
				} elseif (PHP_OS == "NetBSD") {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				} elseif (PHP_OS == "OpenBSD") {
					//set the default template_dir
						if (strlen($this->template_dir) == 0) {
							$this->template_dir = $_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/resources/templates/provision';
						}
				} else {
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
						$mac_exists = true;
					}
					else {
						$mac_exists = false;
					}
				}
				else {
					$mac_exists = false;
				}
				if ($mac_exists) {
					return true;
				}
				else {
					//log the invalid mac address attempt to the syslog server
						openlog("FusionPBX", LOG_PID | LOG_PERROR, LOG_LOCAL0);
						syslog(LOG_WARNING, '['.$_SERVER['REMOTE_ADDR']."] invalid mac address ".$mac);
						closelog();
					//invalid mac address return false
						return false;
				}
		}

		//set the mac address in the correct format for the specific vendor
		public function format_mac($mac, $vendor) {
			switch (strtolower($vendor)) {
			case "aastra":
				$mac = strtoupper($mac);
				break;
			case "cisco":
				$mac = strtoupper($mac);
				break;
			case "linksys":
				$mac = strtolower($mac);
				break;
			case "mitel":
				$mac = strtoupper($mac);
				break;
			case "polycom":
				$mac = strtolower($mac);
				break;
			case "snom":
				$mac = strtolower($mac);
				break;
			case "escene":
				$mac = strtolower($mac);
				break;
			default:
				$mac = strtolower($mac);
				$mac = substr($mac, 0,2).'-'.substr($mac, 2,2).'-'.substr($mac, 4,2).'-'.substr($mac, 6,2).'-'.substr($mac, 8,2).'-'.substr($mac, 10,2);
			}
			return $mac;
		}

		//send http error
		private function http_error($error) {
			if ($error === "404") {
				header("HTTP/1.0 404 Not Found");
				echo "<html>\n";
				echo "<head><title>404 Not Found</title></head>\n";
				echo "<body bgcolor=\"white\">\n";
				echo "<center><h1>404 Not Found</h1></center>\n";
				echo "<hr><center>nginx/1.12.1</center>\n";
				echo "</body>\n";
				echo "</html>\n";
			}
			exit();
		}

		//define a function to check if a contact exists in the contacts array
		private function contact_exists($contacts, $uuid) {
			if (is_array($contacts[$uuid])) {
				return true;
			}
			else {
				return false;
			}
		}

		private function contact_append(&$contacts, &$line, $domain_uuid, $device_user_uuid, $is_group){
			$sql = "select c.contact_uuid, c.contact_organization, c.contact_name_given, c.contact_name_family, ";
			$sql .= "c.contact_type, c.contact_category, p.phone_label,";
			$sql .= "p.phone_number, p.phone_extension, p.phone_primary ";
			$sql .= "from v_contacts as c, v_contact_phones as p ";
			$sql .= "where c.contact_uuid = p.contact_uuid ";
			$sql .= "and p.phone_type_voice = '1' ";
			$sql .= "and c.domain_uuid = '$domain_uuid' ";
			if ($is_group) {
				$sql .= "and c.contact_uuid in ( ";
				$sql .= "	select contact_uuid from v_contact_groups ";
				$sql .= "	where group_uuid in ( ";
				$sql .= "		select group_uuid from v_group_users ";
				$sql .= "		where user_uuid = '$device_user_uuid' ";
				$sql .= "		and domain_uuid = '$domain_uuid' ";
				$sql .= "	)) ";
			}
			else {
				$sql .= "and c.contact_uuid in ( ";
				$sql .= "	select contact_uuid from v_contact_users ";
				$sql .= "	where user_uuid = '$device_user_uuid' ";
				$sql .= "	and domain_uuid = '$domain_uuid' ";
				$sql .= ") ";
			}
			$prep_statement = $this->db->prepare(check_sql($sql));
			$prep_statement->execute();
			$user_contacts = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			unset($prep_statement, $sql);

			if (is_array($user_contacts)) {
				foreach ($user_contacts as &$row) {
					$uuid = $row['contact_uuid'];
					$phone_label = strtolower($row['phone_label']);
					$contact_category = strtolower($row['contact_category']);

					$contact = array();
					$contacts[] = &$contact;
					$contact['category']             = $is_group ? 'groups' : 'users';
					$contact['contact_uuid']         = $row['contact_uuid'];
					$contact['contact_type']         = $row['contact_type'];
					$contact['contact_category']     = $row['contact_category'];
					$contact['contact_organization'] = $row['contact_organization'];
					$contact['contact_name_given']   = $row['contact_name_given'];
					$contact['contact_name_family']  = $row['contact_name_family'];
					$contact['numbers']              = array();

					$numbers = &$contact['numbers'];

					if (($row['phone_primary'] == '1') || (!isset($contact['phone_number']))) {
						$contact['phone_label']		= $phone_label;
						$contact['phone_number']	= $row['phone_number'];
						$contact['phone_extension']	= $row['phone_extension'];
					}

					$numbers[] = array(
						line_number			=> $line['line_number'],
						phone_label			=> $phone_label,
						phone_number		=> $row['phone_number'],
						phone_extension		=> $row['phone_extension'],
						phone_primary		=> $row['phone_primary'],
					);

					$contact['phone_number_' . $phone_label] = $row['phone_number'];
					unset($contact, $numbers, $uuid, $phone_label);
				}
			}
		}

		public function render() {

			//debug
				$debug = $_REQUEST['debug']; // array

			//get the variables
				$domain_uuid = $this->domain_uuid;
				$device_template = $this->device_template;
				$template_dir = $this->template_dir;
				$mac = $this->mac;
				$file = $this->file;

			//set the mac address to lower case to be consistent with the database
				$mac = strtolower($mac);

			//get the device template
				if (strlen($_REQUEST['template']) > 0) {
					$device_template = $_REQUEST['template'];
					$search = array('..', '/./');
					$device_template = str_replace($search, "", $device_template);
					$device_template = str_replace('//', '/', $device_template);
				}

			//remove ../ and slashes in the file name
				$search = array('..', '/', '\\', '/./', '//');
				$file = str_replace($search, "", $file);

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
				$provision = Array();
				if (is_array($_SESSION['provision'])) {
					foreach($_SESSION['provision'] as $key=>$val) {
						if (strlen($val['var']) > 0) { $value = $val['var']; }
						if (strlen($val['text']) > 0) { $value = $val['text']; }
						if (strlen($val['boolean']) > 0) { $value = $val['boolean']; }
						if (strlen($val['numeric']) > 0) { $value = $val['numeric']; }
						if (strlen($value) > 0) { $provision[$key] = $value; }
						unset($value);
					}
				}

			//check to see if the mac_address exists in devices
				if (strlen($_REQUEST['user_id']) == 0 || strlen($_REQUEST['userid']) == 0) {
					if ($this->mac_exists($mac)) {
						//get the device_template
							if (strlen($device_template) == 0) {
								$sql = "SELECT * FROM v_devices ";
								$sql .= "WHERE device_mac_address=:mac ";
								if($provision['http_domain_filter'] == "true") {
									$sql  .= "AND domain_uuid=:domain_uuid ";
								}
								$prep_statement_2 = $this->db->prepare(check_sql($sql));
								if ($prep_statement_2) {
									//use the prepared statement
										$prep_statement_2->bindParam(':mac', $mac);
										if($provision['http_domain_filter'] == "true") {
											$prep_statement_2->bindParam(':domain_uuid', $domain_uuid);
										}
										$prep_statement_2->execute();
										$row = $prep_statement_2->fetch();
									//checks either device enabled
										if($row['device_enabled'] != 'true'){
											if ($_SESSION['provision']['debug']['boolean'] == 'true'){
												echo "<br/>device disabled<br/>";
											}
											else {
												$this->http_error('404');
											}
											exit;
										}

									//set the variables from values in the database
										$device_uuid = $row["device_uuid"];
										$device_label = $row["device_label"];
										if (strlen($row["device_vendor"]) > 0) {
											$device_vendor = strtolower($row["device_vendor"]);
										}
										$device_user_uuid = $row["device_user_uuid"];
										$device_model = $row["device_model"];
										$device_firmware_version = $row["device_firmware_version"];
										$device_enabled = $row["device_enabled"];
										$device_template = $row["device_template"];
										$device_profile_uuid = $row["device_profile_uuid"];
										$device_description = $row["device_description"];
								}
							}

						//find a template that was defined on another phone and use that as the default.
							if (strlen($device_template) == 0) {
								$sql = "SELECT * FROM v_devices ";
								$sql .= "WHERE domain_uuid=:domain_uuid ";
								$sql .= "AND device_enabled='true' ";
								$sql .= "limit 1 ";
								$prep_statement_3 = $this->db->prepare(check_sql($sql));
								if ($prep_statement_3) {
									$prep_statement_3->bindParam(':domain_uuid', $domain_uuid);
									$prep_statement_3->execute();
									$row = $prep_statement_3->fetch();
									$device_label = $row["device_label"];
									$device_vendor = strtolower($row["device_vendor"]);
									$device_model = $row["device_model"];
									$device_firmware_version = $row["device_firmware_version"];
									$device_enabled = $row["device_enabled"];
									$device_template = $row["device_template"];
									$device_profile_uuid = $row["device_profile_uuid"];
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
							if ($_SESSION['provision']['auto_insert_enabled']['boolean'] == "true" and strlen($domain_uuid) > 0) {
								$device_uuid = uuid();
								$sql = "INSERT INTO v_devices ";
								$sql .= "(";
								$sql .= "domain_uuid, ";
								$sql .= "device_uuid, ";
								$sql .= "device_mac_address, ";
								$sql .= "device_vendor, ";
								$sql .= "device_model, ";
								$sql .= "device_enabled, ";
								$sql .= "device_template, ";
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
								$sql .= "'auto {$_SERVER['HTTP_USER_AGENT']}' ";
								$sql .= ")";
								$this->db->exec(check_sql($sql));
								unset($sql);
							}
					}
				}

			//alternate device_uuid
				if (strlen($device_uuid) > 0) {
					$sql = "SELECT * FROM v_devices ";
					$sql .= "WHERE device_uuid = '".$device_uuid."' ";
					$sql .= "AND device_enabled = 'true' ";
					if($provision['http_domain_filter'] == "true") {
						$sql  .= "AND domain_uuid=:domain_uuid ";
					}
					$prep_statement_3 = $this->db->prepare(check_sql($sql));
					if ($prep_statement_3) {
						if($provision['http_domain_filter'] == "true") {
							$prep_statement_3->bindParam(':domain_uuid', $domain_uuid);
						}
						$prep_statement_3->execute();
						$row = $prep_statement_3->fetch();
						$device_uuid_alternate = $row["device_uuid_alternate"];
						if (is_uuid($device_uuid_alternate)) {
							//override the original device_uuid
								$device_uuid = $device_uuid_alternate;
							//get the new devices information
								$sql = "SELECT * FROM v_devices ";
								$sql .= "WHERE device_uuid = '".$device_uuid."' ";
								if($provision['http_domain_filter'] == "true") {
									$sql  .= "AND domain_uuid=:domain_uuid ";
								}
								$prep_statement_4 = $this->db->prepare(check_sql($sql));
								if ($prep_statement_4) {
									if($provision['http_domain_filter'] == "true") {
										$prep_statement_4->bindParam(':domain_uuid', $domain_uuid);
									}
									$prep_statement_4->execute();
									$row = $prep_statement_4->fetch();
									if($row["device_enabled"] == "true") {
										$device_label = $row["device_label"];
										$device_firmware_version = $row["device_firmware_version"];
										$device_user_uuid = $row["device_user_uuid"];
										$device_enabled = $row["device_enabled"];
										//keep the original template
										$device_profile_uuid = $row["device_profile_uuid"];
										$device_description = $row["device_description"];
									}
								}
								unset($prep_statement_4);
						}
					}
					unset($prep_statement_3);
				}

			//get the device settings table in the provision category and update the provision array
				if (strlen($device_uuid) > 0) {
					$sql = "SELECT * FROM v_device_settings ";
					$sql .= "WHERE device_uuid = '".$device_uuid."' ";
					$sql .= "AND device_setting_enabled = 'true' ";
					$prep_statement = $this->db->prepare(check_sql($sql));
					$prep_statement->execute();
					$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
					$result_count = count($result);
					if (is_array($result)) {
						foreach($result as $row) {
							$key = $row['device_setting_subcategory'];
							$value = $row['device_setting_value'];
							$provision[$key] = $value;
						}
					}
					unset ($prep_statement);
				}

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

				//create a mac address with back slashes for backwards compatability
					$mac_dash = substr($mac, 0,2).'-'.substr($mac, 2,2).'-'.substr($mac, 4,2).'-'.substr($mac, 6,2).'-'.substr($mac, 8,2).'-'.substr($mac, 10,2);

				//get the provisioning information from device lines table
					if (strlen($device_uuid) > 0) {
						//get the device lines array
							$sql = "select * from v_device_lines ";
							$sql .= "where device_uuid = '".$device_uuid."' ";
							$sql .= "and (enabled = 'true' or enabled is null or enabled = '') ";
							$prep_statement = $this->db->prepare(check_sql($sql));
							$prep_statement->execute();
							$device_lines = $prep_statement->fetchAll(PDO::FETCH_NAMED);
						//assign the keys array
							$view->assign("lines", $device_lines);
						//set the variables
							if (is_array($device_lines)) {
								foreach($device_lines as $row) {
									//set the variables
										$line_number = $row['line_number'];
										$register_expires = $row['register_expires'];
										$sip_transport = strtolower($row['sip_transport']);
										$sip_port = $row['sip_port'];

									//set defaults
										if (strlen($register_expires) == 0) { $register_expires = "120"; }
										if (strlen($sip_transport) == 0) { $sip_transport = "tcp"; }
										if (strlen($sip_port) == 0) {
											if ($line_number == "" || $line_number == "1") {
												$sip_port = "5060";
											}
											else {
												$sip_port = "506".($line_number + 1);
											}
										}

									//set a lines array index is the line number
										$lines[$line_number]['register_expires'] = $register_expires;
										$lines[$line_number]['sip_transport'] = strtolower($sip_transport);
										$lines[$line_number]['sip_port'] = $sip_port;
										$lines[$line_number]['server_address'] = $row["server_address"];
										$lines[$line_number]['outbound_proxy'] = $row["outbound_proxy_primary"];
										$lines[$line_number]['outbound_proxy_primary'] = $row["outbound_proxy_primary"];
										$lines[$line_number]['outbound_proxy_secondary'] = $row["outbound_proxy_secondary"];
										$lines[$line_number]['display_name'] = $row["display_name"];
										$lines[$line_number]['auth_id'] = $row["auth_id"];
										$lines[$line_number]['user_id'] = $row["user_id"];
										$lines[$line_number]['password'] = $row["password"];

									//assign the variables for line one - short name
										if ($line_number == "1") {
											$view->assign("server_address", $row["server_address"]);
											$view->assign("outbound_proxy", $row["outbound_proxy_primary"]);
											$view->assign("outbound_proxy_primary", $row["outbound_proxy_primary"]);
											$view->assign("outbound_proxy_secondary", $row["outbound_proxy_secondary"]);
											$view->assign("display_name", $row["display_name"]);
											$view->assign("auth_id", $row["auth_id"]);
											$view->assign("user_id", $row["user_id"]);
											$view->assign("user_password", $row["password"]);
											$view->assign("sip_transport", $sip_transport);
											$view->assign("sip_port", $sip_port);
											$view->assign("register_expires", $register_expires);
										}

									//assign the variables with the line number as part of the name
										$view->assign("server_address_".$line_number, $row["server_address"]);
										$view->assign("outbound_proxy_".$line_number, $row["outbound_proxy_primary"]);
										$view->assign("outbound_proxy_primary_".$line_number, $row["outbound_proxy_primary"]);
										$view->assign("outbound_proxy_secondary_".$line_number, $row["outbound_proxy_secondary"]);
										$view->assign("display_name_".$line_number, $row["display_name"]);
										$view->assign("auth_id_".$line_number, $row["auth_id"]);
										$view->assign("user_id_".$line_number, $row["user_id"]);
										$view->assign("user_password_".$line_number, $row["password"]);
										$view->assign("sip_transport_".$line_number, $sip_transport);
										$view->assign("sip_port_".$line_number, $sip_port);
										$view->assign("register_expires_".$line_number, $register_expires);
								}
							}
							unset ($prep_statement);
					}

				//get the list of contact directly assigned to the user
					if (strlen($device_user_uuid) > 0 and strlen($domain_uuid) > 0) {
						//get the contacts assigned to the groups and add to the contacts array
							if ($_SESSION['provision']['contact_groups']['boolean'] == "true") {
								$this->contact_append($contacts, $line, $domain_uuid, $device_user_uuid, true);
							}

						//get the contacts assigned to the user and add to the contacts array
							if ($_SESSION['provision']['contact_users']['boolean'] == "true") {
								$this->contact_append($contacts, $line, $domain_uuid, $device_user_uuid, false);
							}
					}

				//get the extensions and add them to the contacts array
					if (strlen($device_uuid) > 0 and strlen($domain_uuid) > 0 and $_SESSION['provision']['contact_extensions']['boolean'] == "true") {
						//get contacts from the database
							$sql = "select extension_uuid as contact_uuid, directory_full_name, ";
							$sql .= "effective_caller_id_name, effective_caller_id_number, ";
							$sql .= "number_alias, extension ";
							$sql .= "from v_extensions ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and enabled = 'true' ";
							$sql .= "order by number_alias, extension asc ";
							$prep_statement = $this->db->prepare($sql);
							if ($prep_statement) {
								$prep_statement->execute();
								$extensions = $prep_statement->fetchAll(PDO::FETCH_NAMED);
								if (is_array($extensions)) {
									foreach ($extensions as $row) {
										//get the contact_uuid
											$uuid = $row['contact_uuid'];
										//get the names
											if (strlen($row['directory_full_name']) > 0) {
												$name_array = explode(" ", $row['directory_full_name']);
											} else {
												$name_array = explode(" ", $row['effective_caller_id_name']);
											}
											$contact_name_given = array_shift($name_array);
											$contact_name_family = trim(implode(' ', $name_array));
										//get the phone_extension
											if (is_numeric($row['extension'])) {
												$phone_extension = $row['extension'];
											}
											else {
												$phone_extension = $row['number_alias'];
											}
										//save the contact array values
											$contacts[$uuid]['category'] = 'extensions';
											$contacts[$uuid]['contact_uuid'] = $row['contact_uuid'];
											$contacts[$uuid]['contact_category'] = 'extensions';
											$contacts[$uuid]['contact_name_given'] = $contact_name_given;
											$contacts[$uuid]['contact_name_family'] = $contact_name_family;
											$contacts[$uuid]['phone_extension'] = $phone_extension;
										//unset the variables
											unset($name_array, $contact_name_given, $contact_name_family, $phone_extension);
									}
								}
							}
					}

				//assign the contacts array to the template
					if (is_array($contacts)) {
						$view->assign("contacts", $contacts);
						unset($contacts);
					}

				//get the provisioning information from device keys
					if (strlen($device_uuid) > 0) {

						//get the device keys array
							$sql = "SELECT * FROM v_device_keys ";
							$sql .= "WHERE (";
							$sql .= "device_uuid = '".$device_uuid."' ";
							if (strlen($device_profile_uuid) > 0) {
								$sql .= "or device_profile_uuid = '".$device_profile_uuid."' ";
							}
							$sql .= ") ";
							if (strtolower($device_vendor) == 'escene'){
								$sql .= "AND (lower(device_key_vendor) = 'escene' or lower(device_key_vendor) = 'escene programmable' or device_key_vendor is null) ";
							}
							else {
								$sql .= "AND (lower(device_key_vendor) = '".$device_vendor."' or device_key_vendor is null) ";
							}
							$sql .= "ORDER BY ";
							$sql .= "device_key_vendor ASC, ";
							$sql .= "CASE device_key_category ";
							$sql .= "WHEN 'line' THEN 1 ";
							$sql .= "WHEN 'memory' THEN 2 ";
							$sql .= "WHEN 'programmable' THEN 3 ";
							$sql .= "WHEN 'expansion' THEN 4 ";
							$sql .= "ELSE 100 END, ";
							if ($GLOBALS['db_type'] == "mysql") {
								$sql .= "device_key_id ASC, ";
							}
							else {
								$sql .= "CAST(device_key_id as numeric) ASC, ";
							}
							$sql .= "CASE WHEN device_uuid IS NULL THEN 0 ELSE 1 END ASC ";
							$prep_statement = $this->db->prepare(check_sql($sql));
							$prep_statement->execute();
							$keys = $prep_statement->fetchAll(PDO::FETCH_NAMED);

						//override profile keys with device keys
							if (is_array($keys)) {
								foreach($keys as $row) {
									//set the variables
									$id = $row['device_key_id'];
									$category = $row['device_key_category'];

									//build the device keys array
									$device_keys[$category][$id] = $row;
									if (is_uuid($row['device_profile_uuid'])) {
										$device_keys[$category][$id]['device_key_owner'] = "profile";
									}
									else {
										$device_keys[$category][$id]['device_key_owner'] = "device";
									}

									//kept temporarily for backwards comptability to allow custom templates to be updated
									$device_keys[$id] = $row;
									if (is_uuid($row['device_profile_uuid'])) {
										$device_keys[$id]['device_key_owner'] = "profile";
									}
									else {
										$device_keys[$id]['device_key_owner'] = "device";
									}
								}
							}
							unset($keys);
					}

				//debug information
					if ($debug == "array") {
						echo "<pre>\n";
						print_r($device_keys);
						echo "<pre>\n";
						exit;
					}

				//set the variables key and values
					$x = 1;
					$variables['domain_name'] = $domain_name;
					$variables['user_id'] = $lines[$x]['user_id'];
					$variables['auth_id'] = $lines[$x]['auth_id'];
					$variables['extension'] = $lines[$x]['extension'];
					//$variables['password'] = $lines[$x]['password'];
					$variables['register_expires'] = $lines[$x]['register_expires'];
					$variables['sip_transport'] = $lines[$x]['sip_transport'];
					$variables['sip_port'] = $lines[$x]['sip_port'];
					$variables['server_address'] = $lines[$x]['server_address'];
					$variables['outbound_proxy'] = $lines[$x]['outbound_proxy_primary'];
					$variables['outbound_proxy_primary'] = $lines[$x]['outbound_proxy_primary'];
					$variables['outbound_proxy_secondary'] = $lines[$x]['outbound_proxy_secondary'];
					$variables['display_name'] = $lines[$x]['display_name'];

				//update the device keys by replacing variables with their values
					foreach($variables as $name => $value) {
						if (is_array($device_keys)) {
							foreach($device_keys as $k => $field) {
								if (strlen($field['device_key_uuid']) > 0) {
										$device_keys[$k]['device_key_value'] = str_replace("\${".$name."}", $value, $field['device_key_value']);
										$device_keys[$k]['device_key_extension'] = str_replace("\${".$name."}", $value, $field['device_key_extension']);
										$device_keys[$k]['device_key_label'] = str_replace("\${".$name."}", $value, $field['device_key_label']);
								}
								else {
									if (is_array($field)) {
										foreach($field as $key => $row) {
											$device_keys[$k][$key]['device_key_value'] = str_replace("\${".$name."}", $value, $row['device_key_value']);
											$device_keys[$k][$key]['device_key_extension'] = str_replace("\${".$name."}", $value, $row['device_key_extension']);
											$device_keys[$k][$key]['device_key_label'] = str_replace("\${".$name."}", $value, $row['device_key_label']);
										}
									}
								}
							}
						}
					}

				//assign the keys array
					$view->assign("keys", $device_keys);

				//set the variables
					$types = array("line", "memory", "expansion", "programmable");
					foreach ($types as $type) {
						if (is_array($device_keys[$type])) {
							foreach($device_keys[$type] as $row) {
								//set the variables
									$device_key_category = $row['device_key_category'];
									$device_key_id = $row['device_key_id']; //1
									$device_key_type = $row['device_key_type']; //line
									$device_key_line = $row['device_key_line'];
									$device_key_value = $row['device_key_value']; //1
									$device_key_extension = $row['device_key_extension'];
									$device_key_label = $row['device_key_label']; //label

								//add general variables
									$device_key_value = str_replace("\${domain_name}", $domain_name, $device_key_value);
									$device_key_extension = str_replace("\${domain_name}", $domain_name, $device_key_extension);
									$device_key_label = str_replace("\${domain_name}", $domain_name, $device_key_label);

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
										if ($device_key_category == "memory" || $device_key_category == "expansion") {
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
						}
					}
					unset ($prep_statement);

				//set the mac address in the correct format
					$mac = $this->format_mac($mac, $device_vendor);

				//replace the variables in the template in the future loop through all the line numbers to do a replace for each possible line number
					$view->assign("mac" , $mac);
					$view->assign("label", $device_label);
					$view->assign("device_label", $device_label);
					$view->assign("firmware_version", $device_firmware_version);
					$view->assign("domain_name", $domain_name);
					$view->assign("project_path", PROJECT_PATH);
					$view->assign("server1_address", $server1_address);
					$view->assign("proxy1_address", $proxy1_address);
					$view->assign("user_id",$user_id);
					$view->assign("password",$password);
					$view->assign("template",$device_template);

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
						if (!isset($provision["time_zone_offset"])) {
							$provision["time_zone_offset"] = $time_zone_offset;
						}
					}

				//set the daylight savings time
					if (!isset($provision["yealink_time_zone_start_time"])) {
						$provision["yealink_time_zone_start_time"] = $provision["daylight_savings_start_month"]."/".$provision["daylight_savings_start_day"]."/".$provision["daylight_savings_start_time"];
					}
					if (!isset($provision["yealink_time_zone_end_time"])) {
						$provision["yealink_time_zone_end_time"] = $provision["daylight_savings_stop_month"]."/".$provision["daylight_savings_stop_day"]."/".$provision["daylight_savings_stop_time"];
					}

				//replace the dynamic provision variables that are defined in default, domain, and device settings
					if (is_array($provision)) {
						foreach($provision as $key=>$val) {
							$view->assign($key, $val);
						}
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
							$this->http_error('404');
							exit;
						}
					}
					else {
						//make sure the file exists
						if (!file_exists($template_dir."/".$device_template ."/".$file)) {
							echo "file not found ".$template_dir."/".$device_template ."/".$file;
							if ($_SESSION['provision']['debug']['boolean'] == 'true'){
								echo ":$template_dir/$device_template/$file<br/>";
								echo "template_dir: $template_dir<br/>";
								echo "device_template: $device_template<br/>";
								echo "file: $file";
							}
							exit;
						}
					}

				//output template to string for header processing
					$file_contents = $view->render($file);

				//log file for testing
					if ($_SESSION['provision']['debug']['boolean'] == 'true'){
						$tmp_file = "/tmp/provisioning_log.txt";
						$fh = fopen($tmp_file, 'w') or die("can't open file");
						$tmp_string = $mac."\n";
						fwrite($fh, $tmp_string);
						fclose($fh);
					}

					$this->file = $file;
				//returned the rendered template
					return $file_contents;

		} //end render function

		function write() {
			//build the provision array
				$provision = Array();
				if (is_array($_SESSION['provision'])) {
					foreach($_SESSION['provision'] as $key=>$val) {
						if (strlen($val['var']) > 0) { $value = $val['var']; }
						if (strlen($val['text']) > 0) { $value = $val['text']; }
						if (strlen($val['boolean']) > 0) { $value = $val['boolean']; }
						if (strlen($val['numeric']) > 0) { $value = $val['numeric']; }
						if (strlen($value) > 0) { $provision[$key] = $value; }
						unset($value);
					}
				}

			//check either we have destination path to write files
				if(strlen($provision["path"]) == 0){
					return;
				}

			//get the devices from database
				$sql = "select * from v_devices ";
				//$sql .= "where domain_uuid = '".$this->domain_uuid."' ";
				$prep_statement = $this->db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
				unset ($prep_statement);

			//process each device
				if (is_array($result)) foreach ($result as &$row) {
					//get the values from the database and set as variables
						$domain_uuid = $row["domain_uuid"];
						$device_uuid = $row["device_uuid"];
						$device_mac_address = $row["device_mac_address"];
						$device_label = $row["device_label"];
						$device_vendor = strtolower($row["device_vendor"]);
						$device_model = $row["device_model"];
						$device_firmware_version = $row["device_firmware_version"];
						$device_enabled = $row["device_enabled"];
						$device_template = $row["device_template"];
						$device_username = $row["device_username"];
						$device_password = $row["device_password"];
						$device_description = $row["device_description"];

						clearstatcache();

					//loop through the provision template directory
						$dir_array = array();
						if (strlen($device_template) > 0) {
							$template_path = path_join($this->template_dir, $device_template);
							$dir_list = opendir($template_path);
							if ($dir_list) {
								$x = 0;
								while (false !== ($file = readdir($dir_list))) {
									$ignore = $file == "." || $file == ".." || substr($file, -3) == ".db" ||
										substr($file, -4) == ".svn" || substr($file, -4) == ".git";
									if (!$ignore) {
										$dir_array[] = path_join($template_path, $file);
										if ($x > 1000) { break; };
										$x++;
									}
								}
								closedir($dir_list);
								unset($x, $file);
							}
							unset($dir_list, $template_path);
						}

					//loop through the provision templates
						if (is_array($dir_array)) foreach ($dir_array as &$template_path) {
							if (is_dir($template_path)) continue;
							if (!file_exists($template_path)) continue;

							//template file name
								$file_name = basename($template_path);

							//configure device object
								$this->domain_uuid = $domain_uuid;
								$this->mac = $device_mac_address;
								$this->file = $file_name;

							//format the mac address
								$mac = $this->format_mac($device_mac_address, $device_vendor);

							//replace {$mac} in the file name
								$file_name = str_replace("{\$mac}", $mac, $file_name);

							//render and write configuration to file
								$provision_dir_array = explode(";", $provision["path"]);
								if (is_array($provision_dir_array)) foreach($provision_dir_array as $directory) {
									//destinatino file path
										$dest_path = path_join($directory, $file_name);

										if ($device_enabled == 'true'){
											//output template to string for header processing
												$file_contents = $this->render();

											//write the file
												mkdir($directory,0777,true);
												$fh = fopen($dest_path,"w") or die("Unable to write to $directory for provisioning. Make sure the path exists and permissons are set correctly.");
												fwrite($fh, $file_contents);
												fclose($fh);
										}
										else{ // device disabled
											//remove only files with `{$mac}` name
												if(strpos($template_path, '{$mac}') !== false){
													unlink($dest_path);
												}
										}

										unset($dest_path);
								}
							//unset variables
								unset($file_name, $provision_dir_array);
						} //end for each

					//unset variables
						unset($dir_array);
				}
		} //end write function

	} //end provision class

?>
